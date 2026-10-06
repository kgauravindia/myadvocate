<?php
// admin/advocate_reports.php - Comprehensive Advocate Reports & Special Lists
$pageTitle = "Advocate Reports & Analysis";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$tab = sanitize($_GET['tab'] ?? 'district');
$selectedState = sanitize($_GET['state'] ?? 'BR');

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

// 4. 500 Pending Approval
$pendingAdvocates = [];
if ($tab === 'pending') {
    try {
        $pendingAdvocates = $db->query("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, created_at 
                                       FROM advocate 
                                       WHERE status != 'ACTIVE' OR type = 'PENDING' 
                                       ORDER BY id DESC LIMIT 500")->fetchAll();
    } catch (Exception $e) {}
}

// 5. 500 Recently Registered
$latestAdvocates = [];
if ($tab === 'latest') {
    try {
        $latestAdvocates = $db->query("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, created_at 
                                      FROM advocate 
                                      ORDER BY id DESC LIMIT 500")->fetchAll();
    } catch (Exception $e) {}
}

// 6. 500 Recently Updated
$updatedAdvocates = [];
if ($tab === 'updated') {
    try {
        $updatedAdvocates = $db->query("SELECT id, name, mobile, email, state_code, district_code, e_no, e_year, plan_type, type, updated_at 
                                       FROM advocate 
                                       WHERE updated_at IS NOT NULL 
                                       ORDER BY updated_at DESC LIMIT 500")->fetchAll();
    } catch (Exception $e) {}
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Advocate Reports & Analysis
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Geographic breakdowns, Bar Association analytics, and special batch lists.
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
    <a href="advocate_reports.php?tab=pending" class="btn <?= $tab === 'pending' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-clock"></i> 500 Pending Approval
    </a>
    <a href="advocate_reports.php?tab=latest" class="btn <?= $tab === 'latest' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-user-plus"></i> 500 Latest Registered
    </a>
    <a href="advocate_reports.php?tab=updated" class="btn <?= $tab === 'updated' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
        <i class="fas fa-rotate"></i> 500 Recently Updated
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
                        <th style="width: 70px;">ID</th>
                        <th>Advocate Name</th>
                        <th>Contact</th>
                        <th>Enrollment No</th>
                        <th>State / District</th>
                        <th>Status / Plan</th>
                        <th style="text-align: right; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dataset)): ?>
                        <?php foreach ($dataset as $adv): 
                            $badge = getVerificationBadge($adv);
                        ?>
                            <tr>
                                <td>#<?= $adv['id'] ?></td>
                                <td><strong><a href="advocate_edit.php?id=<?= $adv['id'] ?>"><?= sanitize($adv['name'] ?: 'Advocate') ?></a></strong></td>
                                <td>
                                    <div><i class="fas fa-phone" style="font-size:0.75rem;"></i> <?= sanitize($adv['mobile'] ?: '—') ?></div>
                                    <small style="color: var(--text-muted);"><?= sanitize($adv['email'] ?: '—') ?></small>
                                </td>
                                <td><?= sanitize($adv['e_no'] ?: '—') ?><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?></td>
                                <td>
                                    <?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?><br>
                                    <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                                </td>
                                <td>
                                    <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.725rem;">
                                        <?= $badge['label'] ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                                        <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" title="Login As">
                                            <i class="fas fa-right-to-bracket"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">No records found in this category.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
