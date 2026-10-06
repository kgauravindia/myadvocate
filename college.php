<?php
// college.php - Legacy Route mapping directly to law-college.php / law-college-search.php
if (!empty($_GET['link']) || !empty($_GET['id'])) {
    require_once __DIR__ . '/law-college.php';
} else {
    require_once __DIR__ . '/law-college-search.php';
}
