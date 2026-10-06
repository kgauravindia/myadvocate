<?php
// admin/settings.php - Portal Settings & Configuration
$pageTitle = "Portal Settings";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';

// Helper to get config value
function getAdminConfig($name, $default = '') {
    global $db;
    try {
        $st = $db->prepare("SELECT option_value FROM op_config WHERE option_name = ? LIMIT 1");
        $st->execute([$name]);
        $val = $st->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Helper to set config value
function setAdminConfig($name, $value) {
    global $db;
    $st = $db->prepare("SELECT id FROM op_config WHERE option_name = ? LIMIT 1");
    $st->execute([$name]);
    if ($st->fetchColumn()) {
        $up = $db->prepare("UPDATE op_config SET option_value = ?, allow_edit = 'YES', updated_at = NOW() WHERE option_name = ?");
        $up->execute([$value, $name]);
    } else {
        $ins = $db->prepare("INSERT INTO op_config (option_name, option_value, default_value, option_type, status, allow_edit, created_at) VALUES (?, ?, ?, 'SINGLE', 'ACTIVE', 'YES', NOW())");
        $ins->execute([$name, $value, $value]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = sanitize($_POST['site_name'] ?? 'MY ADVOCATE');
    $sitePhone = sanitize($_POST['site_phone'] ?? '');
    $siteEmail = sanitize($_POST['site_email'] ?? '');
    $siteAddress = sanitize($_POST['site_address'] ?? '');
    $disclaimerNotice = sanitize($_POST['disclaimer_notice'] ?? '');
    $tickerNotice = sanitize($_POST['ticker_notice'] ?? '');
    $metaDescription = sanitize($_POST['meta_description'] ?? '');

    // Social Media URLs
    $facebook = sanitize($_POST['facebook'] ?? '');
    $twitter = sanitize($_POST['twitter'] ?? '');
    $linkedin = sanitize($_POST['linkedin'] ?? '');
    $instagram = sanitize($_POST['instagram'] ?? '');
    $telegram = sanitize($_POST['telegram'] ?? '');
    $youtube = sanitize($_POST['youtube'] ?? '');
    $whatsapp = sanitize($_POST['whatsapp'] ?? '');
    $olawApiKey = sanitize($_POST['olaw_api_key'] ?? OLAW_API_KEY);
    $olawApiUrl = sanitize($_POST['olaw_api_url'] ?? OLAW_API_URL);

    try {
        setAdminConfig('inst_name', $siteName);
        setAdminConfig('inst_contact', $sitePhone);
        setAdminConfig('inst_support', $sitePhone);
        setAdminConfig('inst_email', $siteEmail);
        setAdminConfig('inst_address2', $siteAddress);
        setAdminConfig('disclaimer_notice', $disclaimerNotice);
        setAdminConfig('ticker_notice', $tickerNotice);
        setAdminConfig('meta_description', $metaDescription);

        setAdminConfig('facebook', $facebook);
        setAdminConfig('twitter', $twitter);
        setAdminConfig('linkedin', $linkedin);
        setAdminConfig('instagram', $instagram);
        setAdminConfig('telegram', $telegram);
        setAdminConfig('youtube', $youtube);
        setAdminConfig('whatsapp', $whatsapp);
        setAdminConfig('olaw_api_key', $olawApiKey);
        setAdminConfig('olaw_api_url', $olawApiUrl);

        $msg = "Portal settings, API configurations, and social media URLs updated successfully.";
    } catch (Exception $e) {
        $err = "Error updating settings: " . $e->getMessage();
    }
}

$siteName = getAdminConfig('inst_name', 'MY ADVOCATE');
$sitePhone = getAdminConfig('inst_contact', '9431426600');
$siteEmail = getAdminConfig('inst_email', 'help@myadv.in');
$siteAddress = getAdminConfig('inst_address2', 'High Court Enclave, New Delhi, India');
$disclaimerNotice = getAdminConfig('disclaimer_notice', 'As per the Bar Council of India rules, advocates are not permitted to advertise or solicit work.');
$tickerNotice = getAdminConfig('ticker_notice', 'Welcome to MY ADVOCATE — India\'s Premier Legal Directory & Bare Acts Portal.');
$metaDescription = getAdminConfig('meta_description', 'Search 161,800+ verified Indian advocates, read latest Bare Acts (BNS, BNSS, BSA), and explore Law Colleges.');

$facebook = getAdminConfig('facebook', 'https://facebook.com/MyAdvocateAI');
$twitter = getAdminConfig('twitter', 'https://x.com/MyAdvocateAI');
$linkedin = getAdminConfig('linkedin', 'https://linkedin.com/company/MyAdvocateAI');
$instagram = getAdminConfig('instagram', 'https://instagram.com/MyAdvocateAI');
$telegram = getAdminConfig('telegram', 'https://t.me/MyAdvocateAI');
$youtube = getAdminConfig('youtube', 'https://youtube.com/@MyAdvocateAI');
$whatsapp = getAdminConfig('whatsapp', 'https://whatsapp.com/channel/0029VaA5aAnL7UVOohTGS31P');
$olawApiKey = getAdminConfig('olaw_api_key', OLAW_API_KEY);
$olawApiUrl = getAdminConfig('olaw_api_url', OLAW_API_URL);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Portal Settings & Configuration</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Configure platform branding, official social media handles, contact details, and disclaimers.</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<form action="settings.php" method="POST">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;" class="admin-settings-grid">
        <style>
            @media(max-width: 992px) {
                .admin-settings-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>

        <!-- General Platform Settings -->
        <div class="admin-card" style="border-top: 4px solid var(--brand-red);">
            <h3 class="admin-card-title" style="margin-bottom: 1.25rem;"><i class="fas fa-gear"></i> General Settings</h3>

            <div class="filter-group">
                <label class="filter-label">Portal Title / Brand Name</label>
                <input type="text" name="site_name" class="filter-input" value="<?= sanitize($siteName) ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Primary Support Phone</label>
                    <input type="text" name="site_phone" class="filter-input" value="<?= sanitize($sitePhone) ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Support Email Address</label>
                    <input type="email" name="site_email" class="filter-input" value="<?= sanitize($siteEmail) ?>">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Office / Secretariat Address</label>
                <input type="text" name="site_address" class="filter-input" value="<?= sanitize($siteAddress) ?>">
            </div>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

            <!-- Official Social Media Handles -->
            <h3 class="admin-card-title" style="margin-bottom: 1.25rem;"><i class="fas fa-share-nodes"></i> Official Social Media URLs</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-facebook" style="color: #1877f2;"></i> Facebook Page URL</label>
                    <input type="url" name="facebook" class="filter-input" value="<?= sanitize($facebook) ?>" placeholder="https://facebook.com/MyAdvocateAI">
                </div>
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-instagram" style="color: #e4405f;"></i> Instagram Profile URL</label>
                    <input type="url" name="instagram" class="filter-input" value="<?= sanitize($instagram) ?>" placeholder="https://instagram.com/MyAdvocateAI">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-x-twitter"></i> Twitter / X Handle URL</label>
                    <input type="url" name="twitter" class="filter-input" value="<?= sanitize($twitter) ?>" placeholder="https://x.com/MyAdvocateAI">
                </div>
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-telegram" style="color: #0088cc;"></i> Telegram Channel / Group URL</label>
                    <input type="url" name="telegram" class="filter-input" value="<?= sanitize($telegram) ?>" placeholder="https://t.me/MyAdvocateAI">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-linkedin" style="color: #0a66c2;"></i> LinkedIn Company URL</label>
                    <input type="url" name="linkedin" class="filter-input" value="<?= sanitize($linkedin) ?>" placeholder="https://linkedin.com/company/MyAdvocateAI">
                </div>
                <div class="filter-group">
                    <label class="filter-label"><i class="fab fa-youtube" style="color: #ff0000;"></i> YouTube Channel URL</label>
                    <input type="url" name="youtube" class="filter-input" value="<?= sanitize($youtube) ?>" placeholder="https://youtube.com/@MyAdvocateAI">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label"><i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Channel / Community URL</label>
                <input type="url" name="whatsapp" class="filter-input" value="<?= sanitize($whatsapp) ?>" placeholder="https://whatsapp.com/channel/0029VaA5aAnL7UVOohTGS31P">
            </div>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

            <h3 class="admin-card-title" style="margin-bottom: 1.25rem;"><i class="fas fa-network-wired"></i> External API & Data Integration (olaw.in)</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">OLAW API Key</label>
                    <input type="text" name="olaw_api_key" class="filter-input" value="<?= sanitize($olawApiKey) ?>" placeholder="OLAW_D776A66967200383A932" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">OLAW API Endpoint URL</label>
                    <input type="url" name="olaw_api_url" class="filter-input" value="<?= sanitize($olawApiUrl) ?>" placeholder="https://olaw.in/api.php" required>
                </div>
            </div>
            <div style="margin-top: -0.5rem; margin-bottom: 1.25rem;">
                <small style="color: var(--text-muted);">
                    Used for Bank IFSC, Postal PIN Codes, Bare Acts, and NIC lookups. 
                    <a href="api_tools.php" style="color: var(--brand-red); font-weight: 700; text-decoration: none;">Open Live API Tools &rsaquo;</a>
                </small>
            </div>

            <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--border-color);">

            <h3 class="admin-card-title" style="margin-bottom: 1.25rem;"><i class="fas fa-bullhorn"></i> Notices & Regulatory Disclaimers</h3>

            <div class="filter-group">
                <label class="filter-label">Announcement / Live Ticker Message</label>
                <input type="text" name="ticker_notice" class="filter-input" value="<?= sanitize($tickerNotice) ?>" placeholder="Banner text for visitors">
            </div>

            <div class="filter-group">
                <label class="filter-label">Bar Council Compliance Disclaimer Text</label>
                <textarea name="disclaimer_notice" class="filter-input" rows="3"><?= sanitize($disclaimerNotice) ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">SEO Meta Description</label>
                <textarea name="meta_description" class="filter-input" rows="3"><?= sanitize($metaDescription) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Configuration</button>
        </div>

        <!-- System Information -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div class="admin-card" style="border-top: 4px solid var(--brand-gold);">
                <h3 class="admin-card-title" style="margin-bottom: 1rem;"><i class="fas fa-server"></i> System Information</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <div>
                        <span style="color: var(--text-muted);">PHP Version:</span>
                        <strong><?= phpversion() ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Database:</span>
                        <strong>MySQL (u305984835_myadv)</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Server Environment:</span>
                        <strong>Laragon / Apache</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">App Version:</span>
                        <strong><?= APP_VERSION ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Brand Theme:</span>
                        <span style="color: var(--brand-red); font-weight: 700;">Red</span> / <span style="color: var(--brand-gold-dark); font-weight: 700;">Yellow</span> / <span style="font-weight: 700;">Black</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
