<?php
session_start();

// Dashboard page to return to
$redirect = 'admin.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

function back(string $type, string $message): void {
    global $redirect;
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $redirect");
    exit;
}

// Only accept POST (the modal form uses POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect");
    exit;
}

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    back('danger', 'Invalid request. Please refresh the page and try again.');
}

$id = (int)($_POST['user_id'] ?? 0);
if ($id <= 0) {
    back('danger', 'No user selected.');
}

// Admin can't delete their own account
if ($id === (int)$_SESSION['user_id']) {
    back('danger', "You can't delete your own account.");
}

try {
    // Make sure PDO throws exceptions so failures are visible
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Only farmers/buyers can be deleted, never admins
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role IN ('farmer', 'buyer')");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        back('success', 'User deleted.');
    }
    back('warning', 'User not found.');

} catch (PDOException $e) {
    // Error 1451 = foreign key constraint (user has linked products/orders/etc.)
    if ($e->getCode() === '23000') {
        back('danger', 'Cannot delete: this user still has related records (products, orders, etc.). Remove those first or enable ON DELETE CASCADE.');
    }
    error_log($e->getMessage());
    back('danger', 'Could not delete the user. Check the server error log.');
}