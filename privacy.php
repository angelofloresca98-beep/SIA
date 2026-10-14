<?php
// ============================================
// FARM PRODUCTS AND MARKET SALES MANAGEMENT
// SYSTEM - PRIVACY POLICY PAGE
// ============================================

// Current page key
$page = 'privacy';

// Available pages (file name => title)
$pages = [
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms and Conditions',
    'moderation' => 'Moderation Policy',
    'copyright' => 'Copyright Notice'
];

$title = $pages[$page];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>

    <!-- ================================= -->
    <!-- CSS DESIGN -->
    <!-- ================================= -->
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f8f2;
            color: #243529;
            line-height: 1.7;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 35px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        h1 {
            color: #216c3a;
            margin-bottom: 5px;
        }

        h2 {
            color: #287545;
            margin-top: 25px;
            font-size: 20px;
        }

        .subtitle {
            color: #687c6d;
            margin-bottom: 30px;
        }

        p, li {
            font-size: 15px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 20px;
            background: #267a43;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .back-btn:hover {
            background: #195b30;
        }

        footer {
            background: #153e27;
            color: white;
            padding: 35px 20px;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .footer-links a {
            padding: 10px 15px;
            background: #286b43;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }

        .footer-links a:hover,
        .footer-links a.active {
            background: #43a365;
        }

        .footer-note {
            font-size: 13px;
            color: #c5dfcc;
        }

        @media (max-width: 600px) {
            .container {
                margin: 15px;
                padding: 20px;
            }

            .footer-links {
                flex-direction: column;
            }
        }

/* =====================================
   POLICY BUTTON AND SCROLL ANIMATIONS
===================================== */

html {
    scroll-behavior: smooth;
}

.policy-nav a,
footer a,
.back-btn {
    transition: all 0.3s ease;
}

.policy-nav a:hover,
.back-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 12px rgba(0,0,0,0.2);
}

.policy-nav a:active,
.back-btn:active {
    transform: scale(0.93);
}

/* Highlight selected policy */
section:target {
    animation: policyHighlight 1.2s ease;
    border-radius: 10px;
}

@keyframes policyHighlight {
    0% {
        background: #d5f5df;
        transform: scale(0.98);
    }

    50% {
        background: #e9f8ed;
    }

    100% {
        background: transparent;
        transform: scale(1);
    }
}

/* Initial page animation */
.container {
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    html {
        scroll-behavior: auto;
    }

    .container,
    section:target {
        animation: none;
    }

    .policy-nav a,
    footer a,
    .back-btn {
        transition: none;
    }
}

    </style>
</head>

<body>

<!-- ========================================= -->
<!-- MAIN POLICY CONTENT -->
<!-- ========================================= -->

<main class="container">

<h1><?php echo htmlspecialchars($title); ?></h1>
<p class="subtitle">
    Farm Products and Market Sales Management System
</p>

<!-- ===================================== -->
<!-- 1. PRIVACY POLICY -->
<!-- ===================================== -->

<section id="privacy">
    <h2>1. Privacy Policy</h2>

    <p><strong>Last Updated:</strong> October 12, 2026</p>

    <p>
        <strong>Disclaimer:</strong>
        This website is created for demonstration and academic
        purposes only as part of an SIA subject project.
        It is not a real company, official service, registered
        business, or commercial website. No claims are made
        regarding actual business operations, legal standing,
        or real-world services.
    </p>

    <p>
        The Farm Products and Market Sales Management System
        is designed to help farmers and buyers in the fictional
        Avengers City, Wakanda, manage agricultural products,
        inventory, buyer requests, and sales transactions.
        This policy explains how user information may be
        collected, used, stored, and protected.
    </p>

    <!-- 1.1 INFORMATION COLLECTION -->
    <h3>1.1 Information We Collect</h3>
    <p>We may collect the following information:</p>
    <ul>
        <li>User names, emails, contact details, and account roles.</li>
        <li>Farmer, buyer, and supplier profile information.</li>
        <li>Product images, descriptions, prices, and stock quantities.</li>
        <li>Buyer requests, proposed budgets, and order details.</li>
        <li>Transaction histories, order statuses, and digital receipts.</li>
        <li>Supplier details and restocking records.</li>
        <li>AI chatbot interactions, when logging is enabled.</li>
        <li>IP addresses, browser details, and server logs, if collected.</li>
        <li>Information submitted when contacting administrators.</li>
    </ul>

    <!-- 1.2 INFORMATION USAGE -->
    <h3>1.2 How We Use the Information</h3>
    <p>
        Information is used for account authentication,
        product posting, inventory management, buyer
        requests, order approval, sales recording,
        digital receipt generation, and reporting.
        It may also support chatbot recommendations,
        system troubleshooting, and security monitoring.
    </p>

    <!-- 1.3 PUBLIC INFORMATION -->
    <h3>1.3 User Profiles and Product Information</h3>
    <p>
        Farmers may display products, prices, quantities,
        and relevant seller details. Buyers may submit
        product requests with their proposed budgets.
        Information included in public listings may
        be visible to other users. Private account
        details and restricted records should not
        be publicly disclosed.
    </p>

    <!-- 1.4 DATA RETENTION -->
    <h3>1.4 Data Retention</h3>
    <p>
        Account details, product listings, buyer requests,
        supplier records, and transaction histories may
        be stored in the MySQL database for academic
        testing and demonstration. Information should
        only be retained as long as reasonably necessary
        for the project's intended purposes.
    </p>

    <!-- 1.5 SECURITY -->
    <h3>1.5 Security</h3>
    <p>
        The system is intended to use secure authentication,
        password hashing, role-based access, and database
        protection measures. However, no online system
        can guarantee absolute security. Users are
        encouraged to provide fictional or test data.
    </p>

    <!-- 1.6 THIRD-PARTY SERVICES -->
    <h3>1.6 Third-Party Services</h3>
    <p>
        The website may use hosting services, software
        libraries, and AI chatbot providers. These
        services may process information according
        to their own privacy policies and configurations.
    </p>

    <!-- 1.7 COOKIES -->
    <h3>1.7 Cookies</h3>
    <p>
        Cookies may be used for login sessions,
        authentication, and website functionality.
        Disabling cookies may affect dashboard access
        and other features.
    </p>

    <!-- 1.8 PRIVACY RIGHTS -->
    <h3>1.8 Your Privacy Rights</h3>
    <p>
        Depending on applicable laws, users may request
        access to, correction of, or deletion of their
        personal information. Requests may be submitted
        to the project administrators for review.
    </p>

    <!-- 1.9 POLICY UPDATES -->
    <h3>1.9 Changes to This Privacy Policy</h3>
    <p>
        This policy may be updated when system features,
        security practices, or academic requirements
        change. Revisions will be reflected through
        an updated date on this page.
    </p>

    <!-- 1.10 CONTACT -->
    <h3>1.10 Contact</h3>
    <p>
        For privacy questions or information requests,
        please contact:
    </p>

    <p>
        <strong>Project:</strong>
        Farm Products and Market Sales Management System
    </p>

    <p>
        <strong>Academic Adviser:</strong> Sir Jerico Garcia
    </p>

    <p>
        <strong>Email:</strong>
        <a href="mailto:garciaj@udd.edu.ph">
            garciaj@udd.edu.ph
        </a>
    </p>

    <p>
        <strong>Website:</strong> [Insert your website URL]
    </p>
</section>

<!-- ========================================= -->
<!-- BACK TO HOME BUTTON -->
<!-- ========================================= -->

<a href="index.php" class="back-btn">Back to Home</a>

</main>

<!-- ========================================= -->
<!-- FOOTER WITH ALL POLICY BUTTONS -->
<!-- ========================================= -->

<footer>
    <h3>Farm Products and Market Sales Management System</h3>

    <p>
        Connecting farmers and buyers in
        Avengers City, Wakanda.
    </p>

    <div class="footer-links">
        <?php foreach ($pages as $key => $name): ?>
            <a
                href="<?php echo $key; ?>.php"
                class="<?php echo $page == $key ? 'active' : ''; ?>"
            >
                <?php echo htmlspecialchars($name); ?>
            </a>
        <?php endforeach; ?>
    </div>


<!-- ================================= -->
<!-- DISCLAIMER BOX -->
<!-- ================================= -->

<style>
.disclaimer-box {
    background: #dff2d8;
    border: 1px solid #c5e5bb;
    border-radius: 18px;
    padding: 25px 30px;
    margin: 30px auto;
    max-width: 1200px;

    display: flex;
    align-items: center;
    gap: 25px;

    box-shadow: 0 4px 15px rgba(0, 80, 30, 0.06);
    transition: all 0.3s ease;
}

.disclaimer-box:hover {
    background: #d1ebc8;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 80, 30, 0.12);
}

.disclaimer-icon {
    background: #bfe1b3;
    width: 70px;
    height: 70px;
    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 32px;
    flex-shrink: 0;
}

.disclaimer-message {
    border-left: 3px solid #9acb8d;
    padding-left: 25px;
}

.disclaimer-message p {
    color: #294b32;
    font-size: 15px;
    line-height: 1.8;
    margin: 0;
}

.disclaimer-message strong {
    color: #174d2c;
}

/* MOBILE RESPONSIVE */
@media (max-width: 600px) {
    .disclaimer-box {
        flex-direction: column;
        text-align: center;
        padding: 25px 20px;
        margin: 20px 15px;
    }

    .disclaimer-message {
        border-left: none;
        border-top: 3px solid #9acb8d;
        padding-left: 0;
        padding-top: 18px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .disclaimer-box {
        transition: none;
    }
}
</style>

<!-- DISCLAIMER CONTENT -->
<div class="disclaimer-box">

    <div class="disclaimer-icon">
        🎓
    </div>

    <div class="disclaimer-message">
        <p>
            <strong>Disclaimer:</strong>
            This website is created for demonstration and
            academic purposes only as part of an SIA subject
            project. It is not a real company, official service,
            registered business, or commercial website.
            No claims are made regarding actual business
            operations, legal standing, or real-world services.
        </p>
    </div>

</div>


    <p>
        &copy; <?php echo date("Y"); ?>
        Farm Products and Market Sales Management System.
        Academic Project.
    </p>
</footer>

</body>
</html>