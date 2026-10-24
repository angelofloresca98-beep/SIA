<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require './database/connection.php';

$error = "";


// LOGIN PROCESS

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check empty fields
    if (empty($email) || empty($password)) {

        $error = "Please fill all fields!";

    } else {

        try {

            // Find user by email
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {

                // Check account status
                if ($user['status'] !== 'active') {

                    $error = "Your account is inactive.";

                } else {

                    $_SESSION['user_id'] = $user['user_id'];

                    $_SESSION['user'] =
                        $user['firstName'] . ' ' . $user['lastName'];

                    $_SESSION['role'] = $user['role'];

                    $_SESSION['email'] = $user['email'];

                    // REDIRECT BY ROLE

                    if ($user['role'] === 'buyer') {

                        header("Location: buyers/buyer.php");
                        exit();

                    } elseif ($user['role'] === 'farmer') {

                        header("Location: seller/farmer.php");
                        exit();

                    } elseif ($user['role'] === 'admin') {

                        header("Location: admin/admin.php");
                        exit();

                    } else {

                        $error = "Invalid user role.";

                    }
                }

            } else {

                $error = "Invalid email or password!";

            }

        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();

        }
    }
}

// (display only) keep the typed email after a failed attempt
$emailValue = htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8');
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Sign in · Farm to Market</title>
 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://accounts.google.com/gsi/client" async defer></script>
<style>
:root {
    --ink: #1c2a24;
    --muted: #6b7a72;
    --paper: #f4f6f2;
    --forest: #0d3823;
    --leaf: #1d6e3f;
    --harvest: #d99b26;
    --danger: #b3261e;
    --danger-soft: #fbe3e0;
}
 
* { box-sizing: border-box; margin: 0; padding: 0; }
 
body {
    font-family: 'Figtree', system-ui, sans-serif;
    color: var(--ink);
    background: var(--paper);
    min-height: 100vh;
    min-height: 100dvh;
    display: grid;
    grid-template-columns: minmax(320px, 5.5fr) 6.5fr;
}
 
a:focus-visible, button:focus-visible, input:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}
 
/* ---------- Brand panel with Gradient Overlay & Background Image ---------- */
.brand-panel {
    position: relative;
    color: #fff;
    padding: clamp(28px, 4.5vw, 56px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    background: 
        linear-gradient(
            135deg, 
            rgba(13, 56, 35, 0.88) 0%, 
            rgba(29, 110, 63, 0.75) 50%, 
            rgba(217, 155, 38, 0.65) 100%
        ),
        url('farmpic1.jpg') center center / cover no-repeat;
}

.brand-panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top left, rgba(0,0,0,0.1), rgba(0,0,0,0.4));
    pointer-events: none;
}

.brand-mark, .brand-copy, .brand-foot {
    position: relative;
    z-index: 2;
}

.brand-mark { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    font-weight: 700; 
    font-size: 22px; 
    color: #fff; 
    text-decoration: none; 
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.brand-mark:hover { color: #fff; opacity: .9; }
.brand-mark i {
    width: 44px; height: 44px; border-radius: 12px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(8px);
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid rgba(255,255,255,0.3);
}

.brand-copy h2 {
    font-size: clamp(28px, 3.5vw, 42px);
    line-height: 1.15; 
    letter-spacing: -0.02em;
    max-width: 14ch; 
    margin-bottom: 16px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.brand-copy p { 
    color: rgba(255,255,255,0.92); 
    max-width: 38ch; 
    line-height: 1.6; 
    font-size: 16px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.2);
}

.brand-foot { 
    color: rgba(255,255,255,0.75); 
    font-size: 13.5px; 
}
 
/* ---------- Form panel ---------- */
.form-panel { 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    padding: 32px 20px; 
    background: #ffffff;
}
.form-wrap { width: 100%; max-width: 400px; }

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    margin-bottom: 24px;
    padding: 8px 14px;
    border-radius: 8px;
    background: var(--paper);
    transition: all .15s ease;
    width: fit-content;
}
.back-link:hover {
    color: var(--forest);
    background: #e2e8df;
    transform: translateX(-2px);
}
 
h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; color: var(--ink); }
.sub { color: var(--muted); margin-bottom: 26px; }
 
.alert {
    display: flex; gap: 10px; align-items: flex-start;
    background: var(--danger-soft); color: var(--danger);
    border-radius: 10px; padding: 12px 14px; margin-bottom: 18px;
    font-size: 14.5px; font-weight: 500;
}
.alert i { margin-top: 2px; }
 
.field { margin-bottom: 18px; }
label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }
 
input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5cd;
    border-radius: 10px;
    font: inherit;
    font-size: 16px;            /* stops iOS zoom on focus */
    min-height: 46px;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
input:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(29,110,63,0.18); }
 
