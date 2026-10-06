<?php
// login.php - Common Unified Login Portal for Advocates & Members
require_once __DIR__ . '/config/app.php';

$pageTitle = "Sign In - Advocate & Member Portal | My Advocate";
$pageDescription = "Sign in to access your advocate dashboard or member account on My Advocate.";

$redirect = sanitize($_POST['redirect'] ?? $_GET['redirect'] ?? '');
$authRequired = isset($_GET['auth_required']);

// If already logged in, redirect to respective dashboard
if (!empty($_SESSION['advocate_id'])) {
    if ($redirect === 'pricing') {
        header("Location: pricing");
    } else {
        header("Location: dashboard");
    }
    exit;
} elseif (!empty($_SESSION['member_id'])) {
    if (!empty($redirect)) {
        header("Location: " . $redirect);
    } else {
        header("Location: member-profile");
    }
    exit;
}

$error = '';
$loggedOut = isset($_GET['logged_out']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim(sanitize($_POST['identifier'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both your identifier (Mobile/Email/Enrollment) and password.";
    } else {
        $db = getDB();
        try {
            $authenticated = false;
            $userFound = false;

            // 1. Check Advocate table first
            $stmtAdv = $db->prepare("SELECT id, name, mobile, email, e_no, password, status, plan_type FROM advocate WHERE (mobile = ? OR email = ? OR e_no = ?) AND status != 'BLOCK' LIMIT 1");
            $stmtAdv->execute([$identifier, $identifier, $identifier]);
            $adv = $stmtAdv->fetch();

            if ($adv) {
                $userFound = true;
                $passValid = false;
                if (!empty($adv['password'])) {
                    if (md5($password) === $adv['password'] || 
                        password_verify($password, $adv['password']) || 
                        $password === $adv['password']) {
                        $passValid = true;
                    }
                }

                if ($passValid) {
                    $_SESSION['advocate_id'] = (int)$adv['id'];
                    $_SESSION['advocate_name'] = $adv['name'];
                    $_SESSION['user_type'] = 'advocate';

                    if ($redirect === 'pricing') {
                        header("Location: pricing");
                    } else {
                        header("Location: dashboard");
                    }
                    exit;
                }
            }

            // 2. Check Member table
            $stmtMem = $db->prepare("SELECT id, name, mobile, email, password, status FROM member WHERE (mobile = ? OR email = ?) AND status != 'BLOCK' LIMIT 1");
            $stmtMem->execute([$identifier, $identifier]);
            $member = $stmtMem->fetch();

            if ($member) {
                $userFound = true;
                $passValid = false;
                if (!empty($member['password'])) {
                    if (md5($password) === $member['password'] || 
                        password_verify($password, $member['password']) || 
                        $password === $member['password']) {
                        $passValid = true;
                    }
                }

                if ($passValid) {
                    $_SESSION['member_id'] = (int)$member['id'];
                    $_SESSION['member_name'] = $member['name'];
                    $_SESSION['user_type'] = 'member';

                    if (!empty($redirect)) {
                        header("Location: " . $redirect);
                    } else {
                        header("Location: member-profile");
                    }
                    exit;
                }
            }

            // If found in either table but password failed
            if ($userFound) {
                $error = "Incorrect password. Please verify your credentials or reset your password.";
            } else {
                $error = "No account found matching this mobile number, email, or enrollment number.";
            }
        } catch (Exception $e) {
            $error = "Login service is temporarily unavailable. Please try again in a moment.";
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="max-width: 480px; margin: 0 auto;">
        
        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md);">
            <!-- Header Branding & Title -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: #fee2e2; border-radius: 50%; color: var(--brand-red); font-size: 1.5rem; margin-bottom: 0.85rem; border: 1px solid #fecaca;">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem;">
                    Sign In
                </h1>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0;">
                    Access your Advocate Dashboard or Client Member Account
                </p>
            </div>

            <!-- Notifications & Alerts -->
            <?php if ($authRequired && $redirect === 'pricing'): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-shield-alt text-warning"></i>
                    <span><strong>Login Required:</strong> Please log in to manage your Advocate Membership & Pricing plans.</span>
                </div>
            <?php elseif ($loggedOut): ?>
                <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-circle-check" style="color: #16a34a;"></i>
                    <span>You have been successfully signed out.</span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <i class="fas fa-triangle-exclamation"></i>
                    <span><?= sanitize($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Unified Login Form -->
            <form action="login" method="POST">
                <?php if (!empty($redirect)): ?>
                    <input type="hidden" name="redirect" value="<?= sanitize($redirect) ?>">
                <?php endif; ?>

                <div class="filter-group" style="margin-bottom: 1.25rem;">
                    <label class="filter-label" style="font-weight: 600;">
                        Mobile Number / Email / Enrollment No.
                    </label>
                    <div style="position: relative;">
                        <input type="text" name="identifier" class="filter-input" placeholder="e.g. 9876543210, name@domain.com, or Enr No." required autofocus value="<?= isset($_POST['identifier']) ? sanitize($_POST['identifier']) : '' ?>" style="padding-left: 2.5rem;">
                        <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                    </div>
                </div>

                <div class="filter-group" style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="filter-label" style="margin-bottom: 0; font-weight: 600;">Password</label>
                        <a href="reset-password" style="font-size: 0.8125rem; color: var(--brand-red); font-weight: 600; text-decoration: none;">Forgot password?</a>
                    </div>
                    <div style="position: relative;">
                        <input type="password" name="password" class="filter-input" placeholder="••••••••" required style="padding-left: 2.5rem;">
                        <i class="fas fa-key" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-muted); margin-bottom: 0;">
                        <input type="checkbox" name="remember" value="1" checked style="accent-color: var(--brand-red); cursor: pointer;">
                        <span>Remember me</span>
                    </label>
                    <a href="claim-profile" style="font-size: 0.8125rem; color: var(--brand-gold-dark); font-weight: 600; text-decoration: none;">
                        <i class="fas fa-id-badge"></i> Claim Profile
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 700; justify-content: center; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-right-to-bracket"></i> Sign In to Account
                </button>
            </form>

            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); text-align: center; font-size: 0.875rem; color: var(--text-muted);">
                Don't have an account yet? 
                <div style="margin-top: 0.5rem; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="register" style="font-weight: 700; color: var(--brand-red); text-decoration: none;">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                    &bull;
                    <a href="claim-profile" style="font-weight: 700; color: var(--brand-gold-dark); text-decoration: none;">
                        <i class="fas fa-certificate"></i> Claim Advocate Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
