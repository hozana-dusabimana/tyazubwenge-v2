<?php
/**
 * API Middleware for Token Authentication
 * Use this in API endpoints that need token-based auth
 */

function verifyApiToken() {
    $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_POST['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token required']);
        exit;
    }
    
    // Check session token (simple implementation)
    // For production, use database-stored tokens
    if (!isset($_SESSION['api_token']) || $_SESSION['api_token'] !== $token) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    // Check token expiration
    if (isset($_SESSION['api_token_expires']) && $_SESSION['api_token_expires'] < date('Y-m-d H:i:s')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token expired']);
        exit;
    }
    
    return true;
}

function getApiUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'branch_id' => $_SESSION['branch_id'] ?? null
    ];
}
?>

