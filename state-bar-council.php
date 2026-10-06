<?php
// state-bar-council.php - State Bar Councils of India Directory
require_once __DIR__ . '/config/app.php';

$pageTitle = "State Bar Councils of India - Official Directory & Portals";
$pageDescription = "Complete directory of all 26 State Bar Councils in India established under the Advocates Act, 1961 with official websites and BCI codes.";

$db = getDB();
$bcs = [];

try {
    $stmt = $db->query("SELECT bc.*, s.name as state_name FROM bc LEFT JOIN state s ON bc.state_code = s.code WHERE bc.status = 'ACTIVE' ORDER BY bc.name ASC");
    $bcs = $stmt->fetchAll();
} catch (Exception $e) {
    try {
        $stmt = $db->query("SELECT * FROM bc WHERE status = 'ACTIVE' ORDER BY name ASC");
        $bcs = $stmt->fetchAll();
    } catch (Exception $ex) {
        $bcs = [];
    }
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Resources</span> &bull; <span>State Bar Councils</span>
    </nav>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fffdf0 100%); border-left: 5px solid var(--brand-gold);">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-scale-balanced"></i> Statutory Regulatory Authorities
        </span>
        <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
            State Bar Councils of India
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 820px; margin-top: 0.35rem; line-height: 1.6;">
            State Bar Councils (SBCs) derive their statutory authority from the <strong>Advocates Act, 1961</strong>. Governing enrollment, professional conduct, and advocacy standards under the apex supervision of the <strong>Bar Council of India (BCI)</strong>.
        </p>
    </div>

    <!-- Search / Filter Input -->
    <div class="stat-box" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <div style="position: relative;">
            <i class="fas fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            <input type="text" id="bcSearchInput" class="filter-input" placeholder="Search Bar Council by state, name or BCI code..." style="padding-left: 2.5rem;">
        </div>
    </div>

    <!-- Bar Councils Grid / Table -->
    <div class="stat-box" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <h2 style="font-size: 1.15rem; color: var(--primary); margin: 0;">
                <i class="fas fa-building-columns" style="color: var(--brand-red);"></i> Registered State Bar Councils
            </h2>
            <span style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;" id="bcCount">
                <?= count($bcs) ?> Statutory Councils
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" id="bcTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9375rem;">
                <thead>
                    <tr style="background: var(--bg-alt); border-bottom: 2px solid var(--border-color); color: var(--primary);">
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700;">Bar Council Name</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700;">State Jurisdiction</th>
                        <th style="padding: 0.85rem 1rem; font-weight: 700; width: 140px;">BCI Code</th>
                        <th style="padding: 0.85rem 1.25rem; font-weight: 700; text-align: right; width: 180px;">Official Portal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bcs as $bc): ?>
                        <tr class="bc-row" style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;" onmouseover="this.style.background='#fdf8f6'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.25rem;">
                                <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.2rem;" class="bc-name">
                                    <i class="fas fa-gavel" style="color: var(--brand-red); margin-right: 0.4rem; font-size: 0.85rem;"></i> <?= sanitize($bc['name']) ?>
                                </div>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">Enrollment & Disciplinary Jurisdiction</span>
                            </td>
                            <td style="padding: 1rem;" class="bc-state">
                                <span style="font-weight: 600; color: var(--text-main);">
                                    <?= sanitize($bc['state_name'] ?? getStateName($bc['state_code'] ?? '') ?: ($bc['state_code'] ?? 'State Council')) ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <span class="badge-verification badge-basic" style="font-weight: 700;">
                                    <?= sanitize($bc['bci_code'] ?? $bc['code'] ?? 'BCI') ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 1.25rem; text-align: right;">
                                <?php if (!empty($bc['url'])): ?>
                                    <a href="<?= sanitize($bc['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">
                                        <i class="fas fa-arrow-up-right-from-square"></i> Visit Website
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
    const input = document.getElementById('bcSearchInput');
    const rows = document.querySelectorAll('.bc-row');
    const countEl = document.getElementById('bcCount');

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
                countEl.textContent = `${visible} Council${visible === 1 ? '' : 's'} Found`;
            }
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
