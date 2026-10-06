<?php
// services.php - Platform Legal Services & Resource Hub
require_once __DIR__ . '/config/app.php';

$pageTitle = "Legal Services & Directory Features - My Advocate";
$pageDescription = "Explore digital advocate searching, central Bare Acts library, High Court case status, AIBE exam hub, and court fee calculators on My Advocate.";

$services = [
    [
        'title' => 'Digital Advocate Directory',
        'desc' => 'Search over 1.6 Lakh verified Indian advocates by name, bar enrollment year, state bar council, district jurisdiction, and practice specializations.',
        'link' => 'advocate-search-result',
        'btn_text' => 'Search Advocates',
        'icon' => 'fa-user-tie',
        'color' => 'var(--brand-red)'
    ],
    [
        'title' => 'Central & State Bare Acts Library',
        'desc' => 'Explore the complete repository of Central Acts, Rules, Amendments, and the new 2023 Bharatiya Criminal Codes (BNS, BNSS, BSA) with section readers.',
        'link' => 'acts',
        'btn_text' => 'Browse Bare Acts',
        'icon' => 'fa-book-journal-whills',
        'color' => 'var(--brand-gold-text)'
    ],
    [
        'title' => 'Indian Courts Case Status & Causelists',
        'desc' => 'Unified direct gateways to inspect live case status, certified orders, and daily causelists for High Courts, District Courts, and Administrative Tribunals.',
        'link' => 'case-status-of-bihar',
        'btn_text' => 'Check Case Status',
        'icon' => 'fa-landmark',
        'color' => 'var(--primary)'
    ],
    [
        'title' => 'AIBE Preparation Hub',
        'desc' => 'Comprehensive All India Bar Examination (AIBE) syllabus, subject marks weightage distribution, qualifying rules, and exam guidelines for law graduates.',
        'link' => 'aibe',
        'btn_text' => 'AIBE Prep Hub',
        'icon' => 'fa-graduation-cap',
        'color' => 'var(--brand-red)'
    ],
    [
        'title' => 'State Bar Councils Directory',
        'desc' => 'Directory of all 26 State Bar Councils in India with official portals, BCI codes, and enrollment jurisdiction details.',
        'link' => 'state-bar-council',
        'btn_text' => 'View Bar Councils',
        'icon' => 'fa-scale-balanced',
        'color' => 'var(--brand-gold-text)'
    ],
    [
        'title' => 'Law Colleges of India (40,000+)',
        'desc' => 'Comprehensive database of BCI-approved Law Colleges, Central Universities, NLUs, and State Law Institutes across all Indian states.',
        'link' => 'college',
        'btn_text' => 'Search Colleges',
        'icon' => 'fa-building-columns',
        'color' => 'var(--primary)'
    ],
    [
        'title' => 'Court Fee & Limitation Calculators',
        'desc' => 'Automated legal calculation utilities to estimate ad-valorem court fees for civil suits and calculate last limitation filing dates.',
        'link' => 'tools',
        'btn_text' => 'Open Legal Tools',
        'icon' => 'fa-calculator',
        'color' => 'var(--brand-red)'
    ],
    [
        'title' => 'Court Calendars & Schedules',
        'desc' => 'Official High Court and Civil Court working days, judicial vacation schedules, and gazetted national holiday calendars from 2022 to 2026.',
        'link' => 'calendars',
        'btn_text' => 'Download Calendars',
        'icon' => 'fa-calendar-days',
        'color' => 'var(--brand-gold-text)'
    ],
    [
        'title' => 'Legal Notices & Gazettes',
        'desc' => 'Latest court circulars, bar council election notifications, judicial recruitment updates, and public legal gazette releases.',
        'link' => 'notice',
        'btn_text' => 'View Legal Notices',
        'icon' => 'fa-bullhorn',
        'color' => 'var(--primary)'
    ]
];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Platform</span> &bull; <span>Legal Services</span>
    </nav>

    <!-- Header Title -->
    <div style="text-align: center; max-width: 780px; margin: 0 auto 3rem;">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-cubes"></i> Digital Legal Ecosystem
        </span>
        <h1 style="font-size: 2.35rem; color: var(--primary); margin: 0.35rem 0 0.75rem; font-weight: 800;">
            Platform Services & Legal Resources
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6;">
            Empowering advocates, litigants, and law students with trusted directory search, Bare Acts, case status gateways, and statutory calculation utilities.
        </p>
    </div>

    <!-- Services Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
        <?php foreach ($services as $srv): ?>
            <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 3px solid <?= $srv['color'] ?>; padding: 2rem 1.75rem; transition: transform 0.2s ease;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
                <div>
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--bg-alt); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: <?= $srv['color'] ?>; margin-bottom: 1.25rem; border: 1px solid var(--border-color);">
                        <i class="fas <?= $srv['icon'] ?>"></i>
                    </div>

                    <h2 style="font-size: 1.3rem; color: var(--primary); margin-bottom: 0.5rem;">
                        <?= sanitize($srv['title']) ?>
                    </h2>

                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem;">
                        <?= sanitize($srv['desc']) ?>
                    </p>
                </div>

                <a href="<?= sanitize($srv['link']) ?>" class="btn btn-outline-primary btn-sm" style="width: 100%;">
                    <?= sanitize($srv['btn_text']) ?> <i class="fas fa-arrow-right" style="margin-left: 0.25rem;"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
