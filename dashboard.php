<?php
// dashboard.php - Advocate Profile Management & Handle Claim Dashboard
require_once __DIR__ . '/config/app.php';

if (empty($_SESSION['advocate_id'])) {
    header("Location: login");
    exit;
}

$pageTitle = "Advocate Dashboard - My Advocate";
$db = getDB();

$advId = (int)$_SESSION['advocate_id'];
$advocate = null;
$msg = '';
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? 'update_profile');

    if ($action === 'claim_handle') {
        $rawHandle = trim($_POST['handle'] ?? '');
        // Clean handle: lowercase, only alphanumeric, hyphen, dot, and underscore
        $cleanHandle = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', $rawHandle));
        $cleanHandle = trim($cleanHandle, '._-');

        $reservedHandles = ['admin', 'api', 'login', 'register', 'dashboard', 'profile', 'acts', 'courts', 'tools', 'aibe', 'about', 'terms', 'privacy', 'disclaimer', 'contact', 'services', 'pricing', 'faq', 'video', 'college', 'law-college', 'bare-acts'];

        if (empty($cleanHandle) || strlen($cleanHandle) < 3 || strlen($cleanHandle) > 40) {
            $error = "Handle must be between 3 and 40 characters long and can contain letters, numbers, hyphens, and underscores.";
        } elseif (in_array($cleanHandle, $reservedHandles)) {
            $error = "The handle '@" . htmlspecialchars($cleanHandle) . "' is reserved by the system. Please choose a different handle.";
        } else {
            try {
                // Check if another advocate has already claimed this handle
                $chkStmt = $db->prepare("SELECT id FROM advocate WHERE public_url = ? AND id != ? LIMIT 1");
                $chkStmt->execute([$cleanHandle, $advId]);
                if ($chkStmt->fetch()) {
                    $error = "The handle '@" . htmlspecialchars($cleanHandle) . "' is already claimed by another advocate. Please try another handle.";
                } else {
                    $upStmt = $db->prepare("UPDATE advocate SET public_url = ?, updated_at = NOW() WHERE id = ?");
                    $upStmt->execute([$cleanHandle, $advId]);
                    $msg = "Congratulations! Your handle '@" . htmlspecialchars($cleanHandle) . "' has been claimed successfully.";
                    
                    // Refresh data
                    $stmt->execute([$advId]);
                    $advocate = $stmt->fetch();
                }
            } catch (Exception $e) {
                $error = "Could not save handle. Please try again.";
            }
        }
    } else {
        // General profile update
        $mobileVis = sanitize($_POST['mobile_visibility'] ?? 'REGISTERED');
        $emailVis = sanitize($_POST['email_visibility'] ?? 'REGISTERED');
        $addrVis = sanitize($_POST['address_visibility'] ?? 'PRIVATE');
        $practiceArea = sanitize($_POST['practice_area'] ?? '');
        $about = sanitize($_POST['about'] ?? '');

        try {
            $updateStmt = $db->prepare("UPDATE advocate SET mobile_visibility = ?, email_visibility = ?, address_visibility = ?, practice_area = ?, about = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([$mobileVis, $emailVis, $addrVis, $practiceArea, $about, $advId]);
            $msg = "Profile details and privacy settings successfully saved.";
            
            // Refresh advocate data
            $stmt->execute([$advId]);
            $advocate = $stmt->fetch();
        } catch (Exception $e) {
            $error = "Error saving profile changes.";
        }
    }
}

