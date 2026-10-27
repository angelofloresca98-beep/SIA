<?php
session_start();
require '../database/connection.php';

$role    = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// Must be a logged-in admin or buyer
if (!$user_id || !in_array($role, ['admin', 'buyer'], true)) {
    header("Location: ../login.php");
    exit();
}

$isAdmin = ($role === 'admin');

// Where to go back to
$back = $isAdmin ? 'admin.php#posts' : 'my_post.php';

// Sets the result message for the right page
function finish(bool $isAdmin, string $back, bool $ok, string $msg): void {
    if ($isAdmin) {
        $_SESSION['flash'] = ['type' => $ok ? 'success' : 'danger', 'message' => $msg];
        header("Location: $back");
    } else {
        header("Location: $back?" . ($ok ? 'deleted=1' : 'error=1'));
    }
    exit();
}

// Must be a POST request with a valid CSRF token
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')
) {
    finish($isAdmin, $back, false, 'Invalid request.');
}

// admin.php sends "id", my_post.php sends "post_id"
$post_id = (int)($_POST['post_id'] ?? $_POST['id'] ?? 0);

if ($post_id <= 0) {
    finish($isAdmin, $back, false, 'Invalid post.');
}

try {
    if ($isAdmin) {
        // Admin can delete any post
        $stmt = $conn->prepare("DELETE FROM post WHERE post_id = :post_id");
        $stmt->execute([':post_id' => $post_id]);
    } else {
        // Buyers can only delete THEIR OWN 'wanted' posts
        $stmt = $conn->prepare("
            DELETE FROM post
            WHERE post_id = :post_id
              AND user_id = :user_id
              AND post_type = 'wanted'
        ");
        $stmt->execute([':post_id' => $post_id, ':user_id' => $user_id]);
    }

    if ($stmt->rowCount() > 0) {
        finish($isAdmin, $back, true, 'Post deleted.');
    }
    finish($isAdmin, $back, false, 'Post not found.');
} catch (PDOException $e) {
    // Uncomment while debugging (e.g. foreign key errors):
    // die($e->getMessage());
    error_log($e->getMessage());
    finish($isAdmin, $back, false, 'Could not delete the post. It may still be referenced by other records.');
}