<?php
// admin/bc.php - Manage State Bar Councils & Official Advocate Verification Portals (url_1)
$pageTitle = "State Bar Councils & Verification Links";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$msg = '';
$err = '';
$action = sanitize($_GET['action'] ?? 'list');
$id = sanitize($_GET['id'] ?? '');

// Handle Create / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $bciCode = sanitize($_POST['bci_code'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $tel = sanitize($_POST['tel'] ?? '');
    $url = sanitize($_POST['url'] ?? '');
    $url1 = sanitize($_POST['url_1'] ?? '');
    $status = sanitize($_POST['status'] ?? 'ACTIVE');
    $editId = sanitize($_POST['id'] ?? '');

    if ($name) {
        try {
            if ($editId) {
                $stmt = $db->prepare("UPDATE bc SET name = ?, code = ?, bci_code = ?, address = ?, email = ?, tel = ?, url = ?, url_1 = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $code, $bciCode, $address, $email, $tel, $url, $url1, $status, $editId]);
                $msg = "Bar Council updated successfully with verification link.";
            } else {
                $stmt = $db->prepare("INSERT INTO bc (name, code, bci_code, address, email, tel, url, url_1, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $code, $bciCode, $address, $email, $tel, $url, $url1, $status]);
                $msg = "New Bar Council added successfully.";
            }
            $action = 'list';
        } catch (Exception $e) {
            $err = "Error saving Bar Council: " . $e->getMessage();
        }
    } else {
        $err = "Bar Council name is required.";
    }
}

// Fetch single for edit
$editItem = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM bc WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

$search = sanitize($_GET['q'] ?? '');
if ($search !== '') {
    $qParam = '%' . $search . '%';
    $councilsStmt = $db->prepare("SELECT * FROM bc WHERE name LIKE ? OR code LIKE ? OR bci_code LIKE ? OR state_code LIKE ? ORDER BY name ASC");
    $councilsStmt->execute([$qParam, $qParam, $qParam, $qParam]);
    $councils = $councilsStmt->fetchAll();
} else {
    $councils = $db->query("SELECT * FROM bc ORDER BY name ASC")->fetchAll();
}

// Metrics
$totalCouncils = count($councils);
$withVerifyLink = 0;
foreach ($councils as $c) {
    if (!empty(trim((string)$c['url_1']))) {
        $withVerifyLink++;
    }
}
$missingVerifyLink = $totalCouncils - $withVerifyLink;
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            State Bar Councils Directory (<?= $totalCouncils ?>)
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Manage State Bar Councils, official websites (<code>url</code>), and online Advocate Verification Portals (<code>url_1</code>).
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('bcFormCard').scrollIntoView({behavior: 'smooth'})">
            <i class="fas fa-plus"></i> Add Bar Council
        </button>
        <a href="advocates.php?type=PENDING" class="btn btn-outline btn-sm">
            <i class="fas fa-clock"></i> Pending Advocates
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
    <div class="stat-box" style="padding: 1rem 1.25rem;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 0.25rem;">Total Councils</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary);"><?= $totalCouncils ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">State Bar Councils</small>
    </div>
    <div class="stat-box" style="padding: 1rem 1.25rem; border-left: 4px solid #059669;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #059669; font-weight: 700; margin-bottom: 0.25rem;">Verify Portals Configured (url_1)</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #059669;"><?= $withVerifyLink ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Active enrollment search links</small>
    </div>
    <div class="stat-box" style="padding: 1rem 1.25rem; border-left: 4px solid #f59e0b;">
        <div style="font-size: 0.75rem; text-transform: uppercase; color: #d97706; font-weight: 700; margin-bottom: 0.25rem;">Pending Verify Links</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #d97706;"><?= $missingVerifyLink ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Councils without url_1</small>
    </div>
</div>

<?php if ($msg): ?>
    <div class="stat-box" style="background: #f0fdf4; border-color: #86efac; color: #166534; font-weight: 600; margin-bottom: 1.5rem;">
        <i class="fas fa-circle-check"></i> <?= sanitize($msg) ?>
    </div>
<?php endif; ?>

