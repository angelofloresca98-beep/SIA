<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

// CSRF token (used by the delete form)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash message (set it in delete_user.php / add.php / update_user.php)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// One query for everyone, split in PHP
$stmt = $conn->prepare(
    "SELECT user_id, firstName, middleName, lastName, address, contact_number, email, role
     FROM users
     WHERE role IN ('farmer', 'buyer')
     ORDER BY user_id DESC"
);
$stmt->execute();
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

$farmers = array_values(array_filter($all, fn($u) => $u['role'] === 'farmer'));
$buyers  = array_values(array_filter($all, fn($u) => $u['role'] === 'buyer'));

$totalUsers   = count($all);
$totalFarmers = count($farmers);
$totalBuyers  = count($buyers);
$farmerPct    = $totalUsers ? round($totalFarmers / $totalUsers * 100) : 0;

// ---------- Posts (farmers + buyers), newest first ----------
// post(post_id, user_id, product_id, post_type, created_at)
// ASSUMED products table: products(product_id, product_name, description, price)  <-- rename to match yours
$stmt = $conn->prepare(
    "SELECT p.post_id, p.post_type, p.created_at,
            pr.product_name AS title, pr.description, pr.price,
            u.firstName, u.middleName, u.lastName, u.role
     FROM post p
     JOIN users u ON u.user_id = p.user_id
     LEFT JOIN products pr ON pr.product_id = p.product_id
     WHERE u.role IN ('farmer', 'buyer')
     ORDER BY p.created_at DESC
     LIMIT 200"
);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------- Transactions ----------
// transactions(transaction_id, user_id, product_id, buyer_name, product_name, quantity, unit,
//              price, total_amount, payment_method, status, transaction_date, notes, created_at)
// user_id = the user who made the transaction (the seller/farmer)
$stmt = $conn->prepare(
    "SELECT t.transaction_id, t.product_name, t.buyer_name, t.quantity, t.unit,
            t.price, t.total_amount, t.payment_method, t.status, t.notes,
            COALESCE(t.transaction_date, t.created_at) AS tx_date,
            CONCAT_WS(' ', u.firstName, u.lastName) AS seller_name
     FROM transactions t
     LEFT JOIN users u ON u.user_id = t.user_id
     ORDER BY COALESCE(t.transaction_date, t.created_at) DESC
     LIMIT 200"
);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total of completed sales
$stmt = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE status = 'completed'");
$totalSales = (float)$stmt->fetchColumn();

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function fullName(array $u): string {
    return trim(implode(' ', array_filter([$u['firstName'], $u['middleName'], $u['lastName']])));
}

function initials(array $u): string {
    return strtoupper(mb_substr($u['firstName'] ?? '', 0, 1) . mb_substr($u['lastName'] ?? '', 0, 1));
}

function statusBadge(string $s): string {
    $map = ['completed' => 'bg-success', 'pending' => 'bg-warning text-dark',
            'cancelled' => 'bg-danger', 'failed' => 'bg-danger'];
    $cls = $map[strtolower($s)] ?? 'bg-secondary';
    return '<span class="badge ' . $cls . '">' . e(ucfirst($s)) . '</span>';
}

