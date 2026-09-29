<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require './database/connection.php';

$error = "";


// LOGIN PROCESS

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check empty fields
    if (empty($email) || empty($password)) {

        $error = "Please fill all fields!";

    } else {

        try {

            // Find user by email
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);


            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {

                // Check account status
                if ($user['status'] !== 'active') {

                    $error = "Your account is inactive.";

                } else {

                    $_SESSION['user_id'] = $user['user_id'];

                    $_SESSION['user'] =
                        $user['firstName'] . ' ' . $user['lastName'];

                    $_SESSION['role'] = $user['role'];

                    $_SESSION['email'] = $user['email'];

                    // REDIRECT BY ROLE

                    if ($user['role'] === 'buyer') {

                        header("Location: buyers/buyer.php");
                        exit();

                    } elseif ($user['role'] === 'farmer') {

                        header("Location: seller/farmer.php");
                        exit();

                    } elseif ($user['role'] === 'admin') {

                        header("Location: admin/admin.php");
                        exit();

                    } else {

                        $error = "Invalid user role.";

                    }
                }

            } else {

                $error = "Invalid email or password!";

            }

        } catch (PDOException $e) {

            $error = "Database error: " . $e->getMessage();

        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            padding: 20px;
        }

        .card-box {
            background: #ffffff;
            width: 100%;
            max-width: 380px;
            padding: 40px 32px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .title {
            text-align: center;
            font-size: 1.4rem;
            font-weight: 600;
            color: #2E7D32;
            margin-bottom: 24px;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 16px;
        }

        .alert-danger {
            background-color: #fdecea;
            color: #b71c1c;
            border: 1px solid #f5c6cb;
        }

        .mb-3 {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 11px;
            background-color: #2E7D32;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #256428;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 16px;
        }

        a {
            color: #2E7D32;
            text-decoration: none;
            font-size: 0.9rem;
        }

        a:hover {
            text-decoration: underline;
        }

        hr {
            border: none;
            border-top: 1px solid #eee;
            margin: 20px 0 0;
        }
    </style>
</head>
<body>
<div class="card-box">

    <div class="title">
        Farm to Market System
    </div>

    <?php if($error != ""): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="mb-3">
            <label>Email</label>
            <input type="text" name="email" class="form-control">
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control">
        </div>

        <button type="submit" name="login" class="btn btn-primary btn-login">
            Login
        </button>

        <div class="text-center mt-3">
            <a href="register.php">Create Account!</a>
        </div>
    </form>

    <hr>

    <!-- g_id_onload contains Google Identity Services settings -->
    <div
      id="g_id_onload"
      data-auto_prompt="false"
      data-callback="handleCredentialResponse"
      data-client_id="867338478390-0d6a06kjso3dect629o0mk7mq681jr9c.apps.googleusercontent.com"
    ></div>
    <!-- g_id_signin places the button on a page and supports customization -->
    <div class="g_id_signin"></div>
  </body>
</html>
<hr style="margin: 25px 0;">
    </div>

</div>
</body>
</html>