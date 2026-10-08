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
                <a href="./" class="brand-logo" style="display: flex; align-items: center; gap: 0.65rem; text-decoration: none;">
                    <img src="assets/images/logo-circle.png" alt="<?= APP_NAME ?>" style="height: 44px; width: 44px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 8px rgba(0,0,0,0.12); border: 2px solid var(--brand-gold-light);">
                    <span style="font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: var(--primary); letter-spacing: -0.02em; display: flex; flex-direction: column; line-height: 1.1;">
                        <span>MY <span style="color: var(--brand-red);">ADVOCATE</span></span>
                        <span style="font-size: 0.65rem; font-weight: 700; color: var(--brand-gold-dark); letter-spacing: 0.08em; text-transform: uppercase;">Legal Portal &bull; India</span>
                    </span>
                </a>

                <!-- 3 Concise Primary Menus with Rich Dropdowns -->
                <nav class="site-nav">
                    <ul class="nav-menu" id="navMenu">
                        <!-- Mobile Drawer Header -->
                        <li class="nav-menu-header">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <img src="assets/images/logo-circle.png" alt="<?= APP_NAME ?>" style="height: 36px; width: 36px; border-radius: 50%; object-fit: cover;">
                                <span style="font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                    MY <span style="color: var(--brand-red);">ADVOCATE</span>
                                </span>
                            </div>
                            <button type="button" class="nav-close-btn" id="navCloseBtn" aria-label="Close menu">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </li>

                        <!-- 1. HOME TAB -->
                        <li class="nav-item">
                            <a href="./" class="nav-link <?= in_array($currentPage, ['index', '']) ? 'active' : '' ?>">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>

                        <!-- 2. SERVICES TAB (Dropdown) -->
                        <li class="nav-item">
                            <a href="services" class="nav-link <?= in_array($currentPage, ['services', 'pricing', 'faq', 'video', 'public-service-commission', 'state-bar-council']) ? 'active' : '' ?>">
                                <i class="fas fa-briefcase"></i> Services <i class="fas fa-chevron-down nav-caret"></i>
                            </a>
                            <ul class="nav-dropdown dropdown-wide">
                                <li>
                                    <a href="about" class="dropdown-link">
                                        <i class="fas fa-circle-info"></i>
                                        <div>
                                            <div class="dropdown-link-title">About Us</div>
                                            <span class="dropdown-link-desc">Our mission & digital legal platform</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="services" class="dropdown-link">
                                        <i class="fas fa-star text-warning"></i>
                                        <div>
                                            <div class="dropdown-link-title">Top Services</div>
                                            <span class="dropdown-link-desc">Advocate profile enhancement & visibility</span>
                                        </div>
                                    </a>
                                </li>
                                <?php if (!empty($_SESSION['advocate_id'])): ?>
                                <li>
                                    <a href="pricing" class="dropdown-link">
                                        <i class="fas fa-tags text-warning"></i>
                                        <div>
                                            <div class="dropdown-link-title">Pricing & Plans</div>
                                            <span class="dropdown-link-desc">Advocate memberships & verification</span>
                                        </div>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <a href="faq" class="dropdown-link">
                                        <i class="fas fa-circle-question"></i>
                                        <div>
                                            <div class="dropdown-link-title">FAQs</div>
                                            <span class="dropdown-link-desc">Frequently asked questions & help</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="video" class="dropdown-link">
                                        <i class="fas fa-circle-play"></i>
                                        <div>
                                            <div class="dropdown-link-title">Videos & Tutorials</div>
                                            <span class="dropdown-link-desc">Court procedures & legal webinars</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <a href="public-service-commission" class="dropdown-link">
                                        <i class="fas fa-landmark-flag"></i>
                                        <div>
                                            <div class="dropdown-link-title">Public Service Commissions</div>
                                            <span class="dropdown-link-desc">UPSC, BPSC, JPSC, UPPSC & 31 State PSCs</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="state-bar-council" class="dropdown-link">
                                        <i class="fas fa-scale-unbalanced"></i>
                                        <div>
                                            <div class="dropdown-link-title">State Bar Councils</div>
                                            <span class="dropdown-link-desc">26 State Bar Councils & BCI Directory</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 3. SEARCH MULTI-TAB (3-Column Mega Menu as in myadvindia) -->
                        <li class="nav-item">
                            <a href="advocate-search-result" class="nav-link <?= in_array($currentPage, ['advocate-search-result', 'advocate-search-result-by-year', 'advocate-search-result-by-mobile', 'case-status-of-bihar', 'case-status-of-jharkhand', 'case-status-of-uttar-pradesh', 'college', 'law-college', 'courts', 'bar-associations']) ? 'active' : '' ?>">
                                <i class="fas fa-magnifying-glass"></i> Search <i class="fas fa-chevron-down nav-caret"></i>
                            </a>
                            <div class="nav-dropdown dropdown-mega">
                                <div class="dropdown-mega-grid">
                                    <!-- Column 1: Advocate -->
                                    <div class="mega-column">
                                        <div class="mega-column-header">
                                            <i class="fas fa-user-tie"></i> Advocate
                                        </div>
                                        <a href="advocate-search-result" class="dropdown-link">
                                            <i class="fas fa-signature"></i>
                                            <div>
                                                <div class="dropdown-link-title">By Name & District</div>
                                                <span class="dropdown-link-desc">1.6 Lakh+ Directory</span>
                                            </div>
                                        </a>
                                        <a href="advocate-search-result" class="dropdown-link">
                                            <i class="fas fa-calendar-check"></i>
                                            <div>
                                                <div class="dropdown-link-title">By Enrollment Year</div>
                                                <span class="dropdown-link-desc">Bar Council Year Search</span>
                                            </div>
                                        </a>
                                        <a href="advocate-search-result-by-mobile" class="dropdown-link">
                                            <i class="fas fa-mobile-screen"></i>
                                            <div>
                                                <div class="dropdown-link-title">By Mobile Number</div>
                                                <span class="dropdown-link-desc">Direct Contact Lookup</span>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Column 2: Case Status -->
                                    <div class="mega-column">
                                        <div class="mega-column-header">
                                            <i class="fas fa-gavel"></i> Case Status
                                        </div>
                                        <a href="case-status-of-bihar" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">Of Bihar</div>
                                                <span class="dropdown-link-desc">Patna HC & District Courts</span>
                                            </div>
                                        </a>
                                        <a href="case-status-of-jharkhand" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">Of Jharkhand</div>
                                                <span class="dropdown-link-desc">Jharkhand HC & Civil Courts</span>
                                            </div>
                                        </a>
                                        <a href="case-status-of-uttar-pradesh" class="dropdown-link">
                                            <i class="fas fa-landmark"></i>
                                            <div>
                                                <div class="dropdown-link-title">Of Uttar Pradesh</div>
                                                <span class="dropdown-link-desc">Allahabad HC & Courts</span>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Column 3: University & Institutions -->
                                    <div class="mega-column">
                                        <div class="mega-column-header">
                                            <i class="fas fa-building-columns"></i> Institutions
                                        </div>
                                        <a href="college" class="dropdown-link">
                                            <i class="fas fa-graduation-cap"></i>
                                            <div>
                                                <div class="dropdown-link-title">Law Colleges</div>
                                                <span class="dropdown-link-desc">40,000+ Colleges & BCI</span>
                                            </div>
                                        </a>
                                        <a href="courts" class="dropdown-link">
                                            <i class="fas fa-scale-balanced"></i>
                                            <div>
                                                <div class="dropdown-link-title">Courts of India</div>
                                                <span class="dropdown-link-desc">Supreme & High Courts</span>
                                            </div>
                                        </a>
                                        <a href="bar-associations" class="dropdown-link">
                                            <i class="fas fa-users-rectangle"></i>
                                            <div>
                                                <div class="dropdown-link-title">Bar Associations</div>
                                                <span class="dropdown-link-desc">340+ Registered Bodies</span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <!-- 4. DOWNLOAD / LEGAL LIBRARY TAB (Dropdown) -->
                        <li class="nav-item">
                            <a href="acts" class="nav-link <?= in_array($currentPage, ['acts', 'act-details', 'calendars', 'notice', 'aibe', 'tools']) ? 'active' : '' ?>">
                                <i class="fas fa-download"></i> Downloads <i class="fas fa-chevron-down nav-caret"></i>
                            </a>
                            <ul class="nav-dropdown dropdown-wide">
                                <li>
                                    <a href="acts" class="dropdown-link">
                                        <i class="fas fa-book-bookmark"></i>
                                        <div>
                                            <div class="dropdown-link-title">Acts and Rules</div>
                                            <span class="dropdown-link-desc">Central & State Bare Acts</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="acts?q=Bharatiya" class="dropdown-link">
                                        <i class="fas fa-scale-balanced"></i>
                                        <div>
                                            <div class="dropdown-link-title">BNS, BNSS & BSA 2023</div>
                                            <span class="dropdown-link-desc">New Indian Criminal Law Codes</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="calendars" class="dropdown-link">
                                        <i class="fas fa-calendar-days"></i>
                                        <div>
                                            <div class="dropdown-link-title">Calendars (2022-2026)</div>
                                            <span class="dropdown-link-desc">High Court holiday schedules & PDFs</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="notice" class="dropdown-link">
                                        <i class="fas fa-bullhorn"></i>
                                        <div>
                                            <div class="dropdown-link-title">Notice & Circulars</div>
                                            <span class="dropdown-link-desc">Court orders, exam dates & gazettes</span>
                                        </div>
                                    </a>
                                </li>
                                <li class="dropdown-divider"></li>
                                <li>
                                    <a href="aibe" class="dropdown-link">
                                        <i class="fas fa-graduation-cap"></i>
                                        <div>
                                            <div class="dropdown-link-title">AIBE Preparation Hub</div>
                                            <span class="dropdown-link-desc">Exam syllabus, weightage & guide</span>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="tools" class="dropdown-link">
                                        <i class="fas fa-calculator"></i>
                                        <div>
                                            <div class="dropdown-link-title">Legal Tools & Calculators</div>
                                            <span class="dropdown-link-desc">Court fees & limitation calculations</span>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>

                <!-- Desktop / Header Action Button (Login/Register vs User Profile Dropdown) -->
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
                            <a href="login" class="btn btn-primary btn-sm">
                                <i class="fas fa-right-to-bracket"></i> Login
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

