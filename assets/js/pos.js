let cart = [];
let products = [];

// Load products
async function loadProducts() {
    const grid = document.getElementById('productsGrid');
    showLoading(grid);
    
    const result = await apiCall('products.php?limit=100');
    
    if (result.success) {
        products = result.data.filter(p => p.status === 'active');
        displayProducts(products);
    }
}

function displayProducts(productsList) {
    const grid = document.getElementById('productsGrid');
    
    if (productsList.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center text-muted">No products available</div>';
        return;
    }
    
    grid.innerHTML = productsList.map(product => `
        <div class="col-md-3 mb-3">
            <div class="card product-card" onclick="addToCart(${product.id})">
                <div class="card-body">
                    <h6 class="card-title">${product.name}</h6>
                    <p class="text-muted small mb-1">${product.sku}</p>
                    <p class="mb-0">
                        <strong>${formatCurrency(product.retail_price)}</strong>
                        <span class="text-muted small">/${product.unit}</span>
                    </p>
                </div>
            </div>
        </div>
    `).join('');
}

async function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    
    // Check stock
    const branchId = window.BRANCH_ID || 1;
    const stockResult = await apiCall(`stock.php?product_id=${productId}&branch_id=${branchId}`);
    const availableStock = stockResult.success && stockResult.data ? parseFloat(stockResult.data.quantity) : 0;
    
    if (availableStock <= 0) {
        showAlert('Product out of stock', 'warning');
        return;
    }
    
    const existingItem = cart.find(item => item.product_id === productId);
    
    if (existingItem) {
        if (existingItem.quantity + 1 > availableStock) {
            showAlert('Insufficient stock', 'warning');
            return;
        }
        existingItem.quantity += 1;
        existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
    } else {
        const price = document.getElementById('saleType').value === 'wholesale' 
            ? product.wholesale_price 
            : product.retail_price;
        
        cart.push({
            product_id: productId,
            product_name: product.name,
            product_sku: product.sku,
            quantity: 1,
            unit: product.unit,
            unit_price: price,
            subtotal: price,
            discount: 0
        });
    }
    
    updateCartDisplay();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartDisplay();
}

function updateCartQuantity(index, change) {
    const item = cart[index];
    const newQuantity = item.quantity + change;
    
    if (newQuantity <= 0) {
        removeFromCart(index);
        return;
    }
    
    item.quantity = newQuantity;
    item.subtotal = item.quantity * item.unit_price;
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartItemsDiv = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p class="text-muted text-center">Cart is empty</p>';
        document.getElementById('cartSubtotal').textContent = '0.00';
        document.getElementById('cartTotal').textContent = '0.00';
        return;
    }
    
    cartItemsDiv.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>${item.product_name}</strong><br>
                    <small class="text-muted">${item.product_sku}</small>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small>${formatCurrency(item.unit_price)} × ${item.quantity} ${item.unit}</small>
                </div>
                <strong>${formatCurrency(item.subtotal)}</strong>
            </div>
            <div class="input-group input-group-sm mt-2">
                <button class="btn btn-outline-secondary" onclick="updateCartQuantity(${index}, -1)">-</button>
                <input type="number" class="form-control text-center" value="${item.quantity}" 
                       onchange="updateCartQuantity(${index}, this.value - ${item.quantity})" min="1">
                <button class="btn btn-outline-secondary" onclick="updateCartQuantity(${index}, 1)">+</button>
            </div>
        </div>
    `).join('');
    
    updateCartTotal();
}

function updateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const discountPercent = parseFloat(document.getElementById('cartDiscount').value) || 0;
    const taxPercent = parseFloat(document.getElementById('cartTax').value) || 0;
    
    const discount = subtotal * (discountPercent / 100);
    const afterDiscount = subtotal - discount;
    const tax = afterDiscount * (taxPercent / 100);
    const total = afterDiscount + tax;
    
    document.getElementById('cartSubtotal').textContent = formatCurrency(subtotal);
    document.getElementById('cartTotal').textContent = formatCurrency(total);
}

async function processSale() {
    if (cart.length === 0) {
        showAlert('Cart is empty', 'warning');
        return;
    }
    
    const saleData = {
        customer_id: document.getElementById('cartCustomer').value || null,
        branch_id: window.BRANCH_ID || 1,
        items: cart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit: item.unit,
            unit_price: item.unit_price,
            discount: item.discount,
            subtotal: item.subtotal
        })),
        total_amount: cart.reduce((sum, item) => sum + item.subtotal, 0),
        discount: parseFloat(document.getElementById('cartDiscount').value) || 0,
        tax: parseFloat(document.getElementById('cartTax').value) || 0,
        payment_method: document.getElementById('paymentMethod').value,
        payment_status: document.getElementById('paymentMethod').value === 'credit' ? 'pending' : 'paid',
        sale_type: document.getElementById('saleType').value,
        notes: ''
    };
    
    const discountAmount = saleData.total_amount * (saleData.discount / 100);
    const afterDiscount = saleData.total_amount - discountAmount;
    const taxAmount = afterDiscount * (saleData.tax / 100);
    saleData.final_amount = afterDiscount + taxAmount;
    
    const result = await apiCall('sales.php', 'POST', saleData);
    
    if (result.success) {
        showAlert('Sale completed successfully!', 'success');
        const invoiceId = result.sale_id;
        if (confirm('Print invoice?')) {
            printInvoice(invoiceId, 'thermal');
        }
        clearCart();
        loadProducts();
    } else {
        showAlert(result.message || 'Error processing sale', 'danger');
    }
}

function clearCart() {
    cart = [];
    updateCartDisplay();
    document.getElementById('cartDiscount').value = 0;
    document.getElementById('cartTax').value = 0;
}

async function searchProduct() {
    const searchTerm = document.getElementById('barcodeInput').value.trim();
    if (!searchTerm) {
        loadProducts();
        return;
    }
    
    const result = await apiCall(`search.php?term=${encodeURIComponent(searchTerm)}`);
    
    if (result.success && result.data) {
        displayProducts([result.data]);
        document.getElementById('barcodeInput').value = '';
    } else {
        showAlert('Product not found', 'warning');
    }
}

// Barcode scanner handler
function onBarcodeScan(barcode) {
    document.getElementById('barcodeInput').value = barcode;
    searchProduct();
    setTimeout(() => {
        const result = products.find(p => p.barcode === barcode);
        if (result) {
            addToCart(result.id);
        }
    }, 100);
}

// Enter key to search
document.getElementById('barcodeInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        searchProduct();
    }
});

// Initial load
loadProducts();
updateCartDisplay();

