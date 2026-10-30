<?php

session_start();

require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}

// GET LOGGED-IN USER

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// CSRF TOKEN

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// FLASH MESSAGE (shown once after redirect)

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function csrf_ok(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}


// UPDATE PRODUCT (quantity / price / status / description)

if (isset($_POST['update_post'])) {

    $post_id     = $_POST['post_id'] ?? '';
    $quantity    = trim($_POST['quantity'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $status      = $_POST['status'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    if (!csrf_ok()) {
        $errors[] = 'Invalid request. Please refresh the page and try again.';
    }

    if (empty($post_id)) {
        $errors[] = 'Post not found.';
    }

    if ($quantity === '' || !is_numeric($quantity) || $quantity < 0) {
        $errors[] = 'Quantity must be a number that is 0 or higher.';
    }

    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = 'Price must be a number that is 0 or higher.';
    }

    if (!in_array($status, ['available', 'unavailable'], true)) {
        $errors[] = 'Invalid status.';
    }

    if (empty($errors)) {

        // If the stock is 0, the product can't be available
        if ((float) $quantity <= 0) {
            $status = 'unavailable';
        }

        try {

            $conn->beginTransaction();

            // MAKE SURE THIS POST BELONGS TO THE LOGGED-IN USER

            $stmt = $conn->prepare("
                SELECT product_id
                FROM post
                WHERE post_id = :post_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':post_id' => $post_id,
                ':user_id' => $user_id
            ]);

            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post) {

                // UPDATE THE PRODUCT

                $stmtUpdate = $conn->prepare("
                    UPDATE products
                    SET quantity    = :quantity,
                        price       = :price,
                        status      = :status,
                        description = :description
                    WHERE product_id = :product_id
                    AND user_id = :user_id
                ");

                $stmtUpdate->execute([
                    ':quantity'    => $quantity,
                    ':price'       => $price,
                    ':status'      => $status,
                    ':description' => $description,
                    ':product_id'  => $post['product_id'],
                    ':user_id'     => $user_id
                ]);

                $conn->commit();

                $_SESSION['flash'] = ['type' => 'success', 'text' => 'Product updated successfully.'];

            } else {

                $conn->rollBack();

                $_SESSION['flash'] = ['type' => 'error', 'text' => 'Post not found.'];

            }

        } catch (PDOException $e) {

            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $_SESSION['flash'] = ['type' => 'error', 'text' => 'Something went wrong while updating. Please try again.'];

        }

    } else {

        $_SESSION['flash'] = ['type' => 'error', 'text' => implode(' ', $errors)];

    }

    // PRG: avoid resubmitting the form on refresh
    header("Location: my_post.php");
    exit();

}


// DELETE PRODUCT / POST

if (isset($_POST['delete_post'])) {

    $post_id = $_POST['post_id'] ?? '';

    if (!empty($post_id) && csrf_ok()) {

        try {

            // Start transaction
            $conn->beginTransaction();


            // GET PRODUCT ID

            $sql = "
                SELECT product_id
                FROM post
                WHERE post_id = :post_id
                AND user_id = :user_id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':post_id' => $post_id,
                ':user_id' => $user_id
            ]);

            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            // CHECK IF POST EXISTS

            if ($post) {

                $product_id = $post['product_id'];


                // DELETE POST FIRST

                $sqlDeletePost = "
                    DELETE FROM post
                    WHERE post_id = :post_id
                    AND user_id = :user_id
                ";

                $stmtDeletePost = $conn->prepare($sqlDeletePost);

                $stmtDeletePost->execute([
                    ':post_id' => $post_id,
                    ':user_id' => $user_id
                ]);

                // DELETE PRODUCT

                $sqlDeleteProduct = "
                    DELETE FROM products
                    WHERE product_id = :product_id
                    AND user_id = :user_id
                ";

                $stmtDeleteProduct = $conn->prepare($sqlDeleteProduct);

                $stmtDeleteProduct->execute([
                    ':product_id' => $product_id,
                    ':user_id' => $user_id
                ]);


                // COMMIT

                $conn->commit();

                $_SESSION['flash'] = ['type' => 'success', 'text' => 'Product deleted.'];

                // RETURN TO MY POSTS

                header("Location: my_post.php");
                exit();


            } else {

                $conn->rollBack();

            }


        } catch (PDOException $e) {

            if ($conn->inTransaction()) {

                $conn->rollBack();

            }

        }

    }

}

// GET ONLY THE LOGGED-IN USER'S POSTS

$sql = "
    SELECT
        post.post_id,
        post.user_id,
        post.product_id,
        post.post_type,
        post.created_at,

        products.product_name,
        products.variety,
        products.unit,
        products.price,
        products.quantity,
        products.description,
        products.status

    FROM post

    INNER JOIN products
        ON post.product_id = products.product_id

    WHERE post.user_id = :user_id

    ORDER BY post.created_at DESC
";


$stmt = $conn->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id
]);

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1f4d3a">
<title>My Posts</title>

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
    --harvest-soft: #fbeccb;
    --danger: #c0392b;
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

