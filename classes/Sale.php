<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Stock.php';

class Sale {
    private $conn;
    private $table = "sales";
    private $itemsTable = "sale_items";
    private $stock;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->stock = new Stock();
    }

    public function create($data) {
        $this->conn->beginTransaction();
        
        try {
            $invoiceNumber = generateInvoiceNumber();
            $query = "INSERT INTO " . $this->table . " 
                      (invoice_number, customer_id, branch_id, user_id, total_amount, discount, tax, 
                       final_amount, payment_method, payment_status, sale_type, notes) 
                      VALUES (:invoice_number, :customer_id, :branch_id, :user_id, :total_amount, :discount, :tax, 
                              :final_amount, :payment_method, :payment_status, :sale_type, :notes)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':invoice_number', $invoiceNumber);
            $stmt->bindParam(':customer_id', $data['customer_id']);
            $stmt->bindParam(':branch_id', $data['branch_id']);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':total_amount', $data['total_amount']);
            $stmt->bindParam(':discount', $data['discount']);
            $stmt->bindParam(':tax', $data['tax']);
            $stmt->bindParam(':final_amount', $data['final_amount']);
            $stmt->bindParam(':payment_method', $data['payment_method']);
            $stmt->bindParam(':payment_status', $data['payment_status']);
            $stmt->bindParam(':sale_type', $data['sale_type']);
            $stmt->bindParam(':notes', $data['notes']);
            $stmt->execute();
            
            $saleId = $this->conn->lastInsertId();
            
            // Insert sale items and deduct stock
            foreach ($data['items'] as $item) {
                $itemQuery = "INSERT INTO " . $this->itemsTable . " 
                             (sale_id, product_id, quantity, unit, unit_price, discount, subtotal) 
                             VALUES (:sale_id, :product_id, :quantity, :unit, :unit_price, :discount, :subtotal)";
                
                $itemStmt = $this->conn->prepare($itemQuery);
                $itemStmt->bindParam(':sale_id', $saleId);
                $itemStmt->bindParam(':product_id', $item['product_id']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':unit', $item['unit']);
                $itemStmt->bindParam(':unit_price', $item['unit_price']);
                $itemStmt->bindParam(':discount', $item['discount']);
                $itemStmt->bindParam(':subtotal', $item['subtotal']);
                $itemStmt->execute();
                
                // Deduct stock
                $this->stock->deductStock($item['product_id'], $data['branch_id'], $item['quantity'], $item['unit'], $saleId);
            }
            
            $this->conn->commit();
            return ['success' => true, 'sale_id' => $saleId, 'invoice_number' => $invoiceNumber];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getById($id) {
        $query = "SELECT s.*, c.name as customer_name, c.phone as customer_phone, 
                         u.full_name as user_name, b.name as branch_name
                  FROM " . $this->table . " s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  JOIN users u ON s.user_id = u.id
                  JOIN branches b ON s.branch_id = b.id
                  WHERE s.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $sale = $stmt->fetch();
        
        if ($sale) {
            $sale['items'] = $this->getSaleItems($id);
        }
        
        return $sale;
    }

    public function getSaleItems($saleId) {
        $query = "SELECT si.*, p.name as product_name, p.sku, p.barcode
                  FROM " . $this->itemsTable . " si
                  JOIN products p ON si.product_id = p.id
                  WHERE si.sale_id = :sale_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sale_id', $saleId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getAll($page = 1, $limit = 10, $branchId = null, $dateFrom = null, $dateTo = null) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT s.*, c.name as customer_name, u.full_name as user_name, b.name as branch_name
                  FROM " . $this->table . " s
                  LEFT JOIN customers c ON s.customer_id = c.id
                  JOIN users u ON s.user_id = u.id
                  JOIN branches b ON s.branch_id = b.id
                  WHERE 1=1";
        
        if ($branchId) {
            $query .= " AND s.branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(s.created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(s.created_at) <= :date_to";
        }
        
        $query .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        if ($dateFrom) {
            $stmt->bindParam(':date_from', $dateFrom);
        }
        if ($dateTo) {
            $stmt->bindParam(':date_to', $dateTo);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getTotalCount($branchId = null, $dateFrom = null, $dateTo = null) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        if ($branchId) {
            $query .= " AND branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(created_at) <= :date_to";
        }
        
        $stmt = $this->conn->prepare($query);
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
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function getSalesSummary($branchId = null, $dateFrom = null, $dateTo = null) {
        $query = "SELECT 
                    COUNT(*) as total_sales,
                    SUM(final_amount) as total_revenue,
                    SUM(total_amount) as total_before_discount,
                    SUM(discount) as total_discount,
                    SUM(tax) as total_tax
                  FROM " . $this->table . " 
                  WHERE payment_status = 'paid'";
        
        if ($branchId) {
            $query .= " AND branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(created_at) <= :date_to";
        }
        
        $stmt = $this->conn->prepare($query);
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
        
        return $stmt->fetch();
    }
}
?>

