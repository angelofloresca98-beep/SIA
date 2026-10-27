<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

$back = 'admin.php#posts'; // admin dashboard

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid request.'];
    header("Location: $back");
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid post.'];
    header("Location: $back");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM post WHERE post_id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = $stmt->rowCount()
        ? ['type' => 'success', 'message' => 'Post deleted.']
        : ['type' => 'warning', 'message' => 'Post not found.'];
} catch (PDOException $ex) {
    // Most likely a foreign key constraint referencing this row
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not delete the post. It may still be referenced by other records.'];
}

header("Location: $back");
exit;