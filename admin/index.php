<?php
// admin/index.php - Comprehensive Admin Dashboard
$pageTitle = "Dashboard Overview";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

// Fetch statistics
$totalAdvocates = 0;
$verifiedAdvocates = 0;
$registeredAdvocates = 0;
$totalActs = 0;
$totalNotices = 0;
$totalColleges = 0;
$totalBarAssns = 0;
$totalHighCourts = 0;
$pendingGrievances = 0;

try {
    $totalAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE status = 'ACTIVE'")->fetchColumn();
    $verifiedAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE (plan_type = 'verified' OR premium_member = 1) AND status = 'ACTIVE'")->fetchColumn();
    $registeredAdvocates = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE (plan_type = 'registered' OR type = 'ACTIVE') AND status = 'ACTIVE'")->fetchColumn();
    $totalActs = (int)$db->query("SELECT COUNT(*) FROM acts WHERE status = 'ACTIVE'")->fetchColumn();
    $totalNotices = (int)$db->query("SELECT COUNT(*) FROM notice WHERE status = 'ACTIVE'")->fetchColumn();
    $totalColleges = (int)$db->query("SELECT COUNT(*) FROM college WHERE (status = 'ACTIVE' OR status = '' OR status IS NULL) AND name != '' AND status != 'AUTO'")->fetchColumn();
    $totalBarAssns = (int)$db->query("SELECT COUNT(*) FROM ba WHERE status = 'ACTIVE'")->fetchColumn();
    $totalHighCourts = (int)$db->query("SELECT COUNT(*) FROM hc WHERE status = 'ACTIVE'")->fetchColumn();
    $pendingGrievances = (int)$db->query("SELECT COUNT(*) FROM contact WHERE status = 'PENDING' OR status = 'NEW' OR status IS NULL OR status = ''")->fetchColumn();
    $pendingChangeRequests = (int)$db->query("SELECT COUNT(*) FROM advocate_change_requests WHERE status = 'PENDING'")->fetchColumn();
} catch (Exception $e) {}

// Recent advocates
$recentAdvocates = [];
try {
    $stmt = $db->query("SELECT id, name, mobile, state_code, district_code, e_no, e_year, plan_type, type, created_at FROM advocate ORDER BY id DESC LIMIT 10");
    $recentAdvocates = $stmt->fetchAll();
} catch (Exception $e) {}

// Recent contacts / grievances
$recentContacts = [];
try {
    $stmt = $db->query("SELECT id, name, email, mobile, subject, message, status, created_at FROM contact ORDER BY id DESC LIMIT 6");
    $recentContacts = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- KPI Stat Grid -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 2rem; gap: 1.25rem;">
    <div class="stat-box" style="border-left: 4px solid var(--brand-red);">
        <div class="stat-label"><i class="fas fa-users" style="color: var(--brand-red);"></i> Total Advocates</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--brand-red); font-family: var(--font-heading);"><?= number_format($totalAdvocates) ?></div>
        <small style="color: var(--text-muted);">Indexed in national database</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid var(--brand-gold);">
        <div class="stat-label"><i class="fas fa-shield-halved" style="color: var(--brand-gold-dark);"></i> Verified Advocates</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--brand-gold-dark); font-family: var(--font-heading);"><?= number_format($verifiedAdvocates) ?></div>
        <small style="color: var(--text-muted);">Bar Council Authenticated</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid var(--primary);">
        <div class="stat-label"><i class="fas fa-book-bookmark" style="color: var(--primary);"></i> Bare Acts</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--primary); font-family: var(--font-heading);"><?= number_format($totalActs) ?></div>
        <small style="color: var(--text-muted);">BNS, BNSS, BSA & Central Acts</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid #dc2626;">
        <div class="stat-label"><i class="fas fa-graduation-cap" style="color: #dc2626;"></i> Law Colleges</div>
        <div class="stat-value" style="font-size: 1.85rem; color: #dc2626; font-family: var(--font-heading);"><?= number_format($totalColleges) ?></div>
        <small style="color: var(--text-muted);">Colleges & Universities</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid #f59e0b;">
        <div class="stat-label"><i class="fas fa-landmark" style="color: #f59e0b;"></i> Bar Associations</div>
        <div class="stat-value" style="font-size: 1.85rem; color: #f59e0b; font-family: var(--font-heading);"><?= number_format($totalBarAssns) ?></div>
        <small style="color: var(--text-muted);">Across 28 States & 8 UTs</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid #ef4444;">
        <div class="stat-label"><i class="fas fa-headset" style="color: #ef4444;"></i> Open Grievances</div>
        <div class="stat-value" style="font-size: 1.85rem; color: #ef4444; font-family: var(--font-heading);"><?= number_format($pendingGrievances) ?></div>
        <small style="color: var(--text-muted);">Requires admin attention</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid var(--brand-red);">
        <div class="stat-label"><i class="fas fa-clipboard-check" style="color: var(--brand-red);"></i> Change Requests</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--brand-red); font-family: var(--font-heading);"><?= number_format($pendingChangeRequests) ?></div>
        <small style="color: var(--text-muted);"><a href="change_requests.php?status=PENDING" style="color: var(--brand-red); text-decoration: none; font-weight: 600;">Pending review &rarr;</a></small>
    </div>
