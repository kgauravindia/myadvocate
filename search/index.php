<?php
// search/index.php - Central Advocate Directory & Legal Search Portal
require_once dirname(__DIR__) . '/config/app.php';

// Dynamic AJAX District Loader
if (isset($_GET['get_districts'])) {
    header('Content-Type: application/json; charset=utf-8');
    $stCode = sanitize($_GET['state_code'] ?? '');
    echo json_encode(!empty($stCode) ? getDistrictsByState($stCode) : []);
    exit;
}

$pageTitle = "Advocate Search & Verification - All India Legal Directory";
$pageDescription = "Search over 160,000+ verified advocates across India. Lookup bar council enrollment numbers, Supreme Court and High Court advocates, district courts, and specialized legal consultants.";
$pageKeywords = "advocate search, find advocate india, lawyer directory, verify advocate enrollment, high court advocate, supreme court lawyer, criminal lawyer, civil advocate, legal search";
$currentPage = 'advocates';
$pageBaseUrl = '../';

$db = getDB();
$states = getStates();
$courtsList = getCourtsList();
$practiceAreasList = getAdvocatePracticeAreas();

// Search Query Parameters
$nameQuery = sanitize($_GET['q'] ?? $_GET['name'] ?? $_GET['keyword'] ?? '');
$mobileQuery = sanitize($_GET['mobile'] ?? $_GET['phone'] ?? '');
$stateCode = sanitize($_GET['state'] ?? $_GET['state_code'] ?? '');
$districtCode = sanitize($_GET['district'] ?? $_GET['district_code'] ?? '');
$courtCode = sanitize($_GET['court'] ?? '');
$yearQuery = sanitize($_GET['year'] ?? $_GET['e_year'] ?? '');
$enrQuery = sanitize($_GET['enr'] ?? $_GET['e_no'] ?? '');
$practiceArea = sanitize($_GET['practice_area'] ?? $_GET['specialization'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 18;
$offset = ($page - 1) * $perPage;

// Build Dynamic SQL Query
$whereClause = "WHERE type IN ('ACTIVE', 'VERIFIED') AND type NOT IN ('PENDING', 'BLOCK', 'DIED') AND (status != 'BLOCK' OR status IS NULL)";
$params = [];

if (!empty($mobileQuery)) {
    $cleanMobile = preg_replace('/\D/', '', $mobileQuery);
    if (!empty($cleanMobile)) {
        $whereClause .= " AND mobile LIKE ?";
        $params[] = "%" . $cleanMobile . "%";
    }
}

if (!empty($nameQuery)) {
    // Check if numeric enrollment or mobile number or text keyword
    if (is_numeric($nameQuery) && strlen($nameQuery) >= 10) {
        $whereClause .= " AND mobile LIKE ?";
        $params[] = "%" . $nameQuery . "%";
    } elseif (is_numeric($nameQuery) && strlen($nameQuery) <= 6) {
        $whereClause .= " AND e_no = ?";
        $params[] = $nameQuery;
    } else {
        $whereClause .= " AND (name LIKE ? OR practice_area LIKE ? OR court LIKE ?)";
        $params[] = "%" . $nameQuery . "%";
        $params[] = "%" . $nameQuery . "%";
        $params[] = "%" . $nameQuery . "%";
    }
}

if (!empty($stateCode)) {
    $whereClause .= " AND state_code = ?";
    $params[] = $stateCode;
}

if (!empty($districtCode)) {
    $whereClause .= " AND district_code = ?";
    $params[] = $districtCode;
}

if (!empty($courtCode)) {
    $whereClause .= " AND court LIKE ?";
    $params[] = "%" . $courtCode . "%";
}

if (!empty($yearQuery)) {
    $whereClause .= " AND e_year = ?";
    $params[] = $yearQuery;
}

if (!empty($enrQuery)) {
    $whereClause .= " AND e_no = ?";
    $params[] = $enrQuery;
}

if (!empty($practiceArea)) {
    $whereClause .= " AND practice_area LIKE ?";
    $params[] = "%" . $practiceArea . "%";
}

// Execute Count
try {
    $countSql = "SELECT COUNT(*) FROM advocate $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalResults = (int)$countStmt->fetchColumn();
} catch (Exception $e) {
    try {
        $fallbackWhere = str_replace("AND (status != 'BLOCK' OR status IS NULL)", "", $whereClause);
        $countStmt = $db->prepare("SELECT COUNT(*) FROM advocate $fallbackWhere");
        $countStmt->execute($params);
        $totalResults = (int)$countStmt->fetchColumn();
    } catch (Exception $e2) {
        $totalResults = 0;
    }
}

$totalPages = max(1, (int)ceil($totalResults / $perPage));

// Execute Paginated Fetch
$advocates = [];
try {
    $dataSql = "SELECT * FROM advocate $whereClause ORDER BY (CASE WHEN premium_member = 1 THEN 1 ELSE 2 END), id DESC LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $fallbackWhere = str_replace("AND (status != 'BLOCK' OR status IS NULL)", "", $whereClause);
        $stmt = $db->prepare("SELECT * FROM advocate $fallbackWhere ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $advocates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $advocates = [];
    }
}

// Load districts for selected state
$districts = !empty($stateCode) ? getDistrictsByState($stateCode) : [];

// Popular years for fast filter pills
$popularYears = [2026, 2025, 2024, 2023, 2022, 2021, 2020, 2019, 2018, 2015, 2010, 2005, 2000, 1995];

require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* Royal Red & Gold Gradient Theme - Consistent with Search Module */
.adv-search-hero {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 45%, #c00000 100%);
    color: #ffffff;
    padding: 3.5rem 0 3.25rem;
    position: relative;
    overflow: hidden;
    border-bottom: 3px solid #ffc000;
}
.adv-search-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 85% 15%, rgba(255, 192, 0, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 15% 85%, rgba(0, 0, 0, 0.28) 0%, transparent 60%);
    pointer-events: none;
}
.adv-hero-content {
    position: relative;
    z-index: 2;
    max-width: 920px;
    margin: 0 auto;
    text-align: center;
}
.adv-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.3);
    color: #ffea79;
    padding: 0.4rem 1.15rem;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 700;
    margin-bottom: 1.25rem;
    border: 1px solid rgba(255, 234, 121, 0.35);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.service-toggle-wrap {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.3);
    padding: 4px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    margin-bottom: 1.5rem;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: center;
}
.service-toggle-btn {
    background: transparent;
    border: none;
    color: #ffffff;
    padding: 0.45rem 1.15rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
}
.service-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
}
.service-toggle-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}
.adv-search-box {
    background: #ffffff;
    border-radius: 14px;
    padding: 0.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.28);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    border: 2px solid #fde047;
}
.adv-input-wrap {
    flex: 1.5;
    display: flex;
    align-items: center;
    padding-left: 1rem;
    gap: 0.75rem;
}
.adv-input-wrap i {
    color: #94a3b8;
    font-size: 1.15rem;
}
.adv-input-field {
    width: 100%;
    border: none;
    outline: none;
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
    background: transparent;
    font-family: inherit;
}
.adv-input-field::placeholder {
    color: #94a3b8;
    font-weight: 400;
}
.adv-state-select {
    flex: 1;
    border: none;
    border-left: 1px solid #e2e8f0;
    outline: none;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e293b;
    background: #ffffff;
    cursor: pointer;
}
.adv-search-btn {
    background: linear-gradient(135deg, #7f1d1d 0%, #c00000 100%);
    color: #ffffff;
    border: 1px solid #ffc000;
    padding: 0.85rem 1.75rem;
    border-radius: 10px;
    font-weight: 800;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(192, 0, 0, 0.35);
}
.adv-search-btn:hover {
    background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(192, 0, 0, 0.45);
}

/* Quick Pick Chips */
.quick-chips-wrap {
    margin-top: 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.chip-label {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #ffea79;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-right: 0.25rem;
}
.quick-chip {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #ffffff;
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
}
.quick-chip:hover {
    background: #ffffff;
    color: #7f1d1d;
    border-color: #ffffff;
    transform: translateY(-1px);
}
.quick-chip strong {
    color: #ffea79;
}
.quick-chip:hover strong {
    color: #7f1d1d;
}

/* Main Layout & Cards */
.search-hub-container {
    padding: 3rem 0 4.5rem;
    background: var(--bg-body, #f8fafc);
}
.advocate-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1.5rem;
}
.adv-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    padding: 1.4rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
    position: relative;
}
.adv-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}
.adv-card.premium-card {
    border-top: 4px solid var(--brand-gold, #ffc000);
}
.adv-card-header {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}
.adv-avatar {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    font-weight: 800;
    flex-shrink: 0;
    overflow: hidden;
    border: 2px solid #e2e8f0;
}
.adv-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.adv-info h3 {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--primary, #0f172a);
    margin: 0.15rem 0 0.35rem;
    line-height: 1.3;
}
.adv-info h3 a {
    color: inherit;
    text-decoration: none;
}
.adv-info h3 a:hover {
    color: var(--brand-red, #c00000);
}
.adv-enr-pill {
    font-size: 0.775rem;
    font-weight: 700;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.25rem;
}
.adv-loc-pill {
    font-size: 0.8rem;
    color: var(--text-muted, #64748b);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

/* Practice Pills */
.practice-pill {
    background: #f1f5f9;
    color: #334155;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
    border: 1px solid #e2e8f0;
}

/* Portal Feature Cards (PIN, IFSC, HSN, Acts) */
.portal-tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.25rem;
    margin-top: 3.5rem;
}
.portal-tool-card {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.portal-tool-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}

@media (max-width: 768px) {
    .adv-search-box {
        flex-direction: column;
        padding: 0.75rem;
    }
    .adv-input-wrap {
        width: 100%;
        padding-left: 0.25rem;
    }
    .adv-state-select {
        width: 100%;
        border-left: none;
        border-top: 1px solid #e2e8f0;
        padding: 0.65rem 0.5rem;
    }
    .adv-search-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Hero Section with Central Search Form -->
<section class="adv-search-hero">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav style="font-size: 0.8125rem; color: #fecaca; margin-bottom: 1.5rem; text-align: center;">
            <a href="./" style="color: #ffffff; text-decoration: none; font-weight: 600;">Home</a> &bull; 
            <span style="color: #ffea79; font-weight: 800;">Advocate Search Directory</span>
        </nav>

        <div class="adv-hero-content">
            <!-- Multi-Search Hub Switcher -->
            <div class="service-toggle-wrap">
                <a href="search/" class="service-toggle-btn active">
                    <i class="fas fa-scale-balanced"></i> Advocates Directory
                </a>
                <a href="search/pin-code.php" class="service-toggle-btn">
                    <i class="fas fa-location-dot"></i> Postal PIN Code
                </a>
                <a href="search/bank-ifsc.php" class="service-toggle-btn">
                    <i class="fas fa-building-columns"></i> Bank IFSC &amp; MICR
                </a>
                <a href="search/hsn-sac.php" class="service-toggle-btn">
                    <i class="fas fa-barcode"></i> GST HSN &amp; SAC
                </a>
                <a href="law-college-search.php" class="service-toggle-btn">
                    <i class="fas fa-graduation-cap"></i> Law Colleges
                </a>
                <a href="bare-acts" class="service-toggle-btn">
                    <i class="fas fa-book-bookmark"></i> Bare Acts
                </a>
            </div>

            <div class="adv-badge">
                <i class="fas fa-shield-halved"></i> 160,000+ Bar Council Verified Advocates
            </div>

            <h1 style="font-size: 2.5rem; font-weight: 900; line-height: 1.2; margin-bottom: 0.85rem; font-family: var(--font-heading); color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.25);">
                India Advocate <span style="color: #ffc000; text-shadow: 0 2px 12px rgba(0,0,0,0.4);">Search Directory</span> &amp; Verification
            </h1>
            <p style="font-size: 1.1rem; color: #ffffff; max-width: 760px; margin: 0 auto; line-height: 1.65; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.2);">
                Search verified advocates across Supreme Court of India, 25 High Courts, District Courts, Tribunals, and State Bar Councils nationwide.
            </p>

            <!-- Main Search Form -->
            <form action="search/" method="GET" class="adv-search-box">
                <div class="adv-input-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" 
                           name="q" 
                           class="adv-input-field" 
                           placeholder="Search by Advocate Name, Enrollment No, Court, or Practice Area..." 
                           value="<?= htmlspecialchars($nameQuery) ?>" 
                           autocomplete="off">
                </div>
                <select name="state" class="adv-state-select" id="heroStateSelect" onchange="this.form.submit()">
                    <option value="">All Indian States &amp; UTs</option>
                    <?php foreach ($states as $code => $stName): ?>
                        <option value="<?= htmlspecialchars($code) ?>" <?= $stateCode === $code ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim($stName)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="adv-search-btn">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>Search Advocates</span>
                </button>
            </form>

            <!-- Popular Quick Filter Chips -->
            <div class="quick-chips-wrap">
                <span class="chip-label"><i class="fas fa-bolt"></i> Popular Legal Hubs:</span>
                <a href="search/?court=SC" class="quick-chip">
                    <span>Supreme Court</span> <strong>SC</strong>
                </a>
                <a href="search/?state=DL&court=HC" class="quick-chip">
                    <span>Delhi High Court</span>
                </a>
                <a href="search/?state=MH&court=HC" class="quick-chip">
                    <span>Bombay HC</span>
                </a>
                <a href="search/?state=UP&court=HC" class="quick-chip">
                    <span>Allahabad HC</span>
                </a>
                <a href="search/?state=WB&court=HC" class="quick-chip">
                    <span>Calcutta HC</span>
                </a>
                <a href="search/?state=BR&court=HC" class="quick-chip">
                    <span>Patna HC</span>
                </a>
                <a href="search/?practice_area=Criminal" class="quick-chip">
                    <span>Criminal Defence</span>
                </a>
                <a href="search/?practice_area=Corporate" class="quick-chip">
                    <span>Corporate Law</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Results & Filtering Layout -->
<main class="search-hub-container">
    <div class="container">
        
        <!-- Year Filter Bar -->
        <div class="stat-box" style="padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.825rem; font-weight: 700; color: #1e293b;">
                    <i class="fas fa-calendar-days" style="color: var(--brand-red, #c00000);"></i>
                    <span>Filter by Enrollment Year:</span>
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
                        $yrUrl = 'search/?' . http_build_query($yrParams);
                    ?>
                        <a href="<?= htmlspecialchars($yrUrl) ?>" class="practice-pill" style="text-decoration: none; font-size: 0.75rem; padding: 0.25rem 0.65rem; <?= $yrActive ? 'background: #991b1b; color: #ffffff; border-color: #991b1b; font-weight: 700;' : '' ?>">
                            <?= $yr ?> <?= $yrActive ? '<i class="fas fa-times" style="margin-left: 2px;"></i>' : '' ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Active Filters Display -->
        <?php 
        $activeFilters = [];
        if (!empty($nameQuery)) $activeFilters['name'] = ['label' => 'Keyword: "' . $nameQuery . '"', 'key' => 'q'];
        if (!empty($mobileQuery)) $activeFilters['mobile'] = ['label' => 'Mobile: ' . $mobileQuery, 'key' => 'mobile'];
        if (!empty($stateCode)) $activeFilters['state'] = ['label' => 'State: ' . getStateName($stateCode), 'key' => 'state'];
        if (!empty($districtCode)) $activeFilters['district'] = ['label' => 'District: ' . getDistrictName($districtCode), 'key' => 'district'];
        if (!empty($courtCode)) $activeFilters['court'] = ['label' => 'Court: ' . getCourtName($courtCode), 'key' => 'court'];
        if (!empty($yearQuery)) $activeFilters['year'] = ['label' => 'Year: ' . $yearQuery, 'key' => 'year'];
        if (!empty($practiceArea)) $activeFilters['practice_area'] = ['label' => 'Specialization: ' . $practiceArea, 'key' => 'practice_area'];
        ?>

        <?php if (!empty($activeFilters)): ?>
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem;">
                <span style="font-size: 0.775rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Filters:</span>
                <?php foreach ($activeFilters as $fKey => $fInfo): 
                    $fParams = $_GET;
                    unset($fParams[$fInfo['key']], $fParams['page']);
                    if ($fInfo['key'] === 'state') unset($fParams['state_code']);
                    if ($fInfo['key'] === 'district') unset($fParams['district_code']);
                    if ($fInfo['key'] === 'year') unset($fParams['e_year']);
                    if ($fInfo['key'] === 'q') unset($fParams['name'], $fParams['keyword']);
                    $fUrl = 'search/' . (!empty($fParams) ? '?' . http_build_query($fParams) : '');
                ?>
                    <a href="<?= htmlspecialchars($fUrl) ?>" style="display: inline-flex; align-items: center; gap: 0.35rem; background: #fee2e2; color: #991b1b; font-size: 0.775rem; font-weight: 700; padding: 0.25rem 0.65rem; border-radius: 999px; text-decoration: none; border: 1px solid #fca5a5;">
                        <?= htmlspecialchars($fInfo['label']) ?> <i class="fas fa-times-circle"></i>
                    </a>
                <?php endforeach; ?>
                <a href="search/" style="font-size: 0.775rem; color: #b91c1c; text-decoration: underline; font-weight: 700; margin-left: 0.5rem;">Clear All</a>
            </div>
        <?php endif; ?>

        <!-- 2-Column Search Layout: Sidebar & Cards -->
        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 1.75rem; align-items: start;">
            
            <!-- Left Sidebar Filter -->
            <aside>
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 14px; padding: 1.4rem; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-sliders" style="color: var(--brand-red);"></i> Filter Advocates
                        </h3>
                        <a href="search/" style="font-size: 0.75rem; color: #991b1b; font-weight: 700; text-decoration: underline;">Reset</a>
                    </div>

                    <form action="search/" method="GET" id="sidebarFilterForm">
                        <!-- Keyword -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem;">Advocate Name / Keyword</label>
                            <input type="text" name="q" class="filter-input" value="<?= htmlspecialchars($nameQuery) ?>" placeholder="e.g. Kumar, Verma, Tax..." style="width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                        </div>

                        <!-- State Dropdown -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem;">State / Bar Council</label>
                            <select name="state" id="sidebarStateSelect" class="filter-select" style="width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <option value="">All States</option>
                                <?php foreach ($states as $sCode => $sName): ?>
                                    <option value="<?= htmlspecialchars($sCode) ?>" <?= $stateCode === $sCode ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(trim($sName)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- District Dropdown -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem;">District</label>
                            <select name="district" id="sidebarDistrictSelect" class="filter-select" style="width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $d): ?>
                                    <option value="<?= htmlspecialchars($d['code'] ?? '') ?>" <?= $districtCode === ($d['code'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($d['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Court Selection -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem;">Practicing Court</label>
                            <select name="court" class="filter-select" style="width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <option value="">All Courts</option>
                                <?php foreach ($courtsList as $cKey => $cVal): ?>
                                    <option value="<?= htmlspecialchars($cKey) ?>" <?= $courtCode === $cKey ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cVal) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Practice Specialization -->
                        <div class="filter-group" style="margin-bottom: 1.25rem;">
                            <label class="filter-label" style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 0.35rem;">Specialization</label>
                            <select name="practice_area" class="filter-select" style="width: 100%; padding: 0.55rem 0.75rem; font-size: 0.85rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                                <option value="">All Specializations</option>
                                <option value="Civil" <?= $practiceArea === 'Civil' ? 'selected' : '' ?>>Civil Law</option>
                                <option value="Criminal" <?= $practiceArea === 'Criminal' ? 'selected' : '' ?>>Criminal Law</option>
                                <option value="Constitutional" <?= $practiceArea === 'Constitutional' ? 'selected' : '' ?>>Constitutional Law</option>
                                <option value="Corporate" <?= $practiceArea === 'Corporate' ? 'selected' : '' ?>>Corporate &amp; Commercial</option>
                                <option value="Family" <?= $practiceArea === 'Family' ? 'selected' : '' ?>>Family &amp; Matrimonial</option>
                                <option value="Property" <?= $practiceArea === 'Property' ? 'selected' : '' ?>>Real Estate &amp; Property</option>
                                <option value="Taxation" <?= $practiceArea === 'Taxation' ? 'selected' : '' ?>>Taxation &amp; GST</option>
                                <option value="Arbitration" <?= $practiceArea === 'Arbitration' ? 'selected' : '' ?>>Arbitration &amp; Mediation</option>
                                <option value="Cyber" <?= $practiceArea === 'Cyber' ? 'selected' : '' ?>>Cyber Law</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-weight: 700; justify-content: center;">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Right Results Column -->
            <div>
                <!-- Results Header Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--primary); margin: 0;">
                            <?= number_format($totalResults) ?> <?= $totalResults === 1 ? 'Advocate Found' : 'Advocates Found' ?>
                        </h2>
                        <span style="font-size: 0.825rem; color: var(--text-muted);">
                            Showing page <?= $page ?> of <?= $totalPages ?> (<?= min($totalResults, $offset + 1) ?> - <?= min($totalResults, $offset + $perPage) ?> records)
                        </span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="font-size: 0.8rem; font-weight: 600;">
                            <i class="fas fa-print"></i> Print Directory
                        </button>
                    </div>
                </div>

                <!-- Advocates Cards Grid -->
                <?php if (!empty($advocates)): ?>
                    <div class="advocate-results-grid">
                        <?php foreach ($advocates as $adv): 
                            $badge = getVerificationBadge($adv);
                            $profileUrl = getAdvocateUrl($adv);
                            $practices = parsePracticeAreas($adv['practice_area'] ?? '');
                            $stName = getStateName($adv['state_code'] ?? '');
                            $dstName = getDistrictName($adv['district_code'] ?? '');
                            $exp = formatPracticeExperience($adv['e_year'] ?? '');
                            $cardPhotoUrl = getAdvocatePhotoUrl($adv['photo'] ?? '');
                            $isPrem = !empty($adv['premium_member']);
                        ?>
                            <div class="adv-card <?= $isPrem ? 'premium-card' : '' ?>">
                                <div>
                                    <div class="adv-card-header">
                                        <div class="adv-avatar">
                                            <?php if (!empty($cardPhotoUrl)): ?>
                                                <img src="<?= htmlspecialchars($cardPhotoUrl) ?>" alt="<?= htmlspecialchars($adv['name'] ?? 'Advocate') ?>">
                                            <?php else: ?>
                                                <?= strtoupper(substr(trim($adv['name'] ?: 'A'), 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>

                                        <div class="adv-info" style="flex: 1; min-width: 0;">
                                            <div>
                                                <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;">
                                                    <i class="fas <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                                                </span>
                                            </div>
                                            <h3>
                                                <a href="<?= htmlspecialchars($profileUrl) ?>" title="View verified profile">
                                                    <?= htmlspecialchars($adv['name'] ?: 'Advocate') ?>
                                                </a>
                                            </h3>
                                            <div class="adv-enr-pill">
                                                <i class="fas fa-shield-check" style="color: #16a34a;"></i>
                                                <span>Enr: <?= htmlspecialchars(formatEnrollmentNumber($adv['e_no'] ?? '')) ?></span>
                                            </div>
                                            <div class="adv-loc-pill">
                                                <i class="fas fa-location-dot" style="color: #ef4444;"></i>
                                                <span><?= htmlspecialchars($dstName ?: 'District') ?><?= $stName ? ', ' . htmlspecialchars($stName) : '' ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Court & Practice Experience -->
                                    <div style="font-size: 0.8rem; color: #475569; margin-bottom: 0.65rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.25rem;">
                                        <span><i class="fas fa-gavel" style="color: #64748b;"></i> <?= htmlspecialchars(getCourtName($adv['court'] ?? '')) ?></span>
                                        <?php if ($exp !== 'Not Available'): ?>
                                            <span style="color: #b45309; font-weight: 700;"><i class="fas fa-briefcase"></i> <?= htmlspecialchars($exp) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Practice Specialization Tags -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-bottom: 1rem;">
                                        <?php foreach (array_slice($practices, 0, 3) as $p): ?>
                                            <span class="practice-pill"><?= htmlspecialchars($p) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div style="border-top: 1px solid #f1f5f9; padding-top: 0.85rem; display: flex; gap: 0.5rem;">
                                    <a href="<?= htmlspecialchars($profileUrl) ?>" class="btn btn-outline btn-sm" style="flex: 1; justify-content: center; font-size: 0.8rem; padding: 0.45rem;">
                                        View Profile
                                    </a>
                                    <a href="<?= htmlspecialchars($profileUrl) ?>#contact" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center; font-size: 0.8rem; padding: 0.45rem;">
                                        Contact
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 0.35rem; margin-top: 2.5rem; flex-wrap: wrap;">
                            <?php 
                            $pParams = $_GET;
                            $curPage = $page;
                            $startPage = max(1, $curPage - 2);
                            $endPage = min($totalPages, $curPage + 2);
                            ?>

                            <?php if ($curPage > 1): 
                                $pParams['page'] = $curPage - 1;
                            ?>
                                <a href="search/?<?= http_build_query($pParams) ?>" class="btn btn-outline btn-sm">
                                    <i class="fas fa-chevron-left"></i> Prev
                                </a>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): 
                                $pParams['page'] = $i;
                                $isActive = ($i === $curPage);
                            ?>
                                <a href="search/?<?= http_build_query($pParams) ?>" class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" style="<?= $isActive ? 'font-weight: 800;' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($curPage < $totalPages): 
                                $pParams['page'] = $curPage + 1;
                            ?>
                                <a href="search/?<?= http_build_query($pParams) ?>" class="btn btn-outline btn-sm">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red, #c00000); padding: 2rem;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <i class="fas fa-circle-exclamation" style="color: var(--brand-red, #c00000); font-size: 1.75rem; margin-top: 2px;"></i>
                            <div>
                                <h3 style="font-size: 1.2rem; color: #991b1b; margin: 0 0 0.5rem; font-weight: 800;">No Advocates Found</h3>
                                <p style="color: #7f1d1d; margin: 0 0 0.75rem; font-size: 0.95rem;">
                                    No advocate records matched your active filter criteria.
                                </p>
                                <ul style="color: #7f1d1d; font-size: 0.85rem; margin: 0; padding-left: 1.25rem; line-height: 1.6;">
                                    <li>Try broadening your search by clearing the district or court filter.</li>
                                    <li>Search with a partial name (e.g. <code>Kumar</code> or <code>Sharma</code>).</li>
                                    <li>Lookup directly by Bar Council Enrollment Number (e.g. <code>1234/2021</code>).</li>
                                </ul>
                                <div style="margin-top: 1.25rem;">
                                    <a href="search/" class="btn btn-primary btn-sm">Reset All Filters</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Central Search Directory Tool Cards -->
        <div class="portal-tools-grid">
            <a href="search/pin-code.php" class="portal-tool-card" style="border-top: 4px solid var(--brand-red, #c00000);">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-location-dot" style="color: var(--brand-red, #c00000); font-size: 1.35rem;"></i>
                        <h4 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--primary);">Postal PIN Code Search</h4>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem;">
                        Instant 6-digit Indian PIN code directory, post office delivery status, and Speed Post tracking for court notices under Order V Rule 9 CPC.
                    </p>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--brand-red, #c00000); display: flex; align-items: center; gap: 0.35rem;">
                    Search PIN Codes <i class="fas fa-arrow-right"></i>
                </span>
            </a>

            <a href="search/bank-ifsc.php" class="portal-tool-card" style="border-top: 4px solid #2563eb;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-building-columns" style="color: #2563eb; font-size: 1.35rem;"></i>
                        <h4 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--primary);">Bank IFSC &amp; MICR Lookup</h4>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem;">
                        Lookup 11-digit Bank IFSC, MICR codes, branch addresses, and electronic settlement capabilities (NEFT, RTGS, IMPS, UPI) nationwide.
                    </p>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #2563eb; display: flex; align-items: center; gap: 0.35rem;">
                    Verify Bank IFSC <i class="fas fa-arrow-right"></i>
                </span>
            </a>

            <a href="search/hsn-sac.php" class="portal-tool-card" style="border-top: 4px solid #d97706;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-barcode" style="color: #d97706; font-size: 1.35rem;"></i>
                        <h4 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--primary);">GST HSN &amp; SAC Tariff Finder</h4>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem;">
                        Search 17,000+ Indian Goods (HSN) &amp; Services (SAC) codes, tax slabs (0% to 28%), and legal Reverse Charge (RCM) rules for advocates.
                    </p>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #d97706; display: flex; align-items: center; gap: 0.35rem;">
                    Search GST Rates <i class="fas fa-arrow-right"></i>
                </span>
            </a>

            <a href="bare-acts" class="portal-tool-card" style="border-top: 4px solid #16a34a;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <i class="fas fa-book-bookmark" style="color: #16a34a; font-size: 1.35rem;"></i>
                        <h4 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--primary);">Central Bare Acts &amp; New Laws</h4>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 1rem;">
                        Explore 800+ full-text Indian Bare Acts including the new criminal major acts: Bharatiya Nyaya Sanhita (BNS), BNSS, and BSA 2023.
                    </p>
                </div>
                <span style="font-size: 0.8rem; font-weight: 700; color: #16a34a; display: flex; align-items: center; gap: 0.35rem;">
                    Browse Bare Acts <i class="fas fa-arrow-right"></i>
                </span>
            </a>
        </div>

    </div>
</main>

<!-- Interactive Script for Dynamic District Loading -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('sidebarStateSelect');
    const districtSelect = document.getElementById('sidebarDistrictSelect');

    if (stateSelect && districtSelect) {
        stateSelect.addEventListener('change', function() {
            const st = this.value;
            districtSelect.innerHTML = '<option value="">Loading districts...</option>';

            if (!st) {
                districtSelect.innerHTML = '<option value="">All Districts</option>';
                return;
            }

            fetch('search/index.php?get_districts=1&state_code=' + encodeURIComponent(st))
                .then(res => res.json())
                .then(data => {
                    let html = '<option value="">All Districts</option>';
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(d => {
                            html += `<option value="${escapeHtml(d.code || '')}">${escapeHtml(d.name || '')}</option>`;
                        });
                    }
                    districtSelect.innerHTML = html;
                })
                .catch(() => {
                    districtSelect.innerHTML = '<option value="">All Districts</option>';
                });
        });
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
