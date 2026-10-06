<?php
// claim-profile.php - Advocate Profile Claim & Verification Workflow
require_once __DIR__ . '/config/app.php';

$pageTitle = "Claim Your Advocate Profile - My Advocate";
$pageDescription = "Claim and verify your listed advocate profile to update professional information, manage privacy, and obtain a verified digital badge.";

$db = getDB();
$states = getStates();

// Contact masking helpers
function maskAdvocatePhone(?string $phone): string {
    if (empty($phone)) return 'Not Registered';
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) >= 10) {
        $last4 = substr($clean, -4);
        $first2 = substr($clean, 0, 2);
        return "+91 " . $first2 . "******" . $last4;
    }
    return substr($phone, 0, 2) . '******' . substr($phone, -2);
}

function maskAdvocateEmail(?string $email): string {
    if (empty($email) || strpos($email, '@') === false) return 'Not Registered';
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '***';
    } else {
        $maskedName = substr($name, 0, 1) . str_repeat('*', min(4, $len - 2)) . substr($name, -1);
    }
    return $maskedName . '@' . $domain;
}

// Fetch active State Bar Councils from table bc
$barCouncils = [];
try {
    $stmt = $db->query("SELECT id, bc_id, code, state_code, name FROM bc WHERE status = 'ACTIVE' ORDER BY name ASC");
    $barCouncils = $stmt->fetchAll();
} catch (Exception $e) {
    $barCouncils = [];
}

$advocateId = sanitize($_GET['id'] ?? '');
$targetAdvocate = null;
$message = '';
$step = 1;
$otpSent = false;

