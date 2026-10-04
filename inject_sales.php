<?php
$file = 'c:/xampp/htdocs/SIA/admin/admin.php';
$c = file_get_contents($file);

// Replace Sales Section
$sales_html = <<<HTML
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
</main>
HTML;

$c = preg_replace('/<\/section>\s*<\/main>/i', $sales_html, $c);

// Replace JS
$js_html = <<<HTML
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

$c = str_replace("// Live search across both tabs\r\nconst searchInput = document.getElementById('userSearch');\r\nsearchInput.addEventListener('input', function () {", $js_html, $c);
$c = str_replace("// Live search across both tabs\nconst searchInput = document.getElementById('userSearch');\nsearchInput.addEventListener('input', function () {", $js_html, $c);

file_put_contents($file, $c);
echo "Injected Sales & JS.";
