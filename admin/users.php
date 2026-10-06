<?php
// admin/users.php - Manage Administrator Accounts
$pageTitle = "Admin Users";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $userName = sanitize($_POST['user_name'] ?? '');
    $userEmail = sanitize($_POST['user_email'] ?? '');
    $userMobile = sanitize($_POST['user_mobile'] ?? '');
    $userType = sanitize($_POST['user_type'] ?? 'ADMIN');
    $userStatus = sanitize($_POST['user_status'] ?? 'ACTIVE');
    $password = $_POST['password'] ?? '';
    $editId = sanitize($_POST['id'] ?? '');

    if ($userName) {
        try {
            if ($editId) {
                // Update
                if (!empty($password)) {
                    $hash = md5($password);
                    $stmt = $db->prepare("UPDATE op_user SET full_name = ?, user_name = ?, user_email = ?, user_mobile = ?, user_type = ?, user_status = ?, user_pass = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$fullName, $userName, $userEmail, $userMobile, $userType, $userStatus, $hash, $editId]);
                } else {
                    $stmt = $db->prepare("UPDATE op_user SET full_name = ?, user_name = ?, user_email = ?, user_mobile = ?, user_type = ?, user_status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$fullName, $userName, $userEmail, $userMobile, $userType, $userStatus, $editId]);
                }
                $msg = "Administrator account updated.";
                $action = 'list';
            } else {
                // Insert
                if (empty($password)) {
                    $password = 'Adv@@2026!';
                }
                $hash = md5($password);
                $stmt = $db->prepare("INSERT INTO op_user (full_name, user_name, user_email, user_mobile, user_type, user_status, user_pass, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$fullName, $userName, $userEmail, $userMobile, $userType, $userStatus, $hash]);
                $msg = "New administrator created successfully.";
                $action = 'list';
            }
        } catch (Exception $e) {
            $err = "Error saving user: " . $e->getMessage();
        }
    } else {
        $err = "Username is required.";
    }
}

// Handle Delete
if ($action === 'delete' && $id) {
    if ((int)$id === (int)($_SESSION['admin_id'] ?? 0)) {
        $err = "Cannot delete your own active administrator account.";
    } else {
        try {
            $del = $db->prepare("DELETE FROM op_user WHERE id = ?");
            $del->execute([$id]);
            $msg = "Administrator deleted.";
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error deleting account.";
        }
    }
}

// Fetch single user for edit
$editUser = null;
if ($action === 'edit' && $id) {
    $st = $db->prepare("SELECT * FROM op_user WHERE id = ?");
    $st->execute([$id]);
    $editUser = $st->fetch();
}

// Fetch all users
$users = $db->query("SELECT * FROM op_user ORDER BY id ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Administrator Accounts</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Manage master credentials, roles, and administrative access permissions.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="users.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Add New Admin</a>
        <?php else: ?>
            <a href="users.php" class="btn btn-outline btn-sm">Back to User List</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editUser)): ?>
    <!-- Form Box -->
    <div class="admin-card" style="max-width: 650px; margin-bottom: 2rem; border-top: 4px solid var(--brand-red);">
        <h3 class="admin-card-title" style="margin-bottom: 1.25rem;">
            <?= $editUser ? 'Edit Admin: ' . sanitize($editUser['user_name']) : 'Create Administrator Account' ?>
        </h3>

        <form action="users.php" method="POST">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Full Name</label>
                <input type="text" name="full_name" class="filter-input" value="<?= sanitize($editUser['full_name'] ?? '') ?>" placeholder="e.g. Kumar Gaurav" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Username *</label>
                    <input type="text" name="user_name" class="filter-input" value="<?= sanitize($editUser['user_name'] ?? '') ?>" required>
                </div>
                <div class="filter-group">
                    <label class="filter-label">User Role</label>
                    <select name="user_type" class="filter-select">
                        <option value="DEV" <?= ($editUser['user_type'] ?? '') === 'DEV' ? 'selected' : '' ?>>DEV (Super Master)</option>
                        <option value="ADMIN" <?= ($editUser['user_type'] ?? 'ADMIN') === 'ADMIN' ? 'selected' : '' ?>>ADMIN (Full Access)</option>
                        <option value="MODERATOR" <?= ($editUser['user_type'] ?? '') === 'MODERATOR' ? 'selected' : '' ?>>MODERATOR (Directory Editor)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label">Email Address</label>
                    <input type="email" name="user_email" class="filter-input" value="<?= sanitize($editUser['user_email'] ?? '') ?>">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Mobile Number</label>
                    <input type="text" name="user_mobile" class="filter-input" value="<?= sanitize($editUser['user_mobile'] ?? '') ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="filter-group">
                    <label class="filter-label"><?= $editUser ? 'Change Password (leave empty to keep)' : 'Password *' ?></label>
                    <input type="password" name="password" class="filter-input" placeholder="••••••••" <?= $editUser ? '' : 'required' ?>>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Account Status</label>
                    <select name="user_status" class="filter-select">
                        <option value="ACTIVE" <?= ($editUser['user_status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                        <option value="INACTIVE" <?= ($editUser['user_status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Account</button>
        </form>
    </div>
<?php endif; ?>

<!-- Users List -->
<div class="table-responsive">
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Administrator</th>
                <th>Username</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th style="text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?= $u['id'] ?></td>
                    <td>
                        <strong><?= sanitize($u['full_name'] ?: 'Admin User') ?></strong>
                    </td>
                    <td><code><?= sanitize($u['user_name']) ?></code></td>
                    <td>
                        <small><?= sanitize($u['user_email'] ?: '—') ?><br><?= sanitize($u['user_mobile'] ?: '—') ?></small>
                    </td>
                    <td>
                        <span class="badge-verification badge-verified" style="font-size: 0.7rem;"><?= sanitize($u['user_type']) ?></span>
                    </td>
                    <td>
                        <span class="badge-verification <?= ($u['user_status'] ?? 'ACTIVE') === 'ACTIVE' ? 'badge-verified' : 'badge-basic' ?>" style="font-size: 0.7rem;">
                            <?= sanitize($u['user_status'] ?? 'ACTIVE') ?>
                        </span>
                    </td>
                    <td>
                        <small><?= $u['last_login'] ? date('d M Y, h:i A', strtotime($u['last_login'])) : '—' ?></small>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-pen"></i> Edit</a>
                        <?php if ((int)$u['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                            <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; color: #dc2626;" onclick="return confirm('Delete this administrator?');"><i class="fas fa-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
