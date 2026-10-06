<?php
// courts.php - Court Directory (Supreme Court, High Courts, District & Subordinate Courts)
require_once __DIR__ . '/config/app.php';

$pageTitle = "Courts in India - Supreme Court, High Courts & District Judiciary Directory";
$pageDescription = "Browse directory of Indian Courts: Supreme Court of India, 25 High Courts, District Courts, Family Courts, and Special Tribunals.";

$db = getDB();
$states = getStates();

$highCourts = [];
try {
    $stmt = $db->query("SELECT * FROM hc WHERE status = 'ACTIVE' ORDER BY name ASC LIMIT 30");
    $highCourts = $stmt->fetchAll();
} catch (Exception $e) {}

// Fallback high courts if table is empty
if (empty($highCourts)) {
    $highCourts = [
        ['name' => 'Patna High Court', 'state' => 'Bihar', 'url' => 'http://patnahighcourt.gov.in'],
        ['name' => 'Allahabad High Court', 'state' => 'Uttar Pradesh', 'url' => 'http://allahabadhighcourt.in'],
        ['name' => 'Delhi High Court', 'state' => 'Delhi', 'url' => 'http://delhihighcourt.nic.in'],
        ['name' => 'Bombay High Court', 'state' => 'Maharashtra', 'url' => 'https://bombayhighcourt.nic.in'],
        ['name' => 'Calcutta High Court', 'state' => 'West Bengal', 'url' => 'https://calcuttahighcourt.gov.in'],
        ['name' => 'Madras High Court', 'state' => 'Tamil Nadu', 'url' => 'https://hcmadras.tn.gov.in'],
        ['name' => 'Punjab & Haryana High Court', 'state' => 'Punjab / Haryana', 'url' => 'https://highcourtchd.gov.in'],
        ['name' => 'Karnataka High Court', 'state' => 'Karnataka', 'url' => 'https://karnatakahi' . 'ghcourt.kar.nic.in']
    ];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Directory</span> &bull; <span>Courts in India</span>
    </nav>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
            <i class="fas fa-landmark" style="color: var(--brand-red);"></i> Indian Judiciary & Courts Directory
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">
            Direct portals to Case Status, Daily Cause Lists, e-Filing, and practicing advocates across High Courts and District Establishments.
        </p>
    </div>

    <!-- Supreme Court Apex Banner -->
    <div class="stat-box" style="border-left: 6px solid #b45309; margin-bottom: 2rem; background: #fffbeb;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge-verification badge-verified" style="background:#fef3c7; color:#92400e; border-color:#fcd34d;">Apex Court</span>
                <h2 style="font-size: 1.6rem; color: #78350f; margin: 0.35rem 0;">Supreme Court of India (SCI)</h2>
                <p style="color: #92400e; font-size: 0.875rem;">Tilak Marg, New Delhi &bull; Constitutional Bench, Special Leave Petitions, Appellate Jurisdiction</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="https://main.sci.gov.in" target="_blank" class="btn btn-gold btn-sm"><i class="fas fa-globe"></i> Official Portal</a>
                <a href="advocate-search-result?court=SC" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-tie"></i> Supreme Court Advocates</a>
            </div>
        </div>
    </div>

    <!-- High Courts Section -->
    <div class="section-header" style="margin-bottom: 1.5rem;">
        <div>
            <h2 class="section-title" style="font-size: 1.6rem;"><i class="fas fa-building-columns text-primary"></i> High Courts of India</h2>
            <p class="section-subtitle">25 High Courts serving States and Union Territories</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 3rem;">
        <?php foreach ($highCourts as $hc): ?>
            <div class="act-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <span class="badge-verification badge-registered" style="margin-bottom: 0.5rem;">High Court</span>
                    <h3 style="font-size: 1.15rem; margin-bottom: 0.35rem; color: var(--primary);"><?= sanitize($hc['name']) ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.8125rem; margin-bottom: 1rem;">
                        <i class="fas fa-location-dot" style="color: var(--brand-accent);"></i> <?= sanitize($hc['state'] ?? 'State Jurisdiction') ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <?php if (!empty($hc['url'])): ?>
                        <a href="<?= sanitize($hc['url']) ?>" target="_blank" class="btn btn-outline btn-sm" style="flex:1;"><i class="fas fa-globe"></i> Website</a>
                    <?php endif; ?>
                    <a href="advocate-search-result?court=HC" class="btn btn-outline-primary btn-sm"><i class="fas fa-users"></i> Advocates</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- District Judiciary Navigation by State -->
    <div class="stat-box">
        <h3 style="font-size: 1.3rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-sitemap" style="color: var(--brand-red);"></i> Find Advocates in District & Subordinate Courts by State</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
            <?php foreach ($states as $sCode => $sName): ?>
                <a href="advocate-search-result?state=<?= sanitize($sCode) ?>" class="btn btn-outline btn-sm" style="justify-content: space-between;">
                    <span><?= sanitize($sName) ?></span>
                    <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-light);"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
