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
        if (!$db) {
            $db = getDB();
        }
        $st = $db->prepare("SELECT option_value FROM op_config WHERE option_name = ? LIMIT 1");
        $st->execute([$name]);
        $val = $st->fetchColumn();
        return ($val !== false && $val !== null) ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

// Helper to set config value
function setAdminConfig($name, $value) {
    global $db;
    try {
        if (!$db) {
            $db = getDB();
        }
        $st = $db->prepare("SELECT id FROM op_config WHERE option_name = ? LIMIT 1");
        $st->execute([$name]);
        if ($st->fetchColumn()) {
            $up = $db->prepare("UPDATE op_config SET option_value = ?, allow_edit = 'YES', updated_at = NOW() WHERE option_name = ?");
            $up->execute([$value, $name]);
        } else {
            $ins = $db->prepare("INSERT INTO op_config (option_name, option_value, default_value, option_type, status, allow_edit, created_at) VALUES (?, ?, ?, 'SINGLE', 'ACTIVE', 'YES', NOW()) ON DUPLICATE KEY UPDATE option_value = VALUES(option_value), allow_edit = 'YES', updated_at = NOW()");
            $ins->execute([$name, $value, $value]);
        }
    } catch (Throwable $e) {
        error_log("setAdminConfig error for {$name}: " . $e->getMessage());
        throw $e;
    }
}

// Fallback defaults for external API
$defaultOlawKey = defined('OLAW_API_KEY') ? OLAW_API_KEY : 'OLAW_D776A66967200383A932';
$defaultOlawUrl = defined('OLAW_API_URL') ? OLAW_API_URL : 'https://olaw.in/api.php';

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
    $olawApiKey = sanitize($_POST['olaw_api_key'] ?? $defaultOlawKey);
    $olawApiUrl = sanitize($_POST['olaw_api_url'] ?? $defaultOlawUrl);

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
    } catch (Throwable $e) {
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
$olawApiKey = getAdminConfig('olaw_api_key', $defaultOlawKey);
$olawApiUrl = getAdminConfig('olaw_api_url', $defaultOlawUrl);

// Git & Deployment Version Preview (Safe for both CLI & Cloud Online Hosting)
$gitBranch = 'main';
$gitCommit = '';
$repoRoot = dirname(__DIR__);

// 1. Read .git_version first (Used for zero-CLI online hostings)
$versionFile = $repoRoot . '/.git_version';
if (file_exists($versionFile)) {
    $verData = @json_decode(@file_get_contents($versionFile), true);
    if (!empty($verData['branch'])) {
        $gitBranch = $verData['branch'];
    }
    if (!empty($verData['commit'])) {
        $gitCommit = substr($verData['commit'], 0, 7);
        if (!empty($verData['updated_at'])) {
            $gitCommit .= ' (' . date('M j, Y', strtotime($verData['updated_at'])) . ')';
        }
    }
}

// 2. If Git CLI is available and .git directory exists, query live git
$disabledFns = array_map('trim', explode(',', (string)@ini_get('disable_functions')));
$canShellExec = function_exists('shell_exec') && !in_array('shell_exec', $disabledFns);

if ($canShellExec && is_dir($repoRoot . '/.git')) {
    try {
        $gb = @shell_exec('git -C ' . escapeshellarg($repoRoot) . ' rev-parse --abbrev-ref HEAD 2>&1');
        if ($gb && strpos($gb, 'fatal:') === false && strpos($gb, 'not recognized') === false) {
            $gitBranch = trim($gb);
        }
        $gc = @shell_exec('git -C ' . escapeshellarg($repoRoot) . ' log -1 --format="%h (%ar)" 2>&1');
        if ($gc && strpos($gc, 'fatal:') === false && strpos($gc, 'not recognized') === false) {
            $gitCommit = trim($gc);
        }
    } catch (Throwable $e) {
        // Silently preserve file-based version
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Portal Settings & Configuration</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Configure platform branding, official social media handles, contact details, and disclaimers.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="github_sync.php?action=update" class="btn btn-primary btn-sm" onclick="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git?');" style="display: inline-flex; align-items: center; gap: 0.4rem; background: var(--brand-red); border-color: var(--brand-red);">
            <i class="fas fa-cloud-arrow-down"></i> Update from GitHub
        </a>
        <a href="github_sync.php?action=fetch" class="btn btn-dark btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fab fa-github"></i> Fetch Only
        </a>
        <a href="github_sync.php" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fas fa-code-branch"></i> Git Console
        </a>
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

        <!-- System Information & Git -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- GitHub Repository & Updates Card -->
            <div class="admin-card" style="border-top: 4px solid #24292e;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <h3 class="admin-card-title" style="margin: 0; font-size: 1.05rem;">
                        <i class="fab fa-github" style="color: #24292e;"></i> GitHub Repository
                    </h3>
                    <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 700; font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                        <i class="fas fa-code-branch"></i> <?= htmlspecialchars($gitBranch) ?>
                    </span>
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.85rem; margin-bottom: 1.25rem;">
                    <div>
                        <span style="color: var(--text-muted); font-size: 0.75rem;">Repository:</span><br>
                        <strong>kgauravindia/myadvocate</strong>
                    </div>
                    <?php if ($gitCommit): ?>
                        <div>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">Current Local Commit:</span><br>
                            <code style="font-size: 0.75rem; background: var(--bg-alt); padding: 0.15rem 0.4rem; border-radius: 3px; color: var(--brand-red); font-weight: 700;"><?= htmlspecialchars($gitCommit) ?></code>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="github_sync.php?action=update" class="btn btn-primary btn-sm w-100" onclick="return confirm('Update from https://github.com/kgauravindia/myadvocate.git?');" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; background: var(--brand-red); border-color: var(--brand-red);">
                        <i class="fas fa-cloud-arrow-down"></i> Update from GitHub
                    </a>
                    <a href="github_sync.php?action=fetch" class="btn btn-dark btn-sm w-100" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                        <i class="fas fa-arrows-rotate"></i> Fetch References
                    </a>
                    <a href="github_sync.php" class="btn btn-outline btn-sm w-100" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                        <i class="fab fa-github"></i> Open Git Sync Console
                    </a>
                </div>
            </div>

            <div class="admin-card" style="border-top: 4px solid var(--brand-gold);">
                <h3 class="admin-card-title" style="margin-bottom: 1rem;"><i class="fas fa-server"></i> System Information</h3>
                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.875rem;">
                    <div>
                        <span style="color: var(--text-muted);">PHP Version:</span>
                        <strong><?= phpversion() ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Database:</span>
                        <strong>MySQL (<?= defined('DB_NAME') ? DB_NAME : 'u305984835_myadvocate' ?>)</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">Server Environment:</span>
                        <strong><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? (PHP_OS_FAMILY === 'Windows' ? 'Apache / Windows' : 'Apache / Linux')) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted);">App Version:</span>
                        <strong><?= defined('APP_VERSION') ? APP_VERSION : '2.2.8' ?></strong>
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
