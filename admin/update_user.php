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
<title>Edit <?= e($displayName) ?> · Admin</title>

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

* { box-sizing: border-box; }

body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); margin: 0; }

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ---------- Navbar ---------- */
.navbar {
    position: sticky;
    top: 0;
    width: 100%;
    background: var(--forest);
    padding: 0 32px;
    display: flex;
    align-items: center;
    z-index: 1000;
    height: 70px;
}

.brand, .logo {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.01em;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-right: 40px;
}
.brand strong, .logo h2 { display: flex; align-items: center; gap: 10px; font-size: 20px; letter-spacing: -0.01em; }
.brand small, .logo p { display: none; }

.navbar nav, .navbar .nav {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    list-style: none;
    margin: 0;
    padding: 0;
}

.navbar a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.78);
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 99px;
    font-weight: 500;
}

.navbar a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
.navbar a.active { background: rgba(255, 255, 255, 0.14); color: #fff; }
.navbar a i { width: 18px; text-align: center; }
.navbar .logout, .navbar li.logout { margin-left: auto; }

/* ---------- Layout ---------- */
.main { margin: 0 auto; padding: 28px 32px 48px; max-width: 1280px; }
.page { max-width: 760px; }

.crumbs { color: var(--muted); font-size: 14px; margin-bottom: 8px; }
.crumbs a { color: var(--muted); text-decoration: none; }
.crumbs a:hover { color: var(--forest); text-decoration: underline; }

.page-head { display: flex; align-items: center; gap: 16px; margin-bottom: 22px; }
.avatar { width: 56px; height: 56px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; flex-shrink: 0; }
.avatar.farmer { background: #dff0e5; color: var(--forest); }
.avatar.buyer  { background: #fbeccb; color: #7a4f0e; }
h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin: 0; }
.sub { color: var(--muted); margin: 2px 0 0; }

/* ---------- Form ---------- */
.panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 28px; }
.section-title { font-size: 15px; font-weight: 700; margin: 0 0 14px; }
.section + .section { margin-top: 28px; padding-top: 24px; border-top: 1px solid var(--line); }

.form-label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }
.optional { color: var(--muted); font-weight: 400; }

.form-control, .form-select { border: 1px solid #cfd8d2; border-radius: 10px; padding: 10px 12px; font: inherit; }
.form-control:focus, .form-select:focus { border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }
.hint { color: var(--muted); font-size: 13px; margin-top: 4px; }

.pw-wrap { position: relative; }
.pw-wrap .form-control { padding-right: 46px; }
.pw-toggle { position: absolute; right: 6px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--muted); width: 36px; height: 36px; border-radius: 8px; }
.pw-toggle:hover { color: var(--ink); background: var(--paper); }

.actions { display: flex; gap: 12px; margin-top: 28px; }
.btn-primary { background: var(--leaf); border-color: var(--leaf); font-weight: 600; padding: 10px 22px; }
.btn-primary:hover, .btn-primary:focus { background: var(--forest); border-color: var(--forest); }
.btn-light { border: 1px solid var(--line); font-weight: 600; padding: 10px 22px; }

/* ---------- Responsive ---------- */
@media (max-width: 768px) {
    .navbar { padding: 0 16px; overflow-x: auto; }
    .brand, .logo { margin-right: 20px; white-space: nowrap; }
    .navbar nav, .navbar .nav { flex: 1; min-width: max-content; }
    .navbar a { white-space: nowrap; }
    .navbar a span { display: none; }
    .main { margin-left: 0; padding: 18px 14px 40px; }
    .panel { padding: 18px; }
}

