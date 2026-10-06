<?php
// advocate-search-result-by-mobile.php - Search Advocates by Mobile Number
require_once __DIR__ . '/config/app.php';

$mobile = trim($_GET['mobile'] ?? $_POST['mobile'] ?? '');

// If mobile query is provided, redirect to the unified advocate-search-result engine
if (!empty($mobile)) {
    header("Location: advocate-search-result?mobile=" . urlencode($mobile), true, 301);
    exit;
}

$pageTitle = "Search Advocates by Mobile Number - My Advocate";
$pageDescription = "Find verified advocate profiles across India by searching registered mobile contact numbers.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem; max-width: 800px;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.5rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="advocate-search-result" style="color: var(--primary); text-decoration: none;">Advocate Directory</a> &bull; 
        <span>Search by Mobile</span>
    </nav>

    <!-- Hero Search Card -->
    <div class="stat-box" style="border-top: 4px solid var(--brand-red); background: #ffffff; padding: 2.5rem; text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: #fee2e2; color: var(--brand-red); display: inline-flex; align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 1rem;">
            <i class="fas fa-phone-volume"></i>
        </div>

        <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
            Advocate Search by Mobile
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 520px; margin: 0 auto 2rem; line-height: 1.6;">
            Search verified advocate profiles registered on My Advocate directory by their official 10-digit mobile number.
        </p>

        <form action="advocate-search-result" method="GET" style="max-width: 500px; margin: 0 auto;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 220px; position: relative;">
                    <i class="fas fa-mobile-screen" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--brand-red);"></i>
                    <input type="tel" name="mobile" class="filter-input" placeholder="Enter 10-digit Mobile No..." required pattern="[0-9]{10}" maxlength="10" style="padding-left: 2.75rem; height: 48px; font-size: 1rem;">
                </div>
                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 1.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px dashed var(--border-color); font-size: 0.8125rem; color: var(--text-muted);">
            <i class="fas fa-shield-halved" style="color: var(--brand-gold-dark);"></i>
            <span>All directory search results adhere to Bar Council privacy and contact verification guidelines.</span>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
