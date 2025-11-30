<?php
require_once __DIR__ . '/../config/database.php';

class Customer {
    private $conn;
    private $table = "customers";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, phone, address, loyalty_points, credit_limit, status) 
                  VALUES (:name, :email, :phone, :address, :loyalty_points, :credit_limit, :status)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':loyalty_points', $data['loyalty_points']);
        $stmt->bindParam(':credit_limit', $data['credit_limit']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->conn->lastInsertId()];
        }
        return ['success' => false, 'message' => 'Failed to create customer'];
    }

    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $query = "SELECT c.*, 
                         (SELECT SUM(final_amount) FROM sales WHERE customer_id = c.id AND payment_status = 'paid') as total_purchases,
                         (SELECT SUM(final_amount) FROM sales WHERE customer_id = c.id AND payment_status IN ('pending', 'partial')) as pending_amount
                  FROM " . $this->table . " c
                  WHERE 1=1";
        
        if (!empty($search)) {
            // Use separate parameters and make search case-insensitive
            $query .= " AND (LOWER(c.name) LIKE LOWER(:search1) OR LOWER(c.email) LIKE LOWER(:search2) OR LOWER(c.phone) LIKE LOWER(:search3))";
        }
        
        $query .= " ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
        
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
                  SET name = :name, email = :email, phone = :phone, address = :address, 
                      loyalty_points = :loyalty_points, credit_limit = :credit_limit, status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':loyalty_points', $data['loyalty_points']);
        $stmt->bindParam(':credit_limit', $data['credit_limit']);
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

    public function getPurchaseHistory($customerId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT s.*, u.full_name as user_name, b.name as branch_name
                  FROM sales s
                  JOIN users u ON s.user_id = u.id
                  JOIN branches b ON s.branch_id = b.id
                  WHERE s.customer_id = :customer_id
                  ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>

