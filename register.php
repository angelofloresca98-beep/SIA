<?php
session_start();
require './database/connection.php';

if(isset($_POST['create'])){
    $firstName = trim($_POST['firstName']);
    $middleName = trim($_POST['middleName']);
    $lastName = trim($_POST['lastName']);

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = "admin";

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
                $sql = "INSERT INTO users (firstName, middleName, lastName, email, password, role) VALUES (?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);

                if($stmt->execute([$firstName, $middleName, $lastName, $email, $hashedPassword, $role])){
                    $_SESSION['message'] = "Successfully Registered!";
                    $_SESSION['type'] = "success";
                }
            }

        } catch(PDOException $e){
            $_SESSION['message'] = "Error: " . $e->getMessage();
            $_SESSION['type'] = "danger";
        }
    }

    header("Location: register.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<link rel="stylesheet" href="style.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;

    background:
    linear-gradient(135deg,#0b1020,#111827,#1e1b4b);

    overflow:hidden;
}

/* BACKGROUND GLOW */

body::before{
    content:'';

    position:absolute;

    width:500px;
    height:500px;

    background:#7c3aed;

    border-radius:50%;

    top:-150px;
    left:-150px;

    filter:blur(150px);

    opacity:0.5;
}

body::after{
    content:'';

    position:absolute;

    width:400px;
    height:400px;

    background:#2563eb;

    border-radius:50%;

    bottom:-120px;
    right:-120px;

    filter:blur(140px);

    opacity:0.5;
}

/* LOGIN BOX */

.card-box{

    position:relative;
    z-index:10;

    width:420px;

    padding:40px;

    border-radius:30px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    border:1px solid rgba(255,255,255,0.1);

    box-shadow:
    0 0 30px rgba(139,92,246,0.3);

    animation:fadeIn 1s ease;
}

/* TITLE */

.title{

    text-align:center;

    font-size:33px;
    font-weight:700;

    color:white;

    margin-bottom:30px;
}

/* LABEL */

label{
    color:#e2e8f0;
    margin-bottom:8px;
}

/* INPUT */

.form-control{

    background:rgba(255,255,255,0.08) !important;

    border:none !important;

    color:white !important;

    padding:14px !important;

    border-radius:14px !important;

    transition:0.3s;
}

.form-control:focus{

    box-shadow:
    0 0 15px rgba(115, 114, 115, 0.5) !important;

    background:rgba(255,255,255,0.12) !important;
}

.form-control::placeholder{
    color:#cbd5e1;
}

/* BUTTON */

.btn-login{

    width:100%;

    padding:14px;

    border:none;

    border-radius:14px;

    background:
    linear-gradient(135deg,#7c3aed,#a855f7);

    font-size:16px;
    font-weight:600;

    transition:0.3s;
}

.btn-login:hover{

    transform:translateY(-3px);

    box-shadow:
    0 0 20px rgba(218, 218, 218, 0.5);
}

/* LINK */

a{
    color:#c084fc;
    text-decoration:none;
}

a:hover{
    color:white;
}

/* ALERT */

.alert{
    border-radius:12px;
}

/* ANIMATION */

@keyframes fadeIn{

    from{
        opacity:0;
        transform:translateY(30px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }

}

.register-card{

    position:relative;
    z-index:10;

    width:420px;

    padding:40px;

    border-radius:30px;

    background:rgba(255,255,255,0.08);

    backdrop-filter:blur(20px);

    border:2px solid rgba(255,255,255,0.2);

    box-shadow:
    0 0 30px rgba(139,92,246,0.3);

    animation:fadeIn 1s ease;
}
</style>
</head>

<body>

<div class="register-card">

    <h3 class="text-center mb-3 text-white">Create Account</h3>

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

        <button type="submit" name="create" class="btn btn-register">
            Register
        </button>

        <div class="text-center mt-3">
            <a href="login.php" class="back-login">
                ← Back to Login
            </a>
        </div>

    </form>

</div>

</body>
</html>