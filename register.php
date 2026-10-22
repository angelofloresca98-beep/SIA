<?php
session_start();
require './database/connection.php';

/* ---------- Helpers ---------- */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/* ---------- Handle submission ---------- */
if (isset($_POST['create'])) {
    $errors = [];

    $firstName  = trim($_POST['firstName']  ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName   = trim($_POST['lastName']   ?? '');
    $contact    = trim($_POST['contact']    ?? '');
    $address    = trim($_POST['location']   ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password']        ?? '';   // never trim passwords
    $confirm    = $_POST['confirmPassword'] ?? '';
    $role       = trim($_POST['role']       ?? '');

    // CSRF
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors['general'] = 'Your session expired. Please try again.';
    }

    // Role
    if (!in_array($role, ['farmer', 'buyer'], true)) {
        $errors['role'] = 'Please select a role (Farmer or Buyer).';
    }

    // Names: letters, spaces, . ' - ; 2 to 50 chars
    $nameRegex = "/^\p{L}[\p{L}\s.'-]{1,49}$/u";
    foreach ([
        'firstName'  => [$firstName,  'First name'],
        'middleName' => [$middleName, 'Middle name'],
        'lastName'   => [$lastName,   'Last name'],
    ] as $key => [$val, $label]) {
        if ($val === '') {
            $errors[$key] = "$label is required.";
        } elseif (!preg_match($nameRegex, $val)) {
            $errors[$key] = "$label must be 2-50 letters (spaces, . ' - allowed).";
        }
    }

    // Contact
    if ($contact === '') {
        $errors['contact'] = 'Contact number is required.';
    } elseif (!preg_match('/^(09[0-9]{9}|639[0-9]{9})$/', $contact)) {
        $errors['contact'] = 'Must start with 09 (11 digits) or 639 (12 digits), numbers only.';
    }

    // Location
    if ($address === '') {
        $errors['location'] = 'Location is required.';
    } elseif (mb_strlen($address) < 3 || mb_strlen($address) > 100) {
        $errors['location'] = 'Location must be 3-100 characters.';
    }

    // Email
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // Password
    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 72) {
        $errors['password'] = 'Password must be 72 characters or fewer.';
    } elseif (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Use at least one uppercase letter, one lowercase letter, and one number.';
    }

    // Confirm password
    if ($confirm === '') {
        $errors['confirmPassword'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    // Agreement
    if (!isset($_POST['agree'])) {
        $errors['agree'] = 'You must agree to the policies to register.';
    }

    // Database checks only if everything else is valid
    if (!$errors) {
        try {
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $errors['email'] = 'That email is already registered.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare(
                    "INSERT INTO users (firstName, middleName, lastName, contact_number, address, email, password, role, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')"
                );
                $stmt->execute([$firstName, $middleName, $lastName, $contact, $address, $email, $hashed, $role]);
            }
        } catch (PDOException $ex) {
            if ($ex->getCode() === '23000') {            // duplicate key (race condition)
                $errors['email'] = 'That email is already registered.';
            } else {
                error_log('Register error: ' . $ex->getMessage());
                $errors['general'] = 'Something went wrong. Please try again later.';
            }
        }
    }

    if ($errors) {
        $_SESSION['errors'] = $errors;
        // Keep what they typed (never passwords)
        $_SESSION['old'] = compact('firstName', 'middleName', 'lastName', 'contact', 'email', 'role') + ['location' => $address];
        $_SESSION['message'] = $errors['general'] ?? 'Please fix the highlighted fields.';
        $_SESSION['type']    = 'danger';
    } else {
        unset($_SESSION['old'], $_SESSION['errors']);
        $_SESSION['csrf']    = bin2hex(random_bytes(32));
        $_SESSION['message'] = 'Account created! You can now sign in.';
        $_SESSION['type']    = 'success';
    }

    header('Location: register.php');
    exit;
}

/* ---------- Pull flash data for display ---------- */
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old']    ?? [];
unset($_SESSION['errors'], $_SESSION['old']);

