<?php

session_start();
require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SESSION['role'] !== 'farmer') {
    header("Location: ../index.php");
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

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Farmer Dashboard</title>

<style>

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
    }

    body {
        background: #f4f7f3;
        min-height: 100vh;
    }


    /* ========================================
       SIDEBAR
    ======================================== */

    .sidebar {
        width: 285px;
        height: 100vh;
        background: #2e7d32;
        color: white;

        position: fixed;
        top: 0;
        left: 0;

        padding: 30px 22px;
        z-index: 1000;
    }

    .logo {
        text-align: center;
        margin-bottom: 38px;
    }

    .logo h2 {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 7px;
    }

    .logo p {
        font-size: 14px;
        opacity: 0.8;
    }


    /* ========================================
       NAVIGATION
    ======================================== */

    .nav {
        list-style: none;
    }

    .nav li {
        margin-bottom: 10px;
    }

    .nav a {
        display: block;
        color: white;
        text-decoration: none;

        padding: 16px 18px;

        border-radius: 9px;

        font-size: 16px;
        font-weight: 500;

        transition: 0.2s;
    }

    .nav a:hover {
        background: #1b5e20;
    }

    .nav a.active {
        background: #1b5e20;
    }

    .logout {
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid rgba(255,255,255,0.35);
    }


    /* ========================================
       MAIN
    ======================================== */

    .main {
        margin-left: 285px;
        width: calc(100% - 285px);
        padding: 35px;
    }


    /* ========================================
       HEADER
    ======================================== */

    .header {
        background: white;

        padding: 25px 30px;

        border-radius: 15px;

        display: flex;
        justify-content: space-between;
        align-items: center;

        margin-bottom: 30px;

        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
    }

    .header h1 {
        color: #2e7d32;
        font-size: 30px;
        margin-bottom: 5px;
    }

    .header p {
        color: #777;
        font-size: 16px;
    }

    .profile {
        background: #e8f5e9;
        color: #2e7d32;

        padding: 13px 20px;

        border-radius: 30px;

        font-weight: bold;
        font-size: 16px;
    }


    /* ========================================
       SUMMARY CARDS
    ======================================== */

    .dashboard-cards {
        display: grid;

        /* Same look as buyer dashboard */
        grid-template-columns: repeat(2, minmax(280px, 450px));

        gap: 22px;

        margin-bottom: 35px;
    }

    .dashboard-card {
        background: white;

        min-height: 220px;

        padding: 30px;

        border-radius: 15px;

        box-shadow: 0 4px 15px rgba(0,0,0,0.07);

        transition: transform 0.2s, box-shadow 0.2s;
    }

    .dashboard-card:hover {
        transform: translateY(-3px);

        box-shadow: 0 7px 20px rgba(0,0,0,0.10);
    }

    .dashboard-card .icon {
        font-size: 38px;
        margin-bottom: 20px;
    }

    .dashboard-card h3 {
        color: #444;

        font-size: 19px;

        margin-bottom: 12px;
    }

    .dashboard-card .number {
        color: #2e7d32;

        font-size: 34px;
        font-weight: bold;

        margin-bottom: 7px;
    }

    .dashboard-card p {
        color: #777;
        font-size: 15px;
    }


    /* ========================================
       BUYER REQUEST SECTION
    ======================================== */

    .section {
        background: white;

        padding: 30px;

        border-radius: 15px;

        box-shadow: 0 4px 15px rgba(0,0,0,0.07);
    }

    .section-header {
        display: flex;

        justify-content: space-between;
        align-items: center;

        margin-bottom: 25px;
    }

    .section-header h2 {
        color: #222;
        font-size: 26px;
    }

    .view-all {
        color: #2e7d32;

        text-decoration: none;

        font-weight: bold;
        font-size: 16px;
    }

    .view-all:hover {
        text-decoration: underline;
    }


    /* ========================================
       REQUEST CARDS
    ======================================== */

    .requests-container {
        display: grid;

        /*
           This makes each request card similar
           to the product card in your screenshot.
        */
        grid-template-columns: repeat(3, minmax(280px, 1fr));

        gap: 22px;
    }

    .request-card {
        background: #fafcf9;

        padding: 25px;

        border: 1px solid #dfe5df;

        border-radius: 12px;

        transition: 0.2s;
    }

    .request-card:hover {
        transform: translateY(-3px);

        box-shadow: 0 6px 18px rgba(0,0,0,0.10);
    }


    /* PRODUCT NAME */

    .request-card h2 {
        color: #2e7d32;

        font-size: 23px;

        margin-bottom: 18px;
    }


    /* REQUEST DETAILS */

    .request-card p {
        color: #555;

        font-size: 15px;

        line-height: 1.5;

        margin: 9px 0;
    }

    .request-card strong {
        color: #222;
    }


    /* BUYER NAME */

    .buyer-name {
        background: #e8f5e9;

        padding: 10px 12px;

        border-radius: 8px;

        margin: 12px 0 !important;

        color: #2e7d32 !important;

        font-weight: bold;
    }

    .buyer-name strong {
        color: #333;
    }


    /* PRICE */

    .price {
        color: #2e7d32 !important;
        font-weight: 600;
    }


    /* DATE */

    .posted-date {
        color: #888 !important;
        font-size: 14px !important;
    }


    /* DESCRIPTION */

    .description {
        background: #f3f6f3;

        padding: 10px;

        border-radius: 7px;

        margin-top: 12px !important;
    }


    /* ========================================
       CONTACT BUTTON
    ======================================== */

    .message-btn {
        display: block;

        width: 100%;

        margin-top: 18px;

        padding: 12px;

        text-align: center;

        background: #2e7d32;

        color: white;

        border-radius: 8px;

        text-decoration: none;

        font-weight: bold;

        transition: 0.2s;
    }

    .message-btn:hover {
        background: #1b5e20;
    }


    /* ========================================
       EMPTY STATE
    ======================================== */

    .no-requests {
        grid-column: 1 / -1;

        text-align: center;

        padding: 60px 20px;

        color: #777;
    }

    .no-requests h3 {
        color: #2e7d32;
        margin-bottom: 10px;
    }


    /* ========================================
       RESPONSIVE
    ======================================== */

    @media (max-width: 1200px) {

        .requests-container {
            grid-template-columns: repeat(2, 1fr);
        }

    }


    @media (max-width: 900px) {

        .dashboard-cards {
            grid-template-columns: 1fr 1fr;
        }

        .requests-container {
            grid-template-columns: 1fr;
        }

    }


    @media (max-width: 700px) {

        .sidebar {
            width: 210px;
        }

        .main {
            margin-left: 210px;
            width: calc(100% - 210px);
            padding: 20px;
        }

        .dashboard-cards {
            grid-template-columns: 1fr;
        }

        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

    }

