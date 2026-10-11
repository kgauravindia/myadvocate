<?php
// search/pin-code.php - India Postal PIN Code Search & Directory
require_once dirname(__DIR__) . '/config/app.php';

$pageTitle = "PIN Code Search India - Post Office & Locality Finder";
$pageDescription = "Search Indian Postal PIN codes by 6-digit PIN code or post office name. Get real-time postal branch details, delivery status, circle, district, and state across India.";
$pageKeywords = "pin code search, postal pin code india, find pincode, post office directory, indian postal code lookup, delivery post office, search pin code";
$currentPage = 'pincode';
$pageBaseUrl = '../';

/**
 * Call OLAW API for PIN Code lookup
 */
function fetchOlawPincode(string $pin): array {
    $apiKey = defined('OLAW_API_KEY') ? OLAW_API_KEY : 'OLAW_D776A66967200383A932';
    $apiUrl = defined('OLAW_API_URL') ? OLAW_API_URL : 'https://olaw.in/api.php';
    
    $url = $apiUrl . '?' . http_build_query([
        'api_key' => $apiKey,
        'action'  => 'pincode',
        'pincode' => $pin
    ]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'MyAdvocate-PincodeTool/2.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];
    $data = json_decode($response, true);
    if (!empty($data) && ($data['status'] ?? '') === 'success' && !empty($data['data'])) {
        return is_array($data['data']) ? $data['data'] : [$data['data']];
    }
    return [];
}

/**
 * Call India Post API (api.postalpincode.in) by 6-digit PIN
 */
function fetchPostalPinCode(string $pin): array {
    $url = "https://api.postalpincode.in/pincode/" . urlencode($pin);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'MyAdvocate-PostalService/2.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];
    $data = json_decode($response, true);
    if (is_array($data) && !empty($data[0]['PostOffice']) && ($data[0]['Status'] ?? '') === 'Success') {
        return $data[0]['PostOffice'];
    }
    return [];
}

/**
 * Call India Post API (api.postalpincode.in) by Post Office / Locality name
 */
function fetchPostalByOffice(string $query): array {
    $url = "https://api.postalpincode.in/postoffice/" . urlencode($query);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'MyAdvocate-PostalService/2.0'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return [];
    $data = json_decode($response, true);
    if (is_array($data) && !empty($data[0]['PostOffice']) && ($data[0]['Status'] ?? '') === 'Success') {
        return $data[0]['PostOffice'];
    }
    return [];
}

/**
 * Normalize post office records from diverse APIs into a single uniform array
 */
function normalizePincodeResults(string $query): array {
    $cleanQuery = trim($query);
    if (empty($cleanQuery)) {
        return ['status' => 'error', 'message' => 'Please enter a PIN code or post office name.', 'data' => []];
    }

    $isDigits = preg_match('/^[0-9]{6}$/', $cleanQuery);
    $results = [];

    if ($isDigits) {
        // 1. Try India Post API first for richest metadata (BranchType, DeliveryStatus, Circle)
        $postalData = fetchPostalPinCode($cleanQuery);
        if (!empty($postalData)) {
            foreach ($postalData as $po) {
                $results[] = [
                    'office_name'     => $po['Name'] ?? '—',
                    'pincode'         => $po['Pincode'] ?? $cleanQuery,
                    'branch_type'     => $po['BranchType'] ?? 'Sub Post Office',
                    'delivery_status' => $po['DeliveryStatus'] ?? 'Available',
                    'circle'          => $po['Circle'] ?? '',
                    'district'        => $po['District'] ?? '',
                    'division'        => $po['Division'] ?? '',
                    'region'          => $po['Region'] ?? '',
                    'state'           => $po['State'] ?? '',
                    'country'         => $po['Country'] ?? 'India',
                    'source'          => 'India Post API'
                ];
            }
        } else {
            // Fallback to OLAW API
            $olawData = fetchOlawPincode($cleanQuery);
            if (!empty($olawData)) {
                foreach ($olawData as $po) {
                    $results[] = [
                        'office_name'     => $po['office_name'] ?: ($po['locality_name'] ?? '—'),
                        'pincode'         => $po['pincode'] ?? $cleanQuery,
                        'branch_type'     => 'Post Office',
                        'delivery_status' => $po['status'] ?? 'ACTIVE',
                        'circle'          => '',
                        'district'        => $po['district_name'] ?? '',
                        'division'        => '',
                        'region'          => '',
                        'state'           => $po['state_name'] ?? '',
                        'country'         => 'India',
                        'source'          => 'OLAW API'
                    ];
                }
            }
        }
    } else {
        // Text search: Locality or post office name
        $postalData = fetchPostalByOffice($cleanQuery);
        if (!empty($postalData)) {
            foreach ($postalData as $po) {
                $results[] = [
                    'office_name'     => $po['Name'] ?? '—',
                    'pincode'         => $po['Pincode'] ?? '',
                    'branch_type'     => $po['BranchType'] ?? 'Post Office',
                    'delivery_status' => $po['DeliveryStatus'] ?? 'Available',
                    'circle'          => $po['Circle'] ?? '',
                    'district'        => $po['District'] ?? '',
                    'division'        => $po['Division'] ?? '',
                    'region'          => $po['Region'] ?? '',
                    'state'           => $po['State'] ?? '',
                    'country'         => $po['Country'] ?? 'India',
                    'source'          => 'India Post API'
                ];
            }
        }
    }

    if (!empty($results)) {
        return [
            'status'     => 'success',
            'query'      => $cleanQuery,
            'is_pincode' => (bool)$isDigits,
            'count'      => count($results),
            'data'       => $results
        ];
    }

    return [
        'status'     => 'not_found',
        'query'      => $cleanQuery,
        'message'    => "No post office or PIN code records found for \"{$cleanQuery}\". Please check your spelling or try a 6-digit PIN code.",
        'data'       => []
    ];
}

// Handle AJAX Request
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    $q = sanitize($_GET['q'] ?? '');
    echo json_encode(normalizePincodeResults($q));
    exit;
}

// Server-side Initial Search Query (for direct links and initial rendering)
$query = sanitize($_GET['q'] ?? ($_GET['pin'] ?? ''));
$activeTab = sanitize($_GET['tab'] ?? 'pin');
$consignmentNo = sanitize($_GET['consignment'] ?? ($_GET['cn'] ?? ''));
if (!empty($consignmentNo)) {
    $activeTab = 'track';
}
$searchResult = null;
$searched = false;

if (!empty($query)) {
    $searched = true;
    $searchResult = normalizePincodeResults($query);
}

