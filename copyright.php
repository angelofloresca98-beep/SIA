<?php
// ============================================
// FARM PRODUCTS AND MARKET SALES MANAGEMENT
// SYSTEM - COPYRIGHT NOTICE PAGE
// ============================================

// Current page key
$page = 'copyright';

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
<!-- 4. COPYRIGHT AND DMCA NOTICE -->
<!-- ===================================== -->

<section id="copyright">
    <h2>4. Copyright and DMCA Notice</h2>

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
        respects the intellectual property rights of others.
        Farmers may upload agricultural product images,
        descriptions, and other product-related materials
        for academic demonstration. Users are responsible
        for ensuring that their submitted content does not
        infringe the rights of others.
    </p>

    <!-- 4.1 COPYRIGHT POLICY -->
    <h3>4.1 Copyright Policy</h3>
    <p>
        Agricultural product images, descriptions, logos,
        graphics, interface designs, and uploaded materials
        may be protected by copyright laws.
        Users must not upload copyrighted content without
        permission or another lawful basis.
    </p>

    <p>
        Original project code, designs, and documentation
        remain subject to applicable intellectual property
        laws and school policies. Third-party materials
        remain the property of their respective owners.
        Avengers City and Wakanda are fictional references;
        the project is not affiliated with Marvel or Disney.
    </p>

    <!-- 4.2 REPORTING INFRINGEMENT -->
    <h3>4.2 Reporting Copyright Infringement</h3>
    <p>
        If you believe that a product image, description,
        logo, or other material infringes your copyright,
        please submit a written notice containing:
    </p>

    <ul>
        <li>Your full name and contact information.</li>
        <li>A description of the copyrighted work.</li>
        <li>The URL or location of the disputed material.</li>
        <li>A statement explaining the suspected unauthorized use.</li>
        <li>A statement confirming the accuracy of your report.</li>
        <li>Your physical or electronic signature, when applicable.</li>
    </ul>

    <!-- 4.3 RESPONSE -->
    <h3>4.3 Response to Copyright Complaints</h3>
    <p>
        Project administrators may review copyright reports
        and investigate disputed product listings, images,
        or descriptions. Content may be removed, restricted,
        or disabled when a valid violation is identified
        and the necessary moderation functions are available.
        The uploader may be contacted for clarification.
    </p>

    <!-- 4.4 COUNTER-NOTICE -->
    <h3>4.4 Counter-Notice</h3>
    <p>
        Users who believe their content was removed by
        mistake or misidentification may submit a
        counter-notice identifying the removed material,
        explaining the dispute, and providing supporting
        information. Administrators may review the request
        and restore content when appropriate and lawful.
    </p>

    <!-- 4.5 CONTACT -->
    <h3>4.5 Contact for Copyright Concerns</h3>
    <p>
        For copyright reports or removal requests,
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