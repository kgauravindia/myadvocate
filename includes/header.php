<?php
// includes/header.php - Global Header & Navigation with 3 Concise Menus
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config/app.php';
}
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Fetch advocate details if logged in
$headerAdvocate = null;
$headerMember = null;
if (!empty($_SESSION['advocate_id'])) {
    if (isset($advocate) && is_array($advocate) && isset($advocate['id']) && (int)$advocate['id'] === (int)$_SESSION['advocate_id']) {
        $headerAdvocate = $advocate;
    } else {
        try {
            $hDb = getDB();
            $hStmt = $hDb->prepare("SELECT id, name, photo, e_no, e_year, mobile, email, plan_type FROM advocate WHERE id = ? LIMIT 1");
            $hStmt->execute([$_SESSION['advocate_id']]);
            $headerAdvocate = $hStmt->fetch();
        } catch (Exception $e) {
            $headerAdvocate = null;
        }
    }
} elseif (!empty($_SESSION['member_id'])) {
    try {
        $hDb = getDB();
        $hStmt = $hDb->prepare("SELECT id, name, mobile, email, state_code, district_code FROM member WHERE id = ? LIMIT 1");
        $hStmt->execute([$_SESSION['member_id']]);
        $headerMember = $hStmt->fetch();
    } catch (Exception $e) {
        $headerMember = null;
    }
}

