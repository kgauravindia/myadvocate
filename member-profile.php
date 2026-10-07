<?php
// member-profile.php - Member / General User Account & Dashboard
require_once __DIR__ . '/config/app.php';

// Auth check
if (empty($_SESSION['member_id'])) {
    if (!empty($_SESSION['advocate_id'])) {
        header("Location: dashboard");
        exit;
    }
    header("Location: login?type=user");
    exit;
}

$memberId = (int)$_SESSION['member_id'];
$db = getDB();
$states = getStates();

$msg = '';
$err = '';

// Fetch member details
$stmt = $db->prepare("SELECT * FROM member WHERE id = ? LIMIT 1");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    header("Location: login?type=user");
    exit;
}

// Handle profile update & password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $whatsapp = sanitize($_POST['whatsapp'] ?? '');
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $dob = sanitize($_POST['dob'] ?? '');
        $stateCode = sanitize($_POST['state_code'] ?? '');
        $districtCode = sanitize($_POST['district_code'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if ($name) {
            try {
                $up = $db->prepare("UPDATE member SET name = ?, email = ?, whatsapp = ?, gender = ?, dob = ?, state_code = ?, district_code = ?, pincode = ?, address = ?, updated_at = NOW() WHERE id = ?");
                $up->execute([$name, $email, $whatsapp, $gender, $dob ?: null, $stateCode, $districtCode, $pincode, $address, $memberId]);
                $_SESSION['member_name'] = $name;
                $msg = "Your profile details have been updated successfully.";

                // Reload member
                $stmt->execute([$memberId]);
                $member = $stmt->fetch();
            } catch (Exception $e) {
                $err = "Failed to update profile: " . $e->getMessage();
            }
        } else {
            $err = "Full name is required.";
        }
    } elseif ($action === 'change_password') {
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!empty($newPass)) {
            if ($newPass !== $confirmPass) {
                $err = "New password and confirmation do not match.";
            } elseif (strlen($newPass) < 6) {
                $err = "Password must be at least 6 characters long.";
            } else {
                try {
                    $hash = md5($newPass);
                    $up = $db->prepare("UPDATE member SET password = ?, updated_at = NOW() WHERE id = ?");
                    $up->execute([$hash, $memberId]);
                    $msg = "Your password has been changed successfully.";
                } catch (Exception $e) {
                    $err = "Failed to change password.";
                }
            }
        } else {
            $err = "Please enter a new password.";
        }
    }
}

$selectedState = $member['state_code'] ?? '';
$selectedDistrict = $member['district_code'] ?? '';
$districts = !empty($selectedState) ? getDistrictsByState($selectedState) : [];

$viewedAdvocates = getMemberAdvocateViews($memberId, 50);

