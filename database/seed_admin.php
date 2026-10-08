<?php
/**
 * Admin Seeder
 * ─────────────────────────────────────────────────────
 * Run this script ONCE to insert the default admin
 * account into the `users` table.
 *
 * Usage: http://localhost/SIA/database/seed_admin.php
 * ─────────────────────────────────────────────────────
 */

require __DIR__ . '/connection.php';

// ── Default admin credentials (change before deploying) ──
$admins = [
    [
        'firstName'  => 'Super',
        'middleName' => 'A',
        'lastName'   => 'Admin',
        'email'      => 'admin@farmtomarket.com',
        'password'   => 'Admin@1234',   // plain-text; will be hashed below
        'role'       => 'admin',
        'status'     => 'active',
    ],
];

$inserted = 0;
$skipped  = 0;

try {
    $checkStmt  = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $insertStmt = $conn->prepare(
        "INSERT INTO users (firstName, middleName, lastName, email, password, role, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($admins as $admin) {
        $checkStmt->execute([$admin['email']]);

        if ($checkStmt->rowCount() > 0) {
            echo "&#9888;  Skipped &mdash; email already exists: <strong>{$admin['email']}</strong><br>";
            $skipped++;
            continue;
        }

        $hashed = password_hash($admin['password'], PASSWORD_DEFAULT);

        $insertStmt->execute([
            $admin['firstName'],
            $admin['middleName'],
            $admin['lastName'],
            $admin['email'],
            $hashed,
            $admin['role'],
            $admin['status'],
        ]);

        echo "&#10003;  Admin seeded: <strong>{$admin['email']}</strong><br>";
        $inserted++;
    }

    echo "<hr>";
    echo "Done &mdash; <strong>{$inserted}</strong> inserted, <strong>{$skipped}</strong> skipped.<br>";
    echo "<a href='../login.php'>&rarr; Go to Login</a>";

} catch (PDOException $e) {
    echo "&#10007;  Database error: " . htmlspecialchars($e->getMessage());
}
?>
