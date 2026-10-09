<?php
session_start();
require './database/connection.php';

if (isset($_POST['create'])) {
    $firstName  = trim($_POST['firstName']  ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName   = trim($_POST['lastName']   ?? '');
    $contact    = trim($_POST['contact']    ?? '');
    $address   = trim($_POST['location']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = trim($_POST['password']   ?? '');
    $role       = trim($_POST['role']       ?? '');

    $allowedRoles = ['farmer', 'buyer'];

    if (empty($firstName) || empty($middleName) || empty($lastName) ||
        empty($contact) || empty($address) ||
        empty($email) || empty($password) || empty($role)) {
        $_SESSION['message'] = 'All fields are required!';
        $_SESSION['type']    = 'danger';

    } elseif (!preg_match('/^[0-9]{11}$/', $contact)) {
    $_SESSION['message'] = 'Contact number must be 11 digits, numbers only.';
    $_SESSION['type']    = 'danger'; 
    
   } elseif (!in_array($role, $allowedRoles)) {
        $_SESSION['message'] = 'Please select a valid role (Farmer or Buyer).';
        $_SESSION['type']    = 'danger';

    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql  = 'SELECT user_id FROM users WHERE email = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = 'That email is already registered.';
                $_SESSION['type']    = 'danger';
            } else {
                $sql  = "INSERT INTO users (firstName, middleName, lastName, contact_number, address, email, password, role, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);

                if ($stmt->execute([$firstName, $middleName, $lastName, $contact, $address, $email, $hashedPassword, $role])) {
                    $_SESSION['message'] = 'Account created! You can now sign in.';
                    $_SESSION['type']    = 'success';
                }
            }

        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error: ' . $e->getMessage();
            $_SESSION['type']    = 'danger';
        }
    }

    header('Location: register.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · Farm to Market</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ink: #1c2a24;
    --muted: #6b7a72;
    --paper: #f4f6f2;
    --forest: #1f4d3a;
    --leaf: #2f7d4f;
    --harvest: #b7791f;
    --danger: #b3261e;
    --danger-soft: #fbe3e0;
    --ok-soft: #dff0e5;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Figtree', system-ui, sans-serif;
    color: var(--ink);
    background: var(--paper);
    min-height: 100vh;
    display: grid;
    grid-template-columns: minmax(320px, 5fr) 6fr;
}

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ---------- Brand panel ---------- */
.brand-panel {
    background: var(--forest);
    color: #fff;
    padding: 48px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.brand-mark { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 20px; }
.brand-mark i {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.14);
    display: inline-flex; align-items: center; justify-content: center;
}

.brand-copy h2 { font-size: 34px; line-height: 1.15; letter-spacing: -0.02em; max-width: 14ch; margin-bottom: 14px; }
.brand-copy p  { color: rgba(255,255,255,.75); max-width: 36ch; line-height: 1.55; }
.brand-foot    { color: rgba(255,255,255,.55); font-size: 13px; }

/* ---------- Role cards ---------- */
.role-picker { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px; }

.role-card input[type="radio"] { display: none; }

.role-card label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 10px;
    border: 2px solid #cbd5cd;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
    transition: border-color .15s, box-shadow .15s, background .15s;
}

.role-card label i {
    font-size: 24px;
    color: var(--muted);
    transition: color .15s;
}

.role-card label .role-desc {
    font-weight: 400;
    font-size: 12px;
    color: var(--muted);
}

.role-card input[type="radio"]:checked + label {
    border-color: var(--leaf);
    background: var(--ok-soft);
    box-shadow: 0 0 0 3px rgba(47,125,79,.15);
}

.role-card input[type="radio"]:checked + label i { color: var(--leaf); }

.role-card label:hover { border-color: var(--leaf); }

/* ---------- Form panel ---------- */
.form-panel { display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
.form-wrap  { width: 100%; max-width: 440px; }

h1  { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; }
.sub { color: var(--muted); margin-bottom: 26px; }

.section-label {
    font-size: 12px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 10px;
}

.alert {
    display: flex; gap: 10px; align-items: flex-start;
    border-radius: 10px; padding: 12px 14px; margin-bottom: 18px;
    font-size: 14.5px; font-weight: 500;
}
.alert i { margin-top: 2px; }
.alert-danger  { background: var(--danger-soft); color: var(--danger); }
.alert-success { background: var(--ok-soft); color: var(--forest); }

.row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field { margin-bottom: 18px; }
.field.full { grid-column: 1 / -1; }
label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }

