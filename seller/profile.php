
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

:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#ffffff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;--harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;--danger-soft:#fbe3e0;--radius:12px;--pad-x:clamp(14px,3vw,32px);}
*{box-sizing:border-box;margin:0;padding:0;}
html{-webkit-text-size-adjust:100%;text-size-adjust:100%;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;min-height:100dvh;overflow-x:hidden;}
a:focus-visible,button:focus-visible,select:focus-visible{outline:3px solid var(--harvest);outline-offset:2px;}

/* Navbar */
.navbar{position:sticky;top:0;width:100%;background:var(--forest);padding:0 var(--pad-x);display:flex;align-items:center;z-index:1000;height:70px;}
.brand{color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin-right:40px;text-decoration:none;white-space:nowrap;}
.nav{display:flex;align-items:center;gap:8px;flex:1;list-style:none;}
.nav a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.78);text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:500;transition:background .15s;white-space:nowrap;}
.nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav a.active{background:rgba(255,255,255,.14);color:#fff;}
.nav a i{width:18px;text-align:center;}
.nav li.logout{margin-left:auto;}

/* Hamburger (mobile/tablet only) */
.nav-toggle{display:none;width:44px;height:44px;align-items:center;justify-content:center;background:rgba(255,255,255,.12);color:#fff;border:none;border-radius:50%;font-size:18px;cursor:pointer;margin-left:auto;flex-shrink:0;}
.nav-toggle:hover{background:rgba(255,255,255,.2);}

/* Layout */
.main{margin:0 auto;padding:28px var(--pad-x) 60px;max-width:1300px;width:100%;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-header h1{font-size:clamp(22px,3.4vw,26px);font-weight:700;letter-spacing:-.02em;margin-bottom:2px;}
.page-header p{color:var(--muted);overflow-wrap:anywhere;}
.profile{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:6px 16px 6px 6px;font-weight:600;flex-shrink:0;}
.avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;background:var(--leaf-soft);color:var(--forest);}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:20px 22px;display:flex;align-items:center;gap:16px;min-width:0;}
.stat-card > div:last-child{min-width:0;}
.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.stat-icon.green{background:var(--leaf-soft);color:var(--forest);}
.stat-icon.gold{background:var(--harvest-soft);color:var(--harvest);}
.stat-icon.blue{background:#e8f0ff;color:#3b5bdb;}
.stat-icon.red{background:var(--danger-soft);color:var(--danger);}
.stat-number{font-size:clamp(22px,2.4vw,28px);font-weight:700;line-height:1.1;letter-spacing:-.02em;overflow-wrap:anywhere;}
.stat-label{font-size:14px;font-weight:600;margin-top:2px;}
.stat-sub{color:var(--muted);font-size:13px;}

/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--line);overflow-x:auto;scrollbar-width:none;}
.tabs::-webkit-scrollbar{display:none;}
.tab-btn{background:none;border:none;padding:10px 20px;font:inherit;font-weight:600;font-size:15px;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;white-space:nowrap;}
.tab-btn.active{color:var(--forest);border-bottom-color:var(--leaf);}
.tab-btn:hover{color:var(--ink);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* Section card */
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;}
.section-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.section-header h2{font-size:19px;font-weight:700;}
.btn-sm{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:var(--leaf);color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;font:inherit;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:background .15s;}
.btn-sm:hover{background:var(--forest);}
.btn-sm.outline{background:#fff;color:var(--forest);border:1px solid var(--line);}
.btn-sm.outline:hover{background:var(--paper);}

/* Cards grid (auto-fits: 3 cols on desktop, 2 on tablet, 1 on phones) */
.grid-3{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr));gap:18px;}
.grid-2{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,320px),1fr));gap:18px;}
.request-card{border:1px solid var(--line);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:12px;background:#fff;transition:border-color .15s;min-width:0;}
.request-card:hover{border-color:#b4c5bc;}
.request-card h3{font-size:17px;font-weight:700;color:var(--forest);overflow-wrap:anywhere;}
.tag{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;padding:3px 10px;border-radius:99px;}
.tag.buyer{background:var(--harvest-soft);color:#7a4f0e;}
.tag.sell{background:var(--leaf-soft);color:var(--forest);}
.variety-label{color:var(--muted);font-size:13.5px;}
.dl{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:14px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;overflow-wrap:anywhere;min-width:0;}
.dl dd.price-val{color:var(--forest);font-weight:700;}
.buyer-chip{display:flex;align-items:center;gap:8px;background:var(--paper);border-radius:8px;padding:8px 12px;font-weight:600;font-size:14px;min-width:0;overflow-wrap:anywhere;}
.buyer-chip small{display:block;font-weight:400;color:var(--muted);font-size:12px;}
.av-xs{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;background:var(--harvest-soft);color:#7a4f0e;}
.req-note{font-size:13.5px;color:#44524a;background:var(--paper);border-radius:8px;padding:8px 10px;overflow-wrap:anywhere;}
.req-date{font-size:13px;color:var(--muted);}

/* Message buyer (button + modal) */
.msg-btn{width:100%;margin-top:auto;padding:10px;background:var(--harvest);border-radius:9px;}
.msg-btn:hover{background:#8c5c0e;}
.sent-tag{font-size:13px;font-weight:600;color:var(--forest);}
#msg-modal{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:2000;padding:16px;}
#msg-modal.open{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:100%;max-width:440px;padding:22px;box-shadow:0 12px 32px rgba(0,0,0,.25);}
.modal-box h3{font-size:18px;margin-bottom:4px;color:var(--forest);}
.modal-box .sub{color:var(--muted);font-size:14px;margin-bottom:14px;overflow-wrap:anywhere;}
.modal-box textarea{width:100%;height:120px;resize:vertical;border:1px solid #d5ddd3;border-radius:10px;padding:10px;font:inherit;font-size:14px;}
.modal-foot{display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:10px;}
#msg-count{font-size:12.5px;color:var(--muted);}
#msg-status{font-size:13.5px;margin-top:8px;min-height:18px;}
#msg-status.ok{color:var(--forest);}
#msg-status.err{color:var(--danger);}

/* Orders table */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:14.5px;}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--line);}
th{font-weight:600;color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.04em;background:#fafbfa;white-space:nowrap;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8faf8;}
.sub-muted{color:var(--muted);font-size:13px;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:12.5px;font-weight:600;}
.status-pending{background:#fff8e6;color:#92610a;}
.status-confirmed{background:#e8f0ff;color:#3b5bdb;}
.status-completed{background:var(--leaf-soft);color:var(--forest);}
.status-cancelled{background:var(--danger-soft);color:var(--danger);}
select.status-select{font:inherit;font-size:13px;padding:4px 8px;border:1px solid var(--line);border-radius:6px;background:#fff;cursor:pointer;}
.status-form{display:inline-flex;gap:6px;align-items:center;}
.status-form .btn-sm{padding:5px 10px;font-size:13px;}

/* ---------- Chatbot ---------- */
#cb-btn { position: fixed; right: 22px; bottom: 22px; width: 56px; height: 56px; border-radius: 50%; border: 0; background: #183d00; color: #fff; font-size: 22px; cursor: pointer; box-shadow: 0 6px 18px rgba(0,0,0,.25); z-index: 1000; }
#cb-panel { position: fixed; right: 22px; bottom: 90px; width: 340px; max-width: calc(100vw - 32px); height: 460px; max-height: 70vh; background: #fff; border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,.2); display: none; flex-direction: column; overflow: hidden; z-index: 1000; }
#cb-panel.open { display: flex; }
#cb-head { background: var(--forest); color: #fff; padding: 14px 16px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
#cb-head button { background: none; border: 0; color: #fff; font-size: 20px; cursor: pointer; }
#cb-msgs { flex: 1; overflow-y: auto; padding: 14px; background: var(--paper); display: flex; flex-direction: column; gap: 10px; }
.cb-m { max-width: 85%; padding: 9px 12px; border-radius: 12px; font-size: 14px; line-height: 1.45; white-space: pre-wrap; overflow-wrap: anywhere; }
.cb-m.bot { background: #fff; border: 1px solid var(--line); align-self: flex-start; }
.cb-m.me  { background: var(--leaf); color: #fff; align-self: flex-end; }
#cb-form { display: flex; gap: 8px; padding: 10px; border-top: 1px solid var(--line); background: #fff; }
#cb-input { flex: 1; border: 1px solid #d5ddd3; border-radius: 10px; padding: 10px; font: inherit; font-size: 14px; }
#cb-send { background: var(--leaf); color: #fff; border: 0; border-radius: 10px; padding: 0 14px; cursor: pointer; font-weight: 600; }
#cb-send:disabled { opacity: .5; cursor: default; }


/* Empty states */
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:28px;opacity:.45;margin-bottom:12px;}
.empty h3{color:var(--ink);font-size:17px;margin-bottom:6px;}

/* =========================================================
   RESPONSIVE
   1100 tablet landscape · 900 tablet portrait · 768 large phone
   600 phone · 400 small phone
   ========================================================= */

/* Collapse navigation into a hamburger menu */
@media(max-width:1100px){
  .brand{margin-right:0;}
  .nav-toggle{display:inline-flex;}
  .nav{
    position:absolute;top:100%;left:0;right:0;
    flex-direction:column;align-items:stretch;gap:2px;flex:none;
    background:var(--forest);padding:8px var(--pad-x) 16px;
    border-top:1px solid rgba(255,255,255,.12);
    box-shadow:0 16px 24px rgba(0,0,0,.18);
    max-height:calc(100vh - 70px);max-height:calc(100dvh - 70px);overflow-y:auto;
    opacity:0;visibility:hidden;transform:translateY(-8px);
    transition:opacity .2s ease,transform .2s ease,visibility .2s;
  }
  .nav.open{opacity:1;visibility:visible;transform:translateY(0);}
  .nav a{padding:14px 16px;border-radius:10px;font-size:16px;}
  .nav li.logout{margin-left:0;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,.12);}
  .stats{grid-template-columns:repeat(2,1fr);}
}

/* Turn the orders table into stacked cards */
@media(max-width:900px){
  .table-wrap{overflow:visible;}
  table.orders,table.orders tbody,table.orders tr,table.orders td{display:block;width:100%;}
  table.orders thead{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;}
  table.orders tbody tr{border:1px solid var(--line);border-radius:var(--radius);padding:6px 16px;margin-bottom:14px;background:#fff;}
  table.orders tbody tr:last-child{margin-bottom:0;}
  table.orders tbody tr:hover td{background:none;}
  table.orders tbody tr td{
    display:flex;justify-content:space-between;align-items:center;gap:16px;
    padding:10px 0;border-bottom:1px dashed var(--line);text-align:right;
    overflow-wrap:anywhere;
  }
  table.orders tbody tr td:last-child{border-bottom:none;}
  table.orders td::before{
    content:attr(data-label);flex-shrink:0;text-align:left;
    font-weight:600;font-size:12.5px;color:var(--muted);
    text-transform:uppercase;letter-spacing:.04em;
  }
  .status-form select.status-select{min-height:40px;font-size:14px;}
  .status-form .btn-sm{min-height:40px;min-width:44px;}
}

@media(max-width:768px){
  .navbar{height:64px;}
  .nav{max-height:calc(100vh - 64px);max-height:calc(100dvh - 64px);}
  .main{padding-top:18px;padding-bottom:40px;}
  .page-header{flex-direction:column;align-items:flex-start;gap:12px;margin-bottom:18px;}
  .stats{gap:12px;margin-bottom:22px;}
  .section{padding:18px;}
  .tabs{margin-bottom:18px;}
}

@media(max-width:600px){
  .brand{font-size:18px;}
  .stat-card{padding:16px;gap:14px;}
  .stat-icon{width:44px;height:44px;font-size:17px;}
  .tabs{gap:0;}
  .tab-btn{flex:1;padding:10px 12px;font-size:14px;text-align:center;}
  .section-header{flex-direction:column;align-items:stretch;}
  .section-header .btn-sm{width:100%;padding:11px 16px;}
  .section-header h2{font-size:17px;}
}

@media(max-width:520px){
  .stats{grid-template-columns:1fr;}
}

@media(max-width:400px){
  .section{padding:14px;}
  .request-card{padding:16px;}
  .dl{grid-template-columns:1fr;gap:2px;}
  .dl dd{margin-bottom:8px;}
  table.orders tbody tr{padding:4px 12px;}
}

@media(prefers-reduced-motion:reduce){*{transition:none!important;}}


/* Profile page content */
.profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;align-items:start;}
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;min-width:0;}
.section-header{display:flex;align-items:center;gap:10px;margin-bottom:20px;}
.section-header h2{font-size:19px;font-weight:700;}
.section-header i{color:var(--harvest);}
.who{display:flex;align-items:center;gap:14px;margin-bottom:22px;min-width:0;}
.who .avatar{width:58px;height:58px;font-size:19px;}
.who h3{font-size:19px;font-weight:700;overflow-wrap:anywhere;}
.role-tag{display:inline-flex;margin-top:5px;font-size:12.5px;font-weight:600;padding:4px 11px;border-radius:99px;background:var(--harvest-soft);color:#7a4f0e;text-transform:capitalize;}
.profile-details{display:grid;grid-template-columns:120px minmax(0,1fr);gap:12px 14px;font-size:14.5px;}
.profile-details dt{color:var(--muted);}
.profile-details dd{font-weight:600;overflow-wrap:anywhere;min-width:0;}
.lock{color:var(--muted);font-size:12.5px;margin-top:18px;line-height:1.5;}
.field{margin-bottom:17px;}
.field label{display:block;font-weight:600;font-size:14px;margin:0 0 7px;}
.field input,.field textarea{width:100%;border:1px solid #d5ddd3;border-radius:9px;padding:11px 12px;font:inherit;font-size:14.5px;background:#fff;color:var(--ink);}
.field textarea{min-height:100px;resize:vertical;}
.field input:focus,.field textarea:focus{outline:2px solid var(--leaf);outline-offset:1px;border-color:var(--leaf);}
.hint{font-size:12.5px;color:var(--muted);margin-top:5px;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--leaf);color:#fff;border:0;border-radius:9px;padding:11px 18px;font:inherit;font-weight:600;font-size:14px;cursor:pointer;text-decoration:none;transition:background .15s;}
.btn:hover{background:var(--forest);}
.alert{border-radius:10px;padding:12px 14px;margin-bottom:18px;font-size:14px;line-height:1.5;}
.alert.ok{background:var(--leaf-soft);color:var(--forest);}
.alert.err{background:var(--danger-soft);color:var(--danger);}
.alert ul{margin-left:18px;}
@media(max-width:900px){.profile-grid{grid-template-columns:1fr;}}
@media(max-width:600px){
  .section{padding:18px;}
  .profile-details{grid-template-columns:1fr;gap:3px;}
  .profile-details dd{margin-bottom:9px;}
  .btn{width:100%;}
}

</style>
<link rel="stylesheet" href="theme.css">
<script>try{var t=localStorage.getItem("fm-theme");if(!t&&window.matchMedia("(prefers-color-scheme: dark)").matches)t="dark";document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");}catch(e){}</script>
</head>
<body>
<nav class="navbar" aria-label="Main navigation">
    <a class="brand" href="<?= $e($dashboard) ?>"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
        <i class="fa-solid fa-bars"></i>
    </button>
    <ul class="nav" id="main-nav">
        <li><a href="<?= $e($dashboard) ?>"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <?php if ($role === 'farmer'): ?>
        <li><a href="profile.php" class="active"><i class="fa-solid fa-user"></i><span>My Profile</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
        <li><a href="transaction_details.php"><i class="fa-solid fa-receipt"></i><span>My Transaction</span></a></li>
        <?php endif; ?>
    <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>

<main class="main">
    <div class="page-header">
        <div>
            <h1>My Profile</h1>
            <p>View your account details and update your location and contact number.</p>
        </div>
        <div class="profile">
            <span class="avatar"><?= $e($initials ?: '?') ?></span>
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
        <section class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-user"></i> Account Information</h2>
            </div>
            <div class="who">
                <span class="avatar"><?= $e($initials ?: '?') ?></span>
                <div>
                    <h3><?= $e($fullName) ?></h3>
                    <span class="role-tag"><?= $e($u['role'] ?? $role) ?></span>
                </div>
            </div>
            <dl class="profile-details">
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

        <section class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-pen-to-square"></i> Update Details</h2>
            </div>
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
                <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </form>
        </section>
    </div>
</main>

<script>
const flash = document.getElementById('flash');
if (flash) setTimeout(() => flash.style.display = 'none', 5000);

// Mobile navigation, matching farmer.php
(function () {
    const toggle = document.getElementById('nav-toggle');
    const nav = document.getElementById('main-nav');
    const icon = toggle.querySelector('i');

    function setMenu(open) {
        nav.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        icon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    }

    toggle.addEventListener('click', e => {
        e.stopPropagation();
        setMenu(!nav.classList.contains('open'));
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.navbar')) setMenu(false);
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') setMenu(false);
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1100) setMenu(false);
    });
})();
</script>
<script src="theme.js"></script>
</body>
</html>
