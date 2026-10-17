<?php
// admin/index.php - Comprehensive Admin Dashboard
$pageTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$msg = '';
$err = '';

// Handle Direct Verification Action from Dashboard
$action = sanitize($_GET['action'] ?? '');
$actId = (int)($_GET['id'] ?? 0);

if ($actId > 0 && $action === 'verify') {
    try {
        $up = $db->prepare("UPDATE advocate SET type = 'ACTIVE', plan_type = 'verified', status = 'ACTIVE', updated_at = NOW() WHERE id = ?");
        $up->execute([$actId]);
        recordAdvocateProfileUpdate($actId, ['type' => 'Manually verified and approved via dashboard'], 'admin');

        if (function_exists('sendAdvocateProfileApprovedSMS')) {
            @sendAdvocateProfileApprovedSMS($actId);
        }
        $msg = "Advocate #{$actId} has been successfully verified & approved! Profile is now active and confirmation SMS sent.";
    } catch (Exception $e) {
        $err = "Action failed: " . $e->getMessage();
    }
}

// Fetch Global Statistics
$totalAdvocates = 0;
$pendingAdvocates = 0;
$activeAdvocates = 0;
$verifiedAdvocates = 0;
$totalActs = 0;
$totalNotices = 0;
$totalColleges = 0;
$totalBarAssns = 0;
$totalHighCourts = 0;
$pendingGrievances = 0;
$pendingChangeRequests = 0;

try {
    $totalAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate")->fetchColumn();
    $pendingAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type = 'PENDING'")->fetchColumn();
    $activeAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type = 'ACTIVE' OR type = 'VERIFIED' OR status = 'ACTIVE'")->fetchColumn();
    $verifiedAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE (plan_type = 'verified' OR premium_member = 1 OR type = 'VERIFIED') AND status != 'BLOCK'")->fetchColumn();
    $totalActs = (int)$db->query("SELECT COUNT(*) FROM acts WHERE status = 'ACTIVE'")->fetchColumn();
    $totalNotices = (int)$db->query("SELECT COUNT(*) FROM notice WHERE status = 'ACTIVE'")->fetchColumn();
    $totalColleges = (int)$db->query("SELECT COUNT(*) FROM college WHERE (status = 'ACTIVE' OR status = '' OR status IS NULL) AND name != '' AND status != 'AUTO'")->fetchColumn();
    $totalBarAssns = (int)$db->query("SELECT COUNT(*) FROM ba WHERE status = 'ACTIVE'")->fetchColumn();
    $totalHighCourts = (int)$db->query("SELECT COUNT(*) FROM hc WHERE status = 'ACTIVE'")->fetchColumn();
    $pendingGrievances = (int)$db->query("SELECT COUNT(*) FROM contact WHERE status = 'PENDING' OR status = 'NEW' OR status IS NULL OR status = ''")->fetchColumn();
    $pendingChangeRequests = (int)$db->query("SELECT COUNT(*) FROM advocate_change_requests WHERE status = 'PENDING'")->fetchColumn();
} catch (Exception $e) {}

// Fetch Advocates for Dashboard Hub
$pendingAdvocatesList = [];
$activeAdvocatesList = [];
$recentAdvocatesList = [];

