<?php
// ============================================
// FARM PRODUCTS AND MARKET SALES MANAGEMENT
// SYSTEM - TERMS AND CONDITIONS PAGE
// ============================================

// Current page key
$page = 'terms';

// Available pages (file name => title)
$pages = [
    'privacy' => 'Privacy Policy',
    'terms' => 'Terms and Conditions',
    'moderation' => 'Moderation Policy',
    'copyright' => 'Copyright Notice',
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
<!-- 2. TERMS AND CONDITIONS -->
<!-- ===================================== -->

<section id="terms">
    <h2>2. Terms and Conditions</h2>

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
        is an academic platform demonstrating how farmers
        and buyers in the fictional Avengers City, Wakanda,
        can manage products, inventory, requests, and sales.
        By using this website, you agree to follow these
        Terms and Conditions.
    </p>

    <!-- 2.1 ACCEPTANCE OF TERMS -->
    <h3>2.1 Acceptance of Terms</h3>
    <p>
        Users agree to use this website only for lawful,
        educational, and demonstration purposes.
        Activities that harm others, compromise security,
        or interfere with the system are prohibited.
    </p>

    <!-- 2.2 PRODUCT LISTINGS -->
    <h3>2.2 Product Listings and Buyer Requests</h3>
    <p>
        Farmers may post products with images, descriptions,
        prices, and available quantities. Buyers may browse
        products and submit requests containing desired
        quantities and proposed budgets.
        Submitted information may be stored and displayed
        to authorized users for academic demonstration.
    </p>

    <!-- 2.3 USER RESPONSIBILITIES -->
    <h3>2.3 User Responsibilities</h3>
    <p>Users must not:</p>
    <ul>
        <li>Post false product prices, quantities, or descriptions.</li>
        <li>Submit fraudulent requests or misleading offers.</li>
        <li>Post abusive, threatening, or discriminatory content.</li>
        <li>Upload copyrighted materials without permission.</li>
        <li>Disclose another user's private information.</li>
        <li>Upload malicious files or attempt unauthorized access.</li>
        <li>Manipulate inventory, transaction records, or receipts.</li>
        <li>Misuse the AI chatbot or access restricted information.</li>
    </ul>

    <!-- 2.4 MODERATION RIGHTS -->
    <h3>2.4 Moderation Rights</h3>
    <p>
        Project administrators may review, edit, reject,
        restrict, or remove inappropriate product listings,
        buyer requests, images, or other submitted content.
        Repeated violations may result in account restrictions
        when moderation functions are implemented.
    </p>

    <!-- 2.5 NO WARRANTIES -->
    <h3>2.5 No Warranties</h3>
    <p>
        This website is provided on an "as is" and
        "as available" basis for academic demonstration.
        Uninterrupted access, complete security, and
        error-free functionality are not guaranteed.
    </p>

    <!-- 2.6 LIMITATION OF LIABILITY -->
    <h3>2.6 Limitation of Liability</h3>
    <p>
        The website does not facilitate real commercial
        transactions. To the extent permitted by law,
        the developers do not accept responsibility
        for losses arising from simulated transactions,
        inaccurate listings, chatbot errors, or downtime.
        This does not exclude legally non-excludable duties.
    </p>

    <!-- 2.7 THIRD-PARTY CONTENT -->
    <h3>2.7 Third-Party Content</h3>
    <p>
        External links, software libraries, product images,
        and chatbot services may be provided by third parties.
        Their respective licenses, ownership rights, and
        privacy policies apply.
    </p>

    <!-- 2.8 WEBSITE CHANGES -->
    <h3>2.8 Changes to the Website</h3>
    <p>
        Developers may update or remove features involving
        product posting, inventory monitoring, buyer requests,
        order management, chatbot assistance, and reporting
        during system development and testing.
    </p>

    <!-- 2.9 TERMINATION -->
    <h3>2.9 Termination</h3>
    <p>
        Administrators may restrict access or remove
        content when users violate these Terms.
        Such restrictions depend on the moderation
        features implemented in the system.
    </p>

    <!-- 2.10 GOVERNING LAW -->
    <h3>2.10 Governing Law</h3>
    <p>
        These Terms are intended to be interpreted under
        applicable laws of the Republic of the Philippines.
        Avengers City, Wakanda, is a fictional setting
        and not an actual legal jurisdiction.
    </p>

    <!-- 2.11 CONTACT -->
    <h3>2.11 Contact</h3>
    <p>
        For questions about these Terms, contact:
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