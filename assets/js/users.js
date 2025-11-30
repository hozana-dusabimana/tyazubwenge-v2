let currentPage = 1;
let currentLimit = 5; // Default to 5 rows

// Load users
async function loadUsers(page = 1, limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    
    const tbody = document.getElementById('usersTable');
    showLoading(tbody);
    
    const result = await apiCall(`users.php?page=${currentPage}&limit=${currentLimit}`);
    
    if (result.success) {
        displayUsers(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', loadUsers, (newLimit) => {
                loadUsers(1, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading users</td></tr>';
    }
}

function displayUsers(users) {
    const tbody = document.getElementById('usersTable');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.username}</td>
            <td>${user.full_name}</td>
            <td>${user.email}</td>
            <td>
                <span class="badge bg-${getRoleColor(user.role)}">${user.role}</span>
            </td>
            <td>${user.branch_name || '-'}</td>
            <td>
                <span class="badge bg-${user.status === 'active' ? 'success' : 'secondary'}">
                    ${user.status}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editUser(${user.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${user.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getRoleColor(role) {
    const colors = {
        'admin': 'danger',
        'cashier': 'primary',
        'stock_manager': 'warning',
        'accountant': 'info'
    };
    return colors[role] || 'secondary';
}


async function openUserModal(userId = null) {
    document.getElementById('userModalTitle').textContent = userId ? 'Edit User' : 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordRequired').style.display = 'inline';
    
    // Load branches if needed
    await loadBranches();
    
    if (userId) {
        const result = await apiCall(`users.php?id=${userId}`);
        if (result.success && result.data) {
            const u = result.data;
            document.getElementById('userId').value = u.id;
            document.getElementById('userUsername').value = u.username;
            document.getElementById('userFullName').value = u.full_name;
            document.getElementById('userEmail').value = u.email;
            document.getElementById('userRole').value = u.role;
            document.getElementById('userBranch').value = u.branch_id || '';
            document.getElementById('userStatus').value = u.status;
            document.getElementById('passwordRequired').style.display = 'none';
        }
    }
}

async function loadBranches() {
    // Branches would typically come from an API
    // For now, we'll use the existing select options
    // In a full implementation, you'd fetch from api/branches.php
}

async function saveUser() {
    const form = document.getElementById('userForm');
    const userId = document.getElementById('userId').value;
    const password = document.getElementById('userPassword').value;
    
    // Password is required only for new users
    if (!userId && !password) {
        document.getElementById('userPassword').setCustomValidity('Password is required for new users');
        form.reportValidity();
        return;
    } else {
        document.getElementById('userPassword').setCustomValidity('');
    }
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const data = {
        username: document.getElementById('userUsername').value,
        full_name: document.getElementById('userFullName').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value,
        branch_id: document.getElementById('userBranch').value || null,
        status: document.getElementById('userStatus').value
    };
    
    // Only include password if provided
    if (password) {
        data.password = password;
    }
    
    const method = userId ? 'PUT' : 'POST';
    if (userId) {
        data.id = userId;
    }
    
    const result = await apiCall('users.php', method, data);
    
    if (result.success) {
        showAlert('User saved successfully', 'success');
        bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
        loadUsers(currentPage, currentLimit);
    } else {
        showAlert(result.message || 'Error saving user', 'danger');
    }
}

async function editUser(id) {
    await openUserModal(id);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal')).show();
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        return;
    }
    
    const result = await apiCall(`users.php?id=${id}`, 'DELETE');
    
    if (result.success) {
        showAlert('User deleted successfully', 'success');
        loadUsers(currentPage, currentLimit);
    } else {
        showAlert('Error deleting user', 'danger');
    }
}

// Load summary
async function loadUsersSummary() {
    const result = await apiCall('summary.php?type=users');
    if (result.success) {
        document.getElementById('totalUsers').textContent = result.data.total_users;
        document.getElementById('activeUsers').textContent = result.data.active_users;
    }
}

// Initial load
loadUsersSummary();
loadUsers();

