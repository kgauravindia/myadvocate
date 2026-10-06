<?php
// public-service-commission.php - Public Service Commissions & Judicial Exams Directory
require_once __DIR__ . '/config/app.php';

$pageTitle = "Public Service Commissions of India - Judicial & Civil Exams";
$pageDescription = "Official directory of State and Central Public Service Commissions (PSC / UPSC) conducting Judicial Services and Civil Examinations.";

$db = getDB();
$pscs = [];

try {
    $stmt = $db->query("SELECT * FROM psc WHERE status = 'ACTIVE' ORDER BY name ASC");
    $pscs = $stmt->fetchAll();
} catch (Exception $e) {
    $pscs = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Resources</span> &bull; <span>Public Service Commissions</span>
    </nav>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fdf8f6 100%); border-left: 5px solid var(--brand-red);">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-graduation-cap"></i> Judicial & Civil Service Recruitment
        </span>
        <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
            Public Service Commissions (UPSC / State PSC)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 820px; margin-top: 0.35rem; line-height: 1.6;">
            Access official recruitment portals, notifications, syllabus, and application gateways for the <strong>Union Public Service Commission (UPSC)</strong> and all <strong>State Public Service Commissions (SPSCs)</strong> conducting Civil and Judicial Services examinations.
        </p>
    </div>

    <!-- Search / Filter Input -->
    <div class="stat-box" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <div style="position: relative;">
            <i class="fas fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="pscSearchInput" class="filter-input" placeholder="Search commission by state, name or code (e.g. BPSC, UPPSC, UPSC)..." style="padding-left: 2.5rem;">
        </div>
    </div>

    <!-- Table Card -->
    <div class="stat-box" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <h2 style="font-size: 1.15rem; color: var(--primary); margin: 0;">
                <i class="fas fa-landmark" style="color: var(--brand-red);"></i> Commission Portals & Examination Bodies
            </h2>
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;" id="pscCount">
                <?= count($pscs) ?> Commissions Listed
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" id="pscTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9375rem;">
                <thead>
                    <tr style="background: var(--bg-alt); border-bottom: 2px solid var(--border-color); color: var(--primary);">
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700;">Commission Name</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700; width: 140px;">Code</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700; width: 140px;">Established</th>
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700; text-align: right; width: 180px;">Official Portal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pscs as $p): ?>
                        <tr class="psc-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;" onmouseover="this.style.background='#fdf8f6'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.25rem;">
                                <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.2rem;">
                                    <i class="fas fa-building" style="color: var(--brand-gold); margin-right: 0.4rem; font-size: 0.85rem;"></i> <?= sanitize($p['name']) ?>
                                </div>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Judicial Services & Civil Examinations</span>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge-verification badge-verified" style="font-weight: 700;">
                                    <?= sanitize($p['code'] ?? 'PSC') ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-weight: 600; color: var(--text-muted);">
                                    <?= sanitize($p['year'] ?: 'Public Charter') ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right;">
                                <?php if (!empty($p['url'])): ?>
                                    <a href="<?= sanitize($p['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                        <i class="fas fa-arrow-up-right-from-square"></i> Visit Portal
                                    </a>
                                <?php else: ?>
                                    <span style="font-size: 0.8125rem; color: var(--text-muted);">In Directory</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('pscSearchInput');
    const rows = document.querySelectorAll('.psc-row');
    const countEl = document.getElementById('pscCount');

    if (input) {
        input.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            let visible = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(val)) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countEl) {
                countEl.textContent = `${visible} Commission${visible === 1 ? '' : 's'} Found`;
            }
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
