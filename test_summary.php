<?php
/**
 * Test Summary Page
 * Quick overview of all test results
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Summary - Tyazubwenge Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .test-card { margin-bottom: 20px; }
        .status-badge { font-size: 0.9em; padding: 5px 10px; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-4">
        <h1 class="mb-4">🧪 Test Suite Summary</h1>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card test-card">
                    <div class="card-body">
                        <h5 class="card-title">1. Setup Verification</h5>
                        <p class="card-text">Quick verification of installation</p>
                        <a href="verify_setup.php" class="btn btn-primary" target="_blank">Run Verification</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card">
                    <div class="card-body">
                        <h5 class="card-title">2. System Tests</h5>
                        <p class="card-text">Database, classes, and structure tests</p>
                        <a href="test.php" class="btn btn-primary" target="_blank">Run Tests</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card">
                    <div class="card-body">
                        <h5 class="card-title">3. API Tests</h5>
                        <p class="card-text">REST API endpoint tests</p>
                        <a href="test_api_browser.html" class="btn btn-primary" target="_blank">Run Tests</a>
                        <small class="d-block mt-2 text-muted">Requires login</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card">
                    <div class="card-body">
                        <h5 class="card-title">4. Functional Tests</h5>
                        <p class="card-text">CRUD operations and business logic</p>
                        <a href="test_functionality.php" class="btn btn-primary" target="_blank">Run Tests</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card test-card">
                    <div class="card-body">
                        <h5 class="card-title">5. All Tests</h5>
                        <p class="card-text">Run all test suites together</p>
                        <a href="run_all_tests.php" class="btn btn-success" target="_blank">Run All</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Status Check</h5>
            </div>
            <div class="card-body">
                <?php
                // Quick status check
                $checks = [];
                
                // Check database
                try {
                    require_once 'config/database.php';
                    $db = new Database();
                    $conn = $db->getConnection();
                    if ($conn) {
                        $checks['database'] = ['status' => 'success', 'message' => 'Database connected'];
                    }
                } catch (Exception $e) {
                    $checks['database'] = ['status' => 'danger', 'message' => 'Database error: ' . $e->getMessage()];
                }
                
                // Check files
                $requiredFiles = ['config/config.php', 'classes/User.php', 'api/products.php', 'index.php'];
                $allFilesExist = true;
                foreach ($requiredFiles as $file) {
                    if (!file_exists($file)) {
                        $allFilesExist = false;
                        break;
                    }
                }
                $checks['files'] = $allFilesExist 
                    ? ['status' => 'success', 'message' => 'All required files exist']
                    : ['status' => 'warning', 'message' => 'Some files missing'];
                
                // Check tables
                if (isset($conn)) {
                    try {
                        $stmt = $conn->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $requiredTables = ['users', 'products', 'sales'];
                        $allTablesExist = true;
                        foreach ($requiredTables as $table) {
                            if (!in_array($table, $tables)) {
                                $allTablesExist = false;
                                break;
                            }
                        }
                        $checks['tables'] = $allTablesExist
                            ? ['status' => 'success', 'message' => 'All required tables exist']
                            : ['status' => 'warning', 'message' => 'Some tables missing'];
                    } catch (Exception $e) {
                        $checks['tables'] = ['status' => 'danger', 'message' => 'Error checking tables'];
                    }
                }
                
                foreach ($checks as $name => $check) {
                    $badgeClass = 'bg-' . $check['status'];
                    echo "<div class='mb-2'>";
                    echo "<span class='badge $badgeClass status-badge'>" . ucfirst($name) . "</span> ";
                    echo $check['message'];
                    echo "</div>";
                }
                ?>
            </div>
        </div>
        
        <div class="alert alert-info mt-4">
            <h5>📋 Testing Instructions</h5>
            <ol>
                <li>Start with <strong>Setup Verification</strong> to ensure basic setup is correct</li>
                <li>Run <strong>System Tests</strong> to verify all components</li>
                <li>Login to the system, then run <strong>API Tests</strong></li>
                <li>Run <strong>Functional Tests</strong> to test actual operations</li>
                <li>Use <strong>All Tests</strong> for comprehensive testing</li>
            </ol>
            <p class="mb-0"><strong>Note:</strong> All tests should pass before using the system in production.</p>
        </div>
    </div>
</body>
</html>

