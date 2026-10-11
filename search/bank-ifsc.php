<?php
// search/bank-ifsc.php - Indian Bank IFSC & MICR Code Search & Verification
require_once dirname(__DIR__) . '/config/app.php';

$pageTitle = "Bank IFSC & MICR Code Search - RBI Branch Verification";
$pageDescription = "Lookup Indian Bank IFSC & MICR codes by 11-digit IFSC code. View branch address, district, state, phone, and NEFT, RTGS, IMPS, UPI support across all Indian banks.";
$pageKeywords = "bank ifsc search, ifsc code finder, micr code search, rbi ifsc verification, find bank branch by ifsc, neft rtgs ifsc lookup, sbi hdfc icici ifsc code";
$currentPage = 'ifsc';
$pageBaseUrl = '../';

/**
 * Call OLAW API for Bank IFSC lookup
 */
function fetchOlawIfsc(string $cleanIfsc): ?array {
    $apiKey = defined('OLAW_API_KEY') ? OLAW_API_KEY : 'OLAW_D776A66967200383A932';
    $apiUrl = defined('OLAW_API_URL') ? OLAW_API_URL : 'https://olaw.in/api.php';

    $url = $apiUrl . '?' . http_build_query([
        'api_key' => $apiKey,
        'action'  => 'bank-ifsc',
        'ifsc'    => $cleanIfsc
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'MyAdvocate-IfscTool/2.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;
    $data = json_decode($response, true);
    if (!empty($data) && ($data['status'] ?? '') === 'success' && !empty($data['data'])) {
        return $data['data'];
    }
    return null;
}

/**
 * Call Razorpay IFSC API for fallback & payment rail verification (NEFT, RTGS, IMPS, UPI)
 */
function fetchRazorpayIfsc(string $cleanIfsc): ?array {
    $url = "https://ifsc.razorpay.com/" . urlencode($cleanIfsc);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'MyAdvocate-RazorpayIfsc/2.0'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data['IFSC'])) {
            return $data;
        }
    }
    return null;
}

/**
 * Normalize and combine bank details from APIs
 */
function normalizeIfscResult(string $query): array {
    $cleanIfsc = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $query)));
    
    if (empty($cleanIfsc)) {
        return [
            'status'  => 'error',
            'message' => 'Please enter an 11-digit Bank IFSC code (e.g. SBIN0000001, HDFC0000001).',
            'data'    => null
        ];
    }

    if (strlen($cleanIfsc) !== 11) {
        return [
            'status'  => 'error',
            'message' => "Invalid IFSC code length ({$cleanIfsc}). Bank IFSC codes must be exactly 11 alphanumeric characters (e.g. SBIN0000001).",
            'data'    => null
        ];
    }

    // 1. Try OLAW API
    $olawData = fetchOlawIfsc($cleanIfsc);

    // 2. Try Razorpay IFSC API (for payment systems & fallback)
    $rzpData = fetchRazorpayIfsc($cleanIfsc);

    if (!$olawData && !$rzpData) {
        return [
            'status'  => 'not_found',
            'query'   => $cleanIfsc,
            'message' => "No bank branch record found for IFSC: \"{$cleanIfsc}\". Please verify the code and try again.",
            'data'    => null
        ];
    }

    // Merge data from both sources for maximum richness
    $bankName = $olawData['bank'] ?? ($rzpData['BANK'] ?? 'Bank Details');
    $branch   = $olawData['branch'] ?? ($rzpData['BRANCH'] ?? '');
    $address  = $olawData['address'] ?? ($rzpData['ADDRESS'] ?? '');
    $micr     = $olawData['micr'] ?? ($rzpData['MICR'] ?? '');
    $city     = $olawData['city1'] ?: ($olawData['city2'] ?? ($rzpData['CITY'] ?? ($rzpData['CENTRE'] ?? '')));
    $district = $rzpData['DISTRICT'] ?? ($olawData['city2'] ?? $city);
    $state    = $olawData['state'] ?? ($rzpData['STATE'] ?? '');
    $phone    = $olawData['phone'] ? ($olawData['stdcode'] ? $olawData['stdcode'] . '-' : '') . $olawData['phone'] : ($rzpData['CONTACT'] ?? '');
    $bankCode = $rzpData['BANKCODE'] ?? substr($cleanIfsc, 0, 4);

    $neft = isset($rzpData['NEFT']) ? (bool)$rzpData['NEFT'] : true;
    $rtgs = isset($rzpData['RTGS']) ? (bool)$rzpData['RTGS'] : true;
    $imps = isset($rzpData['IMPS']) ? (bool)$rzpData['IMPS'] : true;
    $upi  = isset($rzpData['UPI']) ? (bool)$rzpData['UPI'] : true;

    $normalized = [
        'bank_name'  => $bankName,
        'ifsc'       => $cleanIfsc,
        'micr'       => $micr ?: '—',
        'bank_code'  => $bankCode,
        'branch'     => $branch ?: 'Main Branch',
        'address'    => $address ?: '—',
        'city'       => $city ?: '—',
        'district'   => $district ?: '—',
        'state'        => $state ?: '—',
        'has_phone'    => (!empty($phone) && $phone !== '—' && trim((string)$phone) !== ''),
        'phone_status' => (!empty($phone) && $phone !== '—' && trim((string)$phone) !== '') ? 'Available' : 'Not Available',
        'neft'         => $neft,
        'rtgs'         => $rtgs,
        'imps'         => $imps,
        'upi'          => $upi,
        'swift'        => $rzpData['SWIFT'] ?? null,
        'status'       => 'ACTIVE',
        'source'       => $olawData ? ($rzpData ? 'RBI Master & OLAW API' : 'OLAW API') : 'RBI Master API'
    ];

    return [
        'status' => 'success',
        'query'  => $cleanIfsc,
        'data'   => $normalized
    ];
}