@media (max-width: 480px) {
    .actions { flex-direction: column-reverse; }
    .actions .btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<nav class="navbar">
    <div class="brand"><i class="fa-solid fa-seedling"></i> Admin Panel</div>

    <nav>
        <a href="admin.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="admin.php#users" class="active"><i class="fa-solid fa-users"></i><span>Users</span></a>
        <a href="admin.php?status=inactive#users"><i class="fa-solid fa-user-slash"></i><span>Inactive users</span></a>
        <a href="add.php"><i class="fa-solid fa-user-plus"></i><span>Add user</span></a>
    </nav>

    <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></a>
</nav>

<main class="main">
<div class="page">

    <div class="crumbs"><a href="admin.php">Dashboard</a> / <a href="admin.php#users">Users</a> / Edit</div>

    <div class="page-head">
        <span class="avatar <?= e($user['role']) ?>"><?= e($initials) ?></span>
        <div>
            <h1><?= e($displayName) ?></h1>
            <p class="sub"><?= e($user['email']) ?></p>
        </div>
    </div>

    <?php if (isset($errors['form'])): ?>
        <div class="alert alert-danger" role="alert"><?= e($errors['form']) ?></div>
    <?php elseif ($errors): ?>
        <div class="alert alert-danger" role="alert">Fix the highlighted fields and try again.</div>
    <?php endif; ?>

    <form method="POST" class="panel" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

        <div class="section">
            <h2 class="section-title">Personal details</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="firstName" class="form-label">First name</label>
                    <input type="text" id="firstName" name="firstName" class="<?= cls($errors, 'firstName') ?>"
                           value="<?= e($old['firstName']) ?>" required>
                    <?= fieldError($errors, 'firstName') ?>
                </div>
                <div class="col-md-4">
                    <label for="middleName" class="form-label">Middle name <span class="optional">(optional)</span></label>
                    <input type="text" id="middleName" name="middleName" class="form-control"
                           value="<?= e($old['middleName']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="lastName" class="form-label">Last name</label>
                    <input type="text" id="lastName" name="lastName" class="<?= cls($errors, 'lastName') ?>"
                           value="<?= e($old['lastName']) ?>" required>
                    <?= fieldError($errors, 'lastName') ?>
                </div>

                <div class="col-md-6">
                    <label for="contact_number" class="form-label">Contact number <span class="optional">(optional)</span></label>
                    <input type="tel" id="contact_number" name="contact_number" class="<?= cls($errors, 'contact_number') ?>"
                           value="<?= e($old['contact_number']) ?>" placeholder="09171234567">
                    <?= fieldError($errors, 'contact_number') ?>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="<?= cls($errors, 'email') ?>"
                           value="<?= e($old['email']) ?>" required>
                    <?= fieldError($errors, 'email') ?>
                </div>

                <div class="col-12">
                    <label for="address" class="form-label">Address <span class="optional">(optional)</span></label>
                    <textarea id="address" name="address" class="form-control" rows="2"><?= e($old['address']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Account</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="<?= cls($errors, 'role', 'form-select') ?>" required>
                        <option value="farmer" <?= $old['role'] === 'farmer' ? 'selected' : '' ?>>Farmer</option>
                        <option value="buyer"  <?= $old['role'] === 'buyer'  ? 'selected' : '' ?>>Buyer</option>
                    </select>
                    <?= fieldError($errors, 'role') ?>
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="<?= cls($errors, 'status', 'form-select') ?>" required>
                        <option value="active"   <?= $old['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $old['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <?= fieldError($errors, 'status') ?>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Reset password</h2>
            <label for="new_password" class="form-label">New password <span class="optional">(optional)</span></label>
            <div class="pw-wrap">
                <input type="password" id="new_password" name="new_password" class="<?= cls($errors, 'new_password') ?>"
                       autocomplete="new-password" minlength="8">
                <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
            <?= fieldError($errors, 'new_password') ?>
            <div class="hint">Leave blank to keep the current password.</div>
        </div>

        <div class="actions">
            <button type="submit" name="update" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Save changes
            </button>
            <a href="admin.php" class="btn btn-light">Cancel</a>
        </div>
    </form>

</div>
</main>

<script>
const pw = document.getElementById('new_password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggle.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});
</script>

</body>
</html>