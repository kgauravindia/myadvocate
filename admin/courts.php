<?php
// admin/courts.php - Manage High Courts & Bar Associations
$pageTitle = "Courts & Bar Associations";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$states = getStates();

$tab = sanitize($_GET['tab'] ?? 'hc'); // hc or ba
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle High Court POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'hc') {
    $name = sanitize($_POST['name'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $judge = sanitize($_POST['judge'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $year = sanitize($_POST['year'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE hc SET name = ?, address = ?, state_code = ?, judge = ?, tel = ?, email = ?, url = ?, year = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $address, $stateCode, $judge, $tel, $email, $url, $year, $status, $editId]);
                $msg = "High Court record updated.";
                $action = 'list';
            } else {
                $stmt = $db->prepare("INSERT INTO hc (name, address, state_code, judge, tel, email, url, year, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $address, $stateCode, $judge, $tel, $email, $url, $year, $status]);
                $msg = "New High Court added.";
                $action = 'list';
            }
        } catch (Exception $e) {
            $err = "Error saving High Court: " . $e->getMessage();
        }
    } else {
        $err = "Court name is required.";
    }
}

// Handle Bar Association POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'ba') {
    $name = sanitize($_POST['name'] ?? '');
    $code = sanitize($_POST['code'] ?? '');
    $stateCode = sanitize($_POST['state_code'] ?? '');
    $districtCode = sanitize($_POST['district_code'] ?? '');
    $regNo = sanitize($_POST['reg_no'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE ba SET name = ?, code = ?, state_code = ?, district_code = ?, reg_no = ?, address = ?, email = ?, tel = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $regNo, $address, $email, $tel, $status, $editId]);
                $msg = "Bar Association updated.";
                $action = 'list';
            } else {
                $stmt = $db->prepare("INSERT INTO ba (name, code, state_code, district_code, reg_no, address, email, tel, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $stateCode, $districtCode, $regNo, $address, $email, $tel, $status]);
                $msg = "New Bar Association added.";
                $action = 'list';
            }
        } catch (Exception $e) {
            $err = "Error saving Bar Association: " . $e->getMessage();
        }
    } else {
        $err = "Bar Association name is required.";
    }
}

// Handle Delete
if ($action === 'delete' && $id) {
    try {
        $tbl = ($tab === 'ba') ? 'ba' : 'hc';
        $del = $db->prepare("DELETE FROM `$tbl` WHERE id = ?");
        $del->execute([$id]);
        $msg = "Record deleted successfully.";
        $action = 'list';
    } catch (Exception $e) {
        $err = "Error deleting record: " . $e->getMessage();
    }
}

// Edit Record
$editItem = null;
if ($action === 'edit' && $id) {
    $tbl = ($tab === 'ba') ? 'ba' : 'hc';
    $st = $db->prepare("SELECT * FROM `$tbl` WHERE id = ?");
    $st->execute([$id]);
    $editItem = $st->fetch();
}

// Fetch list
$highCourts = [];
$barAssns = [];
if ($tab === 'hc') {
    $highCourts = $db->query("SELECT * FROM hc ORDER BY name ASC")->fetchAll();
} else {
    $barAssns = $db->query("SELECT * FROM ba ORDER BY id DESC LIMIT 100")->fetchAll();
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">Courts & Bar Associations</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">Manage High Courts of India and District Bar Association registrations.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
            <a href="courts.php?tab=<?= $tab ?>&action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add <?= $tab === 'hc' ? 'High Court' : 'Bar Association' ?></a>
        <?php else: ?>
            <a href="courts.php?tab=<?= $tab ?>" class="btn btn-outline btn-sm">Back</a>
        <?php endif; ?>
    </div>
</div>

<!-- Tabs Navigation -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color);">
    <a href="courts.php?tab=hc" class="btn <?= $tab === 'hc' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-landmark"></i> High Courts (26)
    </a>
    <a href="courts.php?tab=ba" class="btn <?= $tab === 'ba' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-users-rectangle"></i> Bar Associations (340+)
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert-admin alert-admin-success"><i class="fas fa-circle-check"></i> <?= sanitize($msg) ?></div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="alert-admin alert-admin-error"><i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?></div>
<?php endif; ?>

