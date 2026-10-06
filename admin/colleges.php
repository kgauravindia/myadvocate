<?php
// admin/colleges.php - Manage Law Colleges & Universities
$pageTitle = "Law Colleges & Universities";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Update College
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $univCode = sanitize($_POST['univ_code'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $pincode = sanitize($_POST['pincode'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $year = sanitize($_POST['year'] ?? '');
    $law = sanitize($_POST['law'] ?? 'YES');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE college SET name = ?, univ_code = ?, state_code = ?, district_code = ?, address = ?, pincode = ?, email = ?, url = ?, year = ?, law = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $univCode, $stateCode, $districtCode, $address, $pincode, $email, $url, $year, $law, $status, $editId]);
                $msg = "College updated successfully.";
                $action = 'list';
            } else {
                $stmt = $db->prepare("INSERT INTO college (name, univ_code, state_code, district_code, address, pincode, email, url, year, law, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $univCode, $stateCode, $districtCode, $address, $pincode, $email, $url, $year, $law, $status]);
                $msg = "New College added successfully.";
                $action = 'list';
            }
        } catch (Exception $e) {
            $err = "Error saving college: " . $e->getMessage();
        }
    } else {
        $err = "College Name is required.";
    }
}

// Handle Delete
if ($action === 'delete' && $id) {
    try {
        $del = $db->prepare("DELETE FROM college WHERE id = ?");
        $del->execute([$id]);
        $msg = "College record deleted successfully.";
        $action = 'list';
    } catch (Exception $e) {
        $err = "Error deleting college: " . $e->getMessage();
    }
}