.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 52px; }
.pw-toggle {
    position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
    width: 44px; height: 44px; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); cursor: pointer;
}
.pw-toggle:hover { color: var(--ink); background: var(--paper); }
 
/* ---------- Animated Sign In Button ---------- */
.btn-login {
    width: 100%; padding: 13px; min-height: 48px;
    background: var(--forest); color: #fff;
    border: 0; border-radius: 10px;
    font: inherit; font-weight: 600; font-size: 16px;
    cursor: pointer; 
    /* Smooth spring animation transition */
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 10px rgba(13, 56, 35, 0.15);
    user-select: none;
}

/* Hover Animation: Lifts up slightly, brightens color, and adds a lush glow */
.btn-login:hover { 
    background: var(--leaf); 
    transform: translateY(-3px) scale(1.01);
    box-shadow: 0 8px 22px rgba(29, 110, 63, 0.35);
}

/* Click / Active Animation: Smooth press-down tactile effect */
.btn-login:active {
    transform: translateY(1px) scale(0.98);
    box-shadow: 0 2px 6px rgba(13, 56, 35, 0.2);
    transition-duration: 0.1s;
}
 
.foot { text-align: center; margin-top: 22px; color: var(--muted); font-size: 14.5px; }
.foot a { color: var(--leaf); font-weight: 600; text-decoration: none; }
.foot a:hover { text-decoration: underline; }
 
.divider { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 13px; margin: 24px 0 18px; }
.divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: #dfe5dd; }
 
/* ---------- Animated Role Picker Buttons ---------- */
.role-label { 
    font-weight: 600; 
    font-size: 14px; 
    margin-bottom: 12px; 
    color: var(--ink);
}
.role-picker {
    position: relative;
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 12px;
    margin-bottom: 20px;
}
.role-picker input { position: absolute; opacity: 0; pointer-events: none; }

.role-picker label {
    margin: 0; 
    padding: 18px 12px; 
    text-align: center; 
    cursor: pointer;
    border-radius: 14px; 
    font-weight: 600; 
    color: var(--muted);
    display: flex; 
    flex-direction: column;
    align-items: center; 
    justify-content: center; 
    gap: 8px;
    background: #f8faf7;
    border: 2px solid #e2e8e3;
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    user-select: none;
}

.role-picker label i {
    font-size: 24px;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s ease;
}

.role-picker label span {
    font-size: 15px;
}

/* Hover Animation */
.role-picker label:hover {
    background: #ffffff;
    border-color: var(--leaf);
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 20px rgba(29, 110, 63, 0.12);
    color: var(--forest);
}

.role-picker label:hover i {
    transform: translateY(-2px) scale(1.1);
    color: var(--leaf);
}

/* Click / Active Press Animation */
.role-picker label:active {
    transform: translateY(1px) scale(0.97);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transition-duration: 0.1s;
}

