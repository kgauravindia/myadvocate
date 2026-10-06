<?php
// admin/impersonate.php - Admin Impersonation & Masquerade Controller
require_once dirname(__DIR__) . '/config/app.php';

// Verify that the administrator is logged in
if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$action = sanitize($_GET['action'] ?? 'login');
$type = sanitize($_GET['type'] ?? '');
$id = sanitize($_GET['id'] ?? '');

$db = getDB();

// 1. Exit Impersonation Mode
if ($action === 'exit') {
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['impersonated_by_admin'] ?? null;
    $adminName = $_SESSION['admin_name'] ?? 'Administrator';
    $adminRole = $_SESSION['admin_role'] ?? 'ADMIN';

    // Clear user session keys
    unset($_SESSION['advocate_id']);
    unset($_SESSION['advocate_name']);
    unset($_SESSION['member_id']);
    unset($_SESSION['member_name']);
    unset($_SESSION['user_type']);
    unset($_SESSION['impersonated_by_admin']);

    // Restore admin session
    if ($adminId) {
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_name'] = $adminName;
        $_SESSION['admin_role'] = $adminRole;
    }

    header("Location: index.php");
    exit;
}

// 2. Impersonate as Advocate
if ($type === 'advocate' && !empty($id) && is_numeric($id)) {
    try {
        $stmt = $db->prepare("SELECT id, name, mobile, email, status, plan_type FROM advocate WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $adv = $stmt->fetch();

        if ($adv) {
            // Save admin session state
            $adminId = $_SESSION['admin_id'];
            $adminName = $_SESSION['admin_name'];
            $adminRole = $_SESSION['admin_role'];

            // Clear any previous member login
            unset($_SESSION['member_id']);
            unset($_SESSION['member_name']);

            // Set advocate credentials
            $_SESSION['advocate_id'] = (int)$adv['id'];
            $_SESSION['advocate_name'] = trim($adv['name'] ?: 'Advocate');
            $_SESSION['user_type'] = 'advocate';
            $_SESSION['impersonated_by_admin'] = $adminId;

            // Retain admin credentials in session so admin won't be logged out
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['admin_name'] = $adminName;
            $_SESSION['admin_role'] = $adminRole;

            header("Location: ../dashboard.php");
            exit;
        }
    } catch (Exception $e) {}
}

// 3. Impersonate as Member
if ($type === 'member' && !empty($id) && is_numeric($id)) {
    try {
        $stmt = $db->prepare("SELECT id, name, mobile, email, status FROM member WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $mem = $stmt->fetch();

        if ($mem) {
            // Save admin session state
            $adminId = $_SESSION['admin_id'];
            $adminName = $_SESSION['admin_name'];
            $adminRole = $_SESSION['admin_role'];

            // Clear any previous advocate login
            unset($_SESSION['advocate_id']);
            unset($_SESSION['advocate_name']);

            // Set member credentials
            $_SESSION['member_id'] = (int)$mem['id'];
            $_SESSION['member_name'] = trim($mem['name'] ?: 'Member');
            $_SESSION['user_type'] = 'member';
            $_SESSION['impersonated_by_admin'] = $adminId;

            // Retain admin credentials
            $_SESSION['admin_id'] = $adminId;
            $_SESSION['admin_name'] = $adminName;
            $_SESSION['admin_role'] = $adminRole;

            header("Location: ../member-profile.php");
            exit;
        }
    } catch (Exception $e) {}
}

// Fallback if target not found
header("Location: index.php");
exit;