try {
    // 1. Pending Advocates (Require document / bar verification)
    $stmtPending = $db->query("
        SELECT a.*, bc.url_1 AS bc_verify_url, bc.name AS bc_name, bc.code AS bc_code 
        FROM advocate a 
        LEFT JOIN bc ON (bc.id = a.bc_id OR (a.bc_id IS NULL AND bc.state_code = a.state_code)) 
        WHERE a.type = 'PENDING' 
        ORDER BY a.id DESC 
        LIMIT 8
    ");
    $pendingAdvocatesList = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

    // 2. Active / Approved Advocates
    $stmtActive = $db->query("
        SELECT a.*, bc.url_1 AS bc_verify_url, bc.name AS bc_name, bc.code AS bc_code 
        FROM advocate a 
        LEFT JOIN bc ON (bc.id = a.bc_id OR (a.bc_id IS NULL AND bc.state_code = a.state_code)) 
        WHERE (a.type = 'ACTIVE' OR a.type = 'VERIFIED' OR a.status = 'ACTIVE') 
        ORDER BY a.id DESC 
        LIMIT 8
    ");
    $activeAdvocatesList = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

    // 3. Recent General Registrations
    $stmtRecent = $db->query("
        SELECT a.*, bc.url_1 AS bc_verify_url, bc.name AS bc_name, bc.code AS bc_code 
        FROM advocate a 
        LEFT JOIN bc ON (bc.id = a.bc_id OR (a.bc_id IS NULL AND bc.state_code = a.state_code)) 
        ORDER BY a.id DESC 
        LIMIT 8
    ");
    $recentAdvocatesList = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Recent Grievances / Inquiries
$recentContacts = [];
try {
    $stmt = $db->query("SELECT id, name, email, mobile, subject, message, status, created_at FROM contact ORDER BY id DESC LIMIT 6");
    $recentContacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>

<!-- Alerts -->
<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success" style="margin-bottom: 1.5rem;">
        <i class="fas fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>
<?php if ($err): ?>
    <div class="alert-admin alert-admin-error" style="margin-bottom: 1.5rem;">
        <i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?>
    </div>
<?php endif; ?>

<!-- High-Priority Pending Verification Alert Banner (Shows when pending records exist) -->
<?php if ($pendingAdvocates > 0): ?>
    <div style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; border-left: 5px solid #f59e0b; border-radius: 12px; padding: 1.15rem 1.5rem; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.08);">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: #fef3c7; border: 2px solid #f59e0b; display: flex; align-items: center; justify-content: center; color: #b45309; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-user-clock"></i>
            </div>
            <div>
                <h4 style="margin: 0 0 0.2rem; font-size: 1.05rem; font-weight: 800; color: #92400e; font-family: var(--font-heading);">
                    Action Required: <?= number_format($pendingAdvocates) ?> Advocate<?= $pendingAdvocates > 1 ? 's' : '' ?> Pending Verification
                </h4>
                <p style="margin: 0; font-size: 0.85rem; color: #b45309;">
                    Review uploaded Bar Council ID proofs, verify against official state bar records, or run bulk SMS updates.
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap;">
            <a href="advocates.php?type=PENDING" class="btn btn-primary btn-sm" style="background: #d97706; border-color: #d97706; font-weight: 700; padding: 0.45rem 1rem;">
                <i class="fas fa-tasks"></i> Review & Bulk Action &rarr;
            </a>
            <button type="button" onclick="document.getElementById('advocateHubCard').scrollIntoView({behavior: 'smooth'}); switchAdvTab('pending');" class="btn btn-outline btn-sm" style="background: #ffffff; border-color: #fcd34d; color: #92400e; font-weight: 700;">
                <i class="fas fa-arrow-down"></i> Review Below
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Primary Advocate Status KPI Grid -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); margin-bottom: 1.5rem; gap: 1.25rem;">
    <!-- Pending Advocates Card -->
    <a href="advocates.php?type=PENDING" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-box" style="border-left: 5px solid #f59e0b; background: <?= $pendingAdvocates > 0 ? '#fffdf7' : '#ffffff' ?>; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(245, 158, 11, 0.15)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <div class="stat-label" style="font-weight: 700; color: #b45309;">
                    <i class="fas fa-user-clock" style="color: #f59e0b; margin-right: 0.4rem;"></i> Pending Advocates
                </div>
                <span class="badge" style="background: #fef3c7; color: #b45309; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">
                    <?= $pendingAdvocates > 0 ? 'Needs Action' : 'All Clear' ?>
                </span>
            </div>
            <div class="stat-value" style="font-size: 2.1rem; color: #b45309; font-family: var(--font-heading); font-weight: 800;">
                <?= number_format($pendingAdvocates) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Awaiting document review</span>
                <span style="color: #b45309; font-weight: 700;">Manage &rarr;</span>
            </div>
        </div>
    </a>

    <!-- Active Advocates Card -->
    <a href="advocates.php?type=ACTIVE" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-box" style="border-left: 5px solid #10b981; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.15)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <div class="stat-label" style="font-weight: 700; color: #047857;">
                    <i class="fas fa-user-check" style="color: #10b981; margin-right: 0.4rem;"></i> Active Advocates
                </div>
                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">
                    Live Directory
                </span>
            </div>
            <div class="stat-value" style="font-size: 2.1rem; color: #047857; font-family: var(--font-heading); font-weight: 800;">
                <?= number_format($activeAdvocates) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Approved & searchable</span>
                <span style="color: #047857; font-weight: 700;">View list &rarr;</span>
            </div>
        </div>
    </a>

    <!-- Verified Tier Advocates Card -->
    <a href="advocates.php?plan=verified" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-box" style="border-left: 5px solid var(--brand-gold); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(217, 119, 6, 0.15)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <div class="stat-label" style="font-weight: 700; color: var(--brand-gold-dark);">
                    <i class="fas fa-shield-halved" style="color: var(--brand-gold-dark); margin-right: 0.4rem;"></i> Verified Tier
                </div>
                <span class="badge" style="background: #fef3c7; color: #92400e; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">
                    Authentic
                </span>
            </div>
            <div class="stat-value" style="font-size: 2.1rem; color: var(--brand-gold-dark); font-family: var(--font-heading); font-weight: 800;">
                <?= number_format($verifiedAdvocates) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                <span>Bar Council Verified Tier</span>
                <span style="color: var(--brand-gold-dark); font-weight: 700;">Explore &rarr;</span>
            </div>
        </div>
    </a>

    <!-- Total Advocates Database Card -->
    <a href="advocates.php" style="text-decoration: none; color: inherit; display: block;">
        <div class="stat-box" style="border-left: 5px solid var(--brand-red); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 25px rgba(185, 28, 28, 0.15)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                <div class="stat-label" style="font-weight: 700; color: var(--brand-red);">
                    <i class="fas fa-users" style="color: var(--brand-red); margin-right: 0.4rem;"></i> Total Advocates
                </div>
                <span class="badge" style="background: #fee2e2; color: #991b1b; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 9999px;">
                    National Index
                </span>
            </div>
            <div class="stat-value" style="font-size: 2.1rem; color: var(--brand-red); font-family: var(--font-heading); font-weight: 800;">
                <?= number_format($totalAdvocates) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                <span>All registrations & records</span>
                <span style="color: var(--brand-red); font-weight: 700;">Directory &rarr;</span>
            </div>
        </div>
    </a>
</div>

<!-- Secondary Statistics Grid -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 1.75rem; gap: 1rem;">
    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid var(--primary);">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-book-bookmark" style="color: var(--primary);"></i> Bare Acts</div>
        <div class="stat-value" style="font-size: 1.45rem; color: var(--primary);"><?= number_format($totalActs) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">BNS, BNSS, BSA & Acts</small>
    </div>

    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid #6366f1;">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-graduation-cap" style="color: #6366f1;"></i> Law Colleges</div>
        <div class="stat-value" style="font-size: 1.45rem; color: #6366f1;"><?= number_format($totalColleges) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Universities & Institutes</small>
    </div>

    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid #8b5cf6;">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-landmark" style="color: #8b5cf6;"></i> Bar Associations</div>
        <div class="stat-value" style="font-size: 1.45rem; color: #8b5cf6;"><?= number_format($totalBarAssns) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">Pan-India Associations</small>
    </div>

    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid #ec4899;">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-gavel" style="color: #ec4899;"></i> High Courts</div>
        <div class="stat-value" style="font-size: 1.45rem; color: #ec4899;"><?= number_format($totalHighCourts) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;">State High Courts</small>
    </div>

    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid #ef4444;">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-headset" style="color: #ef4444;"></i> Open Grievances</div>
        <div class="stat-value" style="font-size: 1.45rem; color: #ef4444;"><?= number_format($pendingGrievances) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;"><a href="contacts.php" style="color: #ef4444; text-decoration: none; font-weight: 600;">Pending Inquiries &rarr;</a></small>
    </div>

    <div class="stat-box" style="padding: 1rem 1.15rem; border-left: 3px solid #f97316;">
        <div class="stat-label" style="font-size: 0.78rem;"><i class="fas fa-clipboard-check" style="color: #f97316;"></i> Change Requests</div>
        <div class="stat-value" style="font-size: 1.45rem; color: #f97316;"><?= number_format($pendingChangeRequests) ?></div>
        <small style="color: var(--text-muted); font-size: 0.72rem;"><a href="change_requests.php?status=PENDING" style="color: #f97316; text-decoration: none; font-weight: 600;">Pending review &rarr;</a></small>
    </div>
</div>

<!-- Quick Navigation & Direct Search Bar -->
<div class="admin-card" style="border-left: 4px solid var(--brand-red); margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h3 style="font-size: 1.15rem; margin-bottom: 0.25rem; color: var(--primary); font-family: var(--font-heading); font-weight: 800;">
                <i class="fas fa-bolt" style="color: var(--brand-gold);"></i> Quick Actions & Directory Search
            </h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">Perform immediate administrative updates or locate records across the national database.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="advocates.php?type=PENDING" class="btn btn-warning btn-sm" style="background: #f59e0b; border-color: #f59e0b; color: #ffffff; font-weight: 700;">
                <i class="fas fa-user-clock"></i> Pending (<?= number_format($pendingAdvocates) ?>)
            </a>
            <a href="advocate_add.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add Advocate</a>
            <a href="acts.php?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus-circle"></i> Add Bare Act</a>
            <a href="notices.php?action=add" class="btn btn-dark btn-sm"><i class="fas fa-bullhorn"></i> Post Notice</a>
            <a href="github_sync.php?action=update" class="btn btn-primary btn-sm" onclick="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git?');" style="background: var(--brand-red); border-color: var(--brand-red);">
                <i class="fas fa-cloud-arrow-down"></i> Sync GitHub
            </a>
        </div>
    </div>

    <!-- Quick Search Field -->
    <form action="advocates.php" method="GET" style="margin-top: 1.25rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="text" name="q" class="filter-input" placeholder="Search advocate by Name, Mobile, or Enrollment Number (e.g. D/1234/2021)..." style="flex: 1; min-width: 250px;">
        <select name="type" class="filter-select" style="max-width: 170px;">
            <option value="">All Statuses</option>
            <option value="PENDING" <?= $pendingAdvocates > 0 ? 'selected' : '' ?>>Pending Verification</option>
            <option value="ACTIVE">Active Only</option>
            <option value="VERIFIED">Verified Only</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search Directory</button>
    </form>

    <!-- AIS Modular Hub -->
    <div style="margin-top: 1.25rem; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
        <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem; letter-spacing: 0.05em;">
            <i class="fas fa-cubes-stacked text-primary me-1"></i> Core Directory Modules
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.65rem;">
            <a href="advocate_upload.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-file-csv text-primary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Bulk CSV Import</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">Batch Advocates</small>
                </div>
            </a>
            <a href="advocate_reports.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-chart-line text-success"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Advocate Reports</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">State / District</small>
                </div>
            </a>
            <a href="bc.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-scale-balanced text-warning"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Bar Councils</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">26 Official Portals</small>
                </div>
            </a>
            <a href="ba.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-building-columns text-info"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Bar Associations</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">344+ Associations</small>
                </div>
            </a>
            <a href="hc.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-landmark text-primary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">High Courts</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">26 State High Courts</small>
                </div>
            </a>
            <a href="change_requests.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-clipboard-check text-danger"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Profile Changes</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);"><?= $pendingChangeRequests ?> Pending</small>
                </div>
            </a>
            <a href="questions.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-clipboard-question text-danger"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Questions Bank</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">837+ AIBE MCQs</small>
                </div>
            </a>
            <a href="universities.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-university text-secondary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">Universities</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">994+ Universities</small>
                </div>
            </a>
            <a href="sitemap_manager.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fas fa-sitemap text-dark"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">XML Sitemaps</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">SEO Indexing</small>
                </div>
            </a>
            <a href="github_sync.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.5rem; text-align: left; padding: 0.55rem 0.75rem;">
                <i class="fab fa-github text-dark"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.78rem; line-height: 1.1;">GitHub Sync</div>
                    <small style="font-size: 0.68rem; color: var(--text-muted);">Live Code Updates</small>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Main Split: Interactive Advocate Hub & Recent Grievances -->
<div style="display: grid; grid-template-columns: 1fr; gap: 1.75rem;" class="admin-dash-split">
    <style>
        @media(min-width: 1024px) {
            .admin-dash-split {
                grid-template-columns: 2fr 1fr;
            }
        }
        .adv-tab-btn {
            background: none;
            border: none;
            padding: 0.5rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 700;
            border-radius: 9999px;
            cursor: pointer;
            color: var(--text-muted);
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .adv-tab-btn:hover {
            color: var(--primary);
            background: #f1f5f9;
        }
        .adv-tab-btn.active-tab {
            background: var(--primary);
            color: #ffffff;
        }
        .adv-tab-btn.active-tab-pending {
            background: #f59e0b;
            color: #ffffff;
        }
        .adv-tab-btn.active-tab-active {
            background: #10b981;
            color: #ffffff;
        }
    </style>

    <!-- Left: Interactive Advocate Hub -->
    <div class="admin-card" id="advocateHubCard" style="margin-bottom: 0;">
        <div class="admin-card-header" style="flex-direction: column; align-items: flex-start; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 0.5rem;">
                <h3 class="admin-card-title" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-users-gear" style="color: var(--brand-red);"></i> Advocate Verification & Activity Hub
                </h3>
                <a id="viewAllAdvLink" href="advocates.php<?= $pendingAdvocates > 0 ? '?type=PENDING' : '' ?>" class="btn btn-outline btn-sm" style="font-size: 0.75rem; font-weight: 700;">
                    Manage All in Directory &rarr;
                </a>
            </div>

            <!-- Tab Switcher -->
            <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; width: 100%; padding-top: 0.25rem;">
                <button type="button" class="adv-tab-btn <?= $pendingAdvocates > 0 ? 'active-tab-pending' : '' ?>" id="tabBtn_pending" onclick="switchAdvTab('pending')">
                    <i class="fas fa-clock"></i> Pending Verification (<?= number_format($pendingAdvocates) ?>)
                </button>
                <button type="button" class="adv-tab-btn <?= $pendingAdvocates == 0 ? 'active-tab-active' : '' ?>" id="tabBtn_active" onclick="switchAdvTab('active')">
                    <i class="fas fa-check-circle"></i> Active Advocates (<?= number_format($activeAdvocates) ?>)
                </button>
                <button type="button" class="adv-tab-btn" id="tabBtn_recent" onclick="switchAdvTab('recent')">
                    <i class="fas fa-list"></i> All Recent Records
                </button>
            </div>
        </div>

        <!-- 1. PENDING ADVOCATES TAB CONTENT -->
        <div id="tabContent_pending" style="display: <?= $pendingAdvocates > 0 ? 'block' : 'none' ?>;">
            <?php if (!empty($pendingAdvocatesList)): ?>
                <div style="background: #fffdf5; border-bottom: 1px solid #fef3c7; padding: 0.65rem 1rem; font-size: 0.8rem; color: #b45309; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <span><i class="fas fa-shield-halved"></i> <strong>Pending Bar Council Verification:</strong> Review documents and approve with 1-click SMS dispatch.</span>
                    <a href="advocates.php?type=PENDING" style="color: #b45309; font-weight: 700; text-decoration: underline;">Bulk Verify All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Advocate / Contact</th>
                                <th>Enrollment & Bar Council</th>
                                <th>State / District</th>
                                <th>ID Proof</th>
                                <th style="text-align: right;">Quick Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingAdvocatesList as $adv): 
                                $rawProof = trim((string)($adv['id_proof'] ?? ''));
                                $hasProof = (!empty($rawProof) && $rawProof !== '0' && strtolower($rawProof) !== 'null');
                                $proofUrl = $hasProof ? getAdvocateIdProofUrl($rawProof) : '';
                                $fullProofUrl = (strpos($proofUrl, 'http') === 0) ? $proofUrl : '../' . ltrim($proofUrl, '/');
                                $isPdf = (bool)preg_match('/\.pdf$/i', $rawProof);
                            ?>
                                <tr style="background: #fffdf5;">
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary);">
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" style="color: inherit; text-decoration: none;">
                                                <?= sanitize($adv['name'] ?: 'Unnamed Advocate') ?>
                                            </a>
                                        </div>
                                        <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size: 0.7rem;"></i> <?= sanitize($adv['mobile'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($adv['e_no'] ?: '—') ?></strong><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>
                                        <div style="margin-top: 3px;">
                                            <?php if (!empty($adv['bc_verify_url'])): ?>
                                                <a href="<?= sanitize($adv['bc_verify_url']) ?>" target="_blank" rel="noopener noreferrer" class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-size: 0.65rem; text-decoration: none; padding: 2px 5px; display: inline-flex; align-items: center; gap: 3px; font-weight: 700;" title="Verify on <?= sanitize($adv['bc_code'] ?? 'Bar Council') ?> Official Portal (opens in new tab)">
                                                    <i class="fas fa-shield-halved"></i> Verify BC <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.55rem;"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.65rem; padding: 2px 5px;">
                                                    <?= sanitize($adv['state_code'] ?? 'No BC link') ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?: '—' ?></div>
                                        <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($hasProof): ?>
                                            <button type="button" class="badge" onclick="openDashProofModal('<?= sanitize($fullProofUrl) ?>', '<?= sanitize(addslashes($adv['name'] ?: 'Advocate')) ?>', '<?= sanitize(addslashes($adv['e_no'] ?: '')) ?><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>', <?= $isPdf ? 'true' : 'false' ?>)" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.68rem; padding: 3px 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-weight: 700;" title="Quick Preview ID Proof Document">
                                                <i class="fas <?= $isPdf ? 'fa-file-pdf' : 'fa-id-card' ?>"></i> View Proof
                                            </button>
                                        <?php else: ?>
                                            <span style="font-size: 0.75rem; color: #94a3b8;"><i class="fas fa-circle-minus"></i> No Upload</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                            <a href="index.php?action=verify&id=<?= $adv['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('Verify and approve advocate <?= htmlspecialchars(addslashes($adv['name'] ?: '')) ?>?\n\nThis will activate their profile and send an SMS confirmation.');" style="background: #059669; border-color: #059669; font-weight: 700; padding: 0.25rem 0.55rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.3rem;" title="Approve & Send SMS">
                                                <i class="fas fa-circle-check"></i> Approve
                                            </a>
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Edit Advocate">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Login as Advocate">
                                                <i class="fas fa-right-to-bracket"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 0.85rem 1rem; text-align: center; background: #fafafa; border-top: 1px solid var(--border-color);">
                    <a href="advocates.php?type=PENDING" class="btn btn-warning btn-sm" style="background: #f59e0b; border-color: #f59e0b; color: #fff; font-weight: 700;">
                        <i class="fas fa-tasks"></i> View All <?= number_format($pendingAdvocates) ?> Pending Advocates &rarr;
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    <i class="fas fa-circle-check" style="font-size: 2.5rem; color: #10b981; margin-bottom: 0.75rem;"></i>
                    <h4 style="margin: 0 0 0.25rem; color: var(--primary);">All Caught Up!</h4>
                    <p style="margin: 0; font-size: 0.875rem;">There are no advocates pending manual verification.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. ACTIVE ADVOCATES TAB CONTENT -->
        <div id="tabContent_active" style="display: <?= $pendingAdvocates == 0 ? 'block' : 'none' ?>;">
            <?php if (!empty($activeAdvocatesList)): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Advocate</th>
                                <th>Enrollment No</th>
                                <th>State / District</th>
                                <th>Badge Tier</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeAdvocatesList as $adv): 
                                $badge = getVerificationBadge($adv);
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary);">
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" style="color: inherit; text-decoration: none;">
                                                <?= sanitize($adv['name'] ?: 'Advocate') ?>
                                            </a>
                                        </div>
                                        <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size: 0.7rem;"></i> <?= sanitize($adv['mobile'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($adv['e_no'] ?: '—') ?></strong><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?><br>
                                        <small style="color: var(--text-muted);"><i class="fas fa-gavel"></i> <?= sanitize(getCourtName($adv['court'] ?? '')) ?></small>
                                    </td>
                                    <td>
                                        <div><?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?: '—' ?></div>
                                        <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Edit Advocate">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Login as Advocate">
                                                <i class="fas fa-right-to-bracket"></i>
                                            </a>
                                            <a href="../<?= getAdvocateUrl($adv) ?>" target="_blank" class="btn btn-gold btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Public Profile">
                                                <i class="fas fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 0.85rem 1rem; text-align: center; background: #fafafa; border-top: 1px solid var(--border-color);">
                    <a href="advocates.php?type=ACTIVE" class="btn btn-outline btn-sm" style="font-weight: 700;">
                        View All Active Advocates (<?= number_format($activeAdvocates) ?>) &rarr;
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    <p style="margin: 0;">No active advocates found.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. ALL RECENT RECORDS TAB CONTENT -->
        <div id="tabContent_recent" style="display: none;">
            <?php if (!empty($recentAdvocatesList)): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Advocate</th>
                                <th>Enrollment No</th>
                                <th>State / District</th>
                                <th>Status / Badge</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAdvocatesList as $adv): 
                                $badge = getVerificationBadge($adv);
                                $isPendingAdv = strtoupper(trim((string)($adv['type'] ?? ''))) === 'PENDING';
                            ?>
                                <tr style="<?= $isPendingAdv ? 'background: #fffdf5;' : '' ?>">
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary);">
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" style="color: inherit; text-decoration: none;">
                                                <?= sanitize($adv['name'] ?: 'Advocate') ?>
                                            </a>
                                        </div>
                                        <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size: 0.7rem;"></i> <?= sanitize($adv['mobile'] ?: '—') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= sanitize($adv['e_no'] ?: '—') ?></strong><?= $adv['e_year'] ? '/' . sanitize($adv['e_year']) : '' ?>
                                    </td>
                                    <td>
                                        <div><?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?: '—' ?></div>
                                        <small style="color: var(--text-muted);"><?= sanitize(getStateName($adv['state_code'] ?? '')) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                            <?php if ($isPendingAdv): ?>
                                                <a href="index.php?action=verify&id=<?= $adv['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('Verify and approve advocate <?= htmlspecialchars(addslashes($adv['name'] ?: '')) ?>?');" style="background: #059669; border-color: #059669; font-weight: 700; padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Edit Advocate">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="../<?= getAdvocateUrl($adv) ?>" target="_blank" class="btn btn-gold btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Public Profile">
                                                <i class="fas fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 0.85rem 1rem; text-align: center; background: #fafafa; border-top: 1px solid var(--border-color);">
                    <a href="advocates.php" class="btn btn-outline btn-sm" style="font-weight: 700;">
                        Open Full Advocate Directory (<?= number_format($totalAdvocates) ?>) &rarr;
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    <p style="margin: 0;">No records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Recent Grievances / Inquiries Feed -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-envelope-open-text" style="color: var(--brand-gold-dark);"></i> Recent Grievances</h3>
            <a href="contacts.php" class="btn btn-outline btn-sm" style="font-size: 0.75rem; font-weight: 700;">Manage All (<?= number_format($pendingGrievances) ?>) &rarr;</a>
        </div>

        <?php if (!empty($recentContacts)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <?php foreach ($recentContacts as $c): 
                    $isPending = in_array(strtoupper($c['status'] ?? ''), ['PENDING', 'NEW', '']);
                ?>
                    <div style="background: var(--bg-alt); padding: 0.95rem; border-radius: var(--radius-md); border-left: 3px solid <?= $isPending ? '#ef4444' : '#10b981' ?>;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                            <span style="font-weight: 700; color: var(--primary);"><?= sanitize($c['name']) ?></span>
                            <span><?= date('d M, H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.85rem; color: var(--primary); margin-bottom: 0.25rem;">
                            <?= sanitize($c['subject'] ?: 'Inquiry Submission') ?>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0 0 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                            <?= sanitize($c['message'] ?? '') ?>
                        </p>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem;">
                            <span style="color: var(--text-muted);"><i class="fas fa-phone"></i> <?= sanitize($c['mobile'] ?: $c['email']) ?></span>
                            <a href="contacts.php?id=<?= $c['id'] ?>" style="font-weight: 700; color: var(--brand-red);">Review &rarr;</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                <i class="fas fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem;"></i>
                <p style="margin: 0; font-size: 0.85rem;">No recent grievances or inquiry submissions.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ID Proof Lightbox Modal -->
<div id="dashProofModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.78); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; max-width: 720px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35);">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <div>
                <h4 id="dashProofModalTitle" style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--primary);">Advocate ID Proof</h4>
                <div id="dashProofModalSubtitle" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;"></div>
            </div>
            <button type="button" onclick="closeDashProofModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b; padding: 0.25rem; line-height: 1;">&times;</button>
        </div>
        <div id="dashProofModalBody" style="padding: 1rem; overflow: auto; text-align: center; flex: 1; background: #0f172a; display: flex; align-items: center; justify-content: center; min-height: 300px;">
            <img id="dashProofModalImg" src="" alt="ID Proof" style="max-width: 100%; max-height: 65vh; object-fit: contain; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.5);">
            <div id="dashProofModalPdf" style="display: none; color: #fff; padding: 2rem;">
                <i class="fas fa-file-pdf" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 1rem;"></i>
                <div style="font-weight: 600; font-size: 1.1rem; margin-bottom: 1rem;">PDF Document Uploaded</div>
                <a id="dashProofModalPdfLink" href="#" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Open PDF in New Tab
                </a>
            </div>
        </div>
        <div style="padding: 0.75rem 1.25rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <a id="dashProofModalOpenBtn" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                <i class="fas fa-arrow-up-right-from-square me-1"></i> Open Full Document (New Tab)
            </a>
            <button type="button" onclick="closeDashProofModal()" class="btn btn-outline btn-sm">Close</button>
        </div>
    </div>
</div>

<script>
function switchAdvTab(tabKey) {
    var tabs = ['pending', 'active', 'recent'];
    var viewAllLink = document.getElementById('viewAllAdvLink');

    tabs.forEach(function(t) {
        var content = document.getElementById('tabContent_' + t);
        var btn = document.getElementById('tabBtn_' + t);

        if (content) {
            content.style.display = (t === tabKey) ? 'block' : 'none';
        }
        if (btn) {
            btn.classList.remove('active-tab', 'active-tab-pending', 'active-tab-active');
            if (t === tabKey) {
                if (t === 'pending') {
                    btn.classList.add('active-tab-pending');
                } else if (t === 'active') {
                    btn.classList.add('active-tab-active');
                } else {
                    btn.classList.add('active-tab');
                }
            }
        }
    });

    if (viewAllLink) {
        if (tabKey === 'pending') {
            viewAllLink.href = 'advocates.php?type=PENDING';
            viewAllLink.innerHTML = 'Manage All Pending &rarr;';
        } else if (tabKey === 'active') {
            viewAllLink.href = 'advocates.php?type=ACTIVE';
            viewAllLink.innerHTML = 'View All Active &rarr;';
        } else {
            viewAllLink.href = 'advocates.php';
            viewAllLink.innerHTML = 'Manage All in Directory &rarr;';
        }
    }
}

function openDashProofModal(url, name, eno, isPdf) {
    var modal = document.getElementById('dashProofModal');
    var img = document.getElementById('dashProofModalImg');
    var pdfBox = document.getElementById('dashProofModalPdf');
    var pdfLink = document.getElementById('dashProofModalPdfLink');
    var openBtn = document.getElementById('dashProofModalOpenBtn');
    
    document.getElementById('dashProofModalTitle').innerText = name || 'Advocate ID Proof';
    document.getElementById('dashProofModalSubtitle').innerText = eno ? 'Enrollment: ' + eno : '';
    openBtn.href = url;

    if (isPdf) {
        img.style.display = 'none';
        pdfBox.style.display = 'block';
        pdfLink.href = url;
    } else {
        pdfBox.style.display = 'none';
        img.style.display = 'block';
        img.src = url;
    }

    modal.style.display = 'flex';
}

function closeDashProofModal() {
    var modal = document.getElementById('dashProofModal');
    var img = document.getElementById('dashProofModalImg');
    if (modal) modal.style.display = 'none';
    if (img) img.src = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDashProofModal();
    }
});
var dashModal = document.getElementById('dashProofModal');
if (dashModal) {
    dashModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeDashProofModal();
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