/* Active Selected State */
.role-picker input:checked + label { 
    background: linear-gradient(135deg, #f0f7f2 0%, #e6f4ea 100%); 
    color: var(--forest); 
    border-color: var(--leaf);
    box-shadow: 0 6px 18px rgba(29, 110, 63, 0.2);
    transform: translateY(-2px);
}

.role-picker input:checked + label i {
    transform: scale(1.15);
    color: var(--leaf);
}

.role-picker input:focus-visible + label { 
    outline: 3px solid var(--harvest); 
    outline-offset: 2px; 
}
 
/* ---------- Google Sign-In Container Card ---------- */
.google-wrap { 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    width: 100%; 
    min-height: 54px; 
    padding: 8px;
    background: #f8faf7;
    border: 2px solid #e2e8e3;
    border-radius: 14px;
    overflow: hidden; 
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.google-wrap:hover {
    background: #ffffff;
    border-color: var(--leaf);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(29, 110, 63, 0.12);
}

.google-error { color: var(--danger); font-size: 14px; font-weight: 500; text-align: center; margin-top: 12px; min-height: 20px; }
 
/* ---------- Responsive ---------- */
 
@media (min-width: 1600px) {
    body { grid-template-columns: 1fr 1fr; }
    .form-wrap { max-width: 440px; }
}
 
@media (max-width: 860px) {
    body { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
    .brand-panel { padding: 24px 24px; flex-direction: row; align-items: center; min-height: 120px; }
    .brand-copy, .brand-foot { display: none; }
    .form-panel { align-items: flex-start; padding: 36px 24px 48px; }
    .form-wrap { max-width: 440px; }
}
 
@media (max-width: 480px) {
    .brand-panel { padding: 16px; }
    .brand-mark { font-size: 18px; }
    .brand-mark i { width: 36px; height: 36px; }
    .form-panel { padding: 28px 16px 40px; }
    h1 { font-size: 24px; }
    .sub { margin-bottom: 20px; }
    .role-picker label { padding: 14px 8px; }
    .role-picker label i { font-size: 20px; }
}
 
@media (max-width: 360px) {
    .form-panel { padding: 24px 12px 32px; }
}
 
@media (max-height: 520px) and (orientation: landscape) {
    .form-panel { align-items: flex-start; padding-top: 20px; }
    .brand-panel { padding-top: 10px; padding-bottom: 10px; }
}
 
@supports (padding: max(0px)) {
    .form-panel  { padding-left: max(16px, env(safe-area-inset-left)); padding-right: max(16px, env(safe-area-inset-right)); }
    .brand-panel { padding-top: max(14px, env(safe-area-inset-top)); }
}
 
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>
 
<body>
 
<aside class="brand-panel">
    <a href="index.php" class="brand-mark" title="Back to AgriBayan Home"><i class="fa-solid fa-seedling"></i> Farm to Market</a>
 
    <div class="brand-copy">
        <h2>Fresh from the field to your table.</h2>
        <p>Farmers list what they grow. Buyers order it directly. Sign in to pick up where you left off.</p>
    </div>
 
    <div class="brand-foot">&copy; <?= date('Y') ?> Farm to Market System</div>
</aside>
 
<main class="form-panel">
    <div class="form-wrap">
 
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>

        <h1>Sign in</h1>
        <p class="sub">Use the email and password for your account.</p>
 
        <?php if ($error != ""): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
 
        <form method="POST">
 
            <div class="field">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?= $emailValue ?>" autofocus>
            </div>
 
            <div class="field">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password">
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>
 
            <button type="submit" name="login" class="btn-login">Sign in</button>
 
            <p class="foot">New here? <a href="register.php">Create an account</a></p>
        </form>
 
        <div class="divider">or</div>
 
        <!-- Choose which kind of account the Google sign-in is for (Unselected by default) -->
        <p class="role-label" id="roleLabel">Continue with Google as</p>
        <div class="role-picker" role="radiogroup" aria-labelledby="roleLabel">
            <input type="radio" name="google_role" id="roleBuyer" value="buyer">
            <label for="roleBuyer">
                <i class="fa-solid fa-basket-shopping"></i>
                <span>Buyer</span>
            </label>
 
            <input type="radio" name="google_role" id="roleFarmer" value="farmer">
            <label for="roleFarmer">
                <i class="fa-solid fa-tractor"></i>
                <span>Farmer</span>
            </label>
        </div>
 
        <div class="google-wrap">
            <div id="googleBtn"></div>
        </div>
        <p class="google-error" id="googleError" role="alert"></p>
 
    </div>
</main>
 
<script>
const GOOGLE_CLIENT_ID = '867338478390-0d6a06kjso3dect629o0mk7mq681jr9c.apps.googleusercontent.com';
 
// Called by Google after the user picks an account.
async function handleCredentialResponse(response) {
    const errBox = document.getElementById('googleError');
    errBox.textContent = '';
 
    // Ensure the user selected a role before processing Google Sign-In
    const roleInput = document.querySelector('input[name="google_role"]:checked');
    if (!roleInput) {
        errBox.textContent = 'Please select whether you are a Buyer or a Farmer first.';
        return;
    }
    const role = roleInput.value; // buyer | farmer
 
    try {
        const res = await fetch('google_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ credential: response.credential, role: role })
        });
        const data = await res.json();
 
        if (data.ok) {
            window.location.href = data.redirect;   // buyer or farmer dashboard
        } else {
            errBox.textContent = data.error || 'Google sign-in failed. Please try again.';
        }
    } catch (e) {
        errBox.textContent = 'Could not reach the server. Check your connection and try again.';
    }
}
 
// Render the Google button sized to its container (200-300px)
function renderGoogleButton() {
    if (!window.google || !google.accounts) return;
    const wrap = document.querySelector('.google-wrap');
    const width = Math.min(320, Math.max(200, Math.floor(wrap.clientWidth - 16)));
    const btn = document.getElementById('googleBtn');
    btn.innerHTML = '';
    google.accounts.id.renderButton(btn, {
        type: 'standard', size: 'large', theme: 'outline',
        text: 'continue_with', shape: 'rectangular', width: width
    });
}
 
window.addEventListener('load', function () {
    if (!window.google || !google.accounts) {
        document.getElementById('googleError').textContent =
            'Google sign-in could not load. Check your connection and refresh.';
        return;
    }
    google.accounts.id.initialize({
        client_id: GOOGLE_CLIENT_ID,
        callback: handleCredentialResponse,
        auto_select: false
    });
    renderGoogleButton();
});
 
// Re-fit the Google button on rotate / resize
let resizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(renderGoogleButton, 200);
});
 
// Show / hide password
const pw = document.getElementById('password');
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