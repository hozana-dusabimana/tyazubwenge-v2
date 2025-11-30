let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentFilter = 'all';
let currentSearch = '';

async function loadStock(page = 1, limit = null, search = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    if (search !== null) {
        currentSearch = search;
        currentPage = 1; // Reset to first page when search changes
    }
    currentPage = page;
    const filter = document.getElementById('filterType').value;
    currentFilter = filter;

    const tbody = document.getElementById('stockTable');
    showLoading(tbody);

    let endpoint = `stock.php?page=${currentPage}&limit=${currentLimit}`;
    // Note: Search only works with 'all' filter type
    // For low_stock and near_expiry, search is ignored (those methods don't support search)
    if (filter !== 'all') {
        endpoint += `&type=${filter}`;
        // Don't include search for special filters
    } else if (currentSearch) {
        endpoint += `&search=${encodeURIComponent(currentSearch)}`;
    }

    const result = await apiCall(endpoint);

    if (result.success) {
        displayStock(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', loadStock, (newLimit) => {
                loadStock(1, newLimit, currentSearch);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading stock</td></tr>';
    }
}

function displayStock(stockItems) {
    const tbody = document.getElementById('stockTable');

    if (stockItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No stock items found</td></tr>';
        return;
    }

    tbody.innerHTML = stockItems.map(item => {
        const isLowStock = parseFloat(item.quantity) <= parseFloat(item.min_stock_level || 0);
        const isNearExpiry = item.near_expiry === 1;
        const isOutOfStock = parseFloat(item.quantity) <= 0;

        // Determine status
        let statusBadge = '';
        let statusText = '';
        if (isOutOfStock) {
            statusBadge = '<span class="badge bg-danger">Out of Stock</span>';
            statusText = 'Out of Stock';
        } else if (isNearExpiry) {
            statusBadge = '<span class="badge bg-danger">Near Expiry</span>';
            statusText = 'Near Expiry';
        } else if (isLowStock) {
            statusBadge = '<span class="badge bg-warning">Low Stock</span>';
            statusText = 'Low Stock';
        } else {
            statusBadge = '<span class="badge bg-success">In Stock</span>';
            statusText = 'In Stock';
        }

        // Format expiry date
        let expiryDisplay = '-';
        if (item.expiry_date) {
            const expiryDate = new Date(item.expiry_date);
            const today = new Date();
            const daysDiff = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));

            if (daysDiff < 0) {
                expiryDisplay = `<span class="text-danger">${formatDate(item.expiry_date)} (Expired)</span>`;
            } else if (daysDiff <= 30) {
                expiryDisplay = `<span class="text-warning">${formatDate(item.expiry_date)} (${daysDiff} days)</span>`;
            } else {
                expiryDisplay = formatDate(item.expiry_date);
            }
        }

        return `
            <tr class="${isLowStock ? 'table-warning' : ''} ${isNearExpiry ? 'table-danger' : ''} ${isOutOfStock ? 'table-secondary' : ''}">
                <td>${item.product_name}</td>
                <td>${item.sku}</td>
                <td>${item.category_name || '-'}</td>
                <td>
                    <strong>${item.quantity}</strong> ${item.unit || item.product_unit}
                </td>
                <td>${item.min_stock_level || 0}</td>
                <td>${expiryDisplay}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" onclick="viewStockHistory(${item.product_id}, '${item.product_name}')" title="View Stock History">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="editStock(${item.id})" title="Edit Stock">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="addQuantity(${item.product_id}, '${item.product_name}')" title="Add Quantity">
                        <i class="bi bi-plus"></i> Add Qty
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

async function addQuantity(productId, productName) {
    const quantity = prompt(`Enter quantity to add for ${productName}:`);
    if (!quantity || parseFloat(quantity) <= 0) {
        return;
    }

    const unit = prompt('Enter unit (kg/g/mg):', 'g');
    if (!['kg', 'g', 'mg'].includes(unit)) {
        showAlert('Invalid unit', 'danger');
        return;
    }

    const data = {
        product_id: productId,
        branch_id: window.BRANCH_ID || 1,
        quantity: parseFloat(quantity),
        unit: unit
    };

    const result = await apiCall('stock.php', 'POST', data);

    if (result.success) {
        showAlert('Stock added successfully', 'success');
        loadStock(currentPage);
    } else {
        showAlert('Error adding stock', 'danger');
    }
}

async function editStock(stockId) {
    const result = await apiCall(`stock.php?type=single&stock_id=${stockId}`);

    if (result.success && result.data) {
        const stock = result.data;
        document.getElementById('stockId').value = stock.id;
        document.getElementById('stockProduct').value = stock.product_id;
        document.getElementById('stockProduct').disabled = true; // Can't change product
        document.getElementById('stockQuantity').value = stock.quantity;
        document.getElementById('stockUnit').value = stock.unit;
        document.getElementById('stockBatch').value = stock.batch_number || '';
        document.getElementById('stockExpiry').value = stock.expiry_date || '';
        document.getElementById('stockSupplier').value = stock.supplier_id || '';

        // Change modal title and button text
        document.querySelector('#addStockModal .modal-title').textContent = 'Edit Stock';
        document.getElementById('saveStockBtn').textContent = 'Update Stock';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('addStockModal')).show();
    } else {
        showAlert('Error loading stock data', 'danger');
    }
}

async function saveStock() {
    const form = document.getElementById('addStockForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const stockId = document.getElementById('stockId').value;
    const productId = document.getElementById('stockProduct').value;
    const quantity = parseFloat(document.getElementById('stockQuantity').value);
    const unit = document.getElementById('stockUnit').value;
    const batchNumber = document.getElementById('stockBatch').value;
    const expiryDate = document.getElementById('stockExpiry').value;
    const supplierId = document.getElementById('stockSupplier').value;

    // Normalize empty strings to null
    const normalizedBatch = batchNumber && batchNumber.trim() !== '' ? batchNumber : null;
    const normalizedExpiry = expiryDate && expiryDate.trim() !== '' ? expiryDate : null;
    const normalizedSupplier = supplierId && supplierId !== '' && supplierId !== '0' ? supplierId : null;

    let result;
    if (stockId) {
        // Update existing stock
        const data = {
            stock_id: stockId,
            quantity: quantity,
            unit: unit,
            batch_number: normalizedBatch,
            expiry_date: normalizedExpiry,
            supplier_id: normalizedSupplier
        };
        result = await apiCall('stock.php', 'PUT', data);
    } else {
        // Add new stock
        const data = {
            product_id: productId,
            branch_id: window.BRANCH_ID || 1,
            quantity: quantity,
            unit: unit,
            batch_number: normalizedBatch,
            expiry_date: normalizedExpiry,
            supplier_id: normalizedSupplier
        };
        result = await apiCall('stock.php', 'POST', data);
    }

    if (result.success) {
        showAlert(stockId ? 'Stock updated successfully' : 'Stock added successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
        resetStockForm();
        loadStock();
    } else {
        showAlert(stockId ? 'Error updating stock' : 'Error adding stock', 'danger');
    }
}

function resetStockForm() {
    const form = document.getElementById('addStockForm');
    form.reset();
    document.getElementById('stockId').value = '';
    document.getElementById('stockProduct').disabled = false;
    document.querySelector('#addStockModal .modal-title').textContent = 'Add Stock';
    document.getElementById('saveStockBtn').textContent = 'Add Stock';
}

// Load products for dropdown
async function loadProductsForStock() {
    const result = await apiCall('products.php?limit=1000');
    const select = document.getElementById('stockProduct');

    if (result.success) {
        const filtered = result.data.filter(p => p.status === 'active');
        select.innerHTML = '<option value="">Select Product</option>' +
            filtered.map(p => `<option value="${p.id}">${p.name} (${p.sku})</option>`).join('');
    }
}

// Load suppliers for dropdown
async function loadSuppliersForStock() {
    const result = await apiCall('suppliers.php?limit=1000');
    const select = document.getElementById('stockSupplier');

    if (result.success) {
        const filtered = result.data.filter(s => s.status === 'active');
        select.innerHTML = '<option value="">Select Supplier</option>' +
            filtered.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadStock(1, null, search);
    }, 500);
});

// View Stock History
let currentHistoryPage = 1;
let currentHistoryProductId = null;

async function viewStockHistory(productId, productName) {
    currentHistoryProductId = productId;
    currentHistoryPage = 1;

    document.getElementById('stockHistoryTitle').textContent = `Stock History - ${productName}`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('stockHistoryModal')).show();

    await loadStockHistory(productId, 1);
}

async function loadStockHistory(productId, page = 1) {
    currentHistoryPage = page;

    const tbody = document.getElementById('stockHistoryTable');
    showLoading(tbody);

    const branchId = window.BRANCH_ID || 1;
    const result = await apiCall(`stock.php?type=movements&product_id=${productId}&branch_id=${branchId}&page=${page}&limit=20`);

    if (result.success) {
        displayStockHistory(result.data);
    } else {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading stock history</td></tr>';
    }
}

function displayStockHistory(movements) {
    const tbody = document.getElementById('stockHistoryTable');

    if (movements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No stock movements found</td></tr>';
        return;
    }

    tbody.innerHTML = movements.map(movement => {
        const typeColors = {
            'purchase': 'success',
            'sale': 'danger',
            'adjustment': 'warning',
            'transfer': 'info',
            'return': 'primary'
        };

        const typeLabels = {
            'purchase': 'Purchase',
            'sale': 'Sale',
            'adjustment': 'Adjustment',
            'transfer': 'Transfer',
            'return': 'Return'
        };

        const color = typeColors[movement.movement_type] || 'secondary';
        const label = typeLabels[movement.movement_type] || movement.movement_type;
        const quantity = parseFloat(movement.quantity);
        const sign = movement.movement_type === 'purchase' || movement.movement_type === 'return' ? '+' : '-';

        return `
            <tr>
                <td>${formatDateTime(movement.created_at)}</td>
                <td><span class="badge bg-${color}">${label}</span></td>
                <td>
                    <span class="${movement.movement_type === 'purchase' || movement.movement_type === 'return' ? 'text-success' : 'text-danger'}">
                        ${sign}${Math.abs(quantity)}
                    </span>
                </td>
                <td>${movement.unit}</td>
                <td>${movement.user_name || 'System'}</td>
                <td>${movement.notes || '-'}</td>
            </tr>
        `;
    }).join('');
}

// Load summary
async function loadStockSummary() {
    const result = await apiCall('summary.php?type=stock');
    if (result.success) {
        document.getElementById('totalItems').textContent = result.data.total_items;
        document.getElementById('lowStockCount').textContent = result.data.low_stock_count;
        document.getElementById('nearExpiryCount').textContent = result.data.near_expiry_count;
        document.getElementById('stockValue').textContent = formatCurrency(result.data.total_stock_value);
    }
}

// Initial load
loadStockSummary();
loadProductsForStock();
loadSuppliersForStock();
loadStock();

