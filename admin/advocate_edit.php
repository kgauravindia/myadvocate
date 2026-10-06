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
    $name = sanitize($_POST['name'] ?? '');
    $mobile = sanitize($_POST['mobile'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $eNo = sanitize($_POST['e_no'] ?? '');
    $eYear = sanitize($_POST['e_year'] ?? '');
    $court = sanitize($_POST['court'] ?? 'CC');
    $practiceArea = sanitize($_POST['practice_area'] ?? '');
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
        $up = $db->prepare("UPDATE advocate SET name = ?, mobile = ?, email = ?, state_code = ?, district_code = ?, e_no = ?, e_year = ?, court = ?, practice_area = ?, about = ?, plan_type = ?, type = ?, mobile_visibility = ?, email_visibility = ?, address_visibility = ?, premium_member = ?, public_url = ?, updated_at = NOW() WHERE id = ?");
        $up->execute([$name, $mobile, $email, $stateCode, $districtCode, $eNo, $eYear, $court, $practiceArea, $about, $planType, $statusType, $mobileVis, $emailVis, $addressVis, $premium, $publicUrl, $id]);
        $msg = "Advocate profile updated successfully.";
        
        // Refresh
        $stmt->execute([$id]);
        $adv = $stmt->fetch();
    } catch (Exception $e) {
        $err = "Error saving changes: " . $e->getMessage();
    }
}

$districts = !empty($adv['state_code']) ? getDistrictsByState($adv['state_code']) : [];
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem;">
            <a href="advocates.php">Advocates</a> &bull; <span>Edit Advocate #<?= $adv['id'] ?></span>
        </nav>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;"><?= sanitize($adv['name'] ?: 'Advocate') ?></h1>
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

<form action="advocate_edit.php?id=<?= $adv['id'] ?>" method="POST">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="adv-edit-grid">
        <style>
            @media(max-width: 992px) {
                .adv-edit-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>

        <!-- Left: Core Information -->
        <div class="stat-box" style="padding: 1.5rem;">
            <h3 style="font-size: 1.15rem; margin-bottom: 1.25rem; color: var(--primary);"><i class="fas fa-id-card"></i> Personal & Enrollment Details</h3>

            <div class="filter-group">
                <label class="filter-label">Advocate Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($adv['name']) ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Mobile Number</label>
                    <input type="text" name="mobile" class="filter-input" value="<?= sanitize($adv['mobile']) ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Email Address</label>
                    <input type="email" name="email" class="filter-input" value="<?= sanitize($adv['email']) ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Enrollment Number</label>
                    <input type="text" name="e_no" class="filter-input" value="<?= sanitize($adv['e_no']) ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Enrollment Year</label>
                    <input type="number" name="e_year" class="filter-input" value="<?= sanitize($adv['e_year']) ?>" min="1950" max="<?= date('Y') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">State Bar Council</label>
                    <select name="state_code" class="filter-select state-cascade" data-target="#district_select">
                        <option value="">Select State</option>
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

            <div class="filter-group">
                <label class="filter-label">Court Jurisdiction</label>
                <select name="court" class="filter-select">
                    <?php foreach (getCourtsList() as $cCode => $cName): ?>
                        <option value="<?= sanitize($cCode) ?>" <?= ($adv['court'] ?? '') === $cCode ? 'selected' : '' ?>><?= sanitize($cName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Practice Specializations (comma separated)</label>
                <input type="text" name="practice_area" class="filter-input" value="<?= sanitize($adv['practice_area']) ?>" placeholder="e.g. Civil Law, Criminal Litigation, Property">
            </div>

            <div class="filter-group">
                <label class="filter-label">Professional Bio / About</label>
                <textarea name="about" class="filter-input" rows="4"><?= sanitize($adv['about']) ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Custom Public URL / Slug</label>
                <input type="text" name="public_url" class="filter-input" value="<?= sanitize($adv['public_url'] ?? '') ?>" placeholder="Leave blank to auto-generate (e.g. state/district + 4-char name + 3-char code)">
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
