```php
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

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user'] ?? 'Buyer';

$message = "";
$message_type = "";

// FORM SUBMISSION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product = trim($_POST['product'] ?? '');
    $variety = trim($_POST['variety'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // VALIDATION

    if (
        $product === '' ||
        $unit === '' ||
        $price === '' ||
        $quantity === ''
    ) {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (!is_numeric($price) || (float)$price < 0) {

        $message = "Please enter a valid price.";
        $message_type = "error";

    } elseif (!is_numeric($quantity) || (float)$quantity <= 0) {

        $message = "Please enter a valid quantity.";
        $message_type = "error";

    } else {

        try {

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
                ':variety' => $variety !== '' ? $variety : null,
                ':unit' => $unit,
                ':price' => $price,
                ':quantity' => $quantity,
                ':description' => $description !== '' ? $description : null
            ]);

            // GET PRODUCT ID
            $product_id = $conn->lastInsertId();


            //INSERT BUYER POST

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
                    'wanted'
                )
            ";

            $stmtPost = $conn->prepare($sqlPost);

            $stmtPost->execute([
                ':user_id' => $user_id,
                ':product_id' => $product_id
            ]);

            // SAVE TRANSACTION

            $conn->commit();

            // Go to My Posts after creating request
            header("Location: my_post.php");
            exit();


        } catch (PDOException $e) {

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Buyer Request</title>

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


        /* =========================
           SIDEBAR
        ========================= */

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


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 240px;
            width: calc(100% - 240px);
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


        /* =========================
           FORM
        ========================= */

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            max-width: 900px;
            margin: auto;
        }

        .form-card h2 {
            color: #2e7d32;
            margin-bottom: 8px;
        }

        .form-description {
            color: #777;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: bold;
            color: #444;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font-size: 15px;
            outline: none;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 2px rgba(46,125,50,0.10);
        }

        .form-group input[readonly] {
            background: #f3f3f3;
            color: #666;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }


        /* =========================
           BUTTONS
        ========================= */

        .button-container {
            margin-top: 25px;
            display: flex;
            gap: 10px;
        }

        .btn {
            border: none;
            background: #2e7d32;
            color: white;
            padding: 12px 25px;
            border-radius: 7px;
            font-size: 15px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn:hover {
            background: #1b5e20;
        }

        .cancel-btn {
            display: inline-block;
            background: #777;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 7px;
        }

        .cancel-btn:hover {
            background: #555;
        }


        /* =========================
           MESSAGE
        ========================= */

        .message {
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 700px) {

            .sidebar {
                width: 200px;
            }

            .main {
                margin-left: 200px;
                width: calc(100% - 200px);
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
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


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="logo">

        <h2>🌾 FarmMarket</h2>

        <p>
            Farm-to-Market System
        </p>

    </div>


    <ul class="nav">

        <li>
            <a href="buyer.php">
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a href="my_post.php">
                📋 My Posts
            </a>
        </li>

        <li>
            <a href="buyer_post.php" class="active">
                ➕ Create Post
            </a>
        </li>

        <li class="logout">
            <a href="../logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</aside>


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- HEADER -->

    <div class="header">

        <div>

            <h1>
                Create Buyer Request
            </h1>

            <p>
                Tell farmers what agricultural product you are looking for.
            </p>

        </div>


        <div class="profile">

            👤 <?= htmlspecialchars($user_name) ?>

        </div>

    </div>


    <!-- FORM -->

    <div class="form-card">

        <h2>
            📋 Create Product Request
        </h2>

        <p class="form-description">
            Enter the product, quantity and budget you need.
        </p>


        <?php if ($message !== ""): ?>

            <div class="message <?= htmlspecialchars($message_type) ?>">

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="">


            <div class="form-grid">


                <!-- PRODUCT -->

                <div class="form-group">

                    <label for="product">
                        Product *
                    </label>

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

                    <label for="variety">
                        Variety
                    </label>

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

                    <label for="quantity">
                        Quantity Needed *
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="0.01"
                        step="0.01"
                        placeholder="e.g. 50"
                        value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- UNIT -->

                <div class="form-group">

                    <label for="unit">
                        Unit *
                    </label>

                    <select
                        id="unit"
                        name="unit"
                        required
                    >

                        <option value="">
                            Select Unit
                        </option>

                        <option
                            value="kg"
                            <?= ($_POST['unit'] ?? '') === 'kg' ? 'selected' : '' ?>
                        >
                            Kilogram (kg)
                        </option>

                        <option
                            value="sack"
                            <?= ($_POST['unit'] ?? '') === 'sack' ? 'selected' : '' ?>
                        >
                            Sack
                        </option>

                        <option
                            value="ton"
                            <?= ($_POST['unit'] ?? '') === 'ton' ? 'selected' : '' ?>
                        >
                            Ton
                        </option>

                        <option
                            value="piece"
                            <?= ($_POST['unit'] ?? '') === 'piece' ? 'selected' : '' ?>
                        >
                            Piece
                        </option>

                        <option
                            value="box"
                            <?= ($_POST['unit'] ?? '') === 'box' ? 'selected' : '' ?>
                        >
                            Box
                        </option>

                        <option
                            value="liter"
                            <?= ($_POST['unit'] ?? '') === 'liter' ? 'selected' : '' ?>
                        >
                            Liter
                        </option>

                    </select>

                </div>


                <!-- BUDGET -->

                <div class="form-group">

                    <label for="price">
                        Budget / Price per Unit *
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        placeholder="e.g. 50.00"
                        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                        required
                    >

                </div>


                <!-- POST TYPE -->

                <div class="form-group">

                    <label>
                        Post Type
                    </label>

                    <input
                        type="text"
                        value="Wanted"
                        readonly
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe the product you are looking for..."
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="button-container">

                <button
                    type="submit"
                    class="btn"
                >
                    📋 Create Request
                </button>


                <a
                    href="buyer.php"
                    class="cancel-btn"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</main>

</body>
</html>
```
