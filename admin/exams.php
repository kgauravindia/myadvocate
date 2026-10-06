<?php
// admin/exams.php - Manage Legal & Judicial Examinations
$pageTitle = "Legal Examinations";
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
    $url = sanitize($_POST['url'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE exam SET name = ?, details = ?, url = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $details, $url, $status, $editId]);
                $msg = "Examination updated.";
            } else {
                $stmt = $db->prepare("INSERT INTO exam (name, details, url, status, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $details, $url, $status]);
                $msg = "New Examination added.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving exam: " . $e->getMessage();
        }
    }
}

// Fetch for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM exam WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$exams = $db->query("SELECT e.*, (SELECT COUNT(*) FROM question WHERE exam_id = e.id) as questions_count FROM exam e ORDER BY e.name ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Legal &amp; Judicial Examinations (<?= count($exams) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage AIBE (All India Bar Examination), Judiciary, CLAT, and State Bar exams.
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="exam-grid">
    <style>
        @media(max-width: 992px) {
            .exam-grid {
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
                        <th>Exam Name</th>
                        <th>Associated Questions</th>
                        <th>Official URL</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $ex): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($ex['name']) ?></strong>
                                <?php if ($ex['details']): ?>
                                    <div><small style="color: var(--text-muted);"><?= sanitize($ex['details']) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: var(--primary);"><?= number_format($ex['questions_count']) ?></span> Questions
                            </td>
                            <td>
                                <?php if ($ex['url']): ?>
                                    <a href="<?= sanitize($ex['url']) ?>" target="_blank" style="font-size: 0.8125rem; color: var(--brand-red); font-weight: 600;">
                                        <i class="fas fa-external-link-alt"></i> Portal
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-verification <?= ($ex['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                    <?= sanitize($ex['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="exams.php?action=edit&id=<?= $ex['id'] ?>" class="btn btn-outline btn-sm">
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
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Examination' : '<i class="fas fa-plus"></i> Add Examination' ?>
        </h3>

        <form action="exams.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Examination Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. All India Bar Examination (AIBE)">
            </div>

            <div class="filter-group">
                <label class="filter-label">Details / Eligibility</label>
                <textarea name="details" class="filter-input" rows="3" placeholder="Syllabus or qualification details..."><?= sanitize($editItem['details'] ?? '') ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Exam Portal URL</label>
                <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>" placeholder="https://allindiabarexamination.com">
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Examination' : '<i class="fas fa-plus"></i> Add Examination' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="exams.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
