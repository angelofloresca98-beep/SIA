<?php
session_start();

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

$errors = [];
$old = ['firstName' => '', 'middleName' => '', 'lastName' => '', 'email' => '', 'role' => '', 'address' => '', 'contact' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }

    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';   // never trim passwords

    // Validation
    if ($old['firstName'] === '') $errors['firstName'] = 'Enter a first name.';
    if ($old['lastName'] === '')  $errors['lastName']  = 'Enter a last name.';

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }

    if (!in_array($old['role'], ['farmer', 'buyer'], true)) {
        $errors['role'] = 'Select a role.';
    }

    if ($old['contact'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['contact'])) {
        $errors['contact'] = 'Use digits only, for example 09171234567.';
    }

    // Email must be unique
    if (!isset($errors['email'])) {
        $check = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
        $check->execute([$old['email']]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'This email is already registered.';
        }
    }

    if (!$errors) {
        try {
            $insert = $conn->prepare(
                "INSERT INTO users (firstName, middleName, lastName, email, password, role, address, contact_number)
                 VALUES (?,?,?,?,?,?,?,?)"
            );
            $insert->execute([
                $old['firstName'],
                $old['middleName'],
                $old['lastName'],
                $old['email'],
                password_hash($password, PASSWORD_DEFAULT),
                $old['role'],
                $old['address'],
                $old['contact'],
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => $old['firstName'] . ' ' . $old['lastName'] . ' was added as a ' . $old['role'] . '.',
            ];
            header("Location: admin.php");
            exit;

        } catch (PDOException $ex) {
            error_log('add.php: ' . $ex->getMessage());   // log it, don't show it
            $errors['form'] = "We couldn't save this user. Try again in a moment.";
        }
    }
}

function fieldError(array $errors, string $name): string {
    return isset($errors[$name])
        ? '<div class="invalid-feedback d-block">' . e($errors[$name]) . '</div>'
        : '';
}

function cls(array $errors, string $name): string {
    return 'form-control' . (isset($errors[$name]) ? ' is-invalid' : '');
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User · AgriBayan Admin</title>

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
select:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ==============================
   SIDEBAR
================================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: var(--forest);
    color: #fff;
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
    text-align: center;
    font-size: 16px;
    flex-shrink: 0;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,.09);
    color: #fff;
}

.sidebar-nav a.active {
    background: #e1f1e6;
    color: var(--forest);
    font-weight: 700;
    box-shadow: 0 3px 10px rgba(0,0,0,.08);
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
    color: #fff;
}

.sidebar-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 22px;
    padding: 12px 5px;
    border-radius: 10px;
    background: rgba(255,255,255,.07);
    color: #c4d9cc;
    font-size: 12px;
}

/* ==============================
   MAIN CONTENT
================================= */
.main {
    margin-left: 250px;
    padding: 32px;
    min-height: 100vh;
    width: calc(100% - 250px);
}

.page {
    max-width: 900px;
    margin: 0 auto;
}

.crumbs {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 10px;
}

.crumbs a {
    color: var(--muted);
    text-decoration: none;
}

.crumbs a:hover {
    color: var(--forest);
    text-decoration: underline;
}

h1 {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: -.02em;
    margin: 0 0 6px;
}

.lead-text {
    color: var(--muted);
    margin-bottom: 24px;
}

/* ==============================
   FORM
================================= */
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

.form-label .optional {
    color: var(--muted);
    font-weight: 400;
}

