<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class Stock {
    private $conn;
    private $inventoryTable = "stock_inventory";
    private $movementsTable = "stock_movements";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function addStock($productId, $branchId, $quantity, $unit, $batchNumber = null, $expiryDate = null, $supplierId = null) {
        // Get product's base unit
        $productQuery = "SELECT unit FROM products WHERE id = :product_id";
        $productStmt = $this->conn->prepare($productQuery);
        $productStmt->bindParam(':product_id', $productId);
        $productStmt->execute();
        $product = $productStmt->fetch();
        
        if (!$product) {
            return false; // Product not found
        }
        
        $productUnit = $product['unit']; // Product's base unit (kg, g, or mg)
        
        // Convert incoming quantity to product's base unit
        $convertedQuantity = convertUnit($quantity, $unit, $productUnit);
        
        // Check if values are provided (not empty)
        $hasBatchNumber = !empty($batchNumber) && trim($batchNumber) !== '';
        $hasExpiryDate = !empty($expiryDate) && trim($expiryDate) !== '';
        $hasSupplierId = !empty($supplierId) && $supplierId !== '0' && $supplierId !== '';
        
        // Check if stock exists
        $query = "SELECT id, quantity, unit, expiry_date, batch_number, supplier_id FROM " . $this->inventoryTable . " 
                  WHERE product_id = :product_id AND branch_id = :branch_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':branch_id', $branchId);
        $stmt->execute();
        $existing = $stmt->fetch();

        if ($existing) {
            // Convert existing stock quantity to product's base unit if needed
            $existingQuantity = $existing['quantity'];
            if ($existing['unit'] !== $productUnit) {
                $existingQuantity = convertUnit($existing['quantity'], $existing['unit'], $productUnit);
            }
            
            // Add converted quantities together
            $newQuantity = $existingQuantity + $convertedQuantity;
            
            // Use new values if provided, otherwise preserve existing ones
            $finalExpiryDate = $hasExpiryDate ? trim($expiryDate) : ($existing['expiry_date'] ?? null);
            $finalBatchNumber = $hasBatchNumber ? trim($batchNumber) : ($existing['batch_number'] ?? null);
            $finalSupplierId = $hasSupplierId ? $supplierId : ($existing['supplier_id'] ?? null);
            
            // Update existing stock (always store in product's base unit)
            $updateQuery = "UPDATE " . $this->inventoryTable . " 
                           SET quantity = :quantity, unit = :unit, batch_number = :batch_number, 
                               expiry_date = :expiry_date, supplier_id = :supplier_id
                           WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $newQuantity);
            $updateStmt->bindParam(':unit', $productUnit);
            // Handle null values properly for PDO
            $updateStmt->bindValue(':batch_number', $finalBatchNumber, $finalBatchNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $updateStmt->bindValue(':expiry_date', $finalExpiryDate, $finalExpiryDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $updateStmt->bindValue(':supplier_id', $finalSupplierId, $finalSupplierId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $updateStmt->bindParam(':id', $existing['id']);
            $updateStmt->execute();
        } else {
            // Insert new stock (store in product's base unit)
            $newBatchNumber = $hasBatchNumber ? trim($batchNumber) : null;
            $newExpiryDate = $hasExpiryDate ? trim($expiryDate) : null;
            $newSupplierId = $hasSupplierId ? $supplierId : null;
            
            $insertQuery = "INSERT INTO " . $this->inventoryTable . " 
                           (product_id, branch_id, quantity, unit, batch_number, expiry_date, supplier_id) 
                           VALUES (:product_id, :branch_id, :quantity, :unit, :batch_number, :expiry_date, :supplier_id)";
            $insertStmt = $this->conn->prepare($insertQuery);
            $insertStmt->bindParam(':product_id', $productId);
            $insertStmt->bindParam(':branch_id', $branchId);
            $insertStmt->bindParam(':quantity', $convertedQuantity);
            $insertStmt->bindParam(':unit', $productUnit);
            // Handle null values properly for PDO
            $insertStmt->bindValue(':batch_number', $newBatchNumber, $newBatchNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertStmt->bindValue(':expiry_date', $newExpiryDate, $newExpiryDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insertStmt->bindValue(':supplier_id', $newSupplierId, $newSupplierId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insertStmt->execute();
        }

        // Record movement (keep original unit for audit trail)
        $expiryNote = $expiryDate ? " (Expiry: {$expiryDate})" : "";
        $this->recordMovement($productId, $branchId, 'purchase', $quantity, $unit, null, "Stock added: {$quantity} {$unit} (converted to {$convertedQuantity} {$productUnit}){$expiryNote}");
        
        return true;
    }

    public function deductStock($productId, $branchId, $quantity, $unit, $referenceId = null) {
        // Get product's base unit
        $productQuery = "SELECT unit FROM products WHERE id = :product_id";
        $productStmt = $this->conn->prepare($productQuery);
        $productStmt->bindParam(':product_id', $productId);
        $productStmt->execute();
        $product = $productStmt->fetch();
        
        if (!$product) {
            return false; // Product not found
        }
        
        $productUnit = $product['unit']; // Product's base unit
        
        // Convert quantity to product's base unit
        $convertedQuantity = convertUnit($quantity, $unit, $productUnit);
        
        $query = "SELECT id, quantity, unit FROM " . $this->inventoryTable . " 
                  WHERE product_id = :product_id AND branch_id = :branch_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':branch_id', $branchId);
        $stmt->execute();
        $stock = $stmt->fetch();

        if ($stock) {
            // Convert existing stock to product's base unit if needed
            $existingQuantity = $stock['quantity'];
            if ($stock['unit'] !== $productUnit) {
                $existingQuantity = convertUnit($stock['quantity'], $stock['unit'], $productUnit);
            }
            
            if ($existingQuantity >= $convertedQuantity) {
                $newQuantity = $existingQuantity - $convertedQuantity;
                
                // Update stock (always store in product's base unit)
                $updateQuery = "UPDATE " . $this->inventoryTable . " 
                               SET quantity = :quantity, unit = :unit WHERE id = :id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':quantity', $newQuantity);
                $updateStmt->bindParam(':unit', $productUnit);
                $updateStmt->bindParam(':id', $stock['id']);
                $updateStmt->execute();

                // Record movement (keep original unit for audit trail)
                $this->recordMovement($productId, $branchId, 'sale', $quantity, $unit, $referenceId, "Stock deducted: {$quantity} {$unit} (converted from {$convertedQuantity} {$productUnit})");
                return true;
            }
        }
        return false;
    }

    public function getStock($productId, $branchId) {
        $query = "SELECT * FROM " . $this->inventoryTable . " 
                  WHERE product_id = :product_id AND branch_id = :branch_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':branch_id', $branchId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getStockById($stockId) {
        $query = "SELECT si.*, p.name as product_name, p.sku, p.unit as product_unit
                  FROM " . $this->inventoryTable . " si
                  JOIN products p ON si.product_id = p.id
                  WHERE si.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $stockId);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function updateStock($stockId, $quantity, $unit, $batchNumber = null, $expiryDate = null, $supplierId = null) {
        // Get existing stock record
        $query = "SELECT si.*, p.unit as product_unit FROM " . $this->inventoryTable . " si
                  JOIN products p ON si.product_id = p.id
                  WHERE si.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $stockId);
        $stmt->execute();
        $existing = $stmt->fetch();
        
        if (!$existing) {
            return false; // Stock record not found
        }
        
        $productUnit = $existing['product_unit'];
        
        // Convert quantity to product's base unit
        $convertedQuantity = convertUnit($quantity, $unit, $productUnit);
        
        // Normalize empty values
        $hasBatchNumber = !empty($batchNumber) && trim($batchNumber) !== '';
        $hasExpiryDate = !empty($expiryDate) && trim($expiryDate) !== '';
        $hasSupplierId = !empty($supplierId) && $supplierId !== '0' && $supplierId !== '';
        
        $finalBatchNumber = $hasBatchNumber ? trim($batchNumber) : null;
        $finalExpiryDate = $hasExpiryDate ? trim($expiryDate) : null;
        $finalSupplierId = $hasSupplierId ? $supplierId : null;
        
        // Update stock record
        $updateQuery = "UPDATE " . $this->inventoryTable . " 
                       SET quantity = :quantity, unit = :unit, batch_number = :batch_number, 
                           expiry_date = :expiry_date, supplier_id = :supplier_id,
                           last_updated = CURRENT_TIMESTAMP
                       WHERE id = :id";
        $updateStmt = $this->conn->prepare($updateQuery);
        $updateStmt->bindParam(':quantity', $convertedQuantity);
        $updateStmt->bindParam(':unit', $productUnit);
        $updateStmt->bindValue(':batch_number', $finalBatchNumber, $finalBatchNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $updateStmt->bindValue(':expiry_date', $finalExpiryDate, $finalExpiryDate ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $updateStmt->bindValue(':supplier_id', $finalSupplierId, $finalSupplierId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $updateStmt->bindParam(':id', $stockId);
        $updateStmt->execute();
        
        // Record movement for audit trail
        $oldQuantity = $existing['quantity'];
        $quantityDiff = $convertedQuantity - $oldQuantity;
        $movementType = $quantityDiff > 0 ? 'adjustment' : 'adjustment';
        $notes = "Stock updated: {$quantity} {$unit} (converted to {$convertedQuantity} {$productUnit}). Previous: {$oldQuantity} {$existing['unit']}";
        if ($finalExpiryDate) {
            $notes .= " (Expiry: {$finalExpiryDate})";
        }
        
        $this->recordMovement($existing['product_id'], $existing['branch_id'], $movementType, abs($quantityDiff), $productUnit, null, $notes);
        
        return true;
    }

    public function getAllStock($branchId = null, $page = 1, $limit = 5, $search = '') {
        $offset = ($page - 1) * $limit;
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM " . $this->inventoryTable . " si
                       JOIN products p ON si.product_id = p.id
                       WHERE 1=1";
        if ($branchId) {
            $countQuery .= " AND si.branch_id = :branch_id";
        }
        if (!empty($search)) {
            // Use separate parameters and make search case-insensitive
            $countQuery .= " AND (LOWER(p.name) LIKE LOWER(:search1) OR LOWER(p.sku) LIKE LOWER(:search2) OR LOWER(p.barcode) LIKE LOWER(:search3))";
        }
        $countStmt = $this->conn->prepare($countQuery);
        if ($branchId) {
            $countStmt->bindParam(':branch_id', $branchId);
        }
        if (!empty($search)) {
            $searchParam = "%{$search}%";
            // Bind the same search parameter to all three placeholders
            $countStmt->bindValue(':search1', $searchParam);
            $countStmt->bindValue(':search2', $searchParam);
            $countStmt->bindValue(':search3', $searchParam);
        }
        $countStmt->execute();
        $totalItems = $countStmt->fetch()['total'];
        $totalPages = ceil($totalItems / $limit);
        
        // Get data
        $query = "SELECT si.*, p.name as product_name, p.sku, p.unit as product_unit, 
                         p.min_stock_level, c.name as category_name,
                         CASE WHEN si.expiry_date IS NOT NULL AND si.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                              THEN 1 ELSE 0 END as near_expiry,
                         CASE WHEN si.expiry_date IS NOT NULL AND si.expiry_date < CURDATE() 
                              THEN 1 ELSE 0 END as expired
                  FROM " . $this->inventoryTable . " si
                  JOIN products p ON si.product_id = p.id
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE 1=1";
        
        if ($branchId) {
            $query .= " AND si.branch_id = :branch_id";
        }
        if (!empty($search)) {
            // Use separate parameters and make search case-insensitive
            $query .= " AND (LOWER(p.name) LIKE LOWER(:search1) OR LOWER(p.sku) LIKE LOWER(:search2) OR LOWER(p.barcode) LIKE LOWER(:search3))";
        }
        
        $query .= " ORDER BY si.last_updated DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        if (!empty($search)) {
            $searchParam = "%{$search}%";
            // Bind the same search parameter to all three placeholders
            $stmt->bindValue(':search1', $searchParam);
            $stmt->bindValue(':search2', $searchParam);
            $stmt->bindValue(':search3', $searchParam);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $limit
            ]
        ];
    }

    public function getLowStock($branchId = null) {
        $query = "SELECT si.*, p.name as product_name, p.sku, p.min_stock_level
                  FROM " . $this->inventoryTable . " si
                  JOIN products p ON si.product_id = p.id
                  WHERE si.quantity <= p.min_stock_level";
        
        if ($branchId) {
            $query .= " AND si.branch_id = :branch_id";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getNearExpiry($branchId = null, $days = 30) {
        $query = "SELECT si.*, p.name as product_name, p.sku, 
                         DATEDIFF(si.expiry_date, CURDATE()) as days_remaining
                  FROM " . $this->inventoryTable . " si
                  JOIN products p ON si.product_id = p.id
                  WHERE si.expiry_date IS NOT NULL 
                  AND si.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  AND si.expiry_date >= CURDATE()";
        
        if ($branchId) {
            $query .= " AND si.branch_id = :branch_id";
        }
        
        $query .= " ORDER BY si.expiry_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    private function recordMovement($productId, $branchId, $type, $quantity, $unit, $referenceId, $notes) {
        $query = "INSERT INTO " . $this->movementsTable . " 
                  (product_id, branch_id, movement_type, quantity, unit, reference_id, notes, user_id) 
                  VALUES (:product_id, :branch_id, :movement_type, :quantity, :unit, :reference_id, :notes, :user_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->bindParam(':branch_id', $branchId);
        $stmt->bindParam(':movement_type', $type);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':reference_id', $referenceId);
        $stmt->bindParam(':notes', $notes);
        $userId = $_SESSION['user_id'] ?? null;
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    public function getMovements($productId = null, $branchId = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT sm.*, p.name as product_name, p.sku, u.full_name as user_name
                  FROM " . $this->movementsTable . " sm
                  JOIN products p ON sm.product_id = p.id
                  LEFT JOIN users u ON sm.user_id = u.id
                  WHERE 1=1";
        
        if ($productId) {
            $query .= " AND sm.product_id = :product_id";
        }
        if ($branchId) {
            $query .= " AND sm.branch_id = :branch_id";
        }
        
        $query .= " ORDER BY sm.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        if ($productId) {
            $stmt->bindParam(':product_id', $productId);
        }
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>

