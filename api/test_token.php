<?php
/**
 * Test endpoint to verify token storage and retrieval
 * Usage: GET /api/test_token.php?token=YOUR_TOKEN
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/token_helper.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token parameter required']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$result = [
    'success' => true,
    'token_received' => substr($token, 0, 20) . '...',
    'token_length' => strlen($token),
    'checks' => []
];

// Check 1: Table exists
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'api_tokens'");
    $result['checks']['table_exists'] = $tableCheck->rowCount() > 0;
} catch (Exception $e) {
    $result['checks']['table_exists'] = false;
    $result['checks']['table_error'] = $e->getMessage();
}

// Check 2: Token exists in database
try {
    $stmt = $conn->prepare("SELECT id, user_id, token, expires_at, last_used_at, LENGTH(token) as token_len FROM api_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $dbToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbToken) {
        $result['checks']['token_found'] = true;
        $result['checks']['token_data'] = [
            'id' => $dbToken['id'],
            'user_id' => $dbToken['user_id'],
            'expires_at' => $dbToken['expires_at'],
            'token_length_db' => $dbToken['token_len'],
            'is_expired' => $dbToken['expires_at'] <= date('Y-m-d H:i:s'),
            'token_match' => $dbToken['token'] === $token
        ];
    } else {
        $result['checks']['token_found'] = false;
        
        // List all tokens
        $allStmt = $conn->prepare("SELECT id, user_id, LEFT(token, 20) as token_preview, expires_at FROM api_tokens ORDER BY id DESC LIMIT 10");
        $allStmt->execute();
        $result['checks']['all_tokens'] = $allStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $result['checks']['token_found'] = false;
    $result['checks']['token_error'] = $e->getMessage();
}

// Check 3: Verify using helper function
try {
    $tokenData = verifyApiToken($token);
    $result['checks']['verify_helper'] = [
        'valid' => $tokenData && $tokenData['valid'] ?? false,
        'data' => $tokenData
    ];
} catch (Exception $e) {
    $result['checks']['verify_helper'] = [
        'valid' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>

