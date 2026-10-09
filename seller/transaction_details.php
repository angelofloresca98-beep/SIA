<?php

session_start();

require '../database/connection.php';

// CHECK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// CHECK FARMER ROLE
if ($_SESSION['role'] !== 'farmer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$paymentLabels = [
    'cash'          => 'Cash',
    'gcash'         => 'GCash',
    'bank_transfer' => 'Bank transfer',
    'other'         => 'Other',
];

$statusLabels = [
    'completed' => 'Completed',
    'pending'   => 'Pending',
    'cancelled' => 'Cancelled',
];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function peso($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function qty($value) {
    // Show 50 instead of 50.00, but keep 2.5 as 2.5
    return rtrim(rtrim(number_format((float)$value, 2, '.', ','), '0'), '.');
}

function validDate($d) {
    $obj = DateTime::createFromFormat('Y-m-d', $d);
    return $obj && $obj->format('Y-m-d') === $d;
}


// ---------- FILTERS ----------
$search    = trim($_GET['q'] ?? '');
$statusF   = trim($_GET['status'] ?? '');
$dateFrom  = trim($_GET['from'] ?? '');
$dateTo    = trim($_GET['to'] ?? '');

if ($statusF !== '' && !array_key_exists($statusF, $statusLabels)) $statusF = '';
if ($dateFrom !== '' && !validDate($dateFrom)) $dateFrom = '';
if ($dateTo !== '' && !validDate($dateTo)) $dateTo = '';

$where  = ["t.user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($search !== '') {
    $like = '%' . addcslashes($search, '%_\\') . '%';
    $where[] = "(t.buyer_name LIKE :q1 OR t.product_name LIKE :q2)";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
}
if ($statusF !== '') {
    $where[] = "t.status = :status";
    $params[':status'] = $statusF;
}
if ($dateFrom !== '') {
    $where[] = "t.transaction_date >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "t.transaction_date <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$filtersActive = ($search !== '' || $statusF !== '' || $dateFrom !== '' || $dateTo !== '');


// ---------- DATA ----------
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));

$transactions = [];
$stats = [
    'count'           => 0,
    'completed_total' => 0,
    'pending_total'   => 0,
    'cancelled_count' => 0,
];
$error = "";

