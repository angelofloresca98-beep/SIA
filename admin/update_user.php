<?php
session_start();

// The original file had no login check at all: anyone with the URL could edit users.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    header("Location: admin.php");
    exit;
}

// Only farmers and buyers are managed here (same as the dashboard list)
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role IN ('farmer','buyer')");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'That user could not be found.'];
    header("Location: admin.php");
    exit;
}

$errors = [];
$old = [
    'firstName'      => $user['firstName'],
    'middleName'     => $user['middleName'] ?? '',
    'lastName'       => $user['lastName'],
    'email'          => $user['email'],
    'contact_number' => $user['contact_number'] ?? '',
    'address'        => $user['address'] ?? '',
    'role'           => $user['role'],
    'status'         => $user['status'] ?? 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }

    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $newPassword = $_POST['new_password'] ?? '';   // optional, never trimmed

    if ($old['firstName'] === '') $errors['firstName'] = 'Enter a first name.';
    if ($old['lastName'] === '')  $errors['lastName']  = 'Enter a last name.';

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Role is limited to farmer/buyer so this form can never create an admin
    if (!in_array($old['role'], ['farmer', 'buyer'], true)) {
        $errors['role'] = 'Select a role.';
    }

    if (!in_array($old['status'], ['active', 'inactive'], true)) {
        $errors['status'] = 'Select a status.';
    }

    if ($old['contact_number'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['contact_number'])) {
        $errors['contact_number'] = 'Use digits only, for example 09171234567.';
    }

    if ($newPassword !== '' && strlen($newPassword) < 8) {
        $errors['new_password'] = 'Use at least 8 characters, or leave it blank.';
    }

    // Email must be unique (ignoring this user)
    if (!isset($errors['email'])) {
        $check = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND user_id <> ?");
        $check->execute([$old['email'], $user_id]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'Another user already has this email.';
        }
    }

    if (!$errors) {
        try {
            $sql = "UPDATE users SET
                        firstName = :firstName, middleName = :middleName, lastName = :lastName,
                        email = :email, role = :role, status = :status,
                        contact_number = :contact_number, address = :address";
            $params = [
                ':firstName'      => $old['firstName'],
                ':middleName'     => $old['middleName'],
                ':lastName'       => $old['lastName'],
                ':email'          => $old['email'],
                ':role'           => $old['role'],
                ':status'         => $old['status'],
                ':contact_number' => $old['contact_number'],
                ':address'        => $old['address'],
                ':user_id'        => $user_id,
            ];

            // Only touch the password if a new one was entered
            if ($newPassword !== '') {
                $sql .= ", password = :password";
                $params[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE user_id = :user_id";
            $conn->prepare($sql)->execute($params);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => $old['firstName'] . ' ' . $old['lastName'] . ' was updated.',
            ];
            header("Location: admin.php");
            exit;

        } catch (PDOException $ex) {
            error_log('update_user.php: ' . $ex->getMessage());
            $errors['form'] = "We couldn't save your changes. Try again in a moment.";
        }
    }
}

function fieldError(array $errors, string $name): string {
    return isset($errors[$name])
        ? '<div class="invalid-feedback d-block">' . e($errors[$name]) . '</div>'
        : '';
}

function cls(array $errors, string $name, string $base = 'form-control'): string {
    return $base . (isset($errors[$name]) ? ' is-invalid' : '');
}

$displayName = trim($user['firstName'] . ' ' . ($user['middleName'] ?? '') . ' ' . $user['lastName']);
$initials    = strtoupper(mb_substr($user['firstName'], 0, 1) . mb_substr($user['lastName'], 0, 1));
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit <?= e($displayName) ?> · AgriBayan Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ink: #1c2a24;
    --muted: #6b7a72;
    --paper: #f4f6f2;
    --card: #ffffff;
    --line: #e4e9e2;
    --forest: #1f4d3a;
    --leaf: #2f7d4f;
    --harvest: #b7791f;
    --radius: 12px;
}

* {
    box-sizing: border-box;
}

body {
    font-family: 'Figtree', system-ui, sans-serif;
    background: var(--paper);
    color: var(--ink);
    margin: 0;
}

