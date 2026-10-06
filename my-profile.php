<?php
// my-profile.php - Legacy My Profile Route
require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['advocate_id'])) {
    header("Location: dashboard");
    exit;
} else {
    header("Location: login");
    exit;
}
