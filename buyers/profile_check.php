<?php
// Include AFTER session_start(), connection.php, and the login/role checks.
$chk = $conn->prepare("SELECT address, contact_number FROM users WHERE user_id=?");
$chk->execute([$_SESSION['user_id']]);
$u = $chk->fetch(PDO::FETCH_ASSOC);

if (!$u || trim((string)$u['address']) === '' || trim((string)$u['contact_number']) === '') {
    header("Location: complete_profile.php");
    exit();
}