</div>

<!-- Quick Actions Banner & Direct Search -->
<div class="admin-card" style="border-left: 4px solid var(--brand-red);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h3 style="font-size: 1.2rem; margin-bottom: 0.35rem; color: var(--primary); font-family: var(--font-heading); font-weight: 800;">
                <i class="fas fa-bolt" style="color: var(--brand-gold);"></i> Quick Navigation & Tools
            </h3>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Perform immediate administrative updates or search records instantly.</p>
        </div>
        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap;">
            <a href="advocate_add.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add Advocate</a>
            <a href="acts.php?action=add" class="btn btn-gold btn-sm"><i class="fas fa-plus-circle"></i> Add Bare Act</a>
            <a href="notices.php?action=add" class="btn btn-dark btn-sm"><i class="fas fa-bullhorn"></i> Post Notice</a>
            <a href="colleges.php?action=add" class="btn btn-outline btn-sm"><i class="fas fa-graduation-cap"></i> Add College</a>
        </div>
    </div>

    <!-- Quick Search Field -->
    <form action="advocates.php" method="GET" style="margin-top: 1.25rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="text" name="q" class="filter-input" placeholder="Quick find advocate by Name, Enrollment No, or Mobile..." style="flex: 1; min-width: 250px;">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-magnifying-glass"></i> Search Directory</button>
    </form>

    <!-- AIS Modular Hub -->
    <div style="margin-top: 1.25rem; pt-3; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
        <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.75rem; letter-spacing: 0.05em;">
            <i class="fas fa-cubes-stacked text-primary me-1"></i> Core AIS Management Hub
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem;">
            <a href="advocate_upload.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-file-csv text-primary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Bulk CSV Import</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">Batch Advocates</small>
                </div>
            </a>
            <a href="advocate_reports.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-chart-line text-success"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Advocate Reports</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">State / District / BA</small>
                </div>
            </a>
            <a href="bc.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-scale-balanced text-warning"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Bar Councils</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">26 Councils & BCI</small>
                </div>
            </a>
            <a href="ba.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-building-columns text-info"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Bar Associations</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">344+ Associations</small>
                </div>
            </a>
            <a href="hc.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-landmark text-primary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">High Courts</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">26 State High Courts</small>
                </div>
            </a>
            <a href="change_requests.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-clipboard-check text-danger"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Change Requests</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);"><?= $pendingChangeRequests ?> Pending Verification</small>
                </div>
            </a>
            <a href="questions.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-clipboard-question text-danger"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Questions Bank</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">837+ AIBE MCQs</small>
                </div>
            </a>
            <a href="universities.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-university text-secondary"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">Universities</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">994+ Universities</small>
                </div>
            </a>
            <a href="sitemap_manager.php" class="btn btn-outline btn-sm" style="display: flex; align-items: center; justify-content: flex-start; gap: 0.5rem; text-align: left; padding: 0.6rem 0.75rem;">
                <i class="fas fa-sitemap text-dark"></i>
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; line-height: 1.1;">XML Sitemaps</div>
                    <small style="font-size: 0.7rem; color: var(--text-muted);">Search Engine SEO</small>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Main Split: Recent Advocates & Inquiries -->
