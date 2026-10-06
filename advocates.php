<?php
// advocates.php - Alias to Advocate Directory Search
require_once __DIR__ . '/config/app.php';

$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: advocate-search-result" . $queryString, true, 301);
exit;
