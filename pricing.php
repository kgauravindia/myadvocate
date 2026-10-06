<?php
// pricing.php - Advocate Digital Chamber & Verification Plans (Restricted to logged-in advocates)
require_once __DIR__ . '/config/app.php';

// Only show to advocate after login
if (empty($_SESSION['advocate_id'])) {
    header("Location: login?redirect=pricing&auth_required=1");
    exit;
}

$pageTitle = "Advocate Profile Plans & Verification - My Advocate";
$pageDescription = "Explore digital profile claiming, verified bar council credentials, and digital chamber presence plans for legal practitioners on My Advocate.";

$db = getDB();
$advId = $_SESSION['advocate_id'];
$advocate = null;
try {
    $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
    $stmt->execute([$advId]);
    $advocate = $stmt->fetch();
} catch (Exception $e) {}

if (!$advocate) {
    session_destroy();
    header("Location: login");
    exit;
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <a href="dashboard">Advocate Dashboard</a> &bull; <span>Advocate Pricing</span>
    </nav>

    <!-- Advocate Member Banner (Light Theme) -->
    <div style="background: linear-gradient(135deg, #fffbeb 0%, #ffffff 50%, #fef2f2 100%); color: var(--text-main); border-radius: var(--radius-md); padding: 1.25rem 1.5rem; margin-bottom: 2.5rem; border: 1px solid var(--brand-gold-border); border-left: 5px solid var(--brand-red); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: var(--shadow-sm);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 46px; height: 46px; border-radius: 50%; background: var(--brand-red); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.25rem; border: 2px solid var(--brand-gold);">
                <?= strtoupper(substr(trim($advocate['name'] ?? 'A'), 0, 1)) ?>
            </div>
            <div>
                <div style="font-size: 1.05rem; font-weight: 700; color: var(--primary);">
                    <?= sanitize($advocate['name']) ?>
                </div>
                <div style="font-size: 0.8125rem; color: var(--text-muted);">
                    <i class="fas fa-id-card text-warning"></i> Enr No: <strong><?= sanitize($advocate['e_no'] ?? 'N/A') ?><?= !empty($advocate['e_year']) ? '/' . sanitize($advocate['e_year']) : '' ?></strong> &bull; Current Plan: <span class="badge-verification badge-verified" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;"><?= !empty($advocate['plan_type']) ? sanitize($advocate['plan_type']) : 'Active Advocate' ?></span>
                </div>
            </div>
        </div>
        <div>
            <a href="dashboard" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-gauge"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Header Title -->
    <div style="text-align: center; max-width: 760px; margin: 0 auto 3rem;">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-shield-check"></i> Transparent & BCI Compliant
        </span>
        <h1 style="font-size: 2.35rem; color: var(--primary); margin: 0.35rem 0 0.75rem; font-weight: 800;">
            Advocate Digital Presence & Verification
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6;">
            Claim your public record, obtain a verified digital badge, and control your contact privacy on India’s most trusted advocate directory.
        </p>
    </div>

    <!-- Pricing Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
        <!-- Plan 1: Basic Directory Listing -->
        <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--text-muted); padding: 2.25rem 1.75rem;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Public Record</span>
                <h3 style="font-size: 1.5rem; color: var(--primary); margin: 0.25rem 0 0.5rem;">Basic Listing</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">Standard public registry listing derived from State Bar Council gazettes.</p>
                
                <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-size: 2.25rem; font-weight: 800; color: var(--primary);">₹0</span>
                    <span style="color: var(--text-muted); font-size: 0.875rem;">/ lifetime free</span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Name & Bar Council Enrollment Listed</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> State & Primary District Jurisdiction</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Basic Green Verification Badge</li>
                    <li style="color: var(--text-muted);"><i class="fas fa-xmark text-danger" style="margin-right: 0.5rem;"></i> Direct Bio & Specialization Edit</li>
                    <li style="color: var(--text-muted);"><i class="fas fa-xmark text-danger" style="margin-right: 0.5rem;"></i> Granular Privacy Masking Controls</li>
                </ul>
            </div>

            <a href="advocate-search-result" class="btn btn-outline" style="width: 100%;">
                Search Directory
            </a>
        </div>

        <!-- Plan 2: Registered Profile (Claimed) - Most Popular -->
        <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--brand-red); padding: 2.25rem 1.75rem; position: relative; box-shadow: var(--shadow-lg);">
            <div style="position: absolute; top: -13px; right: 20px; background: var(--brand-red); color: #ffffff; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.75rem; border-radius: var(--radius-full); letter-spacing: 0.5px;">
                Recommended
            </div>
            <div>
                <span style="font-size: 0.75rem; font-weight: 800; color: var(--brand-red); text-transform: uppercase; letter-spacing: 0.5px;">OTP Verified</span>
                <h3 style="font-size: 1.5rem; color: var(--primary); margin: 0.25rem 0 0.5rem;">Claimed & Active</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">Full profile management and privacy control for practicing advocates.</p>
                
                <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-size: 2.25rem; font-weight: 800; color: var(--primary);">₹0</span>
                    <span style="color: var(--text-muted); font-size: 0.875rem;">/ free claim</span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> <strong>Blue Verified Claim Badge</strong></li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Custom Bio & Professional Summary</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Up to 6 Practice Areas & Specializations</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Mobile & Email Privacy Visibility Toggles</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Personal Digital Profile Web Link</li>
                </ul>
            </div>

            <a href="claim-profile" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-shield-halved"></i> Claim Your Profile Free
            </a>
        </div>

        <!-- Plan 3: Verified Digital Chamber -->
        <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid var(--brand-gold); padding: 2.25rem 1.75rem; background: #fffefb;">
            <div>
                <span style="font-size: 0.75rem; font-weight: 800; color: var(--brand-gold-text); text-transform: uppercase; letter-spacing: 0.5px;">Premium Chamber</span>
                <h3 style="font-size: 1.5rem; color: var(--primary); margin: 0.25rem 0 0.5rem;">Digital Chamber Pro</h3>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">Complete digital suite, certified gold badge, and dedicated chamber URL.</p>
                
                <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border-color);">
                    <span style="font-size: 2.25rem; font-weight: 800; color: var(--primary);">₹499</span>
                    <span style="color: var(--text-muted); font-size: 0.875rem;">/ year</span>
                </div>

                <ul style="list-style: none; padding: 0; margin: 0 0 2rem 0; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> <strong>Certified Gold Verification Badge</strong></li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Custom Profile URL (<code>myadv.in/profile/yourname</code>)</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Priority Discovery in District Search</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Multiple Chamber Addresses & Courts</li>
                    <li><i class="fas fa-check text-success" style="margin-right: 0.5rem;"></i> Dedicated Support Desk & ID Verification</li>
                </ul>
            </div>

            <a href="claim-profile" class="btn btn-gold" style="width: 100%;">
                <i class="fas fa-certificate"></i> Upgrade Digital Chamber
            </a>
        </div>
    </div>

    <!-- BCI Compliance Notice -->
    <div style="background: var(--bg-alt); padding: 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); text-align: center; max-width: 800px; margin: 0 auto;">
        <small style="color: var(--text-muted); line-height: 1.6; display: block;">
            <i class="fas fa-shield-alt text-warning"></i> <strong>BCI Compliance Disclaimer:</strong> 
            My Advocate does not advertise or solicit legal work for any practitioner. Pricing covers technical hosting, identity authentication, domain URL routing, and security guardrails for advocate digital profiles under BCI non-solicitation guidelines.
        </small>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
