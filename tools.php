<?php
// tools.php - Practical Legal Tools & Calculators
require_once __DIR__ . '/config/app.php';

$pageTitle = "Legal Tools & Calculators - Court Fee, Limitation & Judicial Reference";
$pageDescription = "Free online legal utility tools for advocates, litigants, and law students: Court Fee Calculator, Limitation Period Finder, and Date Reference.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Utilities</span> &bull; <span>Legal Tools</span>
    </nav>

    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
            <i class="fas fa-calculator" style="color: var(--brand-red);"></i> Legal Tools & Calculators
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">
            Fast, reliable procedural reference tools designed for advocates and litigants.
        </p>
    </div>

    <!-- Calculators Grid -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;" class="tools-grid">
        <style>
            @media(min-width: 992px) {
                .tools-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
        </style>

        <!-- Tool 1: Court Fee Calculator -->
        <div class="stat-box" id="courtFee" style="border-top: 4px solid var(--brand-red);">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div class="action-icon icon-red" style="width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-coins"></i></div>
                <h2 style="font-size: 1.3rem; color: var(--primary);">Court Fee Calculator</h2>
            </div>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
                Estimate ad-valorem or fixed court fees payable on civil suits, partition suits, and injunctions.
            </p>

            <form id="courtFeeForm">
                <div class="filter-group">
                    <label class="filter-label">Type of Suit / Petition</label>
                    <select id="suitType" class="filter-select">
                        <option value="money">Money Recovery Suit (Ad-valorem)</option>
                        <option value="partition">Partition Suit (Fixed / Share-based)</option>
                        <option value="injunction">Permanent Injunction</option>
                        <option value="declaration">Declaratory Suit with Consequential Relief</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Valuation / Claim Amount (₹)</label>
                    <input type="number" id="claimAmount" class="filter-input" placeholder="e.g. 250000" min="0" value="100000" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-calculator"></i> Calculate Court Fee
                </button>
            </form>

            <div id="courtFeeResult" style="margin-top: 1.25rem; display: none;"></div>
        </div>

        <!-- Tool 2: Limitation Period Calculator -->
        <div class="stat-box" id="limitation" style="border-top: 4px solid var(--brand-gold);">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div class="action-icon icon-gold" style="width: 36px; height: 36px; font-size: 1rem;"><i class="fas fa-hourglass-half"></i></div>
                <h2 style="font-size: 1.3rem; color: var(--primary);">Limitation Period Calculator</h2>
            </div>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
                Calculate the last statutory date for filing suits and appeals under the Limitation Act, 1963.
            </p>

            <form id="limitationForm">
                <div class="filter-group">
                    <label class="filter-label">Nature of Proceeding / Article Schedule</label>
                    <select id="limitationArticle" class="filter-select">
                        <option value="1095">Civil Money Suit / Contract Breach (3 Years)</option>
                        <option value="4380">Suit for Possession based on Title (12 Years)</option>
                        <option value="10950">Mortgage Foreclosure / Redemption (30 Years)</option>
                        <option value="90">High Court First Appeal (90 Days)</option>
                        <option value="30">District Court Appeal (30 Days)</option>
                        <option value="90">Supreme Court SLP (90 Days)</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Date of Cause of Action / Order Date</label>
                    <input type="date" id="causeDate" class="filter-input" required value="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn btn-gold" style="width: 100%;">
                    <i class="fas fa-clock"></i> Check Limitation
                </button>
            </form>

            <div id="limitationResult" style="margin-top: 1.25rem; display: none;"></div>
        </div>
    </div>

    <!-- Informational Note -->
    <div class="stat-box" style="margin-top: 2rem; background: #f8fafc; border-left: 4px solid #64748b;">
        <h4 style="font-size: 0.95rem; color: var(--primary); margin-bottom: 0.25rem;"><i class="fas fa-info-circle"></i> Informational Notice</h4>
        <p style="font-size: 0.8125rem; color: var(--text-muted);">
            All calculation tools on My Advocate are intended strictly for general reference and preliminary estimation. Court fee calculations vary across state-specific amendments, court circulars, and judicial discretion.
        </p>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
