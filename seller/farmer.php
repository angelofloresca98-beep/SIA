<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'farmer') { header("Location: ../login.php"); exit(); }

$user_id    = $_SESSION['user_id'];
$farmerName = $_SESSION['user'] ?? 'Farmer';

// ── Stats ──────────────────────────────────────────────
// My active listings
$stmt = $conn->prepare("SELECT COUNT(*) FROM post p JOIN products pr ON p.product_id=pr.product_id WHERE p.user_id=? AND p.post_type='sell'");
$stmt->execute([$user_id]); $myPostsCount = $stmt->fetchColumn();

// Total sales (completed orders for this farmer)
$stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(o.total_price),0) FROM orders o JOIN products pr ON o.product_id=pr.product_id WHERE pr.user_id=? AND o.status='completed'");
$stmt->execute([$user_id]); [$salesCount, $totalRevenue] = $stmt->fetch(PDO::FETCH_NUM);

// Pending orders
$stmt = $conn->prepare("SELECT COUNT(*) FROM orders o JOIN products pr ON o.product_id=pr.product_id WHERE pr.user_id=? AND o.status='pending'");
$stmt->execute([$user_id]); $pendingOrders = $stmt->fetchColumn();

// Latest buyer "wanted" posts
$sql = "SELECT p.post_id, p.created_at, pr.product_name, pr.variety, pr.unit, pr.quantity, pr.price, pr.description,
               u.user_id AS buyer_id, u.firstName, u.lastName, u.contact_number, u.address
        FROM post p
        JOIN products pr ON p.product_id=pr.product_id
        JOIN users u ON p.user_id=u.user_id
        WHERE p.post_type='wanted' AND u.role='buyer'
        ORDER BY p.created_at DESC LIMIT 6";
$stmt = $conn->prepare($sql); $stmt->execute(); $buyerPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Latest orders for this farmer (pending)
$sql = "SELECT o.order_id, o.quantity AS order_qty, o.total_price, o.status, o.created_at,
               pr.product_name, pr.unit,
               u.firstName, u.lastName, u.contact_number
        FROM orders o
        JOIN products pr ON o.product_id=pr.product_id
        JOIN users u ON o.buyer_id=u.user_id
        WHERE pr.user_id=?
        ORDER BY o.created_at DESC LIMIT 8";
