<?php
// admin/subjects.php - Manage Law Subjects for Examinations
$pageTitle = "Law Subjects";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $details = sanitize($_POST['details'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE subject SET name = ?, details = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $details, $status, $editId]);
                $msg = "Subject updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO subject (name, details, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $details, $status]);
                $msg = "New Subject added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving subject: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM subject WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$subjects = $db->query("SELECT s.*, (SELECT COUNT(*) FROM question WHERE subject_id = s.id) as questions_count FROM subject s ORDER BY s.name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Law Subjects Directory (<?= count($subjects) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage Constitutional Law, Criminal Procedure, CPC, Evidence, Contract Law, etc.
        </p>
    </div>
    <a href="questions.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-circle-question"></i> View Question Bank</a>
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="sub-grid">
    <style>
        @media(max-width: 992px) {
            .sub-grid {
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
                        <th>Subject Name</th>
                        <th style="text-align: right;">Questions Bank</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $s): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($s['name']) ?></strong>
                                <?php if ($s['details']): ?>
                                    <div><small style="color: var(--text-muted);"><?= sanitize($s['details']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="questions.php?subject=<?= $s['id'] ?>" class="btn btn-outline-primary btn-sm" style="font-weight: 700;">
                                    <?= number_format($s['questions_count']) ?> Questions &rarr;
                                </a>
                            </td>
                            <td>
                                <span class="badge-verification <?= ($s['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($s['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="subjects.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">
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
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Subject' : '<i class="fas fa-plus"></i> Add Subject' ?>
        </h3>

        <form action="subjects.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Subject Title *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Constitutional Law">
            </div>

            <div class="filter-group">
                <label class="filter-label">Details / Syllabus Overview</label>
                <textarea name="details" class="filter-input" rows="3" placeholder="Brief subject scope..."><?= sanitize($editItem['details'] ?? '') ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Subject' : '<i class="fas fa-plus"></i> Add Subject' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="subjects.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
