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

$message = "";
$message_type = "";

$paymentOptions = [
    'cash'          => 'Cash',
    'gcash'         => 'GCash',
    'bank_transfer' => 'Bank transfer',
    'other'         => 'Other',
];

$unitOptions = [
    'kg'    => 'Kilogram (kg)',
    'sack'  => 'Sack',
    'ton'   => 'Ton',
    'piece' => 'Piece',
    'box'   => 'Box',
    'liter' => 'Liter',
];

$statusOptions = [
    'completed' => 'Completed',
    'pending'   => 'Pending',
    'cancelled' => 'Cancelled',
];

// LOAD THIS FARMER'S PRODUCTS (used for suggestions / auto-fill)
$myProducts = [];
try {
    $stmt = $conn->prepare("
        SELECT product_name, variety, unit, price
        FROM products
        WHERE user_id = :user_id
        ORDER BY product_name ASC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $myProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $myProducts = [];
}


// FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $buyer_name       = trim($_POST['buyer_name'] ?? '');
    $product          = trim($_POST['product'] ?? '');
    $quantity         = trim($_POST['quantity'] ?? '');
    $unit             = trim($_POST['unit'] ?? '');
    $price            = trim($_POST['price'] ?? '');
    $payment_method   = trim($_POST['payment_method'] ?? '');
    $status           = trim($_POST['status'] ?? '');
    $transaction_date = trim($_POST['transaction_date'] ?? '');
    $notes            = trim($_POST['notes'] ?? '');

    $dateObj = DateTime::createFromFormat('Y-m-d', $transaction_date);
    $dateValid = $dateObj && $dateObj->format('Y-m-d') === $transaction_date;

    // VALIDATION
    if (
        $buyer_name === '' ||
        $product === '' ||
        $quantity === '' ||
        $unit === '' ||
        $price === '' ||
        $payment_method === '' ||
        $transaction_date === ''
    ) {
        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($quantity) || $quantity <= 0) {
        $message = "Please enter a valid quantity.";
        $message_type = "error";

    } elseif (!is_numeric($price) || $price < 0) {
        $message = "Please enter a valid price.";
        $message_type = "error";

    } elseif (!array_key_exists($unit, $unitOptions)) {
        $message = "Please select a valid unit.";
        $message_type = "error";

    } elseif (!array_key_exists($payment_method, $paymentOptions)) {
        $message = "Please select a valid payment method.";
        $message_type = "error";

    } elseif (!array_key_exists($status, $statusOptions)) {
        $message = "Please select a valid status.";
        $message_type = "error";

    } elseif (!$dateValid) {
        $message = "Please enter a valid transaction date.";
        $message_type = "error";

    } elseif ($transaction_date > date('Y-m-d')) {
        $message = "Transaction date cannot be in the future.";
        $message_type = "error";

    } else {

        try {

            // Link to one of the farmer's own products if the name matches (optional)
            $stmtFind = $conn->prepare("
                SELECT product_id
                FROM products
                WHERE user_id = :user_id AND product_name = :product_name
                LIMIT 1
            ");
            $stmtFind->execute([
                ':user_id'      => $user_id,
                ':product_name' => $product,
            ]);
            $found = $stmtFind->fetch(PDO::FETCH_ASSOC);
            $product_id = $found ? $found['product_id'] : null;

            // Total is always computed on the server
            $total_amount = round((float)$quantity * (float)$price, 2);

            $sql = "
                INSERT INTO transactions
                (
                    user_id,
                    buyer_name,
                    product_name,
                    quantity,
                    unit,
                    price,
                    total_amount,
                    payment_method,
                    status,
                    transaction_date,
                    notes
                )
                VALUES
                (
                    :user_id,
                    :buyer_name,
                    :product_name,
                    :quantity,
                    :unit,
                    :price,
                    :total_amount,
                    :payment_method,
                    :status,
                    :transaction_date,
                    :notes
                )
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':user_id'          => $user_id,
                ':buyer_name'       => $buyer_name,
                ':product_name'     => $product,
                ':quantity'         => $quantity,
                ':unit'             => $unit,
                ':price'            => $price,
                ':total_amount'     => $total_amount,
                ':payment_method'   => $payment_method,
                ':status'           => $status,
                ':transaction_date' => $transaction_date,
                ':notes'            => $notes !== '' ? $notes : null,
            ]);

            // Reset the form after a successful save
            $_POST = [];
            $message = "Transaction recorded successfully.";
            $message_type = "success";

        } catch (PDOException $e) {
            $message = "Something went wrong while saving the transaction.";
            $message_type = "error";
        }
    }
}

// Helper for re-filling fields
function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1f4d3a">
<title>Record Transaction</title>

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
    --danger: #b3261e;
    --danger-soft: #fbe3e0;
    --radius: 12px;
    --pad-x: clamp(14px, 3vw, 32px);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }

