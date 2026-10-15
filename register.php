<?php
session_start();
require './database/connection.php';

if (isset($_POST['create'])) {
    $firstName  = trim($_POST['firstName']  ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $lastName   = trim($_POST['lastName']   ?? '');
    $contact    = trim($_POST['contact']    ?? '');
    $address    = trim($_POST['location']   ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = trim($_POST['password']   ?? '');
    $role       = trim($_POST['role']       ?? '');

    $allowedRoles = ['farmer', 'buyer'];

    if (empty($firstName) || empty($middleName) || empty($lastName) ||
        empty($contact) || empty($address) ||
        empty($email) || empty($password) || empty($role)) {
        $_SESSION['message'] = 'All fields are required!';
        $_SESSION['type']    = 'danger';

    } elseif (!preg_match('/^(09[0-9]{9}|639[0-9]{9})$/', $contact)) {
        $_SESSION['message'] = 'Contact number must start with 09 (11 digits) or 63 (12 digits), numbers only.';
        $_SESSION['type']    = 'danger';

    } elseif (!in_array($role, $allowedRoles)) {
        $_SESSION['message'] = 'Please select a valid role (Farmer or Buyer).';
        $_SESSION['type']    = 'danger';

    } elseif (!isset($_POST['agree'])) {
        $_SESSION['message'] = 'You must agree to the Privacy Policy, Terms of Service, Moderation Policy, and Copyright Policy to register.';
        $_SESSION['type']    = 'danger';

    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql  = 'SELECT user_id FROM users WHERE email = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['message'] = 'That email is already registered.';
                $_SESSION['type']    = 'danger';
            } else {
                $sql  = "INSERT INTO users (firstName, middleName, lastName, contact_number, address, email, password, role, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);

                if ($stmt->execute([$firstName, $middleName, $lastName, $contact, $address, $email, $hashedPassword, $role])) {
                    $_SESSION['message'] = 'Account created! You can now sign in.';
                    $_SESSION['type']    = 'success';
                }
            }

        } catch (PDOException $e) {
            $_SESSION['message'] = 'Error: ' . $e->getMessage();
            $_SESSION['type']    = 'danger';
        }
    }

    header('Location: register.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · Farm to Market</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ink: #1c2a24;
    --muted: #6b7a72;
    --paper: #f4f6f2;
    --forest: #1f4d3a;
    --leaf: #2f7d4f;
    --harvest: #b7791f;
    --danger: #b3261e;
    --danger-soft: #fbe3e0;
    --ok-soft: #dff0e5;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Figtree', system-ui, sans-serif;
    color: var(--ink);
    background: var(--paper);
    min-height: 100vh;
    display: grid;
    grid-template-columns: minmax(320px, 5fr) 6fr;
}

a:focus-visible, button:focus-visible, input:focus-visible, select:focus-visible {
    outline: 3px solid var(--harvest);
    outline-offset: 2px;
}

/* ---------- Brand panel ---------- */
.brand-panel {
    background: var(--forest);
    color: #fff;
    padding: 48px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.brand-mark { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 20px; }
.brand-mark i {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.14);
    display: inline-flex; align-items: center; justify-content: center;
}

.brand-copy h2 { font-size: 34px; line-height: 1.15; letter-spacing: -0.02em; max-width: 14ch; margin-bottom: 14px; }
.brand-copy p  { color: rgba(255,255,255,.75); max-width: 36ch; line-height: 1.55; }
.brand-foot    { color: rgba(255,255,255,.55); font-size: 13px; }

/* ---------- Role cards ---------- */
.role-picker { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 22px; }

.role-card input[type="radio"] { display: none; }

.role-card label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 10px;
    border: 2px solid #cbd5cd;
    border-radius: 12px;
    background: #fff;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-align: center;
    transition: border-color .15s, box-shadow .15s, background .15s;
}

.role-card label i {
    font-size: 24px;
    color: var(--muted);
    transition: color .15s;
}

.role-card label .role-desc {
    font-weight: 400;
    font-size: 12px;
    color: var(--muted);
}

.role-card input[type="radio"]:checked + label {
    border-color: var(--leaf);
    background: var(--ok-soft);
    box-shadow: 0 0 0 3px rgba(47,125,79,.15);
}

.role-card input[type="radio"]:checked + label i { color: var(--leaf); }

.role-card label:hover { border-color: var(--leaf); }