try {

    // Summary numbers for the current filter
    $stmtStats = $conn->prepare("
        SELECT
            COUNT(*) AS cnt,
            COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.total_amount END), 0) AS completed_total,
            COALESCE(SUM(CASE WHEN t.status = 'pending'   THEN t.total_amount END), 0) AS pending_total,
            COALESCE(SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END), 0)       AS cancelled_count
        FROM transactions t
        WHERE $whereSql
    ");
    $stmtStats->execute($params);
    $row = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $stats['count']           = (int)$row['cnt'];
    $stats['completed_total'] = (float)$row['completed_total'];
    $stats['pending_total']   = (float)$row['pending_total'];
    $stats['cancelled_count'] = (int)$row['cancelled_count'];

    $totalPages = max(1, (int)ceil($stats['count'] / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    // The rows for this page
    $stmt = $conn->prepare("
        SELECT t.*, p.variety
        FROM transactions t
        LEFT JOIN products p ON p.product_id = t.product_id
        WHERE $whereSql
        ORDER BY t.transaction_date DESC, t.transaction_id DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Unable to load your transactions right now.";
    $totalPages = 1;
    $offset = 0;
}

// Keep filters when moving between pages
function pageUrl($p, $search, $statusF, $dateFrom, $dateTo) {
    $q = array_filter([
        'q'      => $search,
        'status' => $statusF,
        'from'   => $dateFrom,
        'to'     => $dateTo,
        'page'   => $p > 1 ? $p : '',
    ], function ($v) { return $v !== '' && $v !== null; });

    return '?' . http_build_query($q);
}

$showingFrom = $stats['count'] > 0 ? $offset + 1 : 0;
$showingTo   = $offset + count($transactions);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Transactions</title>

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
    --leaf-soft: #dff0e5;
    --harvest: #b7791f;
    --harvest-soft: #f8ecd2;
    --danger: #b3261e;
    --danger-soft: #fbe3e0;
    --radius: 12px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; }

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
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
.brand strong, .logo h2 { margin: 0; display: flex; align-items: center; gap: 10px; font-size: 20px; letter-spacing: -0.01em; }
.brand small, .logo p { margin: 0; display: none; }

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
.main { margin: 0 auto; padding: 22px 32px 32px; max-width: 1600px; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
.header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }
.header-actions { display: flex; gap: 10px; }

/* ---------- Buttons ---------- */
.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 0; background: var(--leaf); color: #fff; text-decoration: none;
    padding: 10px 20px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn:hover { background: var(--forest); }

.btn-outline {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    background: #fff; color: var(--ink); text-decoration: none;
    border: 1px solid var(--line); padding: 10px 20px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
}
.btn-outline:hover { background: var(--paper); }

/* ---------- Stats ---------- */
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 18px; }
.stat { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 16px 20px; display: flex; align-items: center; gap: 14px; }
.stat .icon { width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.stat .icon.green  { background: var(--leaf-soft); color: var(--forest); }
.stat .icon.gold   { background: var(--harvest-soft); color: var(--harvest); }
.stat .icon.red    { background: var(--danger-soft); color: var(--danger); }
.stat .icon.neutral { background: var(--paper); color: var(--ink); }
.stat .label { font-size: 12.5px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
.stat .value { font-size: 22px; font-weight: 700; letter-spacing: -0.01em; }

/* ---------- Filters ---------- */
.filters { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 16px 20px; margin-bottom: 18px; }
.filters form { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 14px; align-items: end; }
.filters .field { display: flex; flex-direction: column; }
.filters label { font-weight: 600; font-size: 13px; margin-bottom: 5px; }
.filters input, .filters select {
    padding: 10px 12px; border: 1px solid #cbd5cd; border-radius: 10px;
    font: inherit; background: #fff; color: var(--ink);
    transition: border-color .15s, box-shadow .15s;
}
.filters input:focus, .filters select:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }
.filters .actions { display: flex; gap: 8px; }

/* ---------- Table ---------- */
.table-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; }
.table-wrap { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; min-width: 980px; }
thead th {
    text-align: left; font-size: 12.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
    color: var(--muted); background: var(--paper); padding: 12px 16px; border-bottom: 1px solid var(--line); white-space: nowrap;
}
tbody td { padding: 14px 16px; border-bottom: 1px solid var(--line); vertical-align: top; font-size: 14.5px; }
tbody tr:last-child td { border-bottom: 0; }
tbody tr:hover { background: #fafbf9; }

th.num, td.num { text-align: right; white-space: nowrap; }
td.ref { font-weight: 700; color: var(--forest); white-space: nowrap; }
td.date { white-space: nowrap; }
td .main-text { font-weight: 600; }
td .sub-text { display: block; color: var(--muted); font-size: 12.5px; margin-top: 2px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
td.total { font-weight: 700; }

/* Status badge */
.badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 11px; border-radius: 999px;
    font-size: 12.5px; font-weight: 700; white-space: nowrap;
}
.badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.badge.completed { background: var(--leaf-soft); color: var(--forest); }
.badge.pending   { background: var(--harvest-soft); color: var(--harvest); }
.badge.cancelled { background: var(--danger-soft); color: var(--danger); }

/* Footer / pagination */
.table-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 18px; border-top: 1px solid var(--line); color: var(--muted); font-size: 14px; flex-wrap: wrap; }
.pager { display: flex; align-items: center; gap: 8px; }
.pager a, .pager span.disabled {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px;
    border: 1px solid var(--line); background: #fff; color: var(--ink); text-decoration: none; font-weight: 600; font-size: 14px;
}
.pager a:hover { background: var(--paper); }
.pager span.disabled { color: #a7b2ab; background: var(--paper); cursor: not-allowed; }
.pager .current { color: var(--ink); font-weight: 600; padding: 0 6px; }

/* Messages / empty */
.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-weight: 500; font-size: 14.5px; background: var(--danger-soft); color: var(--danger); }
.message::before { content: "\f06a"; font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }

