<?php
// change-password.php - Logged-in Advocate Password Update
require_once __DIR__ . '/config/app.php';

if (empty($_SESSION['advocate_id'])) {
    header("Location: login");
    exit;
}

$pageTitle = "Change Password - Advocate Dashboard";
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPass = sanitize($_POST['current_password'] ?? '');
    $newPass = sanitize($_POST['new_password'] ?? '');
    $confirmPass = sanitize($_POST['confirm_password'] ?? '');

    if (empty($newPass) || strlen($newPass) < 4) {
        $error = "Password must be at least 4 characters.";
    } elseif ($newPass !== $confirmPass) {
        $error = "New password and confirmation do not match.";
    } else {
        $msg = "Password has been successfully updated.";
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <a href="dashboard">Dashboard</a> &bull; <span>Change Password</span>
    </nav>

    <div style="max-width: 500px; margin: 0 auto;">
        <div class="stat-box" style="padding: 2.5rem 2rem;">
            <div style="margin-bottom: 1.5rem;">
                <h1 style="font-size: 1.75rem; color: var(--primary); margin-bottom: 0.25rem;">
                    <i class="fas fa-lock" style="color: var(--brand-red);"></i> Change Password
                </h1>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Keep your advocate account secure with a strong password</p>
            </div>

            <?php if ($msg): ?>
                <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-circle-check" style="color: #16a34a;"></i>
                    <span><?= sanitize($msg) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span><?= sanitize($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="change-password" method="POST">
                <div class="filter-group">
                    <label class="filter-label">Current Password</label>
                    <input type="password" name="current_password" class="filter-input" placeholder="••••••••" required>
                </div>

                <div class="filter-group">
                    <label class="filter-label">New Password</label>
                    <input type="password" name="new_password" class="filter-input" placeholder="••••••••" required>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="filter-input" placeholder="••••••••" required>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-floppy-disk"></i> Update Password
                    </button>
                    <a href="dashboard" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
