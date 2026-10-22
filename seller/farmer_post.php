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

// GET LOGGED-IN FARMER

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";


// FORM SUBMISSION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // GET FORM VALUES

    $product = trim($_POST['product'] ?? '');
    $variety = trim($_POST['variety'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $description = trim($_POST['description'] ?? '');


    // VALIDATION

    if (
        empty($product) ||
        empty($unit) ||
        empty($price) ||
        empty($quantity)
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($price) || $price < 0) {

        $message = "Please enter a valid price.";
        $message_type = "error";

    } elseif (!is_numeric($quantity) || $quantity <= 0) {

        $message = "Please enter a valid quantity.";
        $message_type = "error";

    } else {


        try {

            // Start transaction
            $conn->beginTransaction();


            $sqlProduct = "
                INSERT INTO products
                (
                    user_id,
                    product_name,
                    variety,
                    unit,
                    price,
                    quantity,
                    description,
                    status
                )
                VALUES
                (
                    :user_id,
                    :product_name,
                    :variety,
                    :unit,
                    :price,
                    :quantity,
                    :description,
                    'available'
                )
            ";


            $stmtProduct = $conn->prepare($sqlProduct);


            $stmtProduct->execute([

                ':user_id' => $user_id,

                ':product_name' => $product,

                ':variety' => $variety !== ''
                    ? $variety
                    : null,

                ':unit' => $unit,

                ':price' => $price,

                ':quantity' => $quantity,

                ':description' => $description !== ''
                    ? $description
                    : null

            ]);


            $product_id = $conn->lastInsertId();


            // Farmer posts are automatically "sell"

            $sqlPost = "
                INSERT INTO post
                (
                    user_id,
                    product_id,
                    post_type
                )
                VALUES
                (
                    :user_id,
                    :product_id,
                    'sell'
                )
            ";


            $stmtPost = $conn->prepare($sqlPost);


            $stmtPost->execute([

                ':user_id' => $user_id,

                ':product_id' => $product_id

            ]);

            $conn->commit();

            header("Location: farmer.php");
            exit();


        } catch (PDOException $e) {

            // Cancel database changes if something fails
            if ($conn->inTransaction()) {

                $conn->rollBack();

            }


            $message = "Something went wrong: " . $e->getMessage();

            $message_type = "error";

        }

    }

}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1f4d3a">
<title>Create Farmer Post</title>

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

/* Hamburger (shown on tablets/phones only) */
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
.main { margin: 0 auto; padding: 28px var(--pad-x) 48px; max-width: 1280px; width: 100%; }
.page { max-width: 780px; width: 100%; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: clamp(22px, 3.4vw, 26px); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; flex-shrink: 0; }
.profile i { width: 38px; height: 38px; border-radius: 50%; background: var(--leaf-soft); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; }

/* ---------- Form ---------- */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 28px; }
.form-card h2 { font-size: 19px; font-weight: 700; margin-bottom: 22px; }

.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14.5px; overflow-wrap: anywhere; }
.message::before { font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }
.message.error   { background: var(--danger-soft); color: var(--danger); }
.message.error::before   { content: "\f06a"; }
.message.success { background: var(--leaf-soft); color: var(--forest); }
.message.success::before { content: "\f058"; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; min-width: 0; }
.form-group.full { grid-column: 1 / -1; }

.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }

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

.form-group input[readonly] { background: var(--paper); color: var(--muted); cursor: default; }
.form-group textarea { min-height: 120px; resize: vertical; line-height: 1.5; }

.button-container { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 12px; }

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
   768 tablet portrait / large phone · 560 phone · 400 small phone
   ========================================================= */

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
    .header { flex-direction: column; align-items: flex-start; gap: 12px; margin-bottom: 18px; }
    .form-card { padding: 20px; }
}

@media (max-width: 560px) {
    .form-grid { grid-template-columns: 1fr; gap: 16px; }
    .form-group.full { grid-column: auto; }

    /* 16px+ inputs stop iOS Safari from zooming the page on focus; taller targets for thumbs */
    .form-group input,
    .form-group select,
    .form-group textarea { font-size: 16px; min-height: 46px; }
    .form-group textarea { min-height: 120px; }

    .button-container { flex-direction: column-reverse; }
    .btn, .cancel-btn { width: 100%; min-height: 48px; }
}

@media (max-width: 400px) {
    .form-card { padding: 16px; }
    .form-card h2 { font-size: 17px; margin-bottom: 18px; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
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
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php" class="active"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
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
            <h1>Create Farmer Post</h1>
            <p>Post your available agricultural products.</p>
        </div>

        <div class="profile"><i class="fa-solid fa-tractor"></i> Farmer</div>
    </div>


    <!-- FORM -->
    <div class="form-card">

        <h2>Add new product</h2>

        <?php if (isset($message) && $message !== ""): ?>
            <div class="message <?= htmlspecialchars($message_type ?? '') ?>" role="alert">
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grid">

                <!-- PRODUCT -->
                <div class="form-group">
                    <label for="product">Product *</label>
                    <input
                        type="text"
                        id="product"
                        name="product"
                        placeholder="e.g. Rice"
                        value="<?= htmlspecialchars($_POST['product'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- VARIETY -->
                <div class="form-group">
                    <label for="variety">Variety</label>
                    <input
                        type="text"
                        id="variety"
                        name="variety"
                        placeholder="e.g. Dinorado"
                        value="<?= htmlspecialchars($_POST['variety'] ?? '') ?>"
                    >
                </div>

                <!-- QUANTITY -->
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 50"
                        value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- UNIT -->
                <div class="form-group">
                    <label for="unit">Unit *</label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <?php
                        $unitOptions = [
                            'kg'    => 'Kilogram (kg)',
                            'sack'  => 'Sack',
                            'ton'   => 'Ton',
                            'piece' => 'Piece',
                            'box'   => 'Box',
                            'liter' => 'Liter',
                        ];
                        foreach ($unitOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['unit'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PRICE -->
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 2500"
                        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- POST TYPE -->
                <div class="form-group">
                    <label for="post_type_display">Post type</label>
                    <input
                        type="text"
                        id="post_type_display"
                        value="Selling"
                        readonly
                    >
                </div>

                <!-- DESCRIPTION -->
                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe your product..."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

            </div>

            <!-- BUTTONS -->
            <div class="button-container">
                <button type="submit" class="btn"><i class="fa-solid fa-seedling"></i> Create post</button>
                <a href="farmer.php" class="cancel-btn">Cancel</a>
            </div>

        </form>

    </div>

</div>
</main>

<script>
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

</body>
</html>