<?php
// admin/bc.php - Manage State Bar Councils & BCI Directory
$pageTitle = "Bar Councils Directory";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $bciCode = sanitize($_POST['bci_code'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE bc SET name = ?, code = ?, bci_code = ?, address = ?, email = ?, tel = ?, url = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $bciCode, $address, $email, $tel, $url, $status, $editId]);
                $msg = "Bar Council updated successfully.";
            } else {
                $stmt = $db->prepare("INSERT INTO bc (name, code, bci_code, address, email, tel, url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $bciCode, $address, $email, $tel, $url, $status]);
                $msg = "New Bar Council added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving Bar Council: " . $e->getMessage();
        }
    }
}

// Fetch single for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM bc WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$councils = $db->query("SELECT * FROM bc ORDER BY name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            State Bar Councils Directory (<?= count($councils) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage 26 State Bar Councils and Bar Council of India official registry.
        </p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('bcFormCard').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-plus"></i> Add Bar Council
    </button>
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="bc-grid">
    <style>
        @media(max-width: 992px) {
            .bc-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Table List -->
    <div class="admin-card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
        <div class="table-responsive" style="border: none;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Bar Council Name</th>
                        <th>Code</th>
                        <th>Contact / Email</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($councils as $bc): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($bc['name']) ?></strong>
                                <?php if ($bc['url']): ?>
                                    <div><a href="<?= sanitize($bc['url']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--brand-red);"><i class="fas fa-external-link-alt"></i> Website</a></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?= sanitize($bc['code'] ?: '—') ?></code></td>
                            <td>
                                <div><i class="fas fa-envelope" style="font-size:0.75rem;"></i> <?= sanitize($bc['email'] ?: '—') ?></div>
                                <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size:0.75rem;"></i> <?= sanitize($bc['tel'] ?: '—') ?></small>
                            </td>
                            <td>
                                <span class="badge-verification <?= ($bc['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($bc['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="bc.php?action=edit&id=<?= $bc['id'] ?>" class="btn btn-outline btn-sm" title="Edit">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create / Edit Form Card -->
    <div class="stat-box" id="bcFormCard" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Bar Council' : '<i class="fas fa-plus"></i> Add Bar Council' ?>
        </h3>

        <form action="bc.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Bar Council Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Bar Council of Delhi">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">State Code</label>
                    <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" placeholder="e.g. DL">
                </div>
                <div class="filter-group">
                    <label class="filter-label">BCI Code</label>
                    <input type="text" name="bci_code" class="filter-input" value="<?= sanitize($editItem['bci_code'] ?? '') ?>" placeholder="e.g. BCI001">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Website URL</label>
                <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>" placeholder="https://...">
            </div>

            <div class="filter-group">
                <label class="filter-label">Email Address</label>
                <input type="email" name="email" class="filter-input" value="<?= sanitize($editItem['email'] ?? '') ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Telephone / Phone</label>
                <input type="text" name="tel" class="filter-input" value="<?= sanitize($editItem['tel'] ?? '') ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Headquarters Address</label>
                <textarea name="address" class="filter-input" rows="2"><?= sanitize($editItem['address'] ?? '') ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Changes' : '<i class="fas fa-plus"></i> Create Bar Council' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="bc.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
