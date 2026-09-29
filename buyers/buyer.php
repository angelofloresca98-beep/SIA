<?php

session_start();
require '../database/connection.php';

/* CHECK LOGIN */

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}


$user_id = $_SESSION['user_id'];


/*  GET LATEST FARMER PRODUCTS */

$sql = "
    SELECT
        post.post_id,
        post.post_type,
        post.created_at,

        products.product_id,
        products.product_name,
        products.variety,
        products.unit,
        products.price,
        products.quantity,
        products.description,

        users.user_id AS farmer_user_id,
        CONCAT(users.firstName, ' ', users.lastName) AS farmer_name,
        users.contact_number,
        users.address

    FROM post

    INNER JOIN products
        ON post.product_id = products.product_id

    INNER JOIN users
        ON post.user_id = users.user_id

    WHERE post.post_type = 'sell'
    AND users.role = 'farmer'

    ORDER BY post.created_at DESC

    LIMIT 6
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$farmerPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*  COUNT BUYER'S POSTS */

$sqlMyPosts = "
    SELECT COUNT(*)
    FROM post
    WHERE user_id = ?
    AND post_type = 'wanted'
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

    <title>Buyer Dashboard</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7f3;
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */

        .sidebar {
            width: 240px;
            height: 100vh;
            background: #2e7d32;
            color: white;
            padding: 25px 15px;
            position: fixed;
            left: 0;
            top: 0;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo h2 {
            font-size: 24px;
        }

        .logo p {
            font-size: 13px;
            margin-top: 5px;
            opacity: 0.8;
        }

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
            padding: 13px 15px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .nav a:hover {
            background: #1b5e20;
        }

        .nav .active {
            background: #1b5e20;
        }

        .logout {
            margin-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.3);
            padding-top: 20px;
        }

        /* MAIN CONTENT */

        .main {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
        }

        /* HEADER */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .header h1 {
            color: #2e7d32;
            font-size: 26px;
        }

        .header p {
            color: #777;
            margin-top: 5px;
        }

        .profile {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        /* DASHBOARD CARDS */

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .dashboard-card .icon {
            font-size: 30px;
            margin-bottom: 15px;
        }

        .dashboard-card h3 {
            color: #555;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .dashboard-card .number {
            font-size: 30px;
            font-weight: bold;
            color: #2e7d32;
        }

        .dashboard-card p {
            color: #777;
            margin-top: 5px;
        }

        /* SECTION */

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            color: #333;
        }

        .view-all {
            color: #2e7d32;
            text-decoration: none;
            font-weight: bold;
        }

        .view-all:hover {
            text-decoration: underline;
        }

        /* PRODUCTS */

        .products-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .product-card {
            background: #f9fbf9;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.10);
        }

        .product-card h2 {
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .product-card p {
            margin: 10px 0;
            color: #555;
        }

        .product-card strong {
            color: #333;
        }

        /* FARMER NAME */

        .farmer-name {
            background: #e8f5e9;
            color: #2e7d32 !important;
            padding: 10px;
            border-radius: 8px;
            font-weight: bold;
        }

        .contact-btn {
            display: block;
            text-align: center;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            padding: 10px;
            border-radius: 7px;
            margin-top: 15px;
        }

        .contact-btn:hover {
            background: #1b5e20;
        }

        /* =========================
           NO PRODUCTS
        ========================= */

        .no-products {
            text-align: center;
            padding: 40px;
            color: #777;
            grid-column: 1 / -1;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .dashboard-cards {
                grid-template-columns: 1fr 1fr;
            }

            .products-container {
                grid-template-columns: 1fr 1fr;
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }

            .dashboard-cards,
            .products-container {
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


<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">

        <h2>🌾 FarmMarket</h2>

        <p>Farm-to-Market System</p>

    </div>


    <ul class="nav">

        <li>
            <a href="buyer.php" class="active">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="my_post.php">
                📋 My Posts
            </a>
        </li>

        <li>
            <a href="buyer_post.php">
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

<main class="main">


    <!-- HEADER -->

    <div class="header">

        <div>

            <h1>Buyer Dashboard</h1>

            <p>
                Find fresh products directly from farmers.
            </p>

        </div>

        <div class="profile">
            👤 Buyer
        </div>

    </div>


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
                Your product requests
            </p>

        </div>


        <!-- FARMER PRODUCTS -->

        <div class="dashboard-card">

            <div class="icon">
                🌱
            </div>

            <h3>
                Farmer Products
            </h3>

            <div class="number">
                <?= count($farmerPosts) ?>
            </div>

            <p>
                Latest products from farmers
            </p>

        </div>

    </div>


    <div class="section">

        <div class="section-header">

            <h2>
                Latest Farmer Products
            </h2>

            <a href="farmer_posts.php" class="view-all">
                View All →
            </a>

        </div>


        <div class="products-container">


            <?php if (count($farmerPosts) > 0): ?>


                <?php foreach ($farmerPosts as $post): ?>

                    <div class="product-card">


                        <!-- PRODUCT -->

                        <h2>

                            🌾
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


                        <!-- FARMER NAME -->

                        <p class="farmer-name">

                            👨‍🌾

                            <strong>
                                Farmer:
                            </strong>

                            <?= htmlspecialchars($post['farmer_name']) ?>

                        </p>


                        <!-- QUANTITY -->

                        <p>

                            <strong>
                                Quantity:
                            </strong>

                            <?= htmlspecialchars($post['quantity']) ?>

                            <?= htmlspecialchars($post['unit']) ?>

                        </p>


                        <!-- PRICE -->

                        <p>

                            <strong>
                                Price:
                            </strong>

                            ₱<?= number_format($post['price'], 2) ?>

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

                        <p>

                            <strong>
                                Description:
                            </strong>

                            <?= htmlspecialchars($post['description']) ?>

                        </p>


                        <!-- POSTED -->

                        <p>

                            <strong>
                                Posted:
                            </strong>

                            <?= htmlspecialchars($post['created_at']) ?>

                        </p>

                    </div>

                <?php endforeach; ?>


            <?php else: ?>


                <div class="no-products">

                    <h3>
                        🌱 No Farmer Products Yet
                    </h3>

                    <p>
                        Farmers haven't posted any products yet.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</main>

</body>

</html>