<?php
/**
 * Master Test Runner
 * Runs all test suites and provides comprehensive report
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Test Suite - Tyazubwenge Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #0d6efd; }
        .test-suite { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border: 2px solid #dee2e6; }
        .test-suite h2 { margin-top: 0; }
        iframe { width: 100%; height: 600px; border: 1px solid #dee2e6; border-radius: 5px; }
        .summary { margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0b5ed7; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Complete Test Suite - Tyazubwenge Management System</h1>
        <p>This page runs all test suites to verify system functionality.</p>
        
        <div style='margin: 20px 0;'>
            <a href='test.php' class='btn' target='_blank'>Run System Tests</a>
            <a href='test_api.php' class='btn' target='_blank'>Run API Tests</a>
            <a href='test_functionality.php' class='btn' target='_blank'>Run Functional Tests</a>
        </div>
        
        <div class='test-suite'>
            <h2>1. System Tests</h2>
            <p>Tests database connections, class instantiation, and basic functionality.</p>
            <iframe src='test.php'></iframe>
        </div>
        
        <div class='test-suite'>
            <h2>2. API Endpoint Tests</h2>
            <p>Tests all API endpoints for proper responses.</p>
            <iframe src='test_api.php'></iframe>
        </div>
        
        <div class='test-suite'>
            <h2>3. Functional Tests</h2>
            <p>Tests actual functionality with sample data operations.</p>
            <iframe src='test_functionality.php'></iframe>
        </div>
        
        <div class='summary'>
            <h2>📋 Test Instructions</h2>
            <ol>
                <li><strong>System Tests:</strong> Verifies database connections, tables, classes, and helper functions</li>
                <li><strong>API Tests:</strong> Tests all REST API endpoints for proper JSON responses</li>
                <li><strong>Functional Tests:</strong> Tests actual CRUD operations with sample data</li>
            </ol>
            
            <h3>✅ What to Check:</h3>
            <ul>
                <li>All database tables exist</li>
                <li>All classes can be instantiated</li>
                <li>API endpoints return proper JSON responses</li>
                <li>CRUD operations work correctly</li>
                <li>No PHP errors or warnings</li>
            </ul>
            
            <h3>🔧 If Tests Fail:</h3>
            <ul>
                <li>Check database connection in <code>config/database.php</code></li>
                <li>Verify database schema is imported correctly</li>
                <li>Check file permissions</li>
                <li>Review PHP error logs</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>

