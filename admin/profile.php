<?php
// admin/profile.php - Admin Self Profile & Password Update
$pageTitle = "My Profile";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$adminId = $_SESSION['admin_id'] ?? 1;

$msg = '';
$err = '';

// Fetch current user
$stmt = $db->prepare("SELECT * FROM op_user WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$currentUser = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['user_email'] ?? '');
    $mobile = sanitize($_POST['user_mobile'] ?? '');
    $currPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($fullName) {
        try {
            if (!empty($newPass)) {
                if ($newPass !== $confirmPass) {
                    $err = "New password and confirmation do not match.";
                } elseif (strlen($newPass) < 6) {
                    $err = "Password must be at least 6 characters long.";
                } else {
                    $hash = md5($newPass);
                    $up = $db->prepare("UPDATE op_user SET full_name = ?, user_email = ?, user_mobile = ?, user_pass = ?, updated_at = NOW() WHERE id = ?");
                    $up->execute([$fullName, $email, $mobile, $hash, $adminId]);
                    $_SESSION['admin_name'] = $fullName;
                    $msg = "Profile and password updated successfully.";
                }
            } else {
                $up = $db->prepare("UPDATE op_user SET full_name = ?, user_email = ?, user_mobile = ?, updated_at = NOW() WHERE id = ?");
                $up->execute([$fullName, $email, $mobile, $adminId]);
                $_SESSION['admin_name'] = $fullName;
                $msg = "Profile details updated successfully.";
            }

            // Reload user
            $stmt->execute([$adminId]);
            $currentUser = $stmt->fetch();
        } catch (Exception $e) {
            $err = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $err = "Full name is required.";
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">My Profile & Credentials</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Update your administrator profile, contact info, and security credentials.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="admin-profile-grid">
    <style>
        @media(max-width: 992px) {
            .admin-profile-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Profile Form -->
    <div class="admin-card" style="border-top: 4px solid var(--brand-red);">
        <h3 class="admin-card-title" style="margin-bottom: 1.25rem;"><i class="fas fa-user-pen"></i> Personal Information</h3>

        <form action="profile.php" method="POST">
            <div class="filter-group">
                <label class="filter-label">Username (Cannot be changed)</label>
                <input type="text" class="filter-input" value="<?= sanitize($currentUser['user_name'] ?? 'admin') ?>" disabled style="background: #f3f4f6; color: #6b7280;">
            </div>

            <div class="filter-group">
                <label class="filter-label">Full Name *</label>
                <input type="text" name="full_name" class="filter-input" value="<?= sanitize($currentUser['full_name'] ?? '') ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Official Email</label>
                    <input type="email" name="user_email" class="filter-input" value="<?= sanitize($currentUser['user_email'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Mobile Number</label>
                    <input type="text" name="user_mobile" class="filter-input" value="<?= sanitize($currentUser['user_mobile'] ?? '') ?>">
                </div>
            </div>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

            <h4 style="font-size: 1rem; color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-key"></i> Change Password (Optional)</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">New Password</label>
                    <input type="password" name="new_password" class="filter-input" placeholder="Leave empty to keep current">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="filter-input" placeholder="Confirm new password">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;"><i class="fas fa-save"></i> Save Profile Changes</button>
        </form>
    </div>

    <!-- Security Summary Box -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div class="admin-card" style="border-top: 4px solid var(--brand-gold);">
            <h3 class="admin-card-title" style="margin-bottom: 1rem;"><i class="fas fa-shield-check"></i> Account Details</h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                <div>
                    <span style="color: var(--text-muted);">Assigned Role:</span>
                    <strong style="color: var(--brand-red);"><?= sanitize($currentUser['user_type'] ?? 'ADMIN') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Account Status:</span>
                    <strong style="color: #065f46;"><?= sanitize($currentUser['user_status'] ?? 'ACTIVE') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Last Login:</span>
                    <span><?= $currentUser['last_login'] ? date('d M Y, h:i A', strtotime($currentUser['last_login'])) : '—' ?></span>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Member Since:</span>
                    <span><?= $currentUser['created_at'] ? date('d M Y', strtotime($currentUser['created_at'])) : '—' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
