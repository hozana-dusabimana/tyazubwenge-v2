const CustomersPage = {
    async load() {
        try {
            const content = `
                <div class="page-header">
                    <h2><i class="bi bi-people"></i> Customers</h2>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Customers</span>
                        <button class="btn btn-primary btn-sm" onclick="CustomersPage.showAddModal()">
                            <i class="bi bi-plus-circle"></i> Add Customer
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="customersTable">Loading...</div>
                    </div>
                </div>
            `;

            const pageContent = document.getElementById('pageContent');
            if (!pageContent) {
                console.error('pageContent element not found');
                return;
            }

            pageContent.innerHTML = content;
            await this.loadCustomers();
        } catch (error) {
            console.error('Error loading customers page:', error);
            const pageContent = document.getElementById('pageContent');
            if (pageContent) {
                pageContent.innerHTML = `<div class="alert alert-danger">Error loading page: ${error.message}</div>`;
            }
        }
    },

    async loadCustomers() {
        try {
            const tableDiv = document.getElementById('customersTable');
            if (!tableDiv) {
                console.error('customersTable element not found');
                return;
            }

            const result = await window.electronAPI.db.query(
                `SELECT * FROM customers ORDER BY name`
            );

            // Handle both array result and object with success property
            const customers = Array.isArray(result) ? result : (result?.success !== false ? result : []);

            if (customers && customers.length > 0) {
                tableDiv.innerHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Loyalty Points</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${customers.map(customer => `
                                <tr>
                                    <td>${customer.name || 'N/A'}</td>
                                    <td>${customer.email || 'N/A'}</td>
                                    <td>${customer.phone || 'N/A'}</td>
                                    <td>${customer.loyalty_points || 0}</td>
                                    <td>
                                        <span class="badge ${customer.sync_status === 'synced' ? 'bg-success' : 'bg-warning'}">
                                            ${customer.sync_status || 'pending'}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                tableDiv.innerHTML = '<p class="text-muted">No customers found</p>';
            }
        } catch (error) {
            console.error('Error loading customers:', error);
            const tableDiv = document.getElementById('customersTable');
            if (tableDiv) {
                tableDiv.innerHTML = `<div class="alert alert-danger">Error loading customers: ${error.message}</div>`;
            }
        }
    },

    showAddModal() {
        try {
            // Remove existing modal if any
            const existingModal = document.getElementById('addCustomerModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create modal HTML
            const modalHTML = `
                <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addCustomerModalLabel">
                                    <i class="bi bi-person-plus"></i> Add Customer
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addCustomerForm">
                                    <div class="mb-3">
                                        <label for="customerName" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="customerName" required placeholder="Enter customer name">
                                    </div>
                                    <div class="mb-3">
                                        <label for="customerEmail" class="form-label">Email (optional)</label>
                                        <input type="email" class="form-control" id="customerEmail" placeholder="Enter email address">
                                    </div>
                                    <div class="mb-3">
                                        <label for="customerPhone" class="form-label">Phone (optional)</label>
                                        <input type="tel" class="form-control" id="customerPhone" placeholder="Enter phone number">
                                    </div>
                                    <div class="mb-3">
                                        <label for="customerAddress" class="form-label">Address (optional)</label>
                                        <textarea class="form-control" id="customerAddress" rows="2" placeholder="Enter address"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="CustomersPage.handleAddCustomer()">
                                    <i class="bi bi-check-circle"></i> Add Customer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Initialize Bootstrap modal
            const modalElement = document.getElementById('addCustomerModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Clean up when modal is hidden
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
            });
        } catch (error) {
            console.error('Error showing add customer modal:', error);
            alert('Error: ' + error.message);
        }
    },

    handleAddCustomer() {
        try {
            const name = document.getElementById('customerName').value;
            const email = document.getElementById('customerEmail').value;
            const phone = document.getElementById('customerPhone').value;
            const address = document.getElementById('customerAddress').value;

            if (!name || name.trim() === '') {
                alert('Customer name is required');
                return;
            }

            // Close modal
            const modalElement = document.getElementById('addCustomerModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }

            this.addCustomer(
                name.trim(),
                email?.trim() || null,
                phone?.trim() || null,
                address?.trim() || null
            );
        } catch (error) {
            console.error('Error handling add customer:', error);
            alert('Error: ' + error.message);
        }
    },

    async addCustomer(name, email, phone, address = null) {
        try {
            if (!name || name.trim() === '') {
                alert('Customer name is required');
                return;
            }

            if (!window.electronAPI || !window.electronAPI.db) {
                alert('Database API not available. Please restart the application.');
                return;
            }

            const localId = 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            const result = await window.electronAPI.db.query(
                `INSERT INTO customers (name, email, phone, address, local_id, sync_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)`,
                [name, email, phone, address, localId]
            );

            if (result && result.success !== false && (result.changes > 0 || result.lastInsertRowid)) {
                alert('Customer added! Will sync when online.');
                await this.loadCustomers();
            } else {
                const errorMsg = result?.error || result?.message || 'Unknown error';
                console.error('Failed to add customer:', result);
                alert('Error adding customer: ' + errorMsg);
            }
        } catch (error) {
            console.error('Error adding customer:', error);
            alert('Error adding customer: ' + error.message);
        }
    }
};

window.CustomersPage = CustomersPage;

