<?php
// admin/api_tools.php - External API Tools & Data Lookups (IFSC, PIN Code, Bare Acts, NIC)
$pageTitle = "Legal & Financial API Tools";
require_once __DIR__ . '/includes/admin_header.php';

$apiKey = defined('OLAW_API_KEY') ? OLAW_API_KEY : 'OLAW_D776A66967200383A932';
$apiUrl = defined('OLAW_API_URL') ? OLAW_API_URL : 'https://olaw.in/api.php';

// Helper function to call OLAW API
function callOlawApi($params) {
    global $apiUrl, $apiKey;
    $params['api_key'] = $apiKey;
    $url = $apiUrl . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MyAdvocate-Admin/2.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['status' => 'error', 'message' => 'Network error: ' . $error];
    }
    
    $json = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $json;
    }
    return ['status' => 'error', 'message' => 'Invalid API response format (HTTP ' . $httpCode . ')', 'raw' => $response];
}

// Fetch live API status & quota
$apiStatus = null;
try {
    $ch = curl_init($apiUrl . '?check_usage=' . urlencode($apiKey));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    $apiStatus = json_decode($res, true);
} catch (Exception $e) {
    $apiStatus = null;
}

$tab = sanitize($_GET['tab'] ?? 'ifsc');
$query = sanitize($_GET['q'] ?? '');

$searchResult = null;
$searched = false;

if (!empty($query)) {
    $searched = true;
    if ($tab === 'ifsc') {
        $cleanIfsc = strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $query)));
        $searchResult = callOlawApi(['action' => 'bank-ifsc', 'ifsc' => $cleanIfsc]);
    } elseif ($tab === 'pincode') {
        $cleanPin = trim(preg_replace('/[^0-9]/', '', $query));
        $searchResult = callOlawApi(['action' => 'pincode', 'pincode' => $cleanPin]);
    } elseif ($tab === 'nic') {
        $cleanNic = trim(preg_replace('/[^0-9]/', '', $query));
        $searchResult = callOlawApi(['action' => 'nic', 'code' => $cleanNic]);
    } elseif ($tab === 'passport') {
        $searchResult = callOlawApi(['action' => 'passport', 'q' => trim($query)]);
    } elseif ($tab === 'acts') {
        // Query local Bare Acts database
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT * FROM acts WHERE name LIKE ? OR details LIKE ? ORDER BY id DESC LIMIT 50");
            $like = '%' . $query . '%';
            $stmt->execute([$like, $like]);
            $actsData = $stmt->fetchAll();
            $searchResult = ['status' => 'success', 'data' => $actsData, 'count' => count($actsData)];
        } catch (Exception $e) {
            $searchResult = ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800;">
            Legal, Bank IFSC & PIN Code API
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Real-time verification & data intelligence powered by OLAW RESTful API.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <span class="badge-verification badge-verified" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
            <i class="fas fa-circle-check"></i> API Connected
        </span>
        <a href="settings.php" class="btn btn-outline btn-sm"><i class="fas fa-gear"></i> Settings</a>
    </div>
</div>

<!-- API Health & Status Card -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem; background: #ffffff; border: 1px solid var(--border-color);">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: center;">
        <div>
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">API Gateway & Endpoint</div>
            <div style="font-weight: 700; color: var(--primary); font-size: 0.95rem; margin-top: 2px;">
                <code style="background: #F1F5F9; padding: 2px 8px; border-radius: 6px; color: var(--brand-red); font-size: 0.85rem;"><?= htmlspecialchars($apiUrl) ?></code>
            </div>
        </div>
        <div>
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Active API Key</div>
            <div style="font-weight: 700; color: var(--primary); font-size: 0.9rem; margin-top: 2px; display: flex; align-items: center; gap: 6px;">
                <code style="background: #FEF3C7; color: #92400E; padding: 2px 8px; border-radius: 6px; font-size: 0.82rem; font-weight: 800;"><?= substr($apiKey, 0, 8) ?>••••••••••••<?= substr($apiKey, -4) ?></code>
                <button type="button" class="btn btn-outline btn-sm" style="padding: 0.15rem 0.45rem; font-size: 0.75rem;" onclick="navigator.clipboard.writeText('<?= $apiKey ?>'); alert('API Key copied to clipboard!');" title="Copy API Key"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        <div>
            <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Subscription Status</div>
            <div style="margin-top: 4px; display: flex; align-items: center; gap: 8px;">
                <?php if ($apiStatus && ($apiStatus['status'] ?? '') === 'success'): ?>
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #166534; font-weight: 700; font-size: 0.875rem;">
                        <i class="fas fa-circle" style="color: #22C55E; font-size: 0.65rem;"></i> Active (<?= htmlspecialchars($apiStatus['data']['website_name'] ?? 'My Advocate') ?>)
                    </span>
                    <small style="color: var(--text-muted);">| Usage: <?= (int)($apiStatus['data']['usage_count'] ?? 0) ?></small>
                <?php else: ?>
                    <span style="color: #166534; font-weight: 700; font-size: 0.875rem;"><i class="fas fa-circle-check" style="color:#22C55E;"></i> Ready</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); flex-wrap: wrap;">
    <a href="api_tools.php?tab=ifsc" class="btn <?= $tab === 'ifsc' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-building-columns"></i> Bank IFSC & MICR
    </a>
    <a href="api_tools.php?tab=pincode" class="btn <?= $tab === 'pincode' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-map-pin"></i> Postal PIN Code
    </a>
    <a href="api_tools.php?tab=acts" class="btn <?= $tab === 'acts' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-book-journal-whills"></i> Bare Acts & Codes
    </a>
    <a href="api_tools.php?tab=nic" class="btn <?= $tab === 'nic' ? 'btn-primary' : 'btn-outline' ?>" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; margin-bottom: -2px;">
        <i class="fas fa-briefcase"></i> NIC 2025 Activity
    </a>