<div style="display: grid; grid-template-columns: 1fr; gap: 1.75rem;" class="admin-dash-split">
    <style>
        @media(min-width: 1024px) {
            .admin-dash-split {
                grid-template-columns: 2fr 1.1fr;
            }
        }
    </style>

    <!-- Left: Recent Advocates -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-user-tie" style="color: var(--brand-red);"></i> Latest Advocate Records</h3>
            <a href="advocates.php" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">View All (<?= number_format($totalAdvocates) ?>) &rarr;</a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Advocate</th>
                        <th>Enrollment No</th>
                        <th>State / District</th>
                        <th>Tier Badge</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentAdvocates)): ?>
                        <?php foreach ($recentAdvocates as $adv): 
                            $badge = getVerificationBadge($adv);
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);"><?= sanitize($adv['name'] ?: 'Advocate') ?></div>
                                    <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size: 0.7rem;"></i> <?= sanitize($adv['mobile'] ?: 'Not Provided') ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--text-main);"><?= sanitize($adv['e_no'] ?: 'N/A') ?></span>
                                    <?php if ($adv['e_year']): ?>
                                        <small style="color: var(--text-muted);">/ <?= sanitize($adv['e_year']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= sanitize(getDistrictName($adv['district_code'] ?? '')) ?></div>
                                    <small style="color: var(--text-muted); font-weight: 600;"><?= sanitize($adv['state_code'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge-verification <?= $badge['badge_class'] ?>" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                        <?= $badge['label'] ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="advocate_edit.php?id=<?= $adv['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" title="Edit Advocate">
                                        <i class="fas fa-pen-to-square"></i> Edit
                                    </a>
                                    <a href="impersonate.php?type=advocate&id=<?= $adv['id'] ?>" target="_blank" class="btn btn-dark btn-sm" style="padding: 0.3rem 0.55rem; font-size: 0.75rem;" title="Login as Advocate">
                                        <i class="fas fa-right-to-bracket"></i>
                                    </a>
                                    <a href="../<?= getAdvocateUrl($adv) ?>" target="_blank" class="btn btn-gold btn-sm" style="padding: 0.3rem 0.5rem; font-size: 0.75rem;" title="View Live Profile">
                                        <i class="fas fa-arrow-up-right-from-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Recent Grievances / Inquiries -->
    <div class="admin-card" style="margin-bottom: 0;">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fas fa-envelope-open-text" style="color: var(--brand-gold-dark);"></i> Recent Grievances</h3>
            <a href="contacts.php" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">Manage All &rarr;</a>
        </div>

        <?php if (!empty($recentContacts)): ?>
            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                <?php foreach ($recentContacts as $c): 
                    $isPending = in_array(strtoupper($c['status'] ?? ''), ['PENDING', 'NEW', '']);
                ?>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-md); border-left: 3px solid <?= $isPending ? 'var(--brand-red)' : 'var(--brand-gold)' ?>;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                            <span style="font-weight: 700; color: var(--primary);"><?= sanitize($c['name']) ?></span>
                            <span><?= date('d M, H:i', strtotime($c['created_at'])) ?></span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.875rem; color: var(--primary); margin-bottom: 0.25rem;">
                            <?= sanitize($c['subject'] ?: 'Inquiry Submission') ?>
                        </div>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); margin: 0 0 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
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
            <p style="color: var(--text-muted); font-size: 0.875rem; text-align: center; padding: 2rem;">No recent messages.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
