<?php
// law-college-search.php - Dedicated Law Colleges & Universities Search Portal
require_once __DIR__ . '/config/app.php';

$pageTitle = "Law College Search - 5,000+ BCI Approved Law Colleges in India | My Advocate";
$pageDescription = "Search and explore 5,000+ Bar Council of India (BCI) recognized law colleges, LL.B., 5-Year Integrated B.A. LL.B., and LL.M. universities across India.";

$db = getDB();

// Fetch distinct states from law_college table
$collegeStates = [];
try {
    $stStmt = $db->query("SELECT DISTINCT TRIM(state) as st FROM law_college WHERE state != '' AND (status = 'ACTIVE' OR status = '' OR status IS NULL) ORDER BY st ASC");
    $collegeStates = $stStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $collegeStates = [];
}

$stateQuery = trim(sanitize($_GET['state'] ?? ''));
$nameQuery = trim(sanitize($_GET['name'] ?? $_GET['q'] ?? ''));
$courseQuery = trim(sanitize($_GET['course'] ?? ''));
$idQuery = (int)($_GET['id'] ?? 0);

// Parse legacy link params (e.g. ?link=slug&link=base64(id=...))
$candidateLinks = [];
if (!empty($_SERVER['QUERY_STRING'])) {
    $pairs = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($pairs as $pair) {
        $kv = explode('=', $pair, 2);
        $k = urldecode($kv[0] ?? '');
        $v = urldecode($kv[1] ?? '');
        if (in_array(strtolower($k), ['link', 'l', 'id'])) {
            $candidateLinks[] = $v;
        }
    }
}
foreach (array_unique(array_filter($candidateLinks)) as $lv) {
    $decoded = @base64_decode($lv, true);
    if ($decoded && preg_match('/[a-zA-Z0-9_]+=/', $decoded)) {
        parse_str($decoded, $p);
        if (!empty($p['id'])) $idQuery = (int)$p['id'];
    } elseif (is_numeric($lv) && empty($idQuery)) {
        $idQuery = (int)$lv;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 21;
$offset = ($page - 1) * $perPage;

$whereClauses = ["(status = 'ACTIVE' OR status = '' OR status IS NULL)", "name != ''"];
$params = [];

if (!empty($idQuery)) {
    $whereClauses[] = "id = ?";
    $params[] = $idQuery;
}

if (!empty($stateQuery)) {
    $whereClauses[] = "state LIKE ?";
    $params[] = "%" . $stateQuery . "%";
}

if (!empty($nameQuery)) {
    $whereClauses[] = "(name LIKE ? OR affiliating_university LIKE ? OR course_name LIKE ? OR remarks LIKE ?)";
    $params[] = "%" . $nameQuery . "%";
    $params[] = "%" . $nameQuery . "%";
    $params[] = "%" . $nameQuery . "%";
    $params[] = "%" . $nameQuery . "%";
}

if (!empty($courseQuery)) {
    $whereClauses[] = "course_name LIKE ?";
    $params[] = "%" . $courseQuery . "%";
}

$whereSql = implode(" AND ", $whereClauses);

$countSql = "SELECT COUNT(*) FROM law_college WHERE " . $whereSql;
try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalColleges = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalColleges = 0;
}

$totalPages = max(1, ceil($totalColleges / $perPage));

$sql = "SELECT * FROM law_college WHERE " . $whereSql . " ORDER BY state ASC, name ASC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $colleges = $stmt->fetchAll();
} catch (Exception $e) {
    $colleges = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="law-college-search" style="color: var(--primary); text-decoration: none;">Legal Education</a> &bull; 
        <span>Law College Search</span>
    </nav>

    <!-- Hero / Title Section -->
    <div style="margin-bottom: 2rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #fffbeb; padding: 0.35rem 0.85rem; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 700; color: #92400e; margin-bottom: 0.75rem; border: 1px solid #fde68a;">
            <i class="fas fa-graduation-cap"></i> Bar Council of India (BCI) Recognized Institutions
        </div>
        <h1 style="font-size: 2.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem; line-height: 1.2;">
            Law College <span style="background: linear-gradient(120deg, #c00000, #b45309); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Search Directory</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 850px; line-height: 1.6;">
            Search over 5,000+ BCI-approved law colleges, faculties of law, and universities across India offering 3-Year LL.B., 5-Year Integrated (B.A. LL.B., B.B.A. LL.B.), and LL.M. degree programs.
        </p>
    </div>

    <!-- Filter Card -->
    <div class="stat-box" style="padding: 1.5rem; margin-bottom: 2rem; border-top: 4px solid var(--brand-red);">
        <form action="law-college-search" method="GET" class="search-filter-form">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; align-items: flex-end;">
                <!-- Keyword Input -->
                <div class="filter-group" style="margin-bottom: 0;">
                    <label class="filter-label" style="font-weight: 600;">
                        <i class="fas fa-magnifying-glass text-primary"></i> College Name / University
                    </label>
                    <input type="text" name="name" value="<?= sanitize($nameQuery) ?>" placeholder="e.g. Government Law College, Patna, Delhi..." class="filter-input">
                </div>

                <!-- State Dropdown -->
                <div class="filter-group" style="margin-bottom: 0;">
                    <label class="filter-label" style="font-weight: 600;">
                        <i class="fas fa-map-location-dot" style="color: var(--brand-red);"></i> State
                    </label>
                    <select name="state" class="filter-select">
                        <option value="">All States of India</option>
                        <?php foreach ($collegeStates as $stName): ?>
                            <option value="<?= sanitize($stName) ?>" <?= (stripos($stateQuery, trim($stName)) !== false || $stateQuery === $stName) ? 'selected' : '' ?>>
                                <?= sanitize(trim($stName)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Course Program Filter -->
                <div class="filter-group" style="margin-bottom: 0;">
                    <label class="filter-label" style="font-weight: 600;">
                        <i class="fas fa-book-bookmark" style="color: var(--brand-gold-dark);"></i> Law Course
                    </label>
                    <select name="course" class="filter-select">
                        <option value="">All Law Courses</option>
                        <option value="3 year" <?= $courseQuery === '3 year' ? 'selected' : '' ?>>3-Year LL.B.</option>
                        <option value="5 year" <?= $courseQuery === '5 year' ? 'selected' : '' ?>>5-Year Integrated LL.B. (B.A. / B.B.A.)</option>
                        <option value="LLM" <?= $courseQuery === 'LLM' ? 'selected' : '' ?>>LL.M. (Master of Laws)</option>
                    </select>
                </div>

                <!-- Submit / Clear Buttons -->
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center; height: 42px; font-weight: 600;">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($nameQuery) || !empty($stateQuery) || !empty($courseQuery)): ?>
                        <a href="law-college-search" class="btn btn-outline" style="height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 0 1rem;" title="Reset filters">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Results Header Stats -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
        <div style="font-size: 1rem; font-weight: 700; color: var(--primary);">
            <i class="fas fa-building-columns text-primary" style="margin-right: 4px;"></i>
            Found <span style="color: var(--brand-red);"><?= number_format($totalColleges) ?></span> Law Colleges
            <?php if (!empty($stateQuery)): ?>
                in <span style="color: var(--brand-gold-dark);"><?= sanitize($stateQuery) ?></span>
            <?php endif; ?>
        </div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">
            Page <?= $page ?> of <?= $totalPages ?> (Showing <?= count($colleges) ?> per page)
        </div>
    </div>

    <!-- Colleges Grid -->
    <?php if (!empty($colleges)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">
            <?php foreach ($colleges as $c): ?>
                <?php 
                $cEncoded = base64_encode("id=" . $c['id']);
                $cSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($c['name'])));
                $detailUrl = "law-college?link=law-college-{$cSlug}&link={$cEncoded}";
                ?>
                <div class="act-card" style="display: flex; flex-direction: column; justify-content: space-between; height: 100%; border-top: 3px solid var(--border-color); transition: transform 0.2s ease, box-shadow 0.2s ease;">
                    <div>
                        <!-- Top Metadata Row -->
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; margin-bottom: 0.65rem;">
                            <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.55rem;">
                                <i class="fas fa-shield-halved"></i> BCI Approved
                            </span>
                            <?php if (!empty($c['e_year'])): ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                                    <i class="fas fa-calendar"></i> Est. <?= sanitize($c['e_year']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- College Title -->
                        <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary); line-height: 1.4;">
                            <a href="<?= $detailUrl ?>" style="color: inherit; text-decoration: none;">
                                <?= sanitize(trim($c['name'])) ?>
                            </a>
                        </h3>

                        <!-- State -->
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.75rem; display: flex; align-items: flex-start; gap: 0.4rem;">
                            <i class="fas fa-location-dot" style="color: var(--brand-red); margin-top: 0.2rem; flex-shrink: 0;"></i>
                            <span><?= sanitize(trim($c['state'])) ?></span>
                        </p>

                        <!-- Affiliating University -->
                        <?php if (!empty($c['affiliating_university'])): ?>
                            <div style="font-size: 0.8125rem; color: var(--text-main); margin-bottom: 0.75rem; line-height: 1.4; background: var(--bg-alt); padding: 0.5rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                <i class="fas fa-university" style="color: var(--brand-gold); margin-right: 3px;"></i> 
                                <strong>Affiliation:</strong> <?= sanitize(trim(preg_replace('/\s+/', ' ', $c['affiliating_university']))) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Offered Course Tags -->
                        <?php if (!empty($c['course_name'])): ?>
                            <div style="margin-bottom: 0.75rem;">
                                <span class="practice-pill" style="font-size: 0.75rem; padding: 0.2rem 0.5rem; background: #eff6ff; color: #1e40af; border-color: #bfdbfe;">
                                    <i class="fas fa-graduation-cap"></i> <?= sanitize(trim($c['course_name'])) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bottom Approval & Link Row -->
                    <div style="border-top: 1px solid var(--border-color); padding-top: 0.75rem; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; font-size: 0.8125rem;">
                        <span style="color: var(--text-muted); font-size: 0.75rem;">
                            <?php if (!empty($c['approval_till'])): ?>
                                <i class="fas fa-clock" style="color: #16a34a;"></i> <?= sanitize(trim($c['approval_till'])) ?>
                            <?php else: ?>
                                <i class="fas fa-check-circle" style="color: #16a34a;"></i> Recognized
                            <?php endif; ?>
                        </span>
                        <a href="<?= $detailUrl ?>" class="btn btn-outline-primary btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.75rem;">
                            View Details <i class="fas fa-chevron-right" style="font-size: 0.65rem; margin-left: 2px;"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-top: 2rem;">
                <?php
                $queryParams = $_GET;
                unset($queryParams['page']);
                $baseQuery = http_build_query($queryParams);
                $pageUrl = function($p) use ($baseQuery) {
                    return 'law-college-search?' . ($baseQuery ? $baseQuery . '&' : '') . 'page=' . $p;
                };
                ?>

                <!-- Prev Button -->
                <?php if ($page > 1): ?>
                    <a href="<?= $pageUrl($page - 1) ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <!-- Page numbers window -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1): ?>
                    <a href="<?= $pageUrl(1) ?>" class="btn btn-outline btn-sm">1</a>
                    <?php if ($startPage > 2): ?><span>...</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="<?= $pageUrl($i) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="min-width: 36px; text-align: center;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?><span>...</span><?php endif; ?>
                    <a href="<?= $pageUrl($totalPages) ?>" class="btn btn-outline btn-sm"><?= $totalPages ?></a>
                <?php endif; ?>

                <!-- Next Button -->
                <?php if ($page < $totalPages): ?>
                    <a href="<?= $pageUrl($page + 1) ?>" class="btn btn-outline btn-sm">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="stat-box" style="text-align: center; padding: 3rem 1.5rem; margin-bottom: 2rem;">
            <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;">
                <i class="fas fa-building-columns"></i>
            </div>
            <h3 style="font-size: 1.3rem; color: var(--primary); margin-bottom: 0.5rem;">No Law Colleges Found</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 500px; margin: 0 auto 1.5rem;">
                We couldn't find any law institutions matching your search criteria. Try modifying your state or keyword filters.
            </p>
            <a href="law-college-search" class="btn btn-primary btn-md">
                <i class="fas fa-rotate-left"></i> Reset All Filters
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
