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

function fieldError(array $errors, string $name): string {
    return isset($errors[$name])
        ? '<div class="invalid-feedback d-block">' . e($errors[$name]) . '</div>'
        : '';
}

function cls(array $errors, string $name, string $base = 'form-control'): string {
    return $base . (isset($errors[$name]) ? ' is-invalid' : '');
}

$errors = [];
$old = [
    'firstName'      => '',
    'middleName'     => '',
    'lastName'       => '',
    'email'          => '',
    'contact_number' => '',
    'address'        => '',
    'role'           => 'farmer',
    'status'         => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }

    foreach ($old as $key => $_) {
        $old[$key] = trim($_POST[$key] ?? '');
    }
    $password = $_POST['password'] ?? '';   // never trimmed

    if ($old['firstName'] === '') $errors['firstName'] = 'Enter a first name.';
    if ($old['lastName'] === '')  $errors['lastName']  = 'Enter a last name.';

    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Only farmer / buyer, so this form can never create an admin
    if (!in_array($old['role'], ['farmer', 'buyer'], true)) {
        $errors['role'] = 'Select a role.';
    }

    if (!in_array($old['status'], ['active', 'inactive'], true)) {
        $errors['status'] = 'Select a status.';
    }

    if ($old['contact_number'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $old['contact_number'])) {
        $errors['contact_number'] = 'Use digits only, for example 09171234567.';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }

    // Email must be unique
    if (!isset($errors['email'])) {
        $check = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
        $check->execute([$old['email']]);
        if ($check->fetchColumn()) {
            $errors['email'] = 'A user with this email already exists.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO users
                    (firstName, middleName, lastName, email, password, role, status, contact_number, address)
                 VALUES
                    (:firstName, :middleName, :lastName, :email, :password, :role, :status, :contact_number, :address)"
            );
            $stmt->execute([
                ':firstName'      => $old['firstName'],
                ':middleName'     => $old['middleName'],
                ':lastName'       => $old['lastName'],
                ':email'          => $old['email'],
                ':password'       => password_hash($password, PASSWORD_DEFAULT),
                ':role'           => $old['role'],
                ':status'         => $old['status'],
                ':contact_number' => $old['contact_number'],
                ':address'        => $old['address'],
            ]);

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => $old['firstName'] . ' ' . $old['lastName'] . ' was added as a ' . $old['role'] . '.',
            ];
            header("Location: admin.php");
            exit;

        } catch (PDOException $ex) {
            error_log('add.php: ' . $ex->getMessage());
            $errors['form'] = "We couldn't add this user. Try again in a moment.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User · Admin Dashboard</title>

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
    --forest-dark: #173a2c;
    --leaf: #2f7d4f;
    --leaf-soft: #dff0e5;
    --harvest: #b7791f;
    --harvest-soft: #fbeccb;
    --danger: #c0392b;
    --danger-soft: #fbe3e0;
    --radius: 12px;
    --sidebar-w: 250px;
}

* { box-sizing: border-box; }

body {
    font-family: 'Figtree', system-ui, sans-serif;
    background: var(--paper);
    color: var(--ink);
    margin: 0;
}

a:focus-visible, button:focus-visible, input:focus-visible,
select:focus-visible, textarea:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ---------- Sidebar ---------- */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-w);
    background: var(--forest);
    color: #fff;
    display: flex;
    flex-direction: column;
    padding: 24px 16px;
    z-index: 1040;
    overflow-y: auto;
    transition: transform .25s ease;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.01em;
    padding: 0 10px 24px;
    margin-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.brand i {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.14);
    display: inline-flex; align-items: center; justify-content: center;
}

.side-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.45);
    padding: 18px 12px 8px;
}

.side-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sidebar a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255, 255, 255, 0.78);
    text-decoration: none;
    padding: 11px 14px;
    border-radius: 10px;
    font-weight: 500;
    transition: background .15s, color .15s;
}