// Fetch single record for editing
$editCollege = null;
if ($action === 'edit' && $id) {
    $st = $db->prepare("SELECT * FROM college WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $editCollege = $st->fetch();
}

// Filters for listing
$q = sanitize($_GET['q'] ?? '');
$filterState = sanitize($_GET['state'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($q) {
    $where[] = "(name LIKE ? OR address LIKE ? OR email LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($filterState) {
    $where[] = "state_code = ?";
    $params[] = $filterState;
}

$whereSql = implode(" AND ", $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM college WHERE $whereSql");
$countStmt->execute($params);
$totalColleges = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalColleges / $perPage);

// List
$listStmt = $db->prepare("SELECT * FROM college WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$listStmt->execute($params);
$colleges = $listStmt->fetchAll();

// Fetch universities for select dropdown
$universities = [];
try {
    $universities = $db->query("SELECT code, name FROM university ORDER BY name ASC LIMIT 200")->fetchAll();
} catch (Exception $e) {}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Law Colleges & Universities</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Manage <?= number_format($totalColleges) ?> educational institutions & bar council recognitions.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="colleges.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add College</a>
        <?php else: ?>
            <a href="colleges.php" class="btn btn-outline btn-sm">Back to Directory</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editCollege)): ?>
    <!-- College Form -->
    <div class="admin-card" style="max-width: 850px; margin-bottom: 2rem; border-top: 4px solid var(--brand-red);">
        <h3 class="admin-card-title" style="margin-bottom: 1.25rem;">
            <?= $editCollege ? 'Edit College: ' . sanitize($editCollege['name']) : 'Add New Law College' ?>
        </h3>

        <form action="colleges.php" method="POST">
            <?php if ($editCollege): ?>
                <input type="hidden" name="id" value="<?= $editCollege['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">College / Institute Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editCollege['name'] ?? '') ?>" required placeholder="e.g. National Law School of India University">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Affiliated University</label>
                    <select name="univ_code" class="filter-select">
                        <option value="">Autonomous / Independent / Select University</option>
                        <?php foreach ($universities as $u): ?>
                            <option value="<?= sanitize($u['code']) ?>" <?= ($editCollege['univ_code'] ?? '') === $u['code'] ? 'selected' : '' ?>><?= sanitize($u['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Year of Establishment</label>
                    <input type="text" name="year" class="filter-input" value="<?= sanitize($editCollege['year'] ?? '') ?>" placeholder="e.g. 1987">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">State</label>
                    <select name="state_code" class="filter-select">
                        <option value="">Select State</option>
                        <?php foreach ($states as $sCode => $sName): ?>
                            <option value="<?= sanitize($sCode) ?>" <?= ($editCollege['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">District Code / City</label>
                    <input type="text" name="district_code" class="filter-input" value="<?= sanitize($editCollege['district_code'] ?? '') ?>" placeholder="e.g. Bengaluru Urban">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Campus Address</label>
                <input type="text" name="address" class="filter-input" value="<?= sanitize($editCollege['address'] ?? '') ?>" placeholder="Complete address">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Email Address</label>
                    <input type="email" name="email" class="filter-input" value="<?= sanitize($editCollege['email'] ?? '') ?>" placeholder="info@college.edu.in">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Website URL</label>
                    <input type="url" name="url" class="filter-input" value="<?= sanitize($editCollege['url'] ?? '') ?>" placeholder="https://college.edu.in">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Pincode</label>
                    <input type="text" name="pincode" class="filter-input" value="<?= sanitize($editCollege['pincode'] ?? '') ?>" placeholder="560072">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Law Program Offered</label>
                    <select name="law" class="filter-select">
                        <option value="YES" <?= ($editCollege['law'] ?? 'YES') === 'YES' ? 'selected' : '' ?>>YES (Bar Council Approved)</option>
                        <option value="NO" <?= ($editCollege['law'] ?? '') === 'NO' ? 'selected' : '' ?>>NO</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="ACTIVE" <?= ($editCollege['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                        <option value="INACTIVE" <?= ($editCollege['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save College Record</button>
        </form>
    </div>
<?php endif; ?>

<!-- Search Filter Bar -->
<div class="admin-card" style="padding: 1.25rem;">
    <form action="colleges.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search Name / City / Email</label>
            <input type="text" name="q" value="<?= sanitize($q) ?>" placeholder="Keywords..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">State Filter</label>
            <select name="state" class="filter-select">
                <option value="">All States</option>
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $filterState === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-search"></i> Search</button>
            <a href="colleges.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<!-- Listing Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>College & Campus</th>
                <th>State & District</th>
                <th>Est. Year</th>
                <th>BCI Law Approved</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($colleges)): ?>
                <?php foreach ($colleges as $col): ?>
                    <tr>
                        <td>#<?= $col['id'] ?></td>
                        <td>
                            <strong><?= sanitize($col['name']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= sanitize($col['address'] ?: '—') ?></small>
                            <?php if ($col['url']): ?>
                                <br><a href="<?= sanitize($col['url']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--brand-red);"><i class="fas fa-link"></i> Website</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?= sanitize($col['district_code'] ?: $col['district'] ?: '—') ?></div>
                            <small style="color: var(--text-muted); font-weight: 700;"><?= sanitize($col['state_code'] ?: $col['state'] ?: '—') ?></small>
                        </td>
                        <td><?= sanitize($col['year'] ?: '—') ?></td>
                        <td>
                            <?php if (strtoupper($col['law'] ?? '') === 'YES'): ?>
                                <span class="badge-verification badge-verified" style="font-size: 0.7rem;"><i class="fas fa-check"></i> Approved</span>
                            <?php else: ?>
                                <span class="badge-verification badge-basic" style="font-size: 0.7rem;">General</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-verification <?= ($col['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'badge-verified' : 'badge-basic' ?>" style="font-size: 0.7rem;">
                                <?= sanitize($col['status'] ?? 'ACTIVE') ?>
                            </span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="colleges.php?action=edit&id=<?= $col['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-pen"></i> Edit</a>
                            <a href="colleges.php?action=delete&id=<?= $col['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #dc2626;" onclick="return confirm('Delete this college entry?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No colleges found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'colleges.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