</div>

<!-- Single Line Search Bar -->
<div class="stat-box" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form action="api_tools.php" method="GET" style="display: grid; grid-template-columns: 3fr auto; gap: 1rem; align-items: flex-end;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div>
            <label class="filter-label">
                <?php if ($tab === 'ifsc'): ?>
                    Enter 11-Digit Bank IFSC Code (e.g. SBIN0000001, HDFC0000001, ICIC0000001, PUNB0000100)
                <?php elseif ($tab === 'pincode'): ?>
                    Enter 6-Digit Indian Postal PIN Code (e.g. 110001, 800001, 400001, 700001)
                <?php elseif ($tab === 'acts'): ?>
                    Search Bare Act or Keyword (e.g. Bharatiya Nyaya Sanhita, BNSS, BSA, Constitution, Evidence)
                <?php elseif ($tab === 'nic'): ?>
                    Enter 5-Digit National Industrial Classification (NIC) Code (e.g. 62011, 69100, 64191)
                <?php endif; ?>
            </label>
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" 
                   placeholder="<?= $tab === 'ifsc' ? 'e.g. SBIN0000001' : ($tab === 'pincode' ? 'e.g. 110001' : ($tab === 'nic' ? 'e.g. 62011' : 'Search keyword...')) ?>" 
                   class="filter-input" required style="font-weight: 600; letter-spacing: 0.03em;">
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary" style="height: 40px; white-space: nowrap;">
                <i class="fas fa-magnifying-glass"></i> Lookup API
            </button>
            <?php if (!empty($query)): ?>
                <a href="api_tools.php?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline" style="height: 40px; white-space: nowrap;">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Search Results Display Area -->
