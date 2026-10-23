<?php
session_start();
require '../database/connection.php';

// Must be a logged-in buyer
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php");
    exit();
}

// Must be a POST request with a valid CSRF token
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')
) {
    header("Location: my_post.php?error=1");
    exit();
}

$post_id = (int)($_POST['post_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($post_id > 0) {
    try {
        // user_id in the WHERE clause ensures buyers can only delete THEIR OWN posts
        $stmt = $conn->prepare("
            DELETE FROM post
            WHERE post_id = :post_id
              AND user_id = :user_id
              AND post_type = 'wanted'
        ");
        $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id
        ]);

        if ($stmt->rowCount() > 0) {
            header("Location: my_post.php?deleted=1");
            exit();
        }
    } catch (PDOException $e) {
        // Uncomment while debugging (e.g. foreign key errors):
        // die($e->getMessage());
        error_log($e->getMessage());
    }
}

header("Location: my_post.php?error=1");
exit();