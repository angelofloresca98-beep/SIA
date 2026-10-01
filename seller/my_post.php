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

// DELETE PRODUCT / POST


if (isset($_POST['delete_post'])) {

    $post_id = $_POST['post_id'] ?? '';

    if (!empty($post_id)) {

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
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Figtree', system-ui, sans-serif; background: var(--paper); color: var(--ink); min-height: 100vh; }

a:focus-visible, button:focus-visible { outline: 3px solid var(--harvest); outline-offset: 2px; }

/* ---------- Sidebar (same as farmer dashboard) ---------- */
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

.btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--leaf); color: #fff; text-decoration: none;
    border-radius: 10px; padding: 10px 18px; font-weight: 600;
    transition: background .15s;
}
.btn-primary:hover { background: var(--forest); }

/* ---------- Posts ---------- */
.posts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }

.post-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px; display: flex; flex-direction: column; }

.card-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 14px; }

.post-type { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.sell   { background: var(--leaf-soft); color: var(--forest); }
.wanted { background: var(--harvest-soft); color: #7a4f0e; }

.status { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 13px; font-weight: 600; }
.available   { background: var(--leaf-soft); color: var(--forest); }
.unavailable { background: var(--danger-soft); color: var(--danger); }

.post-card h2 { font-size: 20px; font-weight: 700; line-height: 1.25; }
.variety { color: var(--muted); font-size: 14px; margin-top: 2px; }

.details { display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; margin-top: 16px; font-size: 14.5px; }
.details dt { color: var(--muted); }
.details dd { font-weight: 500; overflow-wrap: anywhere; }
.details dd.price { color: var(--forest); font-weight: 700; }

.description { margin-top: 14px; padding: 10px 12px; background: var(--paper); border-radius: 8px; font-size: 14px; color: #44524a; line-height: 1.5; }

.delete-form { margin-top: auto; padding-top: 18px; }

.delete-btn {
    width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    border: 1px solid #efc3bd; background: #fff; color: var(--danger);
    padding: 10px 14px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}
.delete-btn:hover { background: var(--danger); border-color: var(--danger); color: #fff; }

/* ---------- Empty state ---------- */
.no-post { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); text-align: center; padding: 60px 24px; }
.no-post i.big { font-size: 32px; color: var(--muted); opacity: .55; margin-bottom: 14px; }
.no-post h2 { font-size: 20px; margin-bottom: 6px; }
.no-post p { color: var(--muted); margin-bottom: 20px; }

/* ---------- Responsive ---------- */
@media (max-width: 1100px) { .posts { grid-template-columns: repeat(2, 1fr); } }

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
    .posts { grid-template-columns: 1fr; }
}

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
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="my_post.php" class="active"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="farmer_post.php"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</aside>


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

                    <!-- DELETE BUTTON -->
                    <div class="delete-form">

                        <form
                            method="POST"
                            action=""
                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                        >

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

</body>
</html>