</style>

</head>


<body>


<!-- ========================================
     SIDEBAR
======================================== -->

<aside class="sidebar">

    <div class="logo">

        <h2>🌾 FarmMarket</h2>

        <p>
            Farm-to-Market System
        </p>

    </div>


    <ul class="nav">

        <li>
            <a
                href="farmerDashboard.php"
                class="active"
            >
                🏠 Dashboard
            </a>
        </li>


        <li>
            <a href="my_post.php">
                📋 My Posts
            </a>
        </li>


        <li>
            <a href="farmer_post.php">
                + Create Post
            </a>
        </li>


        <li class="logout">
            <a href="../logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</aside>


<!-- ========================================
     MAIN
======================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="header">

        <div>

            <h1>
                Farmer Dashboard
            </h1>

            <p>
                Welcome, <?= htmlspecialchars($farmerName) ?>!
            </p>

        </div>


        <div class="profile">
            👨‍🌾 Farmer
        </div>

    </div>


    <!-- ====================================
         SUMMARY CARDS
    ===================================== -->

    <div class="dashboard-cards">


        <!-- MY POSTS -->

        <div class="dashboard-card">

            <div class="icon">
                📋
            </div>

            <h3>
                My Posts
            </h3>

            <div class="number">
                <?= htmlspecialchars($myPostsCount) ?>
            </div>

            <p>
                Your product listings
            </p>

        </div>


        <!-- BUYER REQUESTS -->

        <div class="dashboard-card">

            <div class="icon">
                🛒
            </div>

            <h3>
                Buyer Requests
            </h3>

            <div class="number">
                <?= count($buyerPosts) ?>
            </div>

            <p>
                Latest requests from buyers
            </p>

        </div>

    </div>


    <!-- ====================================
         BUYER REQUESTS
    ===================================== -->

    <div class="section">


        <div class="section-header">

            <h2>
                Latest Buyer Requests
            </h2>

            <a
                href="buyer_posts.php"
                class="view-all"
            >
                View All →
            </a>

        </div>


        <div class="requests-container">


            <?php if (!empty($buyerPosts)): ?>


                <?php foreach ($buyerPosts as $post): ?>


                    <div class="request-card">


                        <!-- PRODUCT -->

                        <h2>

                            🛒
                            <?= htmlspecialchars($post['product_name']) ?>

                        </h2>


                        <!-- VARIETY -->

                        <?php if (!empty($post['variety'])): ?>

                            <p>

                                <strong>
                                    Variety:
                                </strong>

                                <?= htmlspecialchars($post['variety']) ?>

                            </p>

                        <?php endif; ?>


                        <!-- BUYER -->

                        <p class="buyer-name">

                            👤

                            <strong>
                                Buyer:
                            </strong>

                            <?= htmlspecialchars(
                                $post['firstName']
                                . ' '
                                . $post['lastName']
                            ) ?>

                        </p>


                        <!-- QUANTITY -->

                        <p>

                            <strong>
                                Quantity:
                            </strong>

                            <?= htmlspecialchars($post['quantity']) ?>

                            <?= htmlspecialchars($post['unit']) ?>

                        </p>


                        <!-- BUDGET -->

                        <p class="price">

                            <strong>
                                Budget:
                            </strong>

                            ₱<?= number_format((float)$post['price'], 2) ?>

                            / <?= htmlspecialchars($post['unit']) ?>

                        </p>


                        <!-- LOCATION -->

                        <p>

                            <strong>
                                Location:
                            </strong>

                            <?= htmlspecialchars($post['address']) ?>

                        </p>


                        <!-- CONTACT -->

                        <p>

                            <strong>
                                Contact:
                            </strong>

                            <?= htmlspecialchars($post['contact_number']) ?>

                        </p>


                        <!-- DESCRIPTION -->

                        <?php if (!empty($post['description'])): ?>

                            <p class="description">

                                <strong>
                                    Description:
                                </strong>

                                <?= htmlspecialchars($post['description']) ?>

                            </p>

                        <?php endif; ?>


                        <!-- POSTED DATE -->

                        <p class="posted-date">

                            <strong>
                                Posted:
                            </strong>

                            <?= date(
                                'M d, Y',
                                strtotime($post['created_at'])
                            ) ?>

                        </p>


                        <!-- CONTACT BUYER -->

                        <a
                            href="messages.php?user_id=<?= urlencode($post['buyer_user_id']) ?>"
                            class="message-btn"
                        >
                            💬 Contact Buyer
                        </a>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="no-requests">

                    <h3>
                        🛒 No Buyer Requests Yet
                    </h3>

                    <p>
                        Buyers haven't posted any requests yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


</main>

</body>

</html>