a:focus-visible, button:focus-visible, summary:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible { outline: 3px solid var(--harvest); outline-offset: 2px; }

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

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: clamp(22px, 3.4vw, 26px); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.btn-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    background: var(--leaf); color: #fff; text-decoration: none;
    border: 0; cursor: pointer; font: inherit;
    border-radius: 10px; padding: 10px 18px; font-weight: 600;
    transition: background .15s;
    white-space: nowrap;
}
.btn-primary:hover { background: var(--forest); }

/* ---------- Alerts ---------- */
.alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; overflow-wrap: anywhere; }
.alert.success { background: var(--leaf-soft); color: var(--forest); }
.alert.error   { background: var(--danger-soft); color: var(--danger); }

/* ---------- Posts ---------- */
/* Auto-fits: 3 columns on desktop, 2 on tablet, 1 on phones */
.posts { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 18px; align-items: start; }

.post-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column; min-width: 0; }

.card-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }

.post-type { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.sell   { background: var(--leaf-soft); color: var(--forest); }
.wanted { background: var(--harvest-soft); color: #7a4f0e; }

.status { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.available   { background: var(--leaf-soft); color: var(--forest); }
.unavailable { background: var(--danger-soft); color: var(--danger); }

.post-card h2 { font-size: 20px; font-weight: 700; line-height: 1.25; overflow-wrap: anywhere; }
.variety { color: var(--muted); font-size: 14px; margin-top: 2px; overflow-wrap: anywhere; }

.details { display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; margin-top: 16px; font-size: 14.5px; }
.details dt { color: var(--muted); }
.details dd { font-weight: 500; overflow-wrap: anywhere; min-width: 0; }
.details dd.price { color: var(--forest); font-weight: 700; }

.description { margin-top: 14px; padding: 10px 12px; background: var(--paper); border-radius: 8px; font-size: 14px; color: #44524a; line-height: 1.5; overflow-wrap: anywhere; }

/* ---------- Update box ---------- */
.update-box { margin-top: 18px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }

.update-box summary {
    list-style: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 14px; font-weight: 600; color: var(--forest);
    border-radius: 10px; user-select: none;
    min-height: 44px;
}
.update-box summary::-webkit-details-marker { display: none; }
.update-box summary:hover { background: var(--leaf-soft); }
.update-box[open] summary { border-bottom: 1px solid var(--line); border-radius: 10px 10px 0 0; background: var(--leaf-soft); }

.update-form { padding: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.update-form .field { display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.update-form .field.full { grid-column: 1 / -1; }
.update-form label { font-size: 13px; font-weight: 600; color: var(--muted); }

.update-form input,
.update-form select,
.update-form textarea {
    width: 100%; min-width: 0; font: inherit; font-size: 14.5px; color: var(--ink);
    padding: 9px 10px; border: 1px solid var(--line); border-radius: 8px; background: #fff;
}
.update-form textarea { resize: vertical; min-height: 70px; }
.update-form .btn-primary { grid-column: 1 / -1; }

.delete-form { margin-top: 12px; }

.delete-btn {
    width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    border: 1px solid #efc3bd; background: #fff; color: var(--danger);
    padding: 10px 14px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
    min-height: 44px;
}
.delete-btn:hover { background: var(--danger); border-color: var(--danger); color: #fff; }

/* ---------- Empty state ---------- */
.no-post { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); text-align: center; padding: 60px 24px; }
.no-post i.big { font-size: 32px; color: var(--muted); opacity: .55; margin-bottom: 14px; }
.no-post h2 { font-size: 20px; margin-bottom: 6px; }
.no-post p { color: var(--muted); margin-bottom: 20px; }

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
}

@media (max-width: 560px) {
    .header .btn-primary { width: 100%; padding: 12px 18px; }
    .post-card { padding: 16px; }
    .post-card h2 { font-size: 18px; }
    .no-post { padding: 40px 18px; }

    /* 16px inputs stop iOS Safari from zooming on focus; taller targets for thumbs */
    .update-form input,
    .update-form select,
    .update-form textarea { font-size: 16px; min-height: 44px; }
    .update-form textarea { min-height: 90px; }
    .update-form .btn-primary { min-height: 46px; }
}

@media (max-width: 400px) {
    .update-form { grid-template-columns: 1fr; padding: 12px; }
    .details { grid-template-columns: 1fr; gap: 2px; }
    .details dd { margin-bottom: 8px; }
    .details dd:last-child { margin-bottom: 0; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
<link rel="stylesheet" href="theme.css">
  <script>try{var t=localStorage.getItem("fm-theme");if(!t&&window.matchMedia("(prefers-color-scheme: dark)").matches)t="dark";document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");}catch(e){}</script>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar" aria-label="Main navigation">

    <div class="brand">
        <strong><i class="fa-solid fa-seedling"></i> FarmMarket</strong>
        <small>Farm-to-Market System</small>
    </div>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
        <i class="fa-solid fa-bars"></i>
    </button>

    <ul class="nav" id="main-nav">
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="profile.php" class="acti"><i class="fa-solid fa-user"></i><span>My Profile</span></a></li>
        <li><a href="my_post.php" class="active"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
        <li><a href="transaction_details.php"><i class="fa-solid fa-receipt"></i><span>My Transaction</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</nav>

<main class="main">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>My Posts</h1>
            <p>
                <?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?> · View and manage your listings.
            </p>
        </div>

        <a href="farmer_post.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create post</a>
    </div>

    <!-- FLASH MESSAGE -->
    <?php if ($flash): ?>
        <div class="alert <?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
            <?= htmlspecialchars($flash['text']) ?>
        </div>
    <?php endif; ?>


    <!-- POSTS -->
    <?php if (count($posts) > 0): ?>

        <div class="posts">

            <?php foreach ($posts as $post): ?>

                <div class="post-card">

                    <div class="card-top">

                        <!-- POST TYPE -->
                        <?php if ($post['post_type'] === 'sell'): ?>
                            <span class="post-type sell"><i class="fa-solid fa-wheat-awn"></i> Selling</span>
                        <?php else: ?>
                            <span class="post-type wanted"><i class="fa-solid fa-cart-shopping"></i> Wanted</span>
                        <?php endif; ?>

                        <!-- STATUS -->
                        <?php if ($post['status'] === 'available'): ?>
                            <span class="status available">Available</span>
                        <?php else: ?>
                            <span class="status unavailable">Unavailable</span>
                        <?php endif; ?>

                    </div>

                    <!-- PRODUCT NAME -->
                    <h2><?= htmlspecialchars($post['product_name']) ?></h2>

                    <!-- VARIETY -->
                    <?php if (!empty($post['variety'])): ?>
                        <div class="variety"><?= htmlspecialchars($post['variety']) ?></div>
                    <?php endif; ?>

                    <dl class="details">
                        <!-- QUANTITY -->
                        <dt>Quantity</dt>
                        <dd><?= htmlspecialchars($post['quantity']) ?> <?= htmlspecialchars($post['unit']) ?></dd>

                        <!-- PRICE -->
                        <dt>Price</dt>
                        <dd class="price">₱<?= number_format($post['price'], 2) ?></dd>

                        <!-- POSTED DATE -->
                        <dt>Posted</dt>
                        <dd><?= htmlspecialchars(date('M d, Y', strtotime($post['created_at']))) ?></dd>
                    </dl>

                    <!-- DESCRIPTION -->
                    <?php if (!empty($post['description'])): ?>
                        <p class="description"><?= nl2br(htmlspecialchars($post['description'])) ?></p>
                    <?php endif; ?>

                    <!-- UPDATE PRODUCT -->
                    <details class="update-box">

                        <summary><i class="fa-solid fa-pen-to-square"></i> Update product</summary>

                        <form method="POST" action="" class="update-form">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['post_id']) ?>">

                            <div class="field">
                                <label for="quantity-<?= (int) $post['post_id'] ?>">Quantity (<?= htmlspecialchars($post['unit']) ?>)</label>
                                <input
                                    type="number"
                                    id="quantity-<?= (int) $post['post_id'] ?>"
                                    name="quantity"
                                    min="0"
                                    step="any"
                                    inputmode="decimal"
                                    value="<?= htmlspecialchars($post['quantity']) ?>"
                                    required
                                >
                            </div>

                            <div class="field">
                                <label for="price-<?= (int) $post['post_id'] ?>">Price (₱)</label>
                                <input
                                    type="number"
                                    id="price-<?= (int) $post['post_id'] ?>"
                                    name="price"
                                    min="0"
                                    step="0.01"
                                    inputmode="decimal"
                                    value="<?= htmlspecialchars($post['price']) ?>"
                                    required
                                >
                            </div>

                            <div class="field full">
                                <label for="status-<?= (int) $post['post_id'] ?>">Status</label>
                                <select id="status-<?= (int) $post['post_id'] ?>" name="status">
                                    <option value="available" <?= $post['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                    <option value="unavailable" <?= $post['status'] !== 'available' ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="description-<?= (int) $post['post_id'] ?>">Description</label>
                                <textarea
                                    id="description-<?= (int) $post['post_id'] ?>"
                                    name="description"
                                ><?= htmlspecialchars($post['description'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" name="update_post" class="btn-primary">
                                <i class="fa-solid fa-floppy-disk"></i> Save changes
                            </button>

                        </form>

                    </details>

                    <!-- DELETE BUTTON -->
                    <div class="delete-form">

                        <form
                            method="POST"
                            action=""
                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                        >

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                            <input
                                type="hidden"
                                name="post_id"
                                value="<?= htmlspecialchars($post['post_id']) ?>"
                            >

                            <button type="submit" name="delete_post" class="delete-btn">
                                <i class="fa-solid fa-trash"></i> Delete product
                            </button>

                        </form>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <!-- NO POSTS -->
        <div class="no-post">

            <i class="fa-solid fa-clipboard-list big"></i>

            <h2>You don't have any posts yet.</h2>

            <p>Create a post and it will appear here.</p>

            <a href="farmer_post.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create post</a>

        </div>

    <?php endif; ?>

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
  <script src="theme.js"></script>
</body>
</html>