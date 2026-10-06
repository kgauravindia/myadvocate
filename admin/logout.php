<?php
// admin/logout.php - Admin Logout
require_once dirname(__DIR__) . '/config/app.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
header("Location: login.php");
exit;
