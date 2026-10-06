<?php
// video.php - Legal Tutorials, AIBE Lectures & Video Learning Hub
require_once __DIR__ . '/config/app.php';

$pageTitle = "Legal Video Lectures & Tutorials - My Advocate";
$pageDescription = "Watch curated legal video lectures on AIBE examination prep, new criminal law codes (BNS, BNSS, BSA), civil procedure, and advocate practice tips.";

$videos = [
    [
        'title' => 'Bharatiya Nyaya Sanhita (BNS 2023) - Key Changes Explained',
        'category' => 'Criminal Law Codes',
        'duration' => '45 Mins',
        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'desc' => 'Detailed walkthrough of key amendments, section mapping from IPC to BNS, and procedural highlights.',
        'icon' => 'fa-scale-balanced'
    ],
    [
        'title' => 'AIBE Examination Strategy & Subject Marks Weightage',
        'category' => 'AIBE Prep',
        'duration' => '30 Mins',
        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'desc' => 'Comprehensive preparation guide covering high-scoring topics: Constitutional Law, CrPC/BNSS, CPC, and Evidence.',
        'icon' => 'fa-graduation-cap'
    ],
    [
        'title' => 'Understanding Limitation Period & Court Fee Computations',
        'category' => 'Civil Practice',
        'duration' => '25 Mins',
        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'desc' => 'Practical tutorial on calculating limitation expiry under the Limitation Act and calculating ad-valorem court fees.',
        'icon' => 'fa-calculator'
    ],
    [
        'title' => 'Digital Case Tracking & e-Filing 3.0 on e-Courts Portal',
        'category' => 'Legal Technology',
        'duration' => '20 Mins',
        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'desc' => 'Step-by-step guide for advocates on navigating e-Courts Services, CNR lookup, causelist alerts, and e-Filing vakalatnama.',
        'icon' => 'fa-laptop-code'
    ]
];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Resources</span> &bull; <span>Legal Video Hub</span>
    </nav>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fdf8f6 100%); border-left: 5px solid var(--brand-red);">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-play-circle"></i> Continuing Legal Education
        </span>
        <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
            Legal Tutorials & Video Lectures
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 820px; margin-top: 0.35rem; line-height: 1.6;">
            Curated video masterclasses, exam preparation webinars, statutory analysis of the new 2023 criminal codes, and digital practice tutorials for the Indian legal community.
        </p>
    </div>

    <!-- Videos Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.75rem;">
        <?php foreach ($videos as $v): ?>
            <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; padding: 1.5rem;">
                <div>
                    <!-- Video Embed Placeholder / Thumbnail Header -->
                    <div style="background: var(--primary); border-radius: var(--radius-sm); height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #ffffff; margin-bottom: 1.25rem; position: relative; overflow: hidden;">
                        <i class="fas fa-circle-play" style="font-size: 3rem; color: var(--brand-gold); margin-bottom: 0.5rem;"></i>
                        <span style="font-size: 0.8125rem; font-weight: 600; color: #cbd5e1;"><?= sanitize($v['duration']) ?> Tutorial</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <span class="badge-verification badge-basic" style="font-size: 0.75rem;">
                            <i class="fas <?= $v['icon'] ?>"></i> <?= sanitize($v['category']) ?>
                        </span>
                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">
                            <i class="fas fa-clock"></i> <?= sanitize($v['duration']) ?>
                        </span>
                    </div>

                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        <?= sanitize($v['title']) ?>
                    </h3>

                    <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem;">
                        <?= sanitize($v['desc']) ?>
                    </p>
                </div>

                <a href="aibe" class="btn btn-primary btn-sm" style="width: 100%;">
                    <i class="fas fa-play"></i> Watch Video & Learn
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
