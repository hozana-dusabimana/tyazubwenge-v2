<?php
require_once 'config/config.php';
requireLogin();

require_once 'classes/Sale.php';
require_once 'classes/Product.php';
require_once 'classes/Stock.php';
require_once 'classes/Customer.php';
require_once 'classes/Report.php';

$sale = new Sale();
$product = new Product();
$stock = new Stock();
$customer = new Customer();
$report = new Report();

$branchId = $_SESSION['branch_id'] ?? null;

// Get today's summary
$todaySummary = $sale->getSalesSummary($branchId, date('Y-m-d'), date('Y-m-d'));
$todaySummary = $todaySummary ?: ['total_sales' => 0, 'total_revenue' => 0];

// Get low stock alerts
$lowStock = $stock->getLowStock($branchId);

// Get near expiry alerts
$nearExpiry = $stock->getNearExpiry($branchId, 30);

// Get top products
$topProducts = $report->getTopProducts($branchId, date('Y-m-d', strtotime('-7 days')), date('Y-m-d'), 5);

// Get recent sales
$recentSales = $sale->getAll(1, 5, $branchId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Dashboard</h2>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Today's Sales</h6>
                                <h3 class="mb-0"><?php echo formatCurrency($todaySummary['total_revenue'] ?? 0); ?></h3>
                            </div>
                            <i class="bi bi-cart-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Total Orders</h6>
                                <h3 class="mb-0"><?php echo $todaySummary['total_sales'] ?? 0; ?></h3>
                            </div>
                            <i class="bi bi-receipt fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Low Stock Items</h6>
                                <h3 class="mb-0"><?php echo count($lowStock); ?></h3>
                            </div>
                            <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2">Near Expiry</h6>
                                <h3 class="mb-0"><?php echo count($nearExpiry); ?></h3>
                            </div>
                            <i class="bi bi-calendar-x fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Sales -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Sales</h5>
                        <a href="sales.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentSales)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No sales today</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentSales as $saleItem): ?>
                                            <tr>
                                                <td><?php echo $saleItem['invoice_number']; ?></td>
                                                <td><?php echo $saleItem['customer_name'] ?? 'Walk-in'; ?></td>
                                                <td><?php echo formatCurrency($saleItem['final_amount']); ?></td>
                                                <td><?php echo formatDateTime($saleItem['created_at']); ?></td>
                                                <td>
                                                    <a href="invoice.php?id=<?php echo $saleItem['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="bi bi-printer"></i> Print
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Products & Alerts -->
            <div class="col-md-4 mb-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Top Products (7 Days)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topProducts)): ?>
                            <p class="text-muted mb-0">No data available</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($topProducts as $product): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo $product['name']; ?></strong><br>
                                            <small class="text-muted"><?php echo $product['sku']; ?></small>
                                        </div>
                                        <span class="badge bg-primary"><?php echo formatCurrency($product['total_revenue']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($lowStock)): ?>
                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">Low Stock Alerts</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($lowStock, 0, 5) as $item): ?>
                                <li class="list-group-item">
                                    <strong><?php echo $item['product_name']; ?></strong><br>
                                    <small>Stock: <?php echo $item['quantity']; ?> <?php echo $item['unit']; ?> | Min: <?php echo $item['min_stock_level']; ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($lowStock) > 5): ?>
                            <a href="stock.php?filter=low_stock" class="btn btn-sm btn-warning w-100 mt-2">View All</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

