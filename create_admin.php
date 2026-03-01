<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    die("Database connection failed\n");
}

// Admin user data
$username = 'admin';
$email = 'admin@tyazubwenge.com';
$password = 'Admin@123'; // You should change this after first login
$full_name = 'System Administrator';
$role = 'admin';
$status = 'active';

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if admin user already exists
$query = "SELECT id FROM users WHERE username = :username OR email = :email";
$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "Admin user already exists!\n".$hashedPassword;
    exit;
}

// Insert admin user
$query = "INSERT INTO users (username, email, password, full_name, role, status) 
          VALUES (:username, :email, :password, :full_name, :role, :status)";

$stmt = $conn->prepare($query);
$stmt->bindParam(':username', $username);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':password', $hashedPassword);
$stmt->bindParam(':full_name', $full_name);
$stmt->bindParam(':role', $role);
$stmt->bindParam(':status', $status);

if ($stmt->execute()) {
    echo "Admin user created successfully!\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Please change the password after first login.\n";
} else {
    echo "Failed to create admin user.\n";
}
?>