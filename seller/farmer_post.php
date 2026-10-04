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
.main { margin: 0 auto; padding: 28px 32px 48px; max-width: 1280px; }
.page { max-width: 780px; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; }
.profile i { width: 38px; height: 38px; border-radius: 50%; background: var(--leaf-soft); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; }

/* ---------- Form ---------- */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 28px; }
.form-card h2 { font-size: 19px; font-weight: 700; margin-bottom: 22px; }

.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14.5px; }
.message::before { font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }
.message.error   { background: var(--danger-soft); color: var(--danger); }
.message.error::before   { content: "\f06a"; }
.message.success { background: var(--leaf-soft); color: var(--forest); }
.message.success::before { content: "\f058"; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full { grid-column: 1 / -1; }

.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }

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

.form-group input[readonly] { background: var(--paper); color: var(--muted); cursor: default; }
.form-group textarea { min-height: 120px; resize: vertical; line-height: 1.5; }

.button-container { margin-top: 26px; display: flex; gap: 12px; }

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
    .form-group.full { grid-column: auto; }
    .button-container { flex-direction: column-reverse; }
    .btn, .cancel-btn { width: 100%; justify-content: center; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<!-- SIDEBAR -->
<nav class="navbar">

    <div class="logo">
        <h2><i class="fa-solid fa-seedling"></i> FarmMarket</h2>
        <p>Farm-to-Market System</p>
    </div>

    <ul class="nav">
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="farmer_post.php" class="active"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
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

</body>
</html>