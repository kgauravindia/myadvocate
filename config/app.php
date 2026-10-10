<?php
// config/app.php - Application Configuration & Global Constants

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_path', '/');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 86400 * 30,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(86400 * 30, '/; samesite=Lax', '', $isHttps, true);
    }
    session_start();
}

define('APP_NAME', 'My Advocate');
define('APP_TAGLINE', 'Find Advocates • Explore Law • Access Legal Resources');
define('APP_DOMAIN', 'myadv.in');
define('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('APP_VERSION', '2.2.7');

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

