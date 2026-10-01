<?php

session_start();
require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'farmer') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$farmerName = $_SESSION['user'] ?? 'Farmer';

// GET LATEST BUYER REQUESTS

$sql = "
    SELECT
        post.post_id,
        post.post_type,
        post.created_at,

        products.product_id,
        products.product_name,
        products.variety,
        products.unit,
        products.quantity,
        products.price,
        products.description,

        users.user_id AS buyer_user_id,
        users.firstName,
        users.lastName,
        users.contact_number,
        users.address

    FROM post

    INNER JOIN products
        ON post.product_id = products.product_id

    INNER JOIN users
        ON post.user_id = users.user_id

    WHERE post.post_type = 'wanted'
      AND users.role = 'buyer'

    ORDER BY post.created_at DESC

    LIMIT 6
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$buyerPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// COUNT FARMER'S POSTS

$sqlMyPosts = "
    SELECT COUNT(*)
    FROM post
    WHERE user_id = ?
      AND post_type = 'sell'
";

$stmtMyPosts = $conn->prepare($sqlMyPosts);
$stmtMyPosts->execute([$user_id]);

$myPostsCount = $stmtMyPosts->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmer Dashboard</title>

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
    --radius: 12px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; }

a:focus-visible, button:focus-visible { outline: 3px solid var(--harvest); outline-offset: 2px; }

/* ---------- Sidebar ---------- */
.sidebar { position: fixed; inset: 0 auto 0 0; width: 240px; background: var(--forest); padding: 26px 16px; display: flex; flex-direction: column; z-index: 10; }
.brand { color: #fff; padding: 0 12px 28px; }
.brand strong { display: flex; align-items: center; gap: 10px; font-size: 20px; letter-spacing: -0.01em; }
.brand small { display: block; margin-top: 4px; padding-left: 30px; color: rgba(255,255,255,.6); font-size: 13px; }

.nav { list-style: none; flex: 1; display: flex; flex-direction: column; }
.nav a { display: flex; align-items: center; gap: 14px; color: rgba(255,255,255,.78); text-decoration: none; padding: 12px 14px; border-radius: 10px; margin-bottom: 4px; font-weight: 500; }
.nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
.nav a.active { background: rgba(255,255,255,.14); color: #fff; }
.nav a i { width: 18px; text-align: center; }
.nav .logout { margin-top: auto; border-top: 1px solid rgba(255,255,255,.12); padding-top: 12px; }

/* ---------- Layout ---------- */
.main { margin-left: 240px; padding: 28px 32px 48px; max-width: 1280px; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }
.header p b { color: var(--ink); font-weight: 600; }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; }

.avatar { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
.avatar.farmer { background: var(--leaf-soft); color: var(--forest); }
.avatar.buyer  { background: var(--harvest-soft); color: #7a4f0e; }

/* ---------- Summary ---------- */
.dashboard-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px; max-width: 720px; }
.dashboard-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 22px 24px; display: flex; align-items: center; gap: 18px; }
.dashboard-card .icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
.dashboard-card.posts .icon    { background: var(--leaf-soft); color: var(--forest); }
.dashboard-card.requests .icon { background: var(--harvest-soft); color: #7a4f0e; }
.dashboard-card .number { font-size: 32px; font-weight: 700; line-height: 1.1; letter-spacing: -0.02em; }
.dashboard-card h3 { font-size: 15px; font-weight: 600; margin-top: 2px; }
.dashboard-card p { color: var(--muted); font-size: 13.5px; }

/* ---------- Requests ---------- */
.section { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 26px; }
.section-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 20px; }
.section-header h2 { font-size: 20px; font-weight: 700; }
.view-all { color: var(--forest); text-decoration: none; font-weight: 600; }
.view-all:hover { text-decoration: underline; }

.requests-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }

