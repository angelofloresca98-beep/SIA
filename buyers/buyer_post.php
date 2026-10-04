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

$units = [
    'kg'    => 'Kilogram (kg)',
    'sack'  => 'Sack',
    'ton'   => 'Ton',
    'piece' => 'Piece',
    'box'   => 'Box',
    'liter' => 'Liter',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Buyer Request</title>

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
.profile { display: inline-flex; align-items: center; gap: 8px; background: var(--card); border: 1px solid var(--line); color: var(--forest); padding: 8px 16px; border-radius: 99px; font-weight: 600; }
.profile i { color: var(--leaf); }

/* Form card */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 28px; max-width: 860px; }
.card-head { display: flex; align-items: center; gap: 14px; padding-bottom: 20px; margin-bottom: 22px; border-bottom: 1px solid var(--line); }
.card-icon { width: 44px; height: 44px; border-radius: 12px; background: var(--harvest-soft); color: var(--harvest); display: grid; place-items: center; font-size: 18px; flex-shrink: 0; }
.card-head h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.01em; }
.card-head p { color: var(--muted); font-size: 14.5px; margin-top: 2px; }

.message { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; font-size: 14.5px; }
.message.error { background: var(--danger-soft); color: var(--danger); border: 1px solid #efc3bd; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 7px; }
.form-group label .req { color: var(--danger); }
.form-group label .opt { color: var(--muted); font-weight: 500; }

.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 11px 13px; border: 1px solid var(--line); border-radius: 10px;
    font: inherit; font-size: 15px; color: var(--ink); background: #fff; outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.form-group input::placeholder, .form-group textarea::placeholder { color: #a3aea7; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--leaf); box-shadow: 0 0 0 3px var(--leaf-soft);
}
.form-group textarea { min-height: 120px; resize: vertical; line-height: 1.5; }
.form-group select { appearance: none; cursor: pointer; padding-right: 38px;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%236b7a72' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 14px center; }

.input-prefix { position: relative; }
.input-prefix span { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); font-weight: 600; pointer-events: none; }
.input-prefix input { padding-left: 30px; }

.type-chip { display: inline-flex; align-items: center; gap: 8px; height: 44px; padding: 0 14px; border-radius: 10px; background: var(--harvest-soft); color: #7a4f0e; font-weight: 600; font-size: 15px; width: fit-content; }

.hint { color: var(--muted); font-size: 13px; margin-top: 6px; }
.counter { text-align: right; color: var(--muted); font-size: 13px; margin-top: 6px; }

/* Buttons */
.button-container { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line); display: flex; gap: 10px; justify-content: flex-end; }
.btn-primary { display: inline-flex; align-items: center; gap: 8px; background: var(--leaf); color: #fff; border: 0; border-radius: 10px; padding: 11px 20px; font: inherit; font-weight: 600; cursor: pointer; transition: background .15s; }
.btn-primary:hover { background: var(--forest); }
.btn-ghost { display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--muted); text-decoration: none; border: 1px solid var(--line); border-radius: 10px; padding: 11px 20px; font-weight: 600; transition: background .15s, color .15s; }
.btn-ghost:hover { background: var(--paper); color: var(--ink); }

/* Responsive */
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
    .form-card { padding: 20px; }
    .form-grid { grid-template-columns: 1fr; }
    .form-group.full { grid-column: auto; }
    .button-container { flex-direction: column-reverse; }
    .btn-primary, .btn-ghost { justify-content: center; }
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
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Posts</span></a></li>
        <li><a href="buyer_post.php" class="active"><i class="fa-solid fa-plus"></i><span>Create Post</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</nav>

<main class="main">

    <div class="header">
        <div>
            <h1>Create Buyer Request</h1>
            <p>Tell farmers what agricultural product you are looking for.</p>
        </div>
        <div class="profile"><i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($user_name) ?></div>
    </div>

    <div class="form-card">

        <div class="card-head">
            <div class="card-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>
                <h2>Product request</h2>
                <p>Enter the product, quantity and budget you need.</p>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grid">

                <!-- PRODUCT -->
                <div class="form-group">
                    <label for="product">Product <span class="req">*</span></label>
                    <input type="text" id="product" name="product" placeholder="e.g. Rice"
                           value="<?= htmlspecialchars($_POST['product'] ?? '') ?>" required>
                </div>

                <!-- VARIETY -->
                <div class="form-group">
                    <label for="variety">Variety <span class="opt">(optional)</span></label>
                    <input type="text" id="variety" name="variety" placeholder="e.g. Dinorado"
                           value="<?= htmlspecialchars($_POST['variety'] ?? '') ?>">
                </div>

                <!-- QUANTITY -->
                <div class="form-group">
                    <label for="quantity">Quantity needed <span class="req">*</span></label>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="0.01" placeholder="e.g. 50"
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
                </div>

                <!-- UNIT -->
                <div class="form-group">
                    <label for="unit">Unit <span class="req">*</span></label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <?php foreach ($units as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['unit'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- BUDGET -->
                <div class="form-group">
                    <label for="price">Budget / price per unit <span class="req">*</span></label>
                    <div class="input-prefix">
                        <span>₱</span>
                        <input type="number" id="price" name="price" min="0" step="0.01" placeholder="50.00"
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                    </div>
                    <div class="hint">The most you are willing to pay for each unit.</div>
                </div>

                <!-- POST TYPE -->
                <div class="form-group">
                    <label>Post type</label>
                    <div class="type-chip"><i class="fa-solid fa-cart-shopping"></i> Wanted</div>
                    <div class="hint">Buyer requests are always posted as Wanted.</div>
                </div>

                <!-- DESCRIPTION -->
                <div class="form-group full">
                    <label for="description">Description <span class="opt">(optional)</span></label>
                    <textarea id="description" name="description" maxlength="500"
                              placeholder="Describe the product you are looking for..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    <div class="counter"><span id="count">0</span>/500</div>
                </div>

            </div>

            <div class="button-container">
                <a href="buyer.php" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> Create request</button>
            </div>

        </form>
    </div>

</main>

<script>
    // Live character counter for the description box
    const desc = document.getElementById('description');
    const count = document.getElementById('count');
    const update = () => { count.textContent = desc.value.length; };
    desc.addEventListener('input', update);
    update();
</script>

</body>
</html>