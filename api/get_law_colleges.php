<?php
// api/get_law_colleges.php - Returns Law Colleges for a specified University as JSON
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__) . '/config/app.php';

$univCode = sanitize($_GET['univ_code'] ?? $_POST['univ_code'] ?? '');
if (empty($univCode)) {
    echo json_encode([]);
    exit;
}

$colleges = getLawCollegesByUniversity($univCode);
echo json_encode($colleges);
