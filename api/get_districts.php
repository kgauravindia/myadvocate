<?php
// api/get_districts.php - Returns districts as JSON for dynamic cascading dropdowns
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config/app.php';

$stateCode = sanitize($_GET['state'] ?? '');
if (empty($stateCode)) {
    echo json_encode([]);
    exit;
}

$districts = getDistrictsByState($stateCode);
echo json_encode($districts);
