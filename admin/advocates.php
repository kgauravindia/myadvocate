<?php
// admin/advocates.php - Manage Advocates Directory
$pageTitle = "Manage Advocates";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$search = sanitize($_GET['q'] ?? '');
$state = sanitize($_GET['state'] ?? '');
$plan = sanitize($_GET['plan'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM advocate WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM advocate WHERE 1=1";
$params = [];

if (!empty($search)) {
    if (is_numeric($search) && strlen($search) >= 10) {
        $sql .= " AND mobile LIKE ?";
        $countSql .= " AND mobile LIKE ?";
        $params[] = "%" . $search . "%";
    } elseif (is_numeric($search) && strlen($search) <= 6) {
        $sql .= " AND e_no = ?";
        $countSql .= " AND e_no = ?";
        $params[] = $search;
    } else {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR court LIKE ?)";
        $countSql .= " AND (name LIKE ? OR email LIKE ? OR court LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }
}

if (!empty($state)) {
    $sql .= " AND state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $state;
}

if (!empty($plan)) {
    $sql .= " AND plan_type = ?";
    $countSql .= " AND plan_type = ?";
    $params[] = $plan;
}

try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);

$sql .= " ORDER BY id DESC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $advocates = $stmt->fetchAll();
} catch (Exception $e) {
    $advocates = [];
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0;">Advocate Directory (<?= number_format($totalCount) ?> Records)</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Search, verify, edit, and manage advocate listings.</p>
    </div>
    <a href="advocate_add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Advocate</a>
</div>

<!-- Search / Filter Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="advocates.php" method="GET" style="display: grid; grid-template-columns: 2fr 1.2fr 1.2fr auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label class="filter-label">Search Query</label>
            <input type="text" name="q" value="<?= sanitize($search) ?>" placeholder="Name, Mobile, Enrollment..." class="filter-input">
        </div>
        <div>
            <label class="filter-label">State Bar Council</label>
            <select name="state" class="filter-select">
                <option value="">All States</option>
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $state === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="filter-label">Verification / Plan</label>
            <select name="plan" class="filter-select">
                <option value="">All Verification Statuses</option>
                <option value="basic" <?= $plan === 'basic' ? 'selected' : '' ?>>Basic (Public Record)</option>
                <option value="registered" <?= $plan === 'registered' ? 'selected' : '' ?>>Registered</option>
                <option value="verified" <?= $plan === 'verified' ? 'selected' : '' ?>>Verified</option>
            </select>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;"><i class="fas fa-filter"></i> Filter</button>
            <a href="advocates.php" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="stat-box" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Advocate Name</th>
                    <th>Mobile / Email</th>
                    <th>Enrollment No.</th>
                    <th>State / District</th>
                    <th>Status Badge</th>
                    <th>Advocate Index</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($advocates)): ?>
                    <?php foreach ($advocates as $adv): 
                        $badge = getVerificationBadge($adv);
                        $advIndex = calculateAdvocateIndex($adv);
                    ?>
                        <tr>
                            <td>#<?= $adv['id'] ?></td>
                            <td>
                                <strong><a href="advocate_edit.php?id=<?= $adv['id'] ?>"><?= sanitize($adv['name'] ?: 'Unnamed Advocate') ?></a></strong><br>
                                <small style="color: var(--text-muted);"><i class="fas fa-gavel"></i> <?= sanitize(getCourtName($adv['court'] ?? '')) ?></small>
                            </td>
                            <td>
                                <div><i class="fas fa-phone" style="font-size:0.75rem;"></i> <?= sanitize($adv['mobile'] ?: '—') ?></div>
                                <small style="color: var(--text-muted);"><i class="fas fa-envelope" style="font-size:0.75rem;"></i> <?= sanitize($adv['email'] ?: '—') ?></small>
                            </td>
                            <td>
                                <strong><?= sanitize($adv['e_no'] ?: '—') ?></strong><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>
                            </td>
                            <td>
                                <?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?><br>
                                <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                            </td>
                            <td>
                                <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.75rem;">
                                    <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?= renderAdvocateIndexBadge($advIndex, 'compact') ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem; align-items: center;">
                                    <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" title="Edit Profile">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="font-size: 0.75rem; font-weight: 700; padding: 0.3rem 0.6rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Login as Advocate">
                                        <i class="fas fa-right-to-bracket"></i> Login As
                                    </a>
                                    <a href="../<?= getAdvocateUrl($adv) ?>" target="_blank" class="btn btn-outline-gold btn-sm" title="Public View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">No advocates match your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?= renderPagination($page, $totalPages, 'advocates.php', $_GET) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
