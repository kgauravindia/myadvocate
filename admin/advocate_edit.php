<?php
// admin/advocate_edit.php - Edit & Verify Advocate Profile
$pageTitle = "Edit Advocate Profile";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$id = sanitize($_GET['id'] ?? '');
$adv = null;
$msg = '';
$err = '';

if ($id && is_numeric($id)) {
    try {
        $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $adv = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$adv) {
    echo "<div class='stat-box'><p>Advocate record not found.</p><a href='advocates.php' class='btn btn-primary btn-sm'>Back to Directory</a></div>";
    require_once __DIR__ . '/includes/admin_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Personal Info
    $name = sanitize($_POST['name'] ?? '');
    $rName = sanitize($_POST['r_name'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'Male');
    $dob = sanitize($_POST['dob'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $altMobile = sanitize($_POST['alt_mobile'] ?? '');

    // Professional Details
    $eNo = sanitize($_POST['e_no'] ?? '');
    $bcId = sanitize($_POST['bc_id'] ?? '');
    $eDate = sanitize($_POST['e_date'] ?? '');
    $eYear = sanitize($_POST['e_year'] ?? '');
    if (empty($eYear) && !empty($eDate)) {
        if (preg_match('/(\d{4})/', $eDate, $ym)) {
            $eYear = $ym[1];
        }
    }
    $practicingCourtsArr = isset($_POST['practicing_courts']) && is_array($_POST['practicing_courts']) ? array_map('sanitize', $_POST['practicing_courts']) : [];
    $otherCourt = sanitize($_POST['other_court'] ?? '');
    if (!empty($otherCourt)) {
        $practicingCourtsArr[] = $otherCourt;
    }
    $practicingCourtsStr = implode(', ', array_filter(array_unique($practicingCourtsArr)));
    $court = sanitize($_POST['court'] ?? ($practicingCourtsArr[0] ?? 'CC'));
    $practiceArea = sanitize($_POST['practice_area'] ?? '');
    $baCode = sanitize($_POST['ba_code'] ?? '');
    $sittingAddress = sanitize($_POST['sitting_address'] ?? '');

    // Address Details
    $address = sanitize($_POST['address'] ?? '');
    $mohallaVillage = sanitize($_POST['mohalla_village'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');

    // Settings & Privacy
    $about = sanitize($_POST['about'] ?? '');
    $planType = sanitize($_POST['plan_type'] ?? 'basic');
    $statusType = sanitize($_POST['type'] ?? 'PENDING');
    $mobileVis = sanitize($_POST['mobile_visibility'] ?? 'REGISTERED');
    $emailVis = sanitize($_POST['email_visibility'] ?? 'REGISTERED');
    $addressVis = sanitize($_POST['address_visibility'] ?? 'PRIVATE');
    $premium = isset($_POST['premium_member']) ? 1 : 0;
    $publicUrl = sanitize($_POST['public_url'] ?? '');

    try {
        if (empty($publicUrl)) {
            $publicUrl = generateAdvocatePublicUrl($name, $stateCode, $districtCode, $db, (int)$id);
        }
        $up = $db->prepare("UPDATE advocate SET 
            name = ?, r_name = ?, gender = ?, dob = ?, email = ?, mobile = ?, alt_mobile = ?,
            e_no = ?, bc_id = ?, e_date = ?, e_year = ?, court = ?, practicing_courts = ?,
            practice_area = ?, ba_code = ?, sitting_address = ?,
            address = ?, mohalla_village = ?, state_code = ?, district_code = ?, pincode = ?,
            about = ?, plan_type = ?, type = ?, mobile_visibility = ?, email_visibility = ?, address_visibility = ?,
            premium_member = ?, public_url = ?, updated_at = NOW() 
            WHERE id = ?");
        $up->execute([
            $name, $rName, $gender, $dob, $email, $mobile, $altMobile,
            $eNo, $bcId, $eDate, $eYear, $court, $practicingCourtsStr,
            $practiceArea, $baCode, $sittingAddress,
            $address, $mohallaVillage, $stateCode, $districtCode, $pincode,
            $about, $planType, $statusType, $mobileVis, $emailVis, $addressVis,
            $premium, $publicUrl, $id
        ]);
        $msg = "Advocate profile updated successfully.";

        // Record update in advocate_data table
        $changedFields = [];
        if (($adv['name'] ?? '') !== $name) $changedFields['name'] = $name;
        if (($adv['r_name'] ?? '') !== $rName) $changedFields['r_name'] = $rName;
        if (($adv['gender'] ?? '') !== $gender) $changedFields['gender'] = $gender;
        if (($adv['dob'] ?? '') !== $dob) $changedFields['dob'] = $dob;
        if (($adv['email'] ?? '') !== $email) $changedFields['email'] = $email;
        if (($adv['mobile'] ?? '') !== $mobile) $changedFields['mobile'] = $mobile;
        if (($adv['alt_mobile'] ?? '') !== $altMobile) $changedFields['alt_mobile'] = $altMobile;
        if (($adv['e_no'] ?? '') !== $eNo) $changedFields['e_no'] = $eNo;
        if (($adv['bc_id'] ?? '') !== $bcId) $changedFields['bc_id'] = $bcId;
        if (($adv['e_date'] ?? '') !== $eDate) $changedFields['e_date'] = $eDate;
        if (($adv['e_year'] ?? '') !== $eYear) $changedFields['e_year'] = $eYear;
        if (($adv['practicing_courts'] ?? '') !== $practicingCourtsStr) $changedFields['practicing_courts'] = $practicingCourtsStr;
        if (($adv['practice_area'] ?? '') !== $practiceArea) $changedFields['practice_area'] = $practiceArea;
        if (($adv['ba_code'] ?? '') !== $baCode) $changedFields['ba_code'] = $baCode;
        if (($adv['sitting_address'] ?? '') !== $sittingAddress) $changedFields['sitting_address'] = $sittingAddress;
        if (($adv['address'] ?? '') !== $address) $changedFields['address'] = $address;
        if (($adv['mohalla_village'] ?? '') !== $mohallaVillage) $changedFields['mohalla_village'] = $mohallaVillage;
        if (($adv['state_code'] ?? '') !== $stateCode) $changedFields['state_code'] = $stateCode;
        if (($adv['district_code'] ?? '') !== $districtCode) $changedFields['district_code'] = $districtCode;
        if (($adv['pincode'] ?? '') !== $pincode) $changedFields['pincode'] = $pincode;
        if (($adv['plan_type'] ?? '') !== $planType) $changedFields['plan_type'] = $planType;
        if (($adv['type'] ?? '') !== $statusType) $changedFields['type'] = $statusType;
        if (empty($changedFields)) $changedFields = ['Profile Details (Saved by Admin)'];
        recordAdvocateProfileUpdate((int)$id, $changedFields, 'admin');

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $pRes = uploadAdvocatePhoto($_FILES['photo'], (int)$id);
            if (!$pRes['success']) {
                $err = ($err ? $err . ' ' : '') . $pRes['error'];
            } else {
                recordAdvocateProfileUpdate((int)$id, ['photo' => 'Uploaded photograph via admin'], 'admin');
            }
        }

        // Handle ID proof upload
        if (!empty($_FILES['id_proof']['name'])) {
            $idRes = uploadAdvocateIdProof($_FILES['id_proof'], (int)$id);
            if (!$idRes['success']) {
                $err = ($err ? $err . ' ' : '') . $idRes['error'];
            } else {
                recordAdvocateProfileUpdate((int)$id, ['id_proof' => 'Uploaded ID proof document via admin'], 'admin');
            }
        }
        
        // Refresh
        $stmt->execute([$id]);
        $adv = $stmt->fetch();
    } catch (Exception $e) {
        $err = "Error saving changes: " . $e->getMessage();
    }
}

$districts = !empty($adv['state_code']) ? getDistrictsByState($adv['state_code']) : [];
$barCouncils = getBarCouncils();
$barAssociations = getBarAssociations($adv['state_code'] ?? '', $adv['district_code'] ?? '');
$advIndex = calculateAdvocateIndex($adv);
$advData = getAdvocateData((int)$adv['id']);

$savedCourts = array_map('trim', explode(',', $adv['practicing_courts'] ?? ''));
if (empty($savedCourts) && !empty($adv['court'])) {
    $savedCourts[] = $adv['court'];
}
$isDistrictCourt = in_array('District Court', $savedCourts) || in_array('CC', $savedCourts) || in_array('District', $savedCourts);
$isHighCourt = in_array('High Court', $savedCourts) || in_array('HC', $savedCourts);
$isSupremeCourt = in_array('Supreme Court', $savedCourts) || in_array('SC', $savedCourts);
$isTribunals = in_array('Tribunals', $savedCourts) || in_array('TR', $savedCourts) || in_array('Tribunal', $savedCourts) || in_array('DRT', $savedCourts) || in_array('NCLT', $savedCourts);

$standardCourts = ['District Court', 'CC', 'District', 'High Court', 'HC', 'Supreme Court', 'SC', 'Tribunals', 'TR', 'Tribunal', 'DRT', 'NCLT'];
$otherCourtsList = array_diff($savedCourts, $standardCourts);
$otherCourtVal = implode(', ', $otherCourtsList);
$practiceAreasList = getAdvocatePracticeAreas();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem;">
            <a href="advocates.php">Advocates</a> &bull; <span>Edit Advocate #<?= $adv['id'] ?></span>
        </nav>
        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;"><?= sanitize($adv['name'] ?: 'Advocate') ?></h1>
            <?= renderAdvocateIndexBadge($advIndex, 'pill') ?>
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="font-weight: 700;">
            <i class="fas fa-right-to-bracket"></i> Login as Advocate
        </a>
        <a href="../<?= getAdvocateUrl($adv) ?>" target="_blank" class="btn btn-outline-gold btn-sm"><i class="fas fa-eye"></i> View Live Profile</a>
        <a href="advocates.php" class="btn btn-outline btn-sm">Back</a>
    </div>
</div>

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

<form action="advocate_edit.php?id=<?= $adv['id'] ?>" method="POST" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="adv-edit-grid">
        <style>
            @media(max-width: 992px) {
                .adv-edit-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>

        <!-- Left: 3 Form Sections -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- SECTION 1: PERSONAL INFORMATION -->
            <div class="stat-box" style="padding: 1.5rem; border-left: 4px solid var(--brand-red);">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--primary);"><i class="fas fa-id-badge" style="color: var(--brand-red);"></i> PERSONAL INFORMATION</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Full Name *</label>
                        <input type="text" name="name" class="filter-input" value="<?= sanitize($adv['name']) ?>" required>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Father’s Name</label>
                        <input type="text" name="r_name" class="filter-input" value="<?= sanitize($adv['r_name'] ?? '') ?>" placeholder="Father's / Relative's Name">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Gender</label>
                        <div style="display: flex; gap: 1rem; align-items: center; padding-top: 0.35rem;">
                            <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.875rem; cursor: pointer;">
                                <input type="radio" name="gender" value="Male" <?= (strtolower($adv['gender'] ?? 'male') === 'male') ? 'checked' : '' ?>> Male
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.875rem; cursor: pointer;">
                                <input type="radio" name="gender" value="Female" <?= (strtolower($adv['gender'] ?? '') === 'female') ? 'checked' : '' ?>> Female
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.875rem; cursor: pointer;">
                                <input type="radio" name="gender" value="Other" <?= (strtolower($adv['gender'] ?? '') === 'other') ? 'checked' : '' ?>> Other
                            </label>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Date of Birth (DD/MM/YYYY)</label>
                        <input type="text" name="dob" class="filter-input" value="<?= sanitize($adv['dob'] ?? '') ?>" placeholder="DD/MM/YYYY">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Email ID</label>
                        <input type="email" name="email" class="filter-input" value="<?= sanitize($adv['email']) ?>" placeholder="advocate@example.com">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Mobile Numbers (Primary &amp; Secondary)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <input type="text" name="mobile" class="filter-input" value="<?= sanitize($adv['mobile']) ?>" placeholder="+91 Primary">
                            <input type="text" name="alt_mobile" class="filter-input" value="<?= sanitize($adv['alt_mobile'] ?? $adv['whatsapp'] ?? '') ?>" placeholder="+91 Secondary">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: PROFESSIONAL DETAILS -->
            <div class="stat-box" style="padding: 1.5rem; border-left: 4px solid var(--brand-gold);">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--primary);"><i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> PROFESSIONAL DETAILS</h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Bar Council Enrollment Number</label>
                        <input type="text" name="e_no" class="filter-input" value="<?= sanitize($adv['e_no']) ?>" placeholder="e.g. BR/1234/2018">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">State Bar Council Name</label>
                        <select name="bc_id" class="filter-select">
                            <option value="">Select State Bar Council</option>
                            <?php foreach ($barCouncils as $bc): ?>
                                <option value="<?= sanitize($bc['id']) ?>" <?= ((string)($adv['bc_id'] ?? '') === (string)$bc['id'] || (!empty($adv['state_code']) && $adv['state_code'] === $bc['state_code'] && empty($adv['bc_id']))) ? 'selected' : '' ?>>
                                    <?= sanitize($bc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Date of Enrollment (DD/MM/YYYY) &amp; Year</label>
                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.5rem;">
                            <input type="text" name="e_date" class="filter-input" value="<?= sanitize($adv['e_date'] ?? '') ?>" placeholder="DD/MM/YYYY">
                            <input type="number" name="e_year" class="filter-input" value="<?= sanitize($adv['e_year']) ?>" min="1950" max="<?= date('Y') ?>" placeholder="Year">
                        </div>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Name of Associations</label>
                        <select name="ba_code" class="filter-select">
                            <option value="">Select Bar Association</option>
                            <?php foreach ($barAssociations as $ba): ?>
                                <option value="<?= sanitize($ba['code']) ?>" <?= ($adv['ba_code'] ?? '') === $ba['code'] ? 'selected' : '' ?>>
                                    <?= sanitize($ba['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Practicing Courts</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.5rem; background: #f9fafb; padding: 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 0.5rem;">
                        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="practicing_courts[]" value="District Court" <?= $isDistrictCourt ? 'checked' : '' ?>> District Court
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="practicing_courts[]" value="High Court" <?= $isHighCourt ? 'checked' : '' ?>> High Court
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="practicing_courts[]" value="Supreme Court" <?= $isSupremeCourt ? 'checked' : '' ?>> Supreme Court
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; cursor: pointer;">
                            <input type="checkbox" name="practicing_courts[]" value="Tribunals" <?= $isTribunals ? 'checked' : '' ?>> Tribunals
                        </label>
                    </div>
                    <input type="text" name="other_court" class="filter-input" value="<?= sanitize($otherCourtVal) ?>" placeholder="Other Courts (e.g. NGT, DRT, Family Court)">
                </div>

                <div class="filter-group">
                    <label class="filter-label" style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Specialization (Optional - Powered by advocate_config)</span>
                        <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal;">Click tags below or type to auto-complete</span>
                    </label>
                    <input type="text" name="practice_area" id="adminPracticeAreaInput" list="adminPracticeAreasDatalist" class="filter-input" value="<?= sanitize($adv['practice_area']) ?>" placeholder="e.g. Civil Law, Criminal Defense, Property Disputes">
                    <datalist id="adminPracticeAreasDatalist">
                        <?php foreach ($practiceAreasList as $pa): ?>
                            <option value="<?= sanitize($pa) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <!-- Quick Select Chips from advocate_config -->
                    <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.35rem; max-height: 80px; overflow-y: auto; padding: 0.25rem 0;">
                        <?php 
                        $popularAreas = array_slice($practiceAreasList, 0, 18);
                        foreach ($popularAreas as $pa): 
                        ?>
                            <button type="button" class="btn btn-outline btn-sm" onclick="addAdminPracticeArea('<?= addslashes(sanitize($pa)) ?>')" style="padding: 0.15rem 0.5rem; font-size: 0.72rem; border-radius: 9999px; background: #f8fafc; border-color: #cbd5e1; color: #475569;">
                                + <?= sanitize($pa) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <script>
                    function addAdminPracticeArea(areaName) {
                        var input = document.getElementById('adminPracticeAreaInput');
                        if (!input) return;
                        var current = input.value.trim();
                        if (!current) {
                            input.value = areaName;
                        } else {
                            var parts = current.split(',').map(function(s) { return s.trim(); });
                            if (parts.indexOf(areaName) === -1) {
                                input.value = current + ', ' + areaName;
                            }
                        }
                    }
                </script>

                <div class="filter-group">
                    <label class="filter-label">Sitting Place Address</label>
                    <input type="text" name="sitting_address" class="filter-input" value="<?= sanitize($adv['sitting_address'] ?? '') ?>" placeholder="e.g. Chamber No. 42, Civil Court Complex / Lawyers Block">
                </div>
            </div>

            <!-- SECTION 3: ADDRESS DETAILS (Home/Office) -->
            <div class="stat-box" style="padding: 1.5rem; border-left: 4px solid #3b82f6;">
                <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--primary);"><i class="fas fa-location-dot" style="color: #2563eb;"></i> ADDRESS DETAILS (Home/Office)</h3>

                <div class="filter-group">
                    <label class="filter-label">Address</label>
                    <textarea name="address" class="filter-input" rows="2" placeholder="Full Street Address, House/Office Number..."><?= sanitize($adv['address'] ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Mohalla / Village</label>
                        <input type="text" name="mohalla_village" class="filter-input" value="<?= sanitize($adv['mohalla_village'] ?? '') ?>" placeholder="Mohalla / Village Name">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Pin Code</label>
                        <input type="text" name="pincode" class="filter-input" value="<?= sanitize($adv['pincode'] ?? '') ?>" maxlength="6" placeholder="6-digit PIN code">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">State / UT</label>
                        <select name="state_code" class="filter-select state-cascade" data-target="#district_select">
                            <option value="">Select State / UT</option>
                            <?php foreach ($states as $sCode => $sName): ?>
                                <option value="<?= sanitize($sCode) ?>" <?= ($adv['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">District</label>
                        <select name="district_code" id="district_select" class="filter-select">
                            <option value="">All Districts</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= sanitize($d['code']) ?>" <?= ($adv['district_code'] ?? '') === $d['code'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Professional Bio & Custom URL -->
            <div class="stat-box" style="padding: 1.5rem;">
                <div class="filter-group">
                    <label class="filter-label">Professional Summary / Chambers Bio</label>
                    <textarea name="about" class="filter-input" rows="4"><?= sanitize($adv['about']) ?></textarea>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Custom Public URL / Handle</label>
                    <input type="text" name="public_url" class="filter-input" value="<?= sanitize($adv['public_url'] ?? '') ?>" placeholder="Leave blank to auto-generate">
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save All Changes</button>
            </div>
        </div>

        <!-- Right: Verification & Privacy Settings -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Verification & Badge Controls -->
            <div class="stat-box" style="border-top: 4px solid var(--brand-red);">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-certificate" style="color: var(--brand-red);"></i> Verification & Plan</h3>

                <div class="filter-group">
                    <label class="filter-label">Badge Plan Type</label>
                    <select name="plan_type" class="filter-select">
                        <option value="basic" <?= ($adv['plan_type'] ?? '') === 'basic' ? 'selected' : '' ?>>🟢 Basic Profile (Public Record)</option>
                        <option value="registered" <?= ($adv['plan_type'] ?? '') === 'registered' ? 'selected' : '' ?>>🟡 Registered Profile (Claimed)</option>
                        <option value="verified" <?= ($adv['plan_type'] ?? '') === 'verified' ? 'selected' : '' ?>>🔴 Verified Information (Bar Council Auth)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Account Status</label>
                    <select name="type" class="filter-select">
                        <option value="PENDING" <?= ($adv['type'] ?? '') === 'PENDING' ? 'selected' : '' ?>>PENDING (Unclaimed)</option>
                        <option value="ACTIVE" <?= ($adv['type'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE (Claimed / Managed)</option>
                    </select>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                    <input type="checkbox" name="premium_member" id="premium_member" value="1" <?= ($adv['premium_member'] ?? 0) == 1 ? 'checked' : '' ?>>
                    <label for="premium_member" style="font-size: 0.875rem; font-weight: 600; cursor: pointer;">Mark as Featured / Priority Advocate</label>
                </div>
            </div>

            <!-- Photo & ID Proof Media -->
            <div class="stat-box" style="border-top: 4px solid var(--brand-gold);">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-camera"></i> Profile Photo &amp; ID Proof</h3>

                <!-- Photo Preview & Upload -->
                <div style="margin-bottom: 1.25rem;">
                    <label class="filter-label" style="font-weight: 600;">Advocate Photograph</label>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <div style="width: 52px; height: 52px; border-radius: 50%; overflow: hidden; background: #f3f4f6; border: 2px solid var(--brand-gold); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: 700; color: var(--brand-red); flex-shrink: 0;">
                            <?php 
                            $adminPhotoUrl = getAdvocatePhotoUrl($adv['photo'] ?? '');
                            if ($adminPhotoUrl): 
                            ?>
                                <img src="../<?= sanitize($adminPhotoUrl) ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?= strtoupper(substr(trim($adv['name'] ?: 'A'), 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); word-break: break-all;">
                            <?= !empty($adv['photo']) ? '<code>' . sanitize($adv['photo']) . '</code>' : 'No photo uploaded' ?>
                        </div>
                    </div>
                    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="filter-input" style="padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
                    <small style="color: var(--text-muted); font-size: 0.7rem;">JPG, PNG, WEBP (Max 50 KB)</small>
                </div>

                <!-- ID Proof Preview & Upload -->
                <div>
                    <label class="filter-label" style="font-weight: 600;">ID Proof / Certificate</label>
                    <?php 
                    $adminIdUrl = getAdvocateIdProofUrl($adv['id_proof'] ?? '');
                    if ($adminIdUrl): 
                    ?>
                        <div style="margin-bottom: 0.5rem;">
                            <a href="../<?= sanitize($adminIdUrl) ?>" target="_blank" class="btn btn-outline-success btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                <i class="fas fa-file-lines"></i> View Document (<?= sanitize($adv['id_proof']) ?>)
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                            <i class="fas fa-clock"></i> No ID proof document on file
                        </div>
                    <?php endif; ?>
                    <input type="file" name="id_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" class="filter-input" style="padding: 0.35rem 0.5rem; font-size: 0.8125rem;">
                    <small style="color: var(--text-muted); font-size: 0.7rem;">JPG, PNG, PDF (Max 100 KB)</small>
                </div>
            </div>

            <!-- Privacy Controls -->
            <div class="stat-box">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-lock"></i> Privacy Visibility Controls</h3>

                <div class="filter-group">
                    <label class="filter-label">Mobile Visibility</label>
                    <select name="mobile_visibility" class="filter-select">
                        <option value="PUBLIC" <?= ($adv['mobile_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                        <option value="REGISTERED" <?= ($adv['mobile_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Masked / Registered Users Only</option>
                        <option value="PRIVATE" <?= ($adv['mobile_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden / Confidential</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Email Visibility</label>
                    <select name="email_visibility" class="filter-select">
                        <option value="PUBLIC" <?= ($adv['email_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                        <option value="REGISTERED" <?= ($adv['email_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Masked / Registered Users Only</option>
                        <option value="PRIVATE" <?= ($adv['email_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden / Confidential</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Address Visibility</label>
                    <select name="address_visibility" class="filter-select">
                        <option value="PUBLIC" <?= ($adv['address_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                        <option value="PRIVATE" <?= ($adv['address_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Private</option>
                    </select>
                </div>
            </div>

            <!-- Advocate Tracking & Activity Card (advocate_data) -->
            <div class="stat-box" style="border-top: 4px solid var(--primary);">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-chart-line text-primary"></i> Activity &amp; Update History
                </h3>

                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.85rem;">
                    <div>
                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block;">Last Seen From</span>
                        <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem;">
                            <?php 
                            $seenFrom = strtolower($advData['last_seen_from'] ?? 'web');
                            ?>
                            <span class="badge" style="background: <?= $seenFrom === 'app' ? '#8b5cf6' : '#0284c7' ?>; color: #fff; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 9999px;">
                                <i class="fas <?= $seenFrom === 'app' ? 'fa-mobile-screen' : 'fa-globe' ?> me-1"></i> <?= strtoupper($seenFrom) ?>
                            </span>
                            <span style="color: var(--text-muted); font-size: 0.78rem;">
                                <?= !empty($advData['last_seen_at']) ? date('d M Y, h:i A', strtotime($advData['last_seen_at'])) : 'Not logged yet' ?>
                            </span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm);">
                        <div>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; display: block;">Impression / Views</span>
                            <strong style="font-size: 1.05rem; color: var(--primary);"><?= number_format((int)($advData['seen_count'] ?? 0)) ?></strong>
                        </div>
                        <div>
                            <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; display: block;">Last Profile Update</span>
                            <span style="font-size: 0.78rem; font-weight: 600; color: #334155;">
                                <?= !empty($advData['last_updated_at']) ? date('d M Y', strtotime($advData['last_updated_at'])) : 'No recent updates' ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($advData['last_update_summary'])): ?>
                        <div>
                            <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); display: block;">Latest Update Summary</span>
                            <div style="font-size: 0.78rem; background: #eff6ff; border-left: 3px solid #3b82f6; padding: 0.4rem 0.6rem; color: #1e40af; border-radius: 0 4px 4px 0; margin-top: 0.2rem;">
                                <?= sanitize($advData['last_update_summary']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php 
                    $updateHistory = !empty($advData['update_history']) ? json_decode($advData['update_history'], true) : [];
                    if (!empty($updateHistory) && is_array($updateHistory)):
                    ?>
                        <details style="margin-top: 0.25rem;">
                            <summary style="font-size: 0.78rem; font-weight: 700; color: var(--brand-red); cursor: pointer;">
                                View Detailed Update History (<?= count($updateHistory) ?>)
                            </summary>
                            <div style="margin-top: 0.5rem; display: flex; flex-direction: column; gap: 0.4rem; max-height: 180px; overflow-y: auto;">
                                <?php foreach ($updateHistory as $h): ?>
                                    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 0.35rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.73rem;">
                                        <div style="display: flex; justify-content: space-between; color: var(--text-muted); margin-bottom: 0.15rem;">
                                            <span><strong><?= strtoupper($h['updated_by'] ?? 'User') ?></strong> (<?= strtoupper($h['platform'] ?? 'WEB') ?>)</span>
                                            <span><?= date('d M, h:i A', strtotime($h['timestamp'] ?? 'now')) ?></span>
                                        </div>
                                        <div style="color: #334155; font-weight: 500;">
                                            <?= sanitize($h['summary'] ?? '') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
