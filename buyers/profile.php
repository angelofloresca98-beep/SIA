<?php
session_start();
require '../database/connection.php';

/* Any logged-in farmer or buyer can open this page */
if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if (!in_array($_SESSION['role'], ['farmer', 'buyer'], true)) { header("Location: ../login.php"); exit(); }

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

/* Where the "Back" link goes. Change 'buyer.php' to your real buyer dashboard file name. */
$dashboard = $role === 'farmer' ? 'farmer.php' : 'buyer.php';

/* CSRF token */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errors = [];

/* ---------- Handle update (ONLY address + contact_number) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    }

    $address = trim((string)($_POST['address'] ?? ''));
    $contact = preg_replace('/[\s\-]/', '', trim((string)($_POST['contact_number'] ?? '')));

    if ($address === '' || mb_strlen($address) < 5 || mb_strlen($address) > 255) {
        $errors[] = 'Location must be between 5 and 255 characters.';
    }
    // Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX
    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact)) {
        $errors[] = 'Enter a valid mobile number (e.g. 09171234567 or +639171234567).';
    }

    if (!$errors) {
        // Only these two columns can ever be changed from this page
        $s = $conn->prepare("UPDATE users SET address = ?, contact_number = ? WHERE user_id = ?");
        $s->execute([$address, $contact, $user_id]);
        $_SESSION['flash'] = 'Profile updated successfully.';
        header("Location: profile.php");
        exit();
    }
}

/* ---------- Load current info ---------- */
$s = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$s->execute([$user_id]);
$u = $s->fetch(PDO::FETCH_ASSOC);
if (!$u) { header("Location: ../logout.php"); exit(); }

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

/* Keep what the user typed if validation failed */
$valAddress = $errors ? ($_POST['address'] ?? '')        : ($u['address'] ?? '');
$valContact = $errors ? ($_POST['contact_number'] ?? '') : ($u['contact_number'] ?? '');

$fullName = trim(($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? ''));
$initials = strtoupper(mb_substr($u['firstName'] ?? '', 0, 1) . mb_substr($u['lastName'] ?? '', 0, 1));
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1f4d3a">
<title>My Profile · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
    --ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#fff;
    --line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;
    --harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;
    --danger-soft:#fbe3e0;--radius:12px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;overflow-x:hidden;}
a:focus-visible,button:focus-visible,input:focus-visible,textarea:focus-visible{outline:3px solid var(--harvest);outline-offset:2px;}

