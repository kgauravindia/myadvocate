<?php
// admin/district.php - Manage District Directory of India
$pageTitle = "Districts Directory";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

$stateFilter = sanitize($_GET['state'] ?? 'BR');
$search = sanitize($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name && $code && $stateCode) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE district SET name = ?, code = ?, state_code = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $stateCode, $status, $editId]);
                $msg = "District updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO district (name, code, state_code, status, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $stateCode, $status]);
                $msg = "New District added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM district WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$sql = "SELECT d.*, (SELECT COUNT(*) FROM advocate WHERE district_code = d.code) as advocates_count FROM district d WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM district WHERE 1=1";
$params = [];

if (!empty($stateFilter)) {
    $sql .= " AND d.state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $stateFilter;
}

if (!empty($search)) {
    $sql .= " AND (d.name LIKE ? OR d.code LIKE ?)";
    $countSql .= " AND (name LIKE ? OR code LIKE ?)";
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

$sql .= " ORDER BY d.name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $districts = $stmt->fetchAll();
} catch (Exception $e) {
    $districts = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Districts Directory (<?= number_format($totalCount) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage 699+ districts, jurisdiction boundaries, and advocate counts.
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
    <form action="district.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search District</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="District Name or Code..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">Filter by State</label>
            <select name="state" class="filter-select">
                <option value="">All States</option>
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $stateFilter === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="district.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="dist-grid">
    <style>
        @media(max-width: 992px) {
            .dist-grid {
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
                        <th style="width: 100px;">Code</th>
                        <th>District Name</th>
                        <th>State</th>
                        <th style="text-align: right;">Advocates</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($districts)): ?>
                        <?php foreach ($districts as $d): ?>
                            <tr>
                                <td><code><?= sanitize($d['code']) ?></code></td>
                                <td><strong><?= sanitize($d['name']) ?></strong></td>
                                <td><?= sanitize(getStateName($d['state_code'])) ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--brand-red);">
                                    <?= number_format($d['advocates_count']) ?>
                                </td>
                                <td>
                                    <span class="badge-verification <?= ($d['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                        <?= sanitize($d['status'] ?: 'ACTIVE') ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="district.php?action=edit&id=<?= $d['id'] ?>&state=<?= urlencode($stateFilter) ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">No districts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="stat-box" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit District' : '<i class="fas fa-plus"></i> Add District' ?>
        </h3>

        <form action="district.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">District Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Saran">
            </div>

            <div class="filter-group">
                <label class="filter-label">District Code (e.g. BRSAR) *</label>
                <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" required placeholder="e.g. BRSAR">
            </div>

            <div class="filter-group">
                <label class="filter-label">State Jurisdiction *</label>
                <select name="state_code" class="filter-select" required>
                    <option value="">Select State</option>
                    <?php foreach ($states as $sCode => $sName): ?>
                        <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? $stateFilter) === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save District' : '<i class="fas fa-plus"></i> Add District' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="district.php?state=<?= urlencode($stateFilter) ?>" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'district.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
