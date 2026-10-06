<?php
// config/db.example.php - Database Configuration Template
// Copy this file to config/db.php and fill in your database credentials.

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_FALLBACK', 'your_database_fallback');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                try {
                    $dsnFallback = "mysql:host=" . DB_HOST . ";dbname=" . DB_FALLBACK . ";charset=" . DB_CHARSET;
                    self::$instance = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
                } catch (PDOException $e2) {
                    try {
                        $dsnRoot = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                        self::$instance = new PDO($dsnRoot, 'root', '', $options);
                    } catch (PDOException $e3) {
                        die("Database Connection Error: " . htmlspecialchars($e3->getMessage()));
                    }
                }
            }
        }
        return self::$instance;
    }
}

function getDB(): PDO {
    return Database::getConnection();
}
