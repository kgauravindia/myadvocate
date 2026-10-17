<?php
// admin/advocates.php - Manage Advocates Directory
$pageTitle = "Manage Advocates";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$msg = '';
$err = '';

// Handle Manual Verification Actions
$action = sanitize($_GET['action'] ?? '');
$actId = (int)($_GET['id'] ?? 0);

if ($actId > 0 && ($action === 'verify' || $action === 'block' || $action === 'reset_pending')) {
    try {
        if ($action === 'verify') {
            $up = $db->prepare("UPDATE advocate SET type = 'ACTIVE', plan_type = 'verified', status = 'ACTIVE', updated_at = NOW() WHERE id = ?");
            $up->execute([$actId]);
            recordAdvocateProfileUpdate($actId, ['type' => 'Manually verified and approved via admin directory'], 'admin');

            // Dispatch approval SMS if function exists
            if (function_exists('sendAdvocateProfileApprovedSMS')) {
                @sendAdvocateProfileApprovedSMS($actId);
            }
            $msg = "Advocate #{$actId} has been successfully verified & approved! Profile is now active.";
        } elseif ($action === 'block') {
            $up = $db->prepare("UPDATE advocate SET type = 'BLOCK', status = 'BLOCKED', updated_at = NOW() WHERE id = ?");
            $up->execute([$actId]);
            recordAdvocateProfileUpdate($actId, ['type' => 'Manually blocked via admin directory'], 'admin');
            if (function_exists('sendAdvocateProfileNotActiveSMS')) {
                @sendAdvocateProfileNotActiveSMS($actId);
            }
            $msg = "Advocate #{$actId} has been blocked. (DLT SMS: Not ACTIVE sent)";
        } elseif ($action === 'reset_pending') {
            $up = $db->prepare("UPDATE advocate SET type = 'PENDING', updated_at = NOW() WHERE id = ?");
            $up->execute([$actId]);
            recordAdvocateProfileUpdate($actId, ['type' => 'Reset to pending verification'], 'admin');
            if (function_exists('sendAdvocateProfileNotActiveSMS')) {
                @sendAdvocateProfileNotActiveSMS($actId);
            }
            $msg = "Advocate #{$actId} set to pending verification. (DLT SMS: Not ACTIVE sent)";
        }
    } catch (Exception $e) {
        $err = "Action failed: " . $e->getMessage();
    }
}

// Fetch Global Counts for Quick Filter Pills
$allCount = 0;
$pendingCount = 0;
$activeCount = 0;
try {
    $allCount = (int)$db->query("SELECT COUNT(*) FROM advocate")->fetchColumn();
    $pendingCount = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type = 'PENDING'")->fetchColumn();
    $activeCount = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type = 'ACTIVE' OR type = 'VERIFIED'")->fetchColumn();
} catch (Exception $e) {}

