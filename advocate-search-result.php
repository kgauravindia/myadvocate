<?php
// advocate-search-result.php - Unified Advocate Directory & Search Engine
require_once __DIR__ . '/config/app.php';

$pageTitle = "Find Advocates - Directory Search";
$pageDescription = "Search verified advocate profiles, enrollment numbers, courts, and practice specializations across India.";

$db = getDB();
$states = getStates();

// Query Parameters (Supporting legacy & modern GET/POST names)
$nameQuery = sanitize($_GET['name'] ?? $_POST['name'] ?? $_GET['q'] ?? $_GET['keyword'] ?? '');
$mobileQuery = sanitize($_GET['mobile'] ?? $_POST['mobile'] ?? $_GET['phone'] ?? '');
$stateCode = sanitize($_GET['state'] ?? $_GET['state_code'] ?? $_POST['state_code'] ?? '');
$districtCode = sanitize($_GET['district'] ?? $_GET['district_code'] ?? $_POST['district_code'] ?? '');
$courtCode = sanitize($_GET['court'] ?? $_POST['court'] ?? '');
$yearQuery = sanitize($_GET['year'] ?? $_GET['e_year'] ?? $_POST['year'] ?? '');
$enrQuery = sanitize($_GET['enr'] ?? $_GET['e_no'] ?? '');
$practiceArea = sanitize($_GET['practice_area'] ?? $_GET['specialization'] ?? '');
$baCode = sanitize($_GET['ba_code'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 18;
$offset = ($page - 1) * $perPage;

// Build Dynamic SQL Query
$sql = "SELECT id, name, photo, mobile, mobile_visibility, email, email_visibility, state_code, district_code, e_no, e_year, court, ba_code, practice_area, public_url, plan_type, type, premium_member FROM advocate WHERE status = 'ACTIVE'";
$countSql = "SELECT COUNT(*) FROM advocate WHERE status = 'ACTIVE'";
$params = [];

if (!empty($mobileQuery)) {
    $cleanMobile = preg_replace('/\D/', '', $mobileQuery);
    if (!empty($cleanMobile)) {
        $sql .= " AND mobile LIKE ?";
        $countSql .= " AND mobile LIKE ?";
        $params[] = "%" . $cleanMobile . "%";
    }
}

if (!empty($nameQuery)) {
    // Check if user entered an enrollment number or mobile or text name
    if (is_numeric($nameQuery) && strlen($nameQuery) >= 10) {
        $sql .= " AND mobile LIKE ?";
        $countSql .= " AND mobile LIKE ?";
        $params[] = "%" . $nameQuery . "%";
    } elseif (is_numeric($nameQuery) && strlen($nameQuery) <= 6) {
        $sql .= " AND e_no = ?";
        $countSql .= " AND e_no = ?";
        $params[] = $nameQuery;
    } else {
        $sql .= " AND (name LIKE ? OR practice_area LIKE ? OR court LIKE ?)";
        $countSql .= " AND (name LIKE ? OR practice_area LIKE ? OR court LIKE ?)";
        $params[] = "%" . $nameQuery . "%";
        $params[] = "%" . $nameQuery . "%";
        $params[] = "%" . $nameQuery . "%";
    }
}

if (!empty($stateCode)) {
    $sql .= " AND state_code = ?";
    $countSql .= " AND state_code = ?";
    $params[] = $stateCode;
}

if (!empty($districtCode)) {
    $sql .= " AND district_code = ?";
    $countSql .= " AND district_code = ?";
    $params[] = $districtCode;
}

if (!empty($courtCode)) {
    $sql .= " AND court LIKE ?";
    $countSql .= " AND court LIKE ?";
    $params[] = "%" . $courtCode . "%";
}

if (!empty($yearQuery)) {
    $sql .= " AND e_year = ?";
    $countSql .= " AND e_year = ?";
    $params[] = $yearQuery;
}

if (!empty($enrQuery)) {
    $sql .= " AND e_no = ?";
    $countSql .= " AND e_no = ?";
    $params[] = $enrQuery;
}

if (!empty($practiceArea)) {
    $sql .= " AND practice_area LIKE ?";
    $countSql .= " AND practice_area LIKE ?";
    $params[] = "%" . $practiceArea . "%";
}

if (!empty($baCode)) {
    $sql .= " AND ba_code = ?";
    $countSql .= " AND ba_code = ?";
    $params[] = $baCode;
}

// Execute Count
try {
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    $totalResults = 0;
}

$totalPages = max(1, ceil($totalResults / $perPage));

// Execute Paginated Fetch
$sql .= " ORDER BY premium_member DESC, (CASE WHEN public_url IS NOT NULL THEN 1 ELSE 0 END) DESC, id DESC LIMIT $perPage OFFSET $offset";
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $advocates = $stmt->fetchAll();
} catch (Exception $e) {
    $advocates = [];
}

// Load districts for selected state
$districts = !empty($stateCode) ? getDistrictsByState($stateCode) : [];

// Popular years for quick filtering
$popularYears = [2026, 2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2015, 2010, 2005, 2000, 1998, 1995, 1990];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs & Header -->
    <div style="margin-bottom: 1.5rem;">
        <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.5rem;">
            <a href="./">Home</a> &bull; <a href="advocate-search-result">Advocate Directory</a>
            <?php if (!empty($stateCode)): ?> &bull; <span><?= sanitize(getStateName($stateCode)) ?></span><?php endif; ?>
            <?php if (!empty($districtCode)): ?> &bull; <span><?= sanitize(getDistrictName($districtCode)) ?></span><?php endif; ?>
            <?php if (!empty($yearQuery)): ?> &bull; <span>Enrolled in <?= sanitize($yearQuery) ?></span><?php endif; ?>
        </nav>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin: 0 0 0.35rem 0;">
                    Advocate Directory &bull; <?= number_format($totalResults) ?> Records Found
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9375rem; margin: 0;">
                    Search and filter verified advocates by State, District, Court, Specialization, or Enrollment Year.
                </p>
            </div>
        </div>
    </div>

    <!-- Quick Year Navigation Filter Bar -->
    <div class="stat-box" style="padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; font-weight: 700; color: var(--text-main);">
                <i class="fas fa-calendar-days" style="color: var(--brand-red);"></i>
                <span>Filter by Year:</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center;">
                <?php foreach ($popularYears as $yr): 
                    $yrActive = ($yearQuery == (string)$yr);
                    $yrParams = $_GET;
                    if ($yrActive) {
                        unset($yrParams['year'], $yrParams['e_year'], $yrParams['page']);
                    } else {
                        $yrParams['year'] = $yr;
                        unset($yrParams['page']);
                    }
                    $yrUrl = 'advocate-search-result' . (!empty($yrParams) ? '?' . http_build_query($yrParams) : '');
                ?>
                    <a href="<?= $yrUrl ?>" class="practice-pill <?= $yrActive ? 'active' : '' ?>" style="text-decoration: none; font-size: 0.75rem; padding: 0.25rem 0.65rem; <?= $yrActive ? 'background: var(--brand-red); color: #fff; border-color: var(--brand-red); font-weight: 700;' : '' ?>">
                        <?= $yr ?> <?= $yrActive ? '<i class="fas fa-times" style="margin-left: 2px;"></i>' : '' ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Active Filters Display -->
    <?php 
    $activeFilters = [];
    if (!empty($nameQuery)) $activeFilters['name'] = ['label' => 'Keyword: "' . $nameQuery . '"', 'key' => 'name'];
    if (!empty($mobileQuery)) $activeFilters['mobile'] = ['label' => 'Mobile: ' . $mobileQuery, 'key' => 'mobile'];
    if (!empty($stateCode)) $activeFilters['state'] = ['label' => 'State: ' . getStateName($stateCode), 'key' => 'state'];
    if (!empty($districtCode)) $activeFilters['district'] = ['label' => 'District: ' . getDistrictName($districtCode), 'key' => 'district'];
    if (!empty($courtCode)) $activeFilters['court'] = ['label' => 'Court: ' . getCourtName($courtCode), 'key' => 'court'];
    if (!empty($yearQuery)) $activeFilters['year'] = ['label' => 'Year: ' . $yearQuery, 'key' => 'year'];
    if (!empty($practiceArea)) $activeFilters['practice_area'] = ['label' => 'Area: ' . $practiceArea, 'key' => 'practice_area'];
    ?>
    <?php if (!empty($activeFilters)): ?>
        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem;">
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Filters:</span>
            <?php foreach ($activeFilters as $fKey => $fInfo): 
                $fParams = $_GET;
                unset($fParams[$fInfo['key']], $fParams['page']);
                if ($fInfo['key'] === 'state') unset($fParams['state_code']);
                if ($fInfo['key'] === 'district') unset($fParams['district_code']);
                if ($fInfo['key'] === 'year') unset($fParams['e_year']);
                if ($fInfo['key'] === 'mobile') unset($fParams['phone']);
                $fUrl = 'advocate-search-result' . (!empty($fParams) ? '?' . http_build_query($fParams) : '');
            ?>
                <a href="<?= $fUrl ?>" style="display: inline-flex; align-items: center; gap: 0.35rem; background: #fee2e2; color: var(--brand-red); font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: var(--radius-full); text-decoration: none; border: 1px solid #fca5a5;">
                    <?= sanitize($fInfo['label']) ?> <i class="fas fa-times-circle"></i>
                </a>
            <?php endforeach; ?>
            <a href="advocate-search-result" style="font-size: 0.75rem; color: var(--brand-red); text-decoration: underline; font-weight: 600; margin-left: 0.5rem;">Clear All</a>
        </div>
    <?php endif; ?>

    <div class="search-layout">
        <!-- Faceted Sidebar Filter -->
        <aside>
            <button type="button" class="btn btn-outline btn-sm mobile-filter-toggle" style="display: none; width: 100%; margin-bottom: 1rem; justify-content: center; height: 44px; font-weight: 700; border-color: var(--brand-red); color: var(--brand-red);">
                <i class="fas fa-sliders"></i> Filter Advocates
            </button>

            <form action="advocate-search-result" method="GET" class="filter-sidebar filter-sidebar-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary);"><i class="fas fa-sliders"></i> Filter Results</h3>
                    <a href="advocate-search-result" style="font-size: 0.75rem; color: var(--brand-red); font-weight: 600;">Reset All</a>
                </div>

                <!-- Name / Keyword Filter -->
                <div class="filter-group">
                    <label class="filter-label">Keyword / Name</label>
                    <input type="text" name="name" class="filter-input" value="<?= sanitize($nameQuery) ?>" placeholder="e.g. Kumar, Verma, Tax...">
                </div>

                <!-- Mobile Number Filter -->
                <div class="filter-group">
                    <label class="filter-label">Mobile Number</label>
                    <input type="text" name="mobile" class="filter-input" value="<?= sanitize($mobileQuery) ?>" placeholder="e.g. 9876543210">
                </div>

                <!-- State Filter -->
                <div class="filter-group">
                    <label class="filter-label">State Bar Council</label>
                    <select name="state" class="filter-select state-cascade" data-target="#district_select">
                        <option value="">All States</option>
                        <?php foreach ($states as $code => $name): ?>
                            <option value="<?= sanitize($code) ?>" <?= $stateCode === $code ? 'selected' : '' ?>><?= sanitize($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- District Filter -->
                <div class="filter-group">
                    <label class="filter-label">District</label>
                    <select name="district" id="district_select" class="filter-select">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= sanitize($d['code']) ?>" <?= $districtCode === $d['code'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Court Filter -->
                <div class="filter-group">
                    <label class="filter-label">Court / Jurisdiction</label>
                    <select name="court" class="filter-select">
                        <option value="">All Courts</option>
                        <?php foreach (getCourtsList() as $cCode => $cName): ?>
                            <option value="<?= sanitize($cCode) ?>" <?= $courtCode === $cCode ? 'selected' : '' ?>><?= sanitize($cName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Enrollment Year -->
                <div class="filter-group">
                    <label class="filter-label">Enrollment Year</label>
                    <input type="number" name="year" class="filter-input" value="<?= sanitize($yearQuery) ?>" placeholder="e.g. 2018, 2023" min="1950" max="<?= date('Y') ?>">
                </div>

                <!-- Practice Area -->
                <div class="filter-group">
                    <label class="filter-label">Practice Specialization</label>
                    <select name="practice_area" class="filter-select">
                        <option value="">All Areas</option>
                        <option value="Civil" <?= $practiceArea === 'Civil' ? 'selected' : '' ?>>Civil Litigation</option>
                        <option value="Criminal" <?= $practiceArea === 'Criminal' ? 'selected' : '' ?>>Criminal Defence</option>
                        <option value="Constitutional" <?= $practiceArea === 'Constitutional' ? 'selected' : '' ?>>Constitutional & Writs</option>
                        <option value="Family" <?= $practiceArea === 'Family' ? 'selected' : '' ?>>Family & Matrimonial</option>
                        <option value="Corporate" <?= $practiceArea === 'Corporate' ? 'selected' : '' ?>>Corporate & Commercial</option>
                        <option value="Property" <?= $practiceArea === 'Property' ? 'selected' : '' ?>>Real Estate & Property</option>
                        <option value="Taxation" <?= $practiceArea === 'Taxation' ? 'selected' : '' ?>>Taxation & GST</option>
                        <option value="Consumer" <?= $practiceArea === 'Consumer' ? 'selected' : '' ?>>Consumer Protection</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px;">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </form>
        </aside>

        <!-- Results Column -->
        <div>
            <?php if (!empty($advocates)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                    <?php foreach ($advocates as $adv): 
                        $badge = getVerificationBadge($adv);
                        $profileUrl = getAdvocateUrl($adv);
                        $practices = parsePracticeAreas($adv['practice_area'] ?? '');
                        $stateName = getStateName($adv['state_code'] ?? '');
                        $distName = getDistrictName($adv['district_code'] ?? '');
                        $exp = formatPracticeExperience($adv['e_year'] ?? '');
                    ?>
                        <div class="advocate-card">
                            <div>
                                <div class="card-top">
                                    <div class="advocate-avatar" style="<?= !empty($adv['photo']) ? 'padding: 0;' : '' ?>">
                                        <?php if (!empty($adv['photo'])): ?>
                                            <img src="<?= sanitize($adv['photo']) ?>" alt="<?= sanitize($adv['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                                        <?php else: ?>
                                            <?= strtoupper(substr(trim($adv['name'] ?: 'A'), 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="advocate-meta">
                                        <div style="margin-bottom: 0.25rem;">
                                            <span class="badge-verification <?= $badge['badge_class'] ?>">
                                                <i class="fas <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                                            </span>
                                        </div>
                                        <h3 class="advocate-name">
                                            <a href="<?= $profileUrl ?>"><?= sanitize($adv['name'] ?: 'Advocate') ?></a>
                                        </h3>
                                        <div class="advocate-enr" style="display: flex; flex-direction: column; gap: 0.15rem; font-size: 0.75rem;">
                                            <span><i class="fas fa-shield-check text-success"></i> Enrollment: <?= formatEnrollmentNumber($adv['e_no'] ?? '') ?></span>
                                            <?php if ($exp !== 'Not Available'): ?>
                                                <span style="color: var(--brand-gold-dark); font-weight: 700;"><i class="fas fa-briefcase"></i> <?= sanitize($exp) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="advocate-location" style="margin-top: 0.2rem;">
                                            <i class="fas fa-location-dot" style="color: var(--brand-accent);"></i>
                                            <span><?= sanitize($distName ?: 'District') ?><?= $stateName ? ', ' . sanitize($stateName) : '' ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Court & Bar Association -->
                                <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.85rem;">
                                    <i class="fas fa-gavel"></i> <?= sanitize(getCourtName($adv['court'] ?? '')) ?>
                                </div>

                                <!-- Practice Pills -->
                                <div class="practice-tags">
                                    <?php foreach (array_slice($practices, 0, 3) as $p): ?>
                                        <span class="practice-pill"><?= sanitize($p) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div style="border-top: 1px solid var(--border-color); padding-top: 1rem; margin-top: 0.5rem; display: flex; gap: 0.5rem;">
                                <a href="<?= $profileUrl ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <a href="claim-profile?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" title="Claim this profile">
                                    <i class="fas fa-shield-alt"></i> Claim
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Component -->
                <?= renderPagination($page, $totalPages, 'advocate-search-result', $_GET) ?>

            <?php else: ?>
                <div class="stat-box" style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No Advocates Found</h3>
                    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
                        We could not find any advocate records matching your current filter criteria. Try broadening your search or resetting filters.
                    </p>
                    <a href="advocate-search-result" class="btn btn-primary">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>