.empty { text-align: center; padding: 56px 24px; }
.empty i { font-size: 38px; color: var(--leaf); margin-bottom: 14px; }
.empty h2 { font-size: 18px; margin-bottom: 6px; }
.empty p { color: var(--muted); margin-bottom: 20px; }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
    .filters form { grid-template-columns: 1fr 1fr 1fr; }
    .filters .field.search { grid-column: 1 / -1; }
    .filters .actions { grid-column: 1 / -1; }
}

@media (max-width: 768px) {
    .navbar { padding: 0 16px; overflow-x: auto; }
    .brand, .logo { margin-right: 20px; white-space: nowrap; }
    .navbar nav, .navbar .nav { flex: 1; min-width: max-content; }
    .navbar a { white-space: nowrap; }
    .navbar a span { display: none; }
    .main { padding: 18px 14px 32px; }
    .header { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 560px) {
    .stats { grid-template-columns: 1fr; }
    .filters form { grid-template-columns: 1fr; }
    .header-actions { width: 100%; flex-direction: column; }
    .header-actions .btn, .header-actions .btn-outline { width: 100%; }
    .table-footer { flex-direction: column; align-items: flex-start; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }

/* ---------- Print ---------- */
@media print {
    .navbar, .header-actions, .filters, .pager { display: none; }
    body { background: #fff; }
    .main { padding: 0; max-width: 100%; }
    .table-wrap { overflow: visible; }
    table { min-width: 0; font-size: 12px; }
    thead th, tbody td { padding: 8px; }
    tr { break-inside: avoid; }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">

    <div class="logo">
        <h2><i class="fa-solid fa-seedling"></i> FarmMarket</h2>
        <p>Farm-to-Market System</p>
    </div>

    <ul class="nav">
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
        <li><a href="farmer_transaction_details.php" class="active"><i class="fa-solid fa-receipt"></i><span>Transactions</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</nav>


<!-- MAIN CONTENT -->
<main class="main">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>My Transactions</h1>
            <p>All the sales you have recorded.</p>
        </div>

        <div class="header-actions">
            <a href="transaction.php" class="btn"><i class="fa-solid fa-plus"></i> Record transaction</a>
            <button type="button" class="btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>

    <?php if ($error !== ""): ?>
        <div class="message" role="alert"><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <!-- STATS -->
    <section class="stats" aria-label="Summary">
        <div class="stat">
            <span class="icon neutral"><i class="fa-solid fa-receipt"></i></span>
            <div>
                <div class="label">Transactions</div>
                <div class="value"><?= number_format($stats['count']) ?></div>
            </div>
        </div>
        <div class="stat">
            <span class="icon green"><i class="fa-solid fa-peso-sign"></i></span>
            <div>
                <div class="label">Completed sales</div>
                <div class="value"><?= e(peso($stats['completed_total'])) ?></div>
            </div>
        </div>
        <div class="stat">
            <span class="icon gold"><i class="fa-solid fa-hourglass-half"></i></span>
            <div>
                <div class="label">Pending amount</div>
                <div class="value"><?= e(peso($stats['pending_total'])) ?></div>
            </div>
        </div>
        <div class="stat">
            <span class="icon red"><i class="fa-solid fa-ban"></i></span>
            <div>
                <div class="label">Cancelled</div>
                <div class="value"><?= number_format($stats['cancelled_count']) ?></div>
            </div>
        </div>
    </section>

    <!-- FILTERS -->
    <section class="filters" aria-label="Filter transactions">
        <form method="GET" action="">

            <div class="field search">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" placeholder="Buyer or product" value="<?= e($search) ?>">
            </div>

            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $statusF === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="from">From</label>
                <input type="date" id="from" name="from" value="<?= e($dateFrom) ?>">
            </div>

            <div class="field">
                <label for="to">To</label>
                <input type="date" id="to" name="to" value="<?= e($dateTo) ?>">
            </div>

            <div class="actions">
                <button type="submit" class="btn"><i class="fa-solid fa-filter"></i> Filter</button>
                <?php if ($filtersActive): ?>
                    <a href="farmer_transaction_details.php" class="btn-outline">Reset</a>
                <?php endif; ?>
            </div>

        </form>
    </section>

    <!-- TRANSACTIONS TABLE -->
    <section class="table-card">

        <?php if (!empty($transactions)): ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Date</th>
                            <th>Buyer</th>
                            <th>Product</th>
                            <th class="num">Quantity</th>
                            <th class="num">Price</th>
                            <th class="num">Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $t):

                        $statusKey   = array_key_exists($t['status'], $statusLabels) ? $t['status'] : 'pending';
                        $statusText  = $statusLabels[$t['status']] ?? ucfirst($t['status']);
                        $payText     = $paymentLabels[$t['payment_method']] ?? ucfirst(str_replace('_', ' ', $t['payment_method']));
                        $refNo       = '#' . str_pad($t['transaction_id'], 5, '0', STR_PAD_LEFT);
                        $dateText    = date('M j, Y', strtotime($t['transaction_date']));

                        $notes = trim((string)($t['notes'] ?? ''));
                    ?>
                        <tr>
                            <td class="ref"><?= e($refNo) ?></td>
                            <td class="date"><?= e($dateText) ?></td>
                            <td><span class="main-text"><?= e($t['buyer_name']) ?></span></td>
                            <td>
                                <span class="main-text"><?= e($t['product_name']) ?></span>
                                <?php if (!empty($t['variety'])): ?>
                                    <span class="sub-text"><?= e($t['variety']) ?></span>
                                <?php endif; ?>
                                <?php if ($notes !== ''): ?>
                                    <span class="sub-text" title="<?= e($notes) ?>"><i class="fa-regular fa-note-sticky"></i> <?= e($notes) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="num"><?= e(qty($t['quantity'])) ?> <?= e($t['unit']) ?></td>
                            <td class="num"><?= e(peso($t['price'])) ?></td>
                            <td class="num total"><?= e(peso($t['total_amount'])) ?></td>
                            <td><?= e($payText) ?></td>
                            <td><span class="badge <?= e($statusKey) ?>"><?= e($statusText) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-footer">
                <span>Showing <?= number_format($showingFrom) ?>–<?= number_format($showingTo) ?> of <?= number_format($stats['count']) ?></span>

                <?php if ($totalPages > 1): ?>
                    <nav class="pager" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(pageUrl($page - 1, $search, $statusF, $dateFrom, $dateTo)) ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a>
                        <?php else: ?>
                            <span class="disabled"><i class="fa-solid fa-chevron-left"></i> Previous</span>
                        <?php endif; ?>

                        <span class="current">Page <?= $page ?> of <?= $totalPages ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(pageUrl($page + 1, $search, $statusF, $dateFrom, $dateTo)) ?>">Next <i class="fa-solid fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="empty">
                <i class="fa-solid fa-receipt"></i>
                <h2><?= $filtersActive ? "No matching transactions" : "No transactions yet" ?></h2>
                <p>
                    <?= $filtersActive
                        ? "Try changing or resetting your filters."
                        : "Record your first sale and it will show up here." ?>
                </p>
                <?php if ($filtersActive): ?>
                    <a href="farmer_transaction_details.php" class="btn-outline">Reset filters</a>
                <?php else: ?>
                    <a href="farmer_transaction.php" class="btn"><i class="fa-solid fa-plus"></i> Record transaction</a>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>