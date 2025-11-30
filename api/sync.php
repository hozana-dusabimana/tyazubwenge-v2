<?php
// Start output buffering to catch any warnings/errors
ob_start();

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../classes/Stock.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Customer.php';

// Verify API token (optional - only if file exists)
if (file_exists(__DIR__ . '/token_helper.php')) {
    require_once __DIR__ . '/token_helper.php';
}

// Get token from multiple sources
$token = '';
$rawInput = null; // Store for later use in POST handler

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
    error_log("Token extracted from Authorization header: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
} else {
    error_log("No Authorization header found. Checking other sources...");
}

// If not in header, check GET parameter
if (empty($token) && isset($_GET['token'])) {
    $token = $_GET['token'];
    $token = trim($token);
}

// If still not found, check POST body (for JSON requests)
// Also read POST body for POST requests even if token is in header (needed for data)
if (empty($token) || $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read raw POST data for JSON requests (only if not already read)
    if ($rawInput === null) {
        $rawInput = file_get_contents('php://input');
    }
    if (!empty($rawInput) && empty($token)) {
        $postData = json_decode($rawInput, true);
        if (isset($postData['token'])) {
            $token = $postData['token'];
            $token = trim($token);
        }
    }
    
    // Also check $_POST array (for form-encoded requests)
    if (empty($token) && isset($_POST['token'])) {
        $token = $_POST['token'];
        $token = trim($token);
    }
}

// Token is optional - if provided, verify it; if not, proceed without authentication
$tokenData = null;
$branchId = null;
$userId = null;

if (!empty($token)) {
    error_log("Token provided, verifying... Token: " . substr($token, 0, 20) . "... (length: " . strlen($token) . ")");
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
    error_log("No token provided - proceeding without authentication");
}

// Log final status
error_log("=== Sync.php proceeding with branchId: " . ($branchId ?? 'NULL') . ", userId: " . ($userId ?? 'NULL') . " ===");

$method = $_SERVER['REQUEST_METHOD'];
$database = new Database();
$conn = $database->getConnection();
// branchId and userId are already set above if token was valid

