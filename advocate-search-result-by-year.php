<?php
// advocate-search-result-by-year.php - Redirect to Unified Advocate Directory Search
require_once __DIR__ . '/config/app.php';

$params = $_GET;

// Normalize parameter names for the unified search engine
if (!empty($params['state_code']) && empty($params['state'])) {
    $params['state'] = $params['state_code'];
    unset($params['state_code']);
}
if (!empty($params['district_code']) && empty($params['district'])) {
    $params['district'] = $params['district_code'];
    unset($params['district_code']);
}
if (!empty($params['e_year']) && empty($params['year'])) {
    $params['year'] = $params['e_year'];
    unset($params['e_year']);
}

$queryString = http_build_query($params);
$targetUrl = 'advocate-search-result' . (!empty($queryString) ? '?' . $queryString : '');

header("Location: " . $targetUrl, true, 301);
exit;
