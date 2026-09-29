<?php 
session_start();

try {
    $conn = new PDO("mysql:host=localhost;dbname=reading", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_POST["name"]) && isset($_POST["email"]) && isset($_POST["picture"])) {
        $name = $_POST["name"];
        $email = $_POST["email"];
        $picture = $_POST["picture"];
        $role = "buyer";
        
        // Check if user already exists
        $checkSql = "SELECT id FROM users WHERE email = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$email]);
        
        if ($checkStmt->rowCount() > 0) {
            // User exists, just set session
            $_SESSION['user'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['picture'] = $picture;
            $_SESSION['role'] = $role;
        } else {
            // Insert new user
            $sql = "INSERT INTO users(name, email, picture, role) VALUES(?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $email, $picture, $role]);
            
            $_SESSION['user'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['picture'] = $picture;
            $_SESSION['role'] = $role;
        }
        
        // IMPORTANT: Redirect to user.php
        header("Location: user/user.php");
        exit;
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo "Login failed. Please try again.";
    exit;
}
?>