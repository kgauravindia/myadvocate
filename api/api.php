<?php
/**
 * MyAdvocate Unified API Gateway
 * Endpoint: https://myadv.in/api/api.php
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse JSON request body if present
$rawBody = file_get_contents('php://input');
if (!empty($rawBody)) {
    $parsedJson = json_decode($rawBody, true);
    if (is_array($parsedJson)) {
        foreach ($parsedJson as $k => $v) {
            if (!isset($_POST[$k])) $_POST[$k] = $v;
            if (!isset($_REQUEST[$k])) $_REQUEST[$k] = $v;
        }
    }
}

// Ensure default token is set if not passed by caller
if (empty($_REQUEST['token']) && empty($_POST['token']) && empty($_GET['token'])) {
    $_REQUEST['token'] = 'a9dcc62d6078b881d318aca0ead65ab5';
    $_GET['token'] = $_REQUEST['token'];
}

// Load master app router
require_once __DIR__ . '/api_app_v5.php';