if ($headerAdvocate) {
    $headerName = $headerAdvocate['name'] ?? $_SESSION['advocate_name'] ?? 'Advocate';
    $headerInitial = strtoupper(substr(trim($headerName), 0, 1) ?: 'A');
    $headerPhoto = getAdvocatePhotoUrl($headerAdvocate['photo'] ?? '');
    $headerEnr = !empty($headerAdvocate['e_no']) ? $headerAdvocate['e_no'] . (!empty($headerAdvocate['e_year']) ? '/' . $headerAdvocate['e_year'] : '') : 'Advocate Member';
    $headerEmail = $headerAdvocate['email'] ?? '';
} elseif ($headerMember) {
    $headerName = $headerMember['name'] ?? $_SESSION['member_name'] ?? 'Member';
    $headerInitial = strtoupper(substr(trim($headerName), 0, 1) ?: 'M');
    $headerPhoto = '';
    $headerEnr = 'Client Member';
    $headerEmail = $headerMember['email'] ?? '';
} else {
    $headerName = '';
    $headerInitial = '';
    $headerPhoto = '';
    $headerEnr = '';
    $headerEmail = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?></title>
    <meta name="description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Search India\'s digital advocate directory, Bare Acts, AIBE exam prep, Court info, Law Colleges, and Legal Tools on My Advocate.' ?>">
    <meta name="keywords" content="<?= isset($pageKeywords) ? sanitize($pageKeywords) : 'advocate search, bare acts download, high court case status, law colleges india, legal resources, all india bar examination, aibe prep, vakil search' ?>">
    <meta name="author" content="OfferPlant">
    <link rel="canonical" href="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4BTK0TMSTG"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag("js", new Date());
      gtag("config", "G-4BTK0TMSTG");
    </script>

    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1272970612043134" crossorigin="anonymous"></script>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?>">
    <meta property="og:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Search India\'s digital advocate directory, Bare Acts, AIBE exam prep, Court info, Law Colleges, and Legal Tools on My Advocate.' ?>">
    <meta property="og:image" content="<?= APP_URL ?>/assets/images/logo-dark.png">
    <meta property="og:site_name" content="My Advocate">

    <!-- Twitter Cards -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
    <meta property="twitter:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE ?>">
    <meta property="twitter:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Search India\'s digital advocate directory, Bare Acts, AIBE exam prep, Court info, Law Colleges, and Legal Tools on My Advocate.' ?>">
    <meta property="twitter:image" content="<?= APP_URL ?>/assets/images/logo-dark.png">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Favicon & Touch Icons -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">

    <!-- Design System CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    
    <!-- Theme Initializer (Night / Light Mode) -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>

<?php if (!empty($_SESSION['impersonated_by_admin'])): ?>
    <div style="background: linear-gradient(90deg, #991b1b 0%, #b91c1c 100%); color: #ffffff; padding: 0.45rem 1.25rem; font-size: 0.8125rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10000; box-shadow: 0 2px 8px rgba(0,0,0,0.25); flex-wrap: wrap; gap: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <i class="fas fa-user-secret" style="font-size: 1rem; color: #fde047;"></i>
            <span><strong>Admin Impersonation:</strong> Logged in as <strong><?= sanitize($_SESSION['advocate_name'] ?? $_SESSION['member_name'] ?? 'User') ?></strong> (<?= ucfirst($_SESSION['user_type'] ?? 'Account') ?>)</span>
        </div>
        <a href="admin/impersonate.php?action=exit" style="color: #ffffff; background: rgba(0,0,0,0.35); padding: 0.25rem 0.75rem; border-radius: 4px; text-decoration: none; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem; border: 1px solid rgba(255,255,255,0.3);">
            <i class="fas fa-right-from-bracket"></i> Exit to Admin Panel
        </a>
    </div>
<?php endif; ?>

<!-- Mobile Backdrop Overlay -->
<div class="mobile-backdrop" id="mobileBackdrop"></div>

<!-- Main Sticky Header -->
<header class="site-header">
    <div class="container">
        <div class="header-main">
            <div class="header-inner">
                <!-- Brand Identity (Circular Logo Emblem) -->
                <!-- Brand Identity (Ai Advocate Index Emblem) -->
                <a href="./" class="brand-logo" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none;">
                    <span class="ai-brand-badge">Ai</span>
                    <span style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: var(--primary); letter-spacing: -0.02em; display: flex; align-items: center; gap: 0.35rem;">
                        Advocate <span style="color: #2563eb;">Index</span>
                    </span>
                </a>

                <!-- Advocate Index Primary Navigation -->
                <nav class="site-nav">
                    <ul class="nav-menu" id="navMenu">
                        <!-- Mobile Drawer Header -->
                        <li class="nav-menu-header">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span class="ai-brand-badge" style="width: 32px; height: 32px; font-size: 0.85rem;">Ai</span>
                                <span style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                    Advocate <span style="color: #2563eb;">Index</span>
                                </span>
                            </div>
                            <button type="button" class="nav-close-btn" id="navCloseBtn" aria-label="Close menu">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </li>

                        <!-- 1. HOME TAB -->
                        <li class="nav-item">
                            <a href="./" class="nav-link <?= in_array($currentPage, ['index', '']) ? 'active' : '' ?>">
                                <span class="nav-link-left"><i class="fas fa-home" style="color: #2563eb;"></i> Home</span>
                                <?php if (in_array($currentPage, ['index', ''])): ?><span class="active-dot">•</span><?php endif; ?>
                            </a>
                        </li>

                        <!-- 2. FIND ADVOCATES TAB -->
                        <li class="nav-item">
                            <a href="advocate-search-result" class="nav-link <?= in_array($currentPage, ['advocate-search-result', 'advocate-search-result-by-year', 'advocate-search-result-by-mobile']) ? 'active' : '' ?>">
                                <span class="nav-link-left"><i class="fas fa-search" style="color: #2563eb;"></i> Find Advocates</span>
                            </a>
                        </li>

                        <!-- 3. LEGAL HUB (Dropdown) -->
                        <li class="nav-item">
                            <a href="acts" class="nav-link <?= in_array($currentPage, ['acts', 'act-details', 'calendars', 'notice', 'aibe', 'tools', 'college', 'courts', 'bar-associations', 'case-status-of-bihar', 'case-status-of-jharkhand', 'case-status-of-uttar-pradesh']) ? 'active' : '' ?>">
                                <span class="nav-link-left"><i class="fas fa-scale-balanced" style="color: #2563eb;"></i> Legal Hub</span> <i class="fas fa-chevron-down nav-caret"></i>
                            </a>
                            <div class="nav-dropdown dropdown-mega">
                                <div class="dropdown-mega-grid">
                                    <div class="mega-column">
                                        <div class="mega-column-header"><i class="fas fa-book-bookmark"></i> Bare Acts & Laws</div>
                                        <a href="acts" class="dropdown-link">
                                            <i class="fas fa-book-open"></i>
                                            <div>
                                                <div class="dropdown-link-title">Central & State Acts</div>
                                                <span class="dropdown-link-desc">Complete Bare Acts library</span>
                                            </div>
                                        </a>
                                        <a href="acts?q=Bharatiya" class="dropdown-link">
                                            <i class="fas fa-scale-balanced"></i>
                                            <div>
                                                <div class="dropdown-link-title">BNS, BNSS & BSA 2023</div>
                                                <span class="dropdown-link-desc">New Indian Criminal Codes</span>
                                            </div>
                                        </a>
                                        <a href="calendars" class="dropdown-link">
                                            <i class="fas fa-calendar-days"></i>
                                            <div>
                                                <div class="dropdown-link-title">Court Calendars</div>
                                                <span class="dropdown-link-desc">High Court holiday schedules</span>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mega-column">
                                        <div class="mega-column-header"><i class="fas fa-gavel"></i> Case Status Online</div>
                                        <a href="case-status-of-bihar" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">High Court of Bihar</div>
                                                <span class="dropdown-link-desc">Patna HC & District Courts</span>
                                            </div>
                                        </a>
                                        <a href="case-status-of-jharkhand" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">High Court of Jharkhand</div>
                                                <span class="dropdown-link-desc">Jharkhand HC & Civil Courts</span>
                                            </div>
                                        </a>
                                        <a href="case-status-of-uttar-pradesh" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">High Court of UP</div>
                                                <span class="dropdown-link-desc">Allahabad HC & Courts</span>
                                            </div>
                                        </a>
                                    </div>

                                    <div class="mega-column">
                                        <div class="mega-column-header"><i class="fas fa-screwdriver-wrench"></i> Tools & Institutions</div>
                                        <a href="tools" class="dropdown-link">
                                            <i class="fas fa-calculator"></i>
                                            <div>
                                                <div class="dropdown-link-title">Legal Calculators</div>
                                                <span class="dropdown-link-desc">Court fees & limitation</span>
                                            </div>
                                        </a>
                                        <a href="aibe" class="dropdown-link">
                                            <i class="fas fa-graduation-cap"></i>
                                            <div>
                                                <div class="dropdown-link-title">AIBE Exam Hub</div>
                                                <span class="dropdown-link-desc">Syllabus, weightage & guide</span>
                                            </div>
                                        </a>
                                        <a href="college" class="dropdown-link">
                                            <i class="fas fa-building-columns"></i>
                                            <div>
                                                <div class="dropdown-link-title">Law Colleges</div>
                                                <span class="dropdown-link-desc">BCI recognized institutions</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <!-- 4. ADVOCATE CORNER (Dropdown) -->
                        <li class="nav-item">
                            <a href="services" class="nav-link <?= in_array($currentPage, ['services', 'pricing', 'claim-profile', 'faq', 'video', 'about', 'public-service-commission', 'state-bar-council']) ? 'active' : '' ?>">
                                <span class="nav-link-left"><i class="fas fa-briefcase" style="color: #2563eb;"></i> Advocate Corner</span> <i class="fas fa-chevron-down nav-caret"></i>
                            </a>
                            <ul class="nav-dropdown dropdown-wide">
                                <li>
                                    <a href="advocate-search-result" class="dropdown-link">
                                        <i class="fas fa-calendar-check"></i>
                                        <div>
                                            <div class="dropdown-link-title">Search by Enrollment Year</div>
                                            <span class="dropdown-link-desc">Bar Council year-wise directory</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="advocate-search-result-by-mobile" class="dropdown-link">
                                        <i class="fas fa-mobile-screen"></i>
                                        <div>
                                            <div class="dropdown-link-title">Search by Mobile Number</div>
                                            <span class="dropdown-link-desc">Direct advocate contact lookup</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="claim-profile" class="dropdown-link">
                                        <i class="fas fa-id-badge text-warning"></i>
                                        <div>
                                            <div class="dropdown-link-title">Claim Advocate Profile</div>
                                            <span class="dropdown-link-desc">Verify & manage your public profile</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="pricing" class="dropdown-link">
                                        <i class="fas fa-shield-halved text-success"></i>
                                        <div>
                                            <div class="dropdown-link-title">Membership & Verification</div>
                                            <span class="dropdown-link-desc">Verified Badge & Premium practice</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="services" class="dropdown-link">
                                        <i class="fas fa-star text-warning"></i>
                                        <div>
                                            <div class="dropdown-link-title">Advocate Services</div>
                                            <span class="dropdown-link-desc">Digital profile enhancement</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <a href="state-bar-council" class="dropdown-link">
                                        <i class="fas fa-scale-unbalanced"></i>
                                        <div>
                                            <div class="dropdown-link-title">State Bar Councils</div>
                                            <span class="dropdown-link-desc">26 Bar Councils & BCI directory</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="faq" class="dropdown-link">
                                        <i class="fas fa-circle-question"></i>
                                        <div>
                                            <div class="dropdown-link-title">Helpdesk & FAQs</div>
                                            <span class="dropdown-link-desc">Frequently asked questions</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Mobile Drawer Footer Action Buttons -->
                        <li class="nav-menu-footer">
                            <?php if (empty($_SESSION['advocate_id']) && empty($_SESSION['member_id'])): ?>
                                <a href="login" class="mobile-drawer-login">
                                    <i class="fas fa-arrow-right-to-bracket"></i> Login
                                </a>
                                <a href="register" class="btn-join-free mobile-drawer-join">
                                    <i class="fas fa-user-plus"></i> Join Free
                                </a>
                            <?php else: ?>
                                <a href="logout.php" class="user-logout-btn" style="width: 100%;">
                                    <i class="fas fa-right-from-bracket"></i> Logout
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </nav>

                <!-- Desktop / Header Action Button (Login/Join Free vs User Profile Dropdown) -->
                <div class="header-actions">
                    <?php if (!empty($_SESSION['advocate_id'])): ?>
                        <div class="user-dropdown-wrapper" id="userDropdownWrapper">
                            <button type="button" class="user-profile-btn" id="userDropdownBtn" aria-expanded="false" aria-label="Advocate account menu">
                                <div class="user-avatar-mini">
                                    <?php if (!empty($headerPhoto)): ?>
                                        <img src="<?= sanitize($headerPhoto) ?>" alt="<?= sanitize($headerName) ?>">
                                    <?php else: ?>
                                        <?= $headerInitial ?>
                                    <?php endif; ?>
                                </div>
                                <div class="user-meta-compact">
                                    <span class="user-name-compact"><?= sanitize($headerName) ?></span>
                                    <span class="user-role-compact"><?= sanitize($headerEnr) ?></span>
                                </div>
                                <i class="fas fa-chevron-down user-caret"></i>
                            </button>

                            <div class="user-dropdown-menu" id="userDropdownMenu">
                                <div class="user-dropdown-header">
                                    <div class="user-avatar-medium">
                                        <?php if (!empty($headerPhoto)): ?>
                                            <img src="<?= sanitize($headerPhoto) ?>" alt="<?= sanitize($headerName) ?>">
                                        <?php else: ?>
                                            <?= $headerInitial ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-dropdown-info">
                                        <div class="user-dropdown-name"><?= sanitize($headerName) ?></div>
                                        <div class="user-dropdown-badge">
                                            <i class="fas fa-id-card"></i> <?= sanitize($headerEnr) ?>
                                        </div>
                                        <?php if (!empty($headerEmail)): ?>
                                            <div class="user-dropdown-email"><?= sanitize($headerEmail) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <ul class="user-dropdown-list">
                                    <li>
                                        <a href="dashboard" class="user-dropdown-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                                            <i class="fas fa-gauge"></i>
                                            <div>
                                                <div class="item-title">Advocate Dashboard</div>
                                                <span class="item-desc">Overview & profile management</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="profile" class="user-dropdown-item <?= $currentPage === 'profile' ? 'active' : '' ?>">
                                            <i class="fas fa-id-badge"></i>
                                            <div>
                                                <div class="item-title">My Public Profile</div>
                                                <span class="item-desc">View profile as seen by clients</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="dashboard" class="user-dropdown-item">
                                            <i class="fas fa-user-pen"></i>
                                            <div>
                                                <div class="item-title">Edit Details & Privacy</div>
                                                <span class="item-desc">Update practice areas & visibility</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="pricing" class="user-dropdown-item <?= $currentPage === 'pricing' ? 'active' : '' ?>">
                                            <i class="fas fa-tags text-warning"></i>
                                            <div>
                                                <div class="item-title">Membership & Pricing</div>
                                                <span class="item-desc">Plans, verification & chamber</span>
                                            </div>
                                        </a>
                                    </li>
                                </ul>

                                <div class="user-dropdown-footer">
                                    <a href="logout.php" class="user-logout-btn">
                                        <i class="fas fa-right-from-bracket"></i> Sign Out / Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($_SESSION['member_id'])): ?>
                        <div class="user-dropdown-wrapper" id="userDropdownWrapper">
                            <button type="button" class="user-profile-btn" id="userDropdownBtn" aria-expanded="false" aria-label="Member account menu">
                                <div class="user-avatar-mini" style="background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff;">
                                    <?= $headerInitial ?>
                                </div>
                                <div class="user-meta-compact">
                                    <span class="user-name-compact"><?= sanitize($headerName) ?></span>
                                    <span class="user-role-compact"><i class="fas fa-shield-alt text-primary"></i> <?= sanitize($headerEnr) ?></span>
                                </div>
                                <i class="fas fa-chevron-down user-caret"></i>
                            </button>

                            <div class="user-dropdown-menu" id="userDropdownMenu">
                                <div class="user-dropdown-header">
                                    <div class="user-avatar-medium" style="background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff;">
                                        <?= $headerInitial ?>
                                    </div>
                                    <div class="user-dropdown-info">
                                        <div class="user-dropdown-name"><?= sanitize($headerName) ?></div>
                                        <div class="user-dropdown-badge" style="background: rgba(59, 130, 246, 0.1); color: #2563eb;">
                                            <i class="fas fa-user-check"></i> Client Member
                                        </div>
                                        <?php if (!empty($headerEmail)): ?>
                                            <div class="user-dropdown-email"><?= sanitize($headerEmail) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <ul class="user-dropdown-list">
                                    <li>
                                        <a href="member-profile" class="user-dropdown-item <?= $currentPage === 'member-profile' ? 'active' : '' ?>">
                                            <i class="fas fa-user-circle text-primary"></i>
                                            <div>
                                                <div class="item-title">Member Profile</div>
                                                <span class="item-desc">My account details & settings</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="advocates" class="user-dropdown-item">
                                            <i class="fas fa-user-tie text-success"></i>
                                            <div>
                                                <div class="item-title">Find Advocates</div>
                                                <span class="item-desc">Search advocates across India</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="bare-acts" class="user-dropdown-item">
                                            <i class="fas fa-book-bookmark text-warning"></i>
                                            <div>
                                                <div class="item-title">Bare Acts Library</div>
                                                <span class="item-desc">Central & state legal acts</span>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="tools" class="user-dropdown-item">
                                            <i class="fas fa-calculator text-info"></i>
                                            <div>
                                                <div class="item-title">Legal Tools</div>
                                                <span class="item-desc">Limitation & interest calculators</span>
                                            </div>
                                        </a>
                                    </li>
                                </ul>

                                <div class="user-dropdown-footer">
                                    <a href="logout.php" class="user-logout-btn">
                                        <i class="fas fa-right-from-bracket"></i> Sign Out / Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-2">
                            <a href="login" class="nav-action-login">
                                <i class="fas fa-arrow-right-to-bracket"></i> Login
                            </a>
                            <a href="register" class="btn-join-free">
                                <i class="fas fa-user-plus"></i> Join Free
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Night / Light Mode Toggle Button -->
                    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle Night Mode" title="Toggle Night / Light Mode">
                        <i class="fas fa-moon theme-icon-moon"></i>
                        <i class="fas fa-sun theme-icon-sun"></i>
                    </button>

                    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>
<main>

