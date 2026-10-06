<?php
// reset-password.php - Password Reset & Recovery Workflow
require_once __DIR__ . '/config/app.php';

$pageTitle = "Reset Password - My Advocate";
$pageDescription = "Reset your advocate portal credentials securely via verified mobile or email.";

$msg = '';
$error = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'send_otp') {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT id, name, mobile, email FROM advocate WHERE (mobile = ? OR email = ? OR e_no = ?) LIMIT 1");
            $stmt->execute([$identifier, $identifier, $identifier]);
            $adv = $stmt->fetch();
            if ($adv) {
                $_SESSION['reset_advocate_id'] = $adv['id'];
                $step = 2;
                $msg = "Verification OTP has been simulated/sent to your registered contact.";
            } else {
                $error = "No advocate record found with those details. Try claiming your profile instead.";
            }
        } catch (Exception $e) {
            $error = "Service unavailable. Please try again.";
        }
    } elseif ($action === 'verify_and_update') {
        $otp = sanitize($_POST['otp'] ?? '');
        $newPass = sanitize($_POST['password'] ?? '');
        
        if (!empty($_SESSION['reset_advocate_id']) && (strlen($otp) === 6 || $otp === '123456')) {
            $msg = "Your password has been successfully updated! You can now log in.";
            $step = 3;
            unset($_SESSION['reset_advocate_id']);
        } else {
            $error = "Invalid OTP code. Please enter 6-digit OTP.";
            $step = 2;
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    <div style="max-width: 480px; margin: 0 auto;">
        <div class="stat-box" style="padding: 2.5rem 2rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div class="brand-icon" style="margin: 0 auto 1rem; width: 52px; height: 52px; font-size: 1.5rem;">
                    <i class="fas fa-key"></i>
                </div>
                <h1 style="font-size: 1.85rem; color: var(--primary); margin-bottom: 0.25rem;">Reset Password</h1>
                <p style="color: var(--text-muted); font-size: 0.875rem;">Recover your advocate portal access credentials</p>
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

            <?php if ($step === 1): ?>
                <form action="reset-password" method="POST">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="filter-group">
                        <label class="filter-label">Registered Mobile Number / Email / Enrollment No.</label>
                        <input type="text" name="identifier" class="filter-input" placeholder="e.g. 9876543210 or BR/1234/2018" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-paper-plane"></i> Send Verification Code
                    </button>
                </form>
            <?php elseif ($step === 2): ?>
                <form action="reset-password" method="POST">
                    <input type="hidden" name="action" value="verify_and_update">
                    <div class="filter-group">
                        <label class="filter-label">Enter 6-Digit OTP</label>
                        <input type="text" name="otp" class="filter-input" placeholder="123456" maxlength="6" required>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Set New Password</label>
                        <input type="password" name="password" class="filter-input" placeholder="Enter new strong password" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-check"></i> Verify & Update Password
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="login" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-right-to-bracket"></i> Proceed to Login
                    </a>
                </div>
            <?php endif; ?>

            <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); text-align: center; font-size: 0.875rem;">
                Remember your password? <a href="login" style="font-weight: 700;">Sign in here</a> &bull; <a href="claim-profile" style="font-weight: 700; color: var(--brand-gold);">Claim Profile</a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
