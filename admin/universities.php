<?php
// admin/universities.php - Manage Universities Directory of India
$pageTitle = "Universities Directory";
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
    $type = sanitize($_POST['type'] ?? 'State University');
    $url = sanitize($_POST['url'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $year = sanitize($_POST['year'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE university SET name = ?, code = ?, state_code = ?, district_code = ?, type = ?, url = ?, email = ?, year = ?, address = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $type, $url, $email, $year, $address, $status, $editId]);
                $msg = "University updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO university (name, code, state_code, district_code, type, url, email, year, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $type, $url, $email, $year, $address, $status]);
                $msg = "New University added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving university: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM university WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$sql = "SELECT * FROM university WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM university WHERE 1=1";
$params = [];

if (!empty($stateFilter)) {
    $sql .= " AND state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $stateFilter;
}

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR code LIKE ?)";
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

$sql .= " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $universities = $stmt->fetchAll();
} catch (Exception $e) {
    $universities = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Universities Directory (<?= number_format($totalCount) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Central, State, Deemed, Private, and National Law Universities across India.
        </p>
    </div>
    <a href="colleges.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-graduation-cap"></i> Law Colleges &rarr;</a>
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
    <form action="universities.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search University</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="University Name or Code..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">State</label>
            <select name="state" class="filter-select">
                <option value="">All States</option>
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $stateFilter === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="universities.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem;" class="univ-grid">
    <style>
        @media(max-width: 992px) {
            .univ-grid {
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
                        <th>University Name</th>
                        <th>Type</th>
                        <th>State</th>
                        <th>Est.</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($universities)): ?>
                        <?php foreach ($universities as $u): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($u['name']) ?></strong>
                                    <?php if ($u['url']): ?>
                                        <div><a href="<?= sanitize($u['url']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--brand-red);"><i class="fas fa-external-link-alt"></i> Website</a></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="practice-pill" style="font-size: 0.7rem;"><?= sanitize($u['type'] ?: 'University') ?></span></td>
                                <td><?= sanitize(getStateName($u['state_code'])) ?></td>
                                <td><?= sanitize($u['year'] ?: '—') ?></td>
                                <td style="text-align: right;">
                                    <a href="universities.php?action=edit&id=<?= $u['id'] ?>&page=<?= $page ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">No universities found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="stat-box" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit University' : '<i class="fas fa-plus"></i> Add University' ?>
        </h3>

        <form action="universities.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">University Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. National Law School of India University">
            </div>

            <div class="filter-group">
                <label class="filter-label">Institution Type</label>
                <select name="type" class="filter-select">
                    <option value="National Law University" <?= ($editItem['type'] ?? '') === 'National Law University' ? 'selected' : '' ?>>National Law University (NLU)</option>
                    <option value="Central University" <?= ($editItem['type'] ?? '') === 'Central University' ? 'selected' : '' ?>>Central University</option>
                    <option value="State University" <?= ($editItem['type'] ?? '') === 'State University' ? 'selected' : '' ?>>State University</option>
                    <option value="Deemed University" <?= ($editItem['type'] ?? '') === 'Deemed University' ? 'selected' : '' ?>>Deemed University</option>
                    <option value="Private University" <?= ($editItem['type'] ?? '') === 'Private University' ? 'selected' : '' ?>>Private University</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">State</label>
                    <select name="state_code" class="filter-select">
                        <option value="">Select State</option>
                        <?php foreach ($states as $sCode => $sName): ?>
                            <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Est. Year</label>
                    <input type="number" name="year" class="filter-input" value="<?= sanitize($editItem['year'] ?? '') ?>" placeholder="e.g. 1987">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Website URL</label>
                <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>" placeholder="https://...">
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save University' : '<i class="fas fa-plus"></i> Add University' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="universities.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'universities.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