<?php if ($err): ?>
    <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
        <i class="fas fa-circle-xmark"></i> <?= sanitize($err) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem;" class="bc-grid">
    <style>
        @media(max-width: 992px) {
            .bc-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Table List -->
    <div>
        <!-- Search bar -->
        <div class="stat-box" style="padding: 0.75rem 1rem; margin-bottom: 1rem;">
            <form method="GET" action="bc.php" style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="text" name="q" class="filter-input" value="<?= sanitize($search) ?>" placeholder="Search Bar Council name, state, or code (e.g. DL, UP, Bihar)..." style="margin: 0;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                <?php if ($search !== ''): ?>
                    <a href="bc.php" class="btn btn-outline btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card" style="padding: 0; overflow: hidden; margin-bottom: 0;">
            <div class="table-responsive" style="border: none;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Bar Council Name</th>
                            <th>Code</th>
                            <th>Advocate Verification Portal (<code>url_1</code>)</th>
                            <th>Contact Info</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($councils)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No Bar Council records found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($councils as $bc): ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--primary);"><?= sanitize($bc['name']) ?></strong>
                                        <?php if (!empty($bc['url'])): ?>
                                            <div>
                                                <a href="<?= sanitize($bc['url']) ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.75rem; color: var(--brand-red); text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                    <i class="fas fa-external-link-alt"></i> Website <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.65rem;"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?= sanitize($bc['code'] ?: '—') ?></code>
                                        <?php if (!empty($bc['state_code'])): ?>
                                            <span style="font-size: 0.7rem; color: var(--text-muted); display: block;"><?= sanitize($bc['state_code']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($bc['url_1'])): ?>
                                            <a href="<?= sanitize($bc['url_1']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 6px; text-decoration: none;" title="Open Online Verification Portal in new tab">
                                                <i class="fas fa-shield-halved"></i> Verify Portal <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.65rem;"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                <i class="fas fa-circle-xmark"></i> Not configured
                                            </span>
                                            <a href="bc.php?action=edit&id=<?= $bc['id'] ?>" style="font-size: 0.7rem; color: var(--brand-red); display: block; margin-top: 0.15rem;">+ Add url_1</a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($bc['email'])): ?>
                                            <div><i class="fas fa-envelope" style="font-size:0.75rem; color: var(--text-muted);"></i> <a href="mailto:<?= sanitize($bc['email']) ?>" style="font-size: 0.75rem; color: inherit;"><?= sanitize($bc['email']) ?></a></div>
                                        <?php endif; ?>
                                        <?php if (!empty($bc['tel'])): ?>
                                            <small style="color: var(--text-muted);"><i class="fas fa-phone" style="font-size:0.75rem;"></i> <?= sanitize($bc['tel']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-verification <?= ($bc['status'] === 'ACTIVE') ? 'badge-verified' : 'badge-suspended' ?>" style="font-size: 0.725rem;">
                                            <?= sanitize($bc['status'] ?: 'ACTIVE') ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="bc.php?action=edit&id=<?= $bc['id'] ?>" class="btn btn-outline btn-sm" title="Edit Bar Council & Verification Link">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create / Edit Form Card -->
    <div class="stat-box" id="bcFormCard" style="padding: 1.5rem; height: fit-content; border-top: 4px solid var(--brand-red);">
        <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1rem;">
            <?= $editItem ? '<i class="fas fa-pen" style="color: var(--brand-red);"></i> Edit Bar Council' : '<i class="fas fa-plus" style="color: var(--brand-red);"></i> Add Bar Council' ?>
        </h3>

        <form action="bc.php" method="POST">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= $editItem['id'] ?>">
            <?php endif; ?>

            <div class="filter-group">
                <label class="filter-label">Bar Council Name *</label>
                <input type="text" name="name" class="filter-input" value="<?= sanitize($editItem['name'] ?? '') ?>" required placeholder="e.g. Bar Council of Delhi">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">State Code</label>
                    <input type="text" name="code" class="filter-input" value="<?= sanitize($editItem['code'] ?? '') ?>" placeholder="e.g. DL">
                </div>
                <div class="filter-group">
                    <label class="filter-label">BCI Code</label>
                    <input type="text" name="bci_code" class="filter-input" value="<?= sanitize($editItem['bci_code'] ?? '') ?>" placeholder="e.g. BCI001">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Official Website URL (<code>url</code>)</label>
                <input type="url" name="url" class="filter-input" value="<?= sanitize($editItem['url'] ?? '') ?>" placeholder="https://delhibarcouncil.com">
                <?php if (!empty($editItem['url'])): ?>
                    <small style="margin-top: 0.25rem; display: block;">
                        <a href="<?= sanitize($editItem['url']) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--brand-red); font-size: 0.75rem;">
                            <i class="fas fa-external-link-alt"></i> Open Website in new tab &rsaquo;
                        </a>
                    </small>
                <?php endif; ?>
            </div>

            <!-- PROMINENT VERIFICATION PORTAL FIELD (url_1) -->
            <div class="filter-group" style="background: #fffdf5; border: 1.5px solid #fcd34d; padding: 0.85rem; border-radius: 8px;">
                <label class="filter-label" style="color: #92400e; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fas fa-shield-halved" style="color: #dc2626;"></i> Advocate Verification Portal URL (<code>url_1</code>)</span>
                    <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; font-size: 0.65rem;">opens in new tab</span>
                </label>
                <input type="url" name="url_1" class="filter-input" value="<?= sanitize($editItem['url_1'] ?? '') ?>" placeholder="https://delhibarcouncil.com/bcd/enrolment_index.php" style="background: #fff;">
                <small style="color: #78350f; font-size: 0.725rem; display: block; margin-top: 0.35rem; line-height: 1.3;">
                    Official Bar Council online search URL. Used in advocate verification screens to verify enrollment numbers in a new tab.
                </small>
                <?php if (!empty($editItem['url_1'])): ?>
                    <div style="margin-top: 0.5rem; padding-top: 0.4rem; border-top: 1px dashed #fcd34d;">
                        <a href="<?= sanitize($editItem['url_1']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="font-size: 0.725rem; padding: 0.2rem 0.5rem; color: #b91c1c; border-color: #fca5a5;">
                            <i class="fas fa-arrow-up-right-from-square"></i> Test url_1 Link in New Tab
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <div class="filter-group">
                    <label class="filter-label">Email Address</label>
                    <input type="email" name="email" class="filter-input" value="<?= sanitize($editItem['email'] ?? '') ?>" placeholder="secretary@barcouncil.org">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Telephone</label>
                    <input type="text" name="tel" class="filter-input" value="<?= sanitize($editItem['tel'] ?? '') ?>" placeholder="011-2338...">
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Headquarters Address</label>
                <textarea name="address" class="filter-input" rows="2" placeholder="Bar Council Bhavan..."><?= sanitize($editItem['address'] ?? '') ?></textarea>
            </div>

            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="ACTIVE" <?= ($editItem['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                    <option value="INACTIVE" <?= ($editItem['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                <?= $editItem ? '<i class="fas fa-save"></i> Save Changes' : '<i class="fas fa-plus"></i> Create Bar Council' ?>
            </button>
            <?php if ($editItem): ?>
                <a href="bc.php" class="btn btn-outline btn-sm" style="width: 100%; margin-top: 0.5rem; text-align: center; display: block;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
