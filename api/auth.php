<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        // Login and get API token
        $data = json_decode(file_get_contents('php://input'), true);
        $username = $data['username'] ?? $_POST['username'] ?? '';
        $password = $data['password'] ?? $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit;
        }
        
        $user = new User();
        $result = $user->login($username, $password);
        
        if ($result['success']) {
            // Get user ID from result (not session, for desktop app compatibility)
            $userId = $result['user']['id'] ?? $_SESSION['user_id'] ?? null;
            $userRole = $result['user']['role'] ?? $_SESSION['role'] ?? null;
            $userBranchId = $result['user']['branch_id'] ?? $_SESSION['branch_id'] ?? null;
            $userUsername = $result['user']['username'] ?? $_SESSION['username'] ?? $username;
            
            if (!$userId) {
                error_log("Error: User ID not available after login");
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to retrieve user information']);
                exit;
            }
            
            // Generate API token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Store token in database (not session)
            require_once __DIR__ . '/token_helper.php';
            $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Desktop App';
            $stored = storeApiToken($userId, $token, $expires, $deviceName);
            if (!$stored) {
                error_log("Failed to store API token for user " . $userId);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to store authentication token']);
                exit;
            }
            
            // Also store in session for web app compatibility (if session is available)
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['api_token'] = $token;
                $_SESSION['api_token_expires'] = $expires;
            }
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'expires' => $expires,
                'user' => [
                    'id' => $userId,
                    'username' => $userUsername,
                    'role' => $userRole,
                    'branch_id' => $userBranchId
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        break;
        
    case 'GET':
        // Verify token
        $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        
        if (empty($token)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Token required']);
            exit;
        }
        
        // Check token in database first (for desktop app)
        require_once __DIR__ . '/token_helper.php';
        $tokenData = verifyApiToken($token);
        
        if ($tokenData && $tokenData['valid']) {
            echo json_encode([
                'success' => true,
                'valid' => true,
                'user' => [
                    'id' => $tokenData['user_id'],
                    'username' => $tokenData['username'],
                    'role' => $tokenData['role'],
                    'branch_id' => $tokenData['branch_id'] ?? null
                ]
            ]);
        } else {
            // Fallback to session check (for web app)
            if (isset($_SESSION['api_token']) && $_SESSION['api_token'] === $token) {
                if (isset($_SESSION['api_token_expires']) && $_SESSION['api_token_expires'] > date('Y-m-d H:i:s')) {
                    echo json_encode([
                        'success' => true,
                        'valid' => true,
                        'user' => [
                            'id' => $_SESSION['user_id'],
                            'username' => $_SESSION['username'],
                            'role' => $_SESSION['role'],
                            'branch_id' => $_SESSION['branch_id'] ?? null
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Token expired']);
                }
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

