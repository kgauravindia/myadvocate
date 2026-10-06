<?php
// admin/contacts.php - Manage Grievances & Contact Inquiries
$pageTitle = "Grievances & Queries";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle status update / reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactId = sanitize($_POST['id'] ?? '');
    $status = sanitize($_POST['status'] ?? 'RESOLVED');
    $reply = sanitize($_POST['reply'] ?? '');

    if ($contactId) {
        try {
            $stmt = $db->prepare("UPDATE contact SET status = ?, reply = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $reply, $contactId]);
            $msg = "Grievance status and response updated.";
        } catch (Exception $e) {
            $err = "Error updating grievance: " . $e->getMessage();
        }
    }
}

// Handle Delete
if ($action === 'delete' && $id) {
    try {
        $del = $db->prepare("DELETE FROM contact WHERE id = ?");
        $del->execute([$id]);
        $msg = "Inquiry deleted.";
    } catch (Exception $e) {
        $err = "Error deleting inquiry.";
    }
}

// Filter
$statusFilter = sanitize($_GET['status'] ?? '');
$where = ["1=1"];
$params = [];

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);
$stmt = $db->prepare("SELECT * FROM contact WHERE $whereSql ORDER BY id DESC LIMIT 100");
$stmt->execute($params);
$contacts = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Grievances & Support Inquiries</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Review user feedback, profile removal/correction requests, and inquiries.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="contacts.php" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline' ?> btn-sm">All (<?= count($contacts) ?>)</a>
        <a href="contacts.php?status=PENDING" class="btn <?= $statusFilter === 'PENDING' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Pending</a>
        <a href="contacts.php?status=RESOLVED" class="btn <?= $statusFilter === 'RESOLVED' ? 'btn-primary' : 'btn-outline' ?> btn-sm">Resolved</a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<!-- Contacts List Table -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Sender Details</th>
                <th>Subject & Message</th>
                <th>Date Received</th>
                <th>Status</th>
                <th style="text-align: right;">Action / Resolution</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($contacts)): ?>
                <?php foreach ($contacts as $c): 
                    $currStatus = strtoupper($c['status'] ?? 'PENDING');
                    if (empty($currStatus)) $currStatus = 'PENDING';
                ?>
                    <tr>
                        <td>#<?= $c['id'] ?></td>
                        <td>
                            <strong><?= sanitize($c['name']) ?></strong><br>
                            <small style="color: var(--text-muted);"><i class="fas fa-envelope"></i> <?= sanitize($c['email'] ?: 'No Email') ?></small><br>
                            <small style="color: var(--text-muted);"><i class="fas fa-phone"></i> <?= sanitize($c['mobile'] ?: 'No Mobile') ?></small>
                        </td>
                        <td style="max-width: 320px;">
                            <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.25rem;"><?= sanitize($c['subject'] ?: 'Inquiry') ?></div>
                            <div style="font-size: 0.8125rem; color: var(--text-main); background: #f9fafb; padding: 0.5rem; border-radius: 4px; border: 1px solid var(--border-color);">
                                <?= nl2br(sanitize($c['message'] ?? '')) ?>
                            </div>
                            <?php if (!empty($c['reply'])): ?>
                                <div style="margin-top: 0.35rem; font-size: 0.75rem; color: #065f46; background: #ecfdf5; padding: 0.35rem; border-radius: 4px;">
                                    <strong>Admin Note:</strong> <?= sanitize($c['reply']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></small>
                        </td>
                        <td>
                            <?php if ($currStatus === 'RESOLVED'): ?>
                                <span class="badge-verification badge-verified" style="font-size: 0.7rem;"><i class="fas fa-check"></i> RESOLVED</span>
                            <?php else: ?>
                                <span class="badge-verification badge-basic" style="font-size: 0.7rem; background: #fef2f2; color: #b91c1c; border-color: #fca5a5;"><i class="fas fa-clock"></i> PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="openReplyModal(<?= $c['id'] ?>, '<?= addslashes(sanitize($c['name'])) ?>', '<?= addslashes(sanitize($c['status'] ?? 'PENDING')) ?>', '<?= addslashes(sanitize($c['reply'] ?? '')) ?>')">
                                <i class="fas fa-reply"></i> Update
                            </button>
                            <a href="contacts.php?action=delete&id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" style="color: #dc2626;" onclick="return confirm('Delete this record?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">No grievances or inquiries recorded.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Quick Update Modal Script -->
<div id="replyModal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: #ffffff; border-radius: var(--radius-lg); max-width: 500px; width: 100%; padding: 2rem; border-top: 5px solid var(--brand-red);">
        <h3 style="margin-top:0; font-family: var(--font-heading); color: var(--primary);">Update Grievance Status</h3>
        <p id="modalUserText" style="color: var(--text-muted); font-size: 0.875rem;"></p>
        
        <form action="contacts.php" method="POST">
            <input type="hidden" name="id" id="modalContactId">
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" id="modalStatus" class="filter-select">
                    <option value="PENDING">PENDING</option>
                    <option value="PROCESSING">IN PROGRESS / REVIEW</option>
                    <option value="RESOLVED">RESOLVED</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Internal Resolution Notes / Reply</label>
                <textarea name="reply" id="modalReply" class="filter-input" rows="3" placeholder="Action taken or response note..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeReplyModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Save Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReplyModal(id, name, status, reply) {
    document.getElementById('modalContactId').value = id;
    document.getElementById('modalUserText').innerText = 'Inquiry from: ' + name;
    document.getElementById('modalStatus').value = status || 'PENDING';
    document.getElementById('modalReply').value = reply || '';
    document.getElementById('replyModal').style.display = 'flex';
}
function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
