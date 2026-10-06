<?php
// admin/advocate_add.php - Add New Advocate
$pageTitle = "Add New Advocate";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$msg = '';
$err = '';

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
    $planType = sanitize($_POST['plan_type'] ?? 'basic');
    $publicUrl = sanitize($_POST['public_url'] ?? '');

    if ($name && $eNo && $eYear) {
        try {
            if (empty($publicUrl)) {
                $publicUrl = generateAdvocatePublicUrl($name, $stateCode, $districtCode, $db);
            }
            $stmt = $db->prepare("INSERT INTO advocate (name, mobile, email, state_code, district_code, e_no, e_year, court, practice_area, plan_type, public_url, type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', NOW())");
            $stmt->execute([$name, $mobile, $email, $stateCode, $districtCode, $eNo, $eYear, $court, $practiceArea, $planType, $publicUrl]);
            $newId = $db->lastInsertId();
            header("Location: advocate_edit.php?id=$newId");
            exit;
        } catch (Exception $e) {
            $err = "Failed to insert record: " . $e->getMessage();
        }
    } else {
        $err = "Advocate name, enrollment number, and year are required.";
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem;">
            <a href="advocates.php">Advocates</a> &bull; <span>Add New Record</span>
        </nav>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;">Add New Advocate</h1>
    </div>
    <a href="advocates.php" class="btn btn-outline btn-sm">Back</a>
</div>

<?php if ($err): ?>
    <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
        <i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?>
    </div>
<?php endif; ?>

<div class="stat-box" style="max-width: 800px; padding: 2rem;">
    <form action="advocate_add.php" method="POST">
        <div class="filter-group">
            <label class="filter-label">Full Name *</label>
            <input type="text" name="name" class="filter-input" placeholder="e.g. Adv. Rajesh Sharma" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="filter-group">
                <label class="filter-label">Mobile Number</label>
                <input type="text" name="mobile" class="filter-input" placeholder="10-digit mobile">
            </div>
            <div class="filter-group">
                <label class="filter-label">Email Address</label>
                <input type="email" name="email" class="filter-input" placeholder="advocate@example.com">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="filter-group">
                <label class="filter-label">Enrollment Number *</label>
                <input type="text" name="e_no" class="filter-input" placeholder="e.g. 1266" required>
            </div>
            <div class="filter-group">
                <label class="filter-label">Enrollment Year *</label>
                <input type="number" name="e_year" class="filter-input" placeholder="e.g. 2024" min="1950" max="<?= date('Y') ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="filter-group">
                <label class="filter-label">State Bar Council</label>
                <select name="state_code" class="filter-select state-cascade" data-target="#district_select">
                    <option value="">Select State</option>
                    <?php foreach ($states as $sCode => $sName): ?>
                        <option value="<?= sanitize($sCode) ?>"><?= sanitize($sName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">District</label>
                <select name="district_code" id="district_select" class="filter-select">
                    <option value="">Select State First</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="filter-group">
                <label class="filter-label">Court Jurisdiction</label>
                <select name="court" class="filter-select">
                    <?php foreach (getCourtsList() as $cCode => $cName): ?>
                        <option value="<?= sanitize($cCode) ?>"><?= sanitize($cName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Initial Badge Status</label>
                <select name="plan_type" class="filter-select">
                    <option value="basic">🟢 Basic Profile (Public Record)</option>
                    <option value="registered">🟡 Registered Profile</option>
                    <option value="verified">🔴 Verified Profile</option>
                </select>
            </div>
        </div>

        <div class="filter-group">
            <label class="filter-label">Practice Specializations (comma separated)</label>
            <input type="text" name="practice_area" class="filter-input" placeholder="e.g. Civil Litigation, Criminal Defense">
        </div>

        <div class="filter-group">
            <label class="filter-label">Custom Public URL / Slug (Optional)</label>
            <input type="text" name="public_url" class="filter-input" placeholder="Leave blank to auto-generate (e.g. state/district + name + 3 chars)">
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
            <i class="fas fa-plus"></i> Create Advocate Listing
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
