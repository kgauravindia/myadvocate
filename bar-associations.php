<?php
// bar-associations.php - Bar Associations Directory
require_once __DIR__ . '/config/app.php';

$pageTitle = "Bar Associations in India - District & High Court Bar Directory";
$pageDescription = "Explore Bar Associations across India, office bearers, court locations, and practicing members.";

$db = getDB();
$states = getStates();

$stateQuery = sanitize($_GET['state'] ?? '');
$nameQuery = sanitize($_GET['name'] ?? $_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM ba WHERE status = 'ACTIVE'";
$countSql = "SELECT COUNT(*) FROM ba WHERE status = 'ACTIVE'";
$params = [];

if (!empty($stateQuery)) {
    $sql .= " AND state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $stateQuery;
}

if (!empty($nameQuery)) {
    $sql .= " AND (name LIKE ? OR address LIKE ?)";
    $countSql .= " AND (name LIKE ? OR address LIKE ?)";
    $params[] = "%" . $nameQuery . "%";
    $params[] = "%" . $nameQuery . "%";
}

try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalBAs = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalBAs = 0;
}

$totalPages = ceil($totalBAs / $perPage);

$sql .= " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $barAssociations = $stmt->fetchAll();
} catch (Exception $e) {
    $barAssociations = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Directory</span> &bull; <span>Bar Associations</span>
    </nav>

    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
                <i class="fas fa-users-rectangle" style="color: var(--brand-red);"></i> Bar Associations Directory
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9375rem;">
                Official bar associations across High Courts, District Courts, and Sub-divisional Civil Courts.
            </p>
        </div>

        <form action="bar-associations" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <input type="text" name="name" value="<?= sanitize($nameQuery) ?>" placeholder="Bar Association name..." class="filter-input" style="min-width: 200px;">
            <select name="state" class="filter-select" style="min-width: 160px;">
                <option value="">All States</option>
                <?php foreach ($states as $code => $name): ?>
                    <option value="<?= sanitize($code) ?>" <?= $stateQuery === $code ? 'selected' : '' ?>><?= sanitize($name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <?php if (!empty($barAssociations)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
            <?php foreach ($barAssociations as $ba): 
                $sName = getStateName($ba['state_code'] ?? '');
                $dName = getDistrictName($ba['district_code'] ?? '');
            ?>
                <div class="act-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span class="badge-verification badge-basic"><i class="fas fa-landmark"></i> Code: <?= sanitize($ba['code'] ?: 'BA') ?></span>
                            <?php if (!empty($ba['year'])): ?>
                                <small style="color: var(--text-muted);">Est. <?= sanitize($ba['year']) ?></small>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary);"><?= sanitize($ba['name']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-location-dot" style="color: var(--brand-accent);"></i> <?= sanitize($dName ?: $ba['address'] ?: 'Court Complex') ?><?= $sName ? ', ' . sanitize($sName) : '' ?>
                        </p>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                        <a href="advocate-search-result?ba_code=<?= urlencode($ba['code']) ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-users"></i> Practicing Advocates
                        </a>
                        <?php if (!empty($ba['email'])): ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-envelope"></i> Verified Contact</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= renderPagination($page, $totalPages, 'bar-associations', $_GET) ?>
    <?php else: ?>
        <div class="stat-box" style="text-align: center; padding: 4rem 2rem;">
            <p>No bar associations found matching your criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
