<?php
// admin/ba.php - Manage Bar Associations Directory
$pageTitle = "Bar Associations Directory";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

$stateFilter = sanitize($_GET['state'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $regNo = sanitize($_POST['reg_no'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE ba SET name = ?, code = ?, state_code = ?, district_code = ?, reg_no = ?, email = ?, tel = ?, address = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $regNo, $email, $tel, $address, $status, $editId]);
                $msg = "Bar Association updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO ba (name, code, state_code, district_code, reg_no, email, tel, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $regNo, $email, $tel, $address, $status]);
                $msg = "New Bar Association created.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving: " . $e->getMessage();
        }
    }
}

// Fetch single for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM ba WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$sql = "SELECT * FROM ba WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM ba WHERE 1=1";
$params = [];

if (!empty($stateFilter)) {
    $sql .= " AND state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $stateFilter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR code LIKE ? OR reg_no LIKE ?)";
    $countSql .= " AND (name LIKE ? OR code LIKE ? OR reg_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

try {
    $cStmt = $db->prepare($countSql);
    $cStmt->execute($params);
    $totalCount = (int)$cStmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = max(1, ceil($totalCount / $perPage));

$sql .= " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $associations = $stmt->fetchAll();
} catch (Exception $e) {
    $associations = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Bar Associations (<?= number_format($totalCount) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            District, Sub-Divisional, High Court and Supreme Court Bar Associations.
        </p>
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

<!-- Search Filter Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="ba.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search Keyword</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="BA Name, Code..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">State Jurisdiction</label>
            <select name="state" class="filter-select">
                <option value="">All States</option>
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $stateFilter === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="ba.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem;" class="ba-grid">
    <style>
        @media(max-width: 992px) {
            .ba-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Table -->
    <div class="admin-card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
        <div class="table-responsive" style="border: none;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Bar Association</th>
                        <th>Code</th>
                        <th>State / District</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($associations)): ?>
                        <?php foreach ($associations as $ba): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($ba['name']) ?></strong>
                                    <?php if ($ba['reg_no']): ?>
                                        <div><small style="color: var(--text-muted);">Reg: <?= sanitize($ba['reg_no']) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= sanitize($ba['code'] ?: '—') ?></code></td>
                                <td>
                                    <div><?= sanitize(getDistrictName($ba['district_code'] ?? '')) ?></div>
                                    <small style="color: var(--text-muted); font-weight: 600;"><?= sanitize($ba['state_code']) ?></small>
                                </td>
                                <td>
                                    <span class="badge-verification <?= ($ba['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                        <?= sanitize($ba['status'] ?: 'ACTIVE') ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="ba.php?action=edit&id=<?= $ba['id'] ?>&page=<?= $page ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">No bar associations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="stat-box" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Bar Association' : '<i class="fas fa-plus"></i> Add Bar Association' ?>
        </h3>

        <form action="ba.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Association Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Chapra Bar Association">
            </div>

            <div class="filter-group">
                <label class="filter-label">Unique Code</label>
                <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" placeholder="e.g. BRSAR01">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">State</label>
                    <select name="state_code" class="filter-select state-cascade" data-target="#districtSelect">
                        <option value="">Select State</option>
                        <?php foreach ($states as $sCode => $sName): ?>
                            <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">District Code</label>
                    <input type="text" name="district_code" class="filter-input" value="<?= sanitize($editItem['district_code'] ?? '') ?>" placeholder="e.g. BRSAR">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Registration No</label>
                <input type="text" name="reg_no" class="filter-input" value="<?= sanitize($editItem['reg_no'] ?? '') ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Update Association' : '<i class="fas fa-plus"></i> Save Association' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="ba.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'ba.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
