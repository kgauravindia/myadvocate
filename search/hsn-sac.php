<?php
// search/hsn-sac.php - India GST HSN & SAC Code Search & Tariff Directory
require_once dirname(__DIR__) . '/config/app.php';

$pageTitle = "GST HSN & SAC Code Search - Goods & Services Tax Tariff Finder";
$pageDescription = "Search 17,000+ Indian GST HSN and SAC codes for Goods & Services. Instant GST tax rates (0%, 5%, 12%, 18%, 28%), legal Reverse Charge (RCM) rules for advocates, chapters, headings, and official CBIC descriptions.";
$pageKeywords = "hsn code search, sac code search, gst rates india, legal services sac 9982, gst tariff directory, hsn finder, goods services tax classification, reverse charge mechanism gst";
$currentPage = 'hsnsac';
$pageBaseUrl = '../';

/**
 * SQLite Database Connection for GST Catalog
 */
function getGstDb(): ?PDO {
    static $db = null;
    if ($db === null) {
        $dbPath = dirname(__DIR__) . '/data/gst/gst_catalog.db';
        if (file_exists($dbPath)) {
            try {
                $db = new PDO("sqlite:" . $dbPath);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $db = null;
            }
        }
    }
    return $db;
}

/**
 * Search HSN and SAC catalog by Code, Keyword, or Classification Type
 */
function searchGstCatalog(string $query, string $type = 'all', int $limit = 80): array {
    $db = getGstDb();
    if (!$db) {
        return [
            'status' => 'error',
            'message' => 'GST catalog database is currently unavailable.',
            'data' => []
        ];
    }

    $cleanQuery = trim($query);
    $type = in_array(strtolower($type), ['goods', 'service']) ? strtolower($type) : 'all';

    // 1. If query is empty, return popular default legal & commercial classifications
    if (empty($cleanQuery)) {
        $defaultCodes = ['998211', '998212', '998213', '998214', '998215', '998216', '998311', '847130', '300490', '998411'];
        $placeholders = implode(',', array_fill(0, count($defaultCodes), '?'));
        $stmt = $db->prepare("SELECT * FROM gst_items WHERE code IN ($placeholders) ORDER BY code ASC");
        $stmt->execute($defaultCodes);
        $rows = $stmt->fetchAll();
        return [
            'status' => 'success',
            'query' => '',
            'type' => $type,
            'is_default' => true,
            'total' => count($rows),
            'data' => formatGstRows($rows)
        ];
    }

    // 2. Determine search strategy (Numeric Code vs Keyword Text)
    $isDigits = preg_match('/^[0-9]+$/', $cleanQuery);
    $typeClause = ($type !== 'all') ? "AND type = :type" : "";

    $rows = [];
    if ($isDigits) {
        // Numeric Prefix / Exact Code Match
        $sql = "SELECT * FROM gst_items 
                WHERE (code = :exact OR code LIKE :prefix) $typeClause 
                ORDER BY (CASE WHEN code = :exact THEN 1 WHEN code LIKE :exact_prefix THEN 2 ELSE 3 END), length(code) ASC, code ASC 
                LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':exact', $cleanQuery, PDO::PARAM_STR);
        $stmt->bindValue(':prefix', $cleanQuery . '%', PDO::PARAM_STR);
        $stmt->bindValue(':exact_prefix', $cleanQuery . '%', PDO::PARAM_STR);
        if ($type !== 'all') {
            $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } else {
        // Keyword Search using FTS5 virtual table for fast full-text ranking
        try {
            // Clean search terms for FTS MATCH
            $terms = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanQuery);
            $words = array_filter(explode(' ', trim($terms)));
            if (!empty($words)) {
                $matchQuery = implode(' OR ', array_map(fn($w) => $w . '*', $words));
                $sql = "SELECT i.* FROM gst_fts f 
                        JOIN gst_items i ON f.rowid = i.id 
                        WHERE gst_fts MATCH :match $typeClause 
                        ORDER BY rank ASC 
                        LIMIT :limit";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':match', $matchQuery, PDO::PARAM_STR);
                if ($type !== 'all') {
                    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                }
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            $rows = [];
        }

        // Fallback to LIKE if FTS produced 0 results
        if (empty($rows)) {
            $sql = "SELECT * FROM gst_items 
                    WHERE (description LIKE :kw OR heading_description LIKE :kw OR section LIKE :kw) $typeClause 
                    ORDER BY length(description) ASC 
                    LIMIT :limit";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':kw', '%' . $cleanQuery . '%', PDO::PARAM_STR);
            if ($type !== 'all') {
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
        }
    }

    return [
        'status' => 'success',
        'query' => $cleanQuery,
        'type' => $type,
        'is_default' => false,
        'total' => count($rows),
        'data' => formatGstRows($rows)
    ];
}

/**
 * Format raw SQLite rows with calculated tax rates and legal RCM metadata
 */
function formatGstRows(array $rows): array {
    $out = [];
    foreach ($rows as $r) {
        $code = (string)($r['code'] ?? '');
        $isService = ($r['type'] ?? '') === 'service' || str_starts_with($code, '99');
        $typeLabel = $isService ? 'Services (SAC)' : 'Goods (HSN)';
        
        // Rate parsing
        $igst = $r['igst_rate'] !== null ? (float)$r['igst_rate'] : ($isService ? 18.0 : null);
        $rateStr = 'Standard Rate';
        if ($igst !== null) {
            if ($igst == 0.0) {
                $rateStr = '0% (Nil / Exempt)';
            } else {
                $cgst = $igst / 2;
                $sgst = $igst / 2;
                $rateStr = "{$igst}% ({$cgst}% CGST + {$sgst}% SGST)";
            }
        }

        // Check if Legal Service subject to RCM (SAC 9982)
        $isLegalRcm = str_starts_with($code, '9982');

        $out[] = [
            'code'                => $code,
            'description'         => $r['description'] ?? '',
            'type'                => $isService ? 'service' : 'goods',
            'type_label'          => $typeLabel,
            'rate_str'            => $rateStr,
            'igst'                => $igst,
            'is_rcm'              => $isLegalRcm,
            'rcm_note'            => $isLegalRcm ? 'Reverse Charge Mechanism (RCM) applies under Sec 9(3) CGST Act. Tax payable by business recipient.' : '',
            'section'             => $r['section'] ?? '',
            'heading'             => $r['heading'] ?? '',
            'heading_description' => $r['heading_description'] ?? '',
            'group'               => $r['item_group'] ?? '',
            'group_description'   => $r['group_description'] ?? ''
        ];
    }
    return $out;
}

// -------------------------------------------------------------
// Request Parameters & Live AJAX Response
// -------------------------------------------------------------
$query = sanitize($_GET['q'] ?? '');
$typeFilter = sanitize($_GET['type'] ?? 'all');
$isAjax = isset($_GET['ajax']) || isset($_GET['api']);

// If AJAX / JSON API requested, return pure JSON and exit
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(searchGstCatalog($query, $typeFilter));
    exit;
}

