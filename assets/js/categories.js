let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentSearch = '';

// Load categories
async function loadCategories(page = 1, search = '', limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    currentSearch = search;
    
    const tbody = document.getElementById('categoriesTable');
    showLoading(tbody);
    
    const result = await apiCall(`categories.php?page=${currentPage}&limit=${currentLimit}&search=${encodeURIComponent(search)}`);
    
    if (result.success) {
        displayCategories(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', (page) => loadCategories(page, currentSearch, currentLimit), (newLimit) => {
                loadCategories(1, currentSearch, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading categories</td></tr>';
    }
}

function displayCategories(categories) {
    const tbody = document.getElementById('categoriesTable');
    
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No categories found</td></tr>';
        return;
    }
    
    tbody.innerHTML = categories.map(category => `
        <tr>
            <td>${category.id}</td>
            <td><strong>${category.name}</strong></td>
            <td>${category.description || '-'}</td>
            <td>${formatDate(category.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editCategory(${category.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${category.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function openCategoryModal(categoryId = null) {
    document.getElementById('categoryModalTitle').textContent = categoryId ? 'Edit Category' : 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    
    if (categoryId) {
        const result = await apiCall('categories.php');
        if (result.success && result.data) {
            const category = result.data.find(c => c.id === categoryId);
            if (category) {
                document.getElementById('categoryId').value = category.id;
                document.getElementById('categoryName').value = category.name;
                document.getElementById('categoryDescription').value = category.description || '';
            }
        }
    }
}

async function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        name: document.getElementById('categoryName').value,
        description: document.getElementById('categoryDescription').value || null
    };
    
    const categoryId = document.getElementById('categoryId').value;
    const method = categoryId ? 'PUT' : 'POST';
    
    if (categoryId) {
        data.id = categoryId;
    }
    
    const result = await apiCall('categories.php', method, data);
    
    if (result.success) {
        showAlert('Category saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
        loadCategoriesSummary(); // Refresh summary
        loadCategories(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error saving category', 'danger');
    }
}

async function editCategory(id) {
    await openCategoryModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('categoryModal')).show();
}

async function deleteCategory(id) {
    if (!confirm('Are you sure you want to delete this category? Products using this category will not be affected, but the category will be removed.')) {
        return;
    }
    
    const result = await apiCall(`categories.php?id=${id}`, 'DELETE');
    
    if (result.success) {
        showAlert('Category deleted successfully', 'success');
        loadCategoriesSummary(); // Refresh summary
        loadCategories(currentPage, currentSearch, currentLimit);
    } else {
        showAlert('Error deleting category', 'danger');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadCategories(1, search, currentLimit);
    }, 500);
});

// Load summary
async function loadCategoriesSummary() {
    const result = await apiCall('summary.php?type=categories');
    if (result.success) {
        document.getElementById('totalCategories').textContent = result.data.total_categories;
    }
}

// Initial load
loadCategoriesSummary();
loadCategories();