input[type=text], input[type=email], input[type=password], input[type=tel] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5cd;
    border-radius: 10px;
    font: inherit;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
input:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 48px; }
.pw-toggle {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    width: 38px; height: 38px; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); cursor: pointer;
}
.pw-toggle:hover { color: var(--ink); background: var(--paper); }

.btn-register {
    width: 100%; padding: 13px; margin-top: 4px;
    background: var(--leaf); color: #fff;
    border: 0; border-radius: 10px;
    font: inherit; font-weight: 600; font-size: 16px;
    cursor: pointer; transition: background .15s;
}
.btn-register:hover { background: var(--forest); }

.foot { text-align: center; margin-top: 22px; color: var(--muted); font-size: 14.5px; }
.foot a { color: var(--forest); font-weight: 600; text-decoration: none; }
.foot a:hover { text-decoration: underline; }

/* ---------- Responsive ---------- */
@media (max-width: 860px) {
    body { grid-template-columns: 1fr; }
    .brand-panel { padding: 22px 24px; flex-direction: row; align-items: center; }
    .brand-copy, .brand-foot { display: none; }
    .form-panel { align-items: flex-start; padding-top: 40px; }
}

@media (max-width: 480px) {
    .row { grid-template-columns: 1fr; gap: 0; }
    .role-picker { grid-template-columns: 1fr 1fr; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<aside class="brand-panel">
    <div class="brand-mark"><i class="fa-solid fa-seedling"></i> Farm to Market</div>

    <div class="brand-copy">
        <h2>Join the Farm to Market community.</h2>
        <p>Register as a Farmer to list your products, or as a Buyer to order fresh produce directly.</p>
    </div>

    <div class="brand-foot">&copy; <?= date('Y') ?> Farm to Market System</div>
</aside>

<main class="form-panel">
    <div class="form-wrap">

        <h1>Create account</h1>
        <p class="sub">Sign up as a Farmer or Buyer.</p>

        <!-- ALERT MESSAGE -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['type']); ?>" role="alert">
                <i class="fa-solid <?= $_SESSION['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($_SESSION['message']); ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- REGISTER FORM -->
        <form method="POST" id="registerForm">

            <!-- ── Step 1: Choose a role ── -->
            <p class="section-label">I am a&hellip;</p>
            <div class="role-picker" role="group" aria-label="Select your role">

                <div class="role-card">
                    <input type="radio" id="roleFarmer" name="role" value="farmer" required>
                    <label for="roleFarmer">
                        <i class="fa-solid fa-tractor"></i>
                        Farmer
                        <span class="role-desc">List &amp; sell produce</span>
                    </label>
                </div>

                <div class="role-card">
                    <input type="radio" id="roleBuyer" name="role" value="buyer">
                    <label for="roleBuyer">
                        <i class="fa-solid fa-basket-shopping"></i>
                        Buyer
                        <span class="role-desc">Browse &amp; order produce</span>
                    </label>
                </div>

            </div>

            <!-- ── Step 2: Personal details ── -->
            <p class="section-label">Personal details</p>
            <div class="row">
                <div class="field">
                    <label for="firstName">First name</label>
                    <input type="text" id="firstName" name="firstName" autocomplete="given-name" autofocus required>
                </div>

                <div class="field">
                    <label for="middleName">Middle name</label>
                    <input type="text" id="middleName" name="middleName" autocomplete="additional-name" required>
                </div>

                <div class="field full">
                    <label for="lastName">Last name</label>
                    <input type="text" id="lastName" name="lastName" autocomplete="family-name" required>
                </div>

                <div class="field">
                    <label for="contact">Contact number</label>
                    <input type="tel" id="contact" name="contact" autocomplete="tel"
                        inputmode="numeric" maxlength="11" pattern="[0-9]{11}"
                        title="Enter an 11-digit number, numbers only" required>
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" autocomplete="address-level2" required>
                </div>
            </div>

            <!-- ── Step 3: Credentials ── -->
            <p class="section-label">Account credentials</p>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="create" class="btn-register" id="submitBtn">Create account</button>

            <p class="foot">Already have an account? <a href="login.php">Sign in</a></p>
        </form>

    </div>
</main>

<script>
// Password toggle
const pw     = document.getElementById('password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggle.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

document.getElementById('contact').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '');
});
</script>

</body>
</html>