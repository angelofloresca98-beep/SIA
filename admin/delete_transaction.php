<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

$back = 'admin.php#sales'; // admin dashboard

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid request.'];
    header("Location: $back");
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid transaction.'];
    header("Location: $back");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = $stmt->rowCount()
        ? ['type' => 'success', 'message' => 'Transaction deleted.']
        : ['type' => 'warning', 'message' => 'Transaction not found.'];
} catch (PDOException $ex) {
    // Most likely a foreign key constraint referencing this row
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Could not delete the transaction. It may still be referenced by other records.'];
}

header("Location: $back");
exit;