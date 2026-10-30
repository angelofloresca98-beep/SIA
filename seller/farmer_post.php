<?php

session_start();

require '../database/connection.php';

// CHECK LOGIN

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit();

}

// CHECK FARMER ROLE

if ($_SESSION['role'] !== 'farmer') {

    header("Location: ../login.php");
    exit();

}

// GET LOGGED-IN FARMER

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";

// SUCCESS MESSAGE (set after a successful post, shown once)

if (isset($_SESSION['flash_success'])) {

    $message = $_SESSION['flash_success'];
    $message_type = "success";

    unset($_SESSION['flash_success']);

}

// IMAGE UPLOAD SETTINGS

const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 MB

const ALLOWED_IMAGE_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

$uploadDirRelative = 'uploads/products/';                 // saved in the database
$uploadDirAbsolute = dirname(__DIR__) . '/' . $uploadDirRelative; // real path on disk

// ALLOWED UNITS (also used to validate the submitted value)

$unitOptions = [
    'kg'    => 'Kilogram (kg)',
    'sack'  => 'Sack',
    'ton'   => 'Ton',
    'piece' => 'Piece',
    'box'   => 'Box',
    'liter' => 'Liter',
];


// FORM SUBMISSION

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // GET FORM VALUES

    $product = trim($_POST['product'] ?? '');
    $variety = trim($_POST['variety'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $savedImagePath = null;  // path stored in DB
    $savedImageFile = null;  // full path on disk (used for cleanup)


    // VALIDATION

    // If the upload is larger than PHP's post_max_size, $_POST and $_FILES arrive empty
    if (empty($_POST) && empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {

        $message = "The upload is too large. Please choose an image under 5 MB.";
        $message_type = "error";

    } elseif ($product === '' || $unit === '' || $price === '' || $quantity === '') {

        $message = "Please fill in all required fields.";
        $message_type = "error";

    } elseif (mb_strlen($product) < 2 || mb_strlen($product) > 100) {

        $message = "Product name must be between 2 and 100 characters.";
        $message_type = "error";

    } elseif (!preg_match('/^[\p{L}\p{N}\s\-\'.,()&\/]+$/u', $product)) {

        $message = "Product name contains invalid characters.";
        $message_type = "error";

    } elseif (mb_strlen($variety) > 100) {

        $message = "Variety must not exceed 100 characters.";
        $message_type = "error";

    } elseif (!array_key_exists($unit, $unitOptions)) {

        $message = "Please select a valid unit.";
        $message_type = "error";

    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $price) || (float)$price <= 0 || (float)$price > 1000000) {

        $message = "Price must be greater than 0, up to 1,000,000, with at most 2 decimal places.";
        $message_type = "error";

    } elseif (!preg_match('/^\d+(\.\d{1,2})?$/', $quantity) || (float)$quantity < 1 || (float)$quantity > 1000000) {

        $message = "Quantity must be between 1 and 1,000,000, with at most 2 decimal places.";
        $message_type = "error";

    } elseif (mb_strlen($description) > 1000) {

        $message = "Description must not exceed 1000 characters.";
        $message_type = "error";

    } else {

        // IMAGE VALIDATION (required field)

        $hasUpload = isset($_FILES['image'])
            && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE;

        if (!$hasUpload) {

            $message = "Please upload a product image.";
            $message_type = "error";

        }

        if ($hasUpload) {

            $file = $_FILES['image'];

            if ($file['error'] !== UPLOAD_ERR_OK) {

                $message = ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
                    ? "The image is too large. Maximum size is 5 MB."
                    : "The image could not be uploaded. Please try again.";
                $message_type = "error";

            } elseif ($file['size'] > MAX_IMAGE_SIZE) {

                $message = "The image is too large. Maximum size is 5 MB.";
                $message_type = "error";

            } else {

                // Check the real file type (not just the extension)
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);

                $dims = @getimagesize($file['tmp_name']);

                if (!isset(ALLOWED_IMAGE_TYPES[$mime]) || $dims === false) {

                    $message = "Only JPG, PNG, or WEBP images are allowed.";
                    $message_type = "error";

                } elseif ($dims[0] < 200 || $dims[1] < 200) {

                    $message = "The image must be at least 200 x 200 pixels.";
                    $message_type = "error";

                } elseif ($dims[0] > 6000 || $dims[1] > 6000) {

                    $message = "The image must not be larger than 6000 x 6000 pixels.";
                    $message_type = "error";

                } else {

                    if (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0755, true)) {

                        $message = "Could not create the upload folder.";
                        $message_type = "error";

                    } else {

                        // Random filename so uploads never overwrite each other
                        $newName = bin2hex(random_bytes(16)) . '.' . ALLOWED_IMAGE_TYPES[$mime];
                        $target = $uploadDirAbsolute . $newName;

                        if (move_uploaded_file($file['tmp_name'], $target)) {

                            $savedImageFile = $target;
                            $savedImagePath = $uploadDirRelative . $newName;

                        } else {

                            $message = "Failed to save the uploaded image.";
                            $message_type = "error";

                        }

                    }

                }

            }

        }

        // Only save to the database if there were no image errors

        if ($message === "") {

            try {

                // Start transaction
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
                        image,
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
                        :image,
                        'available'
                    )
                ";


                $stmtProduct = $conn->prepare($sqlProduct);


                $stmtProduct->execute([

                    ':user_id' => $user_id,

                    ':product_name' => $product,

                    ':variety' => $variety !== ''
                        ? $variety
                        : null,

                    ':unit' => $unit,

                    ':price' => $price,

                    ':quantity' => $quantity,

                    ':description' => $description !== ''
                        ? $description
                        : null,

                    ':image' => $savedImagePath

                ]);


                $product_id = $conn->lastInsertId();


                // Farmer posts are automatically "sell"

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
                        'sell'
                    )
                ";


                $stmtPost = $conn->prepare($sqlPost);


                $stmtPost->execute([

                    ':user_id' => $user_id,

                    ':product_id' => $product_id

                ]);

                $conn->commit();

                $_SESSION['flash_success'] = "Your product \"" . $product . "\" was posted successfully!";

                header("Location: farmer_post.php");
                exit();


            } catch (PDOException $e) {

                // Cancel database changes if something fails
                if ($conn->inTransaction()) {

                    $conn->rollBack();

                }

                // Remove the uploaded image so no orphan files are left behind
                if ($savedImageFile && file_exists($savedImageFile)) {

                    unlink($savedImageFile);

                }

                $message = "Something went wrong: " . $e->getMessage();

                $message_type = "error";

            }

        } elseif ($savedImageFile && file_exists($savedImageFile)) {

            unlink($savedImageFile);

        }

    }

}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1f4d3a">
<title>Create Farmer Post</title>

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
    --danger: #b3261e;
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

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

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
.page { max-width: 780px; width: 100%; }

