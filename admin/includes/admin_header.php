<?php
// admin/includes/admin_header.php - Admin Header & Navigation
require_once dirname(__DIR__, 2) . '/config/app.php';

if (empty($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: login.php");
    exit;
}

$currentAdminPage = basename($_SERVER['PHP_SELF'], '.php');
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminRole = $_SESSION['admin_role'] ?? 'ADMIN';

// Quick counter for pending grievances
$pendingContactsCount = 0;
try {
    $db = getDB();
    $pendingContactsCount = (int)$db->query("SELECT COUNT(*) FROM contact WHERE status = 'PENDING' OR status = 'NEW' OR status IS NULL OR status = ''")->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | My Advocate Admin' : 'Admin Panel | My Advocate' ?></title>
    
    <!-- Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Favicon & Touch Icons -->
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/apple-touch-icon.png">

    <!-- Main Style CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= APP_VERSION ?>">

    <!-- Theme Initializer (Night / Light Mode) -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <style>
        :root {
            --admin-sidebar-bg: #ffffff;
            --admin-sidebar-border: #e2e8f0;
            --admin-topbar-bg: #ffffff;
            --admin-bg: #f8fafc;
        }
        body {
            background-color: var(--admin-bg);
            margin: 0;
            padding: 0;
            font-family: var(--font-body);
        }
        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        .admin-sidebar {
            background: var(--admin-sidebar-bg);
            border-right: 1px solid var(--admin-sidebar-border);
            color: #334155;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02);
        }
        .admin-sidebar-header {
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav-section-title {
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #64748b;
            text-transform: uppercase;
            padding: 0.75rem 1rem 0.25rem;
        }
        .admin-nav {
            list-style: none;
            padding: 0.5rem 0.75rem;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .admin-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            color: #475569;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: var(--radius-sm);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .admin-nav-link-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .admin-nav-link i {
            width: 18px;
            text-align: center;
            font-size: 1rem;
            color: #64748b;
        }
        .admin-nav-link:hover {
            background: var(--brand-red-light);
            color: var(--brand-red);
        }
        .admin-nav-link:hover i {
            color: var(--brand-red);
        }
        .admin-nav-link.active {
            background: var(--brand-red-light);
            color: var(--brand-red);
            border-left: 3px solid var(--brand-red);
            font-weight: 700;
        }
        .admin-nav-link.active i {
            color: var(--brand-red);
        }
        .admin-badge-count {
            background: var(--brand-red);
            color: #ffffff;
            font-size: 0.6875rem;
            font-weight: 800;
            padding: 0.15rem 0.45rem;
            border-radius: 9999px;
        }
        .admin-content {
            background: var(--admin-bg);
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .admin-topbar {
            background: var(--admin-topbar-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0.85rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 40;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .admin-main {
            padding: 2rem 1.75rem;
            flex: 1;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: #ffffff;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .admin-table th {
            background: #111827;
            color: #ffffff;
            text-align: left;
            padding: 0.85rem 1rem;
            font-weight: 700;
            font-family: var(--font-heading);
            letter-spacing: 0.02em;
            white-space: nowrap;
        }
        .admin-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
        }
        .admin-table tr:hover {
            background: #fffdf5;
        }
        .admin-search-bar {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .admin-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .admin-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .admin-card-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-admin {
            padding: 0.85rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .alert-admin-success {
            background: #ecfdf5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .alert-admin-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .admin-mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--primary);
            cursor: pointer;
            padding: 0.4rem;
        }
        .admin-sidebar-close {
            display: none;
            background: transparent;
            border: none;
            color: var(--primary);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.4rem;
        }

        @media(max-width: 992px) {
            .admin-layout {
                grid-template-columns: 1fr;
            }
            .admin-sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 270px;
                height: 100vh;
                z-index: 1002;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex !important;
            }
            .admin-sidebar.mobile-open {
                left: 0;
            }
            .admin-mobile-toggle, .admin-sidebar-close {
                display: inline-flex;
            }
            .admin-main {
                padding: 1.25rem 1rem;
            }
            .admin-topbar {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Admin Mobile Backdrop Overlay -->
<div class="mobile-backdrop" id="adminMobileBackdrop"></div>

<div class="admin-layout">
    <!-- Left Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div>
            <div class="admin-sidebar-header">
                <div>
                    <a href="index.php" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none;">
                        <img src="../assets/images/logo-circle.png" alt="My Advocate" style="height: 38px; width: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--brand-gold);">
                        <span style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                            MY <span style="color: var(--brand-red);">ADVOCATE</span>
                        </span>
                    </a>
                    <div style="font-size: 0.7rem; color: var(--brand-red); font-weight: 700; margin-top: 0.25rem; letter-spacing: 0.05em; text-transform: uppercase;">
                        <i class="fas fa-shield-halved"></i> Master Control
                    </div>
                </div>
                <button type="button" class="admin-sidebar-close" id="adminSidebarClose" aria-label="Close sidebar">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- Main Nav -->
            <div class="admin-nav-section-title">Overview</div>
            <ul class="admin-nav">
                <li>
                    <a href="index.php" class="admin-nav-link <?= $currentAdminPage === 'index' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-chart-pie"></i> Dashboard</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">Advocates Management</div>
            <ul class="admin-nav">
                <li>
                    <a href="advocates.php" class="admin-nav-link <?= in_array($currentAdminPage, ['advocates', 'advocate_edit']) ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-user-tie"></i> Manage Advocates</span>
                    </a>
                </li>
                <li>
                    <a href="advocate_add.php" class="admin-nav-link <?= $currentAdminPage === 'advocate_add' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-user-plus"></i> Add Advocate</span>
                    </a>
                </li>
                <li>
                    <a href="advocate_upload.php" class="admin-nav-link <?= $currentAdminPage === 'advocate_upload' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-file-csv"></i> Bulk CSV Upload</span>
                    </a>
                </li>
                <li>
                    <a href="advocate_reports.php" class="admin-nav-link <?= $currentAdminPage === 'advocate_reports' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-chart-line"></i> Advocate Reports</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">Members & Users</div>
            <ul class="admin-nav">
                <li>
                    <a href="members.php" class="admin-nav-link <?= $currentAdminPage === 'members' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-users"></i> Registered Members</span>
                    </a>
                </li>
                <li>
                    <a href="users.php" class="admin-nav-link <?= $currentAdminPage === 'users' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-users-gear"></i> Admin Users</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">Judicial & State Bodies</div>
            <ul class="admin-nav">
                <li>
                    <a href="bc.php" class="admin-nav-link <?= $currentAdminPage === 'bc' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-scale-balanced"></i> Bar Councils (BC)</span>
                    </a>
                </li>
                <li>
                    <a href="ba.php" class="admin-nav-link <?= $currentAdminPage === 'ba' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-building-columns"></i> Bar Associations (BA)</span>
                    </a>
                </li>
                <li>
                    <a href="hc.php" class="admin-nav-link <?= $currentAdminPage === 'hc' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-landmark"></i> High Courts (HC)</span>
                    </a>
                </li>
                <li>
                    <a href="state.php" class="admin-nav-link <?= $currentAdminPage === 'state' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-map-location-dot"></i> States & UTs</span>
                    </a>
                </li>
                <li>
                    <a href="district.php" class="admin-nav-link <?= $currentAdminPage === 'district' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-map-pin"></i> Districts Directory</span>
                    </a>
                </li>
                <li>
                    <a href="psc.php" class="admin-nav-link <?= $currentAdminPage === 'psc' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-briefcase"></i> Public Service Comm. (PSC)</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">Question Bank & Exams</div>
            <ul class="admin-nav">
                <li>
                    <a href="questions.php" class="admin-nav-link <?= $currentAdminPage === 'questions' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-clipboard-question"></i> Questions Bank (AIBE)</span>
                    </a>
                </li>
                <li>
                    <a href="subjects.php" class="admin-nav-link <?= $currentAdminPage === 'subjects' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-book-bookmark"></i> Law Subjects</span>
                    </a>
                </li>
                <li>
                    <a href="exams.php" class="admin-nav-link <?= $currentAdminPage === 'exams' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-graduation-cap"></i> Examinations</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">Legal & Academics</div>
            <ul class="admin-nav">
                <li>
                    <a href="universities.php" class="admin-nav-link <?= $currentAdminPage === 'universities' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-university"></i> Universities</span>
                    </a>
                </li>
                <li>
                    <a href="colleges.php" class="admin-nav-link <?= $currentAdminPage === 'colleges' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-school"></i> Law Colleges</span>
                    </a>
                </li>
                <li>
                    <a href="acts.php" class="admin-nav-link <?= $currentAdminPage === 'acts' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-book-journal-whills"></i> Bare Acts (BNS/BNSS)</span>
                    </a>
                </li>
                <li>
                    <a href="notices.php" class="admin-nav-link <?= $currentAdminPage === 'notices' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-bullhorn"></i> Legal Notices</span>
                    </a>
                </li>
            </ul>

            <div class="admin-nav-section-title">SEO & System</div>
            <ul class="admin-nav">
                <li>
                    <a href="api_tools.php" class="admin-nav-link <?= $currentAdminPage === 'api_tools' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-network-wired"></i> Legal & Bank APIs</span>
                    </a>
                </li>
                <li>
                    <a href="sitemap_manager.php" class="admin-nav-link <?= $currentAdminPage === 'sitemap_manager' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-sitemap"></i> XML Sitemaps</span>
                    </a>
                </li>
                <li>
                    <a href="contacts.php" class="admin-nav-link <?= $currentAdminPage === 'contacts' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-envelope-open-text"></i> Grievances & Queries</span>
                        <?php if ($pendingContactsCount > 0): ?>
                            <span class="admin-badge-count"><?= $pendingContactsCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="admin-nav-link <?= $currentAdminPage === 'settings' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-sliders"></i> Portal Settings</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="admin-nav-link <?= $currentAdminPage === 'profile' ? 'active' : '' ?>">
                        <span class="admin-nav-link-content"><i class="fas fa-user-gear"></i> My Profile</span>
                    </a>
                </li>
            </ul>
        </div>

        <div style="padding: 1.25rem; border-top: 1px solid var(--border-color); background: var(--bg-alt);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div style="width: 36px; height: 36px; border-radius: var(--radius-full); background: var(--brand-red); color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                    <?= strtoupper(substr($adminName, 0, 1)) ?>
                </div>
                <div style="overflow: hidden;">
                    <div style="font-size: 0.875rem; font-weight: 700; color: var(--primary); white-space: nowrap; text-overflow: ellipsis; overflow: hidden;"><?= sanitize($adminName) ?></div>
                    <small style="color: var(--brand-red); font-weight: 600;"><?= sanitize($adminRole) ?></small>
                </div>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="../" target="_blank" class="btn btn-outline btn-sm" style="flex: 1; padding: 0.35rem 0.5rem; font-size: 0.75rem; text-align: center;">
                    <i class="fas fa-globe"></i> Live Site
                </a>
                <a href="logout.php" class="btn btn-outline btn-sm" style="padding: 0.35rem 0.65rem; font-size: 0.75rem; color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" title="Sign Out">
                    <i class="fas fa-power-off"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-content">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <button type="button" class="admin-mobile-toggle" id="adminSidebarToggle" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 style="font-size: 1.15rem; margin: 0; color: var(--primary); font-family: var(--font-heading); font-weight: 800;"><?= isset($pageTitle) ? sanitize($pageTitle) : 'Admin Dashboard' ?></h2>
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <!-- Night Mode Toggle -->
                <button type="button" class="theme-toggle-btn" aria-label="Toggle Night Mode" title="Toggle Night / Light Mode">
                    <i class="fas fa-moon theme-icon-moon"></i>
                    <i class="fas fa-sun theme-icon-sun"></i>
                </button>
                <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">
                    <i class="fas fa-circle-check"></i> Online
                </span>
                <a href="advocate_add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <span style="display: none; @media(min-width: 576px){display:inline;}">New</span> Advocate</a>
            </div>
        </header>

        <!-- Page Body -->
        <main class="admin-main">
