<?php
// notice.php - Legal Updates & Notifications (Preserving Legacy Route)
require_once __DIR__ . '/config/app.php';

$pageTitle = "Legal Notices & Notifications - Court Updates & Examination Announcements";
$pageDescription = "Official notifications from the Supreme Court, High Courts, Bar Council of India, and Judicial Service Commissions.";

$db = getDB();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$notices = [];
$totalNotices = 0;
try {
    $totalNotices = (int)$db->query("SELECT COUNT(*) FROM notice WHERE status = 'ACTIVE'")->fetchColumn();
    $stmt = $db->query("SELECT * FROM notice WHERE status = 'ACTIVE' ORDER BY (CASE WHEN last_date >= CURDATE() THEN 1 ELSE 2 END), id DESC LIMIT $perPage OFFSET $offset");
    $notices = $stmt->fetchAll();
} catch (Exception $e) {}

$totalPages = ceil($totalNotices / $perPage);

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Legal Updates</span> &bull; <span>Notifications</span>
    </nav>

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary);">
            <i class="fas fa-bullhorn" style="color: var(--brand-accent);"></i> Legal Updates & Official Notifications
        </h1>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">
            Curated notifications from High Courts, Bar Councils, Judicial Service Commissions, and AIBE.
        </p>
    </div>

    <?php if (!empty($notices)): ?>
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <?php foreach ($notices as $n): 
                $isExpired = !empty($n['last_date']) && strtotime($n['last_date']) < time();
                $statusBadge = $isExpired ? '<span class="badge-verification badge-basic">🔴 Expired</span>' : '<span class="badge-verification badge-registered">🟢 Active</span>';
            ?>
                <div class="stat-box" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="max-width: 800px;">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                            <?= $statusBadge ?>
                            <?php if (!empty($n['last_date'])): ?>
                                <span style="font-size: 0.8125rem; color: var(--text-muted);">
                                    <i class="far fa-calendar-alt"></i> Last Date: <strong><?= date('d M Y', strtotime($n['last_date'])) ?></strong>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.35rem;"><?= sanitize($n['name']) ?></h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.5rem;"><?= sanitize($n['details'] ?? 'Official notification details and download documents.') ?></p>
                    </div>

                    <div style="display: flex; gap: 0.5rem;">
                        <?php if (!empty($n['url_1'])): ?>
                            <a href="<?= sanitize($n['url_1']) ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> Official Link</a>
                        <?php else: ?>
                            <button class="btn btn-outline btn-sm" disabled><i class="fas fa-circle-info"></i> Notice Detail</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= renderPagination($page, $totalPages, 'notice', $_GET) ?>
    <?php else: ?>
        <div class="stat-box" style="text-align: center; padding: 4rem 2rem;">
            <p>No legal notifications available at this time.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
