<?php
// acts.php - Central & State Bare Acts Directory (Preserving Legacy Route)
require_once __DIR__ . '/config/app.php';

$pageTitle = "Bare Acts Library - Central & State Acts, BNS, BNSS, BSA";
$pageDescription = "Browse and search complete Indian Bare Acts including the Bharatiya Nyaya Sanhita (BNS), BNSS, BSA, CPC, and Constitution.";

$db = getDB();
$searchQuery = sanitize($_GET['q'] ?? $_GET['name'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$sql = "SELECT id, name, year, english, hindi, official, status FROM acts WHERE status = 'ACTIVE'";
$countSql = "SELECT COUNT(*) FROM acts WHERE status = 'ACTIVE'";
$params = [];

if (!empty($searchQuery)) {
    $sql .= " AND (name LIKE ? OR english LIKE ? OR hindi LIKE ?)";
    $countSql .= " AND (name LIKE ? OR english LIKE ? OR hindi LIKE ?)";
    $params[] = "%" . $searchQuery . "%";
    $params[] = "%" . $searchQuery . "%";
    $params[] = "%" . $searchQuery . "%";
}

try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalActs = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalActs = 0;
}

$totalPages = ceil($totalActs / $perPage);

$sql .= " ORDER BY (CASE WHEN name LIKE '%Bharatiya%' THEN 1 WHEN name LIKE '%Constitution%' THEN 2 ELSE 3 END), name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $acts = $stmt->fetchAll();
} catch (Exception $e) {
    $acts = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Legal Library</span> &bull; <span>Bare Acts</span>
    </nav>

    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
                <i class="fas fa-book-journal-whills" style="color: var(--brand-red);"></i> Indian Bare Acts Library
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9375rem;">
                Search complete text, chapters, and sections of Central Acts, Codes, and New Criminal Legislation (BNS, BNSS, BSA).
            </p>
        </div>

        <!-- Search Form -->
        <form action="acts" method="GET" style="display: flex; gap: 0.5rem; min-width: 320px;">
            <input type="text" name="q" value="<?= sanitize($searchQuery) ?>" placeholder="Search Act by name or keyword..." class="filter-input" style="border-radius: var(--radius-md);">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <!-- Acts Grid -->
    <?php if (!empty($acts)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
            <?php foreach ($acts as $act): 
                $pdfUrl = '';
                if (!empty($act['hindi']) && filter_var($act['hindi'], FILTER_VALIDATE_URL)) {
                    $pdfUrl = $act['hindi'];
                } elseif (!empty($act['english']) && filter_var($act['english'], FILTER_VALIDATE_URL)) {
                    $pdfUrl = $act['english'];
                } elseif (!empty($act['official']) && filter_var($act['official'], FILTER_VALIDATE_URL)) {
                    $pdfUrl = $act['official'];
                }
                $hindiTitle = (!empty($act['hindi']) && !filter_var($act['hindi'], FILTER_VALIDATE_URL)) ? $act['hindi'] : '';
            ?>
                <div class="act-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                            <span class="act-year"><i class="far fa-calendar"></i> <?= sanitize($act['year'] ?: 'Central Act') ?></span>
                            <?php if (stripos($act['name'], 'Bharatiya') !== false): ?>
                                <span class="badge-verification badge-verified">New Law 2023</span>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; line-height: 1.35;">
                            <a href="act-details?id=<?= $act['id'] ?>"><?= sanitize(cleanActName($act['name'])) ?></a>
                        </h3>
                        <?php if (!empty($hindiTitle)): ?>
                            <p style="color: var(--text-muted); font-size: 0.8125rem; margin-bottom: 0.75rem;">
                                <?= sanitize($hindiTitle) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div style="border-top: 1px solid var(--border-color); padding-top: 0.85rem; margin-top: 0.85rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="act-details?id=<?= $act['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-book-open"></i> Read Full Act
                        </a>
                        <?php if (!empty($pdfUrl)): ?>
                            <a href="<?= sanitize($pdfUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-gold btn-sm" title="Download Official Gazette PDF">
                                <i class="fas fa-file-pdf"></i> Official PDF
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.75rem; color: var(--text-light);"><i class="fas fa-file-shield"></i> Official Text</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= renderPagination($page, $totalPages, 'acts', $_GET) ?>
    <?php else: ?>
        <div class="stat-box" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;"><i class="fas fa-book-open-reader"></i></div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No Bare Acts Found</h3>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                No acts matched your search term "<?= sanitize($searchQuery) ?>".
            </p>
            <a href="acts" class="btn btn-primary">View All Acts</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
