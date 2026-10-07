<?php
// admin/advocate_config.php - Master Configuration Manager for advocate_config table
$pageTitle = "Advocate Master Configuration";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = (int)($_GET['id'] ?? 0);
$keyFilter = sanitize($_GET['key'] ?? 'practice_area');

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['form_action'] ?? '');
    $configKey = sanitize($_POST['config_key'] ?? 'practice_area');
    $configValue = sanitize($_POST['config_value'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = (int)($_POST['edit_id'] ?? 0);

    if (empty($configValue)) {
        $err = "Configuration Value cannot be empty.";
    } elseif ($postAction === 'add') {
        try {
            $stmt = $db->prepare("INSERT INTO advocate_config (config_key, config_value, display_order, status, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$configKey, $configValue, $displayOrder, $status]);
            $msg = "New configuration option added successfully!";
        } catch (Exception $e) {
            $err = "Error adding configuration: " . $e->getMessage();
        }
    } elseif ($postAction === 'edit' && $editId > 0) {
        try {
            $stmt = $db->prepare("UPDATE advocate_config SET config_key = ?, config_value = ?, display_order = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$configKey, $configValue, $displayOrder, $status, $editId]);
            $msg = "Configuration option updated successfully!";
        } catch (Exception $e) {
            $err = "Error updating configuration: " . $e->getMessage();
        }
    }
}

// Handle Status Toggle / Delete
if ($action === 'toggle' && $id > 0) {
    try {
        $stmt = $db->prepare("UPDATE advocate_config SET status = IF(status = 'ACTIVE', 'INACTIVE', 'ACTIVE'), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Status toggled successfully.";
    } catch (Exception $e) {
        $err = "Error toggling status.";
    }
} elseif ($action === 'delete' && $id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM advocate_config WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Configuration option deleted successfully.";
    } catch (Exception $e) {
        $err = "Error deleting configuration.";
    }
}

// Fetch edit record if in edit mode
$editRecord = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $db->prepare("SELECT * FROM advocate_config WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editRecord = $stmt->fetch();
}

// Fetch all distinct keys
$availableKeys = $db->query("SELECT config_key, COUNT(*) as cnt FROM advocate_config GROUP BY config_key ORDER BY config_key ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch items for current selected key or all
$where = ["1=1"];
$params = [];
if (!empty($keyFilter) && $keyFilter !== 'all') {
    $where[] = "config_key = ?";
    $params[] = $keyFilter;
}

$whereSql = implode(" AND ", $where);
$stmt = $db->prepare("SELECT * FROM advocate_config WHERE $whereSql ORDER BY config_key ASC, display_order ASC, config_value ASC");
$stmt->execute($params);
$configItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-sliders" style="color: var(--brand-red);"></i> Advocate Master Configuration
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
            Manage practice areas, court categories, education levels, and dynamic system values used in advocate profiles.
        </p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    
    <!-- Left Column: Add / Edit Form Card -->
    <div class="admin-card" style="border-top: 4px solid var(--brand-red); margin-bottom: 0;">
        <h3 class="admin-card-title" style="margin-bottom: 1rem;">
            <i class="fas <?= $editRecord ? 'fa-pen-to-square' : 'fa-plus-circle' ?>" style="color: var(--brand-red);"></i>
            <?= $editRecord ? 'Edit Configuration Item' : 'Add New Config Option' ?>
        </h3>

        <form action="advocate_config.php?key=<?= urlencode($keyFilter) ?>" method="POST">
            <input type="hidden" name="form_action" value="<?= $editRecord ? 'edit' : 'add' ?>">
            <?php if ($editRecord): ?>
                <input type="hidden" name="edit_id" value="<?= $editRecord['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Configuration Category (Key) *</label>
                <input type="text" name="config_key" list="keysList" class="filter-input" value="<?= sanitize($editRecord['config_key'] ?? ($keyFilter !== 'all' ? $keyFilter : 'practice_area')) ?>" required placeholder="e.g. practice_area, court_type, education_level">
                <datalist id="keysList">
                    <?php foreach ($availableKeys as $k): ?>
                        <option value="<?= sanitize($k['config_key']) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="filter-group">
                <label class="filter-label">Configuration Value / Label *</label>
                <input type="text" name="config_value" class="filter-input" value="<?= sanitize($editRecord['config_value'] ?? '') ?>" required placeholder="e.g. Cyber Law, or CC|Civil Court">
                <small style="color: var(--text-muted); font-size: 0.75rem;">For key/value pairs use format <code>CODE|Label</code> (e.g. <code>HC|High Court</code>).</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">Display Order</label>
                    <input type="number" name="display_order" class="filter-input" value="<?= sanitize($editRecord['display_order'] ?? '0') ?>" min="0">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="ACTIVE" <?= ($editRecord['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                        <option value="INACTIVE" <?= ($editRecord['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center; min-height: 38px;">
                    <i class="fas fa-floppy-disk me-1"></i> <?= $editRecord ? 'Update Option' : 'Save Option' ?>
                </button>
                <?php if ($editRecord): ?>
                    <a href="advocate_config.php?key=<?= urlencode($keyFilter) ?>" class="btn btn-outline btn-sm" style="min-height: 38px;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Right Column: Tabs & Data Table -->
    <div>
        <!-- Category Filter Tabs -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
            <a href="advocate_config.php?key=all" class="btn <?= $keyFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                All Keys
            </a>
            <?php foreach ($availableKeys as $k): ?>
                <a href="advocate_config.php?key=<?= urlencode($k['config_key']) ?>" class="btn <?= $keyFilter === $k['config_key'] ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    <?= sanitize(ucwords(str_replace('_', ' ', $k['config_key']))) ?> (<?= $k['cnt'] ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Category Key</th>
                        <th>Option Value / Title</th>
                        <th style="width: 80px;">Order</th>
                        <th style="width: 90px;">Status</th>
                        <th style="text-align: right; width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($configItems)): ?>
                        <?php foreach ($configItems as $item): ?>
                            <tr>
                                <td style="color: var(--text-muted); font-size: 0.8rem;">#<?= $item['id'] ?></td>
                                <td>
                                    <span style="font-size: 0.75rem; font-weight: 700; background: #e2e8f0; color: #334155; padding: 0.15rem 0.45rem; border-radius: var(--radius-sm);">
                                        <?= sanitize($item['config_key']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= sanitize($item['config_value']) ?></strong>
                                </td>
                                <td><?= $item['display_order'] ?></td>
                                <td>
                                    <a href="advocate_config.php?action=toggle&id=<?= $item['id'] ?>&key=<?= urlencode($keyFilter) ?>" title="Click to toggle status">
                                        <?php if ($item['status'] === 'ACTIVE'): ?>
                                            <span style="background: #dcfce7; color: #15803d; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px;">ACTIVE</span>
                                        <?php else: ?>
                                            <span style="background: #fee2e2; color: #b91c1c; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px;">INACTIVE</span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <a href="advocate_config.php?action=edit&id=<?= $item['id'] ?>&key=<?= urlencode($keyFilter) ?>" class="btn btn-outline btn-sm" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="advocate_config.php?action=delete&id=<?= $item['id'] ?>&key=<?= urlencode($keyFilter) ?>" onclick="return confirm('Are you sure you want to delete this option?');" class="btn btn-outline-danger btn-sm" style="padding: 0.2rem 0.45rem; font-size: 0.75rem;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No configuration options found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
