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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Posts</title>


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

            padding: 30px;

        }


        /* =========================
           HEADER
        ========================= */

        .header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            background: white;

            padding: 20px 25px;

            border-radius: 12px;

            margin-bottom: 25px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);

        }


        .header h1 {

            color: #2e7d32;

            font-size: 28px;

        }


        .header p {

            color: #777;

            margin-top: 5px;

        }


        .back-btn {

            background: #2e7d32;

            color: white;

            text-decoration: none;

            padding: 10px 15px;

            border-radius: 7px;

        }


        .back-btn:hover {

            background: #1b5e20;

        }


        /* =========================
           POSTS
        ========================= */

        .posts {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

        }


        .post-card {

            background: white;

            padding: 20px;

            border-radius: 12px;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);

            border: 1px solid #e0e0e0;

        }


        .post-card h2 {

            color: #2e7d32;

            margin-bottom: 15px;

        }


        .post-type {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;

            margin-bottom: 15px;

        }


        .sell {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .wanted {

            background: #fff3e0;

            color: #ef6c00;

        }


        .post-card p {

            margin: 10px 0;

            color: #555;

            line-height: 1.5;

        }


        .post-card strong {

            color: #333;

        }


        .description {

            margin-top: 15px;

            padding-top: 15px;

            border-top: 1px solid #eee;

        }


        /* =========================
           STATUS
        ========================= */

        .status {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 15px;

            font-size: 12px;

            font-weight: bold;

        }


        .available {

            background: #e8f5e9;

            color: #2e7d32;

        }


        .unavailable {

            background: #ffebee;

            color: #c62828;

        }


        /* =========================
           DELETE BUTTON
        ========================= */

        .delete-form {

            margin-top: 20px;

            padding-top: 15px;

            border-top: 1px solid #eee;

        }


        .delete-btn {

            width: 100%;

            border: none;

            background: #c62828;

            color: white;

            padding: 11px 15px;

            border-radius: 7px;

            font-size: 14px;

            font-weight: bold;

            cursor: pointer;

        }


        .delete-btn:hover {

            background: #b71c1c;

        }


        /* =========================
           NO POSTS
        ========================= */

        .no-post {

            background: white;

            padding: 50px 30px;

            border-radius: 12px;

            text-align: center;

            box-shadow:
                0 3px 10px rgba(0,0,0,0.08);

        }


        .no-post h2 {

            color: #555;

            margin-bottom: 10px;

        }


        .no-post p {

            color: #777;

            margin-bottom: 20px;

        }


        .create-btn {

            display: inline-block;

            background: #2e7d32;

            color: white;

            text-decoration: none;

            padding: 11px 18px;

            border-radius: 7px;

        }


        .create-btn:hover {

            background: #1b5e20;

        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .posts {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 700px) {

            body {

                padding: 15px;

            }


            .header {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .posts {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


    <!-- =========================
         HEADER
    ========================= -->

    <div class="header">

        <div>

            <h1>
                📋 My Posts
            </h1>

            <p>
                View all of your posts.
            </p>

        </div>


        <!-- FARMER DASHBOARD -->

        <a
            href="farmer.php"
            class="back-btn"
        >
            ← Dashboard
        </a>

    </div>



    <!-- =========================
         POSTS
    ========================= -->

    <?php if (count($posts) > 0): ?>


        <div class="posts">


            <?php foreach ($posts as $post): ?>


                <div class="post-card">


                    <!-- =========================
                         POST TYPE
                    ========================= -->

                    <?php if ($post['post_type'] === 'sell'): ?>

                        <span class="post-type sell">

                            🌾 SELLING

                        </span>

                    <?php else: ?>

                        <span class="post-type wanted">

                            🛒 WANTED

                        </span>

                    <?php endif; ?>



                    <!-- =========================
                         PRODUCT NAME
                    ========================= -->

                    <h2>

                        <?= htmlspecialchars(
                            $post['product_name']
                        ) ?>

                    </h2>



                    <!-- =========================
                         VARIETY
                    ========================= -->

                    <?php if (!empty($post['variety'])): ?>

                        <p>

                            <strong>
                                Variety:
                            </strong>

                            <?= htmlspecialchars(
                                $post['variety']
                            ) ?>

                        </p>

                    <?php endif; ?>



                    <!-- =========================
                         QUANTITY
                    ========================= -->

                    <p>

                        <strong>
                            Quantity:
                        </strong>

                        <?= htmlspecialchars(
                            $post['quantity']
                        ) ?>

                        <?= htmlspecialchars(
                            $post['unit']
                        ) ?>

                    </p>



                    <!-- =========================
                         PRICE
                    ========================= -->

                    <p>

                        <strong>
                            Price:
                        </strong>

                        ₱<?= number_format(
                            $post['price'],
                            2
                        ) ?>

                    </p>



                    <!-- =========================
                         DESCRIPTION
                    ========================= -->

                    <?php if (!empty($post['description'])): ?>

                        <div class="description">

                            <p>

                                <strong>
                                    Description:
                                </strong>

                            </p>

                            <p>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $post['description']
                                    )
                                ) ?>

                            </p>

                        </div>

                    <?php endif; ?>



                    <!-- =========================
                         STATUS
                    ========================= -->

                    <p>

                        <strong>
                            Status:
                        </strong>

                        <?php if (
                            $post['status'] === 'available'
                        ): ?>

                            <span class="status available">

                                Available

                            </span>

                        <?php else: ?>

                            <span class="status unavailable">

                                Unavailable

                            </span>

                        <?php endif; ?>

                    </p>



                    <!-- =========================
                         POSTED DATE
                    ========================= -->

                    <p>

                        <strong>
                            Posted:
                        </strong>

                        <?= htmlspecialchars(
                            $post['created_at']
                        ) ?>

                    </p>



                    <!-- =========================
                         DELETE BUTTON
                    ========================= -->

                    <div class="delete-form">

                        <form
                            method="POST"
                            action=""
                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                        >

                            <input
                                type="hidden"
                                name="post_id"
                                value="<?= htmlspecialchars(
                                    $post['post_id']
                                ) ?>"
                            >


                            <button
                                type="submit"
                                name="delete_post"
                                class="delete-btn"
                            >

                                🗑️ Delete Product

                            </button>

                        </form>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <!-- =========================
             NO POSTS
        ========================= -->

        <div class="no-post">

            <h2>
                📋 You don't have any posts yet.
            </h2>

            <p>
                Create a post and it will appear here.
            </p>


            <a
                href="farmer.php"
                class="create-btn"
            >

                + Create Post

            </a>

        </div>


    <?php endif; ?>


</body>

</html>