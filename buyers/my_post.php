<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: ../index.php");
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
    SELECT *
    FROM post
    WHERE user_id = :user_id
    AND post_type = 'wanted'
    ORDER BY created_at DESC
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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f7f3;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h1 {
            color: #2e7d32;
        }

        .back-btn {
            background: #2e7d32;
            color: white;
            padding: 10px 18px;
            border-radius: 7px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #1b5e20;
        }

        .post-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .post-card h2 {
            color: #2e7d32;
            margin-bottom: 15px;
        }

        .post-card p {
            margin: 8px 0;
            color: #555;
        }

        .post-card strong {
            color: #333;
        }

        .no-posts {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">

        <h1>📋 My Posts</h1>

        <a href="buyer.php" class="back-btn">
            ← Dashboard
        </a>

    </div>


    <?php if (count($posts) > 0): ?>

        <?php foreach ($posts as $post): ?>

            <div class="post-card">

                <h2>
                    Post #<?= htmlspecialchars($post['post_id']) ?>
                </h2>

                <p>
                    <strong>Post Type:</strong>
                    <?= htmlspecialchars($post['post_type']) ?>
                </p>

                <p>
                    <strong>Posted:</strong>
                    <?= htmlspecialchars($post['created_at']) ?>
                </p>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="no-posts">

            <h2>📭 No Posts Yet</h2>

            <p>
                You haven't created any product requests yet.
            </p>

            <br>

            <a href="buyer_post.php" class="back-btn">
                + Create Post
            </a>

        </div>

    <?php endif; ?>

</div>

</body>
</html>