$search = sanitize($_GET['q'] ?? '');
$state = sanitize($_GET['state'] ?? '');
$plan = sanitize($_GET['plan'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$sql = "SELECT a.*, bc.url_1 AS bc_verify_url, bc.name AS bc_name, bc.code AS bc_code FROM advocate a LEFT JOIN bc ON (bc.id = a.bc_id OR (a.bc_id IS NULL AND bc.state_code = a.state_code)) WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM advocate a WHERE 1=1";
$params = [];

if (!empty($search)) {
    if (is_numeric($search) && strlen($search) >= 10) {
        $sql .= " AND a.mobile LIKE ?";
        $countSql .= " AND a.mobile LIKE ?";
        $params[] = "%" . $search . "%";
    } elseif (is_numeric($search) && strlen($search) <= 6) {
        $sql .= " AND a.e_no = ?";
        $countSql .= " AND a.e_no = ?";
        $params[] = $search;
    } else {
        $sql .= " AND (a.name LIKE ? OR a.email LIKE ? OR a.court LIKE ?)";
        $countSql .= " AND (a.name LIKE ? OR a.email LIKE ? OR a.court LIKE ?)";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
        $params[] = "%" . $search . "%";
    }
}

if (!empty($state)) {
    $sql .= " AND a.state_code = ?";
    $countSql .= " AND a.state_code = ?";
    $params[] = $state;
}

if (!empty($plan)) {
    $sql .= " AND a.plan_type = ?";
    $countSql .= " AND a.plan_type = ?";
    $params[] = $plan;
}

if (!empty($type)) {
    if ($type === 'PENDING') {
        $sql .= " AND a.type = 'PENDING'";
        $countSql .= " AND a.type = 'PENDING'";
    } else {
        $sql .= " AND a.type = ?";
        $countSql .= " AND a.type = ?";
        $params[] = $type;
    }
}

try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalCount = 0;
}

$totalPages = ceil($totalCount / $perPage);

$sql .= " ORDER BY a.id DESC LIMIT $perPage OFFSET $offset";
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
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Search, manually verify registered advocates, edit, and manage listings.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="advocates.php?type=PENDING" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.35rem; <?= $type === 'PENDING' ? 'border-color: #f59e0b; color: #b45309; background: #fef3c7;' : '' ?>">
            <i class="fas fa-user-clock" style="color: #f59e0b;"></i> Pending (<?= number_format($pendingCount) ?>)
        </a>
        <a href="advocate_add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Advocate</a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<!-- Quick Status Pills -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
    <a href="advocates.php" class="btn btn-sm <?= empty($type) ? 'btn-dark' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 0.3rem 0.75rem; border-radius: 9999px;">
        All Advocates (<?= number_format($allCount) ?>)
    </a>
    <a href="advocates.php?type=PENDING" class="btn btn-sm <?= $type === 'PENDING' ? 'btn-primary' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 0.3rem 0.75rem; border-radius: 9999px; <?= $type === 'PENDING' ? 'background: #f59e0b; border-color: #f59e0b; color: #ffffff;' : 'color: #b45309; border-color: #fcd34d; background: #fffbeb;' ?>">
        <i class="fas fa-clock"></i> Pending Verification (<?= number_format($pendingCount) ?>)
    </a>
    <a href="advocates.php?type=ACTIVE" class="btn btn-sm <?= $type === 'ACTIVE' ? 'btn-dark' : 'btn-outline' ?>" style="font-size: 0.8rem; padding: 0.3rem 0.75rem; border-radius: 9999px;">
        <i class="fas fa-check-circle" style="color: #10b981;"></i> Active / Verified (<?= number_format($activeCount) ?>)
    </a>
</div>

<!-- Search / Filter Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="advocates.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 0.75rem; align-items: flex-end;">
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
                <option value="">All Plans</option>
                <option value="basic" <?= $plan === 'basic' ? 'selected' : '' ?>>Basic (Public Record)</option>
                <option value="registered" <?= $plan === 'registered' ? 'selected' : '' ?>>Registered</option>
                <option value="verified" <?= $plan === 'verified' ? 'selected' : '' ?>>Verified</option>
            </select>
        </div>
        <div>
            <label class="filter-label">Status / Type</label>
            <select name="type" class="filter-select">
                <option value="">All Statuses</option>
                <option value="PENDING" <?= $type === 'PENDING' ? 'selected' : '' ?>>Pending Verification</option>
                <option value="ACTIVE" <?= $type === 'ACTIVE' ? 'selected' : '' ?>>Active / Approved</option>
                <option value="VERIFIED" <?= $type === 'VERIFIED' ? 'selected' : '' ?>>Verified</option>
                <option value="BLOCK" <?= $type === 'BLOCK' ? 'selected' : '' ?>>Blocked</option>
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
                        $isPendingAdv = strtoupper(trim((string)($adv['type'] ?? ''))) === 'PENDING';
                    ?>
                        <tr style="<?= $isPendingAdv ? 'background: #fffdf5;' : '' ?>">
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
                                <?php if (!empty($adv['bc_verify_url'])): ?>
                                    <div style="margin-top: 3px;">
                                        <a href="<?= sanitize($adv['bc_verify_url']) ?>" target="_blank" rel="noopener noreferrer" class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-size: 0.68rem; text-decoration: none; padding: 2px 5px; display: inline-flex; align-items: center; gap: 3px; font-weight: 700;" title="Verify on <?= sanitize($adv['bc_code'] ?? 'Bar Council') ?> Official Portal (opens in new tab)">
                                            <i class="fas fa-shield-halved"></i> Verify BC <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.55rem;"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?><br>
                                <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                            </td>
                            <td>
                                <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.75rem;">
                                    <i class="fas <?= $badge['icon'] ?? 'fa-id-card' ?>"></i> <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?= renderAdvocateIndexBadge($advIndex, 'compact') ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                    <?php if ($isPendingAdv): ?>
                                        <a href="advocates.php?action=verify&id=<?= $adv['id'] ?>&type=<?= urlencode($type) ?>&page=<?= $page ?>" class="btn btn-primary btn-sm" onclick="return confirm('Manually verify and approve advocate <?= htmlspecialchars(addslashes($adv['name'] ?: '')) ?>?');" style="background: #059669; border-color: #059669; font-weight: 700; padding: 0.3rem 0.6rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Manually Verify & Approve">
                                            <i class="fas fa-circle-check"></i> Verify
                                        </a>
                                    <?php endif; ?>
                                    <?php if (!empty($adv['bc_verify_url'])): ?>
                                        <a href="<?= sanitize($adv['bc_verify_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="color: #dc2626; border-color: #fca5a5; font-size: 0.75rem; padding: 0.3rem 0.5rem;" title="Verify on Bar Council Portal (opens in new tab)">
                                            <i class="fas fa-shield-halved"></i>
                                        </a>
                                    <?php endif; ?>
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
