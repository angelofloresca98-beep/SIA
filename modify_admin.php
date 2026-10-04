<?php
$file = 'c:/xampp/htdocs/SIA/admin/admin.php';
$c = file_get_contents($file);

// Replace navbar
$nav_target = <<<HTML
    <nav>
        <a href="admin.php" class="active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="#users"><i class="fa-solid fa-users"></i><span>Up</span></a>
        <a href="add.php"><i class="fa-solid fa-user-plus"></i><span>Add user</span></a>
    </nav>
HTML;

$nav_replace = <<<HTML
    <nav>
        <a href="#dashboard" class="menu-link active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="#users" class="menu-link"><i class="fa-solid fa-users"></i><span>User Management</span></a>
        <a href="#sales" class="menu-link"><i class="fa-solid fa-chart-line"></i><span>Sales Overview</span></a>
    </nav>
HTML;

// Clean up whitespace mismatches
$c = preg_replace('/<nav>\s*<a href="admin\.php" class="active"><i class="fa-solid fa-house"><\/i><span>Dashboard<\/span><\/a>\s*<a href="#users"><i class="fa-solid fa-users"><\/i><span>Up<\/span><\/a>\s*<a href="add\.php"><i class="fa-solid fa-user-plus"><\/i><span>Add user<\/span><\/a>\s*<\/nav>/', $nav_replace, $c);

// Replace sales section
$section_target = <<<HTML
            <div class="tab-pane fade" id="buyer" role="tabpanel">
                <?php renderUserTable(\$buyers, 'buyer'); ?>
            </div>
        </div>

    </section>
HTML;

$section_replace = <<<HTML
            <div class="tab-pane fade" id="buyer" role="tabpanel">
                <?php renderUserTable(\$buyers, 'buyer'); ?>
            </div>
        </div>

    </section>

    <section class="panel scroll-section" id="sales" style="margin-top: 24px;">
        <div class="panel-head">
            <h2>Sales Overview</h2>
            <div class="tools">
                <button class="btn btn-primary" onclick="alert('Exporting report...')"><i class="fa-solid fa-download me-1"></i> Export Report</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#TRX-98234</td>
                        <td>Organic Tomatoes</td>
                        <td>₱1,250.00</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>Today, 10:23 AM</td>
                    </tr>
                    <tr>
                        <td>#TRX-98233</td>
                        <td>Fresh Carrots</td>
                        <td>₱450.00</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>Today, 09:15 AM</td>
                    </tr>
                    <tr>
                        <td>#TRX-98232</td>
                        <td>Jasmine Rice (50kg)</td>
                        <td>₱2,800.00</td>
                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                        <td>Yesterday</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
HTML;

$c = str_replace($section_target, $section_replace, $c);

// Replace JS
$js_target = <<<HTML
// Live search across both tabs
const searchInput = document.getElementById('userSearch');
searchInput.addEventListener('input', function () {
HTML;

$js_replace = <<<HTML
// Interactivity: Smooth Scrolling & Scroll Spy
document.addEventListener('DOMContentLoaded', () => {
    const menuLinks = document.querySelectorAll('.menu-link');
    const sections = document.querySelectorAll('.scroll-section');

    // Smooth scrolling for menu links
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId.startsWith('#')) {
                e.preventDefault();
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // Scroll spy to update active menu link
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollY >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });

        menuLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#\${current}`) {
                link.classList.add('active');
            }
        });
    });
});

// Live search across both tabs
const searchInput = document.getElementById('userSearch');
searchInput.addEventListener('input', function () {
HTML;

$c = str_replace($js_target, $js_replace, $c);

file_put_contents($file, $c);
echo "Modifications applied.";