.header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.header h1 { font-size: clamp(22px, 3.4vw, 26px); font-weight: 700; letter-spacing: -0.02em; margin-bottom: 2px; }
.header p { color: var(--muted); }

.profile { display: flex; align-items: center; gap: 10px; background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 6px 16px 6px 6px; font-weight: 600; flex-shrink: 0; }
.profile i { width: 38px; height: 38px; border-radius: 50%; background: var(--leaf-soft); color: var(--forest); display: inline-flex; align-items: center; justify-content: center; }

/* ---------- Form ---------- */
.form-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 28px; }
.form-card h2 { font-size: 19px; font-weight: 700; margin-bottom: 22px; }

.message { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 14.5px; overflow-wrap: anywhere; }
.message::before { font-family: "Font Awesome 6 Free"; font-weight: 900; margin-top: 1px; }
.message.error   { background: var(--danger-soft); color: var(--danger); }
.message.error::before   { content: "\f06a"; }
.message.success { background: var(--leaf-soft); color: var(--forest); }
.message.success::before { content: "\f058"; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { display: flex; flex-direction: column; min-width: 0; }
.form-group.full { grid-column: 1 / -1; }

.form-group label { font-weight: 600; font-size: 14px; margin-bottom: 6px; }

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    min-width: 0;
    padding: 11px 13px;
    border: 1px solid #cbd5cd;
    border-radius: 10px;
    font: inherit;
    background: #fff;
    color: var(--ink);
    transition: border-color .15s, box-shadow .15s;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

.form-group input[readonly] { background: var(--paper); color: var(--muted); cursor: default; }
.form-group textarea { min-height: 120px; resize: vertical; line-height: 1.5; }

/* ---------- Image upload ---------- */
.upload-box {
    position: relative;
    border: 2px dashed #cbd5cd;
    border-radius: var(--radius);
    background: var(--paper);
    padding: 24px 16px;
    text-align: center;
    cursor: pointer;
    transition: border-color .15s, background .15s;
}
.upload-box:hover,
.upload-box.dragover { border-color: var(--leaf); background: var(--leaf-soft); }
.upload-box:focus-within { border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

/* The real file input covers the whole box so it stays clickable and keyboard accessible */
.upload-box input[type="file"] {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    padding: 0;
    border: 0;
}

.upload-prompt i { font-size: 30px; color: var(--leaf); margin-bottom: 8px; display: block; }
.upload-prompt strong { display: block; font-size: 15px; }
.upload-prompt span { display: block; color: var(--muted); font-size: 13px; margin-top: 4px; }

.upload-box.has-image { padding: 12px; border-style: solid; background: var(--card); cursor: default; }
.upload-box.has-image .upload-prompt { display: none; }
.upload-box.has-image input[type="file"] { display: none; }

.preview { display: none; align-items: center; gap: 14px; text-align: left; }
.upload-box.has-image .preview { display: flex; }

.preview img {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--line);
    flex-shrink: 0;
    background: var(--paper);
}
.preview-info { flex: 1; min-width: 0; }
.preview-name { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.preview-size { color: var(--muted); font-size: 13px; margin-top: 2px; }

.remove-image {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; color: var(--danger);
    border: 1px solid var(--line); border-radius: 8px;
    padding: 8px 12px; font: inherit; font-size: 13.5px; font-weight: 600; cursor: pointer;
    flex-shrink: 0;
}
.remove-image:hover { background: var(--danger-soft); }

.upload-error { color: var(--danger); font-size: 13.5px; font-weight: 500; margin-top: 8px; display: none; }
.upload-error.show { display: block; }

.field-error { color: var(--danger); font-size: 13px; font-weight: 500; margin-top: 5px; display: none; }
.form-group.invalid .field-error { display: block; }
.form-group.invalid input:not([type="file"]),
.form-group.invalid select,
.form-group.invalid textarea { border-color: var(--danger); box-shadow: 0 0 0 3px rgba(179,38,30,.12); }
.upload-box.invalid { border-color: var(--danger); }
.char-count { color: var(--muted); font-size: 12px; text-align: right; margin-top: 4px; }

.button-container { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 12px; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 0; background: var(--leaf); color: #fff;
    padding: 11px 24px; border-radius: 10px;
    font: inherit; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn:hover { background: var(--forest); }

.cancel-btn {
    display: inline-flex; align-items: center; justify-content: center;
    background: #fff; color: var(--ink); text-decoration: none;
    border: 1px solid var(--line); padding: 11px 24px; border-radius: 10px; font-weight: 600;
}
.cancel-btn:hover { background: var(--paper); }

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
    .form-card { padding: 20px; }
}

@media (max-width: 560px) {
    .form-grid { grid-template-columns: 1fr; gap: 16px; }
    .form-group.full { grid-column: auto; }

    /* 16px+ inputs stop iOS Safari from zooming the page on focus; taller targets for thumbs */
    .form-group input,
    .form-group select,
    .form-group textarea { font-size: 16px; min-height: 46px; }
    .form-group textarea { min-height: 120px; }

    .preview { flex-wrap: wrap; }
    .preview img { width: 72px; height: 72px; }
    .remove-image { width: 100%; justify-content: center; min-height: 44px; }

    .button-container { flex-direction: column-reverse; }
    .btn, .cancel-btn { width: 100%; min-height: 48px; }
}

@media (max-width: 400px) {
    .form-card { padding: 16px; }
    .form-card h2 { font-size: 17px; margin-bottom: 18px; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
<link rel="stylesheet" href="theme.css">
  <script>try{var t=localStorage.getItem("fm-theme");if(!t&&window.matchMedia("(prefers-color-scheme: dark)").matches)t="dark";document.documentElement.setAttribute("data-theme",t==="dark"?"dark":"light");}catch(e){}</script>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar" aria-label="Main navigation">

    <div class="logo">
        <h2><i class="fa-solid fa-seedling"></i> FarmMarket</h2>
        <p>Farm-to-Market System</p>
    </div>

    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
        <i class="fa-solid fa-bars"></i>
    </button>

    <ul class="nav" id="main-nav">
        <li><a href="farmer.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="profile.php" class="acti"><i class="fa-solid fa-user"></i><span>My Profile</span></a></li>
        <li><a href="my_post.php"><i class="fa-solid fa-clipboard-list"></i><span>My Listings</span></a></li>
        <li><a href="farmer_post.php" class="active"><i class="fa-solid fa-plus"></i><span>Create Product</span></a></li>
        <li><a href="sales.php"><i class="fa-solid fa-chart-line"></i><span>Sales</span></a></li>
        <li><a href="transaction.php"><i class="fa-solid fa-receipt"></i><span>Transaction</span></a></li>
        <li><a href="transaction_details.php"><i class="fa-solid fa-receipt"></i><span>My Transaction</span></a></li>
        <li class="logout"><a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>

</nav>


<!-- MAIN CONTENT -->
<main class="main">
<div class="page">

    <!-- HEADER -->
    <div class="header">
        <div>
            <h1>Create Farmer Post</h1>
            <p>Post your available agricultural products.</p>
        </div>

        <div class="profile"><i class="fa-solid fa-tractor"></i> Farmer</div>
    </div>


    <!-- FORM -->
    <div class="form-card">

        <h2>Add new product</h2>

        <?php if (isset($message) && $message !== ""): ?>
            <div class="message <?= htmlspecialchars($message_type ?? '') ?>" role="alert">
                <span>
                    <?= htmlspecialchars($message) ?>
                    <?php if ($message_type === 'success'): ?>
                        <a href="my_post.php" style="color:inherit;font-weight:700;margin-left:6px;">View my listings</a>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" id="post-form" novalidate>

            <div class="form-grid">

                <!-- PRODUCT -->
                <div class="form-group">
                    <label for="product">Product *</label>
                    <input
                        type="text"
                        id="product"
                        name="product"
                        placeholder="e.g. Rice"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['product'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- VARIETY -->
                <div class="form-group">
                    <label for="variety">Variety</label>
                    <input
                        type="text"
                        id="variety"
                        name="variety"
                        placeholder="e.g. Dinorado"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['variety'] ?? '') ?>"
                    >
                </div>

                <!-- QUANTITY -->
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="1"
                        max="1000000"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 50"
                        value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- UNIT -->
                <div class="form-group">
                    <label for="unit">Unit *</label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <?php foreach ($unitOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($_POST['unit'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- PRICE -->
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0.01"
                        max="1000000"
                        step="0.01"
                        inputmode="decimal"
                        placeholder="e.g. 2500"
                        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                        required
                    >
                </div>

                <!-- POST TYPE -->
                <div class="form-group">
                    <label for="post_type_display">Post type</label>
                    <input
                        type="text"
                        id="post_type_display"
                        value="Selling"
                        readonly
                    >
                </div>

                <!-- PRODUCT IMAGE -->
                <div class="form-group full">
                    <label for="image">Product image *</label>

                    <div class="upload-box" id="upload-box">

                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/jpeg,image/png,image/webp"
                            required
                        >

                        <div class="upload-prompt">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <strong>Click to upload or drag and drop</strong>
                            <span>JPG, PNG or WEBP &middot; up to 5 MB</span>
                        </div>

                        <div class="preview" id="preview">
                            <img id="preview-img" src="" alt="Selected product image preview">
                            <div class="preview-info">
                                <div class="preview-name" id="preview-name"></div>
                                <div class="preview-size" id="preview-size"></div>
                            </div>
                            <button type="button" class="remove-image" id="remove-image">
                                <i class="fa-solid fa-trash-can"></i> Remove
                            </button>
                        </div>

                    </div>

                    <div class="upload-error" id="upload-error" role="alert"></div>
                </div>

                <!-- DESCRIPTION -->
                <div class="form-group full">
                    <label for="description">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Describe your product..."
                        maxlength="1000"
                    ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

            </div>

            <!-- BUTTONS -->
            <div class="button-container">
                <button type="submit" class="btn"><i class="fa-solid fa-seedling"></i> Create post</button>
                <a href="farmer.php" class="cancel-btn">Cancel</a>
            </div>

        </form>

    </div>

</div>
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

// Image upload preview
(function () {
    const MAX_SIZE = 5 * 1024 * 1024;
    const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    const box = document.getElementById('upload-box');
    const input = document.getElementById('image');
    const img = document.getElementById('preview-img');
    const nameEl = document.getElementById('preview-name');
    const sizeEl = document.getElementById('preview-size');
    const removeBtn = document.getElementById('remove-image');
    const errorEl = document.getElementById('upload-error');

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.classList.toggle('show', !!msg);
    }

    function formatSize(bytes) {
        return bytes < 1024 * 1024
            ? (bytes / 1024).toFixed(0) + ' KB'
            : (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function clearImage() {
        input.value = '';
        if (img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
        img.removeAttribute('src');
        box.classList.remove('has-image');
    }

    function handleFile(file) {
        showError('');

        if (!file) { clearImage(); return; }

        if (!ALLOWED.includes(file.type)) {
            clearImage();
            showError('Only JPG, PNG, or WEBP images are allowed.');
            return;
        }
        if (file.size > MAX_SIZE) {
            clearImage();
            showError('The image is too large. Maximum size is 5 MB.');
            return;
        }

        const url = URL.createObjectURL(file);
        const probe = new Image();

        probe.onload = () => {
            if (probe.naturalWidth < 200 || probe.naturalHeight < 200) {
                URL.revokeObjectURL(url);
                clearImage();
                showError('The image must be at least 200 x 200 pixels.');
                return;
            }
            if (probe.naturalWidth > 6000 || probe.naturalHeight > 6000) {
                URL.revokeObjectURL(url);
                clearImage();
                showError('The image must not be larger than 6000 x 6000 pixels.');
                return;
            }
            if (img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
            img.src = url;
            nameEl.textContent = file.name;
            sizeEl.textContent = formatSize(file.size);
            box.classList.add('has-image');
            box.classList.remove('invalid');
        };

        probe.onerror = () => {
            URL.revokeObjectURL(url);
            clearImage();
            showError('That file could not be read as an image.');
        };

        probe.src = url;
    }

    input.addEventListener('change', () => handleFile(input.files[0]));

    removeBtn.addEventListener('click', () => { showError(''); clearImage(); });

    // Drag and drop
    ['dragenter', 'dragover'].forEach(evt =>
        box.addEventListener(evt, e => { e.preventDefault(); box.classList.add('dragover'); })
    );
    ['dragleave', 'drop'].forEach(evt =>
        box.addEventListener(evt, e => { e.preventDefault(); box.classList.remove('dragover'); })
    );
    box.addEventListener('drop', e => {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        handleFile(file);
    });
})();

// Hide the success message after a few seconds
(function () {
    const ok = document.querySelector('.message.success');
    if (ok) setTimeout(() => { ok.style.display = 'none'; }, 8000);
})();

// Form validation
(function () {
    const form = document.getElementById('post-form');
    const decimals = /^\d+(\.\d{1,2})?$/;

    const rules = {
        product: v => {
            if (v === '') return 'Product name is required.';
            if (v.length < 2) return 'Product name must be at least 2 characters.';
            if (v.length > 100) return 'Product name must not exceed 100 characters.';
            if (!/^[\p{L}\p{N}\s\-'.,()&\/]+$/u.test(v)) return 'Product name contains invalid characters.';
            return '';
        },
        variety: v => v.length > 100 ? 'Variety must not exceed 100 characters.' : '',
        quantity: v => {
            if (v === '') return 'Quantity is required.';
            if (!decimals.test(v)) return 'Enter a valid number (max 2 decimal places).';
            if (parseFloat(v) < 1) return 'Quantity must be at least 1.';
            if (parseFloat(v) > 1000000) return 'Quantity must not exceed 1,000,000.';
            return '';
        },
        unit: v => v === '' ? 'Please select a unit.' : '',
        price: v => {
            if (v === '') return 'Price is required.';
            if (!decimals.test(v)) return 'Enter a valid price (max 2 decimal places).';
            if (parseFloat(v) <= 0) return 'Price must be greater than 0.';
            if (parseFloat(v) > 1000000) return 'Price must not exceed 1,000,000.';
            return '';
        },
        description: v => v.length > 1000 ? 'Description must not exceed 1000 characters.' : ''
    };

    function errorBox(field) {
        let box = field.parentElement.querySelector('.field-error');
        if (!box) {
            box = document.createElement('div');
            box.className = 'field-error';
            box.setAttribute('role', 'alert');
            field.parentElement.appendChild(box);
        }
        return box;
    }

    function check(name) {
        const field = form.elements[name];
        const msg = rules[name](field.value.trim());
        field.parentElement.classList.toggle('invalid', msg !== '');
        errorBox(field).textContent = msg;
        field.setAttribute('aria-invalid', msg !== '' ? 'true' : 'false');
        return msg === '';
    }

    function checkImage() {
        const input = form.elements['image'];
        const box = document.getElementById('upload-box');
        const err = document.getElementById('upload-error');
        if (input.files.length === 0) {
            err.textContent = 'Please upload a product image.';
            err.classList.add('show');
            box.classList.add('invalid');
            return false;
        }
        box.classList.remove('invalid');
        return true;
    }

    Object.keys(rules).forEach(name => {
        const field = form.elements[name];
        field.addEventListener('blur', () => check(name));
        field.addEventListener('input', () => {
            if (field.parentElement.classList.contains('invalid')) check(name);
        });
    });

    // Live character counter for the description
    const desc = form.elements['description'];
    const counter = document.createElement('div');
    counter.className = 'char-count';
    desc.parentElement.appendChild(counter);
    const updateCount = () => { counter.textContent = desc.value.length + ' / 1000'; };
    desc.addEventListener('input', updateCount);
    updateCount();

    form.addEventListener('submit', e => {
        let firstInvalid = null;

        Object.keys(rules).forEach(name => {
            if (!check(name) && !firstInvalid) firstInvalid = form.elements[name];
        });

        if (!checkImage() && !firstInvalid) firstInvalid = document.getElementById('upload-box');

        if (firstInvalid) {
            e.preventDefault();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (firstInvalid.focus && firstInvalid.tagName !== 'DIV') firstInvalid.focus({ preventScroll: true });
        }
    });
})();
</script>
  <script src="theme.js"></script>
</body>
</html>