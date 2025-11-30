let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentSearch = '';

// Load brands
async function loadBrands(page = 1, search = '', limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    currentSearch = search;
    
    const tbody = document.getElementById('brandsTable');
    showLoading(tbody);
    
    const result = await apiCall(`brands.php?page=${currentPage}&limit=${currentLimit}&search=${encodeURIComponent(search)}`);
    
    if (result.success) {
        displayBrands(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', (page) => loadBrands(page, currentSearch, currentLimit), (newLimit) => {
                loadBrands(1, currentSearch, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading brands</td></tr>';
    }
}

function displayBrands(brands) {
    const tbody = document.getElementById('brandsTable');
    
    if (brands.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No brands found</td></tr>';
        return;
    }
    
    tbody.innerHTML = brands.map(brand => `
        <tr>
            <td>${brand.id}</td>
            <td><strong>${brand.name}</strong></td>
            <td>${brand.description || '-'}</td>
            <td>${formatDate(brand.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editBrand(${brand.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteBrand(${brand.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function openBrandModal(brandId = null) {
    document.getElementById('brandModalTitle').textContent = brandId ? 'Edit Brand' : 'Add Brand';
    document.getElementById('brandForm').reset();
    document.getElementById('brandId').value = '';
    
    if (brandId) {
        const result = await apiCall('brands.php');
        if (result.success && result.data) {
            const brand = result.data.find(b => b.id === brandId);
            if (brand) {
                document.getElementById('brandId').value = brand.id;
                document.getElementById('brandName').value = brand.name;
                document.getElementById('brandDescription').value = brand.description || '';
            }
        }
    }
}

async function saveBrand() {
    const form = document.getElementById('brandForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        name: document.getElementById('brandName').value,
        description: document.getElementById('brandDescription').value || null
    };
    
    const brandId = document.getElementById('brandId').value;
    const method = brandId ? 'PUT' : 'POST';
    
    if (brandId) {
        data.id = brandId;
    }
    
    const result = await apiCall('brands.php', method, data);
    
    if (result.success) {
        showAlert('Brand saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('brandModal')).hide();
        loadBrandsSummary(); // Refresh summary
        loadBrands(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error saving brand', 'danger');
    }
}

async function editBrand(id) {
    await openBrandModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('brandModal')).show();
}

async function deleteBrand(id) {
    if (!confirm('Are you sure you want to delete this brand? Products using this brand will not be affected, but the brand will be removed.')) {
        return;
    }
    
    const result = await apiCall(`brands.php?id=${id}`, 'DELETE');
    
    if (result.success) {
        showAlert('Brand deleted successfully', 'success');
        loadBrandsSummary(); // Refresh summary
        loadBrands(currentPage, currentSearch, currentLimit);
    } else {
        showAlert('Error deleting brand', 'danger');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadBrands(1, search, currentLimit);
    }, 500);
});

// Load summary
async function loadBrandsSummary() {
    try {
        const result = await apiCall('summary.php?type=brands');
        if (result.success && result.data) {
            const totalBrandsEl = document.getElementById('totalBrands');
            if (totalBrandsEl) {
                totalBrandsEl.textContent = result.data.total_brands || 0;
            }
        }
    } catch (error) {
        console.error('Error loading brands summary:', error);
    }
}

// Initial load
loadBrandsSummary();
loadBrands();

