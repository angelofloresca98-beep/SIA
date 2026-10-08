<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'buyer') { header("Location: ../login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$buyerName = $_SESSION['user'] ?? 'Buyer';

// Stats
$s = $conn->prepare("SELECT COUNT(*) FROM post WHERE user_id=? AND post_type='wanted'");
$s->execute([$user_id]); $myPostsCount = $s->fetchColumn();

$s = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total_price),0) FROM orders WHERE buyer_id=?");
$s->execute([$user_id]); [$orderCount,$totalSpent] = $s->fetch(PDO::FETCH_NUM);

$s = $conn->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='pending'");
$s->execute([$user_id]); $pendingOrders = $s->fetchColumn();

$s = $conn->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id=? AND status='completed'");
$s->execute([$user_id]); $completedOrders = $s->fetchColumn();

// Latest farmer sell posts
$sql = "SELECT p.post_id, p.created_at, pr.product_id, pr.product_name, pr.variety, pr.unit, pr.price, pr.quantity, pr.description, pr.status AS product_status,
               u.user_id AS farmer_id, CONCAT(u.firstName,' ',u.lastName) AS farmer_name, u.contact_number, u.address
        FROM post p
        JOIN products pr ON p.product_id=pr.product_id
        JOIN users u ON p.user_id=u.user_id
        WHERE p.post_type='sell' AND u.role='farmer' AND pr.status='available'
        ORDER BY p.created_at DESC LIMIT 6";
$stmt = $conn->prepare($sql); $stmt->execute(); $farmerPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent orders by this buyer
$sql = "SELECT o.order_id, o.quantity AS order_qty, o.total_price, o.status, o.created_at,
               pr.product_name, pr.unit,
               CONCAT(u.firstName,' ',u.lastName) AS farmer_name
        FROM orders o
        JOIN products pr ON o.product_id=pr.product_id
        JOIN users u ON pr.user_id=u.user_id
        WHERE o.buyer_id=?
        ORDER BY o.created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql); $stmt->execute([$user_id]); $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Buyer Dashboard · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#fff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;--harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;--danger-soft:#fbe3e0;--radius:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;}
