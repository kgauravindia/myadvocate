<?php
// my-profile.php - Advocate Profile Management Route
require_once __DIR__ . '/config/app.php';

if (!empty($_SESSION['advocate_id'])) {
    header("Location: dashboard#profile-edit");
    exit;
} else {
    header("Location: login");
    exit;
}
