<?php
// register.php - Unified Common Registration Portal for Advocates & Members
require_once __DIR__ . '/config/app.php';

$pageTitle = "Register Account | My Advocate";
$pageDescription = "Create your account on India's premier digital legal directory platform as an Advocate or Member.";

$db = getDB();
$success = false;
$successType = 'advocate';
$error = '';

// If already logged in, redirect
if (!empty($_SESSION['advocate_id'])) {
    header("Location: dashboard");
    exit;
} elseif (!empty($_SESSION['member_id'])) {
    header("Location: member-profile");
    exit;
}

// 1. Fetch State Bar Councils from table bc
$barCouncils = [];
try {
    $stmt = $db->query("SELECT id, bc_id, code, state_code, name FROM bc WHERE status = 'ACTIVE' ORDER BY name ASC");
    $barCouncils = $stmt->fetchAll();
} catch (Exception $e) {
    $barCouncils = [];
}

// 2. Fetch States from table state
$states = getStates();

$userType = sanitize($_POST['user_type'] ?? $_GET['type'] ?? 'advocate');
if (!in_array($userType, ['advocate', 'member', 'user'])) {
    $userType = 'advocate';
}
if ($userType === 'user') {
    $userType = 'member';
}

$selectedState = sanitize($_POST['state_code'] ?? '');
$selectedDistrict = sanitize($_POST['district_code'] ?? '');
$districts = !empty($selectedState) ? getDistrictsByState($selectedState) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = sanitize($_POST['user_type'] ?? 'advocate');
    $name = trim(sanitize($_POST['name'] ?? ''));
    $mobile = preg_replace('/[^0-9]/', '', $_POST['mobile'] ?? '');
    $email = trim(sanitize($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');

    if (empty($name) || empty($mobile) || strlen($mobile) !== 10 || empty($password)) {
        $error = "Please fill in all mandatory fields: Full Name, 10-digit Mobile Number, and Password.";
    } elseif ($userType === 'member' || $userType === 'user') {
        // === GENERAL USER / MEMBER REGISTRATION ===
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $address = sanitize($_POST['address'] ?? '');
        $pincode = sanitize($_POST['pincode'] ?? '');

        try {
            // Check if mobile or email exists in member
            $chk = $db->prepare("SELECT id FROM member WHERE mobile = ? OR (email != '' AND email = ?) LIMIT 1");
            $chk->execute([$mobile, $email]);
            if ($chk->fetch()) {
                $error = "An account with this mobile number or email already exists. Please sign in instead.";
            } else {
                $hash = md5($password);

                // Fetch available columns in member table
                $memberCols = [];
                try {
                    $colStmt = $db->query("DESCRIBE member");
                    $memberCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $ce) {}

                $memberData = [
                    'name'          => $name,
                    'mobile'        => $mobile,
                    'email'         => $email,
                    'password'      => $hash,
                    'gender'        => $gender,
                    'state_code'    => $stateCode,
                    'district_code' => $districtCode,
                    'pincode'       => $pincode,
                    'address'       => $address,
                    'status'        => 'ACTIVE',
                    'mobile_status' => 'VERIFIED',
                    'email_status'  => 'VERIFIED',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'created_by'    => $name
                ];

                if (!empty($memberCols)) {
                    $insertData = array_intersect_key($memberData, array_flip($memberCols));
                } else {
                    $insertData = $memberData;
                }

                $colNames = implode('`, `', array_keys($insertData));
                $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
                $sql = "INSERT INTO `member` (`{$colNames}`) VALUES ({$placeholders})";

                $stmt = $db->prepare($sql);
                $stmt->execute(array_values($insertData));
                $newMemberId = $db->lastInsertId();

                $_SESSION['member_id'] = (int)$newMemberId;
                $_SESSION['member_name'] = $name;
                $_SESSION['user_type'] = 'member';

                $success = true;
                $successType = 'member';
            }
        } catch (Exception $e) {
            $error = "Registration failed: " . htmlspecialchars($e->getMessage());
        }
    } else {
        // === ADVOCATE REGISTRATION ===
        $bcIdSelected = sanitize($_POST['bc_id'] ?? '');
        $eNo = trim(sanitize($_POST['e_no'] ?? ''));
        $eYear = trim(sanitize($_POST['e_year'] ?? ''));
        $court = sanitize($_POST['court'] ?? 'CC');

        // Resolve Bar Council ID
        $bcIdToSave = $bcIdSelected;
        foreach ($barCouncils as $bcItem) {
            if ((string)$bcItem['id'] === $bcIdSelected || (string)$bcItem['bc_id'] === $bcIdSelected || $bcItem['code'] === $bcIdSelected) {
                $bcIdToSave = $bcItem['bc_id'] ?: $bcItem['id'];
                if (empty($stateCode)) {
                    $stateCode = $bcItem['state_code'] ?: $bcItem['code'];
                }
                break;
            }
        }

        if (empty($bcIdSelected) || empty($eNo) || empty($eYear)) {
            $error = "Please provide your State Bar Council, Enrollment Number, and Enrollment Year.";
        } else {
            try {
                // Check if mobile or enrollment already registered
                $chk = $db->prepare("SELECT id FROM advocate WHERE mobile = ? OR (e_no = ? AND e_year = ? AND bc_id = ?) LIMIT 1");
                $chk->execute([$mobile, $eNo, $eYear, $bcIdToSave]);
                if ($chk->fetch()) {
                    $error = "An advocate record with this Mobile Number or Enrollment details already exists. Please Sign In or Claim Profile.";
                } else {
                    $hash = md5($password);
                    $publicUrl = generateAdvocatePublicUrl($name, $stateCode, $districtCode, $db);

                    // Fetch available columns in advocate table
                    $advCols = [];
                    try {
                        $colStmt = $db->query("DESCRIBE advocate");
                        $advCols = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                    } catch (Exception $ce) {}

                    $advData = [
                        'name'          => $name,
                        'mobile'        => $mobile,
                        'email'         => $email,
                        'password'      => $hash,
                        'state_code'    => $stateCode,
                        'district_code' => $districtCode,
                        'bc_id'         => $bcIdToSave,
                        'e_no'          => $eNo,
                        'e_year'        => $eYear,
                        'court'         => $court,
                        'public_url'    => $publicUrl,
                        'type'          => 'ACTIVE',
                        'plan_type'     => 'registered',
                        'status'        => 'ACTIVE',
                        'created_at'    => date('Y-m-d H:i:s')
                    ];

                    if (!empty($advCols)) {
                        $insertData = array_intersect_key($advData, array_flip($advCols));
                    } else {
                        $insertData = $advData;
                    }

                    $colNames = implode('`, `', array_keys($insertData));
                    $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
                    $sql = "INSERT INTO `advocate` (`{$colNames}`) VALUES ({$placeholders})";

                    $stmt = $db->prepare($sql);
                    $stmt->execute(array_values($insertData));
                    $newAdvocateId = $db->lastInsertId();

                    $_SESSION['advocate_id'] = (int)$newAdvocateId;
                    $_SESSION['advocate_name'] = $name;
                    $_SESSION['user_type'] = 'advocate';

                    $success = true;
                    $successType = 'advocate';
                }
            } catch (Exception $e) {
                $error = "Advocate registration failed: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 3.5rem; padding-bottom: 5rem;">
    <div style="max-width: 620px; margin: 0 auto;">
        
        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md);">
            <!-- Header Branding & Title -->
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: #fee2e2; border-radius: 50%; color: var(--brand-red); font-size: 1.5rem; margin-bottom: 0.85rem; border: 1px solid #fecaca;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem;">
                    Create an Account
                </h1>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0;">
                    Join India's verified legal intelligence & directory platform
                </p>
            </div>

            <?php if ($success): ?>
                <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 2rem; border-radius: var(--radius-sm); text-align: center;">
                    <div style="font-size: 2.8rem; margin-bottom: 0.5rem;">🎉</div>
                    <h3 style="margin-bottom: 0.5rem; font-size: 1.4rem; font-weight: 800; color: #15803d;">Registration Successful!</h3>
                    <p style="font-size: 0.9375rem; margin-bottom: 1.5rem; color: #166534;">
                        <?= $successType === 'advocate' ? 'Welcome to My Advocate! Your advocate profile has been registered and activated.' : 'Welcome! Your user account has been successfully created and activated.' ?>
                    </p>
                    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                        <?php if ($successType === 'advocate'): ?>
                            <a href="dashboard" class="btn btn-primary btn-md"><i class="fas fa-gauge"></i> Go to Dashboard</a>
                            <a href="profile" class="btn btn-outline btn-md"><i class="fas fa-id-badge"></i> View My Profile</a>
                        <?php else: ?>
                            <a href="member-profile" class="btn btn-primary btn-md"><i class="fas fa-user-circle"></i> View My Profile</a>
                            <a href="advocate-search-result" class="btn btn-outline btn-md"><i class="fas fa-search"></i> Search Directory</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; padding: 0.75rem 1rem; border-radius: var(--radius-sm); font-size: 0.875rem; margin-bottom: 1.25rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span><?= sanitize($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Common Unified Registration Form -->
                <form action="register" method="POST" id="signup_frm">
                    
                    <!-- 1. I AM SELECTOR (Role Identification) -->
                    <div class="filter-group" style="margin-bottom: 1.25rem;">
                        <label class="filter-label" style="font-weight: 700; color: var(--primary);">
                            <i class="fas fa-user-tag text-primary"></i> I am <span style="color: var(--brand-red);">*</span>
                        </label>
                        <select name="user_type" id="user_type" class="filter-select" required onchange="toggleAdvocateFields(this.value)" style="font-weight: 600;">
                            <option value="advocate" <?= ($userType === 'advocate') ? 'selected' : '' ?>>I am an Advocate</option>
                            <option value="member" <?= ($userType === 'member') ? 'selected' : '' ?>>Not an Advocate (Client / Litigant / Student)</option>
                        </select>
                    </div>

                    <!-- 2. DYNAMIC ADVOCATE FIELDS -->
                    <div id="advocate_fields" style="display: <?= ($userType === 'advocate') ? 'block' : 'none' ?>; background: #fffbeb; border: 1px solid #fde68a; padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; font-weight: 700; color: #92400e;">
                            <i class="fas fa-id-card"></i> State Bar Council &amp; Enrollment Credentials
                        </div>

                        <!-- State Bar Council -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-weight: 600;">State Bar Council <span style="color: var(--brand-red);">*</span></label>
                            <select name="bc_id" id="bc_id" class="filter-select" style="background: #ffffff;">
                                <option value="">-- Select Bar Council --</option>
                                <?php foreach ($barCouncils as $bc): ?>
                                    <option value="<?= sanitize($bc['bc_id'] ?: $bc['id']) ?>" <?= (isset($_POST['bc_id']) && (string)$_POST['bc_id'] === (string)($bc['bc_id'] ?: $bc['id'])) ? 'selected' : '' ?>>
                                        <?= sanitize(trim($bc['name'])) ?> <?= !empty($bc['code']) ? '(' . sanitize($bc['code']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Enrollment Number & Year -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 600;">Enrollment No. <span style="color: var(--brand-red);">*</span></label>
                                <input type="text" name="e_no" id="e_no" class="filter-input" placeholder="e.g. 1266 or BR/1266" value="<?= isset($_POST['e_no']) ? sanitize($_POST['e_no']) : '' ?>" style="background: #ffffff;">
                            </div>
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 600;">Enrollment Year <span style="color: var(--brand-red);">*</span></label>
                                <input type="number" name="e_year" id="e_year" class="filter-input" placeholder="e.g. <?= date('Y') ?>" min="1950" max="<?= date('Y') ?>" value="<?= isset($_POST['e_year']) ? sanitize($_POST['e_year']) : '' ?>" style="background: #ffffff;">
                            </div>
                        </div>

                        <!-- Primary Court Jurisdiction -->
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 600;">Primary Court Jurisdiction</label>
                            <select name="court" class="filter-select" style="background: #ffffff;">
                                <?php foreach (getCourtsList() as $cCode => $cName): ?>
                                    <option value="<?= sanitize($cCode) ?>" <?= (isset($_POST['court']) && $_POST['court'] === $cCode) ? 'selected' : '' ?>><?= sanitize($cName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- 3. COMMON PROFILE FIELDS -->
                    <!-- Full Name -->
                    <div class="filter-group" style="margin-bottom: 1.25rem;">
                        <label class="filter-label" style="font-weight: 600;">
                            Full Name <span style="color: var(--brand-red);">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="text" name="name" class="filter-input" placeholder="e.g. Rajesh Kumar Sharma" required value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>" style="padding-left: 2.5rem;">
                            <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        </div>
                    </div>

                    <!-- Mobile Number & Email -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 600;">
                                Mobile Number <span style="color: var(--brand-red);">*</span>
                            </label>
                            <div style="position: relative;">
                                <input type="tel" name="mobile" class="filter-input" placeholder="10-digit Mobile" pattern="[0-9]{10}" maxlength="10" required value="<?= isset($_POST['mobile']) ? sanitize($_POST['mobile']) : '' ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);" style="padding-left: 2.5rem;">
                                <i class="fas fa-phone" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                            </div>
                        </div>
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 600;">Email Address</label>
                            <div style="position: relative;">
                                <input type="email" name="email" class="filter-input" placeholder="name@example.com" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>" style="padding-left: 2.5rem;">
                                <i class="fas fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="filter-group" style="margin-bottom: 1.25rem;">
                        <label class="filter-label" style="font-weight: 600;">
                            Password <span style="color: var(--brand-red);">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="password" name="password" class="filter-input" placeholder="Create a secure password" required minlength="4" style="padding-left: 2.5rem;">
                            <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                        </div>
                    </div>

                    <!-- State & District -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 600;">State</label>
                            <select name="state_code" id="stateSelect" class="filter-select state-cascade" data-target="#districtSelect">
                                <option value="">-- Select State --</option>
                                <?php foreach ($states as $sCode => $sName): ?>
                                    <option value="<?= sanitize($sCode) ?>" <?= ($selectedState === $sCode) ? 'selected' : '' ?>>
                                        <?= sanitize(trim($sName)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 600;">District</label>
                            <select name="district_code" id="districtSelect" class="filter-select">
                                <option value="">-- Select District --</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?= sanitize($d['code']) ?>" <?= ($selectedDistrict === $d['code']) ? 'selected' : '' ?>>
                                        <?= sanitize(trim($d['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Terms & Conditions Checkbox -->
                    <div style="margin-bottom: 1.5rem; font-size: 0.875rem;">
                        <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer; color: var(--text-muted); margin-bottom: 0;">
                            <input type="checkbox" name="terms" value="1" required checked style="margin-top: 0.2rem; accent-color: var(--brand-red); cursor: pointer;">
                            <span>I agree to the <a href="terms" target="_blank" style="color: var(--brand-red); font-weight: 600; text-decoration: underline;">Terms &amp; Conditions</a> and <a href="disclaimer" target="_blank" style="color: var(--brand-gold-dark); font-weight: 600; text-decoration: underline;">BCI Disclaimer</a></span>
                        </label>
                    </div>

                    <button type="submit" id="signup_btn" class="btn btn-primary btn-lg" style="width: 100%; font-weight: 700; justify-content: center; box-shadow: var(--shadow-sm);">
                        <i class="fas fa-user-check"></i> Complete Registration
                    </button>
                </form>

                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); text-align: center; font-size: 0.875rem; color: var(--text-muted);">
                    Already have an account? 
                    <div style="margin-top: 0.5rem; display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                        <a href="login" style="font-weight: 700; color: var(--brand-red); text-decoration: none;">
                            <i class="fas fa-right-to-bracket"></i> Sign In
                        </a>
                        &bull;
                        <a href="claim-profile" style="font-weight: 700; color: var(--brand-gold-dark); text-decoration: none;">
                            <i class="fas fa-id-badge"></i> Claim Existing Profile
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleAdvocateFields(type) {
    const advFields = document.getElementById('advocate_fields');
    const bcSelect = document.getElementById('bc_id');
    const eNo = document.getElementById('e_no');
    const eYear = document.getElementById('e_year');
    
    if (type === 'advocate') {
        if (advFields) advFields.style.display = 'block';
        if (bcSelect) bcSelect.required = true;
        if (eNo) eNo.required = true;
        if (eYear) eYear.required = true;
    } else {
        if (advFields) advFields.style.display = 'none';
        if (bcSelect) bcSelect.required = false;
        if (eNo) eNo.required = false;
        if (eYear) eYear.required = false;
    }
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
