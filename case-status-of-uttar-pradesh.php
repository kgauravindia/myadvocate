<?php
// case-status-of-uttar-pradesh.php - Allahabad High Court & Lucknow Bench e-Courts Portal
require_once __DIR__ . '/config/app.php';

$pageTitle = "Case Status of Uttar Pradesh - Allahabad High Court & Lucknow Bench";
$pageDescription = "Check live case status, judgments, daily causelists, and orders for Allahabad High Court, Lucknow Bench, and 75 UP District Courts.";

$courtPortals = [
    [
        'title' => 'Allahabad High Court (Prayagraj & Lucknow)',
        'category' => 'High Court of Judicature at Allahabad',
        'desc' => 'Search live case status by Case Type & Number, CNR Number, Petitioner/Respondent Name, Advocate Roll No., and Caveat Search across Principal Seat Prayagraj and Lucknow Bench.',
        'url' => 'http://www.allahabadhighcourt.in/',
        'services' => ['Case Status Search', 'Daily Cause List', 'Advocate Roll Lookup', 'Live Court Display Board'],
        'icon' => 'fa-landmark'
    ],
    [
        'title' => 'Allahabad High Court e-Filing Portal',
        'category' => 'Electronic Case Filing',
        'desc' => 'Online e-Filing facility for advocates enrolled with Allahabad High Court Bar Association and Oudh Bar Association Lucknow.',
        'url' => 'https://efiling.ecourts.gov.in/',
        'services' => ['Online e-Filing', 'Caveat Search', 'Vakalatnama Upload', 'Court Fee e-Challan'],
        'icon' => 'fa-cloud-arrow-up'
    ],
    [
        'title' => 'e-Courts Services: 75 District Courts of Uttar Pradesh',
        'category' => '75 District & Sessions Courts of UP',
        'desc' => 'Access case records for Lucknow, Kanpur, Varanasi, Agra, Prayagraj, Meerut, Ghaziabad, Gorakhpur, and all 75 District Courts.',
        'url' => 'https://services.ecourts.gov.in/ecourtindia_v6/',
        'services' => ['CNR Number Lookup', 'Party Name Search', 'Advocate Bar Code Search', 'Daily Orders & Judgments'],
        'icon' => 'fa-gavel'
    ],
    [
        'title' => 'Uttar Pradesh State Consumer Disputes Redressal Commission',
        'category' => 'Consumer Forum & Appeals',
        'desc' => 'Search consumer case status, appeals, execution proceedings, and daily cause list for SCDRC Lucknow and District Consumer Commissions via ConfoNet.',
        'url' => 'http://confonet.nic.in/',
        'services' => ['ConfoNet Case Status', 'State Commission Cause List', 'Judgment Search'],
        'icon' => 'fa-scale-balanced'
    ],
    [
        'title' => 'Central Administrative Tribunal (CAT) Allahabad & Lucknow',
        'category' => 'Service & Employment Matters',
        'desc' => 'Original Applications (O.A.), contempt petitions, and service matter orders for Central Government employees in Uttar Pradesh.',
        'url' => 'https://cgat.gov.in/catlive/index.php',
        'services' => ['CAT Case Information', 'Daily Cause List', 'Order Downloads'],
        'icon' => 'fa-users-gear'
    ],
    [
        'title' => 'Board of Revenue Uttar Pradesh (Prayagraj & Lucknow)',
        'category' => 'Revenue & Land Disputes',
        'desc' => 'Revenue case status, mutation appeals, land record dispute orders, and revision petition status under UP Revenue Code.',
        'url' => 'http://bor.up.nic.in/',
        'services' => ['Revenue Court Case Status (RCCS)', 'Daily Cause List', 'Order Downloads'],
        'icon' => 'fa-map-location-dot'
    ]
];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Case Status</span> &bull; <span>Uttar Pradesh</span>
    </nav>

    <!-- State Switcher -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 4px;">
        <a href="case-status-of-bihar" class="btn btn-outline btn-sm">
            <i class="fas fa-location-dot"></i> Bihar Case Status
        </a>
        <a href="case-status-of-jharkhand" class="btn btn-outline btn-sm">
            <i class="fas fa-location-dot"></i> Jharkhand Case Status
        </a>
        <a href="case-status-of-uttar-pradesh" class="btn btn-primary btn-sm">
            <i class="fas fa-location-dot"></i> Uttar Pradesh Case Status
        </a>
    </div>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fdf8f6 100%); border-left: 5px solid var(--brand-red);">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-scale-balanced"></i> e-Courts & Case Information Services
        </span>
        <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
            Case Status of Uttar Pradesh
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 820px; margin-top: 0.35rem; line-height: 1.6;">
            Direct links and search gateways for the <strong>Hon'ble High Court of Judicature at Allahabad & Lucknow Bench</strong>, 75 District & Sessions Courts, Consumer Commissions, and Board of Revenue Uttar Pradesh.
        </p>
    </div>

    <!-- Portals Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
        <?php foreach ($courtPortals as $cp): ?>
            <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid var(--brand-red); padding: 1.75rem 1.5rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 44px; height: 44px; border-radius: var(--radius-sm); background: var(--brand-red-light); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                            <i class="fas <?= $cp['icon'] ?>"></i>
                        </div>
                        <div>
                            <span style="font-size: 0.75rem; font-weight: 700; color: var(--brand-gold-text); text-transform: uppercase; letter-spacing: 0.5px;">
                                <?= sanitize($cp['category']) ?>
                            </span>
                            <h2 style="font-size: 1.2rem; color: var(--primary); margin: 0.1rem 0 0;">
                                <?= sanitize($cp['title']) ?>
                            </h2>
                        </div>
                    </div>

                    <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem;">
                        <?= sanitize($cp['desc']) ?>
                    </p>

                    <div style="margin-bottom: 1.5rem;">
                        <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Key Services Available:</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                            <?php foreach ($cp['services'] as $s): ?>
                                <span style="font-size: 0.75rem; background: var(--bg-alt); padding: 0.25rem 0.6rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: var(--text-main); font-weight: 500;">
                                    <i class="fas fa-check text-success" style="font-size: 0.65rem;"></i> <?= sanitize($s) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <a href="<?= sanitize($cp['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Open Portal & Check Status
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