body {
    font-family: 'Figtree', system-ui, sans-serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
    min-height: 100dvh;
    overflow-x: hidden;
}

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ---------- Navbar ---------- */
.navbar {
    position: sticky;
    top: 0;
    width: 100%;
    background: var(--forest);
    padding: 0 var(--pad-x);
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
    white-space: nowrap;
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
    white-space: nowrap;
}

.navbar a:hover { background: rgba(255, 255, 255, 0.08); color: #fff; }
.navbar a.active { background: rgba(255, 255, 255, 0.14); color: #fff; }
.navbar a i { width: 18px; text-align: center; }
.navbar .logout, .navbar li.logout { margin-left: auto; }

/* Hamburger (tablet/phone only) */
.nav-toggle {
    display: none;
    width: 44px;
    height: 44px;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border: none;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    margin-left: auto;
    flex-shrink: 0;
}
.nav-toggle:hover { background: rgba(255, 255, 255, 0.2); }

/* ---------- Layout ---------- */
.main { margin: 0 auto; padding: 22px var(--pad-x) 32px; max-width: 1600px; width: 100%; }
.page { max-width: 100%; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
.header h1 { font-size: clamp(22px, 3.4vw, 26px); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; flex-shrink: 0; }
.profile i { width: 38px; height: 38px; border-radius: 50%; background: var(--leaf-soft); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; }

/* ---------- Form ---------- */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 24px 28px; }
.form-card h2 { font-size: 19px; font-weight: 700; margin-bottom: 18px; }

.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14.5px; overflow-wrap: anywhere; }
.message::before { font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }
.message.error   { background: var(--danger-soft); color: var(--danger); }
.message.error::before   { content: "\f06a"; }
.message.success { background: var(--leaf-soft); color: var(--forest); }
.message.success::before { content: "\f058"; }

.form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px 20px; }
.form-group.span-2 { grid-column: span 2; }
.form-group { display: flex; flex-direction: column; min-width: 0; }
.form-group.full { grid-column: 1 / -1; }

.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }
.form-group .hint { color: var(--muted); font-size: 12.5px; margin-top: 5px; }

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    min-width: 0;
    padding: 11px 13px;
    border: 1px solid #cbd5cd;
    border-radius: 10px;
    font: inherit;
    background: #fff;
    color: var(--ink);
    transition: border-color .15s, box-shadow .15s;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

.form-group input[readonly] { background: var(--paper); color: var(--ink); font-weight: 700; cursor: default; }
.form-group textarea { min-height: 80px; resize: vertical; line-height: 1.5; }

.total-box input { font-size: 18px; color: var(--forest); }

.button-container { margin-top: 22px; justify-content: flex-end; display: flex; flex-wrap: wrap; gap: 12px; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 0; background: var(--leaf); color: #fff;
    padding: 11px 24px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn:hover { background: var(--forest); }

.cancel-btn {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fff; color: var(--ink); text-decoration: none;
    border: 1px solid var(--line); padding: 11px 24px; border-radius: 10px; font-weight: 600;
}
.cancel-btn:hover { background: var(--paper); }

/* =========================================================
   RESPONSIVE
   1024 tablet (2-col form) · 768 hamburger nav · 560 phone (1-col) · 400 small phone
   ========================================================= */

@media (max-width: 1024px) {
    .form-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Collapse navigation into a hamburger menu */
@media (max-width: 768px) {
    .navbar { height: 64px; }
    .brand, .logo { margin-right: 0; font-size: 18px; }
    .brand strong, .logo h2 { font-size: 18px; }
    .nav-toggle { display: inline-flex; }

    .navbar .nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        flex: none;
        flex-direction: column;
        align-items: stretch;
        gap: 2px;
        background: var(--forest);
        padding: 8px var(--pad-x) 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.12);
        box-shadow: 0 16px 24px rgba(0, 0, 0, 0.18);
        max-height: calc(100vh - 64px);
        max-height: calc(100dvh - 64px);
        overflow-y: auto;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: opacity .2s ease, transform .2s ease, visibility .2s;
    }
    .navbar .nav.open { opacity: 1; visibility: visible; transform: translateY(0); }

    .navbar .nav a { padding: 14px 16px; border-radius: 10px; font-size: 16px; }
    .navbar .nav li.logout { margin-left: 0; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.12); }

    .main { padding-top: 18px; padding-bottom: 40px; }
    .header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .form-card { padding: 20px; }
}

@media (max-width: 560px) {
    .form-grid { grid-template-columns: 1fr; gap: 16px; }
    .form-group.full, .form-group.span-2 { grid-column: auto; }

    /* 16px+ inputs stop iOS Safari from zooming on focus; taller targets for thumbs */
    .form-group input,
    .form-group select,
    .form-group textarea { font-size: 16px; min-height: 46px; }
    .form-group textarea { min-height: 100px; }
    .total-box input { font-size: 18px; }

    .button-container { flex-direction: column-reverse; }
    .btn, .cancel-btn { width: 100%; min-height: 48px; }
}

