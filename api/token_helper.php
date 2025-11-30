<?php
/**
 * Token Helper Functions for API Authentication
 * Handles token storage and validation in database (not sessions)
 */

require_once __DIR__ . '/../config/database.php';

function storeApiToken($userId, $token, $expiresAt, $deviceName = null) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'api_tokens'");
        if ($tableCheck->rowCount() == 0) {
            error_log("api_tokens table does not exist. Please run database migration.");
            return false;
        }
        
        // Delete old tokens for this user (optional - you might want to keep multiple)
        // $conn->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([$userId]);
        
        // Log token being stored
        error_log("Storing API token for user $userId. Token length: " . strlen($token) . ", Token preview: " . substr($token, 0, 20) . "...");
        
        // Insert new token (use INSERT IGNORE or ON DUPLICATE KEY UPDATE to handle duplicates)
        $stmt = $conn->prepare("INSERT INTO api_tokens (user_id, token, device_name, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at), last_used_at = NULL");
        $stmt->execute([$userId, $token, $deviceName, $expiresAt]);
        
        // Verify token was stored correctly
        $verifyStmt = $conn->prepare("SELECT token, LENGTH(token) as token_len FROM api_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $verifyStmt->execute([$userId]);
        $storedToken = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($storedToken) {
            if ($storedToken['token'] === $token) {
                error_log("Token stored successfully and verified. Length: " . $storedToken['token_len']);
                return true;
            } else {
                error_log("ERROR: Token mismatch! Stored token length: " . $storedToken['token_len'] . ", Expected: " . strlen($token));
                error_log("Stored token preview: " . substr($storedToken['token'], 0, 20) . "...");
                error_log("Expected token preview: " . substr($token, 0, 20) . "...");
                return false;
            }
        } else {
            error_log("ERROR: Token not found after insert!");
            return false;
        }
    } catch (Exception $e) {
        error_log("Error storing API token: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

function verifyApiToken($token) {
    if (empty($token)) {
        return null;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // First check if api_tokens table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'api_tokens'");
        if ($tableCheck->rowCount() == 0) {
            error_log("api_tokens table does not exist");
            return null;
        }
        
        // Get token from database
        // First try exact match
        $stmt = $conn->prepare("
            SELECT at.*, u.id as user_id, u.username, u.email, u.full_name, u.role, u.branch_id 
            FROM api_tokens at
            JOIN users u ON at.user_id = u.id
            WHERE at.token = ? AND at.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found, log for debugging
        if (!$tokenData) {
            error_log("Token verification: Token not found or expired. Token length: " . strlen($token) . ", Preview: " . substr($token, 0, 20) . "...");
            
            // Check if token exists but expired
            $expiredStmt = $conn->prepare("SELECT id, expires_at, NOW() as current_time FROM api_tokens WHERE token = ?");
            $expiredStmt->execute([$token]);
            $expiredToken = $expiredStmt->fetch(PDO::FETCH_ASSOC);
            if ($expiredToken) {
                error_log("Token exists but expired. Expires: " . $expiredToken['expires_at'] . ", Now: " . $expiredToken['current_time']);
            } else {
                error_log("Token does not exist in database at all.");
            }
        }
        
        if ($tokenData) {
            // Update last_used_at
            $updateStmt = $conn->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
            $updateStmt->execute([$tokenData['id']]);
            
            return [
                'valid' => true,
                'user_id' => $tokenData['user_id'],
                'username' => $tokenData['username'],
                'email' => $tokenData['email'],
                'full_name' => $tokenData['full_name'],
                'role' => $tokenData['role'],
                'branch_id' => $tokenData['branch_id']
            ];
        }
        
        // If not found in database, check session as fallback (for web app)
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['api_token']) && $_SESSION['api_token'] === $token) {
            if (isset($_SESSION['api_token_expires']) && $_SESSION['api_token_expires'] > date('Y-m-d H:i:s')) {
                return [
                    'valid' => true,
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'username' => $_SESSION['username'] ?? null,
                    'email' => $_SESSION['email'] ?? null,
                    'full_name' => $_SESSION['full_name'] ?? null,
                    'role' => $_SESSION['role'] ?? null,
                    'branch_id' => $_SESSION['branch_id'] ?? null
                ];
            }
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error verifying API token: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return null;
    }
}

function getApiUser($token) {
    $tokenData = verifyApiToken($token);
    if ($tokenData && $tokenData['valid']) {
        return [
            'id' => $tokenData['user_id'],
            'username' => $tokenData['username'],
            'email' => $tokenData['email'],
            'full_name' => $tokenData['full_name'],
            'role' => $tokenData['role'],
            'branch_id' => $tokenData['branch_id']
        ];
    }
    return null;
}

function revokeApiToken($token) {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        $stmt = $conn->prepare("DELETE FROM api_tokens WHERE token = ?");
        $stmt->execute([$token]);
        return true;
    } catch (Exception $e) {
        error_log("Error revoking API token: " . $e->getMessage());
        return false;
    }
}

?>