.form-control,
.form-select {
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

.hint {
    color: var(--muted);
    font-size: 13px;
    margin-top: 4px;
}

/* Role picker */
.role-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.role-options input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.role-options label {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #cfd8d2;
    border-radius: 10px;
    padding: 14px 16px;
    cursor: pointer;
    font-weight: 600;
    transition: border-color .15s, background .15s;
}

.role-options label i {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.role-options .farmer i {
    background: #dff0e5;
    color: var(--forest);
}

.role-options .buyer i {
    background: #fbeccb;
    color: #7a4f0e;
}

.role-options input:checked + label {
    border-color: var(--leaf);
    background: #f3faf5;
    box-shadow: 0 0 0 1px var(--leaf);
}

.role-options input:focus-visible + label {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* Password toggle */
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

.actions {
    display: flex;
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

/* ==============================
   TABLET
================================= */
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

/* ==============================
   MOBILE
================================= */
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

    .sidebar-bottom {
        margin-top: auto;
    }

    .main {
        margin-left: 65px;
        width: calc(100% - 65px);
        padding: 18px 10px;
    }

    h1 {
        font-size: 23px;
    }

    .lead-text {
        font-size: 14px;
    }

    .panel {
        padding: 15px;
    }

    .role-options {
        grid-template-columns: 1fr;
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

<!-- SIDEBAR -->
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

        <a href="admin.php#users">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>

        <a href="admin.php?status=inactive#users">
            <i class="fa-solid fa-user-slash"></i>
            <span>Inactive Users</span>
        </a>

        <a href="add.php" class="active" aria-current="page">
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

<!-- MAIN CONTENT -->
<main class="main">
    <div class="page">

        <div class="crumbs">
            <a href="admin.php">Dashboard</a> / Add User
        </div>

        <h1>Add User</h1>
        <p class="lead-text">
            Create a farmer or buyer account. They can sign in
            with the email and password you set here.
        </p>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($errors['form']) ?>
            </div>
        <?php elseif ($errors): ?>
            <div class="alert alert-danger" role="alert">
                Fix the highlighted fields and try again.
            </div>
        <?php endif; ?>

        <form method="POST" class="panel" novalidate>

            <input type="hidden"
                   name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <!-- ACCOUNT TYPE -->
            <div class="section">
                <h2 class="section-title">Account Type</h2>

                <div class="role-options">
                    <div>
                        <input
                            type="radio"
                            name="role"
                            id="roleFarmer"
                            value="farmer"
                            <?= $old['role'] === 'farmer' ? 'checked' : '' ?>
                        >

                        <label for="roleFarmer" class="farmer">
                            <i class="fa-solid fa-tractor"></i>
                            Farmer
                        </label>
                    </div>

                    <div>
                        <input
                            type="radio"
                            name="role"
                            id="roleBuyer"
                            value="buyer"
                            <?= $old['role'] === 'buyer' ? 'checked' : '' ?>
                        >

                        <label for="roleBuyer" class="buyer">
                            <i class="fa-solid fa-cart-shopping"></i>
                            Buyer
                        </label>
                    </div>
                </div>

                <?= fieldError($errors, 'role') ?>
            </div>

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
                            autocomplete="off"
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
                            autocomplete="off"
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
                            autocomplete="off"
                            required
                        >

                        <?= fieldError($errors, 'lastName') ?>
                    </div>

                    <div class="col-md-6">
                        <label for="contact" class="form-label">
                            Contact Number
                            <span class="optional">(optional)</span>
                        </label>

                        <input
                            type="tel"
                            id="contact"
                            name="contact"
                            class="<?= cls($errors, 'contact') ?>"
                            value="<?= e($old['contact']) ?>"
                            placeholder="09171234567"
                            autocomplete="off"
                        >

                        <?= fieldError($errors, 'contact') ?>
                    </div>

                    <div class="col-md-6">
                        <label for="address" class="form-label">
                            Address
                            <span class="optional">(optional)</span>
                        </label>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            class="form-control"
                            value="<?= e($old['address']) ?>"
                            autocomplete="off"
                        >
                    </div>

                </div>
            </div>

            <!-- SIGN-IN DETAILS -->
            <div class="section">
                <h2 class="section-title">Sign-in Details</h2>

                <div class="row g-3">

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
                            autocomplete="off"
                            required
                        >

                        <?= fieldError($errors, 'email') ?>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">
                            Password
                        </label>

                        <div class="pw-wrap">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="<?= cls($errors, 'password') ?>"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="pw-toggle"
                                id="pwToggle"
                                aria-label="Show password"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                        <?= fieldError($errors, 'password') ?>

                        <div class="hint">
                            At least 8 characters.
                        </div>
                    </div>

                </div>
            </div>

            <!-- FORM ACTIONS -->
            <div class="actions">
                <button type="submit" name="create" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus me-1"></i>
                    Create User
                </button>

                <a href="admin.php" class="btn btn-light">
                    Cancel
                </a>
            </div>

        </form>
    </div>
</main>

<script>
const pw = document.getElementById('password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';

    pw.type = show ? 'text' : 'password';

    toggle.setAttribute(
        'aria-label',
        show ? 'Hide password' : 'Show password'
    );

    toggle.querySelector('i').className = show
        ? 'fa-solid fa-eye-slash'
        : 'fa-solid fa-eye';
});
</script>

</body>
</html>