@media (max-width: 400px) {
    .form-card { padding: 16px; }
    .form-card h2 { font-size: 17px; margin-bottom: 14px; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
<link rel="stylesheet" href="theme.css">
  <script>try{var t=localStorage.getItem("fm-theme");if(!t&&window.matchMedia("(prefers-color-scheme: dark)").matches)t="dark";document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");}catch(e){}</script>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar" aria-label="Main navigation">

    <div class="logo">
        <h2><i class="fa-solid fa-seedling"></i> FarmMarket</h2>
        <p>Farm-to-Market System</p>
    </div>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
        <i class="fa-solid fa-bars"></i>
    </button>

    <ul class="nav" id="main-nav">
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="profile.php" class="acti"><i class="fa-solid fa-user"></i><span>My Profile</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php" class="active"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
        <li><a href="transaction_details.php"><i class="fa-solid fa-receipt"></i><span>My Transaction</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</nav>


<!-- MAIN CONTENT -->
<main class="main">
<div class="page">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>Record Transaction</h1>
            <p>Manually enter the details of a sale.</p>
        </div>

        <div class="profile"><i class="fa-solid fa-tractor"></i> Farmer</div>
    </div>


    <!-- FORM -->
    <div class="form-card">

        <h2>New transaction</h2>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>" role="alert">
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grid">

                <!-- BUYER -->
                <div class="form-group span-2">
                    <label for="buyer_name">Buyer name *</label>
                    <input
                        type="text"
                        id="buyer_name"
                        name="buyer_name"
                        placeholder="e.g. Juan Dela Cruz"
                        value="<?= old('buyer_name') ?>"
                        required
                    >
                </div>

                <!-- DATE -->
                <div class="form-group">
                    <label for="transaction_date">Transaction date *</label>
                    <input
                        type="date"
                        id="transaction_date"
                        name="transaction_date"
                        max="<?= date('Y-m-d') ?>"
                        value="<?= old('transaction_date', date('Y-m-d')) ?>"
                        required
                    >
                </div>

                <!-- STATUS -->
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['status'] ?? 'completed') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PRODUCT -->
                <div class="form-group span-2">
                    <label for="product">Product *</label>
                    <input
                        type="text"
                        id="product"
                        name="product"
                        list="product-list"
                        placeholder="Type or pick from your products"
                        autocomplete="off"
                        value="<?= old('product') ?>"
                        required
                    >
                    <datalist id="product-list">
                        <?php foreach ($myProducts as $p): ?>
                            <option value="<?= htmlspecialchars($p['product_name']) ?>">
                                <?= htmlspecialchars($p['variety'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                    <span class="hint">Picking one of your listed products fills in unit and price.</span>
                </div>

                <!-- QUANTITY -->
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 50"
                        value="<?= old('quantity') ?>"
                        required
                    >
                </div>

                <!-- UNIT -->
                <div class="form-group">
                    <label for="unit">Unit *</label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <?php foreach ($unitOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['unit'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PRICE -->
                <div class="form-group">
                    <label for="price">Price per unit *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 2500"
                        value="<?= old('price') ?>"
                        required
                    >
                </div>

                <!-- TOTAL (auto) -->
                <div class="form-group total-box">
                    <label for="total_display">Total amount</label>
                    <input type="text" id="total_display" value="₱0.00" readonly tabindex="-1">
                </div>

                <!-- PAYMENT METHOD -->
                <div class="form-group span-2">
                    <label for="payment_method">Payment method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select method</option>
                        <?php foreach ($paymentOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['payment_method'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- NOTES -->
                <div class="form-group full">
                    <label for="notes">Notes</label>
                    <textarea
                        id="notes"
                        name="notes"
                        placeholder="Delivery details, remarks, etc."
                    ><?= old('notes') ?></textarea>
                </div>

            </div>

            <!-- BUTTONS -->
            <div class="button-container">
                <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> Save transaction</button>
                <a href="transaction_details.php" class="cancel-btn">View all transactions</a>
            </div>

        </form>

    </div>

</div>
</main>

<script>
// The farmer's own products, used to auto-fill unit and price
const myProducts = <?= json_encode($myProducts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const qty     = document.getElementById('quantity');
const price   = document.getElementById('price');
const unit    = document.getElementById('unit');
const product = document.getElementById('product');
const total   = document.getElementById('total_display');

function updateTotal() {
    const t = (parseFloat(qty.value) || 0) * (parseFloat(price.value) || 0);
    total.value = '₱' + t.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

product.addEventListener('change', function () {
    const match = myProducts.find(p => p.product_name === product.value.trim());
    if (match) {
        if (!unit.value)  unit.value  = match.unit;
        if (!price.value) price.value = match.price;
        updateTotal();
    }
});

qty.addEventListener('input', updateTotal);
price.addEventListener('input', updateTotal);
updateTotal();

// Mobile menu
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
        if (window.innerWidth > 768) setMenu(false);
    });
})();
</script>
  <script src="theme.js"></script>
</body>
</html>