.sidebar a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
.sidebar a.active { background: rgba(255, 255, 255, 0.16); color: #fff; font-weight: 600; }
.sidebar a i { width: 20px; text-align: center; }

.sidebar .logout {
    margin-top: auto;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0 0 10px 10px;
    padding-top: 16px;
}

.sidebar .logout:hover { background: rgba(255, 255, 255, 0.08); }

/* Mobile top bar (hidden on desktop) */
.mobile-bar {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 58px;
    background: var(--forest);
    color: #fff;
    align-items: center;
    gap: 14px;
    padding: 0 16px;
    z-index: 1030;
    font-weight: 700;
}

.menu-toggle {
    width: 40px; height: 40px;
    border: 0; border-radius: 10px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    font-size: 18px;
    cursor: pointer;
}

.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(28, 42, 36, 0.5);
    z-index: 1035;
}

/* ---------- Layout ---------- */
.main {
    margin-left: var(--sidebar-w);
    padding: 28px 36px 48px;
}

.page { max-width: 900px; }

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    gap: 16px;
}

.topbar h1 { font-size: 26px; font-weight: 700; margin: 0 0 2px; letter-spacing: -0.02em; }
.topbar p { color: var(--muted); margin: 0; }

.admin-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 999px;
    padding: 6px 16px 6px 6px;
}

.avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px;
    flex-shrink: 0;
}

.admin-chip .avatar { background: var(--forest); color: #fff; }
.admin-chip small { color: var(--muted); display: block; line-height: 1.1; }
.admin-chip strong { line-height: 1.2; }

/* ---------- Panel + form ---------- */
.panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 28px;
}

.section-title { font-size: 15px; font-weight: 700; margin: 0 0 14px; }

.section + .section {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid var(--line);
}

.form-label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }
.optional { color: var(--muted); font-weight: 400; }

.form-control, .form-select {
    width: 100%;
    border: 1px solid #cfd8d2;
    border-radius: 10px;
    padding: 10px 12px;
    font: inherit;
}

.form-control:focus, .form-select:focus {
    border-color: var(--leaf);
    box-shadow: 0 0 0 3px rgba(47, 125, 79, .18);
}

textarea.form-control { resize: vertical; }

.hint { color: var(--muted); font-size: 13px; margin-top: 5px; }

.pw-wrap { position: relative; }
.pw-wrap .form-control { padding-right: 46px; }
.pw-toggle {
    position: absolute;
    right: 6px; top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: var(--muted);
    width: 36px; height: 36px;
    border-radius: 8px;
}
.pw-toggle:hover { color: var(--ink); background: var(--paper); }

.actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }

.btn-primary { background: var(--leaf); border-color: var(--leaf); font-weight: 600; padding: 10px 22px; }
.btn-primary:hover, .btn-primary:focus { background: var(--forest); border-color: var(--forest); }
.btn-light { border: 1px solid var(--line); font-weight: 600; padding: 10px 22px; }

/* ---------- Responsive (same breakpoints as dashboard) ---------- */
/* Sidebar becomes a slide-in drawer */
@media (max-width: 992px) {
    .mobile-bar { display: flex; }

    .sidebar { transform: translateX(-100%); box-shadow: none; }
    .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,.35); }
    .sidebar-backdrop.show { display: block; }

    .main { margin-left: 0; padding: 78px 16px 40px; }

    .topbar { flex-direction: column; align-items: flex-start; }
    .panel { padding: 18px; }
}

@media (max-width: 480px) {
    .actions { flex-direction: column-reverse; }
    .actions .btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    * { transition: none !important; }
}
</style>
<link rel="stylesheet" href="theme.css">
</head>

<body>

<!-- Mobile top bar (only visible on small screens) -->
<div class="mobile-bar">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
    </button>
    <span><i class="fa-solid fa-seedling me-2"></i>Admin Panel</span>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand"><i class="fa-solid fa-seedling"></i> Admin Panel</div>

    <div class="side-label">Menu</div>
    <nav class="side-nav">
        <a href="admin.php#dashboard"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="admin.php#users"><i class="fa-solid fa-users"></i><span>User Management</span></a>
        <a href="add.php" class="active" aria-current="page"><i class="fa-solid fa-user-plus"></i><span>Add User</span></a>
        <a href="admin.php#posts"><i class="fa-solid fa-newspaper"></i><span>Posts</span></a>
        <a href="admin.php#sales"><i class="fa-solid fa-chart-line"></i><span>Sales Overview</span></a>
    </nav>

    <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></a>
</aside>