a:focus-visible,
button:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* =========================
   SIDEBAR
========================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: var(--forest);
    color: white;
    display: flex;
    flex-direction: column;
    padding: 25px 16px 18px;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 25px;
    border-bottom: 1px solid rgba(255,255,255,.15);
}

.sidebar-brand > i {
    font-size: 29px;
    color: #b9e4c8;
}

.sidebar-brand strong {
    display: block;
    font-size: 20px;
    font-weight: 700;
}

.sidebar-brand small {
    display: block;
    margin-top: 3px;
    color: #c4d9cc;
    font-size: 12px;
}

.sidebar-section-title {
    margin: 28px 10px 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.3px;
    color: #a9c4b3;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px 14px;
    border-radius: 10px;
    color: #dce9e0;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: background .2s, color .2s;
}

.sidebar a i {
    width: 20px;
    flex-shrink: 0;
    text-align: center;
    font-size: 16px;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,.09);
    color: white;
}

.sidebar-nav a.active {
    background: #e1f1e6;
    color: var(--forest);
    font-weight: 700;
}

.sidebar-bottom {
    margin-top: auto;
    padding-top: 25px;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,.15);
    margin-bottom: 14px;
}

.sidebar a.sidebar-logout {
    color: #ffdfd9;
}

.sidebar a.sidebar-logout:hover {
    background: rgba(255,255,255,.09);
    color: white;
}

.sidebar-footer {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 22px;
    padding: 12px 5px;
    border-radius: 10px;
    background: rgba(255,255,255,.07);
    color: #c4d9cc;
    font-size: 12px;
}

/* =========================
   MAIN CONTENT
========================= */
.main {
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
    padding: 32px;
}

.page {
    max-width: 900px;
    margin: 0 auto;
}

.crumbs {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 20px;
}

.crumbs a {
    color: var(--muted);
    text-decoration: none;
}

.crumbs a:hover {
    color: var(--forest);
    text-decoration: underline;
}

/* =========================
   USER HEADER
========================= */
.page-head {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.avatar {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
}

.avatar.farmer {
    background: #dff0e5;
    color: var(--forest);
}

.avatar.buyer {
    background: #fbeccb;
    color: #7a4f0e;
}

h1 {
    font-size: 27px;
    font-weight: 700;
    letter-spacing: -.02em;
    margin: 0;
    overflow-wrap: anywhere;
}

.sub {
    color: var(--muted);
    margin: 4px 0 0;
    overflow-wrap: anywhere;
}

/* =========================
   FORM
========================= */
.panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 28px;
}

.section-title {
    font-size: 15px;
    font-weight: 700;
    margin: 0 0 14px;
}

.section + .section {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
}

.form-label {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
}

.optional {
    color: var(--muted);
    font-weight: 400;
}

.form-control,
.form-select {
    width: 100%;
    border: 1px solid #cfd8d2;
    border-radius: 10px;
    padding: 10px 12px;
    font: inherit;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--leaf);
    box-shadow: 0 0 0 3px rgba(47,125,79,.18);
}

textarea.form-control {
    resize: vertical;
}

.hint {
    color: var(--muted);
    font-size: 13px;
    margin-top: 5px;
}

/* Password visibility */
.pw-wrap {
    position: relative;
}

.pw-wrap .form-control {
    padding-right: 46px;
}

.pw-toggle {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--muted);
    width: 36px;
    height: 36px;
    border-radius: 8px;
}

.pw-toggle:hover {
    color: var(--ink);
    background: var(--paper);
}

/* Buttons */
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
}

.btn-primary {
    background: var(--leaf);
    border-color: var(--leaf);
    font-weight: 600;
    padding: 10px 22px;
}

.btn-primary:hover,
.btn-primary:focus {
    background: var(--forest);
    border-color: var(--forest);
}

.btn-light {
    border: 1px solid var(--line);
    font-weight: 600;
    padding: 10px 22px;
}

