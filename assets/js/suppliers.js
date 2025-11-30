let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentSearch = '';

// Load suppliers
async function loadSuppliers(page = 1, search = '', limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    currentSearch = search;

    const tbody = document.getElementById('suppliersTable');
    showLoading(tbody);

    const result = await apiCall(`suppliers.php?page=${currentPage}&limit=${currentLimit}&search=${encodeURIComponent(search)}`);

    if (result.success) {
        displaySuppliers(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', (page) => loadSuppliers(page, currentSearch, currentLimit), (newLimit) => {
                loadSuppliers(1, currentSearch, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading suppliers</td></tr>';
    }
}

function displaySuppliers(suppliers) {
    const tbody = document.getElementById('suppliersTable');

    if (suppliers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No suppliers found</td></tr>';
        return;
    }

    tbody.innerHTML = suppliers.map(supplier => `
        <tr>
            <td><strong>${supplier.name}</strong></td>
            <td>${supplier.contact_person || '-'}</td>
            <td>${supplier.email || '-'}</td>
            <td>${supplier.phone || '-'}</td>
            <td>${supplier.total_orders || 0}</td>
            <td>${formatCurrency(supplier.total_purchases || 0)}</td>
            <td>
                <span class="badge bg-${supplier.status === 'active' ? 'success' : 'secondary'}">
                    ${supplier.status}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editSupplier(${supplier.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSupplier(${supplier.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}


async function openSupplierModal(supplierId = null) {
    document.getElementById('supplierModalTitle').textContent = supplierId ? 'Edit Supplier' : 'Add Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';

    if (supplierId) {
        const result = await apiCall(`suppliers.php?id=${supplierId}`);
        if (result.success && result.data) {
            const s = result.data;
            document.getElementById('supplierId').value = s.id;
            document.getElementById('supplierName').value = s.name;
            document.getElementById('supplierContactPerson').value = s.contact_person || '';
            document.getElementById('supplierEmail').value = s.email || '';
            document.getElementById('supplierPhone').value = s.phone || '';
            document.getElementById('supplierAddress').value = s.address || '';
            document.getElementById('supplierStatus').value = s.status;
        }
    }
}

async function saveSupplier() {
    const form = document.getElementById('supplierForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        name: document.getElementById('supplierName').value,
        contact_person: document.getElementById('supplierContactPerson').value || null,
        email: document.getElementById('supplierEmail').value || null,
        phone: document.getElementById('supplierPhone').value || null,
        address: document.getElementById('supplierAddress').value || null,
        status: document.getElementById('supplierStatus').value
    };

    const supplierId = document.getElementById('supplierId').value;
    const method = supplierId ? 'PUT' : 'POST';
    if (supplierId) {
        data.id = supplierId;
    }

    const result = await apiCall('suppliers.php', method, data);

    if (result.success) {
        showAlert('Supplier saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
        loadSuppliers(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error saving supplier', 'danger');
    }
}

async function editSupplier(id) {
    await openSupplierModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal')).show();
}

async function deleteSupplier(id) {
    if (!confirm('Are you sure you want to delete this supplier?')) {
        return;
    }

    const result = await apiCall(`suppliers.php?id=${id}`, 'DELETE');

    if (result.success) {
        showAlert('Supplier deleted successfully', 'success');
        loadSuppliers(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error deleting supplier', 'danger');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadSuppliers(1, search);
    }, 500);
});

// Load summary
async function loadSuppliersSummary() {
    const result = await apiCall('summary.php?type=suppliers');
    if (result.success) {
        document.getElementById('totalSuppliers').textContent = result.data.total_suppliers;
        document.getElementById('activeSuppliers').textContent = result.data.active_suppliers;
        document.getElementById('totalOrders').textContent = result.data.total_orders;
        document.getElementById('totalPurchases').textContent = formatCurrency(result.data.total_purchases);
    }
}

// Initial load
loadSuppliersSummary();
loadSuppliers();

