<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Stock.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$stock = new Stock();

switch ($method) {
    case 'GET':
        $productId = $_GET['product_id'] ?? null;
        $branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? 'all';
        
        switch ($type) {
            case 'low_stock':
                $result = $stock->getLowStock($branchId);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
            case 'near_expiry':
                $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
                $result = $stock->getNearExpiry($branchId, $days);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
            case 'movements':
                $result = $stock->getMovements($productId, $branchId, $page, $limit);
                echo json_encode(['success' => true, 'data' => $result]);
                break;
            case 'single':
                $stockId = $_GET['stock_id'] ?? null;
                if ($stockId) {
                    $result = $stock->getStockById($stockId);
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Stock ID is required']);
                }
                break;
            default:
                if ($productId && $branchId) {
                    $result = $stock->getStock($productId, $branchId);
                    echo json_encode(['success' => true, 'data' => $result]);
                } else {
                    $result = $stock->getAllStock($branchId, $page, $limit, $search);
                    echo json_encode(['success' => true, 'data' => $result['data'], 'pagination' => $result['pagination']]);
                }
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $stock->addStock(
            $data['product_id'],
            $data['branch_id'] ?? $_SESSION['branch_id'],
            $data['quantity'],
            $data['unit'],
            $data['batch_number'] ?? null,
            $data['expiry_date'] ?? null,
            $data['supplier_id'] ?? null
        );
        echo json_encode(['success' => $result]);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $stockId = $data['stock_id'] ?? null;
        if (!$stockId) {
            echo json_encode(['success' => false, 'message' => 'Stock ID is required']);
            break;
        }
        $result = $stock->updateStock(
            $stockId,
            $data['quantity'],
            $data['unit'],
            $data['batch_number'] ?? null,
            $data['expiry_date'] ?? null,
            $data['supplier_id'] ?? null
        );
        echo json_encode(['success' => $result]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

