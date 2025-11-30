<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('BASE_URL', 'http://localhost/tyazubwenge_v2/');
define('SITE_NAME', 'Tyazubwenge Management System');

// Timezone
date_default_timezone_set('Africa/Kigali');


// Include database
require_once __DIR__ . '/database.php';

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        // Check if we're already on login or home page
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($currentPage !== 'login.php' && $currentPage !== 'home.php') {
            redirect('login.php');
        }
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasPermission($permission) {
    $role = $_SESSION['role'] ?? '';
    $permissions = [
        'admin' => ['all'],
        'cashier' => ['sales', 'customers', 'view_products'],
        'stock_manager' => ['products', 'stock', 'suppliers', 'purchases'],
        'accountant' => ['reports', 'financial', 'view_all']
    ];
    
    if ($role === 'admin') return true;
    if (isset($permissions[$role])) {
        return in_array($permission, $permissions[$role]) || in_array('all', $permissions[$role]);
    }
    return false;
}

function formatCurrency($amount) {
    return number_format($amount, 2);
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generatePONumber() {
    return 'PO-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function convertUnit($quantity, $fromUnit, $toUnit) {
    $conversions = [
        'kg' => ['g' => 1000, 'mg' => 1000000],
        'g' => ['kg' => 0.001, 'mg' => 1000],
        'mg' => ['kg' => 0.000001, 'g' => 0.001]
    ];
    
    if ($fromUnit === $toUnit) return $quantity;
    if (isset($conversions[$fromUnit][$toUnit])) {
        return $quantity * $conversions[$fromUnit][$toUnit];
    }
    return $quantity;
}
?>

