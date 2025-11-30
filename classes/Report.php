<?php
require_once __DIR__ . '/../config/database.php';

class Report {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getSalesReport($branchId = null, $dateFrom = null, $dateTo = null, $groupBy = 'day') {
        $query = "SELECT ";
        
        switch ($groupBy) {
            case 'day':
                $query .= "DATE(created_at) as period, ";
                break;
            case 'week':
                $query .= "YEARWEEK(created_at) as period, ";
                break;
            case 'month':
                $query .= "DATE_FORMAT(created_at, '%Y-%m') as period, ";
                break;
            default:
                $query .= "DATE(created_at) as period, ";
        }
        
        $query .= "COUNT(*) as total_sales,
                   SUM(final_amount) as total_revenue,
                   SUM(total_amount) as total_before_discount,
                   SUM(discount) as total_discount,
                   SUM(tax) as total_tax
                   FROM sales
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
        
        $query .= " GROUP BY period ORDER BY period DESC";
        
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
        
        return $stmt->fetchAll();
    }

    public function getTopProducts($branchId = null, $dateFrom = null, $dateTo = null, $limit = 10) {
        $query = "SELECT p.id, p.name, p.sku, 
                         SUM(si.quantity) as total_quantity_sold,
                         SUM(si.subtotal) as total_revenue,
                         COUNT(DISTINCT si.sale_id) as times_sold
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.payment_status = 'paid'";
        
        if ($branchId) {
            $query .= " AND s.branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(s.created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(s.created_at) <= :date_to";
        }
        
        $query .= " GROUP BY p.id, p.name, p.sku 
                   ORDER BY total_revenue DESC 
                   LIMIT :limit";
        
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
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getStockValuation($branchId = null) {
        $query = "SELECT si.*, p.name as product_name, p.sku, p.cost_price,
                         (si.quantity * p.cost_price) as stock_value
                  FROM stock_inventory si
                  JOIN products p ON si.product_id = p.id
                  WHERE 1=1";
        
        if ($branchId) {
            $query .= " AND si.branch_id = :branch_id";
        }
        
        $query .= " ORDER BY stock_value DESC";
        
        $stmt = $this->conn->prepare($query);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getProfitLoss($branchId = null, $dateFrom = null, $dateTo = null) {
        $query = "SELECT 
                    SUM(s.final_amount) as total_revenue,
                    SUM(si.quantity * p.cost_price) as total_cost,
                    (SUM(s.final_amount) - SUM(si.quantity * p.cost_price)) as profit,
                    ((SUM(s.final_amount) - SUM(si.quantity * p.cost_price)) / SUM(s.final_amount) * 100) as profit_margin
                  FROM sales s
                  JOIN sale_items si ON s.id = si.sale_id
                  JOIN products p ON si.product_id = p.id
                  WHERE s.payment_status = 'paid'";
        
        if ($branchId) {
            $query .= " AND s.branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(s.created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(s.created_at) <= :date_to";
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

    public function getCustomReport($type, $params = []) {
        switch ($type) {
            case 'sales_by_customer':
                return $this->getSalesByCustomer($params);
            case 'sales_by_category':
                return $this->getSalesByCategory($params);
            case 'slow_moving':
                return $this->getSlowMovingProducts($params);
            default:
                return [];
        }
    }

    private function getSalesByCustomer($params) {
        $branchId = $params['branch_id'] ?? null;
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        
        $query = "SELECT c.id, c.name, c.phone,
                         COUNT(s.id) as total_orders,
                         SUM(s.final_amount) as total_spent
                  FROM customers c
                  JOIN sales s ON c.id = s.customer_id
                  WHERE s.payment_status = 'paid'";
        
        if ($branchId) {
            $query .= " AND s.branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(s.created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(s.created_at) <= :date_to";
        }
        
        $query .= " GROUP BY c.id, c.name, c.phone ORDER BY total_spent DESC";
        
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
        
        return $stmt->fetchAll();
    }

    private function getSalesByCategory($params) {
        $branchId = $params['branch_id'] ?? null;
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        
        $query = "SELECT cat.id, cat.name,
                         SUM(si.quantity) as total_quantity,
                         SUM(si.subtotal) as total_revenue
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.id
                  LEFT JOIN categories cat ON p.category_id = cat.id
                  JOIN sales s ON si.sale_id = s.id
                  WHERE s.payment_status = 'paid'";
        
        if ($branchId) {
            $query .= " AND s.branch_id = :branch_id";
        }
        if ($dateFrom) {
            $query .= " AND DATE(s.created_at) >= :date_from";
        }
        if ($dateTo) {
            $query .= " AND DATE(s.created_at) <= :date_to";
        }
        
        $query .= " GROUP BY cat.id, cat.name ORDER BY total_revenue DESC";
        
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
        
        return $stmt->fetchAll();
    }

    private function getSlowMovingProducts($params) {
        $branchId = $params['branch_id'] ?? null;
        $days = $params['days'] ?? 90;
        
        $query = "SELECT p.id, p.name, p.sku,
                         si.quantity as current_stock,
                         COALESCE(SUM(si2.quantity), 0) as sold_quantity,
                         DATEDIFF(CURDATE(), MAX(COALESCE(s2.created_at, p.created_at))) as days_since_last_sale
                  FROM products p
                  JOIN stock_inventory si ON p.id = si.product_id
                  LEFT JOIN sale_items si2 ON p.id = si2.product_id
                  LEFT JOIN sales s2 ON si2.sale_id = s2.id AND s2.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                  WHERE 1=1";
        
        if ($branchId) {
            $query .= " AND si.branch_id = :branch_id";
        }
        
        $query .= " GROUP BY p.id, p.name, p.sku, si.quantity
                   HAVING sold_quantity = 0 OR days_since_last_sale > :days
                   ORDER BY days_since_last_sale DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
        if ($branchId) {
            $stmt->bindParam(':branch_id', $branchId);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>

