<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $conn;
    private $table = "products";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, sku, barcode, category_id, brand_id, description, unit, 
                   retail_price, wholesale_price, cost_price, min_stock_level, status) 
                  VALUES (:name, :sku, :barcode, :category_id, :brand_id, :description, :unit, 
                          :retail_price, :wholesale_price, :cost_price, :min_stock_level, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sku', $data['sku']);
        $stmt->bindParam(':barcode', $data['barcode']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':brand_id', $data['brand_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':retail_price', $data['retail_price']);
        $stmt->bindParam(':wholesale_price', $data['wholesale_price']);
        $stmt->bindParam(':cost_price', $data['cost_price']);
        $stmt->bindParam(':min_stock_level', $data['min_stock_level']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false, 'message' => 'Failed to create product'];
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            $query = "SELECT p.*, c.name as category_name, b.name as brand_name,
                      (SELECT SUM(quantity) FROM stock_inventory WHERE product_id = p.id) as total_stock
                      FROM " . $this->table . " p 
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE 1=1";
            
            if (!empty($search)) {
                // Use separate parameters for each field to avoid binding issues
                // Make search case-insensitive using LOWER()
                $query .= " AND (LOWER(p.name) LIKE LOWER(:search1) OR LOWER(p.sku) LIKE LOWER(:search2) OR LOWER(p.barcode) LIKE LOWER(:search3))";
            }
            
            $query .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($query);
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
            
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            error_log("Product::getAll Error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name, b.name as brand_name
                  FROM " . $this->table . " p 
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN brands b ON p.brand_id = b.id
                  WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function getByBarcode($barcode) {
        $query = "SELECT p.*, c.name as category_name, b.name as brand_name
                  FROM " . $this->table . " p 
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN brands b ON p.brand_id = b.id
                  WHERE p.barcode = :barcode AND p.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':barcode', $barcode);
        $stmt->execute();
        
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, sku = :sku, barcode = :barcode, category_id = :category_id, 
                      brand_id = :brand_id, description = :description, unit = :unit,
                      retail_price = :retail_price, wholesale_price = :wholesale_price, 
                      cost_price = :cost_price, min_stock_level = :min_stock_level, status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sku', $data['sku']);
        $stmt->bindParam(':barcode', $data['barcode']);
        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':brand_id', $data['brand_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':retail_price', $data['retail_price']);
        $stmt->bindParam(':wholesale_price', $data['wholesale_price']);
        $stmt->bindParam(':cost_price', $data['cost_price']);
        $stmt->bindParam(':min_stock_level', $data['min_stock_level']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "UPDATE " . $this->table . " SET status = 'inactive' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getTotalCount($search = '') {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
            
            if (!empty($search)) {
                // Use separate parameters and make search case-insensitive
                $query .= " AND (LOWER(name) LIKE LOWER(:search1) OR LOWER(sku) LIKE LOWER(:search2) OR LOWER(barcode) LIKE LOWER(:search3))";
            }
            
            $stmt = $this->conn->prepare($query);
            if (!empty($search)) {
                $searchParam = "%{$search}%";
                // Bind the same search parameter to all three placeholders
                $stmt->bindValue(':search1', $searchParam);
                $stmt->bindValue(':search2', $searchParam);
                $stmt->bindValue(':search3', $searchParam);
            }
            $stmt->execute();
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Product::getTotalCount Error: " . $e->getMessage());
            return 0;
        }
    }
}
?>

