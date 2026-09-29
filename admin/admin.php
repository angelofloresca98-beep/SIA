<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require '../database/connection.php';

// TOTAL USERS
$totalUsers = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role IN ('farmer', 'buyer')"
)->fetchColumn();

// TOTAL FARMERS
$totalFarmers = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role='farmer'")->fetchColumn();

// TOTAL BUYERS
$totalBuyers = $conn->query(
    "SELECT COUNT(*) FROM users WHERE role='buyer'")->fetchColumn();

// FARMERS LIST
$farmerStmt = $conn->prepare("SELECT * FROM users WHERE role='farmer' ORDER BY user_id DESC");
$farmerStmt->execute();
$farmers = $farmerStmt->fetchAll(PDO::FETCH_ASSOC);

// BUYERS LIST
$buyerStmt = $conn->prepare("SELECT * FROM users WHERE role='buyer' ORDER BY user_id DESC");
$buyerStmt->execute();
$buyers = $buyerStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Admin Dashboard</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap -->
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

    <div class="stats">


        <!-- TOTAL USERS -->

        <div class="stat-card">

            <div class="icon-box blue">

                <i class="fa-solid fa-users"></i>

            </div>


            <div>

                <h3>

                    <?= htmlspecialchars($totalUsers) ?>

                </h3>

                <p>
                    Total Users
                </p>

            </div>

        </div>


        <!-- FARMERS -->

        <div class="stat-card">

            <div class="icon-box green">

                <i class="fa-solid fa-tractor"></i>

            </div>


            <div>

                <h3>

                    <?= htmlspecialchars($totalFarmers) ?>

                </h3>

                <p>
                    Farmers
                </p>

            </div>

        </div>


        <!-- BUYERS -->

        <div class="stat-card">

            <div class="icon-box orange">

                <i class="fa-solid fa-cart-shopping"></i>

            </div>


            <div>

                <h3>

                    <?= htmlspecialchars($totalBuyers) ?>

                </h3>

                <p>
                    Buyers
                </p>

            </div>

        </div>

    </div>

    <div class="table-card">


        <div class="d-flex justify-content-between align-items-center mb-4">


            <h4>
                Users Management
            </h4>


            <a
                href="add.php"
                class="btn btn-primary"
            >

                <i class="fa-solid fa-plus"></i>

                Add New User

            </a>


        </div>


        <!-- TABS -->

        <ul class="nav nav-tabs" id="userTabs" role="tablist">

            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="farmer-tab" data-bs-toggle="tab" data-bs-target="#farmer" type="button" role="tab">
                    <i class="fa-solid fa-tractor me-1"></i>
                    Farmers (<?= count($farmers) ?>)
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="buyer-tab" data-bs-toggle="tab" data-bs-target="#buyer" type="button" role="tab">
                    <i class="fa-solid fa-cart-shopping me-1"></i>
                    Buyers (<?= count($buyers) ?>)
                </button>
            </li>

        </ul>


        <div class="tab-content" id="userTabsContent">


            <!-- FARMERS TAB -->

            <div class="tab-pane fade show active" id="farmer" role="tabpanel">

                <?php if (count($farmers) === 0): ?>

                    <div class="alert alert-warning">
                        No farmers found.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>Adress</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($farmers as $row): ?>

                                    <tr>

                                        <td><?= htmlspecialchars($row['user_id']) ?></td>

                                        <td><?= htmlspecialchars($row['firstName']) ?></td>

                                        <td><?= htmlspecialchars($row['middleName']) ?></td>

                                        <td><?= htmlspecialchars($row['lastName']) ?></td>

                                        <td><?= htmlspecialchars($row['address']) ?></td>

                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>

                                        <td><?= htmlspecialchars($row['email']) ?></td>

                                        

                                        <td>
                                            <span class="badge-farmer">Farmer</span>
                                        </td>

                                        <td>

                                            <a
                                                href="update_user.php?user_id=<?= urlencode($row['user_id']) ?>"
                                                class="action-btn edit-btn"
                                                title="Edit User"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <a
                                                href="delete_user.php?user_id=<?= urlencode($row['user_id']) ?>"
                                                class="action-btn delete-btn"
                                                title="Delete User"
                                                onclick="return confirm('Are you sure you want to delete this user?')"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

<!-- Buyers Tab -->
            <div class="tab-pane fade" id="buyer" role="tabpanel">

                <?php if (count($buyers) === 0): ?>

                    <div class="alert alert-warning">
                        No buyers found.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Last Name</th>
                                    <th>Adress</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($buyers as $row): ?>

                                    <tr>
                                        <td><?= htmlspecialchars($row['user_id']) ?></td>

                                        <td><?= htmlspecialchars($row['firstName']) ?></td>

                                        <td><?= htmlspecialchars($row['middleName']) ?></td>

                                        <td><?= htmlspecialchars($row['lastName']) ?></td>

                                        <td><?= htmlspecialchars($row['address']) ?></td>

                                        <td><?= htmlspecialchars($row['contact_number']) ?></td>

                                        <td><?= htmlspecialchars($row['email']) ?></td>


                                        <td>
                                            <span class="badge-buyer">Buyer</span>
                                        </td>

                                        <td>

                                            <a
                                                href="update_user.php?user_id=<?= urlencode($row['user_id']) ?>"
                                                class="action-btn edit-btn"
                                                title="Edit User"
                                            >
                                                <i class="fa-solid fa-pen"></i>
                                            </a>

                                            <a
                                                href="delete_user.php?user_id=<?= urlencode($row['user_id']) ?>"
                                                class="action-btn delete-btn"
                                                title="Delete User"
                                                onclick="return confirm('Are you sure you want to delete this user?')"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                            </a>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>


    </div>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>