$pageTitle = "My Account - " . sanitize($member['name'] ?: 'User Profile');
require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="./">Home</a> &bull; <span>User Portal</span> &bull; <span>My Account</span>
    </nav>

    <!-- Header & User Card -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fffdf0 50%, #fef2f2 100%); border-top: 4px solid var(--brand-gold);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1.25rem;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: var(--brand-gold-light); border: 2px solid var(--brand-gold); display: flex; align-items: center; justify-content: center; font-size: 2rem; color: var(--brand-gold-dark); font-weight: 800; font-family: var(--font-heading);">
                    <?= strtoupper(substr(trim($member['name'] ?: 'U'), 0, 1)) ?>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <h1 style="font-size: 1.6rem; color: var(--primary); margin: 0; font-weight: 800;"><?= sanitize($member['name']) ?></h1>
                        <span class="badge-verification badge-verified" style="font-size: 0.75rem;"><i class="fas fa-check-circle"></i> Active User</span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.35rem 0 0 0;">
                        <i class="fas fa-phone text-primary" style="margin-right: 4px;"></i> <?= sanitize($member['mobile']) ?>
                        <?php if (!empty($member['email'])): ?>
                            &bull; <i class="fas fa-envelope text-primary" style="margin-right: 4px;"></i> <?= sanitize($member['email']) ?>
                        <?php endif; ?>
                        <?php if (!empty($member['state_code'])): ?>
                            &bull; <i class="fas fa-map-marker-alt text-primary" style="margin-right: 4px;"></i> <?= sanitize(getStateName($member['state_code'])) ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div>
                <a href="logout" class="btn btn-outline-danger btn-sm"><i class="fas fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Feedback Alerts -->
    <?php if ($msg): ?>
        <div class="stat-box" style="background: #f0fdf4; border-color: #86efac; color: #166534; font-weight: 600; margin-bottom: 1.5rem;">
            <i class="fas fa-circle-check"></i> <?= sanitize($msg) ?>
        </div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
            <i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?>
        </div>
    <?php endif; ?>

    <!-- User Quick Action Shortcuts -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <a href="advocate-search-result" class="stat-box" style="text-decoration: none; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
            <div class="action-icon icon-red"><i class="fas fa-user-tie"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Find Advocates</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Search verified lawyers</div>
            </div>
        </a>
        <a href="acts" class="stat-box" style="text-decoration: none; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
            <div class="action-icon icon-gold"><i class="fas fa-book-open"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Central Bare Acts</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">BNS, BNSS & Indian Laws</div>
            </div>
        </a>
        <a href="courts" class="stat-box" style="text-decoration: none; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
            <div class="action-icon icon-black"><i class="fas fa-landmark"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Courts Directory</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">High Courts & e-Courts</div>
            </div>
        </a>
        <a href="tools" class="stat-box" style="text-decoration: none; display: flex; align-items: center; gap: 1rem; transition: var(--transition);">
            <div class="action-icon icon-gold"><i class="fas fa-calculator"></i></div>
            <div>
                <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">Legal Tools</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Court Fee & Limitation</div>
            </div>
        </a>
    </div>

    <!-- =========================================================
         SECTION: ADVOCATE PROFILE VIEWS (Recently Viewed Advocates)
         ========================================================= -->
    <div class="stat-box" style="margin-bottom: 2rem; border-top: 4px solid var(--brand-red); padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-eye" style="color: var(--brand-red);"></i> Advocate Profile Views
                </h3>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin: 0.2rem 0 0;">
                    Advocates whose public profiles you have recently viewed and explored.
                </p>
            </div>
            <div>
                <span class="badge" style="background: var(--brand-red); color: #fff; font-size: 0.78rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 9999px;">
                    <?= count($viewedAdvocates) ?> <?= count($viewedAdvocates) === 1 ? 'Advocate' : 'Advocates' ?> Viewed
                </span>
            </div>
        </div>

        <?php if (!empty($viewedAdvocates)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <?php foreach ($viewedAdvocates as $vAdv): 
                    $advName = $vAdv['advocate_name'] ?: 'Advocate';
                    $advPhoto = getAdvocatePhotoUrl($vAdv['photo'] ?? '');
                    $profileUrl = !empty($vAdv['public_url']) ? APP_URL . '/@' . ltrim($vAdv['public_url'], '@') : APP_URL . '/profile.php?id=' . $vAdv['advocate_id'];
                    $locationStr = implode(', ', array_filter([$vAdv['district_name'] ?? '', $vAdv['state_name'] ?? '']));
                    $courtStr = $vAdv['practicing_courts'] ?: ($vAdv['court'] ?? '');
                ?>
                    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='var(--brand-red)'; this.style.boxShadow='var(--shadow-sm)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.03)';">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #fffdf0; border: 2px solid var(--brand-gold); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 800; color: var(--brand-red); flex-shrink: 0;">
                                    <?php if ($advPhoto): ?>
                                        <img src="<?= sanitize($advPhoto) ?>" alt="<?= sanitize($advName) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr(trim($advName ?: 'A'), 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin: 0 0 0.15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <a href="<?= sanitize($profileUrl) ?>" style="color: var(--primary); text-decoration: none;">
                                            Adv. <?= sanitize($advName) ?>
                                        </a>
                                    </h4>
                                    <?php if (!empty($vAdv['e_no'])): ?>
                                        <div style="font-size: 0.73rem; color: var(--text-muted);">
                                            <i class="fas fa-id-card me-1"></i> Enr: <strong><?= sanitize($vAdv['e_no']) ?><?= !empty($vAdv['e_year']) ? '/' . sanitize($vAdv['e_year']) : '' ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($courtStr || $locationStr): ?>
                                <div style="font-size: 0.78rem; color: #475569; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.35rem;">
                                    <i class="fas fa-location-dot" style="color: var(--brand-red); font-size: 0.75rem;"></i>
                                    <span><?= sanitize($courtStr ?: $locationStr) ?><?= ($courtStr && $locationStr) ? ' &bull; ' . sanitize($locationStr) : '' ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($vAdv['practice_area'])): ?>
                                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.75rem; line-height: 1.3;">
                                    <i class="fas fa-scale-balanced me-1 text-warning"></i> <?= sanitize(mb_strimwidth($vAdv['practice_area'], 0, 60, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="border-top: 1px dashed #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="font-size: 0.72rem; color: var(--text-muted);">
                                <i class="fas fa-clock me-1"></i> <?= date('d M, h:i A', strtotime($vAdv['last_viewed_at'])) ?>
                                <?php if ($vAdv['view_count'] > 1): ?>
                                    <span style="background: #f1f5f9; padding: 0.1rem 0.35rem; border-radius: 4px; font-weight: 600; margin-left: 0.2rem;"><?= $vAdv['view_count'] ?> views</span>
                                <?php endif; ?>
                            </span>
                            <a href="<?= sanitize($profileUrl) ?>" class="btn btn-outline-primary btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.65rem; font-weight: 700;">
                                View Profile <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted); background: #f8fafc; border-radius: var(--radius-md);">
                <i class="fas fa-user-slash" style="font-size: 2.25rem; color: #cbd5e1; margin-bottom: 0.5rem; display: block;"></i>
                <p style="margin: 0 0 1rem; font-size: 0.875rem;">You have not viewed any advocate profiles yet.</p>
                <a href="advocate-search-result" class="btn btn-primary btn-sm">
                    <i class="fas fa-magnifying-glass me-1"></i> Explore Advocate Directory
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        <!-- Left Column: Edit Profile Details Form -->
        <div class="stat-box" style="padding: 2rem;">
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <i class="fas fa-user-pen" style="color: var(--brand-red); font-size: 1.25rem;"></i>
                <h3 style="font-size: 1.25rem; color: var(--primary); margin: 0;">Personal Details</h3>
            </div>

            <form action="member-profile" method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="filter-group">
                    <label class="filter-label">Full Name *</label>
                    <input type="text" name="name" class="filter-input" required value="<?= sanitize($member['name'] ?? '') ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="filter-input" value="<?= sanitize($member['mobile'] ?? '') ?>" readonly style="background: var(--bg-alt); cursor: not-allowed;">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">WhatsApp Number</label>
                        <input type="tel" name="whatsapp" class="filter-input" placeholder="WhatsApp Number" value="<?= sanitize($member['whatsapp'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Email Address</label>
                        <input type="email" name="email" class="filter-input" placeholder="user@example.com" value="<?= sanitize($member['email'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Gender</label>
                        <select name="gender" class="filter-select">
                            <option value="Male" <?= ($member['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($member['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($member['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <!-- State & District Selection -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-map-location-dot" style="color: var(--brand-red);"></i> State</label>
                        <select name="state_code" id="stateSelectMem" class="filter-select state-cascade" data-target="#districtSelectMem">
                            <option value="">-- Select State --</option>
                            <?php foreach ($states as $sCode => $sName): ?>
                                <option value="<?= sanitize($sCode) ?>" <?= ($selectedState === $sCode) ? 'selected' : '' ?>>
                                    <?= sanitize(trim($sName)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><i class="fas fa-location-dot" style="color: var(--brand-gold-dark);"></i> District</label>
                        <select name="district_code" id="districtSelectMem" class="filter-select">
                            <option value="">-- Select District --</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= sanitize($d['code']) ?>" <?= ($selectedDistrict === $d['code']) ? 'selected' : '' ?>>
                                    <?= sanitize(trim($d['name'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Address</label>
                        <input type="text" name="address" class="filter-input" placeholder="City, Street, Landmark" value="<?= sanitize($member['address'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Pincode</label>
                        <input type="text" name="pincode" class="filter-input" placeholder="Pincode" maxlength="6" value="<?= sanitize($member['pincode'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
                    <i class="fas fa-floppy-disk"></i> Save Profile Details
                </button>
            </form>
        </div>

        <!-- Right Column: Password & Account Info -->
        <div>
            <!-- Change Password Form -->
            <div class="stat-box" style="padding: 1.75rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                    <i class="fas fa-key" style="color: var(--brand-gold-dark);"></i>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin: 0;">Change Password</h3>
                </div>

                <form action="member-profile" method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="filter-group">
                        <label class="filter-label">New Password *</label>
                        <input type="password" name="new_password" class="filter-input" placeholder="••••••••" required minlength="6">
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="filter-input" placeholder="••••••••" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-gold" style="width: 100%; margin-top: 0.5rem;">
                        <i class="fas fa-lock"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- Are you an Advocate Banner -->
            <div class="stat-box" style="padding: 1.5rem; background: #fffdf0; border-left: 4px solid var(--brand-gold);">
                <h4 style="font-size: 1rem; color: var(--brand-gold-text); margin-bottom: 0.5rem;">
                    <i class="fas fa-scale-balanced"></i> Are you a Practicing Advocate?
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    If you are an enrolled advocate, register your statutory Bar Council enrollment to unlock advocate directory features.
                </p>
                <a href="register?type=advocate" class="btn btn-outline-primary btn-sm" style="width: 100%;">
                    <i class="fas fa-user-tie"></i> Register as Advocate
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
