<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Stock.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Sale.php';

// Verify API token (optional - only if file exists)
if (file_exists(__DIR__ . '/token_helper.php')) {
    require_once __DIR__ . '/token_helper.php';
}

// Get token from multiple sources
$token = '';

// First check Authorization header (most common for API)
// Note: Some servers use REDIRECT_HTTP_AUTHORIZATION or HTTP_AUTHORIZATION
$authHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authHeader = $headers['authorization'];
    }
}

if ($authHeader) {
    $token = str_replace('Bearer ', '', $authHeader);
    $token = trim($token);
    error_log("Token extracted from Authorization header in download.php: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
} else {
    error_log("No Authorization header found in download.php. Checking GET parameter...");
}

// If not in header, check GET parameter
if (empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
    $token = trim($token);
    error_log("Token extracted from GET parameter in download.php: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
}

// Also check POST parameter
if (empty($token) && isset($_POST['token'])) {
    $token = $_POST['token'];
    $token = trim($token);
    error_log("Token extracted from POST parameter in download.php: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
}

// Token is optional - if provided, verify it; if not, proceed without authentication
$tokenData = null;
$branchId = null;
$userId = null;

if (!empty($token)) {
    error_log("Token provided in download.php, verifying... Token: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
    if (function_exists('verifyApiToken')) {
        $tokenData = verifyApiToken($token);
    } else {
        error_log("verifyApiToken function not available - token_helper.php may be missing");
        $tokenData = null;
    }
    if ($tokenData && $tokenData['valid']) {
        $branchId = $tokenData['branch_id'] ?? null;
        $userId = $tokenData['user_id'];
        error_log("Token verified successfully. User ID: " . $userId . ", Branch ID: " . ($branchId ?? 'NULL'));
    } else {
        error_log("Token verification failed - token not found or invalid. Continuing without authentication.");
        $tokenData = null;
    }
} else {
    error_log("No token provided in download.php - proceeding without authentication");
}

// Log final status
error_log("=== Download.php proceeding with branchId: " . ($branchId ?? 'NULL') . ", userId: " . ($userId ?? 'NULL') . " ===");

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$entity = $_GET['entity'] ?? 'all';
$lastSync = $_GET['last_sync'] ?? null;
// branchId is already set above if token was valid

$database = new Database();
$conn = $database->getConnection();

$response = [
    'success' => true,
    'server_time' => date('Y-m-d H:i:s'),
    'data' => []
];

try {
    // Download products
    if ($entity === 'all' || $entity === 'products') {
        $product = new Product();
        $query = "SELECT * FROM products";
        $params = [];
        
        if ($lastSync) {
            $query .= " WHERE updated_at > :last_sync1 OR created_at > :last_sync2";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Download stock
    if ($entity === 'all' || $entity === 'stock') {
        $query = "SELECT si.*, p.name as product_name, p.sku, p.unit as product_unit 
                  FROM stock_inventory si 
                  JOIN products p ON si.product_id = p.id";
        $params = [];
        $conditions = [];
        
        if ($branchId !== null) {
            $conditions[] = "si.branch_id = :branch_id";
            $params[':branch_id'] = $branchId;
        }
        
        if ($lastSync) {
            // Use updated_at or last_updated (stock_inventory doesn't have created_at)
            $conditions[] = "(si.updated_at > :last_sync1 OR si.last_updated > :last_sync2)";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY si.id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['stock'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Download customers
    if ($entity === 'all' || $entity === 'customers') {
        $query = "SELECT * FROM customers";
        $params = [];
        
        if ($lastSync) {
            $query .= " WHERE updated_at > :last_sync1 OR created_at > :last_sync2";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Download sales
    if ($entity === 'all' || $entity === 'sales') {
        $query = "SELECT s.* FROM sales s";
        $params = [];
        $conditions = [];
        
        if ($branchId !== null) {
            $conditions[] = "s.branch_id = :branch_id";
            $params[':branch_id'] = $branchId;
        }
        
        if ($lastSync) {
            $conditions[] = "(s.updated_at > :last_sync1 OR s.created_at > :last_sync2)";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY s.id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sale items separately for each sale
        foreach ($sales as &$sale) {
            $itemsQuery = "SELECT * FROM sale_items WHERE sale_id = :sale_id";
            $itemsStmt = $conn->prepare($itemsQuery);
            $itemsStmt->bindParam(':sale_id', $sale['id']);
            $itemsStmt->execute();
            $sale['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $response['data']['sales'] = $sales;
    }
    
    // Download categories
    if ($entity === 'all' || $entity === 'categories') {
        $query = "SELECT * FROM categories";
        $params = [];
        
        if ($lastSync) {
            $query .= " WHERE updated_at > :last_sync1 OR created_at > :last_sync2";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Download brands
    if ($entity === 'all' || $entity === 'brands') {
        $query = "SELECT * FROM brands";
        $params = [];
        
        if ($lastSync) {
            $query .= " WHERE updated_at > :last_sync1 OR created_at > :last_sync2";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['brands'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Download suppliers
    if ($entity === 'all' || $entity === 'suppliers') {
        $query = "SELECT * FROM suppliers";
        $params = [];
        
        if ($lastSync) {
            $query .= " WHERE updated_at > :last_sync1 OR created_at > :last_sync2";
            $params[':last_sync1'] = $lastSync;
            $params[':last_sync2'] = $lastSync;
        }
        
        $query .= " ORDER BY id ASC";
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $response['data']['suppliers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Download failed: ' . $e->getMessage()
    ]);
}
?>

