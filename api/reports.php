<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Report.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$report = new Report();

if ($method !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'sales';
$branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

switch ($type) {
    case 'sales':
        $groupBy = $_GET['group_by'] ?? 'day';
        $result = $report->getSalesReport($branchId, $dateFrom, $dateTo, $groupBy);
        break;
    case 'top_products':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $result = $report->getTopProducts($branchId, $dateFrom, $dateTo, $limit);
        break;
    case 'stock_valuation':
        $result = $report->getStockValuation($branchId);
        break;
    case 'profit_loss':
        $result = $report->getProfitLoss($branchId, $dateFrom, $dateTo);
        break;
    case 'custom':
        $customType = $_GET['custom_type'] ?? 'sales_by_customer';
        $params = [
            'branch_id' => $branchId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
        $result = $report->getCustomReport($customType, $params);
        break;
    default:
        $result = [];
}

echo json_encode(['success' => true, 'data' => $result]);
?>

