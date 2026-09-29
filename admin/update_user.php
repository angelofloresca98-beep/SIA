<?php

require '../database/connection.php';

if (!isset($_GET['user_id'])) {
    header("Location: admin.php");
    exit();
}

$user_id = $_GET['user_id'];


/* =========================
   GET USER DATA
========================= */

$sql = "
    SELECT *
    FROM users
    WHERE user_id = :user_id
";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {
    echo "User not found.";
    exit();
}


/* =========================
   UPDATE USER
========================= */

if (isset($_POST['update'])) {

    $firstName = trim($_POST['firstName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');


    /* =========================
       VALIDATION
    ========================= */

    if (
        empty($firstName) ||
        empty($lastName) ||
        empty($email) ||
        empty($role) ||
        empty($status)
    ) {

        $error = "Please fill in all required fields.";

    } else {

        try {

            /* =========================
               UPDATE DATABASE
            ========================= */

            $sql = "
                UPDATE users
                SET
                    firstName = :firstName,
                    middleName = :middleName,
                    lastName = :lastName,
                    email = :email,
                    role = :role,
                    status = :status,
                    contact_number = :contact_number,
                    address = :address

                WHERE user_id = :user_id
            ";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ':firstName' => $firstName,
                ':middleName' => $middleName,
                ':lastName' => $lastName,
                ':email' => $email,
                ':role' => $role,
                ':status' => $status,
                ':contact_number' => $contact_number,
                ':address' => $address,
                ':user_id' => $user_id
            ]);


            header("Location: admin.php?msg=updated");
            exit();

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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Update User</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }


        body {

            background:
            radial-gradient(
                circle at top left,
                #1d4ed8 0%,
                transparent 25%
            ),

            radial-gradient(
                circle at bottom right,
                #7c3aed 0%,
                transparent 25%
            ),

            #050816;

            color: white;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px;

        }


        /* =========================
           CARD
        ========================= */

        .form-card {

            width: 550px;

            max-width: 100%;

            background: rgba(10,15,35,0.75);

            border: 1px solid rgba(255,255,255,0.08);

            border-radius: 28px;

            padding: 30px;

            backdrop-filter: blur(18px);

            box-shadow:
                0 0 40px rgba(0,0,0,0.4);

        }


        /* =========================
           TITLE
        ========================= */

        .form-card h2 {

            text-align: center;

            margin-bottom: 25px;

            font-weight: 700;

        }


        /* =========================
           LABEL
        ========================= */

        label {

            display: block;

            margin-top: 12px;

            margin-bottom: 6px;

            color: #cbd5e1;

            font-size: 14px;

        }


        /* =========================
           INPUTS
        ========================= */

        .form-control,
        .form-select {

            background: rgba(255,255,255,0.08);

            border: 1px solid rgba(255,255,255,0.08);

            color: white;

            padding: 13px;

            border-radius: 14px;

        }


        .form-control:focus,
        .form-select:focus {

            background: rgba(255,255,255,0.12);

            color: white;

            box-shadow: none;

            border: 1px solid #7c3aed;

        }


        .form-control::placeholder {

            color: #94a3b8;

        }


        .form-select option {

            background: #111827;

            color: white;

        }


        /* =========================
           ERROR
        ========================= */

        .error-message {

            background: rgba(220,38,38,0.15);

            border: 1px solid rgba(220,38,38,0.4);

            color: #fca5a5;

            padding: 12px;

            border-radius: 10px;

            margin-bottom: 15px;

            font-size: 14px;

        }


        /* =========================
           BUTTON
        ========================= */

        .btn-success {

            width: 100%;

            background:
                linear-gradient(
                    90deg,
                    #16a34a,
                    #22c55e
                );

            border: none;

            padding: 12px;

            border-radius: 14px;

            font-weight: 600;

            margin-top: 20px;

        }


        .btn-secondary {

            width: 100%;

            margin-top: 10px;

            border-radius: 14px;

            padding: 12px;

        }


        /* =========================
           NAME ROW
        ========================= */

        .name-row {

            display: grid;

            grid-template-columns:
                1fr
                1fr
                1fr;

            gap: 10px;

        }


        @media (max-width: 600px) {

            .name-row {

                grid-template-columns: 1fr;

            }

            .form-card {

                padding: 20px;

            }

        }

    </style>

</head>


<body>


<div class="form-card">


    <h2>
        Update User
    </h2>


    <?php if (isset($error)): ?>

        <div class="error-message">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <!-- =========================
             NAME
        ========================= -->

        <label>
            Name
        </label>


        <div class="name-row">


            <div>

                <input
                    type="text"
                    name="firstName"
                    class="form-control"
                    placeholder="First Name"
                    value="<?= htmlspecialchars($user['firstName']) ?>"
                    required
                >

            </div>


            <div>

                <input
                    type="text"
                    name="middleName"
                    class="form-control"
                    placeholder="Middle Name"
                    value="<?= htmlspecialchars($user['middleName'] ?? '') ?>"
                >

            </div>


            <div>

                <input
                    type="text"
                    name="lastName"
                    class="form-control"
                    placeholder="Last Name"
                    value="<?= htmlspecialchars($user['lastName']) ?>"
                    required
                >

            </div>


        </div>


        <!-- =========================
             EMAIL
        ========================= -->

        <label>
            Email
        </label>


        <input
            type="email"
            name="email"
            class="form-control"
            value="<?= htmlspecialchars($user['email']) ?>"
            required
        >


        <!-- =========================
             CONTACT
        ========================= -->

        <label>
            Contact Number
        </label>


        <input
            type="text"
            name="contact_number"
            class="form-control"
            placeholder="Contact Number"
            value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
        >


        <!-- =========================
             ADDRESS
        ========================= -->

        <label>
            Address
        </label>


        <textarea
            name="address"
            class="form-control"
            rows="3"
            placeholder="Address"
        ><?= htmlspecialchars($user['address'] ?? '') ?></textarea>


        <!-- =========================
             ROLE
        ========================= -->

        <label>
            Role
        </label>


        <select
            name="role"
            class="form-select"
            required
        >

            <option
                value="farmer"
                <?= $user['role'] === 'farmer' ? 'selected' : '' ?>
            >
                Farmer
            </option>


            <option
                value="buyer"
                <?= $user['role'] === 'buyer' ? 'selected' : '' ?>
            >
                Buyer
            </option>


            <option
                value="admin"
                <?= $user['role'] === 'admin' ? 'selected' : '' ?>
            >
                Admin
            </option>

        </select>


        <!-- =========================
             STATUS
        ========================= -->

        <label>
            Status
        </label>


        <select
            name="status"
            class="form-select"
            required
        >

            <option
                value="active"
                <?= $user['status'] === 'active' ? 'selected' : '' ?>
            >
                Active
            </option>


            <option
                value="inactive"
                <?= $user['status'] === 'inactive' ? 'selected' : '' ?>
            >
                Inactive
            </option>

        </select>


        <!-- =========================
             BUTTON
        ========================= -->

        <button
            type="submit"
            name="update"
            class="btn btn-success"
        >
            Update User
        </button>


        <a
            href="admin.php"
            class="btn btn-secondary"
        >
            Back
        </a>


    </form>


</div>


</body>

</html>