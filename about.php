<?php
// about.php - About My Advocate Platform
require_once __DIR__ . '/config/app.php';

$pageTitle = "About My Advocate - India's Digital Legal Information Platform";
$pageDescription = "Learn about My Advocate (formerly MyAdv India), our mission, verification methodology, and legal resources.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; <span>About Us</span>
    </nav>

    <div style="max-width: 900px; margin: 0 auto;">
        <!-- Header Switcher Navigation Tabs -->
        <div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 4px;">
            <a href="about" class="btn btn-primary btn-sm">
                <i class="fas fa-circle-info"></i> About Us
            </a>
            <a href="mission-vision" class="btn btn-outline btn-sm">
                <i class="fas fa-compass"></i> Mission & Vision
            </a>
            <a href="pricing" class="btn btn-outline btn-sm">
                <i class="fas fa-shield-halved"></i> Verification Plans
            </a>
            <a href="disclaimer" class="btn btn-outline btn-sm">
                <i class="fas fa-gavel"></i> BCI Disclaimer
            </a>
            <a href="contact" class="btn btn-outline btn-sm">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>

        <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem;">
            About My Advocate
        </h1>
        <p style="font-size: 1.15rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 2rem;">
            My Advocate (formerly MyAdv India) is India's dedicated digital platform unifying advocate directory records, Bare Acts, courts, examination prep, and legal utility tools.
        </p>

        <div class="stat-box" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-bullseye" style="color: var(--brand-red);"></i> Our Core Mission</h2>
            <p style="line-height: 1.7; color: var(--text-main); margin-bottom: 1rem;">
                Our mission is to empower citizens, practicing advocates, and law students through transparent, organized, and reliable access to India's legal ecosystem. By modernizing public records without breaking historical links, we serve as the authoritative bridge between citizens needing legal representation and verified legal professionals.
            </p>
        </div>

        <div class="stat-box" id="verification" style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 1rem;"><i class="fas fa-certificate" style="color: var(--brand-gold);"></i> Verification Methodology</h2>
            <p style="line-height: 1.7; color: var(--text-main); margin-bottom: 1rem;">
                We maintain a strict 3-tier verification classification:
            </p>
            <ul style="padding-left: 1.25rem; line-height: 1.8; color: var(--text-main);">
                <li><strong>🟢 Basic Profile (Public Record):</strong> Sourced from official State Bar Council gazettes and public court registries.</li>
                <li><strong>🔵 Registered Profile:</strong> Claimed and managed directly by the practicing advocate via OTP authorization.</li>
                <li><strong>🟣 Verified Information:</strong> Authenticated with bar council ID cards, certificates of practice, or verified state records.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