/* Same navigation layout as buyer.php */
.navbar{position:sticky;top:0;width:100%;background:var(--forest);padding:0 32px;display:flex;align-items:center;z-index:1000;height:70px;}
.brand{color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin-right:40px;text-decoration:none;white-space:nowrap;}
.nav{display:flex;align-items:center;gap:8px;flex:1;list-style:none;}
.nav a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.78);text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:500;transition:background .15s;white-space:nowrap;}
.nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav a.active{background:rgba(255,255,255,.14);color:#fff;}
.nav a i{width:18px;text-align:center;}
.nav li.logout{margin-left:auto;}

/* Page layout follows buyer.php */
.main{margin:0 auto;padding:28px 32px 60px;max-width:1300px;width:100%;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-header h1{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-bottom:2px;}
.page-header p{color:var(--muted);}
.profile-pill{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:6px 16px 6px 6px;font-weight:600;}
.profile-avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;background:var(--harvest-soft);color:#7a4f0e;}

.profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;}
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;}
.section h2{font-size:19px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:9px;}
.section h2 i{color:var(--harvest);}
.who{display:flex;align-items:center;gap:16px;margin-bottom:22px;}
.avatar{width:64px;height:64px;border-radius:50%;background:var(--leaf-soft);color:var(--forest);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;flex-shrink:0;}
.who h3{font-size:20px;overflow-wrap:anywhere;}
.role-tag{display:inline-block;margin-top:4px;font-size:12.5px;font-weight:600;padding:3px 10px;border-radius:99px;background:var(--harvest-soft);color:#7a4f0e;text-transform:capitalize;}
.dl{display:grid;grid-template-columns:140px 1fr;gap:10px 14px;font-size:14.5px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;overflow-wrap:anywhere;}
.lock{color:var(--muted);font-size:12.5px;margin-top:18px;padding-top:14px;border-top:1px solid var(--line);}

.field{margin-bottom:17px;}
.field label{display:block;font-weight:600;font-size:14px;margin:0 0 7px;}
input[type=tel],textarea{width:100%;border:1px solid #d5ddd3;border-radius:10px;padding:11px 12px;font:inherit;font-size:15px;background:#fff;color:var(--ink);}
textarea{height:110px;resize:vertical;}
input:focus,textarea:focus{border-color:var(--leaf);outline:2px solid rgba(47,125,79,.12);}
.hint{font-size:12.5px;color:var(--muted);margin-top:5px;}
.btn-sm{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:var(--leaf);color:#fff;text-decoration:none;padding:10px 17px;border-radius:8px;font:inherit;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:background .15s;}
.btn-sm:hover{background:var(--forest);}

.alert{border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:14.5px;}
.alert.ok{background:var(--leaf-soft);color:var(--forest);}
.alert.err{background:var(--danger-soft);color:var(--danger);}
.alert ul{margin-left:18px;}

/* Match buyer.php mobile navigation and responsive layout */
.nav-check{display:none;}
.nav-burger{display:none;cursor:pointer;}
@media(max-width:1000px){
    .navbar{padding:0 16px;overflow-x:auto;}
    .brand{margin-right:20px;}
    .nav{min-width:max-content;}
    .nav a span{display:none;}
}
@media(max-width:768px){
    .main{padding:18px 14px 40px;}
    .page-header{flex-direction:column;align-items:flex-start;}
    .profile-grid{grid-template-columns:1fr;}
    .section{padding:20px;}
}
@media(max-width:520px){
    .navbar{height:64px;}
    .brand{font-size:18px;}
    .dl{grid-template-columns:1fr;gap:3px;}
    .dl dd{margin-bottom:9px;}
    .btn-sm{width:100%;}
}
@media(prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
<link rel="stylesheet" href="theme.css">
<script>try{var t=localStorage.getItem("fm-theme");if(!t&&window.matchMedia("(prefers-color-scheme: dark)").matches)t="dark";document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");}catch(e){}</script>
</head>
<body>

<nav class="navbar">
    <input type="checkbox" id="nav-check" class="nav-check" aria-label="Toggle menu">
    <label for="nav-check" class="nav-burger" aria-hidden="true"><span></span><span></span><span></span></label>
    <a class="brand" href="<?= $e($dashboard) ?>"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="<?= $e($dashboard) ?>"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="profile.php" class="active"><i class="fa-solid fa-user"></i><span>My Profile</span></a></li>
        <li><a href="marketplace.php"><i class="fa-solid fa-store"></i><span>Marketplace</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Requests</span></a></li>
        <li><a href="buyer_post.php"><i class="fa-solid fa-plus"></i><span>Post Request</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box-open"></i><span>My Orders</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>

<main class="main">
    <div class="page-header">
        <div>
            <h1>My Profile</h1>
            <p>View your account details and update your location and contact number.</p>
        </div>
        <div class="profile-pill">
            <span class="profile-avatar"><?= $e($initials ?: '?') ?></span>
            <?= $e(ucfirst($role)) ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert ok" id="flash"><i class="fa-solid fa-circle-check"></i> <?= $e($flash) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert err" role="alert">
            <ul><?php foreach ($errors as $er): ?><li><?= $e($er) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <!-- Read-only account information -->
        <section class="section">
            <h2><i class="fa-solid fa-user"></i> Account information</h2>
            <div class="who">
                <div class="avatar"><?= $e($initials ?: '?') ?></div>
                <div>
                    <h3><?= $e($fullName) ?></h3>
                    <span class="role-tag"><?= $e($u['role'] ?? $role) ?></span>
                </div>
            </div>

            <dl class="dl">
                <dt>First name</dt><dd><?= $e($u['firstName'] ?? '—') ?></dd>
                <dt>Last name</dt><dd><?= $e($u['lastName'] ?? '—') ?></dd>
                <?php if (!empty($u['email'])): ?>
                <dt>Email</dt><dd><?= $e($u['email']) ?></dd>
                <?php endif; ?>
                <dt>Location</dt><dd><?= $e($u['address'] ?: '—') ?></dd>
                <dt>Contact number</dt><dd><?= $e($u['contact_number'] ?: '—') ?></dd>
            </dl>
            <p class="lock"><i class="fa-solid fa-lock"></i> Name, email and role can't be changed here.</p>
        </section>

        <!-- Editable: address + contact number only -->
        <section class="section">
            <h2><i class="fa-solid fa-pen-to-square"></i> Update details</h2>
            <form method="POST" action="profile.php" novalidate>
                <input type="hidden" name="csrf" value="<?= $e($_SESSION['csrf']) ?>">

                <div class="field">
                    <label for="address">Location</label>
                    <textarea id="address" name="address" maxlength="255" required><?= $e($valAddress) ?></textarea>
                    <div class="hint">Barangay, city / municipality, province.</div>
                </div>

                <div class="field">
                    <label for="contact_number">Contact number</label>
                    <input type="tel" id="contact_number" name="contact_number" maxlength="16"
                           inputmode="tel" placeholder="09171234567" value="<?= $e($valContact) ?>" required>
                    <div class="hint">Mobile number, e.g. 09171234567 or +639171234567.</div>
                </div>

                <button type="submit" class="btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
            </form>
        </section>
    </div>
</main>

<script>
const flash = document.getElementById('flash');
if (flash) setTimeout(() => flash.style.display = 'none', 5000);
</script>
<script src="theme.js"></script>
</body>
</html>