.request-card { border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column; background: #fff; }
.request-card:hover { border-color: #c9d5cb; }

.request-card h2 { font-size: 19px; font-weight: 700; color: var(--forest); line-height: 1.25; }
.variety { color: var(--muted); font-size: 14px; margin-top: 2px; }

.buyer-name { display: flex; align-items: center; gap: 10px; margin: 16px 0; padding: 10px 12px; background: var(--paper); border-radius: 10px; font-weight: 600; }
.buyer-name small { display: block; font-weight: 400; color: var(--muted); font-size: 12.5px; line-height: 1.2; }

.details { display: grid; grid-template-columns: auto 1fr; gap: 8px 14px; font-size: 14.5px; }
.details dt { color: var(--muted); }
.details dd { font-weight: 500; overflow-wrap: anywhere; }
.details dd.price { color: var(--forest); font-weight: 700; }

.description { margin-top: 14px; padding: 10px 12px; background: var(--paper); border-radius: 8px; font-size: 14px; color: #44524a; line-height: 1.5; }

.posted-date { margin-top: 14px; color: var(--muted); font-size: 13px; }

.message-btn {
    margin-top: auto; padding-top: 0;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    background: var(--leaf); color: #fff; text-decoration: none;
    border-radius: 10px; padding: 11px; font-weight: 600;
    transition: background .15s;
}
.request-card .posted-date + .message-btn { margin-top: 16px; }
.message-btn:hover { background: var(--forest); color: #fff; }

.no-requests { grid-column: 1 / -1; text-align: center; padding: 56px 20px; color: var(--muted); }
.no-requests i { font-size: 30px; opacity: .55; margin-bottom: 12px; }
.no-requests h3 { color: var(--ink); font-size: 18px; margin-bottom: 6px; }

/* ---------- Responsive ---------- */
@media (max-width: 1200px) { .requests-container { grid-template-columns: repeat(2, 1fr); } }

@media (max-width: 768px) {
    .sidebar { position: static; width: 100%; flex-direction: row; align-items: center; padding: 12px; overflow-x: auto; }
    .brand { padding: 0 12px 0 4px; white-space: nowrap; }
    .brand small { display: none; }
    .nav { flex-direction: row; align-items: center; }
    .nav li { margin-right: 4px; }
    .nav a { white-space: nowrap; padding: 10px 12px; margin: 0; }
    .nav a span { display: none; }
    .nav .logout { margin: 0 0 0 auto; border: 0; padding: 0; }
    .main { margin-left: 0; padding: 18px 14px 40px; }
    .header { flex-direction: column; align-items: flex-start; }
    .requests-container { grid-template-columns: 1fr; }
    .section { padding: 16px; }
}

@media (max-width: 520px) { .dashboard-cards { grid-template-columns: 1fr; } }

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">

    <div class="brand">
        <strong><i class="fa-solid fa-seedling"></i> FarmMarket</strong>
        <small>Farm-to-Market System</small>
    </div>

    <ul class="nav">
        <li><a href="farmerDashboard.php" class="active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</aside>


<!-- MAIN -->
<main class="main">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>Farmer Dashboard</h1>
            <p>Welcome, <b><?= htmlspecialchars($farmerName) ?>!</b></p>
        </div>

        <div class="profile">
            <span class="avatar farmer"><?= htmlspecialchars(strtoupper(mb_substr($farmerName, 0, 1))) ?></span>
            Farmer
        </div>
    </div>


    <!-- SUMMARY CARDS -->
    <div class="dashboard-cards">

        <!-- MY POSTS -->
        <div class="dashboard-card posts">
            <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div>
                <div class="number"><?= htmlspecialchars($myPostsCount) ?></div>
                <h3>My Posts</h3>
                <p>Your product listings</p>
            </div>
        </div>

        <!-- BUYER REQUESTS -->
        <div class="dashboard-card requests">
            <div class="icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>
                <div class="number"><?= count($buyerPosts) ?></div>
                <h3>Buyer Requests</h3>
                <p>Latest requests from buyers</p>
            </div>
        </div>

    </div>


    <!-- BUYER REQUESTS -->
    <div class="section">

        <div class="section-header">
            <h2>Latest Buyer Requests</h2>
            <a href="buyer_posts.php" class="view-all">View all</a>
        </div>

        <div class="requests-container">

            <?php if (!empty($buyerPosts)): ?>

                <?php foreach ($buyerPosts as $post): ?>

                    <?php
                        $buyerFull = $post['firstName'] . ' ' . $post['lastName'];
                        $buyerInitials = strtoupper(mb_substr($post['firstName'], 0, 1) . mb_substr($post['lastName'], 0, 1));
                    ?>

                    <div class="request-card">

                        <!-- PRODUCT -->
                        <h2><?= htmlspecialchars($post['product_name']) ?></h2>

                        <!-- VARIETY -->
                        <?php if (!empty($post['variety'])): ?>
                            <div class="variety"><?= htmlspecialchars($post['variety']) ?></div>
                        <?php endif; ?>

                        <!-- BUYER -->
                        <div class="buyer-name">
                            <span class="avatar buyer"><?= htmlspecialchars($buyerInitials) ?></span>
                            <div>
                                <small>Buyer</small>
                                <?= htmlspecialchars($buyerFull) ?>
                            </div>
                        </div>

                        <dl class="details">
                            <!-- QUANTITY -->
                            <dt>Quantity</dt>
                            <dd><?= htmlspecialchars($post['quantity']) ?> <?= htmlspecialchars($post['unit']) ?></dd>

                            <!-- BUDGET -->
                            <dt>Budget</dt>
                            <dd class="price">₱<?= number_format((float)$post['price'], 2) ?> / <?= htmlspecialchars($post['unit']) ?></dd>

                            <!-- LOCATION -->
                            <dt>Location</dt>
                            <dd><?= htmlspecialchars($post['address']) ?></dd>

                            <!-- CONTACT -->
                            <dt>Contact</dt>
                            <dd><?= htmlspecialchars($post['contact_number']) ?></dd>
                        </dl>

                        <!-- DESCRIPTION -->
                        <?php if (!empty($post['description'])): ?>
                            <p class="description"><?= htmlspecialchars($post['description']) ?></p>
                        <?php endif; ?>

                        <!-- POSTED DATE -->
                        <p class="posted-date">Posted <?= date('M d, Y', strtotime($post['created_at'])) ?></p>

                        <!-- CONTACT BUYER -->
                        <a href="messages.php?user_id=<?= urlencode($post['buyer_user_id']) ?>" class="message-btn">
                            <i class="fa-solid fa-comment-dots"></i> Contact buyer
                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-requests">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <h3>No buyer requests yet</h3>
                    <p>New requests from buyers will show up here.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</main>

</body>
</html>