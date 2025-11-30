<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Stock.php';
require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Supplier.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Report.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$type = $_GET['type'] ?? '';
$branchId = $_GET['branch_id'] ?? $_SESSION['branch_id'] ?? null;

$database = new Database();
$conn = $database->getConnection();

switch ($type) {
    case 'products':
        $product = new Product();
        $totalProducts = $product->getTotalCount();
        
        // Get active products count
        $query = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $activeProducts = $stmt->fetch()['total'];
        
        // Get total stock value
        $query = "SELECT SUM(si.quantity * p.cost_price) as total_value 
                  FROM stock_inventory si 
                  JOIN products p ON si.product_id = p.id";
        if ($branchId) {
            $query .= " WHERE si.branch_id = :branch_id";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        $stockValue = $stmt->fetch()['total_value'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_products' => (int)$totalProducts,
                'active_products' => (int)$activeProducts,
                'total_stock_value' => (float)$stockValue
            ]
        ]);
        break;
        
    case 'stock':
        $stock = new Stock();
        $lowStock = $stock->getLowStock($branchId);
        $nearExpiry = $stock->getNearExpiry($branchId, 30);
        
        // Get total stock items
        $query = "SELECT COUNT(*) as total FROM stock_inventory";
        if ($branchId) {
            $query .= " WHERE branch_id = :branch_id";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        $totalItems = $stmt->fetch()['total'];
        
        // Get total stock value
        $query = "SELECT SUM(si.quantity * p.cost_price) as total_value 
                  FROM stock_inventory si 
                  JOIN products p ON si.product_id = p.id";
        if ($branchId) {
            $query .= " WHERE si.branch_id = :branch_id";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        $stockValue = $stmt->fetch()['total_value'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_items' => (int)$totalItems,
                'low_stock_count' => count($lowStock),
                'near_expiry_count' => count($nearExpiry),
                'total_stock_value' => (float)$stockValue
            ]
        ]);
        break;
        
    case 'sales':
        $sale = new Sale();
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Get sales summary
        $summary = $sale->getSalesSummary($branchId, $dateFrom, $dateTo);
        $summary = $summary ?: ['total_sales' => 0, 'total_revenue' => 0];
        
        // Get pending amount
        $query = "SELECT SUM(final_amount) as total FROM sales WHERE payment_status IN ('pending', 'partial')";
        if ($branchId) {
            $query .= " AND branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(created_at) <= :date_to";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        if ($dateFrom) {
            $stmt->bindParam(':date_from', $dateFrom);
        }
        if ($dateTo) {
            $stmt->bindParam(':date_to', $dateTo);
        }
        $stmt->execute();
        $pendingAmount = $stmt->fetch()['total'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_sales' => (int)($summary['total_sales'] ?? 0),
                'total_revenue' => (float)($summary['total_revenue'] ?? 0),
                'pending_amount' => (float)$pendingAmount
            ]
        ]);
        break;
        
    case 'customers':
        $customer = new Customer();
        $totalCustomers = $customer->getTotalCount();
        
        // Get active customers
        $query = "SELECT COUNT(*) as total FROM customers WHERE status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $activeCustomers = $stmt->fetch()['total'];
        
        // Get total purchases
        $query = "SELECT SUM(final_amount) as total FROM sales WHERE payment_status = 'paid'";
        if ($branchId) {
            $query .= " AND branch_id = :branch_id";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        $totalPurchases = $stmt->fetch()['total'] ?? 0;
        
        // Get pending amounts
        $query = "SELECT SUM(final_amount) as total FROM sales WHERE payment_status IN ('pending', 'partial')";
        if ($branchId) {
            $query .= " AND branch_id = :branch_id";
        }
        $stmt = $conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        $pendingAmount = $stmt->fetch()['total'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_customers' => (int)$totalCustomers,
                'active_customers' => (int)$activeCustomers,
                'total_purchases' => (float)$totalPurchases,
                'pending_amount' => (float)$pendingAmount
            ]
        ]);
        break;
        
    case 'suppliers':
        $supplier = new Supplier();
        $totalSuppliers = $supplier->getTotalCount();
        
        // Get active suppliers
        $query = "SELECT COUNT(*) as total FROM suppliers WHERE status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $activeSuppliers = $stmt->fetch()['total'];
        
        // Get total orders: purchase orders + distinct products from direct stock additions
        $query = "SELECT COUNT(*) as total FROM purchase_orders";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $poOrders = $stmt->fetch()['total'] ?? 0;
        
        // Count distinct products purchased directly (from stock_inventory with supplier_id)
        $query = "SELECT COUNT(DISTINCT product_id) as total 
                  FROM stock_inventory 
                  WHERE supplier_id IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stockOrders = $stmt->fetch()['total'] ?? 0;
        
        $totalOrders = $poOrders + $stockOrders;

        // Get total purchases: purchase orders (delivered) + direct stock additions
        $query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM purchase_orders WHERE status = 'delivered'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $poPurchases = $stmt->fetch()['total'] ?? 0;
        
        // Get direct stock purchases (stock added with supplier_id)
        $query = "SELECT COALESCE(SUM(si.quantity * p.cost_price), 0) as total 
                  FROM stock_inventory si 
                  JOIN products p ON si.product_id = p.id 
                  WHERE si.supplier_id IS NOT NULL";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stockPurchases = $stmt->fetch()['total'] ?? 0;
        
        $totalPurchases = $poPurchases + $stockPurchases;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_suppliers' => (int)$totalSuppliers,
                'active_suppliers' => (int)$activeSuppliers,
                'total_orders' => (int)$totalOrders,
                'total_purchases' => (float)$totalPurchases
            ]
        ]);
        break;
        
    case 'users':
        $user = new User();
        $totalUsers = $user->getTotalCount();
        
        // Get active users
        $query = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $activeUsers = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => (int)$totalUsers,
                'active_users' => (int)$activeUsers
            ]
        ]);
        break;
        
    case 'categories':
        $query = "SELECT COUNT(*) as total FROM categories";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $totalCategories = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_categories' => (int)$totalCategories
            ]
        ]);
        break;
        
    case 'brands':
        $query = "SELECT COUNT(*) as total FROM brands";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $totalBrands = $stmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_brands' => (int)$totalBrands
            ]
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid summary type']);
}
?>

