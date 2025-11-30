let currentPage = 1;
let currentLimit = 5; // Default to 5 rows
let currentSearch = '';

async function loadCustomers(page = 1, search = '', limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    currentSearch = search;

    const tbody = document.getElementById('customersTable');
    showLoading(tbody);

    const result = await apiCall(`customers.php?page=${currentPage}&limit=${currentLimit}&search=${encodeURIComponent(search)}`);

    if (result.success) {
        displayCustomers(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', (page) => loadCustomers(page, currentSearch, currentLimit), (newLimit) => {
                loadCustomers(1, currentSearch, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading customers</td></tr>';
    }
}

function displayCustomers(customers) {
    const tbody = document.getElementById('customersTable');

    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No customers found</td></tr>';
        return;
    }

    tbody.innerHTML = customers.map(customer => `
        <tr>
            <td><strong>${customer.name}</strong></td>
            <td>${customer.email || '-'}</td>
            <td>${customer.phone || '-'}</td>
            <td>${formatCurrency(customer.total_purchases || 0)}</td>
            <td>${formatCurrency(customer.pending_amount || 0)}</td>
            <td><span class="badge bg-info">${customer.loyalty_points || 0}</span></td>
            <td>
                <span class="badge bg-${customer.status === 'active' ? 'success' : 'secondary'}">
                    ${customer.status}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editCustomer(${customer.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(${customer.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}


async function openCustomerModal(customerId = null) {
    document.getElementById('customerModalTitle').textContent = customerId ? 'Edit Customer' : 'Add Customer';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';

    if (customerId) {
        const result = await apiCall(`customers.php?id=${customerId}`);
        if (result.success && result.data) {
            const c = result.data;
            document.getElementById('customerId').value = c.id;
            document.getElementById('customerName').value = c.name;
            document.getElementById('customerEmail').value = c.email || '';
            document.getElementById('customerPhone').value = c.phone || '';
            document.getElementById('customerAddress').value = c.address || '';
            document.getElementById('customerPoints').value = c.loyalty_points || 0;
            document.getElementById('customerCredit').value = c.credit_limit || 0;
            document.getElementById('customerStatus').value = c.status;
        }
    }
}

async function saveCustomer() {
    const form = document.getElementById('customerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        name: document.getElementById('customerName').value,
        email: document.getElementById('customerEmail').value || null,
        phone: document.getElementById('customerPhone').value || null,
        address: document.getElementById('customerAddress').value || null,
        loyalty_points: parseInt(document.getElementById('customerPoints').value) || 0,
        credit_limit: parseFloat(document.getElementById('customerCredit').value) || 0,
        status: document.getElementById('customerStatus').value
    };

    const customerId = document.getElementById('customerId').value;
    const method = customerId ? 'PUT' : 'POST';
    if (customerId) {
        data.id = customerId;
    }

    const result = await apiCall('customers.php', method, data);

    if (result.success) {
        showAlert('Customer saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
        loadCustomers(currentPage, currentSearch, currentLimit);
    } else {
        showAlert(result.message || 'Error saving customer', 'danger');
    }
}

async function editCustomer(id) {
    await openCustomerModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal')).show();
}

async function deleteCustomer(id) {
    if (!confirm('Are you sure you want to delete this customer?')) {
        return;
    }

    const result = await apiCall(`customers.php?id=${id}`, 'DELETE');

    if (result.success) {
        showAlert('Customer deleted successfully', 'success');
        loadCustomers(currentPage, currentSearch, currentLimit);
    } else {
        showAlert('Error deleting customer', 'danger');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const search = e.target.value;
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(() => {
        loadCustomers(1, search);
    }, 500);
});

// Load summary
async function loadCustomersSummary() {
    const result = await apiCall('summary.php?type=customers');
    if (result.success) {
        document.getElementById('totalCustomers').textContent = result.data.total_customers;
        document.getElementById('activeCustomers').textContent = result.data.active_customers;
        document.getElementById('totalPurchases').textContent = formatCurrency(result.data.total_purchases);
        document.getElementById('pendingAmount').textContent = formatCurrency(result.data.pending_amount);
    }
}

// Initial load
loadCustomersSummary();
loadCustomers();

