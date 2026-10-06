<?php
// admin/acts.php - Manage Bare Acts
$pageTitle = "Manage Bare Acts";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';

$action = sanitize($_GET['action'] ?? 'list');
$editId = sanitize($_GET['id'] ?? '');

// Handle Add / Edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actName = sanitize($_POST['name'] ?? '');
    $actYear = sanitize($_POST['year'] ?? '');
    $actEnglish = sanitize($_POST['english'] ?? '');
    $actHindi = sanitize($_POST['hindi'] ?? '');
    $actOfficial = sanitize($_POST['official'] ?? '');
    $actStatus = sanitize($_POST['status'] ?? 'ACTIVE');
    $postId = sanitize($_POST['id'] ?? '');

    if ($actName) {
        if ($postId) {
            // Update
            $stmt = $db->prepare("UPDATE acts SET name = ?, year = ?, english = ?, hindi = ?, official = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$actName, $actYear, $actEnglish, $actHindi, $actOfficial, $actStatus, $postId]);
            $msg = "Bare Act updated successfully.";
            $action = 'list';
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO acts (name, year, english, hindi, official, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$actName, $actYear, $actEnglish, $actHindi, $actOfficial, $actStatus]);
            $msg = "New Bare Act added successfully.";
            $action = 'list';
        }
    } else {
        $err = "Act Title is required.";
    }
}

// Handle Delete
if ($action === 'delete' && $editId) {
    $del = $db->prepare("DELETE FROM acts WHERE id = ?");
    $del->execute([$editId]);
    $msg = "Bare Act deleted.";
    $action = 'list';
}

// Fetch act for edit
$editAct = null;
if ($action === 'edit' && $editId) {
    $st = $db->prepare("SELECT * FROM acts WHERE id = ?");
    $st->execute([$editId]);
    $editAct = $st->fetch();
}

// List all acts
$acts = $db->query("SELECT * FROM acts ORDER BY id DESC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;">Manage Bare Acts (<?= count($acts) ?> Enactments)</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Add, update, or link official Gazette Bare Act PDFs.</p>
    </div>
    <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="acts.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Bare Act</a>
    <?php else: ?>
        <a href="acts.php" class="btn btn-outline btn-sm">Back to List</a>
    <?php endif; ?>
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

<?php if ($action === 'add' || ($action === 'edit' && $editAct)): ?>
    <!-- Form Box -->
    <div class="stat-box" style="max-width: 800px; padding: 2rem; margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.25rem; color: var(--primary);">
            <?= $editAct ? 'Edit Bare Act: ' . sanitize($editAct['name']) : 'Add New Bare Act' ?>
        </h3>

        <form action="acts.php" method="POST">
            <?php if ($editAct): ?>
                <input type="hidden" name="id" value="<?= $editAct['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Act Name (English) *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editAct['name'] ?? '') ?>" placeholder="e.g. Bharatiya Nyaya Sanhita, 2023" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Enactment Year</label>
                    <input type="number" name="year" class="filter-input" value="<?= sanitize($editAct['year'] ?? '') ?>" placeholder="e.g. 2023" min="1800" max="<?= date('Y') ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="ACTIVE" <?= ($editAct['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                        <option value="INACTIVE" <?= ($editAct['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">English Bare Act PDF URL (India Code / Gazette Link)</label>
                <input type="url" name="english" class="filter-input" value="<?= sanitize($editAct['english'] ?? '') ?>" placeholder="https://www.indiacode.nic.in/.../act.pdf">
            </div>

            <div class="filter-group">
                <label class="filter-label">Hindi Bare Act PDF URL</label>
                <input type="url" name="hindi" class="filter-input" value="<?= sanitize($editAct['hindi'] ?? '') ?>" placeholder="https://www.indiacode.nic.in/.../act_hi.pdf">
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Legislative Portal URL</label>
                <input type="url" name="official" class="filter-input" value="<?= sanitize($editAct['official'] ?? '') ?>" placeholder="https://legislative.gov.in">
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Bare Act</button>
        </form>
    </div>
<?php endif; ?>

<!-- Acts List Table -->
<div class="stat-box" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Act Title</th>
                    <th>Year</th>
                    <th>PDF Links</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($acts as $a): ?>
                    <tr>
                        <td>#<?= $a['id'] ?></td>
                        <td>
                            <strong><a href="../act-details?id=<?= $a['id'] ?>" target="_blank"><?= sanitize(cleanActName($a['name'])) ?></a></strong>
                        </td>
                        <td><span class="act-year"><?= sanitize($a['year'] ?: '—') ?></span></td>
                        <td>
                            <?php if (!empty($a['english'])): ?>
                                <a href="<?= sanitize($a['english']) ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;"><i class="fas fa-file-pdf text-danger"></i> English</a>
                            <?php endif; ?>
                            <?php if (!empty($a['hindi'])): ?>
                                <a href="<?= sanitize($a['hindi']) ?>" target="_blank" class="btn btn-outline-gold btn-sm" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;"><i class="fas fa-file-pdf"></i> Hindi</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-verification <?= ($a['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'badge-verified' : 'badge-basic' ?>">
                                <?= sanitize($a['status'] ?? 'ACTIVE') ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.35rem;">
                                <a href="acts.php?action=edit&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <a href="acts.php?action=delete&id=<?= $a['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #dc2626;" onclick="return confirm('Delete this Bare Act?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
