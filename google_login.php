<?php
// google_login.php - put this in the same folder as login.php
session_start();
header('Content-Type: application/json');
require_once './database/connection.php';

$clientId  = '867338478390-0d6a06kjso3dect629o0mk7mq681jr9c.apps.googleusercontent.com';
$redirects = ['buyer' => 'buyers/buyer.php', 'farmer' => 'seller/farmer.php'];

function reply($ok, $data = []) {
    echo json_encode(['ok' => $ok] + $data);
    exit;
}

// 1. Get the token and the chosen role (buyer / farmer)
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['credential'] ?? '';
$role  = $input['role'] ?? '';

if ($token === '' || !isset($redirects[$role])) {
    reply(false, ['error' => 'Invalid request.']);
}

// 2. Ask Google who this is
$json = @file_get_contents('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token));
$g    = json_decode($json ?: '{}', true);

if (($g['aud'] ?? '') !== $clientId || empty($g['email'])) {
    reply(false, ['error' => 'Google sign-in failed.']);
}

$email   = strtolower($g['email']);
$first   = $g['given_name']  ?? 'User';
$last    = $g['family_name'] ?? '';
$picture = $g['picture']     ?? '';

// 3. Find the user, or create one (no password needed)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if ($user['role'] !== $role) {
        reply(false, ['error' => 'This email is registered as a ' . $user['role'] . '. Choose that role instead.']);
    }
    if ($user['status'] !== 'active') {
        reply(false, ['error' => 'Your account is inactive.']);
    }
} else {
    $stmt = $conn->prepare(
        "INSERT INTO users (firstName, middleName, lastName, email, password, picture, role, status)
         VALUES (?, '', ?, ?, '', ?, ?, 'active')"
    );
    $stmt->execute([$first, $last, $email, $picture, $role]);

    $user = [
        'user_id'   => $conn->lastInsertId(),
        'firstName' => $first,
        'lastName'  => $last,
        'email'     => $email,
        'role'      => $role,
    ];
}

// 4. Log in (same session values as login.php)
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['user']    = $user['firstName'] . ' ' . $user['lastName'];
$_SESSION['role']    = $user['role'];
$_SESSION['email']   = $user['email'];

reply(true, ['redirect' => $redirects[$role]]);