// Handle AJAX Request
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $q = sanitize($_GET['q'] ?? ($_GET['ifsc'] ?? ''));
    echo json_encode(normalizeIfscResult($q));
    exit;
}

// Initial GET search
$query = sanitize($_GET['q'] ?? ($_GET['ifsc'] ?? ''));
$searchResult = null;
$searched = false;

if (!empty($query)) {
    $searched = true;
    $searchResult = normalizeIfscResult($query);
}

require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* Custom styling for Bank IFSC Search Module */
.ifsc-search-hero {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 35%, #c00000 70%, #580c0c 100%);
    color: #ffffff;
    padding: 3.75rem 1rem 3.25rem;
    position: relative;
    overflow: hidden;
    border-bottom: 4px solid var(--brand-yellow, #ffc000);
}
.ifsc-search-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 80% 15%, rgba(255, 192, 0, 0.22) 0%, transparent 50%),
                radial-gradient(circle at 15% 85%, rgba(0, 0, 0, 0.35) 0%, transparent 60%);
    pointer-events: none;
}
.ifsc-hero-content {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
}
.ifsc-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: #ffea79;
    padding: 0.4rem 1rem;
    border-radius: 999px;
    font-size: 0.825rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 1.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.ifsc-search-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 0.5rem;
    box-shadow: 0 20px 45px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    max-width: 760px;
    margin: 2rem auto 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.ifsc-search-box:focus-within {
    box-shadow: 0 24px 50px -10px rgba(0, 0, 0, 0.6), 0 0 0 3px #ffc000;
}
.ifsc-input-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    padding-left: 1rem;
    gap: 0.75rem;
}
.ifsc-input-wrap i {
    color: var(--brand-red, #c00000);
    font-size: 1.25rem;
}
.ifsc-input-field {
    width: 100%;
    border: none;
    outline: none;
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    background: transparent;
    font-family: monospace, inherit;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.ifsc-input-field::placeholder {
    color: #94a3b8;
    font-weight: 400;
    font-size: 1rem;
    letter-spacing: normal;
    text-transform: none;
}
.ifsc-search-btn {
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: #ffffff;
    border: none;
    padding: 0.85rem 1.75rem;
    font-size: 1rem;
    font-weight: 700;
    border-radius: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    white-space: nowrap;
}
.ifsc-search-btn:hover {
    background: linear-gradient(135deg, #000000 0%, #111827 100%);
    color: #ffc000;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
}
.quick-chips-wrap {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1.25rem;
}
.chip-label {
    font-size: 0.8125rem;
    color: #fef08a;
    font-weight: 700;
    margin-right: 0.25rem;
}
.quick-chip {
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.24);
    color: #ffffff;
    padding: 0.35rem 0.8rem;
    border-radius: 8px;
    font-size: 0.8125rem;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.quick-chip:hover {
    background: rgba(255, 255, 255, 0.28);
    color: #ffffff;
    border-color: #ffc000;
    transform: translateY(-1px);
}
.quick-chip strong {
    color: #ffea79;
}

/* Results Card Styles */
.results-container {
    padding: 2.5rem 0 4rem;
}
.bank-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 24px -4px rgba(0, 0, 0, 0.08);
    margin-bottom: 2.5rem;
}
.bank-card-header {
    background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    padding: 1.5rem 1.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.25rem;
}
.bank-name-badge {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--primary, #0f172a);
    margin: 0;
    font-family: var(--font-heading);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.bank-grid-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 1.75rem;
}
.detail-table-card {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    overflow: hidden;
}
.detail-table-card h4 {
    background: #f8fafc;
    padding: 0.75rem 1.25rem;
    margin: 0;
    font-size: 0.875rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #334155;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.detail-table {
    width: 100%;
    border-collapse: collapse;
}
.detail-table th {
    width: 140px;
    padding: 0.85rem 1.25rem;
    font-size: 0.825rem;
    color: #64748b;
    font-weight: 700;
    text-align: left;
    background: #fafafa;
    border-bottom: 1px solid #f1f5f9;
}
.detail-table td {
    padding: 0.85rem 1.25rem;
    font-size: 0.925rem;
    color: #0f172a;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 600;
}
.ifsc-copy-badge {
    background: #fef3c7;
    color: #92400e;
    font-family: monospace;
    font-size: 1.25rem;
    font-weight: 800;
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
    letter-spacing: 0.08em;
    border: 1px solid #fde68a;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.ifsc-copy-badge:hover {
    background: #fde68a;
    transform: scale(1.02);
}
.rail-badge {
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    font-size: 0.775rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.rail-active {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.rail-inactive {
    background: #f1f5f9;
    color: #94a3b8;
    border: 1px solid #e2e8f0;
}

/* Diagram */
.ifsc-diagram-wrap {
    display: flex;
    gap: 0.5rem;
    margin: 1.5rem 0;
    justify-content: center;
}
.ifsc-box {
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.75rem 0.5rem;
    text-align: center;
    flex: 1;
    min-width: 45px;
}
.ifsc-box.bank {
    border-color: #2563eb;
    background: #eff6ff;
}
.ifsc-box.reserved {
    border-color: var(--brand-red, #dc2626);
    background: #fef2f2;
}
.ifsc-box.branch {
    border-color: #16a34a;
    background: #f0fdf4;
}
.ifsc-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 0.35rem;
    font-family: monospace;
}
.ifsc-lbl {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 0.04em;
}

/* Toast */
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
    .ifsc-search-box {
        flex-direction: column;
        padding: 0.75rem;
    }
    .ifsc-input-wrap {
        width: 100%;
        padding-left: 0.25rem;
    }
    .ifsc-search-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- Hero Section with Search Bar -->
<section class="ifsc-search-hero">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav style="font-size: 0.8125rem; color: #fecaca; margin-bottom: 1.5rem; text-align: center;">
            <a href="./" style="color: #ffffff; text-decoration: none; font-weight: 600;">Home</a> &bull; 
            <span style="color: #fca5a5;">Search</span> &bull; 
            <span style="color: #ffea79; font-weight: 800;">Bank IFSC &amp; MICR</span>
        </nav>

        <div class="ifsc-hero-content">
            <div class="ifsc-badge">
                <i class="fas fa-building-columns"></i> Official RBI Bank Directory &amp; OLAW API
            </div>
            <h1 style="font-size: 2.5rem; font-weight: 900; line-height: 1.2; margin-bottom: 0.85rem; font-family: var(--font-heading); color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.25);">
                Indian Bank <span style="color: #ffc000; text-shadow: 0 2px 12px rgba(0,0,0,0.4);">IFSC &amp; MICR</span> Search
            </h1>
            <p style="font-size: 1.1rem; color: #ffffff; max-width: 720px; margin: 0 auto; line-height: 1.65; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.2);">
                Verify 11-digit Bank IFSC codes, MICR numbers, branch addresses, contact details, and electronic payment systems (NEFT, RTGS, IMPS, UPI) nationwide.
            </p>

            <!-- Search Form -->
            <form id="ifscSearchForm" action="search/bank-ifsc.php" method="GET" class="ifsc-search-box">
                <div class="ifsc-input-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" 
                           id="ifscQuery" 
                           name="q" 
                           class="ifsc-input-field" 
                           placeholder="Enter 11-digit Bank IFSC (e.g. SBIN0000001, HDFC0000001)..." 
                           value="<?= htmlspecialchars($query) ?>" 
                           maxlength="11"
                           autocomplete="off"
                           required>
                    <button type="button" id="clearBtn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.25rem 0.5rem; display: <?= !empty($query) ? 'block' : 'none' ?>;" title="Clear input">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <button type="submit" class="ifsc-search-btn" id="searchSubmitBtn">
                    <span id="searchBtnIcon"><i class="fas fa-arrow-right"></i></span>
                    <span id="searchBtnText">Verify IFSC</span>
                </button>
            </form>

            <!-- Quick Pick Chips for Major National & Private Banks -->
            <div class="quick-chips-wrap">
                <span class="chip-label"><i class="fas fa-bolt"></i> Popular Banks:</span>
                <a href="search/bank-ifsc.php?q=SBIN0000001" class="quick-chip" data-ifsc="SBIN0000001">
                    <span>SBI Main</span> <strong>SBIN0000001</strong>
                </a>
                <a href="search/bank-ifsc.php?q=HDFC0000001" class="quick-chip" data-ifsc="HDFC0000001">
                    <span>HDFC Bank</span> <strong>HDFC0000001</strong>
                </a>
                <a href="search/bank-ifsc.php?q=ICIC0000001" class="quick-chip" data-ifsc="ICIC0000001">
                    <span>ICICI Bank</span> <strong>ICIC0000001</strong>
                </a>
                <a href="search/bank-ifsc.php?q=PUNB0000100" class="quick-chip" data-ifsc="PUNB0000100">
                    <span>PNB</span> <strong>PUNB0000100</strong>
                </a>
                <a href="search/bank-ifsc.php?q=BARB0VJMGRE" class="quick-chip" data-ifsc="BARB0VJMGRE">
                    <span>Bank of Baroda</span> <strong>BARB0VJMGRE</strong>
                </a>
                <a href="search/bank-ifsc.php?q=UTIB0000001" class="quick-chip" data-ifsc="UTIB0000001">
                    <span>Axis Bank</span> <strong>UTIB0000001</strong>
                </a>
                <a href="search/bank-ifsc.php?q=CNRB0000001" class="quick-chip" data-ifsc="CNRB0000001">
                    <span>Canara Bank</span> <strong>CNRB0000001</strong>
                </a>
                <a href="search/bank-ifsc.php?q=UBIN0530018" class="quick-chip" data-ifsc="UBIN0530018">
                    <span>Union Bank</span> <strong>UBIN0530018</strong>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Results & Information Area -->
<main class="results-container">
    <div class="container">
        
        <!-- Live Loading Spinner -->
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 3rem 1rem;">
            <div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #f1f5f9; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">
                Verifying Bank IFSC with Reserve Bank of India &amp; OLAW API...
            </p>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

        <!-- Dynamic Results Target -->
        <div id="resultsWrapper">
            <?php if ($searched): ?>
                <?php if (($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
                    $bank = $searchResult['data'];
                    $fullText = "Bank: {$bank['bank_name']}\nIFSC: {$bank['ifsc']}\nMICR: {$bank['micr']}\nBranch: {$bank['branch']}\nAddress: {$bank['address']}\nCity: {$bank['city']}\nState: {$bank['state']}";
                ?>
                    <div class="bank-card">
                        <div class="bank-card-header">
                            <div>
                                <h2 class="bank-name-badge">
                                    <i class="fas fa-building-columns" style="color: #2563eb;"></i>
                                    <?= htmlspecialchars($bank['bank_name']) ?>
                                </h2>
                                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                    Branch: <strong><?= htmlspecialchars($bank['branch']) ?></strong> &bull; <?= htmlspecialchars($bank['city']) ?>, <?= htmlspecialchars($bank['state']) ?> &bull; Source: <?= htmlspecialchars($bank['source'] ?? 'RBI Master') ?>
                                </p>
                            </div>

                            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                <span class="badge-verification badge-verified" style="font-size: 0.8rem; padding: 0.45rem 0.85rem;">
                                    <i class="fas fa-shield-check"></i> Verified RBI IFSC Record
                                </span>
                                <button type="button" class="btn btn-outline btn-sm" onclick="copyText('<?= htmlspecialchars(addslashes($fullText)) ?>', 'Bank details copied!')">
                                    <i class="fas fa-copy"></i> Copy Details
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>

                        <!-- Payment Rail Capabilities Strip -->
                        <div style="padding: 0.85rem 1.75rem; background: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <span style="font-size: 0.825rem; font-weight: 700; color: #475569; margin-right: 0.25rem;">
                                <i class="fas fa-money-bill-transfer"></i> Supported Payment Rails:
                            </span>
                            <span class="rail-badge <?= $bank['neft'] ? 'rail-active' : 'rail-inactive' ?>">
                                <i class="fas <?= $bank['neft'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> NEFT <?= $bank['neft'] ? 'Supported' : 'No' ?>
                            </span>
                            <span class="rail-badge <?= $bank['rtgs'] ? 'rail-active' : 'rail-inactive' ?>">
                                <i class="fas <?= $bank['rtgs'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> RTGS <?= $bank['rtgs'] ? 'Supported' : 'No' ?>
                            </span>
                            <span class="rail-badge <?= $bank['imps'] ? 'rail-active' : 'rail-inactive' ?>">
                                <i class="fas <?= $bank['imps'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> IMPS <?= $bank['imps'] ? 'Supported' : 'No' ?>
                            </span>
                            <span class="rail-badge <?= $bank['upi'] ? 'rail-active' : 'rail-inactive' ?>">
                                <i class="fas <?= $bank['upi'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i> UPI <?= $bank['upi'] ? 'Enabled' : 'No' ?>
                            </span>
                        </div>

                        <!-- 2-Column Information Grid -->
                        <div class="bank-grid-details">
                            <!-- Card 1: Clearing & Financial Identifiers -->
                            <div class="detail-table-card">
                                <h4><i class="fas fa-hashtag text-primary"></i> Financial &amp; Clearing Codes</h4>
                                <table class="detail-table">
                                    <tr>
                                        <th>IFSC Code</th>
                                        <td>
                                            <span class="ifsc-copy-badge" onclick="copyText('<?= htmlspecialchars($bank['ifsc']) ?>', 'IFSC Code <?= htmlspecialchars($bank['ifsc']) ?> copied!')" title="Click to copy IFSC">
                                                <i class="fas fa-copy"></i> <?= htmlspecialchars($bank['ifsc']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>MICR Code</th>
                                        <td>
                                            <?php if ($bank['micr'] !== '—'): ?>
                                                <span style="font-family: monospace; font-size: 1rem; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; cursor: pointer;" onclick="copyText('<?= htmlspecialchars($bank['micr']) ?>', 'MICR copied!')" title="Click to copy MICR">
                                                    <?= htmlspecialchars($bank['micr']) ?> <i class="fas fa-copy" style="font-size: 0.75rem; color: #64748b; margin-left: 4px;"></i>
                                                </span>
                                            <?php else: ?>
                                                <span>—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Bank Name</th>
                                        <td><strong style="color: var(--primary);"><?= htmlspecialchars($bank['bank_name']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Branch Name</th>
                                        <td><?= htmlspecialchars($bank['branch']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Bank Code</th>
                                        <td><code style="background: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?= htmlspecialchars($bank['bank_code']) ?></code></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Card 2: Branch Address & Jurisdiction -->
                            <div class="detail-table-card">
                                <h4><i class="fas fa-location-dot" style="color: var(--brand-red);"></i> Branch Address &amp; Location</h4>
                                <table class="detail-table">
                                    <tr>
                                        <th>Branch Address</th>
                                        <td style="line-height: 1.5;"><?= htmlspecialchars($bank['address']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>City / Centre</th>
                                        <td><?= htmlspecialchars($bank['city']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>District</th>
                                        <td><?= htmlspecialchars($bank['district']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>State / UT</th>
                                        <td><strong><?= htmlspecialchars($bank['state']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Contact / Phone</th>
                                        <td>
                                            <?php if (!empty($bank['has_phone'])): ?>
                                                <span class="rail-badge rail-active"><i class="fas fa-circle-check"></i> Available</span>
                                            <?php else: ?>
                                                <span class="rail-badge rail-inactive"><i class="fas fa-circle-xmark"></i> Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <i class="fas fa-circle-exclamation" style="color: var(--brand-red); font-size: 1.5rem; margin-top: 2px;"></i>
                            <div>
                                <h3 style="font-size: 1.1rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Bank Details Found</h3>
                                <p style="color: #7f1d1d; margin: 0; font-size: 0.9rem;">
                                    <?= htmlspecialchars($searchResult['message'] ?? "No record found for IFSC: \"{$query}\".") ?>
                                </p>
                                <p style="color: #7f1d1d; margin: 0.5rem 0 0; font-size: 0.85rem;">
                                    <strong>Tip:</strong> Ensure the IFSC code contains exactly 11 characters, where the 5th character is the number zero '0' (e.g. <code>SBIN0000001</code>, <code>HDFC0000001</code>).
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Educational & Architectural Guide: IFSC Code Structure -->
        <div class="guide-card" style="margin-top: 1rem; border-left: 4px solid #2563eb; background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 style="font-size: 1.35rem; color: var(--primary); margin: 0; font-weight: 800;">
                        <i class="fas fa-diagram-project" style="color: #2563eb;"></i> Understanding Indian Financial System Code (IFSC) Structure
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
                        Prescribed by the Reserve Bank of India (RBI) to uniquely identify participating bank branches for electronic fund transfers.
                    </p>
                </div>
                <span class="badge-verification badge-verified" style="font-size: 0.8rem;">
                    <i class="fas fa-check"></i> Standard 11-Character Format
                </span>
            </div>

            <!-- 11-Character Breakdown Diagram -->
            <div class="ifsc-diagram-wrap">
                <div class="ifsc-box bank"><div class="ifsc-val">S</div><div class="ifsc-lbl">Bank</div></div>
                <div class="ifsc-box bank"><div class="ifsc-val">B</div><div class="ifsc-lbl">Bank</div></div>
                <div class="ifsc-box bank"><div class="ifsc-val">I</div><div class="ifsc-lbl">Bank</div></div>
                <div class="ifsc-box bank"><div class="ifsc-val">N</div><div class="ifsc-lbl">Bank</div></div>
                <div class="ifsc-box reserved"><div class="ifsc-val">0</div><div class="ifsc-lbl">Reserved</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">0</div><div class="ifsc-lbl">Branch</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">0</div><div class="ifsc-lbl">Branch</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">0</div><div class="ifsc-lbl">Branch</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">0</div><div class="ifsc-lbl">Branch</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">0</div><div class="ifsc-lbl">Branch</div></div>
                <div class="ifsc-box branch"><div class="ifsc-val">1</div><div class="ifsc-lbl">Branch</div></div>
            </div>

            <div class="guide-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-1" style="color: #2563eb;"></i> First 4 Letters: Bank Code
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                        The first four characters represent the bank's unique identifier. Examples: <code>SBIN</code> for State Bank of India, <code>HDFC</code> for HDFC Bank, <code>ICIC</code> for ICICI Bank, <code>PUNB</code> for Punjab National Bank.
                    </p>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-0" style="color: var(--brand-red);"></i> 5th Character: Always Zero '0'
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                        By Reserve Bank of India regulation, the 5th character is strictly control digit zero (<code>0</code>). It is reserved for future expansion and ensures error-free OCR and checksum verification.
                    </p>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> Legal &amp; Litigation Uses
                    </h4>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; padding-left: 1.25rem; margin: 0;">
                        <li><strong>Section 138 NI Act:</strong> Locating the drawee bank branch in cheque bounce disputes for territorial jurisdiction.</li>
                        <li><strong>Order XXI Rule 46 CPC:</strong> Garnishee notices for bank account attachment.</li>
                        <li><strong>MACT Tribunals:</strong> Accurate bank details for direct compensation award dispatches.</li>
                    </ul>
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
    const form = document.getElementById('ifscSearchForm');
    const input = document.getElementById('ifscQuery');
    const clearBtn = document.getElementById('clearBtn');
    const resultsWrapper = document.getElementById('resultsWrapper');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const quickChips = document.querySelectorAll('.quick-chip');

    // Auto-uppercase & toggle clear button
    input.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        clearBtn.style.display = this.value.trim() ? 'block' : 'none';

        // Auto trigger search if user types full 11 chars
        if (this.value.length === 11) {
            performSearch(this.value);
        }
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        clearBtn.style.display = 'none';
        input.focus();
    });

    // Quick chip click handler
    quickChips.forEach(chip => {
        chip.addEventListener('click', function(e) {
            e.preventDefault();
            const ifsc = this.getAttribute('data-ifsc');
            if (ifsc) {
                input.value = ifsc;
                clearBtn.style.display = 'block';
                performSearch(ifsc);
            }
        });
    });

    // Form submit handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = input.value.trim();
        if (query) {
            performSearch(query);
        }
    });

    function performSearch(query) {
        const clean = query.toUpperCase().replace(/[^A-Z0-9]/g, '');
        const newUrl = 'search/bank-ifsc.php?q=' + encodeURIComponent(clean);
        window.history.pushState({ q: clean }, '', newUrl);

        loadingIndicator.style.display = 'block';
        resultsWrapper.style.opacity = '0.35';

        fetch('search/bank-ifsc.php?ajax=1&q=' + encodeURIComponent(clean))
            .then(res => res.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                renderResult(data, clean);
            })
            .catch(err => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                resultsWrapper.innerHTML = `
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                        <i class="fas fa-circle-exclamation text-danger"></i> Failed to communicate with Bank IFSC API. Please check your network and try again.
                    </div>
                `;
            });
    }

    function renderResult(res, query) {
        if (res.status === 'success' && res.data) {
            const b = res.data;
            const fullText = `Bank: ${b.bank_name}\nIFSC: ${b.ifsc}\nMICR: ${b.micr}\nBranch: ${b.branch}\nAddress: ${b.address}\nCity: ${b.city}\nState: ${b.state}`;

            resultsWrapper.innerHTML = `
                <div class="bank-card">
                    <div class="bank-card-header">
                        <div>
                            <h2 class="bank-name-badge">
                                <i class="fas fa-building-columns" style="color: #2563eb;"></i>
                                ${escapeHtml(b.bank_name)}
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                Branch: <strong>${escapeHtml(b.branch)}</strong> &bull; ${escapeHtml(b.city)}, ${escapeHtml(b.state)} &bull; Source: ${escapeHtml(b.source || 'RBI Master')}
                            </p>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <span class="badge-verification badge-verified" style="font-size: 0.8rem; padding: 0.45rem 0.85rem;">
                                <i class="fas fa-shield-check"></i> Verified RBI IFSC Record
                            </span>
                            <button type="button" class="btn btn-outline btn-sm" onclick="copyText('${escapeQuotes(fullText)}', 'Bank details copied!')">
                                <i class="fas fa-copy"></i> Copy Details
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>

                    <div style="padding: 0.85rem 1.75rem; background: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <span style="font-size: 0.825rem; font-weight: 700; color: #475569; margin-right: 0.25rem;">
                            <i class="fas fa-money-bill-transfer"></i> Supported Payment Rails:
                        </span>
                        <span class="rail-badge ${b.neft ? 'rail-active' : 'rail-inactive'}">
                            <i class="fas ${b.neft ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> NEFT ${b.neft ? 'Supported' : 'No'}
                        </span>
                        <span class="rail-badge ${b.rtgs ? 'rail-active' : 'rail-inactive'}">
                            <i class="fas ${b.rtgs ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> RTGS ${b.rtgs ? 'Supported' : 'No'}
                        </span>
                        <span class="rail-badge ${b.imps ? 'rail-active' : 'rail-inactive'}">
                            <i class="fas ${b.imps ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> IMPS ${b.imps ? 'Supported' : 'No'}
                        </span>
                        <span class="rail-badge ${b.upi ? 'rail-active' : 'rail-inactive'}">
                            <i class="fas ${b.upi ? 'fa-circle-check' : 'fa-circle-xmark'}"></i> UPI ${b.upi ? 'Enabled' : 'No'}
                        </span>
                    </div>

                    <div class="bank-grid-details">
                        <div class="detail-table-card">
                            <h4><i class="fas fa-hashtag text-primary"></i> Financial &amp; Clearing Codes</h4>
                            <table class="detail-table">
                                <tr>
                                    <th>IFSC Code</th>
                                    <td>
                                        <span class="ifsc-copy-badge" onclick="copyText('${escapeHtml(b.ifsc)}', 'IFSC Code ${escapeHtml(b.ifsc)} copied!')" title="Click to copy IFSC">
                                            <i class="fas fa-copy"></i> ${escapeHtml(b.ifsc)}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>MICR Code</th>
                                    <td>
                                        ${b.micr !== '—' ? `
                                            <span style="font-family: monospace; font-size: 1rem; font-weight: 700; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; cursor: pointer;" onclick="copyText('${escapeHtml(b.micr)}', 'MICR copied!')" title="Click to copy MICR">
                                                ${escapeHtml(b.micr)} <i class="fas fa-copy" style="font-size: 0.75rem; color: #64748b; margin-left: 4px;"></i>
                                            </span>
                                        ` : '<span>—</span>'}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td><strong style="color: var(--primary);">${escapeHtml(b.bank_name)}</strong></td>
                                </tr>
                                <tr>
                                    <th>Branch Name</th>
                                    <td>${escapeHtml(b.branch)}</td>
                                </tr>
                                <tr>
                                    <th>Bank Code</th>
                                    <td><code style="background: #eff6ff; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; font-weight: 700;">${escapeHtml(b.bank_code)}</code></td>
                                </tr>
                            </table>
                        </div>

                        <div class="detail-table-card">
                            <h4><i class="fas fa-location-dot" style="color: var(--brand-red);"></i> Branch Address &amp; Location</h4>
                            <table class="detail-table">
                                <tr>
                                    <th>Branch Address</th>
                                    <td style="line-height: 1.5;">${escapeHtml(b.address)}</td>
                                </tr>
                                <tr>
                                    <th>City / Centre</th>
                                    <td>${escapeHtml(b.city)}</td>
                                </tr>
                                <tr>
                                    <th>District</th>
                                    <td>${escapeHtml(b.district)}</td>
                                </tr>
                                <tr>
                                    <th>State / UT</th>
                                    <td><strong>${escapeHtml(b.state)}</strong></td>
                                </tr>
                                <tr>
                                    <th>Contact / Phone</th>
                                    <td>
                                        ${b.has_phone 
                                            ? '<span class="rail-badge rail-active"><i class="fas fa-circle-check"></i> Available</span>' 
                                            : '<span class="rail-badge rail-inactive"><i class="fas fa-circle-xmark"></i> Not Available</span>'
                                        }
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultsWrapper.innerHTML = `
                <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="fas fa-circle-exclamation" style="color: var(--brand-red); font-size: 1.5rem; margin-top: 2px;"></i>
                        <div>
                            <h3 style="font-size: 1.1rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Bank Details Found</h3>
                            <p style="color: #7f1d1d; margin: 0; font-size: 0.9rem;">
                                ${escapeHtml(res.message || 'No record found for IFSC: "' + query + '".')}
                            </p>
                            <p style="color: #7f1d1d; margin: 0.5rem 0 0; font-size: 0.85rem;">
                                <strong>Tip:</strong> Ensure the IFSC code contains exactly 11 characters, where the 5th character is the number zero '0' (e.g. <code>SBIN0000001</code>, <code>HDFC0000001</code>).
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

// Copy helper
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
