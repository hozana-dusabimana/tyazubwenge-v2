<?php
/**
 * API Endpoint Test Script
 * Tests all API endpoints
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for authentication
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Test Admin';
$_SESSION['role'] = 'admin';
$_SESSION['branch_id'] = 1;

echo "<!DOCTYPE html>
<html>
<head>
    <title>API Tests - Tyazubwenge Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #0d6efd; }
        .api-test { margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .api-test h3 { margin-top: 0; }
        .endpoint { font-family: monospace; background: #e9ecef; padding: 5px 10px; border-radius: 3px; }
        .response { margin-top: 10px; padding: 10px; background: white; border-left: 4px solid #0d6efd; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        pre { margin: 5px 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class='test-container'>
        <h1>🔌 API Endpoint Tests</h1>";

function testAPI($name, $endpoint, $method = 'GET', $data = null) {
    echo "<div class='api-test'>";
    echo "<h3>$name</h3>";
    echo "<div class='endpoint'>$method: $endpoint</div>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/tyazubwenge_v2/$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST' || $method === 'PUT') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    // Set session cookie
    $cookie = session_name() . '=' . session_id();
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    $isSuccess = $httpCode == 200 && isset($responseData['success']) && $responseData['success'];
    
    echo "<div class='response " . ($isSuccess ? 'success' : 'error') . "'>";
    echo "<strong>HTTP Code:</strong> $httpCode<br>";
    echo "<strong>Response:</strong>";
    echo "<pre>" . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div>";
    echo "</div>";
    
    return $isSuccess;
}

// Test Products API
testAPI("Get Products", "api/products.php?page=1&limit=10");
testAPI("Get Product by ID", "api/products.php?id=1");

// Test Stock API
testAPI("Get Stock", "api/stock.php?page=1&limit=10");
testAPI("Get Low Stock", "api/stock.php?type=low_stock");
testAPI("Get Near Expiry", "api/stock.php?type=near_expiry");

// Test Customers API
testAPI("Get Customers", "api/customers.php?page=1&limit=10");
testAPI("Get Customer by ID", "api/customers.php?id=1");

// Test Sales API
testAPI("Get Sales", "api/sales.php?page=1&limit=10");

// Test Reports API
testAPI("Sales Report", "api/reports.php?type=sales");
testAPI("Top Products", "api/reports.php?type=top_products&limit=10");
testAPI("Stock Valuation", "api/reports.php?type=stock_valuation");
testAPI("Profit Loss", "api/reports.php?type=profit_loss");
testAPI("Custom Report - Sales by Customer", "api/reports.php?type=custom&custom_type=sales_by_customer");

// Test Search API
testAPI("Search Products", "api/search.php?term=test");

// Test Categories API
testAPI("Get Categories", "api/categories.php");

// Test Brands API
testAPI("Get Brands", "api/brands.php");

// Test Suppliers API
testAPI("Get Suppliers", "api/suppliers.php?page=1&limit=10");

// Test Users API (Admin only)
testAPI("Get Users", "api/users.php?page=1&limit=10");

echo "<div style='margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;'>";
echo "<h2>✅ API Tests Complete</h2>";
echo "<p>Review the responses above. All endpoints should return JSON with a 'success' field.</p>";
echo "</div>";

echo "</div></body></html>";
?>

