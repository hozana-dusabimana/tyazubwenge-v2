<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Report.php';

requireLogin();

if (!hasPermission('reports')) {
    die('Unauthorized access');
}

$type = $_GET['type'] ?? 'sales';
$branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$customType = $_GET['custom_type'] ?? null;

$report = new Report();

// Get report data
switch ($type) {
    case 'sales':
        $groupBy = $_GET['group_by'] ?? 'day';
        $data = $report->getSalesReport($branchId, $dateFrom, $dateTo, $groupBy);
        $title = 'Sales Report';
        break;
    case 'top_products':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $data = $report->getTopProducts($branchId, $dateFrom, $dateTo, $limit);
        $title = 'Top Products Report';
        break;
    case 'stock_valuation':
        $data = $report->getStockValuation($branchId);
        $title = 'Stock Valuation Report';
        break;
    case 'profit_loss':
        $data = $report->getProfitLoss($branchId, $dateFrom, $dateTo);
        $title = 'Profit & Loss Report';
        break;
    case 'custom':
        $params = [
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        $data = $report->getCustomReport($customType, $params);
        $titles = [
            'sales_by_customer' => 'Sales by Customer Report',
            'sales_by_category' => 'Sales by Category Report',
            'slow_moving' => 'Slow Moving Products Report'
        ];
        $title = $titles[$customType] ?? 'Custom Report';
        break;
    default:
        $data = [];
        $title = 'Report';
}

// Get branch name if available
$branchName = '';
if ($branchId) {
    require_once __DIR__ . '/../classes/Branch.php';
    $branch = new Branch();
    $branchData = $branch->getById($branchId);
    $branchName = $branchData['name'] ?? '';
}

// Generate PDF HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 22pt;
            color: #007bff;
            margin-bottom: 5px;
            text-align: center;
        }
        
        .header .subtitle {
            text-align: center;
            color: #666;
            font-size: 10pt;
            margin-bottom: 5px;
        }
        
        .header .branch {
            text-align: center;
            color: #888;
            font-size: 9pt;
        }
        
        .report-info {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .report-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-info td {
            padding: 4px 8px;
            font-size: 10pt;
        }
        
        .report-info td:first-child {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        
        .section-title {
            background: linear-gradient(to right, #007bff, #0056b3);
            color: white;
            padding: 10px 15px;
            font-size: 12pt;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-radius: 3px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        
        table thead {
            background-color: #007bff;
            color: white;
        }
        
        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0056b3;
        }
        
        table th.text-right {
            text-align: right;
        }
        
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        table tbody tr:hover {
            background-color: #e9ecef;
        }
        
        table td.text-right {
            text-align: right;
        }
        
        table td.text-center {
            text-align: center;
        }
        
        .total-row {
            background-color: #e9ecef !important;
            font-weight: bold;
            border-top: 2px solid #007bff;
        }
        
        .summary-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .summary-card {
            flex: 1;
            min-width: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .summary-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .summary-card.danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
        }
        
        .summary-card.info {
            background: linear-gradient(135deg, #3494E6 0%, #EC6EAD 100%);
        }
        
        .summary-card h3 {
            font-size: 18pt;
            margin: 5px 0;
        }
        
        .summary-card p {
            font-size: 9pt;
            opacity: 0.9;
            margin: 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12pt;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background-color: #0056b3;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .no-print,
            .print-button {
                display: none !important;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            .section-title {
                page-break-after: avoid;
            }
            
            .summary-cards {
                page-break-inside: avoid;
            }
            
            .footer {
                position: fixed;
                bottom: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Print Button -->
    <button class="print-button no-print" onclick="window.print()" style="font-family: Arial, sans-serif;">
        🖨️ Print / Save as PDF
    </button>
    
    <!-- Header -->
    <div class="header">
        <h1><?php echo htmlspecialchars(SITE_NAME); ?></h1>
        <div class="subtitle">Laboratory Products Management System</div>
        <?php if ($branchName): ?>
            <div class="branch"><?php echo htmlspecialchars($branchName); ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Report Information -->
    <div class="report-info">
        <table>
            <tr>
                <td>Report Type:</td>
                <td><?php echo htmlspecialchars($title); ?></td>
            </tr>
            <tr>
                <td>Generated On:</td>
                <td><?php echo date('d M Y, H:i:s'); ?></td>
            </tr>
            <?php if ($dateFrom || $dateTo): ?>
            <tr>
                <td>Period:</td>
                <td>
                    <?php 
                    if ($dateFrom && $dateTo) {
                        echo date('d M Y', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo));
                    } elseif ($dateFrom) {
                        echo 'From ' . date('d M Y', strtotime($dateFrom));
                    } elseif ($dateTo) {
                        echo 'Until ' . date('d M Y', strtotime($dateTo));
                    }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Generated By:</td>
                <td><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System'); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- Report Content -->
    <?php
    switch ($type) {
        case 'sales':
            include __DIR__ . '/../includes/pdf_sales_report.php';
            break;
        case 'top_products':
            include __DIR__ . '/../includes/pdf_top_products_report.php';
            break;
        case 'stock_valuation':
            include __DIR__ . '/../includes/pdf_stock_valuation_report.php';
            break;
        case 'profit_loss':
            include __DIR__ . '/../includes/pdf_profit_loss_report.php';
            break;
        case 'custom':
            include __DIR__ . '/../includes/pdf_custom_report.php';
            break;
    }
    ?>
    
    <!-- Footer -->
    <div class="footer">
        <p><strong><?php echo htmlspecialchars(SITE_NAME); ?></strong></p>
        <p>This is a computer-generated report. No signature required.</p>
        <p>Generated on <?php echo date('d M Y, H:i:s'); ?> | Page <span id="pageNum"></span></p>
    </div>
    
    <script>
        // Auto-trigger print dialog when page loads (for direct download)
        window.onload = function() {
            // Check if this is a direct download request
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('download') === '1') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>