/* ---------- Form panel ---------- */
.form-panel { display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
.form-wrap  { width: 100%; max-width: 440px; }

h1  { font-size: 28px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; }
.sub { color: var(--muted); margin-bottom: 26px; }

.section-label {
    font-size: 12px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: var(--muted);
    margin-bottom: 10px;
}

.alert {
    display: flex; gap: 10px; align-items: flex-start;
    border-radius: 10px; padding: 12px 14px; margin-bottom: 18px;
    font-size: 14.5px; font-weight: 500;
}
.alert i { margin-top: 2px; }
.alert-danger  { background: var(--danger-soft); color: var(--danger); }
.alert-success { background: var(--ok-soft); color: var(--forest); }

.row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field { margin-bottom: 18px; }
.field.full { grid-column: 1 / -1; }
label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; }

input[type=text], input[type=email], input[type=password], input[type=tel] {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5cd;
    border-radius: 10px;
    font: inherit;
    background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
input:focus { outline: none; border-color: var(--leaf); box-shadow: 0 0 0 3px rgba(47,125,79,.18); }

.pw-wrap { position: relative; }
.pw-wrap input { padding-right: 48px; }
.pw-toggle {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    width: 38px; height: 38px; border: 0; border-radius: 8px;
    background: transparent; color: var(--muted); cursor: pointer;
}
.pw-toggle:hover { color: var(--ink); background: var(--paper); }

/* ---------- Agreement checkbox ---------- */
.field.agree {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.field.agree input[type="checkbox"] {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: var(--leaf);
    cursor: pointer;
}

.field.agree label {
    margin: 0;
    font-weight: 400;
    font-size: 13.5px;
    line-height: 1.5;
    color: var(--muted);
    cursor: pointer;
}

.policy-link {
    background: none;
    border: 0;
    padding: 0;
    font: inherit;
    font-weight: 600;
    color: var(--forest);
    cursor: pointer;
}

.policy-link:hover { text-decoration: underline; }

/* ---------- Policy modals ---------- */
dialog.policy-modal {
    margin: auto;
    width: min(560px, calc(100% - 32px));
    max-height: 82vh;
    border: 0;
    border-radius: 14px;
    padding: 0;
    color: var(--ink);
    background: #fff;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    overflow: hidden;
}

dialog.policy-modal[open] {
    display: flex;
    flex-direction: column;
}

dialog.policy-modal::backdrop { background: rgba(28,42,36,.55); }

.modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid #e3e9e4;
}

.modal-head h2 { font-size: 20px; letter-spacing: -0.01em; }

.modal-x {
    width: 36px; height: 36px;
    border: 0; border-radius: 8px;
    background: transparent;
    color: var(--muted);
    font-size: 18px;
    cursor: pointer;
}
.modal-x:hover { background: var(--paper); color: var(--ink); }

.modal-body {
    padding: 20px 22px;
    overflow-y: auto;
    font-size: 14.5px;
    line-height: 1.6;
}

.modal-body h2 { font-size: 17px; margin-bottom: 10px; }
.modal-body h3 { font-size: 15px; margin: 16px 0 4px; }
.modal-body p  { color: #3d4b44; margin-bottom: 10px; }
.modal-body ul { padding-left: 22px; margin: 0 0 10px; color: #3d4b44; }
.modal-body li { margin-bottom: 4px; }
.modal-body a  { color: var(--forest); font-weight: 600; }

.modal-foot {
    padding: 14px 22px;
    border-top: 1px solid #e3e9e4;
    text-align: right;
}

.modal-close-btn {
    padding: 10px 20px;
    border: 0; border-radius: 10px;
    background: var(--leaf); color: #fff;
    font: inherit; font-weight: 600;
    cursor: pointer;
}
.modal-close-btn:hover { background: var(--forest); }

.btn-register {
    width: 100%; padding: 13px; margin-top: 4px;
    background: var(--leaf); color: #fff;
    border: 0; border-radius: 10px;
    font: inherit; font-weight: 600; font-size: 16px;
    cursor: pointer; transition: background .15s;
}
.btn-register:hover { background: var(--forest); }

.foot { text-align: center; margin-top: 22px; color: var(--muted); font-size: 14.5px; }
.foot a { color: var(--forest); font-weight: 600; text-decoration: none; }
.foot a:hover { text-decoration: underline; }

/* ---------- Responsive ---------- */
@media (max-width: 860px) {
    body { grid-template-columns: 1fr; }
    .brand-panel { padding: 22px 24px; flex-direction: row; align-items: center; }
    .brand-copy, .brand-foot { display: none; }
    .form-panel { align-items: flex-start; padding-top: 40px; }
}

@media (max-width: 480px) {
    .row { grid-template-columns: 1fr; gap: 0; }
    .role-picker { grid-template-columns: 1fr 1fr; }
}

@media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>

<body>

<aside class="brand-panel">
    <div class="brand-mark"><i class="fa-solid fa-seedling"></i> Farm to Market</div>

    <div class="brand-copy">
        <h2>Join the Farm to Market community.</h2>
        <p>Register as a Farmer to list your products, or as a Buyer to order fresh produce directly.</p>
    </div>

    <div class="brand-foot">&copy; <?= date('Y') ?> Farm to Market System</div>
</aside>

<main class="form-panel">
    <div class="form-wrap">

        <h1>Create account</h1>
        <p class="sub">Sign up as a Farmer or Buyer.</p>

        <!-- ALERT MESSAGE -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['type']); ?>" role="alert">
                <i class="fa-solid <?= $_SESSION['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <span><?= htmlspecialchars($_SESSION['message']); ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- REGISTER FORM -->
        <form method="POST" id="registerForm">

            <!-- ── Step 1: Choose a role ── -->
            <p class="section-label">I am a&hellip;</p>
            <div class="role-picker" role="group" aria-label="Select your role">

                <div class="role-card">
                    <input type="radio" id="roleFarmer" name="role" value="farmer" required>
                    <label for="roleFarmer">
                        <i class="fa-solid fa-tractor"></i>
                        Farmer
                        <span class="role-desc">List &amp; sell produce</span>
                    </label>
                </div>

                <div class="role-card">
                    <input type="radio" id="roleBuyer" name="role" value="buyer">
                    <label for="roleBuyer">
                        <i class="fa-solid fa-basket-shopping"></i>
                        Buyer
                        <span class="role-desc">Browse &amp; order produce</span>
                    </label>
                </div>

            </div>

            <!-- ── Step 2: Personal details ── -->
            <p class="section-label">Personal details</p>
            <div class="row">
                <div class="field">
                    <label for="firstName">First name</label>
                    <input type="text" id="firstName" name="firstName" autocomplete="given-name" autofocus required>
                </div>

                <div class="field">
                    <label for="middleName">Middle name</label>
                    <input type="text" id="middleName" name="middleName" autocomplete="additional-name" required>
                </div>

                <div class="field full">
                    <label for="lastName">Last name</label>
                    <input type="text" id="lastName" name="lastName" autocomplete="family-name" required>
                </div>

                <div class="field">
                    <label for="contact">Contact number</label>
                    <input type="tel" id="contact" name="contact" autocomplete="tel"
                        inputmode="numeric" maxlength="12" placeholder="09XXXXXXXXX"
                        pattern="(09[0-9]{9}|639[0-9]{9})"
                        title="Start with 09 (11 digits) or 63 (12 digits), numbers only" required>
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" autocomplete="address-level2" required>
                </div>
            </div>

            <!-- ── Step 3: Credentials ── -->
            <p class="section-label">Account credentials</p>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" autocomplete="new-password" required>
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Show password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- ── Step 4: Agreement ── -->
            <div class="field agree">
                <input type="checkbox" id="agree" name="agree" value="1" required>
                <label for="agree">
                    I have read and agree to the
                    <button type="button" class="policy-link" data-modal="modal-privacy">Privacy Policy</button>,
                    <button type="button" class="policy-link" data-modal="modal-terms">Terms of Service</button>,
                    <button type="button" class="policy-link" data-modal="modal-moderation">Moderation Policy</button>, and
                    <button type="button" class="policy-link" data-modal="modal-copyright">Copyright Policy</button>.
                </label>
            </div>

            <button type="submit" name="create" class="btn-register" id="submitBtn">Create account</button>

            <p class="foot">Already have an account? <a href="login.php">Sign in</a></p>
        </form>

    </div>
</main>

<!-- ============ POLICY MODALS ============ -->

<dialog class="policy-modal" id="modal-privacy" aria-labelledby="title-privacy">
    <div class="modal-head">
        <h2 id="title-privacy">Privacy Policy</h2>
        <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
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
    </div>
    <div class="modal-foot"><button type="button" class="modal-close-btn" data-close>Close</button></div>
</dialog>

        <!-- Terms -->

<dialog class="policy-modal" id="modal-terms" aria-labelledby="title-terms">
    <div class="modal-head">
        <h2 id="title-terms">Terms of Service</h2>
        <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
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
    </div>
    <div class="modal-foot"><button type="button" class="modal-close-btn" data-close>Close</button></div>
</dialog>

        <!-- Moderation -->

<dialog class="policy-modal" id="modal-moderation" aria-labelledby="title-moderation">
    <div class="modal-head">
        <h2 id="title-moderation">Moderation Policy</h2>
        <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
         <h2>3. Moderation and Removal Policy</h2>

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
        aims to provide a safe, respectful, and organized
        platform for farmers, buyers, and suppliers in the
        fictional Avengers City, Wakanda. This policy explains
        how product listings, buyer requests, user interactions,
        and transaction-related content may be reviewed,
        reported, moderated, or removed.
    </p>

    <!-- 3.1 PROHIBITED CONTENT -->
    <h3>3.1 Content That Is Not Allowed</h3>
    <p>We may remove or moderate content that includes:</p>

    <ul>
        <li>Threats, harassment, abusive language, or discrimination.</li>
        <li>False product prices, quantities, descriptions, or availability.</li>
        <li>Fraudulent buyer requests or deceptive offers.</li>
        <li>Spam, repeated advertisements, or irrelevant postings.</li>
        <li>Illegal activities or attempts to compromise system security.</li>
        <li>Unauthorized sharing of another user's personal information.</li>
        <li>Copyrighted images or materials used without permission.</li>
        <li>Falsified receipts, order statuses, or inventory records.</li>
    </ul>

    <!-- 3.2 CONTENT REVIEW -->
    <h3>3.2 How Content Is Reviewed</h3>
    <p>
        Authorized project administrators may review product
        listings, uploaded images, buyer requests, and other
        submitted content when moderation features are
        available. Reviews may occur before or after publication
        or following a report. Violating content may be
        corrected, restricted, hidden, or removed.
    </p>

    <!-- 3.3 REPORTING CONTENT -->
    <h3>3.3 Reporting Content</h3>
    <p>
        Users may report misleading product listings,
        inappropriate messages, suspicious buyer requests,
        or other violations to the project administrators.
        Reports should include the content involved, reason
        for reporting, and relevant details or supporting
        information without unnecessary personal data.
    </p>

    <!-- 3.4 REMOVAL REQUESTS -->
    <h3>3.4 Removal Requests</h3>
    <p>
        Administrators may remove or restrict inappropriate
        listings, misleading requests, unauthorized images,
        or other violating content following a valid complaint,
        appropriate legal request, or identified violation.
        Related transaction records may be retained when
        necessary and consistent with privacy requirements.
    </p>

    <!-- 3.5 REPEAT VIOLATIONS -->
    <h3>3.5 Repeat Violations</h3>
    <p>
        Users who repeatedly submit fraudulent listings,
        misleading requests, abusive messages, or inappropriate
        content may receive warnings or restrictions.
        Account access or posting privileges may be limited
        when these moderation features are implemented.
    </p>

    <!-- 3.6 PUBLICATION -->
    <h3>3.6 No Guarantee of Publication</h3>
    <p>
        The system does not guarantee publication or continued
        availability of submitted content. Administrators
        may reject, hide, or remove content that violates
        marketplace guidelines or interferes with the
        system's academic purpose.
    </p>

    <!-- 3.7 USER RESPONSIBILITY -->
    <h3>3.7 User Responsibility</h3>
    <p>
        Farmers must provide appropriate product information,
        images, prices, and quantities. Buyers must submit
        legitimate requests with reasonable budgets.
        All users must respect privacy, communicate
        professionally, avoid misleading transactions,
        and use the AI chatbot responsibly.
    </p>

    <!-- 3.8 CONTACT -->
    <h3>3.8 Contact</h3>
    <p>
        For moderation concerns or content removal requests,
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
    </div>
    <div class="modal-foot"><button type="button" class="modal-close-btn" data-close>Close</button></div>
</dialog>


        <!-- CopyRight -->

<dialog class="policy-modal" id="modal-copyright" aria-labelledby="title-copyright">
    <div class="modal-head">
        <h2 id="title-copyright">Copyright Policy</h2>
        <button type="button" class="modal-x" data-close aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
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
    </div>
    <div class="modal-foot"><button type="button" class="modal-close-btn" data-close>Close</button></div>
</dialog>

<script>
// Policy modals
document.querySelectorAll('.policy-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById(btn.dataset.modal).showModal();
    });
});

document.querySelectorAll('dialog.policy-modal').forEach(function (dlg) {
    // Close buttons
    dlg.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () { dlg.close(); });
    });
    // Click on the dark backdrop closes it
    dlg.addEventListener('click', function (e) {
        if (e.target === dlg) dlg.close();
    });
});

// Password toggle
const pw     = document.getElementById('password');
const toggle = document.getElementById('pwToggle');

toggle.addEventListener('click', function () {
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    toggle.querySelector('i').className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
});

// Contact number: digits only
document.getElementById('contact').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '');
});
</script>

</body>
</html>