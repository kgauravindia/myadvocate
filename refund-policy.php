<?php
// refund-policy.php - Cancellation & Refund Policy
require_once __DIR__ . '/config/app.php';

$pageTitle = "Cancellation and Refund Policy - My Advocate";
$pageDescription = "Official Cancellation and Refund Policy for digital subscriptions, verification plans, and legal tools on My Advocate.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Legal & Compliance</span> &bull; <span>Cancellation & Refund Policy</span>
    </nav>

    <div style="max-width: 900px; margin: 0 auto;">
        <!-- Legal Switcher Tabs -->
        <div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 4px;">
            <a href="disclaimer" class="btn btn-outline btn-sm">
                <i class="fas fa-gavel"></i> BCI Disclaimer
            </a>
            <a href="privacy" class="btn btn-outline btn-sm">
                <i class="fas fa-user-shield"></i> Privacy Policy
            </a>
            <a href="terms" class="btn btn-outline btn-sm">
                <i class="fas fa-file-contract"></i> Terms and Conditions
            </a>
            <a href="refund-policy" class="btn btn-primary btn-sm">
                <i class="fas fa-rotate-left"></i> Refund Policy
            </a>
        </div>

        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
                        <i class="fas fa-hand-holding-dollar"></i> Billing & Refunds
                    </span>
                    <h1 style="font-size: 2rem; color: var(--primary); margin-top: 0.25rem;">
                        Cancellation and Refund Policy
                    </h1>
                </div>
                <div style="font-size: 0.8125rem; color: var(--text-muted); background: var(--bg-alt); padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-weight: 600;">
                    <i class="fas fa-calendar-check" style="color: var(--brand-gold);"></i> Effective: 02 Oct 2026
                </div>
            </div>

            <div style="line-height: 1.8; color: var(--text-main); font-size: 0.9375rem; display: flex; flex-direction: column; gap: 1.75rem;">
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">1. Overview</h3>
                    <p style="margin-bottom: 0.75rem;">
                        At <strong>My Advocate</strong> (operated by <strong>OfferPlant Technologies Private Limited</strong>), we strive to deliver top-tier verified legal intelligence, profile management, and digital registry tools. This Cancellation and Refund Policy outlines terms applicable to online payments, membership subscriptions, and verification badges.
                    </p>
                </div>

                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">2. Membership & Verification Badges</h3>
                    <p style="margin-bottom: 0.75rem;">
                        Advocate verification involves human and algorithmic checks against official State Bar Council directories. Once a profile verification order has been reviewed and approved, verification fees are non-refundable as verification resources are committed immediately upon application submission.
                    </p>
                </div>

                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">3. Duplicate or Erroneous Transactions</h3>
                    <p style="margin-bottom: 0.75rem;">
                        In cases where an applicant is charged multiple times due to a payment gateway timeout or banking glitch, the duplicate transaction amount will be refunded in full to the original payment source within <strong>5–7 working days</strong>.
                    </p>
                </div>

                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">4. Refund Request Process</h3>
                    <p style="margin-bottom: 0.75rem;">
                        To request assistance with billing or initiate a refund inquiry:
                    </p>
                    <ul style="padding-left: 1.5rem; margin-bottom: 0.75rem;">
                        <li>Email our billing helpdesk at <strong><?= sanitize(getSiteConfig('support_email', 'contact@myadv.in')) ?></strong> with your Transaction ID, Advocate Name, and Enrollment Details.</li>
                        <li>Contact customer support at <strong><?= sanitize(getSiteConfig('support_phone', '+91 81029 30609')) ?></strong> (Mon–Sat, 10:00 AM – 6:00 PM IST).</li>
                    </ul>
                </div>

                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">5. Contact & Grievance Officer</h3>
                    <p>
                        <strong>OfferPlant Technologies Private Limited</strong><br>
                        2B, Kumar Bhawan, Umanagar, Chapra, District Saran, Bihar 841301, India<br>
                        Support: <?= sanitize(getSiteConfig('support_email', 'contact@myadv.in')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