// Initial server-side query execution for fast first render and SEO
$searchResult = searchGstCatalog($query, $typeFilter);
$searched = !empty($query);

// Include standard header
require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* Royal Red & Gold Gradient Hero - Matching pin-code.php */
.hsn-search-hero {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 45%, #c00000 100%);
    color: #ffffff;
    padding: 3.5rem 0 3.25rem;
    position: relative;
    overflow: hidden;
    border-bottom: 3px solid #ffc000;
}
.hsn-search-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(circle at 80% 20%, rgba(255, 192, 0, 0.18) 0%, transparent 50%),
                radial-gradient(circle at 20% 80%, rgba(0, 0, 0, 0.25) 0%, transparent 60%);
    pointer-events: none;
}
.hsn-hero-content {
    position: relative;
    z-index: 2;
    max-width: 860px;
    margin: 0 auto;
    text-align: center;
}
.hsn-badge {
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
    background: rgba(0, 0, 0, 0.28);
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
    padding: 0.5rem 1.35rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.service-toggle-btn.active {
    background: #ffffff;
    color: #0f172a;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
}
.hsn-search-box {
    background: #ffffff;
    border-radius: 14px;
    padding: 0.5rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    border: 2px solid #fde047;
}
.hsn-input-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    padding-left: 1rem;
    gap: 0.75rem;
}
.hsn-input-wrap i {
    color: #94a3b8;
    font-size: 1.15rem;
}
.hsn-input-field {
    width: 100%;
    border: none;
    outline: none;
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
    background: transparent;
    font-family: inherit;
}
.hsn-input-field::placeholder {
    color: #94a3b8;
    font-weight: 400;
}
.hsn-search-btn {
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
.hsn-search-btn:hover {
    background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(192, 0, 0, 0.45);
}
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

/* Results Section Styling */
.results-container {
    padding: 3rem 0;
    background: var(--bg-body, #f8fafc);
    min-height: 50vh;
}
.results-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 2rem;
}
.results-header {
    padding: 1.5rem 1.75rem;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    background: #fafafa;
}
.results-stats {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.stat-pill {
    background: #ffffff;
    border: 1px solid var(--border-color, #cbd5e1);
    padding: 0.35rem 0.85rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--primary, #0f172a);
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.hsn-badge-code {
    background: #fef3c7;
    color: #92400e;
    font-family: monospace;
    font-size: 1rem;
    font-weight: 800;
    padding: 0.25rem 0.65rem;
    border-radius: 6px;
    letter-spacing: 0.05em;
    border: 1px solid #fde68a;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.hsn-badge-code:hover {
    background: #fde68a;
    transform: scale(1.02);
}
.hsn-table {
    width: 100%;
    border-collapse: collapse;
}
.hsn-table th {
    background: #f1f5f9;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.9rem 1.25rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}
.hsn-table td {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #f1f5f9);
    font-size: 0.9125rem;
    color: var(--text-color, #1e293b);
    vertical-align: middle;
}
.hsn-table tr:hover td {
    background-color: #f8fafc;
}

/* Category Badges */
.badge-service {
    background: #f3e8ff;
    color: #6b21a8;
    border: 1px solid #e9d5ff;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    font-size: 0.775rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.badge-goods {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    font-size: 0.775rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.badge-rate {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
    padding: 0.25rem 0.65rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-family: monospace;
}
.badge-rcm {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.725rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    margin-top: 0.35rem;
}

.copy-btn-mini {
    background: transparent;
    border: 1px solid var(--border-color, #cbd5e1);
    color: var(--text-muted, #64748b);
    padding: 0.35rem 0.65rem;
    border-radius: 6px;
    font-size: 0.775rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.copy-btn-mini:hover {
    background: var(--primary, #0f172a);
    color: #ffffff;
    border-color: var(--primary, #0f172a);
}

/* Educational Guide Section */
.guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}
.guide-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 1.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.digit-diagram {
    display: flex;
    gap: 0.5rem;
    margin: 1.5rem 0;
    justify-content: center;
    flex-wrap: wrap;
}
.digit-box {
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.75rem 0.5rem;
    text-align: center;
    flex: 1;
    min-width: 60px;
}
.digit-box.chapter {
    border-color: var(--brand-red, #c00000);
    background: #fef2f2;
}
.digit-box.heading {
    border-color: var(--brand-gold, #d97706);
    background: #fffbeb;
}
.digit-box.subheading {
    border-color: #2563eb;
    background: #eff6ff;
}
.digit-box.tariff {
    border-color: #16a34a;
    background: #f0fdf4;
}
.digit-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 0.35rem;
}
.digit-lbl {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.04em;
}

/* Toast alert */
#toastBox {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: #0f172a;
    color: #ffffff;
    padding: 0.85rem 1.35rem;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    display: none;
    z-index: 99999;
    align-items: center;
    gap: 0.6rem;
    border-left: 4px solid #22c55e;
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media(max-width: 768px) {
    .hsn-search-box {
        flex-direction: column;
        padding: 0.75rem;
    }
    .hsn-input-wrap {
        width: 100%;
        padding-left: 0.25rem;
    }
    .hsn-search-btn {
        width: 100%;
        justify-content: center;
    }
    .hsn-table-responsive {
        overflow-x: auto;
    }
}
</style>

<!-- Hero Section with Search Bar -->
<section class="hsn-search-hero">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav style="font-size: 0.8125rem; color: #fecaca; margin-bottom: 1.5rem; text-align: center;">
            <a href="./" style="color: #ffffff; text-decoration: none; font-weight: 600;">Home</a> &bull; 
            <a href="search/pin-code.php" style="color: #fca5a5; text-decoration: none; font-weight: 600;">Search</a> &bull; 
            <span style="color: #ffea79; font-weight: 800;">GST HSN &amp; SAC Code Finder</span>
        </nav>

        <div class="hsn-hero-content">
            <!-- Service Switcher & CBIC Link -->
            <div style="display: flex; justify-content: center; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
                <div class="service-toggle-wrap">
                    <button type="button" class="service-toggle-btn <?= $typeFilter === 'all' ? 'active' : '' ?>" id="btnToggleAll" onclick="setTypeFilter('all')">
                        <i class="fas fa-table-cells"></i> All Codes
                    </button>
                    <button type="button" class="service-toggle-btn <?= $typeFilter === 'goods' ? 'active' : '' ?>" id="btnToggleGoods" onclick="setTypeFilter('goods')">
                        <i class="fas fa-box"></i> Goods (HSN)
                    </button>
                    <button type="button" class="service-toggle-btn <?= $typeFilter === 'service' ? 'active' : '' ?>" id="btnToggleServices" onclick="setTypeFilter('service')">
                        <i class="fas fa-scale-balanced"></i> Services (SAC)
                    </button>
                </div>
                <a href="https://www.gst.gov.in/" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="background: rgba(255,255,255,0.18); color: #ffffff; border-color: rgba(255,255,255,0.35); font-size: 0.825rem; font-weight: 700; border-radius: 999px; padding: 0.4rem 0.95rem; text-decoration: none;" title="Visit GST System Official Portal">
                    <i class="fas fa-arrow-up-right-from-square" style="color: #ffea79;"></i> gst.gov.in
                </a>
            </div>

            <div class="hsn-badge">
                <i class="fas fa-file-invoice-dollar"></i> Official CBIC GST &amp; Services Tariff Catalog
            </div>
            <h1 style="font-size: 2.5rem; font-weight: 900; line-height: 1.2; margin-bottom: 0.85rem; font-family: var(--font-heading); color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.25);">
                GST HSN &amp; SAC <span style="color: #ffc000; text-shadow: 0 2px 12px rgba(0,0,0,0.4);">Code Search</span> &amp; Tax Rates
            </h1>
            <p style="font-size: 1.1rem; color: #ffffff; max-width: 740px; margin: 0 auto; line-height: 1.65; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.2);">
                Search 17,000+ Indian Goods (HSN) &amp; Services (SAC) classifications, applicable GST tax slabs (0%, 5%, 12%, 18%, 28%), and legal Reverse Charge (RCM) guidelines.
            </p>

            <!-- Search Form -->
            <form id="hsnSearchForm" action="search/hsn-sac.php" method="GET" class="hsn-search-box">
                <input type="hidden" name="type" id="typeInput" value="<?= htmlspecialchars($typeFilter) ?>">
                <div class="hsn-input-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" 
                           id="hsnQuery" 
                           name="q" 
                           class="hsn-input-field" 
                           placeholder="Enter HSN/SAC code (e.g. 9982, 8471) or item (e.g. Legal, Advocate, Computer)..." 
                           value="<?= htmlspecialchars($query) ?>" 
                           autocomplete="off">
                    <button type="button" id="clearBtn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.25rem 0.5rem; display: <?= !empty($query) ? 'block' : 'none' ?>;" title="Clear input">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <button type="submit" class="hsn-search-btn" id="searchSubmitBtn">
                    <span id="searchBtnIcon"><i class="fas fa-arrow-right"></i></span>
                    <span id="searchBtnText">Search GST Code</span>
                </button>
            </form>

            <!-- Quick Pick Chips -->
            <div class="quick-chips-wrap">
                <span class="chip-label"><i class="fas fa-bolt"></i> Popular Legal &amp; Commercial Codes:</span>
                <a href="search/hsn-sac.php?q=9982" class="quick-chip" data-code="9982" title="Legal & Representation Services (Advocates)">
                    <span>Legal Services</span> <strong>9982</strong>
                </a>
                <a href="search/hsn-sac.php?q=9983" class="quick-chip" data-code="9983" title="Accounting & Audit Services">
                    <span>Accounting</span> <strong>9983</strong>
                </a>
                <a href="search/hsn-sac.php?q=8471" class="quick-chip" data-code="8471" title="Computers, Laptops & Hardware">
                    <span>Computers</span> <strong>8471</strong>
                </a>
                <a href="search/hsn-sac.php?q=3004" class="quick-chip" data-code="3004" title="Medicaments & Pharmaceutical Products">
                    <span>Medicines</span> <strong>3004</strong>
                </a>
                <a href="search/hsn-sac.php?q=9984" class="quick-chip" data-code="9984" title="Telecommunications & Broadcasting">
                    <span>Telecom</span> <strong>9984</strong>
                </a>
                <a href="search/hsn-sac.php?q=9972" class="quick-chip" data-code="9972" title="Real Estate Services">
                    <span>Real Estate</span> <strong>9972</strong>
                </a>
                <a href="search/hsn-sac.php?q=6203" class="quick-chip" data-code="6203" title="Men's Suits, Jackets & Blazers">
                    <span>Garments</span> <strong>6203</strong>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Results Container -->
<main class="results-container">
    <div class="container">
        
        <!-- Live Loading Spinner -->
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 3rem 1rem;">
            <div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #f1f5f9; border-top-color: var(--brand-red, #c00000); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">
                Querying official GST Tariff &amp; HSN/SAC classification catalog...
            </p>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

        <!-- Dynamic Results Target -->
        <div id="resultsWrapper">
            <?php 
                $records = $searchResult['data'] ?? [];
                $totalCount = $searchResult['total'] ?? count($records);
                $isDefault = $searchResult['is_default'] ?? false;
            ?>

            <?php if (!empty($records)): ?>
                <div class="results-card">
                    <div class="results-header">
                        <div>
                            <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                                <i class="fas fa-barcode" style="color: var(--brand-red, #c00000);"></i>
                                <?php if ($isDefault): ?>
                                    Essential Legal &amp; Commercial Classifications
                                <?php else: ?>
                                    Results for: <span style="color: var(--brand-red, #c00000);"><?= htmlspecialchars($query) ?></span>
                                <?php endif; ?>
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                <?= $isDefault ? 'Showing standard advocate legal services and frequent GST codes.' : 'Filtered by: ' . ($typeFilter === 'all' ? 'All Goods & Services' : ucfirst($typeFilter)) ?> &bull; Source: Official CBIC Tariff Master
                            </p>
                        </div>

                        <div class="results-stats">
                            <span class="stat-pill">
                                <i class="fas fa-list-check" style="color: #2563eb;"></i> <?= $totalCount ?> <?= $totalCount === 1 ? 'Code Found' : 'Codes Found' ?>
                            </span>
                            <button type="button" class="btn btn-outline btn-sm" onclick="copyAllCodes()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-copy"></i> Copy Codes
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>

                    <!-- In-Table Live Filter Bar -->
                    <div style="padding: 0.75rem 1.5rem; background: #fafafa; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                            Live instant filter across active results:
                        </div>
                        <div style="position: relative; min-width: 260px;">
                            <i class="fas fa-filter" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                            <input type="text" id="tableFilterInput" placeholder="Filter by code or description..." 
                                   style="width: 100%; padding: 0.4rem 0.75rem 0.4rem 2rem; font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
                        </div>
                    </div>

                    <div class="hsn-table-responsive">
                        <table class="hsn-table" id="hsnDataTable">
                            <thead>
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th style="min-width: 110px;">HSN / SAC Code</th>
                                    <th style="min-width: 140px;">Category</th>
                                    <th>Description &amp; Heading</th>
                                    <th style="min-width: 130px;">GST Rate</th>
                                    <th style="text-align: right; min-width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $index => $item): 
                                    $isService = ($item['type'] ?? '') === 'service';
                                    $isRcm = !empty($item['is_rcm']);
                                    $searchBlob = strtolower($item['code'] . ' ' . $item['description'] . ' ' . ($item['heading_description'] ?? '') . ' ' . $item['rate_str']);
                                ?>
                                    <tr class="hsn-row" data-search="<?= htmlspecialchars($searchBlob) ?>">
                                        <td><span style="color: var(--text-muted); font-weight: 600;"><?= $index + 1 ?></span></td>
                                        <td>
                                            <span class="hsn-badge-code" onclick="copyText('<?= htmlspecialchars($item['code']) ?>', 'Code <?= htmlspecialchars($item['code']) ?> copied!')" title="Click to copy Code">
                                                <i class="fas fa-copy"></i> <?= htmlspecialchars($item['code']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($isService): ?>
                                                <span class="badge-service"><i class="fas fa-scale-balanced"></i> SAC (Service)</span>
                                            <?php else: ?>
                                                <span class="badge-goods"><i class="fas fa-box"></i> HSN (Goods)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem; line-height: 1.45;">
                                                <?= htmlspecialchars($item['description'] ?? '—') ?>
                                            </div>
                                            <?php if (!empty($item['heading_description']) || !empty($item['section'])): ?>
                                                <small style="color: var(--text-muted); font-size: 0.775rem; display: block; margin-top: 0.25rem;">
                                                    <?= htmlspecialchars($item['heading_description'] ?: $item['section']) ?>
                                                    <?= !empty($item['heading']) ? ' (Heading ' . htmlspecialchars($item['heading']) . ')' : '' ?>
                                                </small>
                                            <?php endif; ?>
                                            
                                            <?php if ($isRcm): ?>
                                                <div class="badge-rcm" title="Legal Services Reverse Charge Mechanism">
                                                    <i class="fas fa-shield-halved"></i> <strong>RCM Mandatory:</strong> <?= htmlspecialchars($item['rcm_note']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-rate">
                                                <i class="fas fa-percent" style="font-size: 0.75rem;"></i> <?= htmlspecialchars($item['rate_str']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button" class="copy-btn-mini" onclick="copyText('<?= htmlspecialchars(addslashes($item['code'] . ' - ' . $item['description'])) ?>', 'HSN details copied!')" title="Copy code and description">
                                                <i class="fas fa-copy"></i> Copy
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red, #c00000); padding: 1.75rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="fas fa-circle-exclamation" style="color: var(--brand-red, #c00000); font-size: 1.5rem; margin-top: 2px;"></i>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Classification Records Found</h3>
                            <p style="color: #7f1d1d; margin: 0; font-size: 0.925rem;">
                                <?= htmlspecialchars($searchResult['message'] ?? "No GST HSN/SAC records matched your search query \"{$query}\".") ?>
                            </p>
                            <p style="color: #7f1d1d; margin: 0.65rem 0 0; font-size: 0.85rem; line-height: 1.5;">
                                <strong>Search Suggestions:</strong><br>
                                &bull; Enter the 2, 4, 6, or 8 digit HSN code prefix (e.g. <code>9982</code> for legal services, <code>8471</code> for computers, <code>3004</code> for pharma).<br>
                                &bull; Enter descriptive terms like <code>Advocate</code>, <code>Legal Advisory</code>, <code>Software</code>, <code>Medicines</code>, or <code>Textiles</code>.<br>
                                &bull; Switch between <strong>All Codes</strong>, <strong>Goods (HSN)</strong>, and <strong>Services (SAC)</strong> using the toggle buttons above.
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Educational & Statutory Guide: How Indian GST HSN & SAC Codes Work -->
        <div class="guide-card" style="margin-top: 1rem; border-left: 4px solid var(--brand-gold, #ffc000);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 style="font-size: 1.35rem; color: var(--primary); margin: 0; font-weight: 800;">
                        <i class="fas fa-diagram-project" style="color: var(--brand-gold-dark, #b45309);"></i> Anatomy of Indian GST HSN &amp; SAC Codes
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
                        Established by the Central Board of Indirect Taxes and Customs (CBIC) for systematic tax classification under GST law.
                    </p>
                </div>
                <span class="stat-pill" style="border-color: #fde047; background: #fefce8; color: #854d0e;">
                    <i class="fas fa-check-double"></i> CBIC Statutory Standard
                </span>
            </div>

            <!-- 8-Digit HSN Goods Diagrammatic Breakdown -->
            <div style="margin-top: 1.75rem;">
                <span style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
                    1. 8-Digit HSN Format for Goods (e.g. 8471 30 10 - Laptops / Micro-Computers):
                </span>
                <div class="digit-diagram">
                    <div class="digit-box chapter">
                        <div class="digit-val">84</div>
                        <div class="digit-lbl">Chapter</div>
                    </div>
                    <div class="digit-box heading">
                        <div class="digit-val">71</div>
                        <div class="digit-lbl">Heading</div>
                    </div>
                    <div class="digit-box subheading">
                        <div class="digit-val">30</div>
                        <div class="digit-lbl">Sub-Heading</div>
                    </div>
                    <div class="digit-box tariff">
                        <div class="digit-val">10</div>
                        <div class="digit-lbl">Tariff Item</div>
                    </div>
                </div>
            </div>

            <!-- 6-Digit SAC Services Diagrammatic Breakdown -->
            <div style="margin-top: 1.75rem;">
                <span style="font-size: 0.8rem; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
                    2. 6-Digit SAC Format for Services (e.g. 9982 11 - Legal Advisory &amp; Criminal Representation):
                </span>
                <div class="digit-diagram">
                    <div class="digit-box chapter" style="border-color: #6b21a8; background: #faf5ff;">
                        <div class="digit-val" style="color: #581c87;">99</div>
                        <div class="digit-lbl">Services Chapter</div>
                    </div>
                    <div class="digit-box heading">
                        <div class="digit-val">82</div>
                        <div class="digit-lbl">Heading (Legal)</div>
                    </div>
                    <div class="digit-box subheading">
                        <div class="digit-val">1</div>
                        <div class="digit-lbl">Group</div>
                    </div>
                    <div class="digit-box tariff">
                        <div class="digit-val">1</div>
                        <div class="digit-lbl">Service Code</div>
                    </div>
                </div>
            </div>

            <!-- 3 Informational Pillars -->
            <div class="guide-grid">
                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-scale-balanced" style="color: var(--brand-red, #c00000);"></i> Advocate Legal Services (SAC 9982)
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0 0 0.5rem;">
                        Crucial tax implications for Advocates, Senior Advocates, and Law Firms in India:
                    </p>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; padding-left: 1.25rem; margin: 0;">
                        <li><strong>Section 9(3) CGST Act (RCM):</strong> Legal services rendered to a business entity are taxed under the <em>Reverse Charge Mechanism</em>. The recipient business pays 18% GST.</li>
                        <li><strong>Notification No. 13/2017-CT(R):</strong> Advocates are generally not required to collect GST directly or charge forward GST on invoices to commercial clients.</li>
                        <li><strong>Invoice Compliance:</strong> Mention SAC <strong>9982</strong> with the declaration: <em>"Tax payable under Reverse Charge Mechanism"</em>.</li>
                    </ul>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-file-invoice" style="color: var(--brand-gold-dark, #b45309);"></i> Mandatory Turnover Rules
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0 0 0.5rem;">
                        Under Rule 46 of the CGST Rules, mandatory digits on B2B tax invoices are tied to annual aggregate turnover (AATO):
                    </p>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; padding-left: 1.25rem; margin: 0;">
                        <li><strong>Turnover up to ₹5 Crore:</strong> Minimum <strong>4-digit</strong> HSN code on B2B invoices (optional for B2C).</li>
                        <li><strong>Turnover above ₹5 Crore:</strong> Mandatory <strong>6-digit</strong> HSN code on all B2B invoices and e-invoices.</li>
                        <li><strong>Export &amp; Import:</strong> Mandatory <strong>8-digit</strong> tariff item code regardless of business size.</li>
                    </ul>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-building-columns" style="color: #2563eb;"></i> Official Portals &amp; Verification
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0 0 0.75rem;">
                        Cross-verify rates and official departmental notifications on Government of India portals:
                    </p>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <a href="https://www.gst.gov.in/" target="_blank" rel="noopener noreferrer" style="font-size: 0.8125rem; color: #2563eb; text-decoration: underline; font-weight: 600;">
                            <i class="fas fa-arrow-up-right-from-square"></i> GST Portal (Services &gt; Search HSN)
                        </a>
                        <a href="https://cbic-gst.gov.in/" target="_blank" rel="noopener noreferrer" style="font-size: 0.8125rem; color: #2563eb; text-decoration: underline; font-weight: 600;">
                            <i class="fas fa-arrow-up-right-from-square"></i> CBIC GST Law &amp; Notifications
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Floating Toast Alert -->
<div id="toastBox">
    <i class="fas fa-circle-check" style="color: #22c55e;"></i>
    <span id="toastMsg">Copied!</span>
</div>

<!-- Interactive Client-side Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('hsnSearchForm');
    const input = document.getElementById('hsnQuery');
    const typeInput = document.getElementById('typeInput');
    const clearBtn = document.getElementById('clearBtn');
    const resultsWrapper = document.getElementById('resultsWrapper');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const quickChips = document.querySelectorAll('.quick-chip');
    const tableFilter = document.getElementById('tableFilterInput');

    // Toggle clear button
    input.addEventListener('input', function() {
        clearBtn.style.display = this.value.trim() ? 'block' : 'none';
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        clearBtn.style.display = 'none';
        input.focus();
    });

    // In-table live search filter
    if (tableFilter) {
        tableFilter.addEventListener('input', function() {
            const filterVal = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.hsn-row');
            rows.forEach(row => {
                const text = row.getAttribute('data-search') || '';
                row.style.display = text.includes(filterVal) ? '' : 'none';
            });
        });
    }

    // Quick chip click handler
    quickChips.forEach(chip => {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            const code = this.getAttribute('data-code');
            if (code) {
                input.value = code;
                clearBtn.style.display = 'block';
                performSearch(code, typeInput.value);
            }
        });
    });

    // AJAX form submission for fast interactive experience
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const q = input.value.trim();
        performSearch(q, typeInput.value);
    });

    window.setTypeFilter = function(type) {
        typeInput.value = type;
        ['btnToggleAll', 'btnToggleGoods', 'btnToggleServices'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        const activeBtn = document.getElementById(
            type === 'goods' ? 'btnToggleGoods' :
            type === 'service' ? 'btnToggleServices' : 'btnToggleAll'
        );
        if (activeBtn) activeBtn.classList.add('active');

        performSearch(input.value.trim(), type);
    };

    function performSearch(query, type) {
        const newUrl = 'search/hsn-sac.php?' + new URLSearchParams({ q: query, type: type }).toString();
        window.history.pushState({ q: query, type: type }, '', newUrl);

        loadingIndicator.style.display = 'block';
        resultsWrapper.style.opacity = '0.35';

        fetch('search/hsn-sac.php?ajax=1&q=' + encodeURIComponent(query) + '&type=' + encodeURIComponent(type))
            .then(res => res.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                renderResults(data, query, type);
            })
            .catch(err => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                resultsWrapper.innerHTML = `
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red, #c00000); padding: 1.5rem; margin-bottom: 2rem;">
                        <i class="fas fa-circle-exclamation text-danger"></i> Failed to query GST HSN/SAC catalog. Please check your connection and try again.
                    </div>
                `;
            });
    }

    function renderResults(res, query, type) {
        if (res.status === 'success' && res.data && res.data.length > 0) {
            const items = res.data;
            const total = items.length;
            const isDef = res.is_default;

            let rowsHtml = '';
            items.forEach((item, idx) => {
                const isServ = item.type === 'service';
                const safeCode = escapeHtml(item.code || '');
                const safeDesc = escapeHtml(item.description || '—');
                const safeRate = escapeHtml(item.rate_str || 'Standard');
                const safeHead = escapeHtml(item.heading_description || item.section || '');
                const searchBlob = (safeCode + ' ' + safeDesc + ' ' + safeHead + ' ' + safeRate).toLowerCase();
                const copyPayload = escapeQuotes(safeCode + ' - ' + safeDesc);

                rowsHtml += `
                    <tr class="hsn-row" data-search="${searchBlob}">
                        <td><span style="color: var(--text-muted); font-weight: 600;">${idx + 1}</span></td>
                        <td>
                            <span class="hsn-badge-code" onclick="copyText('${safeCode}', 'Code ${safeCode} copied!')" title="Click to copy Code">
                                <i class="fas fa-copy"></i> ${safeCode}
                            </span>
                        </td>
                        <td>
                            ${isServ 
                                ? `<span class="badge-service"><i class="fas fa-scale-balanced"></i> SAC (Service)</span>`
                                : `<span class="badge-goods"><i class="fas fa-box"></i> HSN (Goods)</span>`
                            }
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem; line-height: 1.45;">
                                ${safeDesc}
                            </div>
                            ${safeHead ? `<small style="color: var(--text-muted); font-size: 0.775rem; display: block; margin-top: 0.25rem;">${safeHead} ${item.heading ? '(Heading ' + escapeHtml(item.heading) + ')' : ''}</small>` : ''}
                            ${item.is_rcm ? `
                                <div class="badge-rcm" title="Legal Services Reverse Charge Mechanism">
                                    <i class="fas fa-shield-halved"></i> <strong>RCM Mandatory:</strong> ${escapeHtml(item.rcm_note)}
                                </div>
                            ` : ''}
                        </td>
                        <td>
                            <span class="badge-rate">
                                <i class="fas fa-percent" style="font-size: 0.75rem;"></i> ${safeRate}
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="copy-btn-mini" onclick="copyText('${copyPayload}', 'HSN details copied!')" title="Copy code and description">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </td>
                    </tr>
                `;
            });

            resultsWrapper.innerHTML = `
                <div class="results-card">
                    <div class="results-header">
                        <div>
                            <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                                <i class="fas fa-barcode" style="color: var(--brand-red, #c00000);"></i>
                                ${isDef ? 'Essential Legal &amp; Commercial Classifications' : 'Results for: <span style="color: var(--brand-red, #c00000);">' + escapeHtml(query) + '</span>'}
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                ${isDef ? 'Showing standard advocate legal services and frequent GST codes.' : 'Filtered by: ' + (type === 'all' ? 'All Goods & Services' : type.charAt(0).toUpperCase() + type.slice(1))} &bull; Source: Official CBIC Tariff Master
                            </p>
                        </div>
                        <div class="results-stats">
                            <span class="stat-pill">
                                <i class="fas fa-list-check" style="color: #2563eb;"></i> ${total} ${total === 1 ? 'Code Found' : 'Codes Found'}
                            </span>
                            <button type="button" class="btn btn-outline btn-sm" onclick="copyAllCodes()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-copy"></i> Copy Codes
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>

                    <div style="padding: 0.75rem 1.5rem; background: #fafafa; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                            Live instant filter across active results:
                        </div>
                        <div style="position: relative; min-width: 260px;">
                            <i class="fas fa-filter" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                            <input type="text" id="tableFilterInput" placeholder="Filter by code or description..." 
                                   style="width: 100%; padding: 0.4rem 0.75rem 0.4rem 2rem; font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
                        </div>
                    </div>

                    <div class="hsn-table-responsive">
                        <table class="hsn-table" id="hsnDataTable">
                            <thead>
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th style="min-width: 110px;">HSN / SAC Code</th>
                                    <th style="min-width: 140px;">Category</th>
                                    <th>Description &amp; Heading</th>
                                    <th style="min-width: 130px;">GST Rate</th>
                                    <th style="text-align: right; min-width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            // Bind new table filter input
            const newFilter = document.getElementById('tableFilterInput');
            if (newFilter) {
                newFilter.addEventListener('input', function() {
                    const filterVal = this.value.toLowerCase().trim();
                    const rows = document.querySelectorAll('.hsn-row');
                    rows.forEach(row => {
                        const text = row.getAttribute('data-search') || '';
                        row.style.display = text.includes(filterVal) ? '' : 'none';
                    });
                });
            }
        } else {
            resultsWrapper.innerHTML = `
                <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red, #c00000); padding: 1.75rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="fas fa-circle-exclamation" style="color: var(--brand-red, #c00000); font-size: 1.5rem; margin-top: 2px;"></i>
                        <div>
                            <h3 style="font-size: 1.15rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Classification Records Found</h3>
                            <p style="color: #7f1d1d; margin: 0; font-size: 0.925rem;">
                                No GST HSN/SAC records matched your search query "${escapeHtml(query)}".
                            </p>
                            <p style="color: #7f1d1d; margin: 0.65rem 0 0; font-size: 0.85rem; line-height: 1.5;">
                                <strong>Search Suggestions:</strong><br>
                                &bull; Enter the 2, 4, 6, or 8 digit HSN code prefix (e.g. <code>9982</code> for legal services, <code>8471</code> for computers, <code>3004</code> for pharma).<br>
                                &bull; Enter descriptive terms like <code>Advocate</code>, <code>Legal Advisory</code>, <code>Software</code>, <code>Medicines</code>, or <code>Textiles</code>.<br>
                                &bull; Switch between <strong>All Codes</strong>, <strong>Goods (HSN)</strong>, and <strong>Services (SAC)</strong> using the toggle buttons above.
                            </p>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function escapeQuotes(str) {
        return String(str || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
});

// Global copy helpers
function copyText(text, successMsg) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        showToast(successMsg || 'Copied to clipboard!');
    }).catch(() => {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        showToast(successMsg || 'Copied to clipboard!');
    });
}

function copyAllCodes() {
    const badges = document.querySelectorAll('.hsn-badge-code');
    if (!badges.length) return;
    const list = [];
    badges.forEach(b => list.push(b.innerText.trim()));
    copyText(list.join(', '), `${list.length} GST HSN/SAC codes copied!`);
}

function showToast(msg) {
    const toast = document.getElementById('toastBox');
    const toastMsg = document.getElementById('toastMsg');
    if (toast && toastMsg) {
        toastMsg.innerText = msg;
        toast.style.display = 'flex';
        clearTimeout(window.toastTimer);
        window.toastTimer = setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