function renderUserTable(array $users, string $role): void {
    if (!$users) {
        echo '<div class="empty"><i class="fa-solid fa-user-slash"></i>'
           . '<p>No ' . e($role) . 's yet.</p>'
           . '<a href="add.php" class="btn btn-sm btn-primary">Add a ' . e($role) . '</a></div>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table align-middle user-table" data-role="<?= e($role) ?>">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr data-search="<?= e(strtolower(fullName($u) . ' ' . $u['email'] . ' ' . $u['contact_number'] . ' ' . $u['address'])) ?>">
                    <td>
                        <div class="person">
                            <span class="avatar <?= e($role) ?>"><?= e(initials($u)) ?></span>
                            <div>
                                <div class="person-name"><?= e(fullName($u)) ?></div>
                                <div class="person-email"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($u['contact_number']) ?></td>
                    <td class="address"><?= e($u['address']) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="update_user.php?user_id=<?= urlencode($u['user_id']) ?>"
                           class="action-btn edit-btn" title="Edit <?= e(fullName($u)) ?>"
                           aria-label="Edit <?= e(fullName($u)) ?>">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <button type="button" class="action-btn delete-btn"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= e($u['user_id']) ?>"
                                data-name="<?= e(fullName($u)) ?>"
                                aria-label="Delete <?= e(fullName($u)) ?>">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="empty d-none no-results">
            <i class="fa-solid fa-magnifying-glass"></i>
            <p>No <?= e($role) ?>s match your search.</p>
        </div>
    </div>
    <?php
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

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

a:focus-visible, button:focus-visible, input:focus-visible {
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

.admin-chip .avatar { background: var(--forest); color: #fff; }
.admin-chip small { color: var(--muted); display: block; line-height: 1.1; }
.admin-chip strong { line-height: 1.2; }

/* ---------- Stats ---------- */
.stats {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 22px 24px;
}

.stat-card .label { color: var(--muted); font-weight: 500; display: flex; align-items: center; gap: 8px; }
.stat-card .value { font-size: 38px; font-weight: 700; line-height: 1.1; margin: 8px 0 0; letter-spacing: -0.02em; }
.stat-card.sales .value { font-size: 30px; }

.stat-card.total { background: var(--forest); border-color: var(--forest); color: #fff; }
.stat-card.total .label { color: rgba(255,255,255,.75); }

.split { display: flex; height: 8px; border-radius: 99px; overflow: hidden; background: rgba(255,255,255,.2); margin-top: 16px; }
.split span { display: block; background: #8fd3a8; }
.split-legend { display: flex; justify-content: space-between; font-size: 13px; color: rgba(255,255,255,.75); margin-top: 8px; }

/* ---------- Panel ---------- */
.panel {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 24px;
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.panel-head h2 { font-size: 19px; font-weight: 700; margin: 0; }

.tools { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.search { position: relative; }
.search i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); }
.search input {
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 9px 12px 9px 38px;
    width: 280px;
    max-width: 100%;
    background: var(--paper);
    font: inherit;
}

.btn-primary { background: var(--leaf); border-color: var(--leaf); font-weight: 600; }
.btn-primary:hover, .btn-primary:focus { background: var(--forest); border-color: var(--forest); }

/* ---------- Tabs ---------- */
.nav-tabs { border-bottom: 1px solid var(--line); margin-bottom: 8px; }
.nav-tabs .nav-link {
    color: var(--muted);
    border: none;
    font-weight: 600;
    padding: 10px 18px;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
}
.nav-tabs .nav-link:hover { color: var(--ink); }
.nav-tabs .nav-link.active { background: transparent; color: var(--forest); border-bottom-color: var(--leaf); }
.count {
    display: inline-block;
    min-width: 24px;
    padding: 1px 8px;
    margin-left: 6px;
    border-radius: 99px;
    background: var(--paper);
    font-size: 12px;
    text-align: center;
}

/* ---------- Table ---------- */
.user-table thead th {
    color: var(--muted);
    font-weight: 600;
    font-size: 13px;
    background: transparent;
    border-bottom: 1px solid var(--line);
    padding: 12px 10px;
}
.user-table tbody td { padding: 14px 10px; border-bottom: 1px solid var(--line); }
.user-table tbody tr:last-child td { border-bottom: none; }
.user-table tbody tr:hover td { background: #fafbf9; }
.user-table .address { color: var(--muted); max-width: 260px; }

.person { display: flex; align-items: center; gap: 12px; }
.person-name { font-weight: 600; }
.person-email { color: var(--muted); font-size: 13px; }

.avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px;
    flex-shrink: 0;
}
.avatar.farmer { background: var(--leaf-soft); color: var(--forest); }
.avatar.buyer  { background: var(--harvest-soft); color: #7a4f0e; }

.action-btn {
    width: 34px; height: 34px;
    border-radius: 8px;
    border: none;
    display: inline-flex; align-items: center; justify-content: center;
    text-decoration: none;
    margin-left: 4px;
    transition: background .15s, color .15s;
}
.edit-btn { background: var(--leaf-soft); color: var(--forest); }
.edit-btn:hover { background: var(--leaf); color: #fff; }
.delete-btn { background: var(--danger-soft); color: var(--danger); }
.delete-btn:hover { background: var(--danger); color: #fff; }

/* ---------- Empty states ---------- */
.empty { text-align: center; color: var(--muted); padding: 40px 16px; }
.empty i { font-size: 28px; margin-bottom: 10px; opacity: .6; }
.empty p { margin-bottom: 12px; }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
    .stats { grid-template-columns: 1fr 1fr; }
    .stat-card.total { grid-column: 1 / -1; }
}

/* Sidebar becomes a slide-in drawer */
@media (max-width: 992px) {
    .mobile-bar { display: flex; }

    .sidebar { transform: translateX(-100%); box-shadow: none; }
    .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,.35); }
    .sidebar-backdrop.show { display: block; }

    .main { margin-left: 0; padding: 78px 16px 40px; }
    .scroll-section { scroll-margin-top: 74px; }

    .topbar { flex-direction: column; align-items: flex-start; }
    .search input { width: 100%; }
    .search, .tools { width: 100%; }
    .panel { padding: 16px; }
}

@media (max-width: 520px) {
    .stats { grid-template-columns: 1fr; }
}

@media (prefers-reduced-motion: reduce) {
    * { transition: none !important; }
}
</style>
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
        <a href="#dashboard" class="menu-link active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="#users" class="menu-link"><i class="fa-solid fa-users"></i><span>User Management</span></a>
        <a href="add.php"><i class="fa-solid fa-user-plus"></i><span>Add User</span></a>
        <a href="#posts" class="menu-link"><i class="fa-solid fa-newspaper"></i><span>Posts</span></a>
        <a href="#sales" class="menu-link"><i class="fa-solid fa-chart-line"></i><span>Sales Overview</span></a>
    </nav>

    <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log out</span></a>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Manage the farmers and buyers on your platform.</p>
        </div>

        <div class="admin-chip">
            <span class="avatar"><?= e(strtoupper(mb_substr($_SESSION['user'] ?? 'A', 0, 1))) ?></span>
            <div>
                <strong><?= e($_SESSION['user'] ?? 'Admin') ?></strong>
                <small>Administrator</small>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type'] ?? 'success') ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <section class="stats scroll-section" id="dashboard" aria-label="Summary">
        <div class="stat-card total">
            <div class="label"><i class="fa-solid fa-users"></i> Total users</div>
            <div class="value"><?= $totalUsers ?></div>
            <div class="split" role="img" aria-label="<?= $farmerPct ?>% farmers, <?= 100 - $farmerPct ?>% buyers">
                <span style="width: <?= $farmerPct ?>%"></span>
            </div>
            <div class="split-legend">
                <span>Farmers <?= $farmerPct ?>%</span>
                <span>Buyers <?= $totalUsers ? 100 - $farmerPct : 0 ?>%</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-tractor"></i> Farmers</div>
            <div class="value"><?= $totalFarmers ?></div>
        </div>

        <div class="stat-card">
            <div class="label"><i class="fa-solid fa-cart-shopping"></i> Buyers</div>
            <div class="value"><?= $totalBuyers ?></div>
        </div>

        <div class="stat-card sales">
            <div class="label"><i class="fa-solid fa-peso-sign"></i> Completed sales</div>
            <div class="value">₱<?= number_format($totalSales, 2) ?></div>
        </div>
    </section>

    <section class="panel scroll-section" id="users">

        <div class="panel-head">
            <h2>Users</h2>

            <div class="tools">
                <label class="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="userSearch" placeholder="Search name, email, contact or address"
                           aria-label="Search users">
                </label>
                <a href="add.php" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add user</a>
            </div>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#farmer" type="button" role="tab">
                    <i class="fa-solid fa-tractor me-1"></i> Farmers
                    <span class="count" data-count="farmer"><?= $totalFarmers ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#buyer" type="button" role="tab">
                    <i class="fa-solid fa-cart-shopping me-1"></i> Buyers
                    <span class="count" data-count="buyer"><?= $totalBuyers ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="farmer" role="tabpanel">
                <?php renderUserTable($farmers, 'farmer'); ?>
            </div>
            <div class="tab-pane fade" id="buyer" role="tabpanel">
                <?php renderUserTable($buyers, 'buyer'); ?>
            </div>
        </div>

    </section>

    <!-- ===== Posts (farmers + buyers) ===== -->
    <section class="panel scroll-section" id="posts" style="margin-top: 24px;">
        <div class="panel-head">
            <h2>Posts <span class="count"><?= count($posts) ?></span></h2>
            <div class="btn-group" role="group" aria-label="Filter posts">
                <button type="button" class="btn btn-outline-secondary btn-sm active" data-post-filter="all">All</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-post-filter="farmer">Farmers</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-post-filter="buyer">Buyers</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="postsTable">
                <thead>
                    <tr>
                        <th>Posted by</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$posts): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No posts yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $p): ?>
                    <tr data-role="<?= e($p['role']) ?>">
                        <td>
                            <div class="person">
                                <span class="avatar <?= e($p['role']) ?>"><?= e(initials($p)) ?></span>
                                <div>
                                    <div class="person-name"><?= e(fullName($p)) ?></div>
                                    <div class="person-email"><?= e(ucfirst($p['role'])) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="person-name"><?= e($p['title'] ?? '—') ?></div>
                            <div class="person-email"><?= e(mb_strimwidth((string)($p['description'] ?? ''), 0, 80, '…')) ?></div>
                        </td>
                        <td><span class="badge bg-secondary"><?= e(ucfirst((string)$p['post_type'])) ?></span></td>
                        <td>₱<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                        <td><?= e(date('M d, Y g:i A', strtotime($p['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ===== Sales / Transactions ===== -->
    <section class="panel scroll-section" id="sales" style="margin-top: 24px;">
        <div class="panel-head">
            <h2>Sales Overview <span class="count"><?= count($transactions) ?></span></h2>
            <div class="tools">
                <button class="btn btn-primary" onclick="alert('Exporting report...')"><i class="fa-solid fa-download me-1"></i> Export Report</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Product</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$transactions): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No transactions yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td>#TRX-<?= e($t['transaction_id']) ?></td>
                        <td><?= e($t['product_name'] ?? '—') ?></td>
                        <td><?= e($t['buyer_name'] ?? '—') ?></td>
                        <td><?= e($t['seller_name'] ?: '—') ?></td>
                        <td><?= e(rtrim(rtrim(number_format((float)$t['quantity'], 2), '0'), '.')) ?> <?= e($t['unit']) ?></td>
                        <td>₱<?= number_format((float)$t['price'], 2) ?></td>
                        <td><strong>₱<?= number_format((float)$t['total_amount'], 2) ?></strong></td>
                        <td><?= e($t['payment_method'] ?? '—') ?></td>
                        <td><?= statusBadge((string)$t['status']) ?></td>
                        <td><?= e(date('M d, Y g:i A', strtotime($t['tx_date']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Delete confirmation (POST + CSRF instead of a GET link) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="delete_user.php">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="user_id" id="deleteUserId">

            <div class="modal-header">
                <h5 class="modal-title" id="deleteTitle">Delete this user?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0"><strong id="deleteUserName"></strong> will be permanently removed. This can't be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete user</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fill the delete modal with the selected user
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (ev) {
    const btn = ev.relatedTarget;
    document.getElementById('deleteUserId').value = btn.dataset.id;
    document.getElementById('deleteUserName').textContent = btn.dataset.name;
});

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

// Smooth scrolling & scroll spy
document.addEventListener('DOMContentLoaded', () => {
    const menuLinks = document.querySelectorAll('.menu-link');
    const sections = document.querySelectorAll('.scroll-section');

    // Smooth scrolling for menu links (also closes the mobile drawer)
    menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId.startsWith('#')) {
                e.preventDefault();
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                setSidebar(false);
            }
        });
    });

    // Scroll spy to update the active sidebar link
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollY >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });

        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });
});

// Live search across both tabs
const searchInput = document.getElementById('userSearch');
searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();

    document.querySelectorAll('.user-table').forEach(function (table) {
        const rows = table.querySelectorAll('tbody tr');
        let visible = 0;

        rows.forEach(function (row) {
            const match = row.dataset.search.includes(q);
            row.hidden = !match;
            if (match) visible++;
        });

        table.parentElement.querySelector('.no-results').classList.toggle('d-none', visible !== 0);
        table.hidden = visible === 0;

        const badge = document.querySelector('[data-count="' + table.dataset.role + '"]');
        if (badge) badge.textContent = visible;
    });
});

// Filter posts by farmer / buyer
document.querySelectorAll('[data-post-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const f = this.dataset.postFilter;
        document.querySelectorAll('[data-post-filter]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('#postsTable tbody tr[data-role]').forEach(function (row) {
            row.hidden = f !== 'all' && row.dataset.role !== f;
        });
    });
});
</script>

</body>
</html>