if (!empty($advocateId) && is_numeric($advocateId)) {
    try {
        $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
        $stmt->execute([$advocateId]);
        $targetAdvocate = $stmt->fetch();
        if ($targetAdvocate) {
            $step = 2;
        }
    } catch (Exception $e) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'search_to_claim') {
        $enr = sanitize($_POST['enr'] ?? '');
        $year = sanitize($_POST['year'] ?? '');
        $bcSelected = sanitize($_POST['bc_id'] ?? '');

        // Resolve matching state_code or bc_id
        $stateCode = '';
        $bcIdVal = $bcSelected;
        foreach ($barCouncils as $bcItem) {
            if ((string)$bcItem['id'] === $bcSelected || (string)$bcItem['bc_id'] === $bcSelected || $bcItem['code'] === $bcSelected) {
                $stateCode = $bcItem['state_code'] ?: $bcItem['code'];
                $bcIdVal = $bcItem['bc_id'] ?: $bcItem['id'];
                break;
            }
        }

        try {
            $sql = "SELECT * FROM advocate WHERE e_no = ? AND e_year = ?";
            $params = [$enr, $year];
            if (!empty($bcIdVal) && !empty($stateCode)) {
                $sql .= " AND (bc_id = ? OR state_code = ?)";
                $params[] = $bcIdVal;
                $params[] = $stateCode;
            }
            $stmt = $db->prepare($sql . " LIMIT 1");
            $stmt->execute($params);
            $targetAdvocate = $stmt->fetch();

            if (!$targetAdvocate) {
                // Fallback direct match
                $stmt2 = $db->prepare("SELECT * FROM advocate WHERE e_no = ? AND e_year = ? LIMIT 1");
                $stmt2->execute([$enr, $year]);
                $targetAdvocate = $stmt2->fetch();
            }

            if ($targetAdvocate) {
                $step = 2;
                $otpSent = false;
            } else {
                $message = "No advocate found with Enrollment No. $enr / $year. You can register as a new advocate.";
            }
        } catch (Exception $e) {
            $message = "Database error. Please try again.";
        }
    } elseif ($action === 'send_otp') {
        $advId = sanitize($_POST['advocate_id'] ?? '');
        try {
            $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
            $stmt->execute([$advId]);
            $targetAdvocate = $stmt->fetch();
            if ($targetAdvocate) {
                $step = 2;
                $otpSent = true;
                $message = "Verification OTP has been sent successfully to your registered mobile and email.";
            }
        } catch (Exception $e) {
            $message = "Failed to initiate OTP verification.";
        }
    } elseif ($action === 'verify_claim') {
        $advId = sanitize($_POST['advocate_id'] ?? '');
        $otp = sanitize($_POST['otp'] ?? '');
        
        try {
            $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
            $stmt->execute([$advId]);
            $targetAdvocate = $stmt->fetch();
        } catch (Exception $e) {}

        // Verified simulation (OTP: 123456 or any 6 digit input)
        if ($otp === '123456' || strlen($otp) == 6) {
            try {
                $pubUrlToSave = !empty($targetAdvocate['public_url']) 
                    ? $targetAdvocate['public_url'] 
                    : generateAdvocatePublicUrl($targetAdvocate['name'] ?? 'Advocate', $targetAdvocate['state_code'] ?? '', $targetAdvocate['district_code'] ?? '', $db, (int)$advId);

                $stmt = $db->prepare("UPDATE advocate SET type = 'ACTIVE', plan_type = 'registered', public_url = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$pubUrlToSave, $advId]);
                $message = "🎉 Profile successfully claimed! You are now the authorized manager of this profile.";
                $step = 3;
            } catch (Exception $e) {
                $message = "Failed to update profile claim status.";
            }
        } else {
            $message = "Invalid 6-digit OTP. Please enter the valid OTP sent to your registered contacts.";
            $step = 2;
            $otpSent = true;
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Advocates</span> &bull; <span>Claim Profile</span>
    </nav>

    <div style="max-width: 680px; margin: 0 auto;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 2rem;">
            <div class="badge-verification badge-verified" style="margin-bottom: 0.75rem;">
                <i class="fas fa-id-card"></i> Official Advocate Verification
            </div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
                Claim Your Advocate Profile
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9375rem;">
                Take ownership of your indexed public record in 2 quick steps, update your practice details, and manage contact privacy.
            </p>
        </div>

        <!-- 2-Step Visual Stepper -->
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap;">
            <!-- Step 1 Indicator -->
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; border-radius: var(--radius-full); background: <?= ($step === 1) ? 'var(--brand-red-light)' : ($step > 1 ? '#f0fdf4' : 'var(--bg-alt)') ?>; border: 1.5px solid <?= ($step === 1) ? 'var(--brand-red)' : ($step > 1 ? '#16a34a' : 'var(--border-color)') ?>; color: <?= ($step === 1) ? 'var(--brand-red)' : ($step > 1 ? '#166534' : 'var(--text-muted)') ?>; font-weight: 700; font-size: 0.875rem;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: <?= ($step === 1) ? 'var(--brand-red)' : ($step > 1 ? '#16a34a' : '#94a3b8') ?>; color: #fff; font-size: 0.75rem;">
                    <?= ($step > 1) ? '<i class="fas fa-check"></i>' : '1' ?>
                </span>
                <span>Step 1: Locate Record</span>
            </div>
            
            <div style="width: 36px; height: 2px; background: <?= ($step >= 2) ? 'var(--brand-gold)' : 'var(--border-color)' ?>;"></div>
            
            <!-- Step 2 Indicator -->
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; border-radius: var(--radius-full); background: <?= ($step === 2) ? 'var(--brand-gold-light)' : ($step === 3 ? '#f0fdf4' : 'var(--bg-alt)') ?>; border: 1.5px solid <?= ($step === 2) ? 'var(--brand-gold)' : ($step === 3 ? '#16a34a' : 'var(--border-color)') ?>; color: <?= ($step === 2) ? 'var(--brand-gold-dark)' : ($step === 3 ? '#166534' : 'var(--text-muted)') ?>; font-weight: 700; font-size: 0.875rem;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: <?= ($step === 2) ? 'var(--brand-gold-dark)' : ($step === 3 ? '#16a34a' : '#94a3b8') ?>; color: #fff; font-size: 0.75rem;">
                    <?= ($step === 3) ? '<i class="fas fa-check"></i>' : '2' ?>
                </span>
                <span>Step 2: Verify & Claim</span>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="stat-box" style="margin-bottom: 1.5rem; background: <?= ($step === 3 || $otpSent) ? '#f0fdf4' : '#fffbeb' ?>; border-color: <?= ($step === 3 || $otpSent) ? '#86efac' : '#fcd34d' ?>;">
                <p style="color: <?= ($step === 3 || $otpSent) ? '#166534' : '#92400e' ?>; font-weight: 600; margin: 0;">
                    <i class="<?= ($step === 3 || $otpSent) ? 'fas fa-circle-check' : 'fas fa-info-circle' ?>"></i> <?= sanitize($message) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Step 1: Find Profile -->
        <?php if ($step === 1): ?>
            <div class="stat-box" style="border-top: 4px solid var(--brand-red);">
                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.25rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-red-light); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;">1</div>
                    <h3 style="font-size: 1.25rem; color: var(--primary); margin: 0;">Locate Your Enrollment Record</h3>
                </div>
                
                <form action="claim-profile" method="POST">
                    <input type="hidden" name="action" value="search_to_claim">
                    
                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-university" style="color: var(--brand-red);"></i> State Bar Council *</label>
                        <select name="bc_id" class="filter-select" required>
                            <option value="">-- Select State Bar Council --</option>
                            <?php foreach ($barCouncils as $bc): ?>
                                <option value="<?= sanitize($bc['bc_id'] ?: $bc['id']) ?>" <?= (isset($_POST['bc_id']) && (string)$_POST['bc_id'] === (string)($bc['bc_id'] ?: $bc['id'])) ? 'selected' : '' ?>>
                                    <?= sanitize(trim($bc['name'])) ?> <?= !empty($bc['code']) ? '(' . sanitize($bc['code']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="filter-group">
                            <label class="filter-label">Enrollment Number *</label>
                            <input type="text" name="enr" class="filter-input" placeholder="e.g. 1266 or BR/1266" required value="<?= isset($_POST['enr']) ? sanitize($_POST['enr']) : '' ?>">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Enrollment Year *</label>
                            <input type="number" name="year" class="filter-input" placeholder="e.g. <?= date('Y') ?>" min="1950" max="<?= date('Y') ?>" required value="<?= isset($_POST['year']) ? sanitize($_POST['year']) : '' ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-magnifying-glass"></i> Find My Record & Continue &rarr;
                    </button>
                </form>

                <div style="margin-top: 1.5rem; text-align: center; font-size: 0.8125rem; color: var(--text-muted);">
                    Not yet listed in our directory? <a href="register" style="font-weight: 700; color: var(--brand-red);">Register as a New Advocate &rarr;</a>
                </div>
            </div>

        <!-- Step 2: Confirm Record & OTP Workflow -->
        <?php elseif ($step === 2 && $targetAdvocate): ?>
            <div class="stat-box" style="border-top: 4px solid var(--brand-gold);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-gold-light); color: var(--brand-gold-dark); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;">2</div>
                        <h3 style="font-size: 1.25rem; color: var(--primary); margin: 0;">Confirm Profile & Verify Contact</h3>
                    </div>
                    <a href="claim-profile" class="btn btn-outline btn-sm" style="font-size: 0.75rem;"><i class="fas fa-arrow-left"></i> Change Record</a>
                </div>
                
                <!-- Matched Advocate Card with Masked Info -->
                <div style="background: var(--bg-alt); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span class="badge-verification badge-verified" style="font-size: 0.75rem; margin-bottom: 0.25rem;">Indexed Public Record</span>
                            <h4 style="font-size: 1.35rem; color: var(--primary); margin: 0.25rem 0; font-weight: 800;"><?= sanitize($targetAdvocate['name']) ?></h4>
                        </div>
                        <span class="act-year">Enr. <?= sanitize($targetAdvocate['e_no']) ?>/<?= sanitize($targetAdvocate['e_year']) ?></span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;"><i class="fas fa-university" style="color: var(--brand-red);"></i> Bar Council / State</div>
                            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-main);"><?= sanitize(getStateName($targetAdvocate['state_code'] ?? '')) ?: 'National Jurisdiction' ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;"><i class="fas fa-landmark" style="color: var(--brand-gold-dark);"></i> Primary Court</div>
                            <div style="font-size: 0.875rem; font-weight: 600; color: var(--text-main);"><?= sanitize(getCourtName($targetAdvocate['court'] ?? '')) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;"><i class="fas fa-phone text-primary"></i> Registered Mobile (Masked)</div>
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--brand-red); font-family: monospace;"><?= sanitize(maskAdvocatePhone($targetAdvocate['mobile'] ?? '')) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;"><i class="fas fa-envelope text-primary"></i> Registered Email (Masked)</div>
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--brand-gold-dark); font-family: monospace;"><?= sanitize(maskAdvocateEmail($targetAdvocate['email'] ?? '')) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Sub-step A: User Clicks to Send OTP -->
                <?php if (!$otpSent): ?>
                    <div style="background: #fffdf0; border: 1px solid var(--brand-gold-border); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem; text-align: center;">
                        <p style="color: var(--brand-gold-text); font-size: 0.875rem; line-height: 1.6; margin: 0 0 1rem 0;">
                            <i class="fas fa-shield-halved"></i> To confirm that you are the rightful owner of this advocate profile, a 6-digit verification OTP will be dispatched to your registered mobile and email shown above.
                        </p>
                        <form action="claim-profile" method="POST">
                            <input type="hidden" name="action" value="send_otp">
                            <input type="hidden" name="advocate_id" value="<?= $targetAdvocate['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                <i class="fas fa-paper-plane"></i> Send OTP to Registered Contact &rarr;
                            </button>
                        </form>
                    </div>

                <!-- Sub-step B: OTP Sent -> User Enters OTP -->
                <?php else: ?>
                    <form action="claim-profile" method="POST" style="background: #ffffff; padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid #86efac; box-shadow: var(--shadow-sm);">
                        <input type="hidden" name="action" value="verify_claim">
                        <input type="hidden" name="advocate_id" value="<?= $targetAdvocate['id'] ?>">

                        <div class="filter-group">
                            <label class="filter-label" style="text-align: center; display: block; font-size: 0.95rem;">
                                Enter 6-Digit Verification OTP *
                            </label>
                            <input type="text" name="otp" class="filter-input" placeholder="Enter 6-digit OTP (demo: 123456)" maxlength="6" pattern="[0-9]{6}" required style="font-size: 1.4rem; letter-spacing: 0.3em; text-align: center; font-weight: 800; max-width: 320px; margin: 0 auto; display: block;" autofocus>
                            <small style="color: var(--text-muted); display: block; text-align: center; margin-top: 0.5rem;">
                                <i class="fas fa-clock"></i> OTP is valid for 10 minutes.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg" style="width: 100%; margin-top: 0.75rem;">
                            <i class="fas fa-circle-check"></i> Verify OTP & Complete Profile Claim
                        </button>
                    </form>

                    <div style="margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; font-size: 0.8125rem;">
                        <form action="claim-profile" method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="send_otp">
                            <input type="hidden" name="advocate_id" value="<?= $targetAdvocate['id'] ?>">
                            <button type="submit" style="background: none; border: none; color: var(--brand-red); font-weight: 700; cursor: pointer; padding: 0;">
                                <i class="fas fa-rotate-right"></i> Resend OTP
                            </button>
                        </form>
                        <a href="claim-profile" style="color: var(--text-muted);"><i class="fas fa-arrow-left"></i> Change Enrollment Search</a>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Step 3: Success -->
        <?php elseif ($step === 3): ?>
            <div class="stat-box" style="text-align: center; padding: 3rem 1.5rem; border-top: 4px solid #16a34a;">
                <div style="font-size: 3.5rem; color: #16a34a; margin-bottom: 1rem;"><i class="fas fa-circle-check"></i></div>
                <h2 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: var(--primary);">Profile Claim Completed!</h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem; max-width: 520px; margin-left: auto; margin-right: auto;">
                    You are now the authorized owner of this advocate profile. You can sign in to customize your practice areas, public bio, and privacy settings.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="login" class="btn btn-primary"><i class="fas fa-right-to-bracket"></i> Login to Dashboard</a>
                    <a href="./" class="btn btn-outline">Back to Home</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>