a:focus-visible,button:focus-visible{outline:3px solid var(--harvest);outline-offset:2px;}
.navbar{position:sticky;top:0;width:100%;background:var(--forest);padding:0 32px;display:flex;align-items:center;z-index:1000;height:70px;}
.brand{color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin-right:40px;text-decoration:none;}
.nav{display:flex;align-items:center;gap:8px;flex:1;list-style:none;}
.nav a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.78);text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:500;transition:background .15s;}
.nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav a.active{background:rgba(255,255,255,.14);color:#fff;}
.nav a i{width:18px;text-align:center;}
.nav li.logout{margin-left:auto;}
.main{margin:0 auto;padding:28px 32px 60px;max-width:1300px;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-header h1{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-bottom:2px;}
.page-header p{color:var(--muted);}
.profile{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:6px 16px 6px 6px;font-weight:600;}
.avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;background:var(--harvest-soft);color:#7a4f0e;}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:20px 22px;display:flex;align-items:center;gap:16px;}
.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.green{background:var(--leaf-soft);color:var(--forest);}
.gold{background:var(--harvest-soft);color:var(--harvest);}
.blue{background:#e8f0ff;color:#3b5bdb;}
.red{background:var(--danger-soft);color:var(--danger);}
.stat-number{font-size:28px;font-weight:700;line-height:1.1;letter-spacing:-.02em;}
.stat-label{font-size:14px;font-weight:600;margin-top:2px;}
.stat-sub{color:var(--muted);font-size:13px;}
.tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--line);}
.tab-btn{background:none;border:none;padding:10px 20px;font:inherit;font-weight:600;font-size:15px;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s;}
.tab-btn.active{color:var(--forest);border-bottom-color:var(--leaf);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;}
.section-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;}
.section-header h2{font-size:19px;font-weight:700;}
.btn-sm{display:inline-flex;align-items:center;gap:6px;background:var(--leaf);color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;font:inherit;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:background .15s;}
.btn-sm:hover{background:var(--forest);}
.btn-sm.outline{background:#fff;color:var(--forest);border:1px solid var(--line);}
.btn-sm.outline:hover{background:var(--paper);}
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
.product-card{border:1px solid var(--line);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:10px;background:#fff;transition:border-color .15s,box-shadow .15s;}
.product-card:hover{border-color:#9fbda9;box-shadow:0 4px 14px rgba(31,77,58,.07);}
.product-card h3{font-size:17px;font-weight:700;color:var(--forest);}
.variety-label{color:var(--muted);font-size:13.5px;}
.farmer-chip{display:flex;align-items:center;gap:8px;background:var(--paper);border-radius:8px;padding:8px 12px;font-weight:600;font-size:14px;}
.farmer-chip small{display:block;font-weight:400;color:var(--muted);font-size:12px;}
.av-xs{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;background:var(--leaf-soft);color:var(--forest);}
.dl{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:14px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;}
.dl dd.price-val{color:var(--forest);font-weight:700;}
.order-btn{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:auto;padding:10px;background:var(--harvest);color:#fff;text-decoration:none;border-radius:9px;font-weight:600;font-size:14px;transition:background .15s;}
.order-btn:hover{background:#8c5c0e;}
table{width:100%;border-collapse:collapse;font-size:14.5px;}
th,td{padding:11px 14px;text-align:left;border-bottom:1px solid var(--line);}
th{font-weight:600;color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.04em;background:#fafbfa;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8faf8;}
.badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600;}
.status-pending{background:#fff8e6;color:#92610a;}
.status-confirmed{background:#e8f0ff;color:#3b5bdb;}
.status-completed{background:var(--leaf-soft);color:var(--forest);}
.status-cancelled{background:var(--danger-soft);color:var(--danger);}
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:28px;opacity:.45;margin-bottom:12px;}
.empty h3{color:var(--ink);font-size:17px;margin-bottom:6px;}
@media(max-width:1100px){.stats{grid-template-columns:repeat(2,1fr);}.grid-3{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.navbar{padding:0 16px;overflow-x:auto;}.nav{min-width:max-content;}.nav a span{display:none;}.main{padding:18px 14px 40px;}.page-header{flex-direction:column;align-items:flex-start;}.stats{grid-template-columns:1fr 1fr;}.grid-3{grid-template-columns:1fr;}}
@media(max-width:520px){.stats{grid-template-columns:1fr;}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
</head>
<body>
<nav class="navbar">
    <a class="brand" href="buyer.php"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="buyer.php" class="active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="marketplace.php"><i class="fa-solid fa-store"></i><span>Marketplace</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Requests</span></a></li>
        <li><a href="buyer_post.php"><i class="fa-solid fa-plus"></i><span>Post Request</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box-open"></i><span>My Orders</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>
<main class="main">
    <div class="page-header">
        <div><h1>Buyer Dashboard</h1><p>Welcome back, <strong><?= htmlspecialchars($buyerName) ?></strong>!</p></div>
        <div class="profile"><span class="avatar"><?= strtoupper(mb_substr($buyerName,0,1)) ?></span> Buyer</div>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="stat-icon gold"><i class="fa-solid fa-peso-sign"></i></div><div><div class="stat-number">₱<?= number_format($totalSpent,2) ?></div><div class="stat-label">Total Spent</div><div class="stat-sub">Completed orders</div></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div><div><div class="stat-number"><?= $orderCount ?></div><div class="stat-label">Total Orders</div><div class="stat-sub">All time</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div><div><div class="stat-number"><?= $completedOrders ?></div><div class="stat-label">Completed</div><div class="stat-sub">Received orders</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="fa-solid fa-clock"></i></div><div><div class="stat-number"><?= $pendingOrders ?></div><div class="stat-label">Pending</div><div class="stat-sub">Awaiting confirmation</div></div></div>
    </div>

    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-market">Fresh from Farmers</button>
        <button class="tab-btn" data-tab="tab-orders">Recent Orders</button>
    </div>

    <!-- Marketplace preview -->
    <div class="tab-panel active" id="tab-market">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-store" style="color:var(--harvest);margin-right:8px;"></i>Available Products</h2>
                <a href="marketplace.php" class="btn-sm"><i class="fa-solid fa-arrow-right"></i> View All</a>
            </div>
            <?php if (!empty($farmerPosts)): ?>
            <div class="grid-3">
                <?php foreach ($farmerPosts as $fp):
                    $parts = preg_split('/\s+/', trim($fp['farmer_name']));
                    $initials = strtoupper(mb_substr($parts[0]??'',0,1).mb_substr(end($parts)?:'',0,1));
                ?>
                <div class="product-card">
                    <h3><?= htmlspecialchars($fp['product_name']) ?></h3>
                    <?php if ($fp['variety']): ?><div class="variety-label"><?= htmlspecialchars($fp['variety']) ?></div><?php endif; ?>
                    <div class="farmer-chip">
                        <span class="av-xs"><?= $initials ?></span>
                        <div><small>Farmer</small><?= htmlspecialchars($fp['farmer_name']) ?></div>
                    </div>
                    <dl class="dl">
                        <dt>Available</dt><dd><?= htmlspecialchars($fp['quantity']) ?> <?= htmlspecialchars($fp['unit']) ?></dd>
                        <dt>Price</dt><dd class="price-val">₱<?= number_format($fp['price'],2) ?>/<?= htmlspecialchars($fp['unit']) ?></dd>
                        <dt>Location</dt><dd><?= htmlspecialchars($fp['address']??'—') ?></dd>
                    </dl>
                    <a href="place_order.php?product_id=<?= $fp['product_id'] ?>" class="order-btn"><i class="fa-solid fa-basket-shopping"></i> Place Order</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty"><i class="fa-solid fa-store"></i><h3>No products available</h3><p>Check back soon for fresh products from farmers.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="tab-panel" id="tab-orders">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-box-open" style="color:var(--forest);margin-right:8px;"></i>Recent Orders</h2>
                <a href="orders.php" class="btn-sm outline"><i class="fa-solid fa-list"></i> All Orders</a>
            </div>
            <?php if (!empty($recentOrders)): ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>#</th><th>Product</th><th>Farmer</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><strong>#<?= $o['order_id'] ?></strong></td>
                        <td><?= htmlspecialchars($o['product_name']) ?></td>
                        <td><?= htmlspecialchars($o['farmer_name']) ?></td>
                        <td><?= htmlspecialchars($o['order_qty']) ?> <?= htmlspecialchars($o['unit']) ?></td>
                        <td><strong>₱<?= number_format($o['total_price'],2) ?></strong></td>
                        <td><span class="badge status-<?= htmlspecialchars($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td style="color:var(--muted);font-size:13px;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty"><i class="fa-solid fa-box-open"></i><h3>No orders yet</h3><p>Browse the marketplace and place your first order!</p><a href="marketplace.php" class="btn-sm" style="margin-top:12px;"><i class="fa-solid fa-store"></i> Go to Marketplace</a></div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
document.querySelectorAll('.tab-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>
