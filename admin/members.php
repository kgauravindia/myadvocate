<?php
// admin/members.php - Manage Registered Members & Citizen Users
$pageTitle = "Manage Members";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$search = sanitize($_GET['q'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM member WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM member WHERE 1=1";
$params = [];

if (!empty($search)) {
    if (is_numeric($search)) {
        $sql .= " AND mobile LIKE ?";
        $countSql .= " AND mobile LIKE ?";
        $params[] = "%" . $search . "%";
    } else {
        $sql .= " AND (name LIKE ? OR email LIKE ?)";
        $countSql .= " AND (name LIKE ? OR email LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $status;
}

try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = max(1, ceil($totalCount / $perPage));

$sql .= " ORDER BY id DESC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    $members = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Registered Members (<?= number_format($totalCount) ?> Records)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage platform users, view citizen registrations, and impersonate/login as member.
        </p>
    </div>
</div>

<!-- Search / Filter Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="members.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search Member</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Search Name, Mobile, Email..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">Account Status</label>
            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="ACTIVE" <?= $status === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                <option value="PENDING" <?= $status === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                <option value="BLOCK" <?= $status === 'BLOCK' ? 'selected' : '' ?>>Blocked</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="members.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="admin-card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive" style="border: none;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Member Name</th>
                    <th>Contact Details</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th style="text-align: right; width: 160px;">Impersonate & Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($members)): ?>
                    <?php foreach ($members as $mem): 
                        $statusClass = ($mem['status'] === 'ACTIVE') ? 'badge-verified' : (($mem['status'] === 'BLOCK') ? 'badge-suspended' : 'badge-claimed');
                    ?>
                        <tr>
                            <td><strong>#<?= $mem['id'] ?></strong></td>
                            <td>
                                <div style="font-weight: 700; color: var(--primary);">
                                    <?= sanitize($mem['name'] ?: 'Member User') ?>
                                </div>
                            </td>
                            <td>
                                <div><i class="fas fa-phone" style="font-size:0.75rem; color: var(--brand-red);"></i> <?= sanitize($mem['mobile'] ?: '—') ?></div>
                                <small style="color: var(--text-muted);"><i class="fas fa-envelope" style="font-size:0.75rem;"></i> <?= sanitize($mem['email'] ?: '—') ?></small>
                            </td>
                            <td>
                                <span class="badge-verification <?= $statusClass ?>" style="font-size: 0.725rem; padding: 0.2rem 0.5rem;">
                                    <?= sanitize($mem['status'] ?: 'ACTIVE') ?>
                                </span>
                            </td>
                            <td>
                                <small style="color: var(--text-muted);"><?= !empty($mem['created_at']) ? date('d M Y, h:i A', strtotime($mem['created_at'])) : '—' ?></small>
                            </td>
                            <td style="text-align: right;">
                                <a href="impersonate.php?type=member&id=<?= $mem['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;" title="Login as Member">
                                    <i class="fas fa-right-to-bracket"></i> Login as Member
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            No member accounts found matching your query.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'members.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