/* =========================
   TABLET
========================= */
@media (max-width: 768px) {
    .sidebar {
        width: 190px;
        padding: 22px 10px 14px;
    }

    .sidebar-brand {
        padding-left: 8px;
        gap: 8px;
    }

    .sidebar-brand strong {
        font-size: 17px;
    }

    .sidebar a {
        gap: 9px;
        padding: 12px 9px;
        font-size: 13px;
    }

    .main {
        margin-left: 190px;
        width: calc(100% - 190px);
        padding: 22px 16px;
    }

    .panel {
        padding: 20px;
    }
}

/* =========================
   MOBILE
========================= */
@media (max-width: 480px) {
    .sidebar {
        width: 65px;
        padding: 18px 7px;
    }

    .sidebar-brand {
        justify-content: center;
        padding: 0 0 22px;
    }

    .sidebar-brand > i {
        font-size: 25px;
    }

    .sidebar-brand div,
    .sidebar-section-title,
    .sidebar a span,
    .sidebar-footer {
        display: none;
    }

    .sidebar-nav {
        margin-top: 20px;
    }

    .sidebar a {
        justify-content: center;
        padding: 13px 5px;
    }

    .sidebar a i {
        font-size: 18px;
    }

    .main {
        margin-left: 65px;
        width: calc(100% - 65px);
        padding: 18px 10px;
    }

    .page-head {
        gap: 10px;
    }

    .avatar {
        width: 45px;
        height: 45px;
        font-size: 15px;
    }

    h1 {
        font-size: 21px;
    }

    .sub {
        font-size: 13px;
        overflow-wrap: anywhere;
    }

    .panel {
        padding: 15px;
    }

    .actions {
        flex-direction: column-reverse;
    }

    .actions .btn {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        transition: none !important;
    }
}
</style>
</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->
<aside class="sidebar">

    <div class="sidebar-brand">
        <i class="fa-solid fa-seedling"></i>
        <div>
            <strong>AgriBayan</strong>
            <small>Admin Panel</small>
        </div>
    </div>

    <div class="sidebar-section-title">MAIN MENU</div>

    <nav class="sidebar-nav">
        <a href="admin.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <a href="admin.php#users" class="active" aria-current="page">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>

        <a href="admin.php?status=inactive#users">
            <i class="fa-solid fa-user-slash"></i>
            <span>Inactive Users</span>
        </a>

        <a href="add.php">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add User</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-divider"></div>

        <a href="../logout.php" class="sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Log Out</span>
        </a>

        <div class="sidebar-footer">
            <i class="fa-solid fa-leaf"></i>
            <span>Growing together</span>
        </div>
    </div>

</aside>

<!-- =========================
     MAIN CONTENT
