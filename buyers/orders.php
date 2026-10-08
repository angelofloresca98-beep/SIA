<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'buyer') { header("Location: ../login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$buyerName = $_SESSION['user'] ?? 'Buyer';

$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');

$where  = "o.buyer_id = :uid";
$params = [':uid' => $user_id];

if ($statusFilter !== 'all') { $where .= " AND o.status = :st"; $params[':st'] = $statusFilter; }
if ($search !== '') { $where .= " AND (pr.product_name LIKE :s OR farmer.firstName LIKE :s OR farmer.lastName LIKE :s)"; $params[':s'] = '%'.$search.'%'; }

$sql = "SELECT o.order_id, o.quantity AS order_qty, o.total_price, o.status, o.created_at,
               pr.product_name, pr.unit, pr.price,
               CONCAT(farmer.firstName,' ',farmer.lastName) AS farmer_name, farmer.contact_number, farmer.address
        FROM orders o
        JOIN products pr ON o.product_id=pr.product_id
        JOIN users farmer ON pr.user_id=farmer.user_id
        WHERE $where
        ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Totals
$s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_price),0), SUM(status='pending'), SUM(status='completed'), SUM(status='cancelled') FROM orders WHERE buyer_id=?");
$s->execute([$user_id]);
[$totalOrders,$totalSpent,$pendingCnt,$completedCnt,$cancelledCnt] = $s->fetch(PDO::FETCH_NUM);

// Flash success message
$success = $_SESSION['order_success'] ?? '';
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#fff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;--harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;--danger-soft:#fbe3e0;--radius:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;}
.navbar{position:sticky;top:0;width:100%;background:var(--forest);padding:0 32px;display:flex;align-items:center;z-index:100;height:70px;}
.brand{color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin-right:40px;text-decoration:none;}
.nav{display:flex;align-items:center;gap:8px;flex:1;list-style:none;}
.nav a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.78);text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:500;}
.nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav a.active{background:rgba(255,255,255,.14);color:#fff;}
.nav a i{width:18px;text-align:center;}
.nav li.logout{margin-left:auto;}
.main{margin:0 auto;padding:28px 32px 60px;max-width:1300px;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-header h1{font-size:26px;font-weight:700;letter-spacing:-.02em;}
.page-header p{color:var(--muted);}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.stat{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;display:flex;align-items:center;gap:14px;}
.stat-icon{width:46px;height:46px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.green{background:var(--leaf-soft);color:var(--forest);}
.gold{background:var(--harvest-soft);color:var(--harvest);}
.blue{background:#e8f0ff;color:#3b5bdb;}
.red{background:var(--danger-soft);color:var(--danger);}
.stat-n{font-size:24px;font-weight:700;line-height:1.1;}
.stat-l{font-size:13.5px;font-weight:600;margin-top:2px;}
.alert{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-weight:600;}
.alert.success{background:var(--leaf-soft);color:var(--forest);}
.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:20px;}
.search-wrap{position:relative;flex:1;min-width:220px;}
.search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);}
.search-wrap input{width:100%;padding:9px 12px 9px 36px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;}
.search-wrap input:focus{outline:none;border-color:var(--leaf);box-shadow:0 0 0 3px rgba(47,125,79,.14);}
select.filter{padding:9px 14px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;cursor:pointer;}
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:14.5px;}
th,td{padding:11px 14px;text-align:left;border-bottom:1px solid var(--line);}
th{font-weight:600;color:var(--muted);font-size:12.5px;text-transform:uppercase;letter-spacing:.05em;background:#fafbfa;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8faf8;}
.badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600;}
.status-pending{background:#fff8e6;color:#92610a;}
.status-confirmed{background:#e8f0ff;color:#3b5bdb;}
.status-completed{background:var(--leaf-soft);color:var(--forest);}
.status-cancelled{background:var(--danger-soft);color:var(--danger);}
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:28px;opacity:.4;margin-bottom:12px;}
.empty h3{color:var(--ink);font-size:16px;}
.btn-sm{display:inline-flex;align-items:center;gap:6px;background:var(--harvest);color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;font:inherit;font-weight:600;font-size:14px;transition:background .15s;}
.btn-sm:hover{background:#8c5c0e;}
@media(max-width:1000px){.stats{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.navbar{padding:0 16px;overflow-x:auto;}.nav{min-width:max-content;}.nav a span{display:none;}.main{padding:18px 14px 40px;}.page-header{flex-direction:column;align-items:flex-start;}.stats{grid-template-columns:1fr 1fr;}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
</head>
<body>
<nav class="navbar">
    <a class="brand" href="buyer.php"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="buyer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="marketplace.php"><i class="fa-solid fa-store"></i><span>Marketplace</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Requests</span></a></li>
        <li><a href="orders.php" class="active"><i class="fa-solid fa-box-open"></i><span>My Orders</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>
<main class="main">
    <div class="page-header">
        <div><h1>My Orders</h1><p>Track all your purchase orders from farmers.</p></div>
        <a href="marketplace.php" class="btn-sm"><i class="fa-solid fa-store"></i> Shop More</a>
    </div>

    <?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><div class="stat-icon gold"><i class="fa-solid fa-peso-sign"></i></div><div><div class="stat-n">₱<?= number_format($totalSpent,2) ?></div><div class="stat-l">Total Spent</div></div></div>
        <div class="stat"><div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div><div><div class="stat-n"><?= $totalOrders ?></div><div class="stat-l">Total Orders</div></div></div>
        <div class="stat"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-n"><?= $completedCnt ?></div><div class="stat-l">Completed</div></div></div>
        <div class="stat"><div class="stat-icon red"><i class="fa-solid fa-clock"></i></div><div><div class="stat-n"><?= $pendingCnt ?></div><div class="stat-l">Pending</div></div></div>
    </div>

    <form method="GET" class="toolbar">
        <div class="search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" name="search" placeholder="Search product or farmer..." value="<?= htmlspecialchars($search) ?>"></div>
        <select name="status" class="filter" onchange="this.form.submit()">
            <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All Statuses</option>
            <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Pending</option>
            <option value="confirmed" <?= $statusFilter==='confirmed'?'selected':'' ?>>Confirmed</option>
            <option value="completed" <?= $statusFilter==='completed'?'selected':'' ?>>Completed</option>
            <option value="cancelled" <?= $statusFilter==='cancelled'?'selected':'' ?>>Cancelled</option>
        </select>
    </form>

    <div class="section">
        <?php if (!empty($orders)): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Product</th><th>Farmer</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['order_id'] ?></strong></td>
                    <td><?= htmlspecialchars($o['product_name']) ?></td>
                    <td><?= htmlspecialchars($o['farmer_name']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($o['contact_number']??'') ?></small></td>
                    <td><?= htmlspecialchars($o['order_qty']) ?> <?= htmlspecialchars($o['unit']) ?></td>
                    <td>₱<?= number_format($o['price'],2) ?></td>
                    <td><strong>₱<?= number_format($o['total_price'],2) ?></strong></td>
                    <td><span class="badge status-<?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="color:var(--muted);font-size:13px;white-space:nowrap;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty"><i class="fa-solid fa-box-open"></i><h3>No orders found</h3><p><?= $search?'Try a different search.':'You haven\'t placed any orders yet.' ?></p></div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