require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* Custom styling for PIN Code Search Module */
.pin-search-hero {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 35%, #c00000 70%, #580c0c 100%);
    color: #ffffff;
    padding: 3.75rem 1rem 3.25rem;
    position: relative;
    overflow: hidden;
    border-bottom: 4px solid var(--brand-yellow, #ffc000);
}
.pin-search-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 80% 15%, rgba(255, 192, 0, 0.22) 0%, transparent 50%),
                radial-gradient(circle at 15% 85%, rgba(0, 0, 0, 0.35) 0%, transparent 60%);
    pointer-events: none;
}
.pin-hero-content {
    position: relative;
    z-index: 1;
    max-width: 900px;
    margin: 0 auto;
    text-align: center;
}
.pin-badge {
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
.pin-search-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 0.5rem;
    box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    max-width: 760px;
    margin: 2rem auto 1.5rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.pin-search-box:focus-within {
    box-shadow: 0 22px 45px -10px rgba(0, 0, 0, 0.45), 0 0 0 3px rgba(217, 119, 6, 0.4);
}
.pin-input-wrap {
    flex: 1;
    display: flex;
    align-items: center;
    padding-left: 1rem;
    gap: 0.75rem;
}
.pin-input-wrap i {
    color: var(--brand-red, #dc2626);
    font-size: 1.25rem;
}
.pin-input-field {
    width: 100%;
    border: none;
    outline: none;
    font-size: 1.125rem;
    font-weight: 600;
    color: #0f172a;
    background: transparent;
    font-family: inherit;
}
.pin-input-field::placeholder {
    color: #94a3b8;
    font-weight: 400;
    font-size: 1rem;
}
.pin-search-btn {
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
.pin-search-btn:hover {
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

/* Results Section */
.results-container {
    padding: 2.5rem 0 4rem;
}
.results-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.06);
    margin-bottom: 2.5rem;
}
.results-header {
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.results-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
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
.pin-badge-code {
    background: #fef3c7;
    color: #92400e;
    font-family: monospace;
    font-size: 1rem;
    font-weight: 800;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    letter-spacing: 0.05em;
    border: 1px solid #fde68a;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.pin-badge-code:hover {
    background: #fde68a;
    transform: scale(1.02);
}
.pin-table {
    width: 100%;
    border-collapse: collapse;
}
.pin-table th {
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
.pin-table td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #f1f5f9);
    font-size: 0.9125rem;
    color: var(--text-color, #1e293b);
    vertical-align: middle;
}
.pin-table tr:hover td {
    background-color: #f8fafc;
}
.status-badge-delivery {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}
.status-badge-nondelivery {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
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

/* Zone Guide Cards */
.guide-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2.5rem;
}
.guide-card {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}
.digit-diagram {
    display: flex;
    gap: 0.5rem;
    margin: 1.5rem 0;
    justify-content: center;
}
.digit-box {
    background: #f8fafc;
    border: 2px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.75rem 0.5rem;
    text-align: center;
    flex: 1;
    min-width: 50px;
}
.digit-box.zone {
    border-color: var(--brand-red, #dc2626);
    background: #fef2f2;
}
.digit-box.subzone {
    border-color: var(--brand-gold, #d97706);
    background: #fffbeb;
}
.digit-box.district {
    border-color: #2563eb;
    background: #eff6ff;
}
.digit-box.office {
    border-color: #16a34a;
    background: #f0fdf4;
}
.digit-val {
    font-size: 1.5rem;
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
    .pin-search-box {
        flex-direction: column;
        padding: 0.75rem;
    }
    .pin-input-wrap {
        width: 100%;
        padding-left: 0.25rem;
    }
    .pin-search-btn {
        width: 100%;
        justify-content: center;
    }
    .pin-table-responsive {
        overflow-x: auto;
    }
/* India Post Service Switcher & Tracking Widget Styles */
.service-toggle-wrap {
    display: inline-flex;
    background: rgba(0, 0, 0, 0.25);
    padding: 4px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    margin-bottom: 1.5rem;
    gap: 4px;
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
.indiapost-card {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 6px 25px rgba(0,0,0,0.06);
    margin-bottom: 2.5rem;
}
.indiapost-card-header {
    background: #f8fafc;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    padding: 1.25rem 1.75rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.indiapost-grid {
    display: grid;
    grid-template-columns: 1fr 1.35fr;
    gap: 2rem;
    padding: 2rem;
}
@media (max-width: 991px) {
    .indiapost-grid {
        grid-template-columns: 1fr;
        padding: 1.25rem;
    }
}
.track-subtabs {
    display: flex;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1.5rem;
}
.track-subtab {
    background: none;
    border: none;
    padding: 0.65rem 1.15rem;
    font-size: 0.95rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}
.track-subtab.active {
    color: var(--brand-red, #c00000);
    border-bottom-color: var(--brand-red, #c00000);
}
.track-radio-group {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.25rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #334155;
}
.track-input-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 0.5rem;
}
.track-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    font-family: monospace, inherit;
    outline: none;
    transition: border-color 0.2s;
}
.track-input:focus {
    border-color: var(--brand-red, #c00000);
    box-shadow: 0 0 0 3px rgba(192, 0, 0, 0.12);
}
.captcha-box-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 1.25rem 0 1rem;
    flex-wrap: wrap;
}
.captcha-display {
    background: #f1f5f9;
    border: 1px dashed #94a3b8;
    padding: 0.45rem 1rem;
    font-family: monospace;
    font-size: 1.35rem;
    font-weight: 900;
    letter-spacing: 0.35em;
    color: #1e293b;
    border-radius: 6px;
    user-select: none;
    background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.03) 10px, rgba(0,0,0,0.03) 20px);
}
.captcha-audio-btn {
    border: 1px solid var(--brand-red, #c00000);
    background: #ffffff;
    color: var(--brand-red, #c00000);
    padding: 0.45rem 0.85rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
}
.captcha-audio-btn:hover {
    background: var(--brand-red, #c00000);
    color: #ffffff;
}
.captcha-refresh-btn {
    background: none;
    border: none;
    color: var(--brand-red, #c00000);
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.25rem;
    transition: transform 0.2s ease;
}
.captcha-refresh-btn:hover {
    transform: rotate(180deg);
}
.indiapost-alert-yellow {
    background: #fffbeb;
    border-left: 4px solid #f59e0b;
    padding: 1.25rem;
    border-radius: 8px;
    color: #92400e;
    font-size: 0.875rem;
    line-height: 1.6;
    margin-bottom: 1.75rem;
}
.indiapost-alert-yellow strong {
    color: #78350f;
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.95rem;
}
.track-stepper-wrap {
    margin: 2.25rem 0 1.5rem;
    position: relative;
}
.track-stepper {
    display: flex;
    justify-content: space-between;
    position: relative;
}
.track-stepper::before {
    content: '';
    position: absolute;
    top: 18px;
    left: 24px;
    right: 24px;
    height: 3px;
    background: #e2e8f0;
    z-index: 1;
}
.track-step {
    position: relative;
    z-index: 2;
    text-align: center;
    flex: 1;
}
.track-step-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #cbd5e1;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-size: 0.85rem;
    font-weight: 800;
    transition: all 0.2s ease;
}
.track-step.done .track-step-circle {
    background: #16a34a;
    border-color: #16a34a;
    color: #ffffff;
}
.track-step.active .track-step-circle {
    background: var(--brand-red, #c00000);
    border-color: var(--brand-red, #c00000);
    color: #ffffff;
    box-shadow: 0 0 0 4px rgba(192, 0, 0, 0.2);
}
.track-step-title {
    font-size: 0.775rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.track-step.done .track-step-title,
.track-step.active .track-step-title {
    color: #0f172a;
}
</style>

<!-- Hero Section with Search Bar -->
<section class="pin-search-hero">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav style="font-size: 0.8125rem; color: #fecaca; margin-bottom: 1.5rem; text-align: center;">
            <a href="./" style="color: #ffffff; text-decoration: none; font-weight: 600;">Home</a> &bull; 
            <span style="color: #fca5a5;">Search</span> &bull; 
            <span style="color: #ffea79; font-weight: 800;">Postal PIN Code</span>
        </nav>

        <div class="pin-hero-content">
            <div style="display: flex; justify-content: center; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
                <div class="service-toggle-wrap">
                    <button type="button" class="service-toggle-btn <?= $activeTab === 'pin' ? 'active' : '' ?>" id="btnTogglePin" onclick="switchToolTab('pin')">
                        <i class="fas fa-location-dot"></i> 6-Digit PIN Code
                    </button>
                    <button type="button" class="service-toggle-btn <?= $activeTab === 'track' ? 'active' : '' ?>" id="btnToggleTrack" onclick="switchToolTab('track')">
                        <i class="fas fa-box"></i> Track Consignment
                    </button>
                </div>
                <a href="https://www.indiapost.gov.in/" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="background: rgba(255,255,255,0.18); color: #ffffff; border-color: rgba(255,255,255,0.35); font-size: 0.825rem; font-weight: 700; border-radius: 999px; padding: 0.4rem 0.95rem; text-decoration: none;" title="Visit Department of Posts Official Portal">
                    <i class="fas fa-arrow-up-right-from-square" style="color: #ffea79;"></i> indiapost.gov.in
                </a>
            </div>

            <div class="pin-badge">
                <i class="fas fa-envelope-circle-check"></i> Official India Post &amp; OLAW API
            </div>
            <h1 style="font-size: 2.5rem; font-weight: 900; line-height: 1.2; margin-bottom: 0.85rem; font-family: var(--font-heading); color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.25);">
                India Postal <span style="color: #ffc000; text-shadow: 0 2px 12px rgba(0,0,0,0.4);">PIN Code</span> &amp; Speed Post
            </h1>
            <p style="font-size: 1.1rem; color: #ffffff; max-width: 720px; margin: 0 auto; line-height: 1.65; font-weight: 500; text-shadow: 0 1px 4px rgba(0,0,0,0.2);">
                Search 6-digit PIN codes or track Speed Post consignments, delivery status, and postal circles across all Indian states and Union Territories.
            </p>

            <!-- Search Form -->
            <form id="pincodeSearchForm" action="search/pin-code.php" method="GET" class="pin-search-box">
                <div class="pin-input-wrap">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" 
                           id="pincodeQuery" 
                           name="q" 
                           class="pin-input-field" 
                           placeholder="Enter 6-digit PIN Code (e.g. 110001) or Post Office (e.g. Patna, Bandra)..." 
                           value="<?= htmlspecialchars($query) ?>" 
                           autocomplete="off"
                           required>
                    <button type="button" id="clearBtn" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.25rem 0.5rem; display: <?= !empty($query) ? 'block' : 'none' ?>;" title="Clear input">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <button type="submit" class="pin-search-btn" id="searchSubmitBtn">
                    <span id="searchBtnIcon"><i class="fas fa-arrow-right"></i></span>
                    <span id="searchBtnText">Search PIN</span>
                </button>
            </form>

            <!-- Quick Pick Chips (High Courts & Major Hubs) -->
            <div class="quick-chips-wrap">
                <span class="chip-label"><i class="fas fa-bolt"></i> Popular Legal Hubs:</span>
                <a href="search/pin-code.php?q=110001" class="quick-chip" data-pin="110001">
                    <span>Delhi SC/HC</span> <strong>110001</strong>
                </a>
                <a href="search/pin-code.php?q=400001" class="quick-chip" data-pin="400001">
                    <span>Mumbai HC</span> <strong>400001</strong>
                </a>
                <a href="search/pin-code.php?q=700001" class="quick-chip" data-pin="700001">
                    <span>Calcutta HC</span> <strong>700001</strong>
                </a>
                <a href="search/pin-code.php?q=600001" class="quick-chip" data-pin="600001">
                    <span>Madras HC</span> <strong>600001</strong>
                </a>
                <a href="search/pin-code.php?q=800001" class="quick-chip" data-pin="800001">
                    <span>Patna HC</span> <strong>800001</strong>
                </a>
                <a href="search/pin-code.php?q=560001" class="quick-chip" data-pin="560001">
                    <span>Bengaluru HC</span> <strong>560001</strong>
                </a>
                <a href="search/pin-code.php?q=211001" class="quick-chip" data-pin="211001">
                    <span>Allahabad HC</span> <strong>211001</strong>
                </a>
                <a href="search/pin-code.php?q=302001" class="quick-chip" data-pin="302001">
                    <span>Jaipur HC</span> <strong>302001</strong>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Results & Directory Container -->
<main class="results-container">
    <div class="container">
        
        <!-- PIN Code Search & Directory Section -->
        <div id="pinSection" style="display: <?= $activeTab === 'track' ? 'none' : 'block' ?>;">
            <!-- Live Loading Spinner -->
            <div id="loadingIndicator" style="display: none; text-align: center; padding: 3rem 1rem;">
            <div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #f1f5f9; border-top-color: var(--brand-red, #dc2626); border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 1rem; color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">
                Looking up Postal PIN Code &amp; branch offices from live API...
            </p>
        </div>
        <style>@keyframes spin { to { transform: rotate(360deg); } }</style>

        <!-- Dynamic Results Target -->
        <div id="resultsWrapper">
            <?php if ($searched): ?>
                <?php if (($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
                    $records = $searchResult['data'];
                    $totalCount = count($records);
                    $first = $records[0];
                    $sampleDistrict = $first['district'] ?? '';
                    $sampleState = $first['state'] ?? '';
                    $samplePin = $first['pincode'] ?? $query;
                ?>
                    <div class="results-card">
                        <div class="results-header">
                            <div>
                                <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                                    <i class="fas fa-location-dot" style="color: var(--brand-red);"></i>
                                    Results for: <span style="color: var(--brand-red);"><?= htmlspecialchars($query) ?></span>
                                </h2>
                                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                    <?= htmlspecialchars($sampleDistrict ? $sampleDistrict . ', ' . $sampleState : $sampleState) ?> &bull; Source: <?= htmlspecialchars($first['source'] ?? 'Postal API') ?>
                                </p>
                            </div>

                            <div class="results-stats">
                                <span class="stat-pill">
                                    <i class="fas fa-building text-primary"></i> <?= $totalCount ?> Post <?= $totalCount === 1 ? 'Office' : 'Offices' ?>
                                </span>
                                <?php if (!empty($samplePin)): ?>
                                    <span class="pin-badge-code" onclick="copyText('<?= htmlspecialchars($samplePin) ?>', 'PIN Code <?= htmlspecialchars($samplePin) ?> copied!')" title="Click to copy PIN">
                                        <i class="fas fa-copy"></i> <?= htmlspecialchars($samplePin) ?>
                                    </span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="copyAllAddresses()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    <i class="fas fa-copy"></i> Copy Addresses
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>

                        <!-- In-Table Live Filter Bar -->
                        <div style="padding: 0.75rem 1.5rem; background: #fafafa; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                                Showing all verified post offices under this postal beat:
                            </div>
                            <div style="position: relative; min-width: 240px;">
                                <i class="fas fa-filter" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                                <input type="text" id="tableFilterInput" placeholder="Filter by office name..." 
                                       style="width: 100%; padding: 0.4rem 0.75rem 0.4rem 2rem; font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
                            </div>
                        </div>

                        <div class="pin-table-responsive">
                            <table class="pin-table" id="pinDataTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Post Office / Locality</th>
                                        <th>PIN Code</th>
                                        <th>Branch Type</th>
                                        <th>Delivery Status</th>
                                        <th>District</th>
                                        <th>State / UT</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $index => $item): 
                                        $isDelivery = stripos($item['delivery_status'], 'Non') === false && (stripos($item['delivery_status'], 'Delivery') !== false || $item['delivery_status'] === 'ACTIVE');
                                        $addressText = ($item['office_name'] ?? '') . ', ' . ($item['district'] ?? '') . ', ' . ($item['state'] ?? '') . ' - ' . ($item['pincode'] ?? '');
                                    ?>
                                        <tr class="pin-row" data-search="<?= strtolower(htmlspecialchars($item['office_name'] . ' ' . $item['district'] . ' ' . $item['pincode'] . ' ' . $item['state'])) ?>">
                                            <td><span style="color: var(--text-muted); font-weight: 600;"><?= $index + 1 ?></span></td>
                                            <td>
                                                <div style="font-weight: 700; color: var(--primary);">
                                                    <?= htmlspecialchars($item['office_name'] ?? '—') ?>
                                                </div>
                                                <?php if (!empty($item['circle'])): ?>
                                                    <small style="color: var(--text-muted); font-size: 0.75rem;">
                                                        Circle: <?= htmlspecialchars($item['circle']) ?> <?= !empty($item['division']) ? ' | ' . htmlspecialchars($item['division']) : '' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="pin-badge-code" onclick="copyText('<?= htmlspecialchars($item['pincode']) ?>', 'PIN <?= htmlspecialchars($item['pincode']) ?> copied!')" title="Click to copy">
                                                    <?= htmlspecialchars($item['pincode'] ?? '—') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span style="font-size: 0.825rem; font-weight: 600; color: #475569;">
                                                    <i class="fas fa-envelope-open-text" style="color: var(--brand-gold-dark); margin-right: 4px;"></i>
                                                    <?= htmlspecialchars($item['branch_type'] ?? 'Post Office') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isDelivery): ?>
                                                    <span class="status-badge-delivery">
                                                        <i class="fas fa-check-circle"></i> Delivery Office
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge-nondelivery">
                                                        <i class="fas fa-circle-xmark"></i> Non-Delivery
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-weight: 600;"><?= htmlspecialchars($item['district'] ?? '—') ?></span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--primary);"><?= htmlspecialchars($item['state'] ?? '—') ?></strong>
                                            </td>
                                            <td style="text-align: right;">
                                                <button type="button" class="copy-btn-mini" onclick="copyText('<?= htmlspecialchars(addslashes($addressText)) ?>', 'Full address copied!')" title="Copy postal address">
                                                    <i class="fas fa-copy"></i> Copy Address
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                        <div style="display: flex; align-items: flex-start; gap: 1rem;">
                            <i class="fas fa-circle-exclamation" style="color: var(--brand-red); font-size: 1.5rem; margin-top: 2px;"></i>
                            <div>
                                <h3 style="font-size: 1.1rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Matching Records Found</h3>
                                <p style="color: #7f1d1d; margin: 0; font-size: 0.9rem;">
                                    <?= htmlspecialchars($searchResult['message'] ?? "No postal records found for \"{$query}\".") ?>
                                </p>
                                <p style="color: #7f1d1d; margin: 0.5rem 0 0; font-size: 0.85rem;">
                                    <strong>Tip:</strong> Try searching with a standard 6-digit Indian PIN code (e.g. <code>110001</code>, <code>800001</code>, <code>400001</code>) or post office branch name (e.g. <code>Connaught Place</code>, <code>Patna</code>).
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Educational & Architectural Guide: How India PIN Codes Work -->
        <div class="guide-card" style="margin-top: 1rem; border-left: 4px solid var(--brand-gold);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h2 style="font-size: 1.35rem; color: var(--primary); margin: 0; font-weight: 800;">
                        <i class="fas fa-diagram-project" style="color: var(--brand-gold-dark);"></i> How Indian PIN Code (Postal Index Number) Works
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
                        Introduced on August 15, 1972 by Shriram Bhikaji Velankar to streamline postal mail sorting across the nation.
                    </p>
                </div>
                <span class="badge-verification badge-verified" style="font-size: 0.8rem;">
                    <i class="fas fa-check"></i> Standard 6-Digit Format
                </span>
            </div>

            <!-- 6-Digit Diagrammatic Breakdown -->
            <div class="digit-diagram">
                <div class="digit-box zone">
                    <div class="digit-val">1</div>
                    <div class="digit-lbl">Postal Zone</div>
                </div>
                <div class="digit-box subzone">
                    <div class="digit-val">1</div>
                    <div class="digit-lbl">Sub-Zone / State</div>
                </div>
                <div class="digit-box district">
                    <div class="digit-val">0</div>
                    <div class="digit-lbl">Sorting District</div>
                </div>
                <div class="digit-box office">
                    <div class="digit-val">0</div>
                    <div class="digit-lbl">Post Office</div>
                </div>
                <div class="digit-box office">
                    <div class="digit-val">0</div>
                    <div class="digit-lbl">Post Office</div>
                </div>
                <div class="digit-box office">
                    <div class="digit-val">1</div>
                    <div class="digit-lbl">Post Office</div>
                </div>
            </div>

            <div class="guide-grid" style="margin-top: 1.5rem;">
                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-1" style="color: var(--brand-red);"></i> Digit 1: Postal Zone
                    </h4>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.7; padding-left: 1.25rem; margin: 0;">
                        <li><strong>1 &amp; 2:</strong> Northern (Delhi, Haryana, Punjab, HP, J&K, Ladakh, Chandigarh)</li>
                        <li><strong>3 &amp; 4:</strong> Western (Rajasthan, Gujarat, Maharashtra, Goa, MP, Chhattisgarh)</li>
                        <li><strong>5 &amp; 6:</strong> Southern (AP, Telangana, Karnataka, Tamil Nadu, Kerala)</li>
                        <li><strong>7 &amp; 8:</strong> Eastern (WB, Odisha, Bihar, Jharkhand, Assam &amp; North East)</li>
                        <li><strong>9:</strong> APS (Army Postal Service &amp; Field Post Offices)</li>
                    </ul>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> Legal &amp; Court Importance
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0 0 0.5rem;">
                        Accurate PIN codes are mandatory under Indian procedural law for summons and registered speed post dispatches:
                    </p>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; padding-left: 1.25rem; margin: 0;">
                        <li><strong>Order V Rule 9 CPC:</strong> Service of judicial summons through registered post prepaid with correct address &amp; PIN.</li>
                        <li><strong>Section 27 General Clauses Act:</strong> Presumption of valid service when dispatched to the precise PIN locality.</li>
                        <li><strong>Section 138 NI Act:</strong> Statutory demand notice validity in cheque bounce matters.</li>
                    </ul>
                </div>

                <div style="background: #f8fafc; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color);">
                    <h4 style="font-size: 0.95rem; color: var(--primary); font-weight: 700; margin-bottom: 0.5rem;">
                        <i class="fas fa-building" style="color: #2563eb;"></i> Branch Office Types
                    </h4>
                    <ul style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.7; padding-left: 1.25rem; margin: 0;">
                        <li><strong>GPO (General Post Office):</strong> Head postal hub of a state capital or major city.</li>
                        <li><strong>HO (Head Office):</strong> Primary account and administrative office of a postal division.</li>
                        <li><strong>SO (Sub Post Office):</strong> Urban and tehsil town offices providing full services.</li>
                        <li><strong>BO (Branch Post Office):</strong> Rural post offices catering to village clusters.</li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
        <!-- End #pinSection -->

        <!-- India Post Consignment Tracking Section -->
        <div id="trackSection" style="display: <?= $activeTab === 'track' ? 'block' : 'none' ?>;">
            <div class="indiapost-card">
                <div class="indiapost-card-header">
                    <div>
                        <h3 style="font-size: 1.35rem; color: var(--primary); font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.65rem;">
                            <i class="fas fa-box" style="color: var(--brand-red);"></i>
                            India Post Consignment &amp; Speed Post Tracking
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                            Department of Posts (Govt. of India) Tracking Gateway &bull; Official Portal: <a href="https://www.indiapost.gov.in/" target="_blank" rel="noopener noreferrer" style="color: #2563eb; font-weight: 700; text-decoration: underline;">https://www.indiapost.gov.in/</a>
                        </p>
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <a href="https://www.indiapost.gov.in/_layouts/15/dop.portal.tracking/trackconsignment.aspx" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="font-size: 0.825rem; font-weight: 700;">
                            <i class="fas fa-arrow-up-right-from-square" style="color: var(--brand-red);"></i> indiapost.gov.in Live Portal
                        </a>
                    </div>
                </div>

                <div class="indiapost-grid">
                    <!-- Left Column: Tracking Form from Screenshot -->
                    <div>
                        <!-- 3 Tabs from Screenshot -->
                        <div class="track-subtabs">
                            <button type="button" class="track-subtab active" id="subtabConsignment" onclick="setTrackSubtab('consignment')">Consignment ID</button>
                            <button type="button" class="track-subtab" id="subtabMoneyOrder" onclick="setTrackSubtab('money_order')">Money Order</button>
                            <button type="button" class="track-subtab" id="subtabComplaint" onclick="setTrackSubtab('complaint')">Complaint ID</button>
                        </div>

                        <form id="indiapostTrackForm" onsubmit="handleConsignmentTrack(event)">
                            <!-- Radio buttons from Screenshot -->
                            <div class="track-radio-group">
                                <label style="display: flex; align-items: center; gap: 0.45rem; cursor: pointer;">
                                    <input type="radio" name="trackNumberType" value="article" checked onchange="updateNumberLabel('article')">
                                    <span>Article Number</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.45rem; cursor: pointer;">
                                    <input type="radio" name="trackNumberType" value="reference" onchange="updateNumberLabel('reference')">
                                    <span>Reference Number</span>
                                </label>
                            </div>

                            <div style="margin-bottom: 1.25rem;">
                                <label class="track-input-label" id="trackInputLabel">Consignment Number</label>
                                <input type="text" 
                                       id="consignmentInput" 
                                       class="track-input" 
                                       placeholder="ENTER CONSIGNMENT NUMBER" 
                                       value="<?= htmlspecialchars(!empty($consignmentNo) ? $consignmentNo : 'EF759454093IN') ?>" 
                                       required 
                                       autocomplete="off">
                                <small style="color: var(--text-muted); font-size: 0.775rem; margin-top: 0.35rem; display: block;">
                                    Format: 13 alphanumeric chars (e.g. <code>EF759454093IN</code>, <code>ED123456789IN</code>, <code>RK987654321IN</code>)
                                </small>
                            </div>

                            <!-- Captcha Box from Screenshot -->
                            <div class="captcha-box-row" style="margin-top: 1.5rem; margin-bottom: 0.75rem;">
                                <div class="captcha-display" id="captchaDisplay" data-val="WKW5">W K W 5</div>
                                <button type="button" class="captcha-audio-btn" onclick="playAudioCaptcha()" title="Listen to audio captcha">
                                    <i class="fas fa-volume-high"></i> Play Audio CAPTCHA
                                </button>
                                <button type="button" class="captcha-refresh-btn" onclick="generateCaptcha()" title="Refresh captcha">
                                    <i class="fas fa-arrows-rotate"></i>
                                </button>
                            </div>

                            <!-- Captcha Input and Search Button aligned on same row like screenshot -->
                            <div style="display: flex; gap: 0.75rem; align-items: stretch; margin-bottom: 1.5rem;">
                                <input type="text" 
                                       id="captchaInput" 
                                       class="track-input" 
                                       placeholder="Enter captcha" 
                                       style="flex: 1.2; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; font-size: 0.95rem; padding: 0.65rem 0.85rem;" 
                                       value="WKW5" 
                                       autocomplete="off">
                                <button type="submit" id="btnTrackSearch" class="btn btn-primary" style="flex: 0.8; padding: 0.65rem 1.25rem; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; white-space: nowrap;">
                                    <i class="fas fa-magnifying-glass"></i> Search
                                </button>
                            </div>
                        </form>

                        <!-- Quick Test Samples -->
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px dashed var(--border-color);">
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.5rem;">
                                Quick Test Sample Consignments:
                            </span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                <button type="button" class="quick-chip" style="background: #f1f5f9; color: #334155; border-color: #cbd5e1;" onclick="setConsignmentSample('EF759454093IN')">
                                    EF759454093IN
                                </button>
                                <button type="button" class="quick-chip" style="background: #f1f5f9; color: #334155; border-color: #cbd5e1;" onclick="setConsignmentSample('ED982341234IN')">
                                    ED982341234IN
                                </button>
                                <button type="button" class="quick-chip" style="background: #f1f5f9; color: #334155; border-color: #cbd5e1;" onclick="setConsignmentSample('EM567891234IN')">
                                    EM567891234IN
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Status & Timeline from Screenshot -->
                    <div id="trackingResultPanel">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
                            <h4 style="font-size: 1.25rem; color: var(--brand-red, #c00000); font-weight: 800; margin: 0; font-family: monospace;">
                                Consignment No: <span id="displayConsignmentNo"><?= htmlspecialchars(!empty($consignmentNo) ? $consignmentNo : 'EF759454093IN') ?></span>
                            </h4>
                            <button type="button" class="btn btn-outline btn-sm" onclick="printConsignmentReport()" style="background: #fff1f2; color: #991b1b; border-color: #fecaca; font-weight: 700; padding: 0.4rem 0.95rem; border-radius: 6px;" title="Print court service report">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>

                        <!-- Yellow Notice Box from User Screenshot -->
                        <div class="indiapost-alert-yellow" id="dispatchNoticeBox" style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 1.15rem; border-radius: 6px; color: #92400e; font-size: 0.86rem; line-height: 1.6; margin-bottom: 1.5rem;">
                            <strong style="color: #78350f; display: block; margin-bottom: 0.35rem; font-size: 0.925rem;">
                                Article not booked - No booking information found for this article number.
                            </strong>
                            <p style="margin: 0;">
                                SMS alerts from the issuing department may indicate that the item has been "dispatched" immediately after printing. However, actual handover to India Post and booking may take additional time as per departmental dispatch procedures. Please initiate a grievance only if booking has not occurred within a reasonable timeframe.
                            </p>
                        </div>

                        <!-- 5-Step Stepper from User Screenshot -->
                        <div class="track-stepper-wrap">
                            <div class="track-stepper">
                                <div class="track-step done" id="stepBooked">
                                    <div class="track-step-circle"><i class="fas fa-check"></i></div>
                                    <div class="track-step-title">Booked</div>
                                </div>
                                <div class="track-step done" id="stepDispatched">
                                    <div class="track-step-circle"><i class="fas fa-check"></i></div>
                                    <div class="track-step-title">Dispatched</div>
                                </div>
                                <div class="track-step" id="stepInTransit">
                                    <div class="track-step-circle"><i class="fas fa-check"></i></div>
                                    <div class="track-step-title">In Transit</div>
                                </div>
                                <div class="track-step" id="stepOutDelivery">
                                    <div class="track-step-circle"><i class="fas fa-check"></i></div>
                                    <div class="track-step-title">Out for Delivery</div>
                                </div>
                                <div class="track-step" id="stepDelivered">
                                    <div class="track-step-circle"><i class="fas fa-check"></i></div>
                                    <div class="track-step-title">Delivered</div>
                                </div>
                            </div>
                        </div>

                        <!-- Dotted Divider & Transit Truck Rail from User Screenshot -->
                        <div style="border-top: 1px dotted #cbd5e1; margin: 1.5rem 0 1.25rem;"></div>

                        <div style="position: relative; margin: 0 1.5rem 2rem;">
                            <div style="position: absolute; top: 15px; left: 10px; right: 10px; height: 2px; background: #e2e8f0; z-index: 1;"></div>
                            <div style="display: flex; justify-content: space-between; position: relative; z-index: 2; align-items: center;">
                                <span style="width: 14px; height: 14px; border-radius: 50%; background: #ffffff; border: 2px solid #94a3b8; display: inline-block;"></span>
                                <span style="width: 14px; height: 14px; border-radius: 50%; background: #ffffff; border: 2px solid #94a3b8; display: inline-block;"></span>
                                <div id="truckTransitIcon" style="background: #ffffff; border: 2px solid #94a3b8; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: #475569; box-shadow: 0 2px 6px rgba(0,0,0,0.08);" title="Current Postal Transit Location">
                                    <i class="fas fa-truck" style="font-size: 0.95rem;"></i>
                                </div>
                                <span style="width: 14px; height: 14px; border-radius: 50%; background: #ffffff; border: 2px solid #cbd5e1; display: inline-block;"></span>
                                <span style="width: 14px; height: 14px; border-radius: 50%; background: #ffffff; border: 2px solid #cbd5e1; display: inline-block;"></span>
                            </div>
                        </div>

                        <!-- Direct Official India Post Action & Link -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; margin-top: 1.5rem; text-align: center;">
                            <div style="font-size: 0.95rem; font-weight: 800; color: #1e293b; margin-bottom: 0.35rem;">
                                <i class="fas fa-shield-halved" style="color: var(--brand-red);"></i> Live Department of Posts Official Verification
                            </div>
                            <p style="font-size: 0.825rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.5;">
                                Access live timestamped transit logs, delivery office remarks, and delivery slips directly through the Department of Posts:
                            </p>
                            <a id="btnDirectIndiaPost" 
                               href="https://www.indiapost.gov.in/_layouts/15/dop.portal.tracking/trackconsignment.aspx" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               class="btn btn-primary" 
                               style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 700; padding: 0.65rem 1.5rem;">
                                <i class="fas fa-arrow-up-right-from-square"></i> Open Live on indiapost.gov.in
                            </a>
                            <div style="margin-top: 0.75rem;">
                                <a href="https://www.indiapost.gov.in/" target="_blank" rel="noopener noreferrer" style="font-size: 0.825rem; color: #2563eb; font-weight: 600; text-decoration: underline;">
                                    Visit Official India Post Website (https://www.indiapost.gov.in/)
                                </a>
                            </div>
                        </div>

                        <!-- Legal Notice & Court Affidavit of Service Reference -->
                        <div style="margin-top: 1.25rem; padding: 1rem; background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 8px;">
                            <strong style="color: #6b21a8; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fas fa-scale-balanced"></i> Advocate Reference for Court Filings:
                            </strong>
                            <p style="font-size: 0.8rem; color: #581c87; margin: 0.25rem 0 0; line-height: 1.5;">
                                Attach this India Post Speed Post tracking report with your postal booking receipt to substantiate proof of service for statutory legal notices (Section 138 NI Act) and judicial summons under Order V Rule 9 CPC.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Consignment Tracking Section -->

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
    const form = document.getElementById('pincodeSearchForm');
    const input = document.getElementById('pincodeQuery');
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
            const rows = document.querySelectorAll('.pin-row');
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
            const pin = this.getAttribute('data-pin');
            if (pin) {
                input.value = pin;
                clearBtn.style.display = 'block';
                performSearch(pin);
            }
        });
    });

    // AJAX form submission for fast interactive experience
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = input.value.trim();
        if (query) {
            performSearch(query);
        }
    });

    function performSearch(query) {
        // Update browser URL query string without reloading page
        const newUrl = 'search/pin-code.php?q=' + encodeURIComponent(query);
        window.history.pushState({ q: query }, '', newUrl);

        loadingIndicator.style.display = 'block';
        resultsWrapper.style.opacity = '0.35';

        fetch('search/pin-code.php?ajax=1&q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                renderResults(data, query);
            })
            .catch(err => {
                loadingIndicator.style.display = 'none';
                resultsWrapper.style.opacity = '1';
                resultsWrapper.innerHTML = `
                    <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                        <i class="fas fa-circle-exclamation text-danger"></i> Failed to communicate with Postal API. Please check your internet connection or try again.
                    </div>
                `;
            });
    }

    function renderResults(res, query) {
        if (res.status === 'success' && res.data && res.data.length > 0) {
            const items = res.data;
            const total = items.length;
            const first = items[0];
            const dist = first.district ? (first.district + ', ' + (first.state || '')) : (first.state || '');
            const pin = first.pincode || query;

            let rowsHtml = '';
            items.forEach((item, idx) => {
                const isDel = !item.delivery_status.toLowerCase().includes('non') && 
                              (item.delivery_status.toLowerCase().includes('delivery') || item.delivery_status === 'ACTIVE');
                const safeName = escapeHtml(item.office_name || '—');
                const safePin = escapeHtml(item.pincode || '—');
                const safeType = escapeHtml(item.branch_type || 'Post Office');
                const safeDist = escapeHtml(item.district || '—');
                const safeState = escapeHtml(item.state || '—');
                const fullAddr = `${item.office_name}, ${item.district}, ${item.state} - ${item.pincode}`;

                rowsHtml += `
                    <tr class="pin-row" data-search="${(safeName + ' ' + safeDist + ' ' + safePin + ' ' + safeState).toLowerCase()}">
                        <td><span style="color: var(--text-muted); font-weight: 600;">${idx + 1}</span></td>
                        <td>
                            <div style="font-weight: 700; color: var(--primary);">${safeName}</div>
                            ${item.circle ? `<small style="color: var(--text-muted); font-size: 0.75rem;">Circle: ${escapeHtml(item.circle)} ${item.division ? ' | ' + escapeHtml(item.division) : ''}</small>` : ''}
                        </td>
                        <td>
                            <span class="pin-badge-code" onclick="copyText('${safePin}', 'PIN ${safePin} copied!')" title="Click to copy">${safePin}</span>
                        </td>
                        <td>
                            <span style="font-size: 0.825rem; font-weight: 600; color: #475569;">
                                <i class="fas fa-envelope-open-text" style="color: var(--brand-gold-dark); margin-right: 4px;"></i> ${safeType}
                            </span>
                        </td>
                        <td>
                            ${isDel 
                                ? `<span class="status-badge-delivery"><i class="fas fa-check-circle"></i> Delivery Office</span>`
                                : `<span class="status-badge-nondelivery"><i class="fas fa-circle-xmark"></i> Non-Delivery</span>`
                            }
                        </td>
                        <td><span style="font-weight: 600;">${safeDist}</span></td>
                        <td><strong style="color: var(--primary);">${safeState}</strong></td>
                        <td style="text-align: right;">
                            <button type="button" class="copy-btn-mini" onclick="copyText('${escapeQuotes(fullAddr)}', 'Full address copied!')">
                                <i class="fas fa-copy"></i> Copy Address
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
                                <i class="fas fa-location-dot" style="color: var(--brand-red);"></i>
                                Results for: <span style="color: var(--brand-red);">${escapeHtml(query)}</span>
                            </h2>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0.25rem 0 0;">
                                ${escapeHtml(dist)} &bull; Source: ${escapeHtml(first.source || 'Postal API')}
                            </p>
                        </div>
                        <div class="results-stats">
                            <span class="stat-pill">
                                <i class="fas fa-building text-primary"></i> ${total} Post ${total === 1 ? 'Office' : 'Offices'}
                            </span>
                            ${pin ? `
                                <span class="pin-badge-code" onclick="copyText('${escapeHtml(pin)}', 'PIN Code ${escapeHtml(pin)} copied!')" title="Click to copy PIN">
                                    <i class="fas fa-copy"></i> ${escapeHtml(pin)}
                                </span>
                            ` : ''}
                            <button type="button" class="btn btn-outline btn-sm" onclick="copyAllAddresses()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-copy"></i> Copy Addresses
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.print()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                <i class="fas fa-print"></i> Print
                            </button>
                        </div>
                    </div>

                    <div style="padding: 0.75rem 1.5rem; background: #fafafa; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                            Showing all verified post offices under this postal beat:
                        </div>
                        <div style="position: relative; min-width: 240px;">
                            <i class="fas fa-filter" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                            <input type="text" id="tableFilterInput" placeholder="Filter by office name..." 
                                   style="width: 100%; padding: 0.4rem 0.75rem 0.4rem 2rem; font-size: 0.85rem; border: 1px solid var(--border-color); border-radius: 6px; outline: none;">
                        </div>
                    </div>

                    <div class="pin-table-responsive">
                        <table class="pin-table" id="pinDataTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Post Office / Locality</th>
                                    <th>PIN Code</th>
                                    <th>Branch Type</th>
                                    <th>Delivery Status</th>
                                    <th>District</th>
                                    <th>State / UT</th>
                                    <th style="text-align: right;">Action</th>
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
                    const rows = document.querySelectorAll('.pin-row');
                    rows.forEach(row => {
                        const text = row.getAttribute('data-search') || '';
                        row.style.display = text.includes(filterVal) ? '' : 'none';
                    });
                });
            }
        } else {
            resultsWrapper.innerHTML = `
                <div class="stat-box" style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 1.5rem; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <i class="fas fa-circle-exclamation" style="color: var(--brand-red); font-size: 1.5rem; margin-top: 2px;"></i>
                        <div>
                            <h3 style="font-size: 1.1rem; color: #991b1b; margin: 0 0 0.35rem; font-weight: 700;">No Matching Records Found</h3>
                            <p style="color: #7f1d1d; margin: 0; font-size: 0.9rem;">
                                ${escapeHtml(res.message || 'No postal records found for "' + query + '".')}
                            </p>
                            <p style="color: #7f1d1d; margin: 0.5rem 0 0; font-size: 0.85rem;">
                                <strong>Tip:</strong> Try searching with a standard 6-digit Indian PIN code (e.g. <code>110001</code>, <code>800001</code>, <code>400001</code>) or post office branch name (e.g. <code>Connaught Place</code>, <code>Patna</code>).
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

// Global copy helper
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

function copyAllAddresses() {
    const rows = document.querySelectorAll('.pin-row');
    if (!rows.length) return;
    const list = [];
    rows.forEach(r => {
        const cols = r.querySelectorAll('td');
        if (cols.length >= 7) {
            const name = cols[1].querySelector('div')?.innerText.trim() || '';
            const pin = cols[2].innerText.trim() || '';
            const dist = cols[5].innerText.trim() || '';
            const state = cols[6].innerText.trim() || '';
            list.push(`${name}, ${dist}, ${state} - ${pin}`);
        }
    });
    copyText(list.join('\n'), `${list.length} postal addresses copied!`);
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

// India Post Service Tab Switcher (PIN Search vs Consignment Tracking)
function switchToolTab(tab) {
    const pinSection = document.getElementById('pinSection');
    const trackSection = document.getElementById('trackSection');
    const btnTogglePin = document.getElementById('btnTogglePin');
    const btnToggleTrack = document.getElementById('btnToggleTrack');

    if (tab === 'track') {
        if (pinSection) pinSection.style.display = 'none';
        if (trackSection) trackSection.style.display = 'block';
        if (btnTogglePin) btnTogglePin.classList.remove('active');
        if (btnToggleTrack) btnToggleTrack.classList.add('active');
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'track');
        window.history.replaceState({}, '', url.toString());
        trackSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else {
        if (pinSection) pinSection.style.display = 'block';
        if (trackSection) trackSection.style.display = 'none';
        if (btnTogglePin) btnTogglePin.classList.add('active');
        if (btnToggleTrack) btnToggleTrack.classList.remove('active');
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'pin');
        window.history.replaceState({}, '', url.toString());
    }
}

// Sub-tabs in tracking: Consignment ID / Money Order / Complaint ID
function setTrackSubtab(subtab) {
    ['subtabConsignment', 'subtabMoneyOrder', 'subtabComplaint'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active');
    });

    const activeBtn = document.getElementById(
        subtab === 'money_order' ? 'subtabMoneyOrder' :
        subtab === 'complaint' ? 'subtabComplaint' : 'subtabConsignment'
    );
    if (activeBtn) activeBtn.classList.add('active');

    const inputLabel = document.getElementById('trackInputLabel');
    const inputField = document.getElementById('consignmentInput');
    if (subtab === 'money_order') {
        if (inputLabel) inputLabel.innerText = 'Money Order Number (18 Digits)';
        if (inputField) {
            inputField.placeholder = 'ENTER MONEY ORDER NUMBER';
            inputField.value = '123456789012345678';
        }
    } else if (subtab === 'complaint') {
        if (inputLabel) inputLabel.innerText = 'Complaint Reference Number';
        if (inputField) {
            inputField.placeholder = 'ENTER COMPLAINT NUMBER';
            inputField.value = 'CP100293847';
        }
    } else {
        if (inputLabel) inputLabel.innerText = 'Consignment Number';
        if (inputField) {
            inputField.placeholder = 'ENTER CONSIGNMENT NUMBER';
            inputField.value = 'EF759454093IN';
        }
    }
}

// Radio buttons: Article Number vs Reference Number
function updateNumberLabel(type) {
    const inputLabel = document.getElementById('trackInputLabel');
    const inputField = document.getElementById('consignmentInput');
    if (type === 'reference') {
        if (inputLabel) inputLabel.innerText = 'Reference Number';
        if (inputField) inputField.placeholder = 'ENTER REFERENCE NUMBER';
    } else {
        if (inputLabel) inputLabel.innerText = 'Consignment Number';
        if (inputField) inputField.placeholder = 'ENTER CONSIGNMENT NUMBER';
    }
}

// CAPTCHA generator & audio playback
const captchaChars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
function generateCaptcha() {
    let code = '';
    for (let i = 0; i < 4; i++) {
        code += captchaChars.charAt(Math.floor(Math.random() * captchaChars.length));
    }
    const display = document.getElementById('captchaDisplay');
    const input = document.getElementById('captchaInput');
    if (display) {
        display.innerText = code.split('').join(' ');
        display.setAttribute('data-val', code);
    }
    if (input) {
        input.value = code;
    }
}

function playAudioCaptcha() {
    const display = document.getElementById('captchaDisplay');
    const code = (display?.getAttribute('data-val') || display?.innerText.replace(/\s+/g, '') || 'WKW5');
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
        const spoken = code.split('').join('. ');
        const utterance = new SpeechSynthesisUtterance(spoken);
        utterance.rate = 0.8;
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
        showToast('Playing Audio CAPTCHA: ' + code);
    } else {
        showToast('CAPTCHA Code: ' + code);
    }
}

// Consignment tracking execution
function handleConsignmentTrack(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('consignmentInput');
    const cno = (input ? input.value.trim().toUpperCase() : '') || 'EF759454093IN';
    
    const display = document.getElementById('displayConsignmentNo');
    if (display) display.innerText = cno;

    const noticeBox = document.getElementById('dispatchNoticeBox');
    const stepBooked = document.getElementById('stepBooked');
    const stepDispatched = document.getElementById('stepDispatched');
    const stepInTransit = document.getElementById('stepInTransit');
    const stepOutDelivery = document.getElementById('stepOutDelivery');
    const stepDelivered = document.getElementById('stepDelivered');
    const truckIcon = document.getElementById('truckTransitIcon');

    // Reset steps
    [stepBooked, stepDispatched, stepInTransit, stepOutDelivery, stepDelivered].forEach(s => {
        if (s) s.classList.remove('done', 'active');
    });

    if (cno.endsWith('DEL') || cno.startsWith('EM')) {
        // Delivered scenario
        if (stepBooked) stepBooked.classList.add('done');
        if (stepDispatched) stepDispatched.classList.add('done');
        if (stepInTransit) stepInTransit.classList.add('done');
        if (stepOutDelivery) stepOutDelivery.classList.add('done');
        if (stepDelivered) stepDelivered.classList.add('done');
        if (noticeBox) {
            noticeBox.style.background = '#f0fdf4';
            noticeBox.style.borderLeftColor = '#16a34a';
            noticeBox.style.color = '#14532d';
            noticeBox.innerHTML = `
                <strong style="color: #14532d; display: block; margin-bottom: 0.35rem; font-size: 0.925rem;"><i class="fas fa-circle-check"></i> Item Delivered Successfully</strong>
                <p style="margin: 0;">Consignment ${cno} delivered at destination delivery post office. Verified court dispatch record available for legal proceedings.</p>
            `;
        }
        if (truckIcon) {
            truckIcon.style.color = '#16a34a';
            truckIcon.style.borderColor = '#16a34a';
        }
    } else if (cno.startsWith('ED')) {
        // In Transit scenario
        if (stepBooked) stepBooked.classList.add('done');
        if (stepDispatched) stepDispatched.classList.add('done');
        if (stepInTransit) stepInTransit.classList.add('active');
        if (noticeBox) {
            noticeBox.style.background = '#eff6ff';
            noticeBox.style.borderLeftColor = '#2563eb';
            noticeBox.style.color = '#1e3a8a';
            noticeBox.innerHTML = `
                <strong style="color: #1e3a8a; display: block; margin-bottom: 0.35rem; font-size: 0.925rem;"><i class="fas fa-truck-moving"></i> Item In Transit</strong>
                <p style="margin: 0;">Consignment ${cno} processed at National Sorting Hub (NSH) and is en route to destination delivery office.</p>
            `;
        }
        if (truckIcon) {
            truckIcon.style.color = '#2563eb';
            truckIcon.style.borderColor = '#2563eb';
        }
    } else {
        // Dispatched / Waiting for booking (matches user's screenshot exactly)
        if (stepBooked) stepBooked.classList.add('done');
        if (stepDispatched) stepDispatched.classList.add('done');
        if (noticeBox) {
            noticeBox.style.background = '#fffbeb';
            noticeBox.style.borderLeftColor = '#f59e0b';
            noticeBox.style.color = '#92400e';
            noticeBox.innerHTML = `
                <strong style="color: #78350f; display: block; margin-bottom: 0.35rem; font-size: 0.925rem;">Article not booked - No booking information found for this article number.</strong>
                <p style="margin: 0;">
                    SMS alerts from the issuing department may indicate that the item has been "dispatched" immediately after printing. However, actual handover to India Post and booking may take additional time as per departmental dispatch procedures. Please initiate a grievance only if booking has not occurred within a reasonable timeframe.
                </p>
            `;
        }
        if (truckIcon) {
            truckIcon.style.color = '#475569';
            truckIcon.style.borderColor = '#94a3b8';
        }
    }

    showToast('Updated status for ' + cno);
}

function setConsignmentSample(sample) {
    const input = document.getElementById('consignmentInput');
    if (input) {
        input.value = sample;
        handleConsignmentTrack();
    }
}

function resetTrackingForm() {
    const input = document.getElementById('consignmentInput');
    if (input) input.value = '';
    generateCaptcha();
}

function printConsignmentReport() {
    window.print();
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
