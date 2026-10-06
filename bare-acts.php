<?php
// bare-acts.php - Alias to Central Bare Acts Library
require_once __DIR__ . '/config/app.php';

$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: acts" . $queryString, true, 301);
exit;
