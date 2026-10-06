<?php
// admin/questions.php - Manage AIBE & Legal Question Bank
$pageTitle = "AIBE Question Bank";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

$subjectFilter = sanitize($_GET['subject'] ?? '');
$search = sanitize($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Fetch subjects and exams for dropdowns
$subjects = $db->query("SELECT id, name FROM subject ORDER BY name ASC")->fetchAll();
$exams = $db->query("SELECT id, name FROM exam ORDER BY name ASC")->fetchAll();

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $examId = (int)($_POST['exam_id'] ?? 0);
    $questionDesc = sanitize($_POST['question_description'] ?? '');
    $optionA = sanitize($_POST['option_a'] ?? '');
    $optionB = sanitize($_POST['option_b'] ?? '');
    $optionC = sanitize($_POST['option_c'] ?? '');
    $optionD = sanitize($_POST['option_d'] ?? '');
    $answer = strtoupper(sanitize($_POST['answer'] ?? 'A'));
    $remarks = sanitize($_POST['remarks'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($questionDesc && $optionA && $optionB) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE question SET subject_id = ?, exam_id = ?, question_description = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, answer = ?, remarks = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$subjectId, $examId, $questionDesc, $optionA, $optionB, $optionC, $optionD, $answer, $remarks, $status, $editId]);
                $msg = "Question updated successfully.";
            } else {
                $stmt = $db->prepare("INSERT INTO question (subject_id, exam_id, question_description, option_a, option_b, option_c, option_d, answer, remarks, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$subjectId, $examId, $questionDesc, $optionA, $optionB, $optionC, $optionD, $answer, $remarks, $status]);
                $msg = "New question added to bank.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving question: " . $e->getMessage();
        }
    } else {
        $err = "Question text and minimum options (A, B) are required.";
    }
}

// Fetch single for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM question WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$sql = "SELECT q.*, s.name as subject_name, e.name as exam_name FROM question q LEFT JOIN subject s ON q.subject_id = s.id LEFT JOIN exam e ON q.exam_id = e.id WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM question q WHERE 1=1";
$params = [];

if (!empty($subjectFilter)) {
    $sql .= " AND q.subject_id = ?";
    $countSql .= " AND q.subject_id = ?";
    $params[] = $subjectFilter;
}

