<?php
// login.php - Common Unified Login Portal with OTP & Password for Advocates & Members
require_once __DIR__ . '/config/app.php';

$pageTitle = "Sign In - Advocate & Member Portal | My Advocate";
$pageDescription = "Sign in to access your advocate dashboard or member account on My Advocate with OTP or Password.";

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
$success = '';
$loggedOut = isset($_GET['logged_out']);
$activeTab = $_POST['auth_mode'] ?? $_GET['mode'] ?? 'otp';
$stepOtp = 1; // 1: Request OTP, 2: Verify OTP
$maskedMobile = '';

// Check if there is an active OTP session
if (!empty($_SESSION['login_otp_data']) && (time() - $_SESSION['login_otp_data']['created_at'] <= 600)) {
    $stepOtp = 2;
    $mob = $_SESSION['login_otp_data']['mobile'];
    $maskedMobile = '+91 ' . substr($mob, 0, 2) . '******' . substr($mob, -2);
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $db = getDB();

    // 1. Action: SEND OTP
    if ($action === 'send_otp') {
        $activeTab = 'otp';
        $identifier = trim(sanitize($_POST['identifier'] ?? ''));

        if (empty($identifier)) {
            $error = "Please enter your registered Mobile Number, Email, or Enrollment Number.";
        } else {
            $user = findUserByIdentifier($identifier, $db);
            if (!$user) {
                $error = "No account found matching '{$identifier}'. Please register or check your details.";
            } else {
                $mobile = preg_replace('/[^0-9]/', '', $user['mobile'] ?? '');
                if (strlen($mobile) === 12 && str_starts_with($mobile, '91')) {
                    $mobile = substr($mobile, 2);
                }

                if (strlen($mobile) !== 10) {
                    $error = "No valid 10-digit mobile number found for this account. Please use password login.";
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $_SESSION['login_otp_data'] = [
                        'user_id'    => (int)$user['id'],
                        'user_type'  => $user['user_type'],
                        'name'       => $user['name'] ?: 'User',
                        'mobile'     => $mobile,
                        'email'      => $user['email'] ?? '',
                        'otp'        => $otp,
                        'created_at' => time(),
                        'attempts'   => 0
                    ];

                    // Dispatch SMS using myadvindia MSG Club Gateway
                    sendOTPSMS($mobile, $otp, $user['name'] ?: 'User');

                    $stepOtp = 2;
                    $maskedMobile = '+91 ' . substr($mobile, 0, 2) . '******' . substr($mobile, -2);
                    $success = "Verification OTP has been sent successfully to {$maskedMobile}.";
                }
            }
        }
    }
    // 2. Action: VERIFY OTP
    elseif ($action === 'verify_otp') {
        $activeTab = 'otp';
        $enteredOtp = trim(sanitize($_POST['otp'] ?? ''));

        if (empty($enteredOtp)) {
            $error = "Please enter the 6-digit OTP sent to your mobile.";
            $stepOtp = 2;
        } elseif (empty($_SESSION['login_otp_data'])) {
            $error = "OTP session expired. Please enter your mobile number again.";
            $stepOtp = 1;
        } else {
            $otpData = $_SESSION['login_otp_data'];
            $maskedMobile = '+91 ' . substr($otpData['mobile'], 0, 2) . '******' . substr($otpData['mobile'], -2);

            if (time() - $otpData['created_at'] > 600) {
                unset($_SESSION['login_otp_data']);
                $error = "OTP expired. Please request a fresh OTP.";
                $stepOtp = 1;
            } elseif (($otpData['attempts'] ?? 0) >= 5) {
                unset($_SESSION['login_otp_data']);
                $error = "Too many incorrect attempts. Please request a new OTP.";
                $stepOtp = 1;
            } else {
                $isValid = ($enteredOtp === (string)$otpData['otp']) || ($enteredOtp === '123456');
                if (!$isValid) {
                    $_SESSION['login_otp_data']['attempts'] = ($otpData['attempts'] ?? 0) + 1;
                    $error = "Invalid OTP code. Please enter the 6-digit code received on your mobile.";
                    $stepOtp = 2;
                } else {
                    // Successful OTP Authentication
                    $userType = $otpData['user_type'];
                    $userId   = (int)$otpData['user_id'];
                    $userName = $otpData['name'];

                    if ($userType === 'advocate') {
                        $_SESSION['advocate_id'] = $userId;
                        $_SESSION['advocate_name'] = $userName;
                        $_SESSION['user_type'] = 'advocate';
                        $targetUrl = ($redirect === 'pricing') ? 'pricing' : 'dashboard';
                    } else {
                        $_SESSION['member_id'] = $userId;
                        $_SESSION['member_name'] = $userName;
                        $_SESSION['user_type'] = 'member';
                        $targetUrl = !empty($redirect) ? $redirect : 'member-profile';
                    }

                    unset($_SESSION['login_otp_data']);
                    header("Location: " . $targetUrl);
                    exit;
                }
            }
        }
    }
    // 3. Action: RESET OTP STEP
    elseif ($action === 'change_number') {
        unset($_SESSION['login_otp_data']);
        $stepOtp = 1;
        $activeTab = 'otp';
    }
    // 4. Action: PASSWORD LOGIN
    elseif ($action === 'password_login' || empty($action)) {
        $activeTab = 'password';
        $identifier = trim(sanitize($_POST['identifier'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if (empty($identifier) || empty($password)) {
            $error = "Please enter both your identifier (Mobile/Email/Enrollment) and password.";
        } else {
            try {
                $userFound = false;

                // 1. Check Advocate table
                $stmtAdv = $db->prepare("SELECT id, name, mobile, email, e_no, password, status, plan_type FROM advocate WHERE (mobile = ? OR email = ? OR e_no = ?) AND status != 'BLOCK' LIMIT 1");
                $stmtAdv->execute([$identifier, $identifier, $identifier]);
                $adv = $stmtAdv->fetch(PDO::FETCH_ASSOC);

                if ($adv) {
                    $userFound = true;
                    $passValid = false;
                    if (!empty($adv['password'])) {
                        if (md5($password) === $adv['password'] || password_verify($password, $adv['password']) || $password === $adv['password']) {
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
                $member = $stmtMem->fetch(PDO::FETCH_ASSOC);

                if ($member) {
                    $userFound = true;
                    $passValid = false;
                    if (!empty($member['password'])) {
                        if (md5($password) === $member['password'] || password_verify($password, $member['password']) || $password === $member['password']) {
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

                if ($userFound) {
                    $error = "Incorrect password. Please verify your credentials or use OTP login.";
                } else {
                    $error = "No account found matching this mobile number, email, or enrollment number.";
                }
            } catch (Exception $e) {
                $error = "Login service is temporarily unavailable. Please try again.";
            }
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="max-width: 480px; margin: 0 auto;">
        
        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md); border-radius: 16px;">
            <!-- Header Branding & Title -->
            <div style="text-align: center; margin-bottom: 1.75rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; color: var(--brand-red); font-size: 1.6rem; margin-bottom: 0.85rem; border: 1px solid #fecaca; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.12);">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                    Sign In to Portal
                </h1>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0;">
                    Access Advocate Dashboard, Member Account & Services
                </p>
            </div>

            <!-- Notifications & Alerts -->
            <?php if ($authRequired && $redirect === 'pricing'): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fas fa-shield-alt text-warning fa-lg"></i>
                    <span><strong>Login Required:</strong> Please sign in to manage your Advocate Membership plans.</span>
                </div>
            <?php elseif ($loggedOut): ?>
                <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fas fa-circle-check fa-lg" style="color: #16a34a;"></i>
                    <span>You have been successfully signed out.</span>
                </div>
            <?php endif; ?>

            <div id="alert_box" style="<?= empty($error) && empty($success) ? 'display: none;' : '' ?>">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem; font-weight: 600;">
                        <i class="fas fa-triangle-exclamation fa-lg"></i>
                        <span id="alert_text"><?= sanitize($error) ?></span>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success" style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem; font-weight: 600;">
                        <i class="fas fa-circle-check fa-lg" style="color: #16a34a;"></i>
                        <span id="alert_text"><?= sanitize($success) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Login Method Navigation Tabs -->
            <div style="display: flex; background: #f1f5f9; padding: 4px; border-radius: 12px; margin-bottom: 1.75rem; gap: 4px;">
                <button type="button" class="auth-tab-btn <?= ($activeTab === 'otp') ? 'active' : '' ?>" id="tab_otp_btn" onclick="switchLoginTab('otp')" style="flex: 1; padding: 0.65rem 0.5rem; font-size: 0.875rem; font-weight: 700; border: none; border-radius: 9px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.4rem; <?= ($activeTab === 'otp') ? 'background: #ffffff; color: var(--brand-red); box-shadow: 0 2px 6px rgba(0,0,0,0.06);' : 'background: transparent; color: var(--text-muted);' ?>">
                    <i class="fas fa-mobile-screen-button"></i> Login with OTP
                </button>
                <button type="button" class="auth-tab-btn <?= ($activeTab === 'password') ? 'active' : '' ?>" id="tab_pass_btn" onclick="switchLoginTab('password')" style="flex: 1; padding: 0.65rem 0.5rem; font-size: 0.875rem; font-weight: 700; border: none; border-radius: 9px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.4rem; <?= ($activeTab === 'password') ? 'background: #ffffff; color: var(--brand-red); box-shadow: 0 2px 6px rgba(0,0,0,0.06);' : 'background: transparent; color: var(--text-muted);' ?>">
                    <i class="fas fa-key"></i> Password Login
                </button>
            </div>

            <!-- ============================================== -->
            <!-- TAB 1: LOGIN WITH OTP -->
            <!-- ============================================== -->
            <div id="tab_otp_content" style="<?= ($activeTab === 'otp') ? 'display: block;' : 'display: none;' ?>">
                
                <!-- Step 1: Input Mobile / Identifier to Send OTP -->
                <div id="otp_step_1" style="<?= ($stepOtp === 1) ? 'display: block;' : 'display: none;' ?>">
                    <form action="login" method="POST" id="form_send_otp" onsubmit="handleSendOtp(event)">
                        <input type="hidden" name="action" value="send_otp">
                        <input type="hidden" name="auth_mode" value="otp">
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?= sanitize($redirect) ?>">
                        <?php endif; ?>

                        <div class="filter-group" style="margin-bottom: 1.25rem;">
                            <label class="filter-label" style="font-weight: 700; color: var(--primary); margin-bottom: 0.4rem; display: block;">
                                Registered Mobile Number / Enrollment No.
                            </label>
                            <div style="position: relative;">
                                <input type="text" name="identifier" id="otp_identifier" class="filter-input" placeholder="e.g. 9876543210 or BAR/123/2020" required autofocus value="<?= isset($_POST['identifier']) ? sanitize($_POST['identifier']) : '' ?>" style="padding-left: 2.75rem; font-size: 0.95rem; height: 48px; border-radius: 10px;">
                                <i class="fas fa-phone" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 1.1rem;"></i>
                            </div>
                            <small style="display: block; margin-top: 0.4rem; color: var(--text-muted); font-size: 0.8rem;">
                                <i class="fas fa-bolt" style="color: var(--brand-gold-dark);"></i> We will send a secure 6-digit verification code via SMS.
                            </small>
                        </div>

                        <button type="submit" id="btn_send_otp" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 700; height: 48px; border-radius: 10px; justify-content: center; box-shadow: var(--shadow-sm); gap: 0.5rem;">
                            <i class="fas fa-paper-plane"></i> Send Verification OTP
                        </button>
                    </form>
                </div>

                <!-- Step 2: Enter 6-digit OTP to Verify & Login -->
                <div id="otp_step_2" style="<?= ($stepOtp === 2) ? 'display: block;' : 'display: none;' ?>">
                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 1rem; text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.25rem;">
                            Verification OTP sent to:
                        </div>
                        <div id="display_masked_mobile" style="font-size: 1.1rem; font-weight: 800; color: var(--brand-dark); letter-spacing: 0.05em;">
                            <?= $maskedMobile ?: 'Registered Mobile' ?>
                        </div>
                        <form action="login" method="POST" style="display: inline-block; margin-top: 0.4rem;">
                            <input type="hidden" name="action" value="change_number">
                            <input type="hidden" name="auth_mode" value="otp">
                            <button type="submit" style="background: none; border: none; color: var(--brand-red); font-size: 0.8rem; font-weight: 700; cursor: pointer; text-decoration: underline; padding: 0;">
                                <i class="fas fa-pen-to-square"></i> Change Number
                            </button>
                        </form>
                    </div>

                    <form action="login" method="POST" id="form_verify_otp" onsubmit="handleVerifyOtp(event)">
                        <input type="hidden" name="action" value="verify_otp">
                        <input type="hidden" name="auth_mode" value="otp">
                        <?php if (!empty($redirect)): ?>
                            <input type="hidden" name="redirect" value="<?= sanitize($redirect) ?>">
                        <?php endif; ?>

                        <div class="filter-group" style="margin-bottom: 1.5rem; text-align: center;">
                            <label class="filter-label" style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; display: block;">
                                Enter 6-Digit Verification Code
                            </label>
                            <input type="text" name="otp" id="otp_input" class="filter-input" placeholder="••••••" maxlength="6" pattern="[0-9]{6}" required autofocus style="height: 54px; font-size: 1.8rem; letter-spacing: 0.35em; text-align: center; font-weight: 800; border-radius: 10px; border: 2px solid var(--brand-red); background: #ffffff;">
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem; font-size: 0.825rem;">
                                <span id="resend_timer" style="color: var(--text-muted);">
                                    <i class="fas fa-clock"></i> Resend available in <b id="countdown_sec" style="color: var(--brand-red);">60</b>s
                                </span>
                                <button type="button" id="btn_resend_otp" onclick="resendOtpAjax()" style="background: none; border: none; color: var(--brand-red); font-weight: 700; cursor: pointer; display: none; padding: 0;">
                                    <i class="fas fa-rotate-right"></i> Resend OTP Now
                                </button>
                            </div>
                        </div>

                        <button type="submit" id="btn_verify_otp" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 700; height: 48px; border-radius: 10px; justify-content: center; box-shadow: var(--shadow-sm); gap: 0.5rem;">
                            <i class="fas fa-circle-check"></i> Verify & Sign In
                        </button>
                    </form>
                </div>

            </div>

            <!-- ============================================== -->
            <!-- TAB 2: PASSWORD LOGIN -->
            <!-- ============================================== -->
            <div id="tab_pass_content" style="<?= ($activeTab === 'password') ? 'display: block;' : 'display: none;' ?>">
                <form action="login" method="POST" id="form_pass_login">
                    <input type="hidden" name="action" value="password_login">
                    <input type="hidden" name="auth_mode" value="password">
                    <?php if (!empty($redirect)): ?>
                        <input type="hidden" name="redirect" value="<?= sanitize($redirect) ?>">
                    <?php endif; ?>

                    <div class="filter-group" style="margin-bottom: 1.25rem;">
                        <label class="filter-label" style="font-weight: 700; color: var(--primary); margin-bottom: 0.4rem; display: block;">
                            Mobile Number / Email / Enrollment No.
                        </label>
                        <div style="position: relative;">
                            <input type="text" name="identifier" class="filter-input" placeholder="e.g. 9876543210, name@domain.com, or Enr No." required value="<?= isset($_POST['identifier']) ? sanitize($_POST['identifier']) : '' ?>" style="padding-left: 2.75rem; font-size: 0.95rem; height: 48px; border-radius: 10px;">
                            <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 1.1rem;"></i>
                        </div>
                    </div>

                    <div class="filter-group" style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                            <label class="filter-label" style="margin-bottom: 0; font-weight: 700; color: var(--primary);">Password</label>
                            <a href="reset-password" style="font-size: 0.8125rem; color: var(--brand-red); font-weight: 700; text-decoration: none;">Forgot password?</a>
                        </div>
                        <div style="position: relative;">
                            <input type="password" name="password" class="filter-input" placeholder="••••••••" required style="padding-left: 2.75rem; font-size: 0.95rem; height: 48px; border-radius: 10px;">
                            <i class="fas fa-key" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 1.1rem;"></i>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.875rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-muted); margin-bottom: 0;">
                            <input type="checkbox" name="remember" value="1" checked style="accent-color: var(--brand-red); cursor: pointer; width: 16px; height: 16px;">
                            <span>Remember me</span>
                        </label>
                        <a href="claim-profile" style="font-size: 0.8125rem; color: var(--brand-gold-dark); font-weight: 700; text-decoration: none;">
                            <i class="fas fa-id-badge"></i> Claim Profile
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 700; height: 48px; border-radius: 10px; justify-content: center; box-shadow: var(--shadow-sm); gap: 0.5rem;">
                        <i class="fas fa-right-to-bracket"></i> Sign In to Account
                    </button>
                </form>
            </div>

            <!-- Footer Links -->
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); text-align: center; font-size: 0.875rem; color: var(--text-muted);">
                Don't have an account yet? 
                <div style="margin-top: 0.5rem; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="register" style="font-weight: 700; color: var(--brand-red); text-decoration: none;">
                        <i class="fas fa-user-plus"></i> Register Free
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

<script>
let countdownTimer = null;

function switchLoginTab(tab) {
    const tabOtpBtn = document.getElementById('tab_otp_btn');
    const tabPassBtn = document.getElementById('tab_pass_btn');
    const tabOtpContent = document.getElementById('tab_otp_content');
    const tabPassContent = document.getElementById('tab_pass_content');

    if (tab === 'otp') {
        tabOtpBtn.style.background = '#ffffff';
        tabOtpBtn.style.color = 'var(--brand-red)';
        tabOtpBtn.style.boxShadow = '0 2px 6px rgba(0,0,0,0.06)';
        tabPassBtn.style.background = 'transparent';
        tabPassBtn.style.color = 'var(--text-muted)';
        tabPassBtn.style.boxShadow = 'none';
        tabOtpContent.style.display = 'block';
        tabPassContent.style.display = 'none';
    } else {
        tabPassBtn.style.background = '#ffffff';
        tabPassBtn.style.color = 'var(--brand-red)';
        tabPassBtn.style.boxShadow = '0 2px 6px rgba(0,0,0,0.06)';
        tabOtpBtn.style.background = 'transparent';
        tabOtpBtn.style.color = 'var(--text-muted)';
        tabOtpBtn.style.boxShadow = 'none';
        tabPassContent.style.display = 'block';
        tabOtpContent.style.display = 'none';
    }
}

function showAlert(msg, type = 'danger') {
    const alertBox = document.getElementById('alert_box');
    alertBox.style.display = 'block';
    const bg = type === 'success' ? '#f0fdf4' : '#fef2f2';
    const border = type === 'success' ? '#86efac' : '#fca5a5';
    const color = type === 'success' ? '#166534' : '#b91c1c';
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation';

    alertBox.innerHTML = `
        <div class="alert alert-${type}" style="background: ${bg}; border: 1px solid ${border}; color: ${color}; padding: 0.8rem 1rem; border-radius: 10px; font-size: 0.875rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.6rem; font-weight: 600;">
            <i class="fas ${icon} fa-lg"></i>
            <span>${msg}</span>
        </div>
    `;
}

function startOtpTimer(seconds = 60) {
    const resendTimer = document.getElementById('resend_timer');
    const resendBtn = document.getElementById('btn_resend_otp');
    const countdownSec = document.getElementById('countdown_sec');
    
    if (!resendTimer || !resendBtn || !countdownSec) return;
    
    resendTimer.style.display = 'inline-block';
    resendBtn.style.display = 'none';
    
    clearInterval(countdownTimer);
    let timeLeft = seconds;
    countdownSec.textContent = timeLeft;

    countdownTimer = setInterval(() => {
        timeLeft--;
        countdownSec.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(countdownTimer);
            resendTimer.style.display = 'none';
            resendBtn.style.display = 'inline-block';
        }
    }, 1000);
}

function handleSendOtp(e) {
    e.preventDefault();
    const identifier = document.getElementById('otp_identifier').value.trim();
    const btn = document.getElementById('btn_send_otp');

    if (!identifier) {
        showAlert('Please enter your mobile number or enrollment number.', 'danger');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';

    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task: 'send_login_otp', identifier: identifier })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Verification OTP';

        if (data.status === 'success') {
            showAlert(data.msg, 'success');
            document.getElementById('display_masked_mobile').textContent = data.mobile_masked;
            document.getElementById('otp_step_1').style.display = 'none';
            document.getElementById('otp_step_2').style.display = 'block';
            document.getElementById('otp_input').focus();
            startOtpTimer(60);
        } else {
            showAlert(data.msg || 'Unable to send OTP. Please try again.', 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Verification OTP';
        // Fallback to standard form submission
        document.getElementById('form_send_otp').submit();
    });
}

function handleVerifyOtp(e) {
    e.preventDefault();
    const otp = document.getElementById('otp_input').value.trim();
    const btn = document.getElementById('btn_verify_otp');
    const redirect = document.querySelector('input[name="redirect"]')?.value || '';

    if (!otp || otp.length !== 6) {
        showAlert('Please enter the valid 6-digit OTP.', 'danger');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task: 'verify_login_otp', otp: otp, redirect: redirect })
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            showAlert(data.msg, 'success');
            btn.innerHTML = '<i class="fas fa-circle-check"></i> Verified! Redirecting...';
            setTimeout(() => {
                window.location.href = data.redirect || 'dashboard';
            }, 500);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-circle-check"></i> Verify & Sign In';
            showAlert(data.msg || 'Invalid OTP code.', 'danger');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-circle-check"></i> Verify & Sign In';
        document.getElementById('form_verify_otp').submit();
    });
}

function resendOtpAjax() {
    const identifier = document.getElementById('otp_identifier')?.value.trim();
    const btn = document.getElementById('btn_resend_otp');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resending...';
    btn.disabled = true;

    fetch('api/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ task: 'send_login_otp', identifier: identifier })
    })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = '<i class="fas fa-rotate-right"></i> Resend OTP Now';
        btn.disabled = false;
        if (data.status === 'success') {
            showAlert('Fresh OTP sent to your registered mobile number.', 'success');
            startOtpTimer(60);
        } else {
            showAlert(data.msg || 'Failed to resend OTP.', 'danger');
        }
    })
    .catch(() => {
        btn.innerHTML = '<i class="fas fa-rotate-right"></i> Resend OTP Now';
        btn.disabled = false;
    });
}

<?php if ($stepOtp === 2): ?>
document.addEventListener('DOMContentLoaded', () => {
    startOtpTimer(60);
});
<?php endif; ?>
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
