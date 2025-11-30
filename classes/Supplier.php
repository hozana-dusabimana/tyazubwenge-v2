<?php
require_once __DIR__ . '/../config/database.php';

class Supplier {
    private $conn;
    private $table = "suppliers";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, contact_person, email, phone, address, status) 
                  VALUES (:name, :contact_person, :email, :phone, :address, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':contact_person', $data['contact_person']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false, 'message' => 'Failed to create supplier'];
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $query = "SELECT s.*, 
                         (SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = s.id) + 
                         (SELECT COUNT(DISTINCT si.product_id) 
                          FROM stock_inventory si 
                          WHERE si.supplier_id = s.id) as total_orders,
                         COALESCE(
                             (SELECT SUM(total_amount) FROM purchase_orders WHERE supplier_id = s.id AND status = 'delivered'),
                             0
                         ) + COALESCE(
                             (SELECT SUM(si.quantity * p.cost_price) 
                              FROM stock_inventory si 
                              JOIN products p ON si.product_id = p.id 
                              WHERE si.supplier_id = s.id),
                             0
                         ) as total_purchases
                  FROM " . $this->table . " s
                  WHERE 1=1";
        
        if (!empty($search)) {
            // Use separate parameters and make search case-insensitive
            $query .= " AND (LOWER(s.name) LIKE LOWER(:search1) OR LOWER(s.email) LIKE LOWER(:search2) OR LOWER(s.phone) LIKE LOWER(:search3))";
        }
        
        $query .= " ORDER BY s.id DESC LIMIT :limit OFFSET :offset";
        
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
        
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, contact_person = :contact_person, email = :email, 
                      phone = :phone, address = :address, status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':contact_person', $data['contact_person']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
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
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE 1=1";
        
        if (!empty($search)) {
            // Use separate parameters and make search case-insensitive
            $query .= " AND (LOWER(name) LIKE LOWER(:search1) OR LOWER(email) LIKE LOWER(:search2) OR LOWER(phone) LIKE LOWER(:search3))";
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
        return $result['total'];
    }
}
?>

