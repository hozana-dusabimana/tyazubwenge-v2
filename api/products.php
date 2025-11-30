<?php
// Start output buffering to catch any errors
ob_start();

// Set error handler to catch all errors
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../classes/Product.php';

    // Check login without redirecting (for API)
    if (!isLoggedIn()) {
        ob_clean();
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
        exit;
    }
    
    // Set JSON header
    header('Content-Type: application/json');

    $method = $_SERVER['REQUEST_METHOD'];
    $product = new Product();

    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $search = $_GET['search'] ?? '';
            
            if ($id) {
                $result = $product->getById($id);
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                $result = $product->getAll($page, $limit, $search);
                $total = $product->getTotalCount($search);
                $totalPages = $limit > 0 ? ceil($total / $limit) : 1;
                echo json_encode([
                    'success' => true,
                    'data' => $result ?: [],
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_items' => (int)$total,
                        'per_page' => $limit
                    ]
                ]);
            }
            break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $product->create($data);
        echo json_encode($result);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;
        if ($id) {
            unset($data['id']);
            $result = $product->update($id, $data);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $result = $product->delete($id);
            echo json_encode(['success' => $result]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID required']);
        }
        break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Products API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Products API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
} catch (Throwable $e) {
    // Clear any output
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Products API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}

// Restore error handler
restore_error_handler();

if (ob_get_level() > 0) {
    ob_end_flush();
}
?>

