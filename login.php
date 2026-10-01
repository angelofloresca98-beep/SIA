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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in · Farm to Market</title>

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

a:focus-visible, button:focus-visible, input:focus-visible {
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
.brand-copy p { color: rgba(255,255,255,.75); max-width: 36ch; line-height: 1.55; }
.brand-foot { color: rgba(255,255,255,.55); font-size: 13px; }

/* ---------- Form panel ---------- */
.form-panel { display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
.form-wrap { width: 100%; max-width: 380px; }

h1 { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; }
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

.btn-login {
    width: 100%; padding: 13px;
    background: var(--leaf); color: #fff;
    border: 0; border-radius: 10px;
    font: inherit; font-weight: 600; font-size: 16px;
    cursor: pointer; transition: background .15s;
}
.btn-login:hover { background: var(--forest); }

.foot { text-align: center; margin-top: 22px; color: var(--muted); font-size: 14.5px; }
.foot a { color: var(--forest); font-weight: 600; text-decoration: none; }
.foot a:hover { text-decoration: underline; }

.divider { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 13px; margin: 24px 0 18px; }
.divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: #dfe5dd; }

/* ---------- Responsive ---------- */
@media (max-width: 860px) {
    body { grid-template-columns: 1fr; }
    .brand-panel { padding: 22px 24px; flex-direction: row; align-items: center; }
    .brand-copy, .brand-foot { display: none; }
    .form-panel { align-items: flex-start; padding-top: 40px; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<aside class="brand-panel">
    <div class="brand-mark"><i class="fa-solid fa-seedling"></i> Farm to Market</div>

    <div class="brand-copy">
        <h2>Fresh from the field to your table.</h2>
        <p>Farmers list what they grow. Buyers order it directly. Sign in to pick up where you left off.</p>
    </div>

    <div class="brand-foot">&copy; <?= date('Y') ?> Farm to Market System</div>
</aside>

<main class="form-panel">
    <div class="form-wrap">

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

        <!-- g_id_onload contains Google Identity Services settings -->
        <div
          id="g_id_onload"
          data-auto_prompt="false"
          data-callback="handleCredentialResponse"
          data-client_id="867338478390-0d6a06kjso3dect629o0mk7mq681jr9c.apps.googleusercontent.com"
        ></div>
        <!-- g_id_signin places the button on a page and supports customization -->
        <div class="g_id_signin"></div>

    </div>
</main>

<script>
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