<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require '../database/connection.php';

if(isset($_POST['create'])){
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $lastName = trim($_POST['lastName']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if(empty($firstName) || empty($middleName) || empty($lastName) || empty($email) || empty($password)){
        $_SESSION['message'] = "All fields are required!";
        $_SESSION['type'] = "danger";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // check email
            $sql = "SELECT user_id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            if($stmt->rowCount() > 0){
                $_SESSION['message'] = "Email already exists!";
                $_SESSION['type'] = "danger";
            } else {

                // insert user
                $sql = "INSERT INTO users (firstName, middleName, lastName, email, password, role, address, contact_number) VALUES (?,?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);

                if($stmt->execute([$firstName, $middleName, $lastName, $email, $hashedPassword, $role, $address, $contact])){
                    $_SESSION['message'] = "Successfully Registered!";
                    $_SESSION['type'] = "success";
                }
            }

        } catch(PDOException $e){
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['type'] = "danger";
        }
    }

    header("Location: add.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<!-- Font Awesome -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<!-- Google Font -->
<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>


* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #f5f7fb;
    color: #333;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background: linear-gradient(
        180deg,
        #2563eb,
        #1d4ed8
    );

    padding: 25px 15px;

    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.08);
}

.logo {
    color: white;

    font-size: 22px;
    font-weight: 700;

    text-align: center;

    margin-bottom: 35px;
}

.sidebar a {
    display: flex;
    align-items: center;

    gap: 15px;

    color: rgba(255, 255, 255, 0.85);

    text-decoration: none;

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 8px;

    transition: 0.3s;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(255, 255, 255, 0.18);
    color: white;
}

.sidebar a i {
    width: 20px;
}

.main {
    margin-left: 250px;
    padding: 30px;
}


.topbar {
    display: flex;

    justify-content: space-between;
    align-items: center;

    background: white;

    padding: 22px 25px;

    border-radius: 15px;

    margin-bottom: 25px;

    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.04);
}

.topbar h2 {
    font-weight: 600;
    margin-bottom: 5px;
}


.stats {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 20px;

    margin-bottom: 25px;
}

.stat-card {
    background: white;

    padding: 25px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    gap: 18px;

    box-shadow:
        0 3px 15px rgba(0, 0, 0, 0.04);
}

.icon-box {
    width: 55px;
    height: 55px;

    border-radius: 12px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 22px;
}



.icon-box.blue {
    background: #dbeafe;
    color: #2563eb;
}


.icon-box.green {
    background: #dcfce7;
    color: #16a34a;
}

.icon-box.orange {
    background: #ffedd5;
    color: #ea580c;
}

.stat-card h3 {
    font-size: 28px;

    font-weight: 600;

    margin: 0;
}

.stat-card p {
    color: #777;

    margin: 3px 0 0;
}


.table-card {
    background: white;

    padding: 25px;

    border-radius: 15px;

    box-shadow:
        0 3px 15px rgba(0, 0, 0, 0.04);
}

.table-card h4 {
    font-weight: 600;
}

.nav-tabs {
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 20px;
}

.nav-tabs .nav-link {
    color: #777;
    border: none;
    font-weight: 500;
    padding: 10px 20px;
    border-radius: 0;
}

.nav-tabs .nav-link.active {
    background: transparent;
    color: #2563eb;
    border-bottom: 3px solid #2563eb;
    font-weight: 600;
}


.table thead th {
    color: #555;

    font-weight: 600;

    background: #f8fafc;
}

.table tbody tr {
    vertical-align: middle;
}

.badge-admin,
.badge-farmer,
.badge-buyer,
.badge-user {

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 500;

    display: inline-block;
}




.badge-admin {
    background: #ede9fe;
    color: #7c3aed;
}




.badge-farmer {
    background: #dcfce7;
    color: #15803d;
}


.badge-buyer {
    background: #dbeafe;
    color: #1d4ed8;
}


.badge-user {
    background: #f1f5f9;
    color: #475569;
}


.action-btn {

    width: 35px;
    height: 35px;

    border-radius: 8px;

    border: none;

    margin-right: 5px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;

    transition: 0.2s;
}

.edit-btn {
    background: #dbeafe;
    color: #2563eb;
}

.edit-btn:hover {
    background: #2563eb;
    color: white;
}

.delete-btn {
    background: #fee2e2;
    color: #dc2626;
}

.delete-btn:hover {
    background: #dc2626;
    color: white;
}


.no-data {
    text-align: center;
    color: #999;
    padding: 20px;
}


@media (max-width: 992px) {

    .sidebar {
        width: 210px;
    }

    .main {
        margin-left: 210px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}


@media (max-width: 768px) {

    .sidebar {
        position: relative;

        width: 100%;
        height: auto;
    }

    .sidebar a {
        display: inline-flex;

        margin-right: 5px;
    }

    .main {
        margin-left: 0;

        padding: 15px;
    }

    .topbar {
        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .table-card {
        padding: 15px;
    }
}

</style>

</head>


<body>

<div class="sidebar">

    <div class="logo">
        ✨ Admin Panel
    </div>


    <!-- DASHBOARD -->

    <a href="admin.php" class="active">

        <i class="fa-solid fa-house"></i>

        Dashboard

    </a>


    <!-- USERS -->

    <a href="admin.php">

        <i class="fa-solid fa-users"></i>

        Users

    </a>


    <!-- ADD USER -->

    <a href="add.php">

        <i class="fa-solid fa-user-plus"></i>

        Add User

    </a>


    <!-- LOGOUT -->

    <a href="../logout.php">

        <i class="fa-solid fa-right-from-bracket"></i>

        Logout

    </a>

</div>


<div class="main">

    <div class="topbar">

        <div>

            <h2>
                Dashboard
            </h2>

            <p class="text-muted mb-0">

                Welcome back!
                Here's your overview 👋

            </p>

        </div>


        <div>

            <strong>

                <?= htmlspecialchars($_SESSION['user']) ?>

            </strong>

            <br>

            <small class="text-muted">

                Administrator

            </small>

        </div>

    </div>

<div class="register-card">

    <h3 class="text-center mb-3 text-black">Create Account</h3>

    <!-- ALERT MESSAGE -->
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['type']; ?>">
            <?= $_SESSION['message']; ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- REGISTER FORM -->
    <form method="POST">
         <div class="mb-3">
            <label>First Name</label>
            <input type="text" name="firstName" class="form-control">
        </div>

        <div class="mb-3">
            <label>Middle Name</label>
            <input type="text" name="middleName" class="form-control">
        </div>

        <div class="mb-3">
            <label>Last Name</label>
            <input type="text" name="lastName" class="form-control">
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control">
        </div>

        <div class="mb-3">
             <label>Role</label>
                <select name="role" class="form-control">
                <option value="" disabled selected>Select a role</option>
                <option value="farmer">Farmer</option>
                <option value="buyer">Buyer</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control">
        </div>

        <div class="mb-3">
            <label>Contact Number</label>
            <input type="text" name="contact" class="form-control">
        </div>

        <button type="submit" name="create" class="btn btn-register">
            Register
        </button>

        <div class="text-center mt-3">
            <a href="admin.php" class="back-login">
                ← Back to dashboard
            </a>
        </div>

    </form>

</div>

</body>
</html>