<?php
// admin/state.php - Manage States & Union Territories of India
$pageTitle = "States & Union Territories";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name && $code) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE state SET name = ?, code = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $status, $editId]);
                $msg = "State updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO state (name, code, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $status]);
                $msg = "New State / UT created.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving state: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM state WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$statesList = $db->query("SELECT s.*, (SELECT COUNT(*) FROM advocate WHERE state_code = s.code) as advocates_count, (SELECT COUNT(*) FROM district WHERE state_code = s.code) as districts_count FROM state s ORDER BY s.name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            States &amp; Union Territories (<?= count($statesList) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage 28 States, 8 Union Territories, postal codes, and regional advocate counts.
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="state-grid">
    <style>
        @media(max-width: 992px) {
            .state-grid {
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
                        <th style="width: 80px;">Code</th>
                        <th>State / UT Name</th>
                        <th style="text-align: right;">Districts</th>
                        <th style="text-align: right;">Advocates</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statesList as $st): ?>
                        <tr>
                            <td><code><?= sanitize($st['code']) ?></code></td>
                            <td><strong><?= sanitize($st['name']) ?></strong></td>
                            <td style="text-align: right; font-weight: 600;">
                                <a href="district.php?state=<?= urlencode($st['code']) ?>"><?= number_format($st['districts_count']) ?></a>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: var(--brand-red);">
                                <?= number_format($st['advocates_count']) ?>
                            </td>
                            <td>
                                <span class="badge-verification <?= ($st['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($st['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="state.php?action=edit&id=<?= $st['id'] ?>" class="btn btn-outline btn-sm">
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
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit State / UT' : '<i class="fas fa-plus"></i> Add State / UT' ?>
        </h3>

        <form action="state.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">State / UT Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Bihar">
            </div>

            <div class="filter-group">
                <label class="filter-label">State Code (2 Letters) *</label>
                <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" maxlength="5" required placeholder="e.g. BR">
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save State' : '<i class="fas fa-plus"></i> Add State' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="state.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
