<?php
require_once 'config/config.php';
requireLogin();
if (!hasPermission('sales')) {
    redirect('index.php');
}

// Load customers for dropdown
require_once 'classes/Customer.php';
$customer = new Customer();
$customers = $customer->getAll(1, 1000, ''); // Get all customers (limit 1000)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="pos-container">
        <div class="pos-left">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h3>Point of Sale</h3>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="barcodeInput" placeholder="Scan barcode or search product...">
                        <button class="btn btn-primary" onclick="searchProduct()">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="row" id="productsGrid">
                <div class="col-12 text-center">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
        </div>
        
        <div class="pos-right">
            <h5 class="mb-3">Cart</h5>
            <div id="cartItems"></div>
            <div class="mt-3">
                <div class="d-flex justify-content-between mb-2">
                    <strong>Subtotal:</strong>
                    <span id="cartSubtotal">0.00</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" class="form-control" id="cartDiscount" value="0" min="0" max="100" onchange="updateCartTotal()">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tax (%)</label>
                    <input type="number" class="form-control" id="cartTax" value="0" min="0" onchange="updateCartTotal()">
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong id="cartTotal">0.00</strong>
                </div>
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select class="form-select" id="cartCustomer">
                        <option value="">Walk-in Customer</option>
                        <?php foreach ($customers as $cust): ?>
                            <option value="<?php echo htmlspecialchars($cust['id']); ?>">
                                <?php echo htmlspecialchars($cust['name']); ?>
                                <?php if (!empty($cust['phone'])): ?>
                                    - <?php echo htmlspecialchars($cust['phone']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" id="paymentMethod">
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sale Type</label>
                    <select class="form-select" id="saleType">
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                    </select>
                </div>
                <button class="btn btn-success w-100 mb-2" onclick="processSale()">
                    <i class="bi bi-check-circle"></i> Complete Sale
                </button>
                <button class="btn btn-outline-danger w-100" onclick="clearCart()">
                    <i class="bi bi-x-circle"></i> Clear Cart
                </button>
            </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        window.BRANCH_ID = <?php echo $_SESSION['branch_id'] ?? 1; ?>;
    </script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/pos.js"></script>
</body>
</html>

