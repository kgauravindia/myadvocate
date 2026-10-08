<?php
// config/app.php - Application Configuration & Global Constants

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'My Advocate');
define('APP_TAGLINE', 'Find Advocates • Explore Law • Access Legal Resources');
define('APP_DOMAIN', 'myadv.in');
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('APP_VERSION', '2.2.3');

// External API Integration (olaw.in)
define('OLAW_API_KEY', 'OLAW_D776A66967200383A932');
define('OLAW_API_URL', 'https://olaw.in/api.php');

// SMS Gateway Integration (from myadvindia)
define('SMS_SENDER_ID', 'EMYADV');
define('SMS_AUTH_KEY_MSG', 'b0e99bea1fa7d15e27e1c5fd8e3c868');
define('SMS_AUTH_KEY_SMS', '180367At8cchpCRSTV59ed9c10');
define('SMS_DLT_TE_ID', '1207173652433489449');

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');

require_once ROOT_PATH . '/config/db.php';
require_once ROOT_PATH . '/includes/functions.php';

