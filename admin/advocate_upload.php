<?php
// admin/advocate_upload.php - Bulk Advocate Records CSV Importer
$pageTitle = "Bulk Upload Advocates";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$importedCount = 0;
$skippedCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            if (($handle = fopen($file['tmp_name'], "r")) !== false) {
                // Read header row
                $header = fgetcsv($handle, 2000, ",");
                $header = array_map(function($h) {
                    return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', $h)));
                }, $header);

                $insStmt = $db->prepare("INSERT INTO advocate (name, mobile, email, state_code, district_code, e_no, e_year, court, practice_area, plan_type, public_url, type, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', 'ACTIVE', NOW())");

                $nameIdx = array_search('name', $header);
                $mobileIdx = array_search('mobile', $header);
                $emailIdx = array_search('email', $header);
                $stateIdx = array_search('state_code', $header) !== false ? array_search('state_code', $header) : array_search('state', $header);
                $distIdx = array_search('district_code', $header) !== false ? array_search('district_code', $header) : array_search('district', $header);
                $enoIdx = array_search('e_no', $header) !== false ? array_search('e_no', $header) : array_search('enrollment_no', $header);
                $eyearIdx = array_search('e_year', $header) !== false ? array_search('e_year', $header) : array_search('enrollment_year', $header);
                $courtIdx = array_search('court', $header);
                $practiceIdx = array_search('practice_area', $header);
                $planIdx = array_search('plan_type', $header);
                $publicUrlIdx = array_search('public_url', $header) !== false ? array_search('public_url', $header) : array_search('slug', $header);

                while (($data = fgetcsv($handle, 2000, ",")) !== false) {
                    $name = ($nameIdx !== false && isset($data[$nameIdx])) ? trim($data[$nameIdx]) : '';
                    $mobile = ($mobileIdx !== false && isset($data[$mobileIdx])) ? trim($data[$mobileIdx]) : '';
                    $email = ($emailIdx !== false && isset($data[$emailIdx])) ? trim($data[$emailIdx]) : '';
                    $stateCode = ($stateIdx !== false && isset($data[$stateIdx])) ? trim($data[$stateIdx]) : 'BR';
                    $distCode = ($distIdx !== false && isset($data[$distIdx])) ? trim($data[$distIdx]) : '';
                    $eNo = ($enoIdx !== false && isset($data[$enoIdx])) ? trim($data[$enoIdx]) : '';
                    $eYear = ($eyearIdx !== false && isset($data[$eyearIdx])) ? trim($data[$eyearIdx]) : date('Y');
                    $court = ($courtIdx !== false && isset($data[$courtIdx])) ? trim($data[$courtIdx]) : 'CC';
                    $practice = ($practiceIdx !== false && isset($data[$practiceIdx])) ? trim($data[$practiceIdx]) : 'General Practice';
                    $plan = ($planIdx !== false && isset($data[$planIdx])) ? trim($data[$planIdx]) : 'basic';
                    $publicUrl = ($publicUrlIdx !== false && isset($data[$publicUrlIdx])) ? trim($data[$publicUrlIdx]) : '';

                    if (!empty($name) && (!empty($eNo) || !empty($mobile))) {
                        try {
                            if (empty($publicUrl)) {
                                $publicUrl = generateAdvocatePublicUrl($name, $stateCode, $distCode, $db);
                            }
                            $insStmt->execute([$name, $mobile, $email, $stateCode, $distCode, $eNo, $eYear, $court, $practice, $plan, $publicUrl]);
                            $importedCount++;
                        } catch (Exception $e) {
                            $skippedCount++;
                        }
                    } else {
                        $skippedCount++;
                    }
                }
                fclose($handle);
                $msg = "CSV Import Completed: Successfully imported $importedCount records ($skippedCount rows skipped).";
            } else {
                $err = "Could not open uploaded CSV file.";
            }
        } else {
            $err = "Invalid file type. Please upload a valid .csv file.";
        }
    } else {
        $err = "File upload failed. Error code: " . $file['error'];
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem;">
            <a href="advocates.php">Advocates</a> &bull; <span>Bulk Import</span>
        </nav>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Bulk CSV Upload &amp; Importer
        </h1>
    </div>
    <a href="advocates.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Directory</a>
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="upload-grid">
    <style>
        @media(max-width: 992px) {
            .upload-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Left: Upload Form -->
    <div class="stat-box" style="padding: 2rem;">
        <h3 style="font-size: 1.2rem; color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-file-csv" style="color: var(--brand-red);"></i> Select CSV File to Import</h3>
        
        <form action="advocate_upload.php" method="POST" enctype="multipart/form-data">
            <div class="filter-group" style="margin-bottom: 1.5rem;">
                <label class="filter-label">Choose CSV Document (.csv format only)</label>
                <input type="file" name="csv_file" accept=".csv" class="filter-input" required style="padding: 0.5rem; height: auto;">
            </div>

            <button type="submit" class="btn btn-primary" style="height: 44px; padding: 0 2rem; font-weight: 700;">
                <i class="fas fa-upload"></i> Upload &amp; Process CSV
            </button>
        </form>
    </div>

    <!-- Right: Format Guide & Sample CSV -->
    <div class="stat-box" style="padding: 1.5rem; background: var(--bg-alt);">
        <h4 style="font-size: 1rem; color: var(--primary); margin-bottom: 0.75rem;"><i class="fas fa-circle-info"></i> CSV Format Guidelines</h4>
        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1rem;">
            Your CSV file must include a header row with standard column titles:
        </p>
        <ul style="font-size: 0.8125rem; color: var(--text-main); padding-left: 1.25rem; line-height: 1.6; margin-bottom: 1rem;">
            <li><code>name</code> (Advocate Full Name)</li>
            <li><code>mobile</code> (10-digit mobile number)</li>
            <li><code>email</code> (Contact Email)</li>
            <li><code>state_code</code> (e.g. DL, UP, BR, MH)</li>
            <li><code>district_code</code> (e.g. BRSAR, DL001)</li>
            <li><code>e_no</code> (Enrollment Number)</li>
            <li><code>e_year</code> (Enrollment Year e.g. 2024)</li>
            <li><code>court</code> (Court Code e.g. HC, CC, SC)</li>
        </ul>
        <a href="data:text/csv;charset=utf-8,name,mobile,email,state_code,district_code,e_no,e_year,court,practice_area%0ARajesh%20Sharma,9876543210,adv.rajesh@example.com,BR,BRSAR,1234,2022,CC,Civil%20Law" download="sample_advocate_import.csv" class="btn btn-outline-primary btn-sm" style="width: 100%; text-align: center; justify-content: center;">
            <i class="fas fa-download"></i> Download Sample CSV
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