switch ($method) {
    case 'GET':
        // Get sync status and last sync timestamp
        $lastSync = $_GET['last_sync'] ?? null;
        $entity = $_GET['entity'] ?? 'all';
        
        $response = [
            'success' => true,
            'server_time' => date('Y-m-d H:i:s'),
            'last_sync' => $lastSync,
            'data' => []
        ];
        
        if ($entity === 'all' || $entity === 'sales') {
            $sale = new Sale();
            $query = "SELECT COUNT(*) as count, MAX(updated_at) as last_update FROM sales WHERE branch_id = :branch_id";
            if ($lastSync) {
                $query .= " AND updated_at > :last_sync";
            }
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':branch_id', $branchId);
            if ($lastSync) {
                $stmt->bindParam(':last_sync', $lastSync);
            }
            $stmt->execute();
            $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['data']['sales'] = $salesData;
        }
        
        if ($entity === 'all' || $entity === 'stock') {
            $query = "SELECT COUNT(*) as count, MAX(updated_at) as last_update FROM stock_inventory WHERE branch_id = :branch_id";
            if ($lastSync) {
                $query .= " AND updated_at > :last_sync";
            }
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':branch_id', $branchId);
            if ($lastSync) {
                $stmt->bindParam(':last_sync', $lastSync);
            }
            $stmt->execute();
            $stockData = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['data']['stock'] = $stockData;
        }
        
        if ($entity === 'all' || $entity === 'products') {
            $query = "SELECT COUNT(*) as count, MAX(updated_at) as last_update FROM products";
            if ($lastSync) {
                $query .= " WHERE updated_at > :last_sync";
            }
            $stmt = $conn->prepare($query);
            if ($lastSync) {
                $stmt->bindParam(':last_sync', $lastSync);
            }
            $stmt->execute();
            $productsData = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['data']['products'] = $productsData;
        }
        
        echo json_encode($response);
        break;
        
    case 'POST':
        // Bulk sync data from desktop app
        // Use stored raw input if available (from token extraction), otherwise read it
        if ($rawInput === null) {
            $rawInput = file_get_contents('php://input');
        }
        $data = json_decode($rawInput, true);
        
        if (!isset($data['entity']) || !isset($data['records'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Entity and records are required']);
            exit;
        }
        
        $entity = $data['entity'];
        $records = $data['records'];
        $results = [
            'success' => true,
            'synced' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            // Don't use a single transaction for all records
            // Instead, commit each record individually to allow partial success
            // $conn->beginTransaction(); // Removed - commit each record individually
            
            foreach ($records as $record) {
                try {
                    // Start a transaction for this individual record
                    $conn->beginTransaction();
                    
                    $recordProcessed = false;
                    
                    switch ($entity) {
                        case 'sales':
                            $sale = new Sale();
                            // Check if sale exists (by local_id or invoice_number)
                            $localId = $record['local_id'] ?? null;
                            $invoiceNumber = $record['invoice_number'] ?? null;
                            
                            if ($localId) {
                                // Check if already synced
                                $checkQuery = "SELECT id FROM sales WHERE local_id = :local_id";
                                $checkStmt = $conn->prepare($checkQuery);
                                $checkStmt->bindParam(':local_id', $localId);
                                $checkStmt->execute();
                                if ($checkStmt->fetch()) {
                                    $conn->rollBack(); // Rollback transaction for skipped record
                                    $recordProcessed = true;
                                    break; // Already synced - break from switch, continue to next record
                                }
                            }
                            
                            // Handle invoice_number if provided (for offline sales)
                            if (isset($record['invoice_number'])) {
                                // Check if invoice already exists
                                $checkInvoiceQuery = "SELECT id FROM sales WHERE invoice_number = :invoice_number";
                                $checkInvoiceStmt = $conn->prepare($checkInvoiceQuery);
                                $checkInvoiceStmt->bindParam(':invoice_number', $record['invoice_number']);
                                $checkInvoiceStmt->execute();
                                if ($checkInvoiceStmt->fetch()) {
                                    $conn->rollBack(); // Rollback transaction for skipped record
                                    $recordProcessed = true;
                                    break; // Already exists - break from switch, continue to next record
                                }
                            }
                            
                            // Set user_id and branch_id - use from record if available, otherwise from token, otherwise default
                            $record['user_id'] = $record['user_id'] ?? $userId ?? 1;
                            $record['branch_id'] = $record['branch_id'] ?? $branchId ?? 1;
                            $record['created_at'] = $record['created_at'] ?? date('Y-m-d H:i:s');
                            
                            // Map field names if needed
                            if (isset($record['subtotal'])) {
                                $record['total_amount'] = $record['subtotal'];
                            }
                            if (!isset($record['payment_status'])) {
                                $record['payment_status'] = 'completed';
                            }
                            if (!isset($record['sale_type'])) {
                                $record['sale_type'] = 'retail';
                            }
                            
                            // Ensure items array exists and is not empty
                            if (!isset($record['items']) || !is_array($record['items']) || empty($record['items'])) {
                                $conn->rollBack(); // Rollback this failed record
                                $results['failed']++;
                                $results['errors'][] = 'Failed to sync sale: Missing or empty items array';
                                error_log("Sale sync failed: Missing or empty items array. Record: " . json_encode($record));
                                $recordProcessed = true;
                                break; // Break from switch, continue to next record
                            }
                            
                            // Ensure all required fields have defaults
                            if (!isset($record['discount'])) {
                                $record['discount'] = 0;
                            }
                            if (!isset($record['tax'])) {
                                $record['tax'] = 0;
                            }
                            if (!isset($record['notes'])) {
                                $record['notes'] = null;
                            }
                            
                            // Log sale data being synced
                            $totalAmount = isset($record['total_amount']) ? $record['total_amount'] : 'null';
                            $itemsCount = isset($record['items']) ? count($record['items']) : 0;
                            error_log("Syncing sale: user_id={$record['user_id']}, branch_id={$record['branch_id']}, total_amount={$totalAmount}, items_count={$itemsCount}");
                            
                            $result = $sale->create($record);
                            
                            if ($result && isset($result['success']) && $result['success'] && isset($result['sale_id'])) {
                                $saleId = $result['sale_id'];
                                
                                // Store local_id in sales table and mapping
                                if ($localId) {
                                    $updateLocalQuery = "UPDATE sales SET local_id = :local_id WHERE id = :id";
                                    $updateLocalStmt = $conn->prepare($updateLocalQuery);
                                    $updateLocalStmt->bindParam(':local_id', $localId);
                                    $updateLocalStmt->bindParam(':id', $saleId);
                                    $updateLocalStmt->execute();
                                    
                                    $mapQuery = "INSERT INTO sync_mappings (entity_type, local_id, server_id, created_at) 
                                                VALUES ('sales', :local_id, :server_id, NOW())
                                                ON DUPLICATE KEY UPDATE server_id = :server_id_update";
                                    $mapStmt = $conn->prepare($mapQuery);
                                    $mapStmt->bindParam(':local_id', $localId);
                                    $mapStmt->bindParam(':server_id', $saleId);
                                    $mapStmt->bindParam(':server_id_update', $saleId);
                                    $mapStmt->execute();
                                }
                                $results['synced']++;
                                $conn->commit(); // Commit this successful record
                            } else {
                                $conn->rollBack(); // Rollback this failed record
                                $results['failed']++;
                                $errorMsg = 'Failed to sync sale: ' . (isset($result['message']) ? $result['message'] : 'Unknown error');
                                $results['errors'][] = $errorMsg;
                                error_log("Sale sync failed. Result: " . json_encode($result));
                                error_log("Sale record data: " . json_encode($record));
                            }
                            break;
                            
                        case 'stock':
                            $stock = new Stock();
                            $localId = $record['local_id'] ?? null;
                            
                            if ($localId) {
                                $checkQuery = "SELECT id FROM stock_inventory WHERE local_id = :local_id";
                                $checkStmt = $conn->prepare($checkQuery);
                                $checkStmt->bindParam(':local_id', $localId);
                                $checkStmt->execute();
                                if ($checkStmt->fetch()) {
                                    $conn->rollBack(); // Rollback transaction for skipped record
                                    $recordProcessed = true;
                                    break; // Break from switch, continue to next record
                                }
                            }
                            
                            // Use branch_id from record if available, otherwise use from token, otherwise default to 1
                            $stockBranchId = $record['branch_id'] ?? $branchId ?? 1;
                            
                            // Log stock data being synced
                            $unit = isset($record['unit']) ? $record['unit'] : 'null';
                            error_log("Syncing stock: product_id={$record['product_id']}, branch_id={$stockBranchId}, quantity={$record['quantity']}, unit={$unit}");
                            
                            $result = $stock->addStock(
                                $record['product_id'],
                                $stockBranchId,
                                $record['quantity'],
                                $record['unit'] ?? null,
                                $record['batch_number'] ?? null,
                                $record['expiry_date'] ?? null,
                                $record['supplier_id'] ?? null
                            );
                            
                            if ($result === true) {
                                // addStock returns true on success, need to get the stock ID
                                $stockQuery = "SELECT id FROM stock_inventory WHERE product_id = :product_id AND branch_id = :branch_id ORDER BY id DESC LIMIT 1";
                                $stockStmt = $conn->prepare($stockQuery);
                                $stockStmt->bindParam(':product_id', $record['product_id']);
                                $stockStmt->bindParam(':branch_id', $stockBranchId);
                                $stockStmt->execute();
                                $stockData = $stockStmt->fetch(PDO::FETCH_ASSOC);
                                $stockId = $stockData ? $stockData['id'] : null;
                                
                                // Note: cost_price is stored in products table, not stock_inventory
                                // If cost_price needs to be updated, it should be done via product update
                                // For now, we skip cost_price updates during stock sync
                                
                                // Store local_id in stock_inventory and mapping
                                if ($localId && $stockId) {
                                    $updateLocalQuery = "UPDATE stock_inventory SET local_id = :local_id WHERE id = :id";
                                    $updateLocalStmt = $conn->prepare($updateLocalQuery);
                                    $updateLocalStmt->bindParam(':local_id', $localId);
                                    $updateLocalStmt->bindParam(':id', $stockId);
                                    $updateLocalStmt->execute();
                                    
                                    $mapQuery = "INSERT INTO sync_mappings (entity_type, local_id, server_id, created_at) 
                                                VALUES ('stock', :local_id, :server_id, NOW())
                                                ON DUPLICATE KEY UPDATE server_id = :server_id_update";
                                    $mapStmt = $conn->prepare($mapQuery);
                                    $mapStmt->bindParam(':local_id', $localId);
                                    $mapStmt->bindParam(':server_id', $stockId);
                                    $mapStmt->bindParam(':server_id_update', $stockId);
                                    $mapStmt->execute();
                                }
                                
                                $results['synced']++;
                                $conn->commit(); // Commit this successful record
                            } else {
                                $conn->rollBack(); // Rollback this failed record
                                $results['failed']++;
                                $errorMsg = 'Failed to sync stock: Product not found or invalid data';
                                $results['errors'][] = $errorMsg;
                                error_log("Stock sync failed. Result: " . ($result === false ? 'false' : json_encode($result)));
                                error_log("Stock record data: " . json_encode($record));
                            }
                            break;
                            
                        case 'customers':
                            $customer = new Customer();
                            $localId = $record['local_id'] ?? null;
                            
                            if ($localId) {
                                $checkQuery = "SELECT id FROM customers WHERE local_id = :local_id";
                                $checkStmt = $conn->prepare($checkQuery);
                                $checkStmt->bindParam(':local_id', $localId);
                                $checkStmt->execute();
                                if ($checkStmt->fetch()) {
                                    $conn->rollBack(); // Rollback transaction for skipped record
                                    $recordProcessed = true;
                                    break; // Break from switch, continue to next record
                                }
                            }
                            
                            $record['created_at'] = $record['created_at'] ?? date('Y-m-d H:i:s');
                            
                            // Set default values for customer if not provided
                            if (!isset($record['loyalty_points'])) {
                                $record['loyalty_points'] = 0;
                            }
                            if (!isset($record['credit_limit'])) {
                                $record['credit_limit'] = 0;
                            }
                            if (!isset($record['status'])) {
                                $record['status'] = 'active';
                            }
                            
                            // Log customer data being synced
                            error_log("Syncing customer: " . json_encode($record));
                            
                            $result = $customer->create($record);
                            
                            if ($result && is_array($result) && isset($result['success']) && $result['success']) {
                                $customerId = $result['id'] ?? null;
                                
                                // Store local_id in customers table and mapping
                                if ($localId) {
                                    $updateLocalQuery = "UPDATE customers SET local_id = :local_id WHERE id = :id";
                                    $updateLocalStmt = $conn->prepare($updateLocalQuery);
                                    $updateLocalStmt->bindParam(':local_id', $localId);
                                    $updateLocalStmt->bindParam(':id', $customerId);
                                    $updateLocalStmt->execute();
                                    
                                    $mapQuery = "INSERT INTO sync_mappings (entity_type, local_id, server_id, created_at) 
                                                VALUES ('customers', :local_id, :server_id, NOW())
                                                ON DUPLICATE KEY UPDATE server_id = :server_id_update";
                                    $mapStmt = $conn->prepare($mapQuery);
                                    $mapStmt->bindParam(':local_id', $localId);
                                    $mapStmt->bindParam(':server_id', $customerId);
                                    $mapStmt->bindParam(':server_id_update', $customerId);
                                    $mapStmt->execute();
                                }
                                $results['synced']++;
                                $conn->commit(); // Commit this successful record
                            } else {
                                $conn->rollBack(); // Rollback this failed record
                                $results['failed']++;
                                $errorMsg = 'Failed to sync customer: ' . (is_array($result) && isset($result['message']) ? $result['message'] : 'Unknown error');
                                $results['errors'][] = $errorMsg;
                                error_log("Customer sync failed. Result: " . json_encode($result));
                                error_log("Customer record data: " . json_encode($record));
                            }
                            break;
                            
                        default:
                            $conn->rollBack(); // Rollback this failed record
                            $results['failed']++;
                            $results['errors'][] = "Unknown entity type: $entity";
                            $recordProcessed = true;
                    }
                    
                    // Skip to next record if this one was already processed (duplicate, etc.)
                    if ($recordProcessed) {
                        continue;
                    }
                } catch (Exception $e) {
                    // Rollback this failed record
                    try {
                        $conn->rollBack();
                    } catch (Exception $rollbackEx) {
                        error_log("Error rolling back transaction: " . $rollbackEx->getMessage());
                    }
                    $results['failed']++;
                    $errorMsg = $e->getMessage();
                    $results['errors'][] = $errorMsg;
                    error_log("Error syncing {$entity} record: " . $errorMsg);
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            }
            
            // No need for final commit - each record is committed individually
            // $conn->commit(); // Removed
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'results' => $results
            ]);
            exit;
        }
        
        // Clean any output before sending JSON
        ob_clean();
        echo json_encode($results);
        ob_end_flush();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>

