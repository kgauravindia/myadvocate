<?php
// config/app.php - Application Configuration & Global Constants

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'My Advocate');
define('APP_TAGLINE', 'Find Advocates • Explore Law • Access Legal Resources');
define('APP_DOMAIN', 'myadv.in');
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('APP_VERSION', '2.0.0');

// External API Integration (olaw.in)
define('OLAW_API_KEY', 'OLAW_D776A66967200383A932');
define('OLAW_API_URL', 'https://olaw.in/api.php');

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');

require_once ROOT_PATH . '/config/db.php';
require_once ROOT_PATH . '/includes/functions.php';