<?php if ($action === 'add' || ($action === 'edit' && $editItem)): ?>
    <!-- Form Box -->
    <div class="admin-card" style="max-width: 800px; margin-bottom: 2rem; border-top: 4px solid var(--brand-red);">
        <h3 class="admin-card-title" style="margin-bottom: 1.25rem;">
            <?= $editItem ? 'Edit ' . ($tab === 'hc' ? 'High Court' : 'Bar Association') : 'Add New ' . ($tab === 'hc' ? 'High Court' : 'Bar Association') ?>
        </h3>

        <form action="courts.php?tab=<?= $tab ?>" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label"><?= $tab === 'hc' ? 'High Court Title' : 'Bar Association Name' ?> *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required>
            </div>

            <?php if ($tab === 'hc'): ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">State Jurisdiction</label>
                        <select name="state_code" class="filter-select">
                            <option value="">Select State</option>
                            <?php foreach ($states as $sCode => $sName): ?>
                                <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Chief Justice / Hon'ble Judge</label>
                        <input type="text" name="judge" class="filter-input" value="<?= sanitize($editItem['judge'] ?? '') ?>" placeholder="Hon'ble Chief Justice...">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Court Complex Address</label>
                    <input type="text" name="address" class="filter-input" value="<?= sanitize($editItem['address'] ?? '') ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Telephone</label>
                        <input type="text" name="tel" class="filter-input" value="<?= sanitize($editItem['tel'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Official Email</label>
                        <input type="email" name="email" class="filter-input" value="<?= sanitize($editItem['email'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Website URL</label>
                        <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>">
                    </div>
                </div>
            <?php else: ?>
                <!-- Bar Association Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Short Code</label>
                        <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" placeholder="e.g. DHCBA">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Registration Number</label>
                        <input type="text" name="reg_no" class="filter-input" value="<?= sanitize($editItem['reg_no'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">State</label>
                        <select name="state_code" class="filter-select">
                            <option value="">Select State</option>
                            <?php foreach ($states as $sCode => $sName): ?>
                                <option value="<?= sanitize($sCode) ?>" <?= ($editItem['state_code'] ?? '') === $sCode ? 'selected' : '' ?>><?= sanitize($sName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">District Code</label>
                        <input type="text" name="district_code" class="filter-input" value="<?= sanitize($editItem['district_code'] ?? '') ?>">
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Office Address</label>
                    <input type="text" name="address" class="filter-input" value="<?= sanitize($editItem['address'] ?? '') ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="filter-group">
                        <label class="filter-label">Contact Phone</label>
                        <input type="text" name="tel" class="filter-input" value="<?= sanitize($editItem['tel'] ?? '') ?>">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Email</label>
                        <input type="email" name="email" class="filter-input" value="<?= sanitize($editItem['email'] ?? '') ?>">
                    </div>
                </div>
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
        </form>
    </div>
<?php endif; ?>

<!-- Listing -->
<?php if ($tab === 'hc'): ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>High Court</th>
                    <th>State</th>
                    <th>Chief Justice / Judge</th>
                    <th>Contact & Web</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($highCourts as $hc): ?>
                    <tr>
                        <td>#<?= $hc['id'] ?></td>
                        <td><strong><?= sanitize($hc['name']) ?></strong><br><small style="color: var(--text-muted);"><?= sanitize($hc['address'] ?: '—') ?></small></td>
                        <td><?= sanitize(getStateName($hc['state_code'] ?? '')) ?></td>
                        <td><?= sanitize($hc['judge'] ?: '—') ?></td>
                        <td>
                            <?php if ($hc['tel']): ?><div><i class="fas fa-phone" style="font-size:0.75rem;"></i> <?= sanitize($hc['tel']) ?></div><?php endif; ?>
                            <?php if ($hc['url']): ?><a href="<?= sanitize($hc['url']) ?>" target="_blank" style="color: var(--brand-red); font-size: 0.75rem;"><i class="fas fa-external-link"></i> Website</a><?php endif; ?>
                        </td>
                        <td><span class="badge-verification badge-verified" style="font-size: 0.7rem;"><?= sanitize($hc['status'] ?? 'ACTIVE') ?></span></td>
                        <td style="text-align: right;">
                            <a href="courts.php?tab=hc&action=edit&id=<?= $hc['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-pen"></i> Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bar Association</th>
                    <th>Code / Reg</th>
                    <th>Location</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($barAssns as $ba): ?>
                    <tr>
                        <td>#<?= $ba['id'] ?></td>
                        <td><strong><?= sanitize($ba['name']) ?></strong><br><small style="color: var(--text-muted);"><?= sanitize($ba['address'] ?: '—') ?></small></td>
                        <td>
                            <strong><?= sanitize($ba['code'] ?: '—') ?></strong><br>
                            <small style="color: var(--text-muted);"><?= sanitize($ba['reg_no'] ?: '—') ?></small>
                        </td>
                        <td><?= sanitize($ba['district_code'] ?: '—') ?>, <small style="font-weight: 700;"><?= sanitize($ba['state_code'] ?: '—') ?></small></td>
                        <td>
                            <?php if ($ba['tel']): ?><div><?= sanitize($ba['tel']) ?></div><?php endif; ?>
                            <?php if ($ba['email']): ?><small style="color: var(--text-muted);"><?= sanitize($ba['email']) ?></small><?php endif; ?>
                        </td>
                        <td><span class="badge-verification badge-verified" style="font-size: 0.7rem;"><?= sanitize($ba['status'] ?? 'ACTIVE') ?></span></td>
                        <td style="text-align: right;">
                            <a href="courts.php?tab=ba&action=edit&id=<?= $ba['id'] ?>" class="btn btn-outline btn-sm" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;"><i class="fas fa-pen"></i> Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
