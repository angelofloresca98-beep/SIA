<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role'])) { header("Location: ../login.php"); exit(); }
if ($_SESSION['role'] !== 'buyer') { header("Location: ../login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$buyerName = $_SESSION['user'] ?? 'Buyer';

$product_id = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
if (!$product_id) { header("Location: marketplace.php"); exit(); }

// Fetch product + farmer info
$stmt = $conn->prepare("SELECT pr.*, CONCAT(u.firstName,' ',u.lastName) AS farmer_name, u.contact_number, u.address, u.user_id AS farmer_id
                        FROM products pr JOIN users u ON pr.user_id=u.user_id WHERE pr.product_id=? AND pr.status='available' LIMIT 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) { header("Location: marketplace.php"); exit(); }

$message = ''; $message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $qty = (float)($_POST['quantity'] ?? 0);
    if ($qty <= 0) {
        $message = 'Please enter a valid quantity.'; $message_type = 'error';
    } elseif ($qty > $product['quantity']) {
        $message = 'Requested quantity exceeds available stock.'; $message_type = 'error';
    } else {
        $total = $qty * $product['price'];
        try {
            $conn->beginTransaction();
            // Insert order
            $s = $conn->prepare("INSERT INTO orders (buyer_id, product_id, quantity, total_price, status) VALUES (?,?,?,?,'pending')");
            $s->execute([$user_id, $product_id, $qty, $total]);
            // Deduct stock
            $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?")->execute([$qty, $product_id]);
            // Mark sold out if zero
            $conn->prepare("UPDATE products SET status = IF(quantity <= 0, 'unavailable', 'available') WHERE product_id = ?")->execute([$product_id]);
            $conn->commit();
            $_SESSION['order_success'] = "Order placed! ₱".number_format($total,2)." for ".$qty." ".$product['unit']." of ".$product['product_name'].".";
            header("Location: orders.php"); exit();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $message = 'Error: '.$e->getMessage(); $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Place Order · Farm to Market</title>
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
.main{margin:0 auto;padding:36px 32px 60px;max-width:860px;}
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--forest);font-weight:600;text-decoration:none;margin-bottom:20px;font-size:14.5px;}
.back-link:hover{text-decoration:underline;}
.layout{display:grid;grid-template-columns:1fr 380px;gap:24px;}
.product-info{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:28px;}
.product-info h1{font-size:24px;font-weight:700;color:var(--forest);margin-bottom:4px;}
.variety{color:var(--muted);font-size:14px;margin-bottom:16px;}
.price-big{font-size:30px;font-weight:700;color:var(--harvest);margin-bottom:20px;}
.price-big span{font-size:16px;font-weight:500;color:var(--muted);}
.dl{display:grid;grid-template-columns:auto 1fr;gap:8px 16px;font-size:14.5px;}
.dl dt{color:var(--muted);}
.dl dd{font-weight:500;}
.farmer-box{display:flex;align-items:center;gap:10px;background:var(--paper);border-radius:10px;padding:12px 14px;margin-top:20px;}
.av{width:40px;height:40px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;background:var(--leaf-soft);color:var(--forest);}
.farmer-box strong{display:block;font-size:15px;}
.farmer-box small{color:var(--muted);font-size:13px;}
.order-form{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:28px;display:flex;flex-direction:column;gap:20px;align-self:start;}
.order-form h2{font-size:19px;font-weight:700;}
.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:10px;font-weight:500;font-size:14.5px;}
.alert.error{background:var(--danger-soft);color:var(--danger);}
label{font-weight:600;font-size:14px;display:block;margin-bottom:6px;}
input[type=number]{width:100%;padding:11px 13px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:15px;background:#fff;}
input[type=number]:focus{outline:none;border-color:var(--leaf);box-shadow:0 0 0 3px rgba(47,125,79,.14);}
.hint{color:var(--muted);font-size:13px;margin-top:4px;}
.total-preview{background:var(--paper);border-radius:10px;padding:14px;font-size:15px;}
.total-preview .lbl{color:var(--muted);margin-bottom:4px;}
.total-val{font-size:22px;font-weight:700;color:var(--forest);}
.btn-order{width:100%;padding:13px;background:var(--harvest);color:#fff;border:none;border-radius:10px;font:inherit;font-weight:700;font-size:16px;cursor:pointer;transition:background .15s;}
.btn-order:hover{background:#8c5c0e;}
.btn-cancel{width:100%;padding:11px;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:10px;font:inherit;font-weight:600;text-align:center;text-decoration:none;display:block;transition:background .15s;}
.btn-cancel:hover{background:var(--paper);}
@media(max-width:860px){.layout{grid-template-columns:1fr;}.main{padding:20px 16px 40px;}}
</style>
</head>
<body>
<nav class="navbar">
    <a class="brand" href="buyer.php"><i class="fa-solid fa-seedling"></i> FarmMarket</a>
    <ul class="nav">
        <li><a href="buyer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="marketplace.php" class="active"><i class="fa-solid fa-store"></i><span>Marketplace</span></a></li>
        <li><a href="orders.php"><i class="fa-solid fa-box-open"></i><span>My Orders</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>
<main class="main">
    <a href="marketplace.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Marketplace</a>

    <div class="layout">
        <!-- Product Info -->
        <div class="product-info">
            <h1><?= htmlspecialchars($product['product_name']) ?></h1>
            <?php if ($product['variety']): ?><div class="variety"><?= htmlspecialchars($product['variety']) ?></div><?php endif; ?>
            <div class="price-big">₱<?= number_format($product['price'],2) ?><span> / <?= htmlspecialchars($product['unit']) ?></span></div>
            <dl class="dl">
                <dt>Available stock</dt><dd><?= htmlspecialchars($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?></dd>
                <dt>Location</dt><dd><?= htmlspecialchars($product['address']??'—') ?></dd>
            </dl>
            <?php if ($product['description']): ?>
            <p style="margin-top:16px;font-size:14px;color:#44524a;line-height:1.55;"><?= htmlspecialchars($product['description']) ?></p>
            <?php endif; ?>
            <?php
                $fparts  = preg_split('/\s+/', trim($product['farmer_name']));
                $fInit   = strtoupper(mb_substr($fparts[0]??'',0,1).mb_substr(end($fparts)?:'',0,1));
            ?>
            <div class="farmer-box">
                <span class="av"><?= $fInit ?></span>
                <div><strong><?= htmlspecialchars($product['farmer_name']) ?></strong><small><?= htmlspecialchars($product['contact_number']??'') ?></small></div>
            </div>
        </div>

        <!-- Order Form -->
        <div class="order-form">
            <h2><i class="fa-solid fa-basket-shopping" style="color:var(--harvest);margin-right:6px;"></i>Place Your Order</h2>

            <?php if ($message): ?>
            <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" id="orderForm">
                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                <div>
                    <label for="quantity">Quantity (<?= htmlspecialchars($product['unit']) ?>) *</label>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="0.01" max="<?= $product['quantity'] ?>" placeholder="e.g. 5" required>
                    <div class="hint">Max available: <?= htmlspecialchars($product['quantity']) ?> <?= htmlspecialchars($product['unit']) ?></div>
                </div>
                <div class="total-preview">
                    <div class="lbl">Estimated Total</div>
                    <div class="total-val" id="totalVal">₱0.00</div>
                </div>
                <button type="submit" name="place_order" class="btn-order"><i class="fa-solid fa-check"></i> Confirm Order</button>
                <a href="marketplace.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</main>
<script>
const qtyInput = document.getElementById('quantity');
const totalVal = document.getElementById('totalVal');
const price    = <?= $product['price'] ?>;
qtyInput.addEventListener('input', () => {
    const q = parseFloat(qtyInput.value) || 0;
    totalVal.textContent = '₱' + (q * price).toLocaleString('en-PH', {minimumFractionDigits:2,maximumFractionDigits:2});
});
</script>
</body>
</html>