$stmt = $conn->prepare($sql); $stmt->execute([$user_id]); $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $order_id  = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['pending','confirmed','completed','cancelled'];
    if ($order_id && in_array($newStatus, $allowed)) {
        $s = $conn->prepare("UPDATE orders o JOIN products pr ON o.product_id=pr.product_id SET o.status=? WHERE o.order_id=? AND pr.user_id=?");
        $s->execute([$newStatus, $order_id, $user_id]);
    }
    header("Location: farmer.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Dashboard · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#ffffff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;--harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;--danger-soft:#fbe3e0;--radius:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;}
a:focus-visible,button:focus-visible{outline:3px solid var(--harvest);outline-offset:2px;}

/* Navbar */
.navbar{position:sticky;top:0;width:100%;background:var(--forest);padding:0 32px;display:flex;align-items:center;z-index:1000;height:70px;}
.brand{color:#fff;font-size:20px;font-weight:700;display:flex;align-items:center;gap:10px;margin-right:40px;text-decoration:none;}
.nav{display:flex;align-items:center;gap:8px;flex:1;list-style:none;}
.nav a{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.78);text-decoration:none;padding:8px 16px;border-radius:99px;font-weight:500;transition:background .15s;}
.nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.nav a.active{background:rgba(255,255,255,.14);color:#fff;}
.nav a i{width:18px;text-align:center;}
.nav li.logout{margin-left:auto;}

/* Layout */
.main{margin:0 auto;padding:28px 32px 60px;max-width:1300px;}
.page-header{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:24px;}
.page-header h1{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-bottom:2px;}
.page-header p{color:var(--muted);}
.profile{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:6px 16px 6px 6px;font-weight:600;}
.avatar{width:38px;height:38px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;background:var(--leaf-soft);color:var(--forest);}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:20px 22px;display:flex;align-items:center;gap:16px;}
.stat-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.stat-icon.green{background:var(--leaf-soft);color:var(--forest);}
.stat-icon.gold{background:var(--harvest-soft);color:var(--harvest);}
.stat-icon.blue{background:#e8f0ff;color:#3b5bdb;}
.stat-icon.red{background:var(--danger-soft);color:var(--danger);}
.stat-number{font-size:28px;font-weight:700;line-height:1.1;letter-spacing:-.02em;}
.stat-label{font-size:14px;font-weight:600;margin-top:2px;}
.stat-sub{color:var(--muted);font-size:13px;}

/* Tabs */
.tabs{display:flex;gap:4px;margin-bottom:24px;border-bottom:2px solid var(--line);}
.tab-btn{background:none;border:none;padding:10px 20px;font:inherit;font-weight:600;font-size:15px;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;}
.tab-btn.active{color:var(--forest);border-bottom-color:var(--leaf);}
.tab-btn:hover{color:var(--ink);}
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* Section card */
.section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:24px;}
.section-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;}
.section-header h2{font-size:19px;font-weight:700;}
.btn-sm{display:inline-flex;align-items:center;gap:6px;background:var(--leaf);color:#fff;text-decoration:none;padding:8px 16px;border-radius:8px;font:inherit;font-weight:600;font-size:14px;cursor:pointer;border:none;transition:background .15s;}
.btn-sm:hover{background:var(--forest);}
.btn-sm.outline{background:#fff;color:var(--forest);border:1px solid var(--line);}
.btn-sm.outline:hover{background:var(--paper);}

/* Cards grid */
.grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
.grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;}
.request-card{border:1px solid var(--line);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:12px;background:#fff;transition:border-color .15s;}
.request-card:hover{border-color:#b4c5bc;}
.request-card h3{font-size:17px;font-weight:700;color:var(--forest);}
.tag{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;padding:3px 10px;border-radius:99px;}
.tag.buyer{background:var(--harvest-soft);color:#7a4f0e;}
.tag.sell{background:var(--leaf-soft);color:var(--forest);}
.variety-label{color:var(--muted);font-size:13.5px;}
.dl{display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:14px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;}
.dl dd.price-val{color:var(--forest);font-weight:700;}
.buyer-chip{display:flex;align-items:center;gap:8px;background:var(--paper);border-radius:8px;padding:8px 12px;font-weight:600;font-size:14px;}
.buyer-chip small{display:block;font-weight:400;color:var(--muted);font-size:12px;}
.av-xs{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;background:var(--harvest-soft);color:#7a4f0e;}

/* Orders table */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:14.5px;}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--line);}
th{font-weight:600;color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.04em;background:#fafbfa;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8faf8;}
.status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:12.5px;font-weight:600;}
.status-pending{background:#fff8e6;color:#92610a;}
.status-confirmed{background:#e8f0ff;color:#3b5bdb;}
.status-completed{background:var(--leaf-soft);color:var(--forest);}
.status-cancelled{background:var(--danger-soft);color:var(--danger);}
select.status-select{font:inherit;font-size:13px;padding:4px 8px;border:1px solid var(--line);border-radius:6px;background:#fff;cursor:pointer;}

/* Empty states */
.empty{text-align:center;padding:50px 20px;color:var(--muted);}
.empty i{font-size:28px;opacity:.45;margin-bottom:12px;}
.empty h3{color:var(--ink);font-size:17px;margin-bottom:6px;}

@media(max-width:1100px){.stats{grid-template-columns:repeat(2,1fr);}.grid-3{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.navbar{padding:0 16px;overflow-x:auto;}.nav{min-width:max-content;}.nav a span{display:none;}.main{padding:18px 14px 40px;}.page-header{flex-direction:column;align-items:flex-start;}.stats{grid-template-columns:1fr 1fr;}.grid-3,.grid-2{grid-template-columns:1fr;}}
@media(max-width:520px){.stats{grid-template-columns:1fr;}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
</head>
<body>

<nav class="navbar">
    <a class="brand" href="farmer.php"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="farmer.php" class="active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
        <li><a href="transaction_details.php"><i class="fa-solid fa-receipt"></i><span>My Transaction</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>

<main class="main">

    <!-- Page header -->
    <div class="page-header">
        <div>
            <h1>Farmer Dashboard</h1>
            <p>Welcome back, <strong><?= htmlspecialchars($farmerName) ?></strong>!</p>
        </div>
        <div class="profile">
            <span class="avatar"><?= strtoupper(mb_substr($farmerName,0,1)) ?></span>
            Farmer
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <div class="stat-number"><?= $myPostsCount ?></div>
                <div class="stat-label">Active Listings</div>
                <div class="stat-sub">Your sell posts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold"><i class="fa-solid fa-peso-sign"></i></div>
            <div>
                <div class="stat-number">₱<?= number_format($totalRevenue,2) ?></div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-sub">Completed orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-box-open"></i></div>
            <div>
                <div class="stat-number"><?= $salesCount ?></div>
                <div class="stat-label">Orders Completed</div>
                <div class="stat-sub">All time</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-clock"></i></div>
            <div>
                <div class="stat-number"><?= $pendingOrders ?></div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-sub">Awaiting action</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-requests">Buyer Requests</button>
        <button class="tab-btn" data-tab="tab-orders">Incoming Orders</button>
    </div>

    <!-- Tab: Buyer Requests -->
    <div class="tab-panel active" id="tab-requests">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-cart-shopping" style="color:var(--harvest);margin-right:8px;"></i>Latest Buyer Requests</h2>
                <a href="my_post.php" class="btn-sm outline"><i class="fa-solid fa-seedling"></i> My Listings</a>
            </div>

            <?php if (!empty($buyerPosts)): ?>
            <div class="grid-3">
                <?php foreach ($buyerPosts as $bp):
                    $initials = strtoupper(mb_substr($bp['firstName'],0,1).mb_substr($bp['lastName'],0,1));
                ?>
                <div class="request-card">
                    <div><span class="tag buyer"><i class="fa-solid fa-cart-shopping"></i> Wanted</span></div>
                    <h3><?= htmlspecialchars($bp['product_name']) ?></h3>
                    <?php if ($bp['variety']): ?><div class="variety-label"><?= htmlspecialchars($bp['variety']) ?></div><?php endif; ?>
                    <div class="buyer-chip">
                        <span class="av-xs"><?= $initials ?></span>
                        <div>
                            <small>Buyer</small>
                            <?= htmlspecialchars($bp['firstName'].' '.$bp['lastName']) ?>
                        </div>
                    </div>
                    <dl class="dl">
                        <dt>Qty needed</dt><dd><?= htmlspecialchars($bp['quantity']) ?> <?= htmlspecialchars($bp['unit']) ?></dd>
                        <dt>Budget</dt><dd class="price-val">₱<?= number_format($bp['price'],2) ?>/<?= htmlspecialchars($bp['unit']) ?></dd>
                        <dt>Location</dt><dd><?= htmlspecialchars($bp['address'] ?? '—') ?></dd>
                        <dt>Contact</dt><dd><?= htmlspecialchars($bp['contact_number'] ?? '—') ?></dd>
                    </dl>
                    <?php if ($bp['description']): ?>
                    <p style="font-size:13.5px;color:#44524a;background:var(--paper);border-radius:8px;padding:8px 10px;"><?= htmlspecialchars($bp['description']) ?></p>
                    <?php endif; ?>
                    <p style="font-size:13px;color:var(--muted);">Posted <?= date('M d, Y', strtotime($bp['created_at'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3>No buyer requests yet</h3>
                <p>Requests from buyers will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab: Incoming Orders -->
    <div class="tab-panel" id="tab-orders">
        <div class="section">
            <div class="section-header">
                <h2><i class="fa-solid fa-box-open" style="color:var(--forest);margin-right:8px;"></i>Incoming Orders</h2>
                <a href="sales.php" class="btn-sm"><i class="fa-solid fa-chart-line"></i> Full Sales Report</a>
            </div>

            <?php if (!empty($recentOrders)): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#Order</th>
                            <th>Product</th>
                            <th>Buyer</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $ord): ?>
                        <tr>
                            <td><strong>#<?= $ord['order_id'] ?></strong></td>
                            <td><?= htmlspecialchars($ord['product_name']) ?></td>
                            <td><?= htmlspecialchars($ord['firstName'].' '.$ord['lastName']) ?><br><small style="color:var(--muted)"><?= htmlspecialchars($ord['contact_number'] ?? '') ?></small></td>
                            <td><?= htmlspecialchars($ord['order_qty']) ?> <?= htmlspecialchars($ord['unit']) ?></td>
                            <td><strong>₱<?= number_format($ord['total_price'],2) ?></strong></td>
                            <td><span class="status-badge status-<?= htmlspecialchars($ord['status']) ?>"><?= ucfirst($ord['status']) ?></span></td>
                            <td>
                                <form method="POST" action="" style="display:inline-flex;gap:6px;align-items:center;">
                                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                                    <select name="new_status" class="status-select">
                                        <?php foreach (['pending','confirmed','completed','cancelled'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $ord['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button name="update_order" class="btn-sm" style="padding:5px 10px;font-size:13px;"><i class="fa-solid fa-check"></i></button>
                                </form>
                            </td>
                            <td style="color:var(--muted);font-size:13px;"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty">
                <i class="fa-solid fa-box-open"></i>
                <h3>No orders yet</h3>
                <p>When buyers place orders on your products, they will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});
</script>
</body>
</html>
