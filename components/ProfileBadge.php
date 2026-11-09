<?php
/**
 * FarmMarket - Reusable Profile Badge Component (PHP)
 * 
 * Usage:
 *   require_once __DIR__ . '/../components/ProfileBadge.php';
 *   render_profile_badge([
 *       'name'        => $buyerName,
 *       'role'        => 'buyer',
 *       'profileUrl'  => 'profile.php',
 *       'settingsUrl' => 'profile.php#settings',
 *       'logoutUrl'   => '../logout.php',
 *   ]);
 */

if (!function_exists('render_profile_badge')) {
    /**
     * Renders the interactive user profile badge / dropdown.
     *
     * @param array $props {
     *   name       string  Full display name
     *   role       string  'buyer' | 'farmer'
     *   profileUrl string  Link to the profile page (default: profile.php)
     *   logoutUrl  string  Link to logout (default: ../logout.php)
     *   email      string  Optional e-mail shown in dropdown header
     *   static     bool    When true the badge is non-interactive (no dropdown).
     *                      Use this on the My Profile page itself.
     * }
     */
    function render_profile_badge(array $props = []): void {
        $name       = trim((string)($props['name']  ?? ($_SESSION['user']  ?? 'User')));
        $role       = strtolower(trim((string)($props['role']  ?? ($_SESSION['role']  ?? 'buyer'))));
        $profileUrl = $props['profileUrl'] ?? 'profile.php';
        $logoutUrl  = $props['logoutUrl']  ?? '../logout.php';
        $email      = trim((string)($props['email'] ?? ($_SESSION['email'] ?? '')));
        $isStatic   = !empty($props['static']); // true → non-interactive badge on the profile page

        // Calculate avatar initials
        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($parts)) {
            $first   = mb_substr($parts[0], 0, 1);
            $last    = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
            $initial = strtoupper($first . $last);
        } else {
            $initial = strtoupper(mb_substr($role, 0, 1));
        }

        $roleLabel = ucfirst($role);
        $uniqueId  = 'user-profile-' . substr(md5($name . $role . microtime()), 0, 6);
        ?>
        <?php if ($isStatic): ?>
        <div class="user-profile-menu user-profile-static" aria-label="Signed in as <?= htmlspecialchars($name) ?> (<?= htmlspecialchars($roleLabel) ?>)">
            <span class="profile profile-btn profile-btn--static" role="presentation">
                <span class="avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
                <span class="profile-role"><?= htmlspecialchars($roleLabel) ?></span>
            </span>
        </div>
        <?php else: ?>
        <div class="user-profile-menu" data-user-menu>
            <button type="button"
                    class="profile profile-btn"
                    id="<?= htmlspecialchars($uniqueId) ?>-btn"
                    aria-haspopup="menu"
                    aria-expanded="false"
                    aria-controls="<?= htmlspecialchars($uniqueId) ?>-menu"
                    aria-label="User profile options for <?= htmlspecialchars($name) ?> (<?= htmlspecialchars($roleLabel) ?>)">
                <span class="avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
                <span class="profile-role"><?= htmlspecialchars($roleLabel) ?></span>
                <i class="fa-solid fa-chevron-down profile-chevron" aria-hidden="true"></i>
            </button>

            <div class="profile-dropdown"
                 id="<?= htmlspecialchars($uniqueId) ?>-menu"
                 role="menu"
                 aria-labelledby="<?= htmlspecialchars($uniqueId) ?>-btn"
                 hidden>
                <div class="profile-dropdown-header">
                    <div class="profile-dropdown-user">
                        <span class="profile-dropdown-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
                        <div class="profile-dropdown-meta">
                            <strong class="profile-dropdown-name"><?= htmlspecialchars($name) ?></strong>
                            <span class="profile-dropdown-role"><?= htmlspecialchars($roleLabel) ?><?= $email ? ' &bull; ' . htmlspecialchars($email) : '' ?></span>
                        </div>
                    </div>
                </div>

                <ul class="profile-dropdown-list" role="none">
                    <li role="none">
                        <a href="<?= htmlspecialchars($profileUrl) ?>" class="profile-dropdown-item" role="menuitem" tabindex="-1">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li role="none" class="dropdown-divider-item">
                        <div class="profile-dropdown-divider" role="separator"></div>
                    </li>
                    <li role="none">
                        <a href="<?= htmlspecialchars($logoutUrl) ?>" class="profile-dropdown-item item-danger" role="menuitem" tabindex="-1">
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }
}

