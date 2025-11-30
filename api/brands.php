<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$conn = $database->getConnection();

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $search = $_GET['search'] ?? '';
        
        if ($id) {
            $query = "SELECT * FROM brands WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $result = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $result]);
        } else {
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM brands WHERE 1=1";
            if (!empty($search)) {
                // Use separate parameters and make search case-insensitive
                $countQuery .= " AND (LOWER(name) LIKE LOWER(:search1) OR LOWER(description) LIKE LOWER(:search2))";
            }
            $countStmt = $conn->prepare($countQuery);
            if (!empty($search)) {
                $searchParam = "%{$search}%";
                // Bind the same search parameter to all placeholders
                $countStmt->bindValue(':search1', $searchParam);
                $countStmt->bindValue(':search2', $searchParam);
            }
            $countStmt->execute();
            $totalItems = $countStmt->fetch()['total'];
            $totalPages = ceil($totalItems / $limit);
            
            // Get data
            $offset = ($page - 1) * $limit;
            $query = "SELECT * FROM brands WHERE 1=1";
            if (!empty($search)) {
                // Use separate parameters and make search case-insensitive
                $query .= " AND (LOWER(name) LIKE LOWER(:search1) OR LOWER(description) LIKE LOWER(:search2))";
            }
            $query .= " ORDER BY name LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($query);
            if (!empty($search)) {
                $searchParam = "%{$search}%";
                // Bind the same search parameter to all placeholders
                $stmt->bindValue(':search1', $searchParam);
                $stmt->bindValue(':search2', $searchParam);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'per_page' => $limit
                ]
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $query = "INSERT INTO brands (name, description) VALUES (:name, :description)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $result = $stmt->execute();
        echo json_encode(['success' => $result, 'id' => $result ? $conn->lastInsertId() : null]);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            $query = "UPDATE brands SET name = :name, description = :description WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':id', $id);
            $result = $stmt->execute();
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Check if brand is used by products
            $checkQuery = "SELECT COUNT(*) as count FROM products WHERE brand_id = :id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':id', $id);
            $checkStmt->execute();
            $check = $checkStmt->fetch();
            
            if ($check['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete brand. It is being used by ' . $check['count'] . ' product(s).']);
            } else {
                $query = "DELETE FROM brands WHERE id = :id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':id', $id);
                $result = $stmt->execute();
                echo json_encode(['success' => $result]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