========================= -->
<main class="main">
<div class="page">

    <div class="crumbs">
        <a href="admin.php">Dashboard</a>
        /
        <a href="admin.php#users">Users</a>
        / Edit User
    </div>

    <!-- USER INFORMATION -->
    <div class="page-head">

        <span class="avatar <?= e($user['role']) ?>">
            <?= e($initials) ?>
        </span>

        <div>
            <h1><?= e($displayName) ?></h1>
            <p class="sub"><?= e($user['email']) ?></p>
        </div>

    </div>

    <!-- ERROR MESSAGES -->
    <?php if (isset($errors['form'])): ?>

        <div class="alert alert-danger" role="alert">
            <?= e($errors['form']) ?>
        </div>

    <?php elseif ($errors): ?>

        <div class="alert alert-danger" role="alert">
            Fix the highlighted fields and try again.
        </div>

    <?php endif; ?>

    <!-- EDIT USER FORM -->
    <form method="POST" class="panel" novalidate>

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($_SESSION['csrf_token']) ?>"
        >

        <!-- PERSONAL DETAILS -->
        <div class="section">
            <h2 class="section-title">Personal Details</h2>

            <div class="row g-3">

                <div class="col-md-4">
                    <label for="firstName" class="form-label">
                        First Name
                    </label>

                    <input
                        type="text"
                        id="firstName"
                        name="firstName"
                        class="<?= cls($errors, 'firstName') ?>"
                        value="<?= e($old['firstName']) ?>"
                        autocomplete="given-name"
                        required
                    >

                    <?= fieldError($errors, 'firstName') ?>
                </div>

                <div class="col-md-4">
                    <label for="middleName" class="form-label">
                        Middle Name
                        <span class="optional">(optional)</span>
                    </label>

                    <input
                        type="text"
                        id="middleName"
                        name="middleName"
                        class="form-control"
                        value="<?= e($old['middleName']) ?>"
                        autocomplete="additional-name"
                    >
                </div>

                <div class="col-md-4">
                    <label for="lastName" class="form-label">
                        Last Name
                    </label>

                    <input
                        type="text"
                        id="lastName"
                        name="lastName"
                        class="<?= cls($errors, 'lastName') ?>"
                        value="<?= e($old['lastName']) ?>"
                        autocomplete="family-name"
                        required
                    >

                    <?= fieldError($errors, 'lastName') ?>
                </div>

                <div class="col-md-6">
                    <label for="contact_number" class="form-label">
                        Contact Number
                        <span class="optional">(optional)</span>
                    </label>

                    <input
                        type="tel"
                        id="contact_number"
                        name="contact_number"
                        class="<?= cls($errors, 'contact_number') ?>"
                        value="<?= e($old['contact_number']) ?>"
                        placeholder="09171234567"
                        autocomplete="tel"
                    >

                    <?= fieldError($errors, 'contact_number') ?>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="<?= cls($errors, 'email') ?>"
                        value="<?= e($old['email']) ?>"
                        autocomplete="email"
                        required
                    >

                    <?= fieldError($errors, 'email') ?>
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">
                        Address
                        <span class="optional">(optional)</span>
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="form-control"
                        rows="2"
                        autocomplete="street-address"
                    ><?= e($old['address']) ?></textarea>
                </div>

            </div>
        </div>

        <!-- ACCOUNT SETTINGS -->
        <div class="section">
            <h2 class="section-title">Account Settings</h2>

            <div class="row g-3">

                <div class="col-md-6">
                    <label for="role" class="form-label">
                        Account Role
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="<?= cls($errors, 'role', 'form-select') ?>"
                        required
                    >
                        <option
                            value="farmer"
                            <?= $old['role'] === 'farmer' ? 'selected' : '' ?>
                        >
                            Farmer
                        </option>

                        <option
                            value="buyer"
                            <?= $old['role'] === 'buyer' ? 'selected' : '' ?>
                        >
                            Buyer
                        </option>
                    </select>

                    <?= fieldError($errors, 'role') ?>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">
                        Account Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="<?= cls($errors, 'status', 'form-select') ?>"
                        required
                    >
                        <option
                            value="active"
                            <?= $old['status'] === 'active' ? 'selected' : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= $old['status'] === 'inactive' ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>
                    </select>

                    <?= fieldError($errors, 'status') ?>
                </div>

            </div>
        </div>

        <!-- PASSWORD RESET -->
        <div class="section">
            <h2 class="section-title">Reset Password</h2>

            <label for="new_password" class="form-label">
                New Password
                <span class="optional">(optional)</span>
            </label>

            <div class="pw-wrap">
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="<?= cls($errors, 'new_password') ?>"
                    autocomplete="new-password"
                    minlength="8"
                >

                <button
                    type="button"
                    class="pw-toggle"
                    id="pwToggle"
                    aria-label="Show password"
                    aria-pressed="false"
                >
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <?= fieldError($errors, 'new_password') ?>

            <div class="hint">
                Leave blank to keep the current password.
            </div>
        </div>

        <!-- FORM BUTTONS -->
        <div class="actions">

            <button
                type="submit"
                name="update"
                class="btn btn-primary"
            >
                <i class="fa-solid fa-floppy-disk me-1"></i>
                Save Changes
            </button>

            <a href="admin.php" class="btn btn-light">
                Cancel
            </a>

        </div>

    </form>

</div>
</main>

<!-- PASSWORD VISIBILITY -->
<script>
const pw = document.getElementById('new_password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';

    pw.type = show ? 'text' : 'password';

    toggle.setAttribute(
        'aria-label',
        show ? 'Hide password' : 'Show password'
    );

    toggle.setAttribute('aria-pressed', String(show));

    toggle.querySelector('i').className = show
        ? 'fa-solid fa-eye-slash'
        : 'fa-solid fa-eye';
});
</script>

</body>
</html>
