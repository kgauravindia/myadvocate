<?php
// admin/change_requests.php - Manage Advocate Credential Change Requests
$pageTitle = "Advocate Credential Change Requests";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

$barCouncils = getBarCouncils();
$bcMap = [];
foreach ($barCouncils as $bc) {
    $bcMap[$bc['id']] = $bc['name'];
}

// Handle Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqId = (int)($_POST['request_id'] ?? 0);
    $reqAction = sanitize($_POST['action'] ?? '');
    $adminRemarks = sanitize($_POST['admin_remarks'] ?? '');
    $adminId = (int)($_SESSION['admin_id'] ?? 0);

    if ($reqId > 0) {
        try {
            $stmtReq = $db->prepare("SELECT r.*, a.name as current_name, a.mobile as current_mobile, a.e_no as current_e_no, a.e_year as current_e_year, a.bc_id as current_bc_id, a.e_date as current_e_date 
                                     FROM advocate_change_requests r 
                                     JOIN advocate a ON r.advocate_id = a.id 
                                     WHERE r.id = ? LIMIT 1");
            $stmtReq->execute([$reqId]);
            $req = $stmtReq->fetch();

            if (!$req) {
                $err = "Change request record not found.";
            } elseif ($req['status'] !== 'PENDING') {
                $err = "This change request has already been " . strtolower($req['status']) . ".";
            } elseif ($reqAction === 'approve') {
                // Apply requested modifications to the advocate table
                $upFields = [];
                $upParams = [];

                if (!empty($req['requested_name'])) {
                    $upFields[] = "name = ?";
                    $upParams[] = $req['requested_name'];
                }
                if (!empty($req['requested_mobile'])) {
                    $upFields[] = "mobile = ?";
                    $upParams[] = $req['requested_mobile'];
                }
                if (!empty($req['requested_e_no'])) {
                    $upFields[] = "e_no = ?";
                    $upParams[] = $req['requested_e_no'];
                }
                if (!empty($req['requested_e_year'])) {
                    $upFields[] = "e_year = ?";
                    $upParams[] = $req['requested_e_year'];
                }
                if (!empty($req['requested_bc_id'])) {
                    $upFields[] = "bc_id = ?";
                    $upParams[] = $req['requested_bc_id'];
                }
                if (!empty($req['requested_e_date'])) {
                    $upFields[] = "e_date = ?";
                    $upParams[] = $req['requested_e_date'];
                }

                if (!empty($upFields)) {
                    $upFields[] = "updated_at = NOW()";
                    $upParams[] = $req['advocate_id'];
                    $sqlUp = "UPDATE advocate SET " . implode(", ", $upFields) . " WHERE id = ?";
                    $stmtAdvUp = $db->prepare($sqlUp);
                    $stmtAdvUp->execute($upParams);
                }

                // Update request status
                $stmtReqUp = $db->prepare("UPDATE advocate_change_requests SET status = 'APPROVED', admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmtReqUp->execute([$adminRemarks ?: 'Approved by Administrator.', $adminId, $reqId]);

                $msg = "Change request #" . $reqId . " approved successfully! Advocate's credentials have been updated.";
            } elseif ($reqAction === 'reject') {
                $stmtReqUp = $db->prepare("UPDATE advocate_change_requests SET status = 'REJECTED', admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
                $stmtReqUp->execute([$adminRemarks ?: 'Rejected by Administrator.', $adminId, $reqId]);

                $msg = "Change request #" . $reqId . " has been marked as REJECTED.";
            }
        } catch (Exception $e) {
            $err = "Error processing request: " . $e->getMessage();
        }
    }
}

// Handle Delete Request
if ($action === 'delete' && $id) {
    try {
        $del = $db->prepare("DELETE FROM advocate_change_requests WHERE id = ?");
        $del->execute([$id]);
        $msg = "Change request deleted successfully.";
    } catch (Exception $e) {
        $err = "Error deleting change request.";
    }
}

// Filter
$statusFilter = strtoupper(sanitize($_GET['status'] ?? ''));
$where = ["1=1"];
$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED'])) {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
}

$whereSql = implode(" AND ", $where);
$stmt = $db->prepare("SELECT r.*, a.name as adv_name, a.mobile as adv_mobile, a.email as adv_email, a.e_no as adv_e_no, a.e_year as adv_e_year, a.bc_id as adv_bc_id, a.e_date as adv_e_date, a.public_url, a.plan_type, a.type
                      FROM advocate_change_requests r
                      LEFT JOIN advocate a ON r.advocate_id = a.id
                      WHERE $whereSql 
                      ORDER BY CASE WHEN r.status = 'PENDING' THEN 1 ELSE 2 END, r.id DESC 
                      LIMIT 150");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Counts for tabs
$cntPending = (int)$db->query("SELECT COUNT(*) FROM advocate_change_requests WHERE status = 'PENDING'")->fetchColumn();
$cntApproved = (int)$db->query("SELECT COUNT(*) FROM advocate_change_requests WHERE status = 'APPROVED'")->fetchColumn();
$cntRejected = (int)$db->query("SELECT COUNT(*) FROM advocate_change_requests WHERE status = 'REJECTED'")->fetchColumn();
$cntTotal = $cntPending + $cntApproved + $cntRejected;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-clipboard-check" style="color: var(--brand-red);"></i> Credential Change Requests
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
            Review and approve/reject verified credentials change requests submitted by active advocates.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="change_requests.php" class="btn <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            All (<?= $cntTotal ?>)
        </a>
        <a href="change_requests.php?status=PENDING" class="btn <?= $statusFilter === 'PENDING' ? 'btn-primary' : 'btn-outline' ?> btn-sm" style="position: relative;">
            Pending
            <?php if ($cntPending > 0): ?>
                <span class="badge" style="background: #ef4444; color: #fff; font-size: 0.7rem; padding: 0.15rem 0.45rem; border-radius: 9999px; margin-left: 0.35rem;"><?= $cntPending ?></span>
            <?php endif; ?>
        </a>
        <a href="change_requests.php?status=APPROVED" class="btn <?= $statusFilter === 'APPROVED' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            Approved (<?= $cntApproved ?>)
        </a>
        <a href="change_requests.php?status=REJECTED" class="btn <?= $statusFilter === 'REJECTED' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
            Rejected (<?= $cntRejected ?>)
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 70px;">Req ID</th>
                <th>Advocate Profile</th>
                <th>Requested Changes</th>
                <th>Reason & Supporting Doc</th>
                <th>Date & Status</th>
                <th style="text-align: right; min-width: 140px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $r): 
                    $currStatus = strtoupper($r['status'] ?? 'PENDING');
                    $docUrl = getChangeRequestDocUrl($r['document_path'] ?? '');
                ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--primary);">
                            #<?= $r['id'] ?>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem;">
                                <?= sanitize($r['adv_name'] ?: 'Unknown Advocate') ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.15rem;">
                                <i class="fas fa-phone me-1"></i> <?= sanitize($r['adv_mobile'] ?: 'N/A') ?>
                            </div>
                            <div style="font-size: 0.78rem; color: var(--text-muted);">
                                <i class="fas fa-id-card me-1"></i> Enr: <strong><?= sanitize($r['adv_e_no'] ?: 'N/A') ?></strong>
                            </div>
                            <div style="margin-top: 0.35rem;">
                                <a href="advocate_edit.php?id=<?= $r['advocate_id'] ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 0.15rem 0.5rem; font-size: 0.7rem;">
                                    <i class="fas fa-arrow-up-right-from-square me-1"></i> Edit Advocate
                                </a>
                            </div>
                        </td>
                        <td style="font-size: 0.85rem;">
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 0.6rem 0.75rem; display: flex; flex-direction: column; gap: 0.4rem;">
                                <?php if (!empty($r['requested_name'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Full Name:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($r['adv_name']) ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($r['requested_name']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['requested_mobile'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Primary Mobile:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($r['adv_mobile']) ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($r['requested_mobile']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['requested_e_no'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Enrollment No:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($r['adv_e_no'] ?: 'None') ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($r['requested_e_no']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['requested_e_year'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Enrollment Year:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($r['adv_e_year'] ?: 'None') ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($r['requested_e_year']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['requested_bc_id'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Bar Council:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($bcMap[$r['adv_bc_id']] ?? 'Not Selected') ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($bcMap[$r['requested_bc_id']] ?? ('ID #' . $r['requested_bc_id'])) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($r['requested_e_date'])): ?>
                                    <div>
                                        <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #64748b; display: block;">Date of Enrollment:</span>
                                        <span style="color: #dc2626; text-decoration: line-through; font-size: 0.8rem;"><?= sanitize($r['adv_e_date'] ?: 'None') ?></span>
                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.7rem; color: #64748b;"></i>
                                        <strong style="color: #16a34a;"><?= sanitize($r['requested_e_date']) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="max-width: 250px;">
                            <div style="font-size: 0.85rem; color: #334155; line-height: 1.4; margin-bottom: 0.5rem;">
                                <strong>Reason:</strong> <?= nl2br(sanitize($r['reason'])) ?>
                            </div>
                            <?php if ($docUrl): ?>
                                <a href="<?= sanitize($docUrl) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; color: var(--brand-red); border-color: var(--brand-red);">
                                    <i class="fas fa-paperclip me-1"></i> View Supporting Document
                                </a>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-file-excel me-1"></i> No document attached</span>
                            <?php endif; ?>

                            <?php if (!empty($r['admin_remarks'])): ?>
                                <div style="margin-top: 0.5rem; background: #fff; border: 1px dashed #cbd5e1; padding: 0.35rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem; color: #475569;">
                                    <strong>Admin Note:</strong> <?= sanitize($r['admin_remarks']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.35rem;">
                                <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
                            </div>
                            <?php if ($currStatus === 'PENDING'): ?>
                                <span style="background: #fef3c7; color: #b45309; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-clock"></i> Pending Review
                                </span>
                            <?php elseif ($currStatus === 'APPROVED'): ?>
                                <span style="background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-check-circle"></i> Approved
                                </span>
                            <?php else: ?>
                                <span style="background: #fee2e2; color: #b91c1c; font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i class="fas fa-circle-xmark"></i> Rejected
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($currStatus === 'PENDING'): ?>
                                <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-end;">
                                    <!-- Approve Form -->
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this change request? The advocate\'s credentials will be updated immediately.');">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="admin_remarks" value="Approved by Admin after verification.">
                                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; background: #16a34a; border-color: #16a34a; width: 100%;">
                                            <i class="fas fa-check me-1"></i> Approve & Apply
                                        </button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to REJECT this change request?');">
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="admin_remarks" value="Request could not be verified with Bar Council records.">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem; width: 100%;">
                                            <i class="fas fa-xmark me-1"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <a href="change_requests.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Delete this change request record?');" class="btn btn-outline-danger btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size: 2.5rem; margin-bottom: 0.75rem; display: block; opacity: 0.4;"></i>
                        No credential change requests found.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