function old($k)   { global $old;    return e($old[$k] ?? ''); }
function err($k)   { global $errors; return e($errors[$k] ?? ''); }
function inv($k)   { global $errors; return isset($errors[$k]) ? ' is-invalid' : ''; }
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
.role-picker { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 6px; }
.role-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
.role-card { position: relative; }
.role-card label {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 18px 10px;
    border: 2px solid #cbd5cd; border-radius: 12px; background: #fff;
    cursor: pointer; font-weight: 600; font-size: 14px; text-align: center;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.role-card label i { font-size: 24px; color: var(--muted); transition: color .15s; }
.role-card label .role-desc { font-weight: 400; font-size: 12px; color: var(--muted); }
.role-card input[type="radio"]:checked + label {
    border-color: var(--leaf); background: var(--ok-soft);
    box-shadow: 0 0 0 3px rgba(47,125,79,.15);
}
.role-card input[type="radio"]:checked + label i { color: var(--leaf); }
.role-card input[type="radio"]:focus-visible + label { outline: 3px solid var(--harvest); outline-offset: 2px; }
.role-card label:hover { border-color: var(--leaf); }
.role-picker.is-invalid .role-card label { border-color: var(--danger); }
#roleBlock { margin-bottom: 22px; }

/* ---------- Form panel ---------- */
.form-panel { display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
.form-wrap  { width: 100%; max-width: 440px; }

h1  { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; }
.sub { color: var(--muted); margin-bottom: 26px; }

.section-label {
    font-size: 12px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted); margin-bottom: 10px;
}

.alert {
    display: flex; gap: 10px; align-items: flex-start;
    border-radius: 10px; padding: 12px 14px; margin-bottom: 18px;
    font-size: 14.5px; font-weight: 500;
}
.alert i { margin-top: 2px; }
.alert-danger  { background: var(--danger-soft); color: var(--danger); }
.alert-success { background: var(--ok-soft); color: var(--forest); }

.row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
.field { margin-bottom: 18px; }
.field.full { grid-column: 1 / -1; }
label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }

input[type=text], input[type=email], input[type=password], input[type=tel] {
    width: 100%; padding: 12px 14px;
    border: 1px solid #cbd5cd; border-radius: 10px;
    font: inherit; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
input:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

/* Validation states */
input.is-invalid { border-color: var(--danger); background: #fffafa; }
input.is-invalid:focus { box-shadow: 0 0 0 3px rgba(179,38,30,.15); }
input.is-valid { border-color: var(--leaf); }
.err {
    display: block; min-height: 0; margin-top: 5px;
    font-size: 12.5px; font-weight: 500; color: var(--danger);
}
.err:empty { display: none; }

.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 48px; }
.pw-toggle {
    position: absolute; right: 6px; top: 6px;
    width: 38px; height: 38px; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); cursor: pointer;
}
.pw-toggle:hover { color: var(--ink); background: var(--paper); }

/* Password strength */
.strength { margin-top: 8px; }
.strength-bar { height: 5px; border-radius: 99px; background: #dde4de; overflow: hidden; }
.strength-bar span { display: block; height: 100%; width: 0; background: var(--danger); transition: width .2s, background .2s; }
.strength-text { font-size: 12px; color: var(--muted); margin-top: 4px; }
.pw-rules { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 2px 10px; margin-top: 8px; font-size: 12px; color: var(--muted); }
.pw-rules li i { width: 14px; margin-right: 4px; font-size: 10px; }
.pw-rules li.ok { color: var(--leaf); }

/* ---------- Agreement checkbox ---------- */
.field.agree { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 0 10px; }
.field.agree input[type="checkbox"] {
    flex-shrink: 0; width: 18px; height: 18px; margin-top: 2px;
    accent-color: var(--leaf); cursor: pointer;
}
.field.agree label {
    flex: 1; margin: 0; font-weight: 400; font-size: 13.5px;
    line-height: 1.5; color: var(--muted); cursor: pointer;
}
.field.agree .err { flex-basis: 100%; padding-left: 28px; }

.policy-link {
    background: none; border: 0; padding: 0;
    font: inherit; font-weight: 600; color: var(--forest); cursor: pointer;
}
.policy-link:hover { text-decoration: underline; }

/* ---------- Policy modals ---------- */
dialog.policy-modal {
    margin: auto; width: min(560px, calc(100% - 32px)); max-height: 82vh;
    border: 0; border-radius: 14px; padding: 0;
    color: var(--ink); background: #fff;
    box-shadow: 0 20px 60px rgba(0,0,0,.3); overflow: hidden;
}
dialog.policy-modal[open] { display: flex; flex-direction: column; }
dialog.policy-modal::backdrop { background: rgba(28,42,36,.55); }

.modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px; border-bottom: 1px solid #e3e9e4;
}
.modal-head h2 { font-size: 20px; letter-spacing: -0.01em; }
.modal-x {
    width: 36px; height: 36px; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); font-size: 18px; cursor: pointer;
}
.modal-x:hover { background: var(--paper); color: var(--ink); }

