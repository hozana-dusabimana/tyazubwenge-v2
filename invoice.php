<?php
require_once 'config/config.php';
requireLogin();

$saleId = $_GET['id'] ?? null;
$size = $_GET['size'] ?? 'a4'; // a4, thermal, thermal-80

if (!$saleId) {
    die('Invalid invoice ID');
}

require_once 'classes/Sale.php';
$sale = new Sale();
$saleData = $sale->getById($saleId);

if (!$saleData) {
    die('Sale not found');
}

$sizeClass = 'invoice-' . $size;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $saleData['invoice_number']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .invoice-container { margin: 0; padding: 10px; }
            .invoice-thermal { width: 58mm; font-size: 10px; }
            .invoice-thermal-80 { width: 80mm; font-size: 11px; }
            .invoice-a4 { width: 210mm; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid no-print mb-3">
        <div class="row">
            <div class="col-12">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
                <button class="btn btn-secondary" onclick="window.close()">Close</button>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="changeSize('thermal')">58mm</button>
                    <button class="btn btn-outline-primary" onclick="changeSize('thermal-80')">80mm</button>
                    <button class="btn btn-outline-primary" onclick="changeSize('a4')">A4</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="invoice-container <?php echo $sizeClass; ?>">
        <div class="invoice-header text-center">
            <h2><?php echo SITE_NAME; ?></h2>
            <p class="mb-1">Laboratory Products</p>
            <p class="mb-0 small">Invoice #<?php echo $saleData['invoice_number']; ?></p>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>Date:</strong><br>
                <?php echo formatDateTime($saleData['created_at']); ?>
            </div>
            <div class="col-6 text-end">
                <?php if ($saleData['customer_name']): ?>
                    <strong>Customer:</strong><br>
                    <?php echo $saleData['customer_name']; ?><br>
                    <?php if ($saleData['customer_phone']): ?>
                        <small><?php echo $saleData['customer_phone']; ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <strong>Walk-in Customer</strong>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="invoice-items">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saleData['items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_name']; ?></strong><br>
                                <small class="text-muted"><?php echo $item['product_sku']; ?></small>
                            </td>
                            <td class="text-end"><?php echo $item['quantity']; ?> <?php echo $item['unit']; ?></td>
                            <td class="text-end"><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="invoice-totals">
            <div class="row mb-2">
                <div class="col-6"><strong>Subtotal:</strong></div>
                <div class="col-6 text-end"><?php echo formatCurrency($saleData['total_amount']); ?></div>
            </div>
            <?php if ($saleData['discount'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-6"><strong>Discount:</strong></div>
                    <div class="col-6 text-end">-<?php echo formatCurrency($saleData['discount']); ?></div>
                </div>
            <?php endif; ?>
            <?php if ($saleData['tax'] > 0): ?>
                <div class="row mb-2">
                    <div class="col-6"><strong>Tax:</strong></div>
                    <div class="col-6 text-end"><?php echo formatCurrency($saleData['tax']); ?></div>
                </div>
            <?php endif; ?>
            <div class="row mb-3 border-top pt-2">
                <div class="col-6"><strong>Total:</strong></div>
                <div class="col-6 text-end"><strong><?php echo formatCurrency($saleData['final_amount']); ?></strong></div>
            </div>
            <div class="row">
                <div class="col-12">
                    <strong>Payment:</strong> <?php echo ucfirst(str_replace('_', ' ', $saleData['payment_method'])); ?><br>
                    <strong>Status:</strong> <?php echo ucfirst($saleData['payment_status']); ?>
                </div>
            </div>
        </div>
        
        <div class="invoice-footer">
            <p class="mb-0">Thank you for your business!</p>
            <p class="mb-0 small"><?php echo SITE_NAME; ?></p>
        </div>
    </div>
    
    <script>
        function changeSize(size) {
            window.location.href = `invoice.php?id=<?php echo $saleId; ?>&size=${size}`;
        }
        
        // Auto print on load if requested
        <?php if (isset($_GET['autoprint'])): ?>
        window.onload = function() {
            window.print();
        };
        <?php endif; ?>
    </script>
</body>
</html>

