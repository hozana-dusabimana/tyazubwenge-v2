<?php
require_once 'config/config.php';
requireLogin();
if (!hasPermission('reports')) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
        <h2 class="mb-4">Reports & Analytics</h2>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Report Type</h6>
                        <select class="form-select" id="reportType" onchange="loadReport()">
                            <option value="sales">Sales Report</option>
                            <option value="top_products">Top Products</option>
                            <option value="stock_valuation">Stock Valuation</option>
                            <option value="profit_loss">Profit & Loss</option>
                            <option value="custom">Custom Reports</option>
                        </select>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Date Range</h6>
                        <div class="mb-2">
                            <label class="form-label small">From</label>
                            <input type="date" class="form-control form-control-sm" id="dateFrom" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">To</label>
                            <input type="date" class="form-control form-control-sm" id="dateTo" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <button class="btn btn-primary btn-sm w-100" onclick="loadReport()">Apply</button>
                    </div>
                </div>
                
                <div class="card mt-3" id="customReportOptions" style="display: none;">
                    <div class="card-body">
                        <h6>Custom Report Type</h6>
                        <select class="form-select form-select-sm" id="customReportType" onchange="loadReport()">
                            <option value="sales_by_customer">Sales by Customer</option>
                            <option value="sales_by_category">Sales by Category</option>
                            <option value="slow_moving">Slow Moving Products</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="reportTitle">Sales Report</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-danger" onclick="exportReport('pdf')" id="pdfExportBtn" style="display: none;">
                                <i class="bi bi-file-earmark-pdf"></i> Download PDF
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="exportReport('csv')" id="csvExportBtn" style="display: none;">
                                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="reportContent">
                            <div class="text-center">
                                <div class="spinner-border" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/reports.js"></script>
</body>
</html>

