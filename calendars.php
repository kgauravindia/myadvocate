<?php
// calendars.php - High Court & Civil Court Official Calendars
require_once __DIR__ . '/config/app.php';

$pageTitle = "Court Calendars 2026 - High Courts & Supreme Court Holidays";
$pageDescription = "Download official 2026 and historical holiday calendars for the Supreme Court of India, Patna High Court, and Indian Civil Courts.";

$calendars = [
    [
        'title' => 'Civil Court of Bihar, High Court of Judicature At Patna and SCI',
        'year' => '2026',
        'file' => '2026.pdf',
        'status' => 'Current Year',
        'is_current' => true
    ],
    [
        'title' => 'Civil Court of Bihar, High Court of Judicature At Patna and SCI',
        'year' => '2025',
        'file' => '2025.pdf',
        'status' => 'Previous Year',
        'is_current' => false
    ],
    [
        'title' => 'Civil Court of Bihar, High Court of Judicature At Patna and SCI',
        'year' => '2024',
        'file' => '2024.pdf',
        'status' => 'Archive',
        'is_current' => false
    ],
    [
        'title' => 'Civil Court of Bihar, High Court of Judicature At Patna and SCI',
        'year' => '2023',
        'file' => '2023.pdf',
        'status' => 'Archive',
        'is_current' => false
    ],
    [
        'title' => 'Civil Court of Bihar, High Court of Judicature At Patna and SCI',
        'year' => '2022',
        'file' => '2022.pdf',
        'status' => 'Archive',
        'is_current' => false
    ]
];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Downloads</span> &bull; <span>Court Calendars</span>
    </nav>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fdf8f6 100%); border-left: 5px solid var(--brand-red);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
                    <i class="fas fa-calendar-days"></i> Official Working Days & Holidays
                </span>
                <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
                    Indian Court Calendars (2022 – 2026)
                </h1>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 680px; margin-top: 0.25rem;">
                    Official PDF schedules detailing vacation sittings, judicial working days, gazetted national holidays, and regional festival breaks for the Supreme Court of India, Patna High Court, and subordinate district courts.
                </p>
            </div>
            <a href="2026.pdf" target="_blank" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Download 2026 Calendar
            </a>
        </div>
    </div>

    <!-- Calendar Table List -->
    <div class="stat-box" style="padding: 0; overflow: hidden;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <h2 style="font-size: 1.15rem; color: var(--primary); margin: 0;">
                <i class="fas fa-download" style="color: var(--brand-red);"></i> Judicial Calendars Library
            </h2>
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;">
                <?= count($calendars) ?> Official Releases
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9375rem;">
                <thead>
                    <tr style="background: var(--bg-alt); border-bottom: 2px solid var(--border-color); color: var(--primary);">
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700;">Court & Jurisdiction</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700; width: 140px;">Calendar Year</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700; width: 140px;">Status</th>
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700; text-align: right; width: 180px;">Download / View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calendars as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;" onmouseover="this.style.background='#fdf8f6'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.25rem;">
                                <div style="font-weight: 600; color: var(--primary); margin-bottom: 0.2rem;">
                                    <i class="fas fa-landmark" style="color: var(--brand-red); margin-right: 0.4rem;"></i> <?= sanitize($c['title']) ?>
                                </div>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Verified PDF Release &bull; Public Gazetted Notice</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-weight: 800; font-size: 1.1rem; color: var(--brand-red);">
                                    <?= sanitize($c['year']) ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <?php if ($c['is_current']): ?>
                                    <span class="badge-verification badge-verified">
                                        <i class="fas fa-circle-check"></i> Current Year
                                    </span>
                                <?php else: ?>
                                    <span class="badge-verification badge-basic">
                                        <i class="fas fa-box-archive"></i> <?= sanitize($c['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right;">
                                <a href="<?= sanitize($c['file']) ?>" target="_blank" class="btn <?= $c['is_current'] ? 'btn-gold' : 'btn-outline' ?> btn-sm">
                                    <i class="fas fa-file-pdf"></i> View PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
