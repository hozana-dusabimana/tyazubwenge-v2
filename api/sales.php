<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Sale.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$sale = new Sale();

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        
        if ($id) {
            $result = $sale->getById($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            $result = $sale->getAll($page, $limit, $branchId, $dateFrom, $dateTo);
            $total = $sale->getTotalCount($branchId, $dateFrom, $dateTo);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'per_page' => $limit
                ]
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $data['user_id'] = $_SESSION['user_id'];
        $data['branch_id'] = $data['branch_id'] ?? $_SESSION['branch_id'];
        $result = $sale->create($data);
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

