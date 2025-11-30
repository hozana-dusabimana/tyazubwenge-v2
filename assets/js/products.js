let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentSearch = '';

// Load products
async function loadProducts(page = 1, search = '', limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    currentSearch = search;
    
    const tbody = document.getElementById('productsTable');
    showLoading(tbody);
    
    const result = await apiCall(`products.php?page=${currentPage}&limit=${currentLimit}&search=${encodeURIComponent(search)}`);
    
    if (result.success) {
        displayProducts(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', loadProducts, (newLimit) => {
                loadProducts(1, currentSearch, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger">Error loading products</td></tr>';
    }
}

function displayProducts(products) {
    const tbody = document.getElementById('productsTable');
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No products found</td></tr>';
        return;
    }
    
    tbody.innerHTML = products.map(product => `
        <tr>
            <td>${product.sku}</td>
            <td>${product.name}</td>
            <td>${product.category_name || '-'}</td>
            <td>${product.brand_name || '-'}</td>
            <td>${product.unit}</td>
            <td>${formatCurrency(product.retail_price)}</td>
            <td>${product.total_stock || 0} ${product.unit}</td>
            <td>
                <span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">
                    ${product.status}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editProduct(${product.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(${product.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}


async function openProductModal(productId = null) {
    document.getElementById('productModalTitle').textContent = productId ? 'Edit Product' : 'Add Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    
    // Load categories and brands
    await loadCategories();
    await loadBrands();
    
    if (productId) {
        const result = await apiCall(`products.php?id=${productId}`);
        if (result.success && result.data) {
            const p = result.data;
            document.getElementById('productId').value = p.id;
            document.getElementById('productName').value = p.name;
            document.getElementById('productSku').value = p.sku;
            document.getElementById('productBarcode').value = p.barcode || '';
            document.getElementById('productUnit').value = p.unit;
            document.getElementById('productCategory').value = p.category_id || '';
            document.getElementById('productBrand').value = p.brand_id || '';
            document.getElementById('productRetailPrice').value = p.retail_price;
            document.getElementById('productWholesalePrice').value = p.wholesale_price;
            document.getElementById('productCostPrice').value = p.cost_price;
            document.getElementById('productMinStock').value = p.min_stock_level;
            document.getElementById('productStatus').value = p.status;
            document.getElementById('productDescription').value = p.description || '';
        }
    }
}

async function saveProduct() {
    const form = document.getElementById('productForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        name: document.getElementById('productName').value,
        sku: document.getElementById('productSku').value,
        barcode: document.getElementById('productBarcode').value,
        unit: document.getElementById('productUnit').value,
        category_id: document.getElementById('productCategory').value || null,
        brand_id: document.getElementById('productBrand').value || null,
        retail_price: parseFloat(document.getElementById('productRetailPrice').value),
        wholesale_price: parseFloat(document.getElementById('productWholesalePrice').value),
        cost_price: parseFloat(document.getElementById('productCostPrice').value),
        min_stock_level: parseFloat(document.getElementById('productMinStock').value) || 0,
        status: document.getElementById('productStatus').value,
        description: document.getElementById('productDescription').value
    };
    
    const productId = document.getElementById('productId').value;
    const method = productId ? 'PUT' : 'POST';
    if (productId) {
        data.id = productId;
    }
    
    const result = await apiCall('products.php', method, data);
    
    if (result.success) {
        showAlert('Product saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
        loadProducts(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error saving product', 'danger');
    }
}

async function editProduct(id) {
    await openProductModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('productModal')).show();
}

async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }
    
    const result = await apiCall(`products.php?id=${id}`, 'DELETE');
    
    if (result.success) {
        showAlert('Product deleted successfully', 'success');
        loadProducts(currentPage, currentSearch, currentLimit);
    } else {
        showAlert('Error deleting product', 'danger');
    }
}

async function loadCategories() {
    const result = await apiCall('categories.php');
    const select = document.getElementById('productCategory');
    
    if (result.success) {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select Category</option>' +
            result.data.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('');
        if (currentValue) {
            select.value = currentValue;
        }
    }
}

async function loadBrands() {
    const result = await apiCall('brands.php');
    const select = document.getElementById('productBrand');
    
    if (result.success) {
        const currentValue = select.value;
        select.innerHTML = '<option value="">Select Brand</option>' +
            result.data.map(brand => `<option value="${brand.id}">${brand.name}</option>`).join('');
        if (currentValue) {
            select.value = currentValue;
        }
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadProducts(1, search);
    }, 500);
});

// Load summary
async function loadProductsSummary() {
    const result = await apiCall('summary.php?type=products');
    if (result.success) {
        document.getElementById('totalProducts').textContent = result.data.total_products;
        document.getElementById('activeProducts').textContent = result.data.active_products;
        document.getElementById('stockValue').textContent = formatCurrency(result.data.total_stock_value);
    }
}

// Initial load
loadProductsSummary();
loadProducts();

