<?php
// admin/advocate_reports.php - Comprehensive Advocate Reports & Special Lists
$pageTitle = "Advocate Reports & Analysis";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$tab = sanitize($_GET['tab'] ?? 'district');
$selectedState = sanitize($_GET['state'] ?? 'BR');

// Quick Approve Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_approve') {
    $approveId = (int)($_POST['advocate_id'] ?? 0);
    if ($approveId > 0) {
        try {
            $upStmt = $db->prepare("UPDATE advocate SET type = 'ACTIVE', status = 'ACTIVE', updated_at = NOW() WHERE id = ?");
            $upStmt->execute([$approveId]);

            // Dispatch Approval SMS
            $smsResult = sendAdvocateProfileApprovedSMS($approveId);

            $redirParams = $_GET;
            $redirParams['approved'] = 1;
            if (!empty($smsResult['success'])) {
                $redirParams['sms_sent'] = 1;
            }
            header("Location: advocate_reports.php?" . http_build_query($redirParams));
            exit;
        } catch (Exception $e) {}
    }
}

// Global counts for tabs and filters
$cntPendingTotal = 0;
$cntPendingType = 0;
$cntPendingProof = 0;
$cntLatestProof = 0;
try {
    $cntPendingTotal = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE status != 'ACTIVE' OR type = 'PENDING' OR status = 'PENDING'")->fetchColumn();
    $cntPendingType = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type = 'PENDING'")->fetchColumn();
    $cntPendingProof = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE (status != 'ACTIVE' OR type = 'PENDING' OR status = 'PENDING') AND (id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0')")->fetchColumn();
    $cntLatestProof = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0'")->fetchColumn();
} catch (Exception $e) {}

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(10, min(100, (int)($_GET['per_page'] ?? 25)));
$offset = ($page - 1) * $perPage;
$totalRecords = 0;
$totalPages = 1;

// 1. District Wise Breakdown
$districtStats = [];
if ($tab === 'district') {
    try {
        $stmt = $db->prepare("SELECT d.code, d.name, COUNT(a.id) as total_advocates 
                              FROM district d 
                              LEFT JOIN advocate a ON d.code = a.district_code 
                              WHERE d.state_code = ? 
                              GROUP BY d.code, d.name 
                              ORDER BY total_advocates DESC, d.name ASC");
        $stmt->execute([$selectedState]);
        $districtStats = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// 2. State Wise Breakdown
$stateStats = [];
if ($tab === 'state') {
    try {
        $stateStats = $db->query("SELECT s.code, s.name, COUNT(a.id) as total_advocates 
                                  FROM state s 
                                  LEFT JOIN advocate a ON s.code = a.state_code 
                                  GROUP BY s.code, s.name 
                                  ORDER BY total_advocates DESC, s.name ASC")->fetchAll();
    } catch (Exception $e) {}
}

// 3. Bar Association Wise Breakdown
$baStats = [];
if ($tab === 'ba') {
    try {
        $stmt = $db->prepare("SELECT ba.code, ba.name, ba.state_code, COUNT(a.id) as total_advocates 
                              FROM ba 
                              LEFT JOIN advocate a ON ba.code = a.ba_code 
                              WHERE ba.state_code = ? 
                              GROUP BY ba.code, ba.name, ba.state_code 
                              ORDER BY total_advocates DESC, ba.name ASC");
        $stmt->execute([$selectedState]);
        $baStats = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Common filter inputs for list tabs
$subFilter = sanitize($_GET['filter'] ?? 'all');
$hasProof = !empty($_GET['proof']);
$searchQuery = sanitize($_GET['q'] ?? '');

// 4. Pending Approval with Pagination
$pendingAdvocates = [];
if ($tab === 'pending') {
    try {
        $where = "WHERE (status != 'ACTIVE' OR type = 'PENDING' OR status = 'PENDING')";
        $params = [];

        if ($subFilter === 'type_pending') {
            $where = "WHERE type = 'PENDING'";
        }

        if ($hasProof) {
            $where .= " AND (id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0')";
        }

        if (!empty($searchQuery)) {
            if (is_numeric($searchQuery) && strlen($searchQuery) >= 10) {
                $where .= " AND mobile LIKE ?";
                $params[] = "%" . $searchQuery . "%";
            } elseif (is_numeric($searchQuery) && strlen($searchQuery) <= 6) {
                $where .= " AND e_no = ?";
                $params[] = $searchQuery;
            } else {
                $where .= " AND (name LIKE ? OR email LIKE ? OR e_no LIKE ?)";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
            }
        }

        $cntStmt = $db->prepare("SELECT COUNT(*) FROM advocate $where");
        $cntStmt->execute($params);
        $totalRecords = (int)$cntStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $dataStmt = $db->prepare("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, status, id_proof, photo, created_at, updated_at 
                                  FROM advocate 
                                  $where 
                                  ORDER BY (CASE WHEN type = 'PENDING' THEN 1 ELSE 2 END), id DESC 
                                  LIMIT $perPage OFFSET $offset");
        $dataStmt->execute($params);
        $pendingAdvocates = $dataStmt->fetchAll();
    } catch (Exception $e) {
        $pendingAdvocates = [];
    }
}

// 5. Latest Registered with Pagination
$latestAdvocates = [];
if ($tab === 'latest') {
    try {
        $where = "WHERE 1=1";
        $params = [];

        if ($hasProof) {
            $where .= " AND (id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0')";
        }

        if (!empty($searchQuery)) {
            if (is_numeric($searchQuery) && strlen($searchQuery) >= 10) {
                $where .= " AND mobile LIKE ?";
                $params[] = "%" . $searchQuery . "%";
            } elseif (is_numeric($searchQuery) && strlen($searchQuery) <= 6) {
                $where .= " AND e_no = ?";
                $params[] = $searchQuery;
            } else {
                $where .= " AND (name LIKE ? OR email LIKE ? OR e_no LIKE ?)";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
            }
        }

        $cntStmt = $db->prepare("SELECT COUNT(*) FROM advocate $where");
        $cntStmt->execute($params);
        $totalRecords = (int)$cntStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $dataStmt = $db->prepare("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, status, id_proof, photo, created_at, updated_at 
                                  FROM advocate 
                                  $where 
                                  ORDER BY id DESC 
                                  LIMIT $perPage OFFSET $offset");
        $dataStmt->execute($params);
        $latestAdvocates = $dataStmt->fetchAll();
    } catch (Exception $e) {
        $latestAdvocates = [];
    }
}

// 6. Recently Updated with Pagination
$updatedAdvocates = [];
if ($tab === 'updated') {
    try {
        $where = "WHERE updated_at IS NOT NULL";
        $params = [];

        if ($hasProof) {
            $where .= " AND (id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0')";
        }

        if (!empty($searchQuery)) {
            if (is_numeric($searchQuery) && strlen($searchQuery) >= 10) {
                $where .= " AND mobile LIKE ?";
                $params[] = "%" . $searchQuery . "%";
            } elseif (is_numeric($searchQuery) && strlen($searchQuery) <= 6) {
                $where .= " AND e_no = ?";
                $params[] = $searchQuery;
            } else {
                $where .= " AND (name LIKE ? OR email LIKE ? OR e_no LIKE ?)";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
                $params[] = "%" . $searchQuery . "%";
            }
        }

        $cntStmt = $db->prepare("SELECT COUNT(*) FROM advocate $where");
        $cntStmt->execute($params);
        $totalRecords = (int)$cntStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $dataStmt = $db->prepare("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, status, id_proof, photo, created_at, updated_at 
                                  FROM advocate 
                                  $where 
                                  ORDER BY updated_at DESC 
                                  LIMIT $perPage OFFSET $offset");
        $dataStmt->execute($params);
        $updatedAdvocates = $dataStmt->fetchAll();
    } catch (Exception $e) {
        $updatedAdvocates = [];
    }
}
?>

<?php if (isset($_GET['approved'])): ?>
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.875rem;">
            <i class="fas fa-circle-check" style="color: #10b981; font-size: 1.1rem;"></i>
            Advocate approved successfully! Profile is now ACTIVE in directory listings.
            <?php if (isset($_GET['sms_sent'])): ?>
                <span class="badge" style="background: #10b981; color: white; font-size: 0.725rem; padding: 0.2rem 0.5rem; border-radius: 4px; margin-left: 0.5rem; font-weight: 700;">
                    <i class="fas fa-paper-plane me-1"></i> SMS Dispatched
                </span>
            <?php endif; ?>
        </div>
        <a href="advocate_reports.php?tab=<?= urlencode($tab) ?>" style="color: #065f46; text-decoration: none; font-size: 0.8125rem;">Dismiss &times;</a>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Advocate Reports & Analysis
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Geographic breakdowns, Bar Association analytics, special batch lists, and ID proof verification.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="advocate_upload.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-import"></i> Bulk Upload CSV</a>
        <a href="advocates.php" class="btn btn-primary btn-sm"><i class="fas fa-users"></i> All Advocates</a>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 4px;">
    <a href="advocate_reports.php?tab=district&state=<?= urlencode($selectedState) ?>" class="btn <?= $tab === 'district' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-location-dot"></i> District Wise
    </a>
    <a href="advocate_reports.php?tab=state" class="btn <?= $tab === 'state' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-map"></i> State Wise
    </a>
    <a href="advocate_reports.php?tab=ba&state=<?= urlencode($selectedState) ?>" class="btn <?= $tab === 'ba' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-landmark"></i> Bar Association Wise
    </a>
    <a href="advocate_reports.php?tab=pending" class="btn <?= $tab === 'pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
        <i class="fas fa-clock"></i> All Pending Approval
        <span class="badge" style="background: rgba(225, 29, 72, 0.15); color: #e11d48; font-size: 0.7rem; padding: 0.15rem 0.45rem; border-radius: 9999px; font-weight: 700;">
            <?= number_format($cntPendingTotal) ?>
        </span>
    </a>
    <a href="advocate_reports.php?tab=latest" class="btn <?= $tab === 'latest' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-user-plus"></i> Latest Registered
    </a>
    <a href="advocate_reports.php?tab=updated" class="btn <?= $tab === 'updated' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-rotate"></i> Recently Updated
    </a>
</div>

<?php if (in_array($tab, ['district', 'ba'])): ?>
    <!-- State Switcher Filter -->
    <div class="stat-box" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <form action="advocate_reports.php" method="GET" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="<?= sanitize($tab) ?>">
            <label class="filter-label" style="margin: 0; font-weight: 700;">Select State / Bar Council:</label>
            <select name="state" class="filter-select" style="max-width: 300px;" onchange="this.form.submit()">
                <?php foreach ($states as $sCode => $sName): ?>
                    <option value="<?= sanitize($sCode) ?>" <?= $selectedState === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">View Report</button>
        </form>
    </div>
<?php endif; ?>

<?php if (in_array($tab, ['pending', 'latest', 'updated'])): ?>
    <!-- Filter Bar for List Tabs -->
    <div class="stat-box" style="margin-bottom: 1.25rem; padding: 1rem 1.25rem;">
        <form action="advocate_reports.php" method="GET" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="<?= sanitize($tab) ?>">

            <!-- Quick Filter Pills -->
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <?php if ($tab === 'pending'): ?>
                    <a href="advocate_reports.php?tab=pending&filter=all<?= $hasProof ? '&proof=1' : '' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="btn <?= $subFilter === 'all' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="font-size: 0.775rem;">
                        All Pending (<?= number_format($cntPendingTotal) ?>)
                    </a>
                    <a href="advocate_reports.php?tab=pending&filter=type_pending<?= $hasProof ? '&proof=1' : '' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="btn <?= $subFilter === 'type_pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="font-size: 0.775rem;">
                        <i class="fas fa-hourglass-half me-1"></i> New Registrations (<?= number_format($cntPendingType) ?>)
                    </a>
                    <a href="advocate_reports.php?tab=pending&filter=<?= urlencode($subFilter) ?>&proof=<?= $hasProof ? '0' : '1' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="btn <?= $hasProof ? 'btn-success' : 'btn-outline' ?> btn-sm" style="font-size: 0.775rem;">
                        <i class="fas fa-id-card me-1"></i> ID Proof Uploaded (<?= number_format($cntPendingProof) ?>)
                    </a>
                <?php elseif ($tab === 'latest'): ?>
                    <a href="advocate_reports.php?tab=latest&proof=<?= $hasProof ? '0' : '1' ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="btn <?= $hasProof ? 'btn-success' : 'btn-outline' ?> btn-sm" style="font-size: 0.775rem;">
                        <i class="fas fa-id-card me-1"></i> ID Proof Uploaded (<?= number_format($cntLatestProof) ?>)
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search and Per-Page Input -->
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin-left: auto;">
                <?php if ($subFilter !== 'all'): ?>
                    <input type="hidden" name="filter" value="<?= sanitize($subFilter) ?>">
                <?php endif; ?>
                <?php if ($hasProof): ?>
                    <input type="hidden" name="proof" value="1">
                <?php endif; ?>

                <div style="position: relative;">
                    <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" placeholder="Search name, phone, enrollment..." class="filter-input" style="min-width: 260px; padding-left: 2rem; font-size: 0.8125rem; min-height: 36px;">
                    <i class="fas fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
                </div>

                <select name="per_page" class="filter-select" style="width: auto; min-height: 36px; font-size: 0.8125rem;" onchange="this.form.submit()">
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / page</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / page</option>
                    <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100 / page</option>
                </select>

                <button type="submit" class="btn btn-primary btn-sm" style="min-height: 36px;">Search</button>
                <?php if (!empty($searchQuery) || $hasProof || $subFilter !== 'all'): ?>
                    <a href="advocate_reports.php?tab=<?= urlencode($tab) ?>" class="btn btn-outline btn-sm" style="min-height: 36px;" title="Reset filters">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div style="margin-top: 0.75rem; font-size: 0.8125rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                Showing <strong><?= number_format(min($totalRecords, $offset + 1)) ?></strong>–<strong><?= number_format(min($totalRecords, $offset + $perPage)) ?></strong> of <strong><?= number_format($totalRecords) ?></strong> records
                <?php if ($hasProof): ?><span class="badge" style="background: #dcfce7; color: #166534; font-size: 0.7rem; margin-left: 0.5rem;"><i class="fas fa-filter"></i> ID Proof Only</span><?php endif; ?>
                <?php if (!empty($searchQuery)): ?><span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.7rem; margin-left: 0.5rem;">Keyword: "<?= sanitize($searchQuery) ?>"</span><?php endif; ?>
            </div>
            <div>Page <?= $page ?> of <?= $totalPages ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Content Table -->
<div class="admin-card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive" style="border: none;">
        <?php if ($tab === 'district'): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Code</th>
                        <th>District Name</th>
                        <th>State</th>
                        <th style="text-align: right;">Advocates Count</th>
                        <th style="text-align: right; width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($districtStats)): ?>
                        <?php foreach ($districtStats as $d): ?>
                            <tr>
                                <td><code><?= sanitize($d['code']) ?></code></td>
                                <td><strong><?= sanitize($d['name']) ?></strong></td>
                                <td><?= sanitize(getStateName($selectedState)) ?></td>
                                <td style="text-align: right; font-weight: 800; color: var(--brand-red); font-size: 1rem;">
                                    <?= number_format($d['total_advocates']) ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="advocates.php?state=<?= urlencode($selectedState) ?>&q=<?= urlencode($d['name']) ?>" class="btn btn-outline btn-sm">
                                        View Advocates &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">No district records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'state'): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Code</th>
                        <th>State / UT Name</th>
                        <th style="text-align: right;">Total Advocates</th>
                        <th style="text-align: right; width: 180px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stateStats as $s): ?>
                        <tr>
                            <td><code><?= sanitize($s['code']) ?></code></td>
                            <td><strong><?= sanitize($s['name']) ?></strong></td>
                            <td style="text-align: right; font-weight: 800; color: var(--brand-red); font-size: 1rem;">
                                <?= number_format($s['total_advocates']) ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="advocate_reports.php?tab=district&state=<?= urlencode($s['code']) ?>" class="btn btn-outline-primary btn-sm">
                                    Districts &rarr;
                                </a>
                                <a href="advocates.php?state=<?= urlencode($s['code']) ?>" class="btn btn-outline btn-sm">
                                    Browse &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'ba'): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 100px;">BA Code</th>
                        <th>Bar Association Name</th>
                        <th>State</th>
                        <th style="text-align: right;">Advocates Count</th>
                        <th style="text-align: right; width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($baStats)): ?>
                        <?php foreach ($baStats as $b): ?>
                            <tr>
                                <td><code><?= sanitize($b['code']) ?></code></td>
                                <td><strong><?= sanitize($b['name']) ?></strong></td>
                                <td><?= sanitize(getStateName($b['state_code'])) ?></td>
                                <td style="text-align: right; font-weight: 800; color: var(--brand-gold-dark); font-size: 1rem;">
                                    <?= number_format($b['total_advocates']) ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="advocates.php?state=<?= urlencode($b['state_code']) ?>&q=<?= urlencode($b['name']) ?>" class="btn btn-outline btn-sm">
                                        View Advocates &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">No bar associations found for this state.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif (in_array($tab, ['pending', 'latest', 'updated'])): 
            $dataset = ($tab === 'pending') ? $pendingAdvocates : (($tab === 'latest') ? $latestAdvocates : $updatedAdvocates);
        ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 75px;">ID</th>
                        <th>Advocate Details</th>
                        <th>Contact</th>
                        <th>Enrollment No</th>
                        <th>Location</th>
                        <th>Status / Type</th>
                        <th style="min-width: 150px;">ID Proof</th>
                        <th style="text-align: right; width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dataset)): ?>
                        <?php foreach ($dataset as $adv): 
                            $badge = getVerificationBadge($adv);
                            $rawProof = trim((string)($adv['id_proof'] ?? ''));
                            $hasDoc = (!empty($rawProof) && $rawProof !== '0' && strtolower($rawProof) !== 'null');
                        ?>
                            <tr>
                                <td>
                                    <strong>#<?= $adv['id'] ?></strong>
                                    <?php if (!empty($adv['created_at'])): ?>
                                        <div style="font-size: 0.675rem; color: var(--text-muted); margin-top: 2px;">
                                            <?= date('d M Y', strtotime($adv['created_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><a href="advocate_edit.php?id=<?= $adv['id'] ?>"><?= sanitize($adv['name'] ?: 'Advocate') ?></a></strong>
                                    <?php if (!empty($adv['plan_type'])): ?>
                                        <div style="font-size: 0.725rem; color: var(--text-muted); text-transform: capitalize;">
                                            Plan: <?= sanitize($adv['plan_type']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-phone" style="font-size:0.75rem; color: var(--text-muted);"></i>
                                        <span><?= sanitize($adv['mobile'] ?: '—') ?></span>
                                    </div>
                                    <small style="color: var(--text-muted); display: block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= sanitize($adv['email']) ?>">
                                        <?= sanitize($adv['email'] ?: '—') ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?= sanitize($adv['e_no'] ?: '—') ?></strong><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>
                                </td>
                                <td>
                                    <?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?><br>
                                    <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 3px; align-items: flex-start;">
                                        <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.725rem;">
                                            <?= $badge['label'] ?>
                                        </span>
                                        <?php if (($adv['type'] ?? '') === 'PENDING'): ?>
                                            <span class="badge" style="background: #fee2e2; color: #b91c1c; font-size: 0.675rem; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 700;">
                                                <i class="fas fa-clock"></i> Type: PENDING
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($adv['status']) && $adv['status'] !== 'ACTIVE'): ?>
                                            <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.675rem; padding: 0.15rem 0.4rem; border-radius: 4px;">
                                                Status: <?= sanitize($adv['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <!-- ID Proof Quick Access -->
                                    <?php if ($hasDoc): 
                                        $proofUrl = getAdvocateIdProofUrl($rawProof);
                                        $ext = strtolower(pathinfo($rawProof, PATHINFO_EXTENSION));
                                        $isPdf = ($ext === 'pdf');
                                    ?>
                                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                            <?php if ($isPdf): ?>
                                                <a href="../<?= sanitize($proofUrl) ?>" target="_blank" class="btn btn-outline-danger btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600;" title="Open PDF in new tab">
                                                    <i class="fas fa-file-pdf"></i> View PDF
                                                </a>
                                            <?php else: ?>
                                                <div style="display: flex; align-items: center; gap: 4px;">
                                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="openProofModal('../<?= sanitize($proofUrl) ?>', '<?= sanitize(addslashes($adv['name'] ?: 'Advocate')) ?>', '<?= sanitize(addslashes($adv['e_no'] ?: '')) ?><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>')" style="font-size: 0.75rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 600;" title="Quick Preview ID Card">
                                                        <i class="fas fa-id-card"></i> Preview
                                                    </button>
                                                    <a href="../<?= sanitize($proofUrl) ?>" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.725rem; padding: 0.25rem 0.45rem;" title="Open full image in new tab">
                                                        <i class="fas fa-arrow-up-right-from-square"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <span style="font-size: 0.675rem; color: var(--text-muted); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= sanitize($rawProof) ?>">
                                                <?= sanitize($rawProof) ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-circle-minus" style="color: #cbd5e1;"></i> None
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.35rem; justify-content: flex-end; align-items: center;">
                                        <?php if (($adv['type'] ?? '') === 'PENDING' || ($adv['status'] ?? '') !== 'ACTIVE'): ?>
                                            <form method="POST" style="margin: 0; display: inline-block;">
                                                <input type="hidden" name="action" value="quick_approve">
                                                <input type="hidden" name="advocate_id" value="<?= (int)$adv['id'] ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Quick Approve as ACTIVE" onclick="return confirm('Approve Advocate <?= sanitize(addslashes($adv['name'])) ?> and set type/status to ACTIVE?');" style="padding: 0.3rem 0.5rem; font-size: 0.75rem;">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" title="Edit Profile">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" title="Login As Advocate">
                                            <i class="fas fa-right-to-bracket"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; padding: 3rem; color: var(--text-muted);">No records found in this category.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination for List Tabs -->
<?php if (in_array($tab, ['pending', 'latest', 'updated']) && $totalPages > 1): ?>
    <div style="margin-top: 1.5rem;">
        <?= renderPagination($page, $totalPages, 'advocate_reports.php', $_GET) ?>
    </div>
<?php endif; ?>

<!-- ID Proof Lightbox Modal -->
<div id="proofModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; max-width: 650px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <div>
                <h4 id="proofModalTitle" style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--primary);">Advocate ID Proof</h4>
                <div id="proofModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;"></div>
            </div>
            <button type="button" onclick="closeProofModal()" style="background: none; border: none; font-size: 1.35rem; cursor: pointer; color: #64748b; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 1.25rem; overflow: auto; text-align: center; flex: 1; background: #0f172a; display: flex; align-items: center; justify-content: center;">
            <img id="proofModalImg" src="" alt="ID Proof" style="max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5);">
        </div>
        <div style="padding: 0.75rem 1.25rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <a id="proofModalOpenBtn" href="#" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-up-right-from-square me-1"></i> Open Full Image
            </a>
            <button type="button" onclick="closeProofModal()" class="btn btn-outline btn-sm">Close</button>
        </div>
    </div>
</div>

<script>
function openProofModal(url, name, eno) {
    var modal = document.getElementById('proofModal');
    document.getElementById('proofModalImg').src = url;
    document.getElementById('proofModalTitle').innerText = name || 'Advocate ID Proof';
    document.getElementById('proofModalSubtitle').innerText = eno ? 'Enrollment: ' + eno : '';
    document.getElementById('proofModalOpenBtn').href = url;
    modal.style.display = 'flex';
}
function closeProofModal() {
    var modal = document.getElementById('proofModal');
    modal.style.display = 'none';
    document.getElementById('proofModalImg').src = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeProofModal();
});
document.getElementById('proofModal').addEventListener('click', function(e) {
    if (e.target === this) closeProofModal();
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