.modal-body { padding: 20px 22px; overflow-y: auto; font-size: 14.5px; line-height: 1.6; }
.modal-body h2 { font-size: 17px; margin-bottom: 10px; }
.modal-body h3 { font-size: 15px; margin: 16px 0 4px; }
.modal-body p  { color: #3d4b44; margin-bottom: 10px; }
.modal-body ul { padding-left: 22px; margin: 0 0 10px; color: #3d4b44; }
.modal-body li { margin-bottom: 4px; }
.modal-body a  { color: var(--forest); font-weight: 600; }

.modal-foot { padding: 14px 22px; border-top: 1px solid #e3e9e4; text-align: right; }
.modal-close-btn {
    padding: 10px 20px; border: 0; border-radius: 10px;
    background: var(--leaf); color: #fff; font: inherit; font-weight: 600; cursor: pointer;
}
.modal-close-btn:hover { background: var(--forest); }

.btn-register {
    width: 100%; padding: 13px; margin-top: 4px;
    background: var(--leaf); color: #fff; border: 0; border-radius: 10px;
    font: inherit; font-weight: 600; font-size: 16px;
    cursor: pointer; transition: background .15s;
}
.btn-register:hover { background: var(--forest); }
.btn-register:disabled { opacity: .7; cursor: wait; }

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
    .row { grid-template-columns: 1fr; }
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
            <div class="alert alert-<?= e($_SESSION['type']); ?>" role="alert">
                <i class="fa-solid <?= $_SESSION['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= e($_SESSION['message']); ?></span>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['type']); ?>
        <?php endif; ?>

        <!-- REGISTER FORM (novalidate: JS shows friendly inline errors; server validates again) -->
        <form method="POST" id="registerForm" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <!-- Role -->
            <p class="section-label">I am a&hellip;</p>
            <div id="roleBlock">
                <div class="role-picker<?= inv('role') ?>" id="rolePicker" role="radiogroup" aria-label="Select your role">
                    <div class="role-card">
                        <input type="radio" id="roleFarmer" name="role" value="farmer" <?= ($old['role'] ?? '') === 'farmer' ? 'checked' : '' ?>>
                        <label for="roleFarmer">
                            <i class="fa-solid fa-tractor"></i> Farmer
                            <span class="role-desc">List &amp; sell produce</span>
                        </label>
                    </div>
                    <div class="role-card">
                        <input type="radio" id="roleBuyer" name="role" value="buyer" <?= ($old['role'] ?? '') === 'buyer' ? 'checked' : '' ?>>
                        <label for="roleBuyer">
                            <i class="fa-solid fa-basket-shopping"></i> Buyer
                            <span class="role-desc">Browse &amp; order produce</span>
                        </label>
                    </div>
                </div>
                <small class="err" id="err-role"><?= err('role') ?></small>
            </div>

            <!-- Personal details -->
            <p class="section-label">Personal details</p>
            <div class="row">
                <div class="field">
                    <label for="firstName">First name</label>
                    <input type="text" id="firstName" name="firstName" class="<?= inv('firstName') ?>" value="<?= old('firstName') ?>" maxlength="50" autocomplete="given-name" autofocus>
                    <small class="err" id="err-firstName"><?= err('firstName') ?></small>
                </div>

                <div class="field">
                    <label for="middleName">Middle name</label>
                    <input type="text" id="middleName" name="middleName" class="<?= inv('middleName') ?>" value="<?= old('middleName') ?>" maxlength="50" autocomplete="additional-name">
                    <small class="err" id="err-middleName"><?= err('middleName') ?></small>
                </div>

                <div class="field full">
                    <label for="lastName">Last name</label>
                    <input type="text" id="lastName" name="lastName" class="<?= inv('lastName') ?>" value="<?= old('lastName') ?>" maxlength="50" autocomplete="family-name">
                    <small class="err" id="err-lastName"><?= err('lastName') ?></small>
                </div>

                <div class="field">
                    <label for="contact">Contact number</label>
                    <input type="tel" id="contact" name="contact" class="<?= inv('contact') ?>" value="<?= old('contact') ?>"
                           autocomplete="tel" inputmode="numeric" maxlength="12" placeholder="09XXXXXXXXX">
                    <small class="err" id="err-contact"><?= err('contact') ?></small>
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="<?= inv('location') ?>" value="<?= old('location') ?>" maxlength="100" autocomplete="address-level2">
                    <small class="err" id="err-location"><?= err('location') ?></small>
                </div>
            </div>

            <!-- Credentials -->
            <p class="section-label">Account credentials</p>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="<?= inv('email') ?>" value="<?= old('email') ?>" maxlength="100" autocomplete="email">
                <small class="err" id="err-email"><?= err('email') ?></small>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" class="<?= inv('password') ?>" maxlength="72" autocomplete="new-password" aria-describedby="pwRules">
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <small class="err" id="err-password"><?= err('password') ?></small>

                <div class="strength" aria-live="polite">
                    <div class="strength-bar"><span id="strengthFill"></span></div>
                    <div class="strength-text" id="strengthText">Enter a password</div>
                </div>
                <ul class="pw-rules" id="pwRules">
                    <li data-rule="len"><i class="fa-regular fa-circle"></i>8+ characters</li>
                    <li data-rule="upper"><i class="fa-regular fa-circle"></i>Uppercase letter</li>
                    <li data-rule="lower"><i class="fa-regular fa-circle"></i>Lowercase letter</li>
                    <li data-rule="num"><i class="fa-regular fa-circle"></i>Number</li>
                </ul>
            </div>

            <div class="field">
                <label for="confirmPassword">Confirm password</label>
                <div class="pw-wrap">
                    <input type="password" id="confirmPassword" name="confirmPassword" class="<?= inv('confirmPassword') ?>" maxlength="72" autocomplete="new-password">
                    <button type="button" class="pw-toggle" data-target="confirmPassword" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <small class="err" id="err-confirmPassword"><?= err('confirmPassword') ?></small>
            </div>

            <!-- Agreement -->
            <div class="field agree">
                <input type="checkbox" id="agree" name="agree" value="1">
                <label for="agree">
                    I have read and agree to the
                    <button type="button" class="policy-link" data-modal="modal-privacy">Privacy Policy</button>,
                    <button type="button" class="policy-link" data-modal="modal-terms">Terms of Service</button>,
                    <button type="button" class="policy-link" data-modal="modal-moderation">Moderation Policy</button>, and
                    <button type="button" class="policy-link" data-modal="modal-copyright">Copyright Policy</button>.
                </label>
                <small class="err" id="err-agree"><?= err('agree') ?></small>
            </div>

            <button type="submit" name="create" class="btn-register" id="submitBtn">Create account</button>

            <p class="foot">Already have an account? <a href="login.php">Sign in</a></p>
        </form>

    </div>
</main>

<!-- ============ POLICY MODALS ============ -->
<!--
  PASTE YOUR FOUR EXISTING <dialog> BLOCKS HERE, UNCHANGED:
  #modal-privacy, #modal-terms, #modal-moderation, #modal-copyright
-->

<script>
(function () {
    const form = document.getElementById('registerForm');

    /* ---------- Policy modals ---------- */
    document.querySelectorAll('.policy-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById(btn.dataset.modal).showModal();
        });
    });
    document.querySelectorAll('dialog.policy-modal').forEach(function (dlg) {
        dlg.querySelectorAll('[data-close]').forEach(function (b) {
            b.addEventListener('click', function () { dlg.close(); });
        });
        dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    });

    /* ---------- Show / hide password (both fields) ---------- */
    document.querySelectorAll('.pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            const show  = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    });

    /* ---------- Contact: digits only ---------- */
    document.getElementById('contact').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    /* ---------- Validation rules (mirror the PHP rules) ---------- */
    const nameRe = /^\p{L}[\p{L}\s.'-]{1,49}$/u;
    const val = id => document.getElementById(id).value;

    const nameRule = label => v => {
        v = v.trim();
        if (!v) return label + ' is required.';
        if (!nameRe.test(v)) return label + " must be 2-50 letters (spaces, . ' - allowed).";
        return '';
    };

    const rules = {
        firstName:  nameRule('First name'),
        middleName: nameRule('Middle name'),
        lastName:   nameRule('Last name'),
        contact: v => {
            if (!v) return 'Contact number is required.';
            if (!/^(09\d{9}|639\d{9})$/.test(v)) return 'Must start with 09 (11 digits) or 639 (12 digits), numbers only.';
            return '';
        },
        location: v => {
            v = v.trim();
            if (!v) return 'Location is required.';
            if (v.length < 3 || v.length > 100) return 'Location must be 3-100 characters.';
            return '';
        },
        email: v => {
            v = v.trim();
            if (!v) return 'Email is required.';
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v) || v.length > 100) return 'Please enter a valid email address.';
            return '';
        },
        password: v => {
            if (!v) return 'Password is required.';
            if (v.length < 8) return 'Password must be at least 8 characters.';
            if (!/[a-z]/.test(v) || !/[A-Z]/.test(v) || !/\d/.test(v))
                return 'Use at least one uppercase letter, one lowercase letter, and one number.';
            return '';
        },
        confirmPassword: v => {
            if (!v) return 'Please confirm your password.';
            if (v !== val('password')) return 'Passwords do not match.';
            return '';
        }
    };

    function setState(id, msg) {
        const input = document.getElementById(id);
        const out   = document.getElementById('err-' + id);
        out.textContent = msg;
        input.classList.toggle('is-invalid', !!msg);
        input.classList.toggle('is-valid', !msg && input.value !== '');
        input.setAttribute('aria-invalid', msg ? 'true' : 'false');
        return !msg;
    }

    function checkField(id) { return setState(id, rules[id](val(id))); }

    function checkRole() {
        const ok = !!form.querySelector('input[name="role"]:checked');
        document.getElementById('err-role').textContent = ok ? '' : 'Please select a role (Farmer or Buyer).';
        document.getElementById('rolePicker').classList.toggle('is-invalid', !ok);
        return ok;
    }

    function checkAgree() {
        const ok = document.getElementById('agree').checked;
        document.getElementById('err-agree').textContent = ok ? '' : 'You must agree to the policies to register.';
        return ok;
    }

    /* ---------- Live validation: on blur, then on every input once touched ---------- */
    Object.keys(rules).forEach(function (id) {
        const input = document.getElementById(id);
        input.addEventListener('blur', function () { input.dataset.touched = '1'; checkField(id); });
        input.addEventListener('input', function () {
            if (input.dataset.touched || input.classList.contains('is-invalid')) checkField(id);
        });
    });
    form.querySelectorAll('input[name="role"]').forEach(r => r.addEventListener('change', checkRole));
    document.getElementById('agree').addEventListener('change', checkAgree);

    /* ---------- Password strength + checklist ---------- */
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');
    const ruleEls = document.querySelectorAll('#pwRules li');

    function updateStrength() {
        const v = val('password');
        const checks = {
            len:   v.length >= 8,
            upper: /[A-Z]/.test(v),
            lower: /[a-z]/.test(v),
            num:   /\d/.test(v)
        };
        ruleEls.forEach(function (li) {
            const ok = checks[li.dataset.rule];
            li.classList.toggle('ok', ok);
            li.querySelector('i').className = ok ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle';
        });

        let score = Object.values(checks).filter(Boolean).length;
        if (v.length >= 12) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        if (!v) score = 0;

        const levels = [
            { w: 0,   c: '#b3261e', t: 'Enter a password' },
            { w: 20,  c: '#b3261e', t: 'Very weak' },
            { w: 40,  c: '#d9822b', t: 'Weak' },
            { w: 60,  c: '#b7791f', t: 'Fair' },
            { w: 80,  c: '#5a9e3d', t: 'Good' },
            { w: 90,  c: '#2f7d4f', t: 'Strong' },
            { w: 100, c: '#1f4d3a', t: 'Very strong' }
        ];
        const lv = levels[Math.min(score, levels.length - 1)];
        fill.style.width = lv.w + '%';
        fill.style.background = lv.c;
        text.textContent = lv.t;
    }
    document.getElementById('password').addEventListener('input', function () {
        updateStrength();
        // Re-check confirm when the main password changes
        if (val('confirmPassword') !== '') checkField('confirmPassword');
    });
    updateStrength();

    /* ---------- Submit: validate everything, focus first error ---------- */
    form.addEventListener('submit', function (ev) {
        const results = Object.keys(rules).map(id => [id, checkField(id)]);
        const roleOk  = checkRole();
        const agreeOk = checkAgree();

        const firstBad = results.find(r => !r[1]);
        if (firstBad || !roleOk || !agreeOk) {
            ev.preventDefault();
            if (!roleOk) document.getElementById('roleFarmer').focus();
            else if (firstBad) document.getElementById(firstBad[0]).focus();
            else document.getElementById('agree').focus();
            return;
        }

        // Prevent double submit. Name/value of the button is not sent when disabled,
        // so add a hidden field to keep the PHP isset($_POST['create']) check working.
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'create'; hidden.value = '1';
        form.appendChild(hidden);
        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.textContent = 'Creating account…';
    });

    // If the server sent errors, scroll to the first one
    const firstServerErr = form.querySelector('.is-invalid');
    if (firstServerErr) firstServerErr.focus();
})();
</script>

</body>
</html>