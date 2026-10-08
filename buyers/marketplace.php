<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'buyer') { header("Location: ../login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$buyerName = $_SESSION['user'] ?? 'Buyer';

$search    = trim($_GET['search'] ?? '');
$sortBy    = $_GET['sort'] ?? 'newest';

$where  = "p.post_type='sell' AND u.role='farmer' AND pr.status='available'";
$params = [];

if ($search !== '') {
    $where .= " AND (pr.product_name LIKE :s OR pr.variety LIKE :s OR u.firstName LIKE :s OR u.lastName LIKE :s)";
    $params[':s'] = '%'.$search.'%';
}

$order = match($sortBy) {
    'price_asc'  => 'pr.price ASC',
    'price_desc' => 'pr.price DESC',
    default      => 'p.created_at DESC',
};

$sql = "SELECT p.post_id, p.created_at, pr.product_id, pr.product_name, pr.variety, pr.unit, pr.price, pr.quantity, pr.description,
               u.user_id AS farmer_id, CONCAT(u.firstName,' ',u.lastName) AS farmer_name, u.contact_number, u.address
        FROM post p
        JOIN products pr ON p.product_id=pr.product_id
        JOIN users u ON p.user_id=u.user_id
        WHERE $where
        ORDER BY $order";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marketplace · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#fff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--leaf-soft:#dff0e5;--harvest:#b7791f;--harvest-soft:#fbeccb;--danger:#b3261e;--danger-soft:#fbe3e0;--radius:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;}
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
.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:24px;}
.search-wrap{position:relative;flex:1;min-width:220px;}
.search-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);}
.search-wrap input{width:100%;padding:10px 12px 10px 38px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff;font-size:15px;}
.search-wrap input:focus{outline:none;border-color:var(--leaf);box-shadow:0 0 0 3px rgba(47,125,79,.14);}
select.filter{padding:10px 14px;border:1px solid var(--line);border-radius:10px;font:inherit;background:#fff;cursor:pointer;}
.result-count{color:var(--muted);font-size:14px;align-self:center;}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.product-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:22px;display:flex;flex-direction:column;gap:12px;transition:border-color .2s,box-shadow .2s;}
.product-card:hover{border-color:#9fbda9;box-shadow:0 6px 20px rgba(31,77,58,.08);}
.product-card h2{font-size:18px;font-weight:700;color:var(--forest);}
.variety-tag{color:var(--muted);font-size:13.5px;}
.price-hero{font-size:22px;font-weight:700;color:var(--forest);}
.price-unit{font-size:14px;font-weight:400;color:var(--muted);}
.farmer-chip{display:flex;align-items:center;gap:8px;background:var(--paper);border-radius:8px;padding:9px 12px;font-weight:600;font-size:14px;}
.farmer-chip small{display:block;font-weight:400;color:var(--muted);font-size:12px;}
.av-xs{width:32px;height:32px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0;background:var(--leaf-soft);color:var(--forest);}
.dl{display:grid;grid-template-columns:auto 1fr;gap:5px 14px;font-size:14px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;}
.desc{font-size:13.5px;color:#44524a;background:var(--paper);border-radius:8px;padding:8px 10px;line-height:1.5;}
.qty-badge{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;background:var(--leaf-soft);color:var(--forest);padding:4px 11px;border-radius:99px;}
.order-btn{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:auto;padding:11px;background:var(--harvest);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;transition:background .15s;}
.order-btn:hover{background:#8c5c0e;}
.empty{text-align:center;padding:70px 20px;color:var(--muted);}
.empty i{font-size:36px;opacity:.35;margin-bottom:14px;}
.empty h2{color:var(--ink);font-size:20px;margin-bottom:8px;}
@media(max-width:1100px){.grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.navbar{padding:0 16px;overflow-x:auto;}.nav{min-width:max-content;}.nav a span{display:none;}.main{padding:18px 14px 40px;}.page-header{flex-direction:column;align-items:flex-start;}.grid{grid-template-columns:1fr;}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;}}
</style>
</head>
<body>
<nav class="navbar">
    <a class="brand" href="buyer.php"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="buyer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="marketplace.php" class="active"><i class="fa-solid fa-store"></i><span>Marketplace</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Requests</span></a></li>
        <li><a href="buyer_post.php"><i class="fa-solid fa-plus"></i><span>Post Request</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box-open"></i><span>My Orders</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>
<main class="main">
    <div class="page-header">
        <div><h1>Marketplace</h1><p>Browse fresh products listed by farmers.</p></div>
    </div>

    <form method="GET" class="toolbar">
        <div class="search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="text" name="search" placeholder="Search products, varieties, farmers..." value="<?= htmlspecialchars($search) ?>"></div>
        <select name="sort" class="filter" onchange="this.form.submit()">
            <option value="newest" <?= $sortBy==='newest'?'selected':'' ?>>Newest First</option>
            <option value="price_asc" <?= $sortBy==='price_asc'?'selected':'' ?>>Price: Low to High</option>
            <option value="price_desc" <?= $sortBy==='price_desc'?'selected':'' ?>>Price: High to Low</option>
        </select>
        <span class="result-count"><?= count($products) ?> product<?= count($products)!==1?'s':'' ?></span>
    </form>

    <?php if (!empty($products)): ?>
    <div class="grid">
        <?php foreach ($products as $fp):
            $parts   = preg_split('/\s+/', trim($fp['farmer_name']));
            $initials= strtoupper(mb_substr($parts[0]??'',0,1).mb_substr(end($parts)?:'',0,1));
        ?>
        <div class="product-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <h2><?= htmlspecialchars($fp['product_name']) ?></h2>
                <span class="qty-badge"><i class="fa-solid fa-boxes-stacked"></i><?= htmlspecialchars($fp['quantity']) ?> <?= htmlspecialchars($fp['unit']) ?></span>
            </div>
            <?php if ($fp['variety']): ?><div class="variety-tag"><?= htmlspecialchars($fp['variety']) ?></div><?php endif; ?>
            <div class="price-hero">₱<?= number_format($fp['price'],2) ?><span class="price-unit"> / <?= htmlspecialchars($fp['unit']) ?></span></div>
            <div class="farmer-chip">
                <span class="av-xs"><?= $initials ?></span>
                <div><small>Farmer</small><?= htmlspecialchars($fp['farmer_name']) ?></div>
            </div>
            <dl class="dl">
                <dt>Location</dt><dd><?= htmlspecialchars($fp['address']??'—') ?></dd>
                <dt>Contact</dt><dd><?= htmlspecialchars($fp['contact_number']??'—') ?></dd>
                <dt>Posted</dt><dd><?= date('M d, Y', strtotime($fp['created_at'])) ?></dd>
            </dl>
            <?php if ($fp['description']): ?><p class="desc"><?= htmlspecialchars($fp['description']) ?></p><?php endif; ?>
            <a href="place_order.php?product_id=<?= $fp['product_id'] ?>" class="order-btn"><i class="fa-solid fa-basket-shopping"></i> Place Order</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty"><i class="fa-solid fa-store"></i><h2>No products found</h2><p><?= $search ? 'Try a different search term.' : 'No products are available right now.' ?></p></div>
    <?php endif; ?>
</main>
</body>
</html>