$currentHandle = trim($advocate['public_url'] ?? '');
$defaultSlug = generateAdvocateSlug($advocate);
$publicProfileUrl = getAdvocateUrl($advocate);
$handleUrl = $currentHandle ? "https://myadv.in/profile.php?link=public=" . urlencode($currentHandle) : "https://myadv.in/profile.php?id=" . $advocate['id'];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <span>Dashboard</span> &bull; 
        <span><?= sanitize($advocate['name']) ?></span>
    </nav>

    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #fee2e2; color: var(--brand-red); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">
                <i class="fas fa-id-card"></i> Enr: <?= sanitize($advocate['e_no']) ?>/<?= sanitize($advocate['e_year']) ?>
            </div>
            <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.25rem;">
                Advocate Dashboard
            </h1>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 0;">
                Welcome back, <strong><?= sanitize($advocate['name']) ?></strong>! Manage your custom public URL, profile details, and privacy.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= $publicProfileUrl ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> View Public Profile
            </a>
            <a href="logout.php" class="btn btn-outline btn-sm">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
        <div class="stat-box" style="margin-bottom: 1.5rem; background: #f0fdf4; border-color: #86efac; color: #166534; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-circle-check" style="color: #16a34a; font-size: 1.1rem;"></i>
            <span><?= sanitize($msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="stat-box" style="margin-bottom: 1.5rem; background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-triangle-exclamation" style="font-size: 1.1rem;"></i>
            <span><?= sanitize($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- 1. CLAIM PUBLIC URL HERO CARD -->
    <div class="stat-box" style="padding: 1.75rem 2rem; margin-bottom: 2rem; border-top: 4px solid var(--brand-gold); background: linear-gradient(135deg, rgba(255, 251, 235, 0.7) 0%, rgba(254, 242, 242, 0.7) 100%);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            <div style="max-width: 600px;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                        <i class="fas fa-link"></i> Public Profile URL
                    </span>
                    <?php if ($currentHandle): ?>
                        <span style="font-size: 0.8125rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm);">
                            <i class="fas fa-check-circle"></i> Active: <?= sanitize($currentHandle) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
                    <?= $currentHandle ? 'Your Custom Public URL' : 'Claim Your Unique Public Profile URL' ?>
                </h2>
                <p style="color: var(--text-main); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
                    Your custom public URL gives you a clean personal link (<code>profile.php?link=public=<?= sanitize($currentHandle ?: 'yourname') ?></code>) to share on business cards, emails, and client communications.
                </p>

                <?php if ($currentHandle): ?>
                    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="font-family: monospace; font-size: 0.92rem; font-weight: 700; color: var(--primary); word-break: break-all;">
                            <span style="color: var(--text-muted);">https://myadv.in/profile.php?link=public=</span><span style="color: var(--brand-red);"><?= sanitize($currentHandle) ?></span>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="https://myadv.in/profile.php?link=public=<?= sanitize($currentHandle) ?>" title="Copy Profile Link">
                                <i class="fas fa-link"></i> Copy Link
                            </button>
                            <a href="<?= $publicProfileUrl ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-up-right-from-square"></i> Open
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Claim / Edit Form -->
                <form action="dashboard" method="POST" style="margin-top: 0.75rem;">
                    <input type="hidden" name="action" value="claim_handle">
                    <div style="display: flex; gap: 0.5rem; max-width: 480px; flex-wrap: wrap;">
                        <div style="position: relative; flex: 1; min-width: 220px;">
                            <input type="text" name="handle" class="filter-input" value="<?= sanitize($currentHandle ?: $defaultSlug) ?>" placeholder="yourname (e.g. kumargaurav)" required pattern="[a-zA-Z0-9._-]{3,40}" style="font-weight: 600;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-md" style="font-weight: 700;">
                            <i class="fas fa-badge-check"></i> <?= $currentHandle ? 'Update URL' : 'Claim URL' ?>
                        </button>
                    </div>
                    <small style="display: block; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.75rem;">
                        Letters, numbers, hyphens, underscores (3-40 chars). E.g., <code>kumargaurav</code>, <code>adv-rajesh</code>
                    </small>
                </form>
            </div>

            <!-- Share Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; min-width: 240px; text-align: center;">
                <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.75rem;">
                    Quick Share Profile
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php $shareLink = $currentHandle ? "https://myadv.in/profile.php?link=public=" . urlencode($currentHandle) : "https://myadv.in/profile.php?id=" . $advocate['id']; ?>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Connect with Advocate " . $advocate['name'] . " on My Advocate: " . $shareLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="width: 100%; justify-content: center; color: #16a34a; border-color: #86efac;">
                        <i class="fab fa-whatsapp"></i> Share on WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= sanitize($shareLink) ?>" style="width: 100%; justify-content: center;">
                        <i class="fas fa-copy"></i> Copy Profile Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MAIN DASHBOARD CONTENT GRID -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;" class="dash-grid">
        <style>
            @media(max-width: 991px) {
                .dash-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>

        <!-- Left Column: Edit Form -->
        <div class="stat-box" style="padding: 2rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-user-pen" style="color: var(--brand-red);"></i> Update Profile Information
            </h3>
            
            <form action="dashboard" method="POST">
                <input type="hidden" name="action" value="update_profile">

                <!-- Practice Areas -->
                <div class="filter-group" style="margin-bottom: 1.25rem;">
                    <label class="filter-label" style="font-weight: 600;">Practice Areas &amp; Specializations (comma separated)</label>
                    <input type="text" name="practice_area" class="filter-input" value="<?= sanitize($advocate['practice_area'] ?? '') ?>" placeholder="e.g. Civil Law, Property Disputes, Criminal Defense, Family Law, Corporate Arbitration">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Adding multiple practice areas improves your search discovery by clients.</small>
                </div>

                <!-- Professional Summary -->
                <div class="filter-group" style="margin-bottom: 1.5rem;">
                    <label class="filter-label" style="font-weight: 600;">Professional Summary &amp; Chambers Bio</label>
                    <textarea name="about" class="filter-input" rows="5" placeholder="Describe your legal experience, notable courts of appearance, chambers, and areas of practice..."><?= sanitize($advocate['about'] ?? '') ?></textarea>
                </div>

                <!-- Privacy Settings -->
                <h4 style="font-size: 1.1rem; font-weight: 700; margin: 1.5rem 0 1rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-shield-halved" style="color: var(--brand-gold-dark);"></i> Contact Privacy &amp; Visibility Settings
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div>
                        <label class="filter-label" style="font-weight: 600;">Mobile Number Visibility</label>
                        <select name="mobile_visibility" class="filter-select">
                            <option value="PUBLIC" <?= ($advocate['mobile_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public (Visible to All)</option>
                            <option value="REGISTERED" <?= ($advocate['mobile_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Masked / Registered Members Only</option>
                            <option value="PRIVATE" <?= ($advocate['mobile_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden / Private</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" style="font-weight: 600;">Email Visibility</label>
                        <select name="email_visibility" class="filter-select">
                            <option value="PUBLIC" <?= ($advocate['email_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public (Visible to All)</option>
                            <option value="REGISTERED" <?= ($advocate['email_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Masked / Registered Members Only</option>
                            <option value="PRIVATE" <?= ($advocate['email_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden / Private</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label" style="font-weight: 600;">Chamber Address Visibility</label>
                        <select name="address_visibility" class="filter-select">
                            <option value="PUBLIC" <?= ($advocate['address_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                            <option value="PRIVATE" <?= ($advocate['address_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Private</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-md" style="font-weight: 700;">
                    <i class="fas fa-floppy-disk"></i> Save Profile &amp; Privacy
                </button>
            </form>
        </div>

        <!-- Right Column: Sidebar Info -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Credentials Box -->
            <div class="stat-box" style="padding: 1.5rem;">
                <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--primary);">
                    <i class="fas fa-certificate text-primary"></i> Enrollment Credentials
                </h4>
                <div style="margin-bottom: 0.75rem;">
                    <span class="badge-verification badge-verified" style="font-size: 0.8125rem;">
                        <i class="fas fa-id-card"></i> <?= sanitize($advocate['e_no']) ?> / <?= sanitize($advocate['e_year']) ?>
                    </span>
                </div>
                <div style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6;">
                    <div>State Bar Council: <strong><?= sanitize(getStateName($advocate['state_code'] ?? '')) ?></strong></div>
                    <div>Court: <strong><?= sanitize(getCourtsList()[$advocate['court'] ?? 'CC'] ?? 'Civil & District Court') ?></strong></div>
                </div>
            </div>

            <!-- Membership Tier Box -->
            <div class="stat-box" style="padding: 1.5rem; border-top: 3px solid var(--brand-red);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <h4 style="font-size: 1rem; font-weight: 700; color: var(--primary); margin: 0;">Membership Plan</h4>
                    <span class="badge-verification badge-verified" style="text-transform: uppercase;">
                        <?= sanitize($advocate['plan_type'] ?: 'Registered') ?>
                    </span>
                </div>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Upgrade to Premium Verification to unlock verified badges, prioritized directory placement, and unlimited practice area listings.
                </p>
                <a href="pricing" class="btn btn-outline-primary btn-sm" style="width: 100%; justify-content: center;">
                    <i class="fas fa-shield-check"></i> View Verification Plans
                </a>
            </div>

            <!-- Profile Tip Box -->
            <div class="stat-box" style="background: #eff6ff; border-color: #bfdbfe; padding: 1.25rem;">
                <h4 style="font-size: 0.95rem; color: #1e3a8a; font-weight: 700; margin-bottom: 0.5rem;">
                    <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Pro Tip
                </h4>
                <p style="font-size: 0.8125rem; color: #1e40af; line-height: 1.5; margin-bottom: 0;">
                    Your custom handle can be used anywhere: <strong>myadv.in/@<?= sanitize($currentHandle ?: 'yourname') ?></strong>. Share it with clients for quick verification.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
