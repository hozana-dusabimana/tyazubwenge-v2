const StockPage = {
    async load() {
        try {
            const content = `
                <div class="page-header">
                    <h2><i class="bi bi-boxes"></i> Stock Management</h2>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Stock Inventory</span>
                        <button class="btn btn-primary btn-sm" onclick="StockPage.showAddStockModal()">
                            <i class="bi bi-plus-circle"></i> Add Stock
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="stockTable">Loading...</div>
                    </div>
                </div>
            `;

            const pageContent = document.getElementById('pageContent');
            if (!pageContent) {
                console.error('pageContent element not found');
                return;
            }

            pageContent.innerHTML = content;
            await this.loadStock();
        } catch (error) {
            console.error('Error loading stock page:', error);
            const pageContent = document.getElementById('pageContent');
            if (pageContent) {
                pageContent.innerHTML = `<div class="alert alert-danger">Error loading page: ${error.message}</div>`;
            }
        }
    },

    async loadStock() {
        try {
            const tableDiv = document.getElementById('stockTable');
            if (!tableDiv) {
                console.error('stockTable element not found');
                return;
            }

            const result = await window.electronAPI.db.query(
                `SELECT si.*, p.name as product_name, p.sku, p.unit as product_unit
                 FROM stock_inventory si
                 JOIN products p ON si.product_id = p.id
                 ORDER BY si.updated_at DESC`
            );

            // Handle both array result and object with success property
            const stock = Array.isArray(result) ? result : (result?.success !== false ? result : []);

            if (stock && stock.length > 0) {
                tableDiv.innerHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${stock.map(item => `
                                <tr>
                                    <td>${item.product_name || 'N/A'}</td>
                                    <td>${item.sku || 'N/A'}</td>
                                    <td>${item.quantity || 0}</td>
                                    <td>${item.unit || item.product_unit || 'N/A'}</td>
                                    <td>${item.batch_number || 'N/A'}</td>
                                    <td>${item.expiry_date || 'N/A'}</td>
                                    <td>
                                        <span class="badge ${item.sync_status === 'synced' ? 'bg-success' : 'bg-warning'}">
                                            ${item.sync_status || 'pending'}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            } else {
                tableDiv.innerHTML = '<p class="text-muted">No stock items</p>';
            }
        } catch (error) {
            console.error('Error loading stock:', error);
            const tableDiv = document.getElementById('stockTable');
            if (tableDiv) {
                tableDiv.innerHTML = `<div class="alert alert-danger">Error loading stock: ${error.message}</div>`;
            }
        }
    },

    showAddStockModal() {
        try {
            // Remove existing modal if any
            const existingModal = document.getElementById('addStockModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Create modal HTML
            const modalHTML = `
                <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addStockModalLabel">
                                    <i class="bi bi-plus-circle"></i> Add Stock
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addStockForm">
                                    <div class="mb-3">
                                        <label for="stockProductId" class="form-label">Product ID <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="stockProductId" required min="1" placeholder="Enter product ID">
                                    </div>
                                    <div class="mb-3">
                                        <label for="stockQuantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="stockQuantity" required min="0.01" step="0.01" placeholder="Enter quantity">
                                    </div>
                                    <div class="mb-3">
                                        <label for="stockUnit" class="form-label">Unit <span class="text-danger">*</span></label>
                                        <select class="form-select" id="stockUnit" required>
                                            <option value="pcs">Pieces (pcs)</option>
                                            <option value="kg">Kilograms (kg)</option>
                                            <option value="g">Grams (g)</option>
                                            <option value="mg">Milligrams (mg)</option>
                                            <option value="L">Liters (L)</option>
                                            <option value="mL">Milliliters (mL)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="stockBatch" class="form-label">Batch Number (optional)</label>
                                        <input type="text" class="form-control" id="stockBatch" placeholder="Enter batch number">
                                    </div>
                                    <div class="mb-3">
                                        <label for="stockExpiry" class="form-label">Expiry Date (optional)</label>
                                        <input type="date" class="form-control" id="stockExpiry">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="StockPage.handleAddStock()">
                                    <i class="bi bi-check-circle"></i> Add Stock
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Initialize Bootstrap modal
            const modalElement = document.getElementById('addStockModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Clean up when modal is hidden
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
            });
        } catch (error) {
            console.error('Error showing add stock modal:', error);
            alert('Error: ' + error.message);
        }
    },

    handleAddStock() {
        try {
            const productId = document.getElementById('stockProductId').value;
            const quantity = document.getElementById('stockQuantity').value;
            const unit = document.getElementById('stockUnit').value;
            const batch = document.getElementById('stockBatch').value;
            const expiry = document.getElementById('stockExpiry').value;

            if (!productId || productId.trim() === '') {
                alert('Product ID is required');
                return;
            }

            if (!quantity || quantity.trim() === '' || isNaN(parseFloat(quantity))) {
                alert('Please enter a valid quantity');
                return;
            }

            if (!unit || unit.trim() === '') {
                alert('Unit is required');
                return;
            }

            const parsedProductId = parseInt(productId);
            const parsedQuantity = parseFloat(quantity);

            if (isNaN(parsedProductId) || parsedProductId <= 0) {
                alert('Please enter a valid product ID');
                return;
            }

            if (isNaN(parsedQuantity) || parsedQuantity <= 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Close modal
            const modalElement = document.getElementById('addStockModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }

            this.addStock(parsedProductId, parsedQuantity, unit.trim(), batch.trim() || null, expiry || null);
        } catch (error) {
            console.error('Error handling add stock:', error);
            alert('Error: ' + error.message);
        }
    },

    async addStock(productId, quantity, unit, batchNumber = null, expiryDate = null) {
        try {
            if (!productId || productId <= 0) {
                alert('Invalid product ID');
                return;
            }

            if (!quantity || quantity <= 0) {
                alert('Invalid quantity');
                return;
            }

            if (!unit || unit.trim() === '') {
                alert('Unit is required');
                return;
            }

            if (!window.electronAPI || !window.electronAPI.db || !window.electronAPI.auth) {
                alert('API not available. Please restart the application.');
                return;
            }

            const user = await window.electronAPI.auth.getUser();
            if (!user) {
                alert('User not authenticated. Please login again.');
                return;
            }

            const localId = 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            const result = await window.electronAPI.db.query(
                `INSERT INTO stock_inventory (product_id, branch_id, quantity, unit, batch_number, expiry_date, local_id, sync_status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)`,
                [productId, user?.branch_id || 1, quantity, unit, batchNumber, expiryDate, localId]
            );

            if (result && result.success !== false && (result.changes > 0 || result.lastInsertRowid)) {
                alert('Stock added! Will sync when online.');
                await this.loadStock();
            } else {
                const errorMsg = result?.error || result?.message || 'Unknown error';
                console.error('Failed to add stock:', result);
                alert('Error adding stock: ' + errorMsg);
            }
        } catch (error) {
            console.error('Error adding stock:', error);
            alert('Error adding stock: ' + error.message);
        }
    }
};

window.StockPage = StockPage;

