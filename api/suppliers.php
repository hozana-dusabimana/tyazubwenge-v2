<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Supplier.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$supplier = new Supplier();

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $search = $_GET['search'] ?? '';
        
        if ($id) {
            $result = $supplier->getById($id);
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            $result = $supplier->getAll($page, $limit, $search);
            $total = $supplier->getTotalCount($search);
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
        $result = $supplier->create($data);
        echo json_encode($result);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            unset($data['id']);
            $result = $supplier->update($id, $data);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $result = $supplier->delete($id);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