if (!empty($search)) {
    $sql .= " AND (q.question_description LIKE ? OR q.remarks LIKE ?)";
    $countSql .= " AND (q.question_description LIKE ? OR q.remarks LIKE ?)";
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

$sql .= " ORDER BY q.id DESC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $questions = $stmt->fetchAll();
} catch (Exception $e) {
    $questions = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            AIBE &amp; Judicial Question Bank (<?= number_format($totalCount) ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage MCQs, questions, answer keys, explanations, and exam associations.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="subjects.php" class="btn btn-outline btn-sm"><i class="fas fa-book"></i> Subjects (<?= count($subjects) ?>)</a>
        <a href="exams.php" class="btn btn-outline btn-sm"><i class="fas fa-graduation-cap"></i> Exams (<?= count($exams) ?>)</a>
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

<!-- Search Filter Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="questions.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search Questions</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Keywords in question or answer..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">Filter by Subject</label>
            <select name="subject" class="filter-select">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $sub): ?>
                    <option value="<?= $sub['id'] ?>" <?= $subjectFilter == $sub['id'] ? 'selected' : '' ?>><?= sanitize($sub['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="questions.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem;" class="q-grid">
    <style>
        @media(max-width: 992px) {
            .q-grid {
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
                        <th style="width: 60px;">ID</th>
                        <th>Question &amp; Options</th>
                        <th>Subject / Exam</th>
                        <th style="text-align: center; width: 70px;">Answer</th>
                        <th style="text-align: right; width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td>#<?= $q['id'] ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.35rem;">
                                        <?= sanitize($q['question_description']) ?>
                                    </div>
                                    <div style="font-size: 0.775rem; color: var(--text-muted); display: grid; grid-template-columns: 1fr 1fr; gap: 0.25rem;">
                                        <div><strong>(A)</strong> <?= sanitize($q['option_a']) ?></div>
                                        <div><strong>(B)</strong> <?= sanitize($q['option_b']) ?></div>
                                        <?php if ($q['option_c']): ?><div><strong>(C)</strong> <?= sanitize($q['option_c']) ?></div><?php endif; ?>
                                        <?php if ($q['option_d']): ?><div><strong>(D)</strong> <?= sanitize($q['option_d']) ?></div><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="practice-pill" style="font-size: 0.7rem;"><?= sanitize($q['subject_name'] ?: 'General Law') ?></span>
                                    <?php if ($q['exam_name']): ?>
                                        <div style="margin-top: 0.2rem;"><small style="color: var(--text-muted);"><?= sanitize($q['exam_name']) ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; width: 28px; height: 28px; line-height: 28px; text-align: center; border-radius: 50%; background: #ecfdf5; color: #047857; font-weight: 800; border: 1px solid #a7f3d0;">
                                        <?= sanitize($q['answer']) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="questions.php?action=edit&id=<?= $q['id'] ?>&subject=<?= urlencode($subjectFilter) ?>&page=<?= $page ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">No questions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form -->
    <div class="stat-box" style="padding: 1.5rem; height: fit-content;">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen"></i> Edit Question' : '<i class="fas fa-plus"></i> Add Question' ?>
        </h3>

        <form action="questions.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Subject *</label>
                <select name="subject_id" class="filter-select" required>
                    <option value="">Select Subject</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= ($editItem['subject_id'] ?? $subjectFilter) == $sub['id'] ? 'selected' : '' ?>><?= sanitize($sub['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Associated Examination</label>
                <select name="exam_id" class="filter-select">
                    <option value="0">All / AIBE Standard</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" <?= ($editItem['exam_id'] ?? '') == $ex['id'] ? 'selected' : '' ?>><?= sanitize($ex['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Question Description / Text *</label>
                <textarea name="question_description" class="filter-input" rows="3" required placeholder="Enter the complete question..."><?= sanitize($editItem['question_description'] ?? '') ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Option (A) *</label>
                <input type="text" name="option_a" class="filter-input" value="<?= sanitize($editItem['option_a'] ?? '') ?>" required>
            </div>

            <div class="filter-group">
                <label class="filter-label">Option (B) *</label>
                <input type="text" name="option_b" class="filter-input" value="<?= sanitize($editItem['option_b'] ?? '') ?>" required>
            </div>

            <div class="filter-group">
                <label class="filter-label">Option (C)</label>
                <input type="text" name="option_c" class="filter-input" value="<?= sanitize($editItem['option_c'] ?? '') ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Option (D)</label>
                <input type="text" name="option_d" class="filter-input" value="<?= sanitize($editItem['option_d'] ?? '') ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Correct Answer *</label>
                <select name="answer" class="filter-select" required>
                    <option value="A" <?= ($editItem['answer'] ?? '') === 'A' ? 'selected' : '' ?>>Option A</option>
                    <option value="B" <?= ($editItem['answer'] ?? '') === 'B' ? 'selected' : '' ?>>Option B</option>
                    <option value="C" <?= ($editItem['answer'] ?? '') === 'C' ? 'selected' : '' ?>>Option C</option>
                    <option value="D" <?= ($editItem['answer'] ?? '') === 'D' ? 'selected' : '' ?>>Option D</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label">Explanation / Remarks</label>
                <textarea name="remarks" class="filter-input" rows="2" placeholder="Legal section reference or reasoning..."><?= sanitize($editItem['remarks'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Question' : '<i class="fas fa-plus"></i> Add Question' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="questions.php?subject=<?= urlencode($subjectFilter) ?>" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center;">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'questions.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
