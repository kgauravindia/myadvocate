<?php
// admin/psc.php - Manage Public Service Commissions of India
$pageTitle = "Public Service Commissions";
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
    $code = sanitize($_POST['code'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $year = sanitize($_POST['year'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE psc SET name = ?, code = ?, state_code = ?, district_code = ?, url = ?, email = ?, tel = ?, address = ?, year = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $url, $email, $tel, $address, $year, $status, $editId]);
                $msg = "PSC record updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO psc (name, code, state_code, district_code, url, email, tel, address, year, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $url, $email, $tel, $address, $year, $status]);
                $msg = "New PSC added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving PSC: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM psc WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$pscs = $db->query("SELECT * FROM psc ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Public Service Commissions (<?= count($pscs) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            UPSC, BPSC, UPPSC, JPSC and all 31 State Public Service Commission judicial portals.
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="psc-grid">
    <style>
        @media(max-width: 992px) {
            .psc-grid {
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
                        <th>Commission Name</th>
                        <th>Code / State</th>
                        <th>Official Portal</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pscs as $p): ?>
                        <tr>
                            <td><strong><?= sanitize($p['name']) ?></strong></td>
                            <td>
                                <code><?= sanitize($p['code'] ?: '—') ?></code>
                                <div><small style="color: var(--text-muted);"><?= sanitize(getStateName($p['state_code'])) ?></small></div>
                            </td>
                            <td>
                                <?php if ($p['url']): ?>
                                    <a href="<?= sanitize($p['url']) ?>" target="_blank" style="font-size: 0.8125rem; color: var(--brand-red); font-weight: 600;">
                                        <i class="fas fa-external-link-alt"></i> Official Site
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-verification <?= ($p['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($p['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="psc.php?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">
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
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Commission' : '<i class="fas fa-plus"></i> Add Commission' ?>
        </h3>

        <form action="psc.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Commission Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Bihar Public Service Commission">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">Code</label>
                    <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" placeholder="e.g. BPSC">
                </div>
                <div class="filter-group">
                    <label class="filter-label">State</label>
                    <select name="state_code" class="filter-select">
                        <option value="">Select State</option>
                        <?php foreach ($states as $sCode => $sName): ?>
                            <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Website URL</label>
                <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>" placeholder="https://bpsc.bih.nic.in">
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Commission' : '<i class="fas fa-plus"></i> Add Commission' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="psc.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
