<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

// Only buyers can access this page
if ($_SESSION['role'] !== 'buyer') {
    header("Location: ../index.php");
    exit();
}

// Logged-in buyer ID
$user_id = $_SESSION['user_id'];

// GET BUYER'S POSTS

$sql = "
    SELECT p.*, pr.product_name
    FROM post p
    LEFT JOIN products pr ON pr.product_id = p.product_id
    WHERE p.user_id = :user_id
    AND p.post_type = 'wanted'
    ORDER BY p.created_at DESC
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
<title>My Posts</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ink: #1c2a24; --muted: #6b7a72; --paper: #f4f6f2; --card: #ffffff; --line: #e4e9e2;
    --forest: #1f4d3a; --leaf: #2f7d4f; --leaf-soft: #dff0e5;
    --harvest: #b7791f; --harvest-soft: #fbeccb;
    --danger: #c0392b; --danger-soft: #fbe3e0; --radius: 12px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; }
a:focus-visible, button:focus-visible { outline: 3px solid var(--harvest); outline-offset: 2px; }

/* Sidebar */
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
.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }
.btn-primary { display: inline-flex; align-items: center; gap: 8px; background: var(--leaf); color: #fff; text-decoration: none; border-radius: 10px; padding: 10px 18px; font-weight: 600; transition: background .15s; }
.btn-primary:hover { background: var(--forest); }

/* Post cards */
.posts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.post-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column; }
.card-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 14px; }
.post-type { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.sell   { background: var(--leaf-soft); color: var(--forest); }
.wanted { background: var(--harvest-soft); color: #7a4f0e; }
.post-card h2 { font-size: 20px; font-weight: 700; line-height: 1.25; }
.details { display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; margin-top: 16px; font-size: 14.5px; }
.details dt { color: var(--muted); }
.details dd { font-weight: 500; overflow-wrap: anywhere; }
.details dd.price { color: var(--forest); font-weight: 700; }

/* Empty state */
.no-post { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); text-align: center; padding: 60px 24px; }
.no-post i.big { display: block; font-size: 40px; color: var(--muted); opacity: .55; margin-bottom: 14px; }
.no-post h2 { font-size: 24px; margin-bottom: 6px; }
.no-post p { color: var(--muted); margin-bottom: 20px; }

/* Farmer-only parts of the card */
.status { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.available   { background: var(--leaf-soft); color: var(--forest); }
.unavailable { background: var(--danger-soft); color: var(--danger); }
.variety { color: var(--muted); font-size: 14px; margin-top: 2px; }
.description { margin-top: 14px; padding: 10px 12px; background: var(--paper); border-radius: 8px; font-size: 14px; color: #44524a; line-height: 1.5; }
.delete-form { margin-top: auto; padding-top: 18px; }
.delete-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #efc3bd; background: #fff; color: var(--danger); padding: 10px 14px; border-radius: 10px; font: inherit; font-weight: 600; cursor: pointer; transition: background .15s, color .15s, border-color .15s; }
.delete-btn:hover { background: var(--danger); border-color: var(--danger); color: #fff; }

/* Responsive */
@media (max-width: 1100px) { .posts { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
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
    .header { flex-direction: column; align-items: flex-start; }
    .posts { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<nav class="navbar">
    <div class="brand">
        <strong><i class="fa-solid fa-seedling"></i> FarmMarket</strong>
        <small>Farm-to-Market System</small>
    </div>
    <ul class="nav">
        <li><a href="buyer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php" class="active"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="buyer_post.php"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>

<main class="main">

    <div class="header">
        <div>
            <h1>My Posts</h1>
            <p><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?> · View and manage your listings.</p>
        </div>
        <a href="buyer_post.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create post</a>
    </div>

    <?php if (count($posts) > 0): ?>
        <div class="posts">
            <?php foreach ($posts as $post): ?>
                <div class="post-card">

                    <div class="card-top">
                        <span class="post-type wanted"><i class="fa-solid fa-cart-shopping"></i> Wanted</span>
                        <span class="status available">Available</span>
                    </div>

                    <h2><?= htmlspecialchars($post['product_name'] ?? ('Post #' . $post['post_id'])) ?></h2>
                    <div class="variety">Product category</div>

                    <dl class="details">
                        <dt>Quantity</dt>
                        <dd>50 kg</dd>
                        <dt>Price</dt>
                        <dd class="price">₱150.00</dd>
                        <dt>Posted</dt>
                        <dd><?= htmlspecialchars(date('M d, Y', strtotime($post['created_at']))) ?></dd>
                    </dl>

                    <div class="description">Short description of the request.</div>

                    <div class="delete-form">
                        <button type="button" class="delete-btn"><i class="fa-solid fa-trash"></i> Delete post</button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="no-post">
            <i class="fa-solid fa-clipboard-list big"></i>
            <h2>You don't have any posts yet.</h2>
            <p>Create a post and it will appear here.</p>
            <a href="buyer_post.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Create post</a>
        </div>
    <?php endif; ?>

</main>

</body>
</html>