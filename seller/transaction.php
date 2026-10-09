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
                    product_id,
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
                    :product_id,
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
                ':product_id'       => $product_id,
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
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; }

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
.page { max-width: 100%; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
.header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; }
.profile i { width: 38px; height: 38px; border-radius: 50%; background: var(--leaf-soft); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; }

/* ---------- Form ---------- */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 24px 28px; }
.form-card h2 { font-size: 19px; font-weight: 700; margin-bottom: 18px; }

.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14.5px; }
.message::before { font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }
.message.error   { background: var(--danger-soft); color: var(--danger); }
.message.error::before   { content: "\f06a"; }
.message.success { background: var(--leaf-soft); color: var(--forest); }
.message.success::before { content: "\f058"; }

.form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px 20px; }
.form-group.span-2 { grid-column: span 2; }
.form-group { display: flex; flex-direction: column; }
.form-group.full { grid-column: 1 / -1; }

.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }
.form-group .hint { color: var(--muted); font-size: 12.5px; margin-top: 5px; }

.form-group input,
.form-group textarea,
.form-group select {
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

.button-container { margin-top: 22px; justify-content: flex-end; display: flex; gap: 12px; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 0; background: var(--leaf); color: #fff;
    padding: 11px 24px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn:hover { background: var(--forest); }

.cancel-btn {
    display: inline-flex; align-items: center;
    background: #fff; color: var(--ink); text-decoration: none;
    border: 1px solid var(--line); padding: 11px 24px; border-radius: 10px; font-weight: 600;
}
.cancel-btn:hover { background: var(--paper); }

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
    .form-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .navbar { padding: 0 16px; overflow-x: auto; }
    .brand, .logo { margin-right: 20px; white-space: nowrap; }
    .navbar nav, .navbar .nav { flex: 1; min-width: max-content; }
    .navbar a { white-space: nowrap; }
    .navbar a span { display: none; }
    .main { margin-left: 0; padding: 18px 14px 40px; }
    .header { flex-direction: column; align-items: flex-start; }
    .form-card { padding: 18px; }
}

@media (max-width: 560px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full, .form-group.span-2 { grid-column: auto; }
    .button-container { flex-direction: column-reverse; }
    .btn, .cancel-btn { width: 100%; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
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
</script>

</body>
</html>