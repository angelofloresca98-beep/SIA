<?php
session_start();
require '../database/connection.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];

$s = $conn->prepare("SELECT firstName, address, contact_number FROM users WHERE user_id=?");
$s->execute([$user_id]);
$u = $s->fetch(PDO::FETCH_ASSOC);

// Already complete? Go to dashboard.
if ($u && trim((string)$u['address']) !== '' && trim((string)$u['contact_number']) !== '') {
    header("Location: buyer.php"); exit();
}

$address = trim($u['address'] ?? '');
$contact = trim($u['contact_number'] ?? '');
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $contact = preg_replace('/[\s\-]/', '', trim($_POST['contact_number'] ?? ''));

    if ($address === '') {
        $errors['address'] = 'Location is required.';
    } elseif (mb_strlen($address) < 5) {
        $errors['address'] = 'Please enter a more complete location.';
    }

    if ($contact === '') {
        $errors['contact'] = 'Contact number is required.';
    } elseif (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contact)) {
        $errors['contact'] = 'Use a valid PH mobile number (e.g. 09123456789 or +639123456789).';
    }

    if (!$errors) {
        $up = $conn->prepare("UPDATE users SET address=?, contact_number=? WHERE user_id=?");
        $up->execute([$address, $contact, $user_id]);
        header("Location: buyer.php"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Your Profile · Farm to Market</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c2a24;--muted:#6b7a72;--paper:#f4f6f2;--card:#fff;--line:#e4e9e2;--forest:#1f4d3a;--leaf:#2f7d4f;--harvest:#b7791f;--danger:#b3261e;--danger-soft:#fbe3e0;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Figtree',system-ui,sans-serif;background:var(--paper);color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:32px;width:100%;max-width:460px;}
.icon{width:54px;height:54px;border-radius:12px;background:#fbeccb;color:var(--harvest);display:flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:16px;}
h1{font-size:22px;letter-spacing:-.02em;margin-bottom:6px;}
p.sub{color:var(--muted);margin-bottom:22px;font-size:14.5px;}
label{display:block;font-weight:600;font-size:14px;margin-bottom:6px;}
label span{color:var(--danger);}
input,textarea{width:100%;font:inherit;padding:11px 14px;border:1px solid var(--line);border-radius:9px;background:#fff;color:var(--ink);}
textarea{resize:vertical;min-height:84px;}
input:focus,textarea:focus{outline:3px solid #f0d9a8;border-color:var(--harvest);}
.field{margin-bottom:18px;}
.field.has-error input,.field.has-error textarea{border-color:var(--danger);background:var(--danger-soft);}
.err{color:var(--danger);font-size:13px;margin-top:5px;}
button{width:100%;background:var(--leaf);color:#fff;border:none;border-radius:9px;padding:12px;font:inherit;font-weight:600;font-size:15px;cursor:pointer;transition:background .15s;}
button:hover{background:var(--forest);}
.out{display:block;text-align:center;margin-top:14px;color:var(--muted);font-size:13.5px;text-decoration:none;}
.out:hover{color:var(--ink);}
</style>
</head>
<body>
<form class="card" method="POST" novalidate>
    <div class="icon"><i class="fa-solid fa-user-pen"></i></div>
    <h1>Complete your profile</h1>
    <p class="sub">Hi <?= htmlspecialchars($u['firstName'] ?? 'there') ?>! Farmers need your location and contact number. Please fill these in to continue.</p>

    <div class="field <?= isset($errors['address']) ? 'has-error' : '' ?>">
        <label for="address">Location / Address <span>*</span></label>
        <textarea id="address" name="address" required placeholder="House no., Street, Barangay, City"><?= htmlspecialchars($address) ?></textarea>
        <?php if (isset($errors['address'])): ?><div class="err"><?= htmlspecialchars($errors['address']) ?></div><?php endif; ?>
    </div>

    <div class="field <?= isset($errors['contact']) ? 'has-error' : '' ?>">
        <label for="contact_number">Contact Number <span>*</span></label>
        <input type="tel" id="contact_number" name="contact_number" required placeholder="09123456789" value="<?= htmlspecialchars($contact) ?>">
        <?php if (isset($errors['contact'])): ?><div class="err"><?= htmlspecialchars($errors['contact']) ?></div><?php endif; ?>
    </div>

    <button type="submit"><i class="fa-solid fa-check"></i> Save &amp; Continue</button>
    <a class="out" href="../logout.php">Logout</a>
</form>
</body>
</html>