<main class="main">
<div class="page">

    <header class="topbar">
        <div>
            <h1>Add User</h1>
            <p>Create a new farmer or buyer account.</p>
        </div>

        <div class="admin-chip">
            <span class="avatar"><?= e(strtoupper(mb_substr($_SESSION['user'] ?? 'A', 0, 1))) ?></span>
            <div>
                <strong><?= e($_SESSION['user'] ?? 'Admin') ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </header>

    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
    <?php elseif ($errors): ?>
        <div class="alert alert-danger" role="alert">Fix the highlighted fields and try again.</div>
    <?php endif; ?>

    <form method="POST" class="panel" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <!-- Personal details -->
        <div class="section">
            <h2 class="section-title">Personal Details</h2>

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="firstName" class="form-label">First Name</label>
                    <input type="text" id="firstName" name="firstName"
                           class="<?= cls($errors, 'firstName') ?>"
                           value="<?= e($old['firstName']) ?>" autocomplete="given-name" required>
                    <?= fieldError($errors, 'firstName') ?>
                </div>

                <div class="col-md-4">
                    <label for="middleName" class="form-label">Middle Name <span class="optional">(optional)</span></label>
                    <input type="text" id="middleName" name="middleName" class="form-control"
                           value="<?= e($old['middleName']) ?>" autocomplete="additional-name">
                </div>

                <div class="col-md-4">
                    <label for="lastName" class="form-label">Last Name</label>
                    <input type="text" id="lastName" name="lastName"
                           class="<?= cls($errors, 'lastName') ?>"
                           value="<?= e($old['lastName']) ?>" autocomplete="family-name" required>
                    <?= fieldError($errors, 'lastName') ?>
                </div>

                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact Number <span class="optional">(optional)</span></label>
                    <input type="tel" id="contact_number" name="contact_number"
                           class="<?= cls($errors, 'contact_number') ?>"
                           value="<?= e($old['contact_number']) ?>" placeholder="09171234567" autocomplete="tel">
                    <?= fieldError($errors, 'contact_number') ?>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email"
                           class="<?= cls($errors, 'email') ?>"
                           value="<?= e($old['email']) ?>" autocomplete="email" required>
                    <?= fieldError($errors, 'email') ?>
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">Address <span class="optional">(optional)</span></label>
                    <textarea id="address" name="address" class="form-control" rows="2"
                              autocomplete="street-address"><?= e($old['address']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- Account settings -->
        <div class="section">
            <h2 class="section-title">Account Settings</h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="role" class="form-label">Account Role</label>
                    <select id="role" name="role" class="<?= cls($errors, 'role', 'form-select') ?>" required>
                        <option value="farmer" <?= $old['role'] === 'farmer' ? 'selected' : '' ?>>Farmer</option>
                        <option value="buyer"  <?= $old['role'] === 'buyer'  ? 'selected' : '' ?>>Buyer</option>
                    </select>
                    <?= fieldError($errors, 'role') ?>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">Account Status</label>
                    <select id="status" name="status" class="<?= cls($errors, 'status', 'form-select') ?>" required>
                        <option value="active"   <?= $old['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <?= fieldError($errors, 'status') ?>
                </div>
            </div>
        </div>

        <!-- Password -->
        <div class="section">
            <h2 class="section-title">Password</h2>

            <label for="password" class="form-label">Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password"
                       class="<?= cls($errors, 'password') ?>"
                       autocomplete="new-password" minlength="8" required>
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password" aria-pressed="false">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <?= fieldError($errors, 'password') ?>
            <div class="hint">At least 8 characters.</div>
        </div>

        <div class="actions">
            <button type="submit" name="add" class="btn btn-primary">
                <i class="fa-solid fa-user-plus me-1"></i> Add User
            </button>
            <a href="admin.php" class="btn btn-light">Cancel</a>
        </div>
    </form>

</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar: mobile open/close
const sidebar   = document.getElementById('sidebar');
const backdrop  = document.getElementById('sidebarBackdrop');
const menuBtn   = document.getElementById('menuToggle');

function setSidebar(open) {
    sidebar.classList.toggle('open', open);
    backdrop.classList.toggle('show', open);
    menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
}

menuBtn.addEventListener('click', function () {
    setSidebar(!sidebar.classList.contains('open'));
});
backdrop.addEventListener('click', function () { setSidebar(false); });
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setSidebar(false);
});

// Password visibility
const pw = document.getElementById('password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggle.setAttribute('aria-pressed', String(show));
    toggle.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});
</script>

</body>
</html>