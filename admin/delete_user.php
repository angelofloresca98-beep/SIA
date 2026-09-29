<?php
require '../database/connection.php';

if (isset($_GET['id'])) {

    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        header("Location: admin.php?msg=deleted");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

} else {
    header("Location: admin.php");
    exit;
}
?>