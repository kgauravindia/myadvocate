<?php
// admin/hc.php - Manage High Courts of India
$pageTitle = "High Courts Directory";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $judge = sanitize($_POST['judge'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $year = sanitize($_POST['year'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE hc SET name = ?, state_code = ?, district_code = ?, judge = ?, tel = ?, email = ?, url = ?, year = ?, address = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $stateCode, $districtCode, $judge, $tel, $email, $url, $year, $address, $status, $editId]);
                $msg = "High Court record updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO hc (name, state_code, district_code, judge, tel, email, url, year, address, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $stateCode, $districtCode, $judge, $tel, $email, $url, $year, $address, $status]);
                $msg = "New High Court added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error: " . $e->getMessage();
        }
    }
}

// Fetch single for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM hc WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$highCourts = $db->query("SELECT * FROM hc ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            High Courts of India (<?= count($highCourts) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage all 25 High Courts, Chief Justices, sanctioned judge strengths, and portals.
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="hc-grid">
    <style>
        @media(max-width: 992px) {
            .hc-grid {
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
                        <th>High Court</th>
                        <th>Established</th>
                        <th>Sanctioned Judges</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($highCourts as $hc): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($hc['name']) ?></strong>
                                <?php if ($hc['url']): ?>
                                    <div><a href="<?= sanitize($hc['url']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--brand-red);"><i class="fas fa-external-link-alt"></i> Official Portal</a></div>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($hc['year'] ?: '—') ?></td>
                            <td>
                                <span style="font-weight: 700; color: var(--primary);"><?= sanitize($hc['judge'] ?: '—') ?></span> Judges
                            </td>
                            <td>
                                <span class="badge-verification <?= ($hc['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($hc['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="hc.php?action=edit&id=<?= $hc['id'] ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="stat-box" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit High Court' : '<i class="fas fa-plus"></i> Add High Court' ?>
        </h3>

        <form action="hc.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">High Court Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. High Court of Judicature at Patna">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">State Code</label>
                    <input type="text" name="state_code" class="filter-input" value="<?= sanitize($editItem['state_code'] ?? '') ?>" placeholder="e.g. BR">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Est. Year</label>
                    <input type="number" name="year" class="filter-input" value="<?= sanitize($editItem['year'] ?? '') ?>" placeholder="e.g. 1916">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Judge Strength</label>
                <input type="number" name="judge" class="filter-input" value="<?= sanitize($editItem['judge'] ?? '') ?>" placeholder="e.g. 53">
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Portal URL</label>
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
                <?= $editItem ? '<i class="fas fa-save"></i> Save High Court' : '<i class="fas fa-plus"></i> Add High Court' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="hc.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
