<?php
// admin/notices.php - Manage Legal Notices
$pageTitle = "Manage Legal Notices";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';

$action = sanitize($_GET['action'] ?? 'list');
$editId = sanitize($_GET['id'] ?? '');

// Handle Add / Edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noticeName = sanitize($_POST['name'] ?? '');
    $noticeDetails = sanitize($_POST['details'] ?? '');
    $lastDate = sanitize($_POST['last_date'] ?? '');
    $url1 = sanitize($_POST['url_1'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $postId = sanitize($_POST['id'] ?? '');

    if ($noticeName) {
        if ($postId) {
            // Update
            $stmt = $db->prepare("UPDATE notice SET name = ?, details = ?, last_date = ?, url_1 = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$noticeName, $noticeDetails, $lastDate ?: null, $url1, $status, $postId]);
            $msg = "Notice updated successfully.";
            $action = 'list';
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO notice (name, details, last_date, url_1, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$noticeName, $noticeDetails, $lastDate ?: null, $url1, $status]);
            $msg = "New notification published.";
            $action = 'list';
        }
    } else {
        $err = "Notice title is required.";
    }
}

// Handle Delete
if ($action === 'delete' && $editId) {
    $del = $db->prepare("DELETE FROM notice WHERE id = ?");
    $del->execute([$editId]);
    $msg = "Notice removed.";
    $action = 'list';
}

// Fetch notice for edit
$editNotice = null;
if ($action === 'edit' && $editId) {
    $st = $db->prepare("SELECT * FROM notice WHERE id = ?");
    $st->execute([$editId]);
    $editNotice = $st->fetch();
}

// List all notices
$notices = $db->query("SELECT * FROM notice ORDER BY id DESC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;">Legal Notices & Updates (<?= count($notices) ?> Records)</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Publish court orders, examination notices, and bar council notifications.</p>
    </div>
    <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <a href="notices.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Post New Notice</a>
    <?php else: ?>
        <a href="notices.php" class="btn btn-outline btn-sm">Back to List</a>
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

<?php if ($action === 'add' || ($action === 'edit' && $editNotice)): ?>
    <!-- Form Box -->
    <div class="stat-box" style="max-width: 800px; padding: 2rem; margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.25rem; color: var(--primary);">
            <?= $editNotice ? 'Edit Notice' : 'Post New Legal Notice' ?>
        </h3>

        <form action="notices.php" method="POST">
            <?php if ($editNotice): ?>
                <input type="hidden" name="id" value="<?= $editNotice['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Notice Title *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editNotice['name'] ?? '') ?>" placeholder="e.g. All India Bar Examination (AIBE XXII) Registration Notice" required>
            </div>

            <div class="filter-group">
                <label class="filter-label">Description / Summary</label>
                <textarea name="details" class="filter-input" rows="4"><?= sanitize($editNotice['details'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Last Date / Application Deadline</label>
                    <input type="date" name="last_date" class="filter-input" value="<?= sanitize($editNotice['last_date'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="ACTIVE" <?= ($editNotice['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>🟢 ACTIVE</option>
                        <option value="CLOSED" <?= ($editNotice['status'] ?? '') === 'CLOSED' ? 'selected' : '' ?>>⚪ CLOSED</option>
                        <option value="EXPIRED" <?= ($editNotice['status'] ?? '') === 'EXPIRED' ? 'selected' : '' ?>>🔴 EXPIRED</option>
                    </select>
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official PDF / Document Link</label>
                <input type="url" name="url_1" class="filter-input" value="<?= sanitize($editNotice['url_1'] ?? '') ?>" placeholder="https://highcourt.gov.in/notice.pdf">
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publish Notice</button>
        </form>
    </div>
<?php endif; ?>

<!-- Notices List Table -->
<div class="stat-box" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Notice Title</th>
                    <th>Deadline</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notices as $n): 
                    $isExpired = !empty($n['last_date']) && strtotime($n['last_date']) < time();
                ?>
                    <tr>
                        <td>#<?= $n['id'] ?></td>
                        <td>
                            <strong><?= sanitize($n['name']) ?></strong><br>
                            <small style="color: var(--text-muted);"><?= sanitize(substr($n['details'] ?? '', 0, 80)) ?>...</small>
                        </td>
                        <td>
                            <?= !empty($n['last_date']) ? date('d M Y', strtotime($n['last_date'])) : '—' ?>
                        </td>
                        <td>
                            <?php if (!empty($n['url_1'])): ?>
                                <a href="<?= sanitize($n['url_1']) ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;"><i class="fas fa-file-pdf text-danger"></i> PDF</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-verification <?= $isExpired ? 'badge-basic' : 'badge-verified' ?>">
                                <?= $isExpired ? '🔴 Expired' : '🟢 Active' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.35rem;">
                                <a href="notices.php?action=edit&id=<?= $n['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <a href="notices.php?action=delete&id=<?= $n['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #dc2626;" onclick="return confirm('Delete this notice?');">
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