<?php if ($searched): ?>
    <?php if ($tab === 'ifsc'): ?>
        <?php if (!empty($searchResult) && ($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
            $bank = $searchResult['data'];
        ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-building-columns text-primary"></i> <?= htmlspecialchars($bank['bank'] ?? 'Bank Details') ?>
                    </h3>
                    <span class="badge-verification badge-verified" style="font-size: 0.8rem;">
                        <i class="fas fa-shield-check"></i> Verified RBI IFSC Record
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                    <div>
                        <table class="admin-table" style="border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <tr>
                                <th style="width: 140px;">IFSC Code</th>
                                <td><strong style="color: var(--brand-red); font-size: 1.1rem; letter-spacing: 0.05em;"><?= htmlspecialchars($bank['ifsc'] ?? '—') ?></strong></td>
                            </tr>
                            <tr>
                                <th>MICR Code</th>
                                <td><strong><?= htmlspecialchars($bank['micr'] ?? '—') ?></strong></td>
                            </tr>
                            <tr>
                                <th>Bank Name</th>
                                <td><strong><?= htmlspecialchars($bank['bank'] ?? '—') ?></strong></td>
                            </tr>
                            <tr>
                                <th>Branch Name</th>
                                <td><?= htmlspecialchars($bank['branch'] ?? '—') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table class="admin-table" style="border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                            <tr>
                                <th style="width: 140px;">Branch Address</th>
                                <td><?= htmlspecialchars($bank['address'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>City / District</th>
                                <td><?= htmlspecialchars($bank['city1'] ?: ($bank['city2'] ?? '—')) ?></td>
                            </tr>
                            <tr>
                                <th>State / UT</th>
                                <td><strong><?= htmlspecialchars($bank['state'] ?? '—') ?></strong></td>
                            </tr>
                            <tr>
                                <th>Contact / Phone</th>
                                <td><?= htmlspecialchars($bank['phone'] ? ($bank['stdcode'] ? $bank['stdcode'] . '-' : '') . $bank['phone'] : '—') ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 1.25rem; pt-3; border-top: 1px dashed var(--border-color); padding-top: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <small style="color: var(--text-muted);"><i class="fas fa-clock"></i> Record Last Updated: <?= htmlspecialchars($bank['updated_at'] ?? 'Live') ?></small>
                    <button type="button" class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(JSON.stringify(<?= htmlspecialchars(json_encode($bank)) ?>, null, 2)); alert('Bank details JSON copied!');">
                        <i class="fas fa-copy"></i> Copy Bank JSON
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
                <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($searchResult['message'] ?? 'No bank details found for IFSC: ' . $query) ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'pincode'): ?>
        <?php if (!empty($searchResult) && ($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
            $pinList = is_array($searchResult['data']) ? $searchResult['data'] : [$searchResult['data']];
        ?>
            <div class="admin-card" style="padding: 0; overflow: hidden;">
                <div class="admin-card-header" style="padding: 1.25rem; margin: 0;">
                    <h3 class="admin-card-title">
                        <i class="fas fa-map-pin text-primary"></i> Post Offices & Localities in PIN Code: <span style="color: var(--brand-red);"><?= htmlspecialchars($query) ?></span> (<?= count($pinList) ?> Locations)
                    </h3>
                </div>
                <div class="table-responsive" style="border: none;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Locality / Post Office</th>
                                <th>Office Name</th>
                                <th>PIN Code</th>
                                <th>District</th>
                                <th>State / UT</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pinList as $idx => $p): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($p['locality_name'] ?? '—') ?></strong></td>
                                    <td><?= htmlspecialchars($p['office_name'] ?? '—') ?></td>
                                    <td><code style="background: #FEF3C7; color: #92400E; padding: 2px 6px; border-radius: 4px; font-weight: 700;"><?= htmlspecialchars($p['pincode'] ?? '—') ?></code></td>
                                    <td><?= htmlspecialchars($p['district_name'] ?? '—') ?></td>
                                    <td><strong><?= htmlspecialchars($p['state_name'] ?? '—') ?></strong></td>
                                    <td><span class="badge-verification badge-verified"><?= htmlspecialchars($p['status'] ?? 'ACTIVE') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
                <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($searchResult['message'] ?? 'No locations found for PIN Code: ' . $query) ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'acts'): ?>
        <?php if (!empty($searchResult) && ($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
            $actsList = $searchResult['data'];
        ?>
            <div class="admin-card" style="padding: 0; overflow: hidden;">
                <div class="admin-card-header" style="padding: 1.25rem; margin: 0;">
                    <h3 class="admin-card-title">
                        <i class="fas fa-book-journal-whills text-primary"></i> Bare Acts Matching: "<?= htmlspecialchars($query) ?>" (<?= count($actsList) ?> Acts)
                    </h3>
                    <a href="acts.php?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Bare Act</a>
                </div>
                <div class="table-responsive" style="border: none;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Act Name</th>
                                <th>Year</th>
                                <th>English Bare Act</th>
                                <th>Hindi Bare Act</th>
                                <th>Official Portal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actsList as $act): ?>
                                <tr>
                                    <td>#<?= $act['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($act['name'] ?? 'Unnamed Act') ?></strong></td>
                                    <td><span class="badge-verification badge-claimed"><?= htmlspecialchars($act['year'] ?? '—') ?></span></td>
                                    <td>
                                        <?php if (!empty($act['english'])): ?>
                                            <a href="<?= htmlspecialchars($act['english']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf text-danger"></i> PDF</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($act['hindi'])): ?>
                                            <a href="<?= htmlspecialchars($act['hindi']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf text-danger"></i> Hindi PDF</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($act['official'])): ?>
                                            <a href="<?= htmlspecialchars($act['official']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-globe"></i> Portal</a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="acts.php?action=edit&id=<?= $act['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
                <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($searchResult['message'] ?? 'No Bare Acts found matching: ' . $query) ?>
            </div>
        <?php endif; ?>

    <?php elseif ($tab === 'nic'): ?>
        <?php if (!empty($searchResult) && ($searchResult['status'] ?? '') === 'success' && !empty($searchResult['data'])): 
            $nic = $searchResult['data'];
            $sec = $nic['section'] ?? [];
            $div = $nic['division'] ?? [];
            $cls = $nic['class'] ?? [];
        ?>
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">
                        <i class="fas fa-briefcase text-primary"></i> NIC 2025 Classification: <?= htmlspecialchars($query) ?>
                    </h3>
                    <span class="badge-verification badge-verified">National Industrial Classification</span>
                </div>
                
                <table class="admin-table">
                    <?php if (!empty($sec)): ?>
                        <tr>
                            <th style="width: 180px;">Section <?= htmlspecialchars($sec['code'] ?? '') ?></th>
                            <td><strong><?= htmlspecialchars($sec['title'] ?? '—') ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($div)): ?>
                        <tr>
                            <th>Division <?= htmlspecialchars($div['code'] ?? '') ?></th>
                            <td><?= htmlspecialchars($div['title'] ?? '—') ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if (!empty($cls)): ?>
                        <tr>
                            <th>Class / Group</th>
                            <td><?= htmlspecialchars($cls['title'] ?? ($cls['description'] ?? '—')) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php else: ?>
            <div class="stat-box" style="background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; margin-bottom: 1.5rem;">
                <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($searchResult['message'] ?? 'No NIC classification found for code: ' . $query) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
