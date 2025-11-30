<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Product.php';

requireLogin();

$term = $_GET['term'] ?? '';
$branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;

if (empty($term)) {
    echo json_encode(['success' => false, 'message' => 'Search term required']);
    exit;
}

$product = new Product();
require_once __DIR__ . '/../classes/Stock.php';
$stock = new Stock();

// Search by barcode, SKU, or name
$productData = $product->getByBarcode($term);

if (!$productData) {
    // Try searching by name or SKU
    $products = $product->getAll(1, 10, $term);
    if (!empty($products)) {
        $productData = $products[0];
    }
}

if ($productData && $branchId) {
    $stockData = $stock->getStock($productData['id'], $branchId);
    $productData['stock'] = $stockData ? $stockData['quantity'] : 0;
    $productData['stock_unit'] = $stockData ? $stockData['unit'] : $productData['unit'];
}

echo json_encode([
    'success' => true,
    'data' => $productData
]);
?>

