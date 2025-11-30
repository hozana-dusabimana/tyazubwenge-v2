const POSPage = {
    cart: [],
    
    async load() {
        const content = `
            <div class="page-header">
                <h2><i class="bi bi-cart-plus"></i> Point of Sale</h2>
            </div>
            
            <div class="pos-container">
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Products</span>
                                <input type="text" class="form-control" id="productSearch" placeholder="Search products..." style="width: 300px;">
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="productsGrid" class="product-grid">Loading products...</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="card">
                        <div class="card-header">Cart</div>
                        <div class="card-body">
                            <div id="cartItems"></div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Subtotal:</strong>
                                    <span id="cartSubtotal">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Tax:</strong>
                                    <span id="cartTax">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <span id="cartTotal">0.00</span>
                                </div>
                                <button class="btn btn-primary w-100" id="checkoutBtn">
                                    <i class="bi bi-check-circle"></i> Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('pageContent').innerHTML = content;
        this.cart = [];
        await this.loadProducts();
        this.setupEventListeners();
    },

    async loadProducts() {
        const products = await window.electronAPI.db.query(
            `SELECT * FROM products WHERE status = 'active' ORDER BY name`
        );

        const grid = document.getElementById('productsGrid');
        
        if (products && products.length > 0) {
            grid.innerHTML = products.map(product => `
                <div class="product-card" data-product-id="${product.id}">
                    <div class="fw-bold">${product.name}</div>
                    <div class="text-muted small">${product.sku || ''}</div>
                    <div class="mt-2">
                        <strong>${(product.retail_price || 0).toFixed(2)}</strong>
                    </div>
                </div>
            `).join('');

            // Add click handlers
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', () => {
                    const productId = parseInt(card.dataset.productId);
                    this.addToCart(productId, products.find(p => p.id === productId));
                });
            });
        } else {
            grid.innerHTML = '<p class="text-muted">No products available</p>';
        }
    },

    setupEventListeners() {
        // Product search
        document.getElementById('productSearch').addEventListener('input', (e) => {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(search) ? 'block' : 'none';
            });
        });

        // Checkout
        document.getElementById('checkoutBtn').addEventListener('click', () => {
            this.checkout();
        });
    },

    addToCart(productId, product) {
        const existing = this.cart.find(item => item.product_id === productId);
        
        if (existing) {
            existing.quantity += 1;
        } else {
            this.cart.push({
                product_id: productId,
                name: product.name,
                unit_price: product.retail_price || 0,
                quantity: 1,
                unit: product.unit || 'pcs'
            });
        }
        
        this.updateCart();
    },

    removeFromCart(index) {
        this.cart.splice(index, 1);
        this.updateCart();
    },

    updateCart() {
        const cartDiv = document.getElementById('cartItems');
        
        if (this.cart.length === 0) {
            cartDiv.innerHTML = '<p class="text-muted">Cart is empty</p>';
        } else {
            cartDiv.innerHTML = this.cart.map((item, index) => `
                <div class="cart-item">
                    <div>
                        <div class="fw-bold">${item.name}</div>
                        <small class="text-muted">${item.quantity} x ${item.unit_price.toFixed(2)}</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold">${(item.quantity * item.unit_price).toFixed(2)}</span>
                        <button class="btn btn-sm btn-danger" onclick="POSPage.removeFromCart(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Update totals
        const subtotal = this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        const tax = subtotal * 0.18; // 18% tax
        const total = subtotal + tax;

        document.getElementById('cartSubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('cartTax').textContent = tax.toFixed(2);
        document.getElementById('cartTotal').textContent = total.toFixed(2);
    },

    async checkout() {
        if (this.cart.length === 0) {
            alert('Cart is empty');
            return;
        }

        const subtotal = this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        const tax = subtotal * 0.18;
        const total = subtotal + tax;

        // Get user
        const user = await window.electronAPI.auth.getUser();
        
        // Check if online
        const isOnline = await window.electronAPI.sync.isOnline();
        
        // Generate local_id
        const localId = 'local_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        // Create sale record
        const sale = {
            local_id: localId,
            invoice_number: 'INV-' + Date.now(),
            customer_id: null,
            branch_id: user?.branch_id || 1,
            user_id: user?.id || 1,
            total_amount: subtotal,
            discount: 0,
            tax: tax,
            final_amount: total,
            payment_method: 'cash',
            payment_status: 'completed',
            sale_type: 'retail',
            notes: '',
            sync_status: isOnline ? 'syncing' : 'pending',
            created_at: new Date().toISOString()
        };

        // Insert sale
        const saleResult = await window.electronAPI.db.query(
            `INSERT INTO sales (local_id, invoice_number, customer_id, branch_id, user_id, 
             total_amount, discount, tax, final_amount, payment_method, payment_status, 
             sale_type, notes, sync_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                sale.local_id, sale.invoice_number, sale.customer_id, sale.branch_id, sale.user_id,
                sale.total_amount, sale.discount, sale.tax, sale.final_amount,
                sale.payment_method, sale.payment_status, sale.sale_type, sale.notes,
                sale.sync_status, sale.created_at
            ]
        );

        if (saleResult.success) {
            const saleId = saleResult.lastInsertRowid;

            // Insert sale items
            for (const item of this.cart) {
                await window.electronAPI.db.query(
                    `INSERT INTO sale_items (sale_id, product_id, quantity, unit, unit_price, discount, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)`,
                    [
                        saleId, item.product_id, item.quantity, item.unit,
                        item.unit_price, 0, item.quantity * item.unit_price
                    ]
                );

                // Deduct stock
                await window.electronAPI.db.query(
                    `UPDATE stock_inventory SET quantity = quantity - ? WHERE product_id = ?`,
                    [item.quantity, item.product_id]
                );
            }

            // If online, try to sync immediately
            if (isOnline) {
                try {
                    await window.electronAPI.sync.syncNow();
                    alert('Sale completed and synced!');
                } catch (error) {
                    alert('Sale completed! Will sync when connection is stable.');
                }
            } else {
                alert('Sale completed! Will sync when online.');
            }
            
            this.cart = [];
            this.updateCart();
            
            // Refresh dashboard if on that page
            if (window.App && window.App.currentPage === 'dashboard') {
                DashboardPage.loadData();
            }
        } else {
            alert('Error processing sale: ' + (saleResult.error || 'Unknown error'));
        }
    }
};

window.POSPage = POSPage;

