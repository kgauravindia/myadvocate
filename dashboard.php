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

// Record advocate activity in advocate_data table
recordAdvocateSeen($advId);

$isLocked = (
    (!empty($advocate['type']) && strtoupper($advocate['type']) === 'ACTIVE') ||
    (!empty($advocate['status']) && strtoupper($advocate['status']) === 'ACTIVE') ||
    in_array(strtolower($advocate['plan_type'] ?? ''), ['registered', 'verified'])
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? 'update_profile');

    if ($action === 'upload_photo') {
        if (!empty($_FILES['photo']['name'])) {
            $res = uploadAdvocatePhoto($_FILES['photo'], $advId);
            if ($res['success']) {
                $msg = "Profile photo updated successfully.";
                recordAdvocateProfileUpdate($advId, ['photo' => 'Uploaded new profile photograph'], 'advocate');
                $stmt->execute([$advId]);
                $advocate = $stmt->fetch();
            } else {
                $error = $res['error'];
            }
        } else {
            $error = "Please choose a photo file to upload.";
        }
    } elseif ($action === 'upload_id_proof') {
        if (!empty($_FILES['id_proof']['name'])) {
            $res = uploadAdvocateIdProof($_FILES['id_proof'], $advId);
            if ($res['success']) {
                $msg = "ID proof document uploaded successfully. Verification is pending review.";
                recordAdvocateProfileUpdate($advId, ['id_proof' => 'Uploaded ID proof document'], 'advocate');
                $stmt->execute([$advId]);
                $advocate = $stmt->fetch();
            } else {
                $error = $res['error'];
            }
        } else {
            $error = "Please choose an ID proof file to upload.";
        }
    } elseif ($action === 'claim_handle') {
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
                    recordAdvocateProfileUpdate($advId, ['public_url' => 'Claimed custom handle @' . $cleanHandle], 'advocate');
                    
                    // Refresh data
                    $stmt->execute([$advId]);
                    $advocate = $stmt->fetch();
                }
            } catch (Exception $e) {
                $error = "Could not save handle. Please try again.";
            }
        }
    } elseif ($action === 'submit_change_request') {
        $reqName = sanitize($_POST['req_name'] ?? '');
        $reqMobile = sanitize($_POST['req_mobile'] ?? '');
        $reqENo = sanitize($_POST['req_e_no'] ?? '');
        $reqEYear = sanitize($_POST['req_e_year'] ?? '');
        $reqBcId = !empty($_POST['req_bc_id']) ? (int)$_POST['req_bc_id'] : null;
        $reqEDate = sanitize($_POST['req_e_date'] ?? '');
        $reason = sanitize($_POST['reason'] ?? '');

        if (empty($reqName) && empty($reqMobile) && empty($reqENo) && empty($reqEYear) && empty($reqBcId) && empty($reqEDate)) {
            $error = "Please specify at least one credential field you wish to change.";
        } elseif (empty($reason)) {
            $error = "Please provide a reason / justification for the change request.";
        } else {
            $docFilename = null;
            if (!empty($_FILES['change_doc']['name'])) {
                $uploadRes = uploadChangeRequestDoc($_FILES['change_doc'], $advId);
                if ($uploadRes['success']) {
                    $docFilename = $uploadRes['filename'];
                } else {
                    $error = $uploadRes['error'];
                }
            }

            if (empty($error)) {
                try {
                    $stmtReq = $db->prepare("INSERT INTO advocate_change_requests 
                        (advocate_id, requested_name, requested_mobile, requested_e_no, requested_e_year, requested_bc_id, requested_e_date, reason, document_path, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())");
                    $stmtReq->execute([
                        $advId,
                        $reqName ?: null,
                        $reqMobile ?: null,
                        $reqENo ?: null,
                        $reqEYear ?: null,
                        $reqBcId ?: null,
                        $reqEDate ?: null,
                        $reason,
                        $docFilename
                    ]);
                    $msg = "Your change request has been submitted to the Admin for verification review. You can track status below.";
                } catch (Exception $e) {
                    $error = "Failed to submit change request: " . $e->getMessage();
                }
            }
        }
    } else {
        // General profile update
        $isLocked = (
            (!empty($advocate['type']) && strtoupper($advocate['type']) === 'ACTIVE') ||
            (!empty($advocate['status']) && strtoupper($advocate['status']) === 'ACTIVE') ||
            in_array(strtolower($advocate['plan_type'] ?? ''), ['registered', 'verified'])
        );

        if ($isLocked) {
            // Preserved locked credentials for Active / Verified advocates
            $name = $advocate['name'];
            $mobile = $advocate['mobile'];
            $eNo = $advocate['e_no'];
            $eYear = $advocate['e_year'];
            $bcId = $advocate['bc_id'];
            $eDate = $advocate['e_date'];
        } else {
            $name = sanitize($_POST['name'] ?? '');
            $mobile = sanitize($_POST['mobile'] ?? '');
            $eNo = sanitize($_POST['e_no'] ?? '');
            $bcId = sanitize($_POST['bc_id'] ?? '');
            $eDate = sanitize($_POST['e_date'] ?? '');
            $eYear = sanitize($_POST['e_year'] ?? '');
            if (empty($eYear) && !empty($eDate)) {
                if (preg_match('/(\d{4})/', $eDate, $ym)) {
                    $eYear = $ym[1];
                }
            }
        }

        // Editable fields
        $rName = sanitize($_POST['r_name'] ?? '');
        $gender = sanitize($_POST['gender'] ?? 'Male');
        $dob = sanitize($_POST['dob'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $altMobile = sanitize($_POST['alt_mobile'] ?? '');

        // Practicing Courts
        $practicingCourtsArr = isset($_POST['practicing_courts']) && is_array($_POST['practicing_courts']) ? array_map('sanitize', $_POST['practicing_courts']) : [];
        $otherCourt = sanitize($_POST['other_court'] ?? '');
        if (!empty($otherCourt)) {
            $practicingCourtsArr[] = $otherCourt;
        }
        $practicingCourtsStr = implode(', ', array_filter(array_unique($practicingCourtsArr)));
        $court = sanitize($_POST['court'] ?? ($practicingCourtsArr[0] ?? ($advocate['court'] ?? 'CC')));

        $practiceArea = sanitize($_POST['practice_area'] ?? '');
        $baCode = sanitize($_POST['ba_code'] ?? '');
        $sittingAddress = sanitize($_POST['sitting_address'] ?? '');

        // Address details
        $address = sanitize($_POST['address'] ?? '');
        $mohallaVillage = sanitize($_POST['mohalla_village'] ?? '');
        $stateCode = sanitize($_POST['state_code'] ?? ($advocate['state_code'] ?? ''));
        $districtCode = sanitize($_POST['district_code'] ?? ($advocate['district_code'] ?? ''));
        $pincode = sanitize($_POST['pincode'] ?? '');

        // Privacy & Bio
        $mobileVis = sanitize($_POST['mobile_visibility'] ?? 'REGISTERED');
        $emailVis = sanitize($_POST['email_visibility'] ?? 'REGISTERED');
        $addrVis = sanitize($_POST['address_visibility'] ?? 'PRIVATE');
        $about = sanitize($_POST['about'] ?? '');

        try {
            $updateStmt = $db->prepare("UPDATE advocate SET 
                name = ?, r_name = ?, gender = ?, dob = ?, email = ?, mobile = ?, alt_mobile = ?,
                e_no = ?, bc_id = ?, e_date = ?, e_year = ?, court = ?, practicing_courts = ?,
                practice_area = ?, ba_code = ?, sitting_address = ?,
                address = ?, mohalla_village = ?, state_code = ?, district_code = ?, pincode = ?,
                mobile_visibility = ?, email_visibility = ?, address_visibility = ?, about = ?,
                updated_at = NOW() 
                WHERE id = ?");
            $updateStmt->execute([
                $name, $rName, $gender, $dob, $email, $mobile, $altMobile,
                $eNo, $bcId, $eDate, $eYear, $court, $practicingCourtsStr,
                $practiceArea, $baCode, $sittingAddress,
                $address, $mohallaVillage, $stateCode, $districtCode, $pincode,
                $mobileVis, $emailVis, $addrVis, $about,
                $advId
            ]);
            $msg = "Profile details successfully updated and saved.";

            // Record what was updated in advocate_data
            $changedFields = [];
            if (($advocate['name'] ?? '') !== $name) $changedFields['name'] = $name;
            if (($advocate['r_name'] ?? '') !== $rName) $changedFields['r_name'] = $rName;
            if (($advocate['gender'] ?? '') !== $gender) $changedFields['gender'] = $gender;
            if (($advocate['dob'] ?? '') !== $dob) $changedFields['dob'] = $dob;
            if (($advocate['email'] ?? '') !== $email) $changedFields['email'] = $email;
            if (($advocate['mobile'] ?? '') !== $mobile) $changedFields['mobile'] = $mobile;
            if (($advocate['alt_mobile'] ?? '') !== $altMobile) $changedFields['alt_mobile'] = $altMobile;
            if (($advocate['practicing_courts'] ?? '') !== $practicingCourtsStr) $changedFields['practicing_courts'] = $practicingCourtsStr;
            if (($advocate['practice_area'] ?? '') !== $practiceArea) $changedFields['practice_area'] = $practiceArea;
            if (($advocate['ba_code'] ?? '') !== $baCode) $changedFields['ba_code'] = $baCode;
            if (($advocate['sitting_address'] ?? '') !== $sittingAddress) $changedFields['sitting_address'] = $sittingAddress;
            if (($advocate['address'] ?? '') !== $address) $changedFields['address'] = $address;
            if (($advocate['mohalla_village'] ?? '') !== $mohallaVillage) $changedFields['mohalla_village'] = $mohallaVillage;
            if (($advocate['state_code'] ?? '') !== $stateCode) $changedFields['state_code'] = $stateCode;
            if (($advocate['district_code'] ?? '') !== $districtCode) $changedFields['district_code'] = $districtCode;
            if (($advocate['pincode'] ?? '') !== $pincode) $changedFields['pincode'] = $pincode;
            if (($advocate['about'] ?? '') !== $about) $changedFields['about'] = 'Updated About & Practice Overview';
            if (empty($changedFields)) {
                $changedFields = ['Profile Details (Saved)'];
            }
            recordAdvocateProfileUpdate($advId, $changedFields, 'advocate');

            if (!empty($_FILES['photo']['name'])) {
                $resPhoto = uploadAdvocatePhoto($_FILES['photo'], $advId);
                if (!$resPhoto['success']) {
                    $error = ($error ? $error . ' ' : '') . $resPhoto['error'];
                }
            }

            if (!empty($_FILES['id_proof']['name'])) {
                $resId = uploadAdvocateIdProof($_FILES['id_proof'], $advId);
                if (!$resId['success']) {
                    $error = ($error ? $error . ' ' : '') . $resId['error'];
                }
            }
            
            // Refresh advocate data
            $stmt->execute([$advId]);
            $advocate = $stmt->fetch();
        } catch (Exception $e) {
            $error = "Error saving profile changes: " . $e->getMessage();
        }
    }
}

$isLocked = (
    (!empty($advocate['type']) && strtoupper($advocate['type']) === 'ACTIVE') ||
    (!empty($advocate['status']) && strtoupper($advocate['status']) === 'ACTIVE') ||
    in_array(strtolower($advocate['plan_type'] ?? ''), ['registered', 'verified'])
);

$currentHandle = ltrim(trim($advocate['public_url'] ?? ''), '@');
$defaultSlug = generateAdvocateSlug($advocate);
$publicProfileUrl = getAdvocateUrl($advocate);
$handleUrl = $currentHandle ? APP_URL . "/@" . urlencode($currentHandle) : APP_URL . "/profile.php?id=" . $advocate['id'];
$advIndex = calculateAdvocateIndex($advocate);

$states = getStates();
$districts = !empty($advocate['state_code']) ? getDistrictsByState($advocate['state_code']) : [];
$barCouncils = getBarCouncils();
$barAssociations = getBarAssociations($advocate['state_code'] ?? '', $advocate['district_code'] ?? '');
$practiceAreasList = getAdvocatePracticeAreas();

$advChangeRequests = [];
try {
    $crStmt = $db->prepare("SELECT * FROM advocate_change_requests WHERE advocate_id = ? ORDER BY id DESC LIMIT 10");
    $crStmt->execute([$advId]);
    $advChangeRequests = $crStmt->fetchAll();
} catch (Exception $e) {}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <span>Dashboard</span> &bull; 
        <span><?= sanitize($advocate['name']) ?></span>
    </nav>

    <!-- Header Banner -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
                <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: #fee2e2; color: var(--brand-red); padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700;">
                    <i class="fas fa-id-card"></i> Enr: <?= sanitize($advocate['e_no']) ?><?= !empty($advocate['e_year']) ? '/' . sanitize($advocate['e_year']) : '' ?>
                </span>
                <?= renderAdvocateIndexBadge($advIndex, 'pill') ?>
            </div>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 800; color: var(--primary); margin-bottom: 0.25rem;">
                Advocate Dashboard
            </h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0;">
                Welcome back, <strong><?= sanitize($advocate['name']) ?></strong>! Manage your profile, custom URL, and verified details.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; width: auto;" class="mobile-w-full">
            <a href="<?= $publicProfileUrl ?>" class="btn btn-outline-primary btn-sm" target="_blank" style="flex: 1; text-align: center; justify-content: center; min-height: 38px;">
                <i class="fas fa-eye me-1"></i> View Live Profile
            </a>
            <a href="logout.php" class="btn btn-outline btn-sm" style="flex: 0; min-height: 38px; justify-content: center;">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
        <div class="stat-box" style="margin-bottom: 1.5rem; background: #f0fdf4; border-color: #86efac; color: #166534; font-weight: 600; display: flex; align-items: center; gap: 0.65rem; padding: 1rem 1.25rem; border-radius: var(--radius-md);">
            <i class="fas fa-circle-check" style="color: #16a34a; font-size: 1.25rem; flex-shrink: 0;"></i>
            <span style="font-size: 0.9375rem;"><?= sanitize($msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="stat-box" style="margin-bottom: 1.5rem; background: #fef2f2; border-color: #fca5a5; color: #b91c1c; font-weight: 600; display: flex; align-items: center; gap: 0.65rem; padding: 1rem 1.25rem; border-radius: var(--radius-md);">
            <i class="fas fa-triangle-exclamation" style="font-size: 1.25rem; flex-shrink: 0;"></i>
            <span style="font-size: 0.9375rem;"><?= sanitize($error) ?></span>
        </div>
    <?php endif; ?>

    <!-- ADVOCATE INDEX COMPLETION HERO CARD -->
    <div class="adv-index-meter-box" style="margin-bottom: 1.75rem; border-top: 4px solid <?= $advIndex['color'] ?>; background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 1.25rem 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                    <span class="adv-index-badge <?= $advIndex['badge_class'] ?>" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                        <i class="fas fa-bolt"></i> <?= $advIndex['grade'] ?> &bull; <?= $advIndex['label'] ?>
                    </span>
                    <span style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted);">
                        Profile Completeness
                    </span>
                </div>
                <h2 style="font-size: clamp(1.15rem, 3vw, 1.35rem); font-weight: 800; color: var(--primary); margin: 0;">
                    Profile is <span style="color: <?= $advIndex['color'] ?>;"><?= $advIndex['percent'] ?>% Complete</span>
                </h2>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 1.75rem; font-weight: 900; color: <?= $advIndex['color'] ?>; line-height: 1;">
                    <?= $advIndex['percent'] ?>%
                </div>
                <small style="color: var(--text-muted); font-weight: 600; font-size: 0.75rem;">
                    <?= $advIndex['completed_count'] ?> of <?= $advIndex['total_count'] ?> Verified
                </small>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="adv-index-meter-bar-bg" style="height: 10px; margin: 0.5rem 0 1rem; border-radius: 9999px; background: #f1f5f9; overflow: hidden;">
            <div class="adv-index-meter-bar-fill" style="width: <?= $advIndex['percent'] ?>%; height: 100%; border-radius: 9999px; background: linear-gradient(90deg, <?= $advIndex['color'] ?> 0%, var(--brand-gold) 100%); transition: width 0.4s ease;"></div>
        </div>

        <!-- Checklist Grid -->
        <div class="adv-index-checklist" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.5rem;">
            <?php foreach ($advIndex['breakdown'] as $b): ?>
                <div class="adv-index-item <?= $b['completed'] ? 'completed' : 'pending' ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.8125rem; background: <?= $b['completed'] ? '#f0fdf4' : '#f8fafc' ?>; border: 1px solid <?= $b['completed'] ? '#bbf7d0' : '#e2e8f0' ?>; color: <?= $b['completed'] ? '#166534' : '#64748b' ?>;">
                    <div style="display: flex; align-items: center; gap: 0.4rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <i class="fas <?= $b['completed'] ? 'fa-circle-check text-success' : 'fa-circle-exclamation' ?>"></i>
                        <span title="<?= sanitize($b['tip']) ?>" style="font-weight: 600;"><?= sanitize($b['title']) ?></span>
                    </div>
                    <span style="font-weight: 700; font-size: 0.75rem; margin-left: 0.25rem; white-space: nowrap;">
                        <?= $b['completed'] ? '+' . $b['weight'] . '%' : 'Missing' ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 1. CLAIM PUBLIC URL HERO CARD -->
    <div class="stat-box" style="padding: 1.5rem; margin-bottom: 2rem; border-top: 4px solid var(--brand-gold); background: linear-gradient(135deg, rgba(255, 251, 235, 0.75) 0%, rgba(254, 242, 242, 0.75) 100%); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
            <div style="max-width: 620px; width: 100%;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem; flex-wrap: wrap;">
                    <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                        <i class="fas fa-link"></i> Public Profile URL
                    </span>
                    <?php if ($currentHandle): ?>
                        <span style="font-size: 0.75rem; font-weight: 700; color: #15803d; background: #dcfce7; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm);">
                            <i class="fas fa-check-circle"></i> Active: @<?= sanitize($currentHandle) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem;">
                    <?= $currentHandle ? 'Your Unique Profile Handle' : 'Claim Your Unique Public Profile URL' ?>
                </h2>
                <p style="color: var(--text-main); font-size: 0.875rem; line-height: 1.5; margin-bottom: 0.85rem;">
                    Share your personalized profile link on visiting cards, court documents, and WhatsApp.
                </p>

                <?php if ($currentHandle): ?>
                    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.65rem 0.85rem; margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="font-family: monospace; font-size: 0.9rem; font-weight: 700; color: var(--primary); word-break: break-all;">
                            <span style="color: var(--text-muted);">https://myadv.in/@</span><span style="color: var(--brand-red);"><?= sanitize($currentHandle) ?></span>
                        </div>
                        <div style="display: flex; gap: 0.5rem; width: 100%; max-width: 220px;" class="handle-action-row">
                            <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="https://myadv.in/@<?= sanitize($currentHandle) ?>" style="flex: 1; justify-content: center; font-size: 0.8125rem;">
                                <i class="fas fa-link me-1"></i> Copy
                            </button>
                            <a href="<?= $publicProfileUrl ?>" target="_blank" class="btn btn-primary btn-sm" style="flex: 1; justify-content: center; font-size: 0.8125rem;">
                                <i class="fas fa-arrow-up-right-from-square me-1"></i> Open
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Claim / Edit Form -->
                <form action="dashboard" method="POST">
                    <input type="hidden" name="action" value="claim_handle">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <div style="position: relative; flex: 1; min-width: 180px;">
                            <input type="text" name="handle" class="filter-input" value="<?= sanitize($currentHandle ?: $defaultSlug) ?>" placeholder="yourhandle (e.g. kumargaurav)" required pattern="[a-zA-Z0-9._-]{3,40}" style="font-weight: 600; min-height: 42px;">
                        </div>
                        <button type="submit" class="btn btn-primary btn-md" style="font-weight: 700; min-height: 42px; padding: 0 1.25rem;">
                            <i class="fas fa-badge-check me-1"></i> <?= $currentHandle ? 'Update Handle' : 'Claim Handle' ?>
                        </button>
                    </div>
                    <small style="display: block; margin-top: 0.35rem; color: var(--text-muted); font-size: 0.75rem;">
                        Allowed: letters, numbers, hyphens, underscores (3-40 chars).
                    </small>
                </form>
            </div>

            <!-- Share Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; width: 100%; max-width: 260px; text-align: center;" class="share-box-mobile">
                <div style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">
                    Quick Share Profile
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php $shareLink = $currentHandle ? "https://myadv.in/@" . urlencode($currentHandle) : "https://myadv.in/profile.php?id=" . $advocate['id']; ?>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Connect with Advocate " . $advocate['name'] . " on My Advocate: " . $shareLink) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="width: 100%; justify-content: center; color: #16a34a; border-color: #86efac; min-height: 38px;">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= sanitize($shareLink) ?>" style="width: 100%; justify-content: center; min-height: 38px;">
                        <i class="fas fa-copy me-1"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. MAIN DASHBOARD CONTENT GRID -->
    <div class="dash-layout-grid">
        <style>
            .dash-layout-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 1.75rem;
                align-items: start;
            }
            .form-grid-2 {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            .court-checkbox-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 0.65rem;
                background: #ffffff;
                padding: 0.85rem 1rem;
                border-radius: var(--radius-md);
                border: 1px solid var(--border-color);
            }
            .court-check-card {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
                padding: 0.4rem 0.5rem;
                border-radius: 6px;
                transition: background 0.2s;
            }
            .court-check-card:hover {
                background: #f8fafc;
            }
            .radio-group-row {
                display: flex;
                gap: 0.75rem;
                align-items: center;
                flex-wrap: wrap;
                padding-top: 0.25rem;
            }
            .radio-card-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
                padding: 0.45rem 0.85rem;
                border-radius: 9999px;
                background: #ffffff;
                border: 1px solid var(--border-color);
                transition: all 0.2s;
            }
            .radio-card-pill:has(input:checked) {
                background: #fee2e2;
                border-color: var(--brand-red);
                color: var(--brand-red);
            }
            .phone-input-group {
                display: flex;
                align-items: stretch;
            }
            .phone-input-prefix {
                padding: 0 0.75rem;
                background: #f1f5f9;
                border: 1px solid var(--border-color);
                border-right: none;
                border-radius: var(--radius-md) 0 0 var(--radius-md);
                font-weight: 700;
                font-size: 0.8125rem;
                color: var(--text-muted);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .phone-input-field {
                border-radius: 0 var(--radius-md) var(--radius-md) 0 !important;
            }
            .section-card-box {
                background: #ffffff;
                padding: 1.5rem;
                border-radius: var(--radius-lg);
                border: 1px solid var(--border-color);
                margin-bottom: 1.5rem;
                box-shadow: var(--shadow-sm);
            }
            .input-locked {
                background-color: #f1f5f9 !important;
                color: #475569 !important;
                border-color: #cbd5e1 !important;
                cursor: not-allowed !important;
                font-weight: 600 !important;
            }
            .prefix-locked {
                background-color: #e2e8f0 !important;
                color: #64748b !important;
            }
            .locked-badge {
                font-size: 0.7rem;
                font-weight: 700;
                color: var(--brand-red);
                background: #fee2e2;
                padding: 0.15rem 0.5rem;
                border-radius: 9999px;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            @media(max-width: 991px) {
                .dash-layout-grid {
                    grid-template-columns: 1fr !important;
                }
                .share-box-mobile {
                    max-width: 100% !important;
                }
            }
            @media(max-width: 640px) {
                .form-grid-2 {
                    grid-template-columns: 1fr !important;
                    gap: 0.75rem;
                }
                .section-card-box {
                    padding: 1.15rem 1rem !important;
                }
                .upload-grid-row {
                    grid-template-columns: 1fr !important;
                }
                .mobile-w-full {
                    width: 100% !important;
                }
                .handle-action-row {
                    max-width: 100% !important;
                }
            }
        </style>

        <!-- Left Column: Edit Form & Documents -->
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">
            
            <!-- Photo & ID Proof Upload Card -->
            <div class="stat-box" style="padding: 1.5rem; border-top: 4px solid var(--brand-red); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1.25rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-camera-rotate" style="color: var(--brand-red);"></i> Profile Photograph &amp; ID Proof
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;" class="upload-grid-row">

                    <!-- 1. Advocate Photograph -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.75rem;">
                                <div style="width: 60px; height: 60px; border-radius: 50%; overflow: hidden; background: #ffffff; border: 2px solid var(--brand-gold); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; font-weight: 800; color: var(--brand-red); flex-shrink: 0; box-shadow: var(--shadow-sm);">
                                    <?php 
                                    $dashPhotoUrl = getAdvocatePhotoUrl($advocate['photo'] ?? '');
                                    if ($dashPhotoUrl): 
                                    ?>
                                        <img src="<?= sanitize($dashPhotoUrl) ?>" alt="<?= sanitize($advocate['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr(trim($advocate['name'] ?: 'A'), 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin: 0 0 0.15rem;">Profile Photograph</h4>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Max 50 KB (JPG, PNG)</span>
                                </div>
                            </div>
                        </div>

                        <form action="dashboard" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_photo">
                            <div style="margin-bottom: 0.65rem;">
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png" required class="filter-input" style="padding: 0.4rem 0.5rem; font-size: 0.8125rem; min-height: 38px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center; min-height: 36px;">
                                <i class="fas fa-cloud-arrow-up me-1"></i> Upload Photo
                            </button>
                        </form>
                    </div>

                    <!-- 2. ID Proof Document -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.15rem; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background: #fef2f2; color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                                    <i class="fas fa-id-card-clip"></i>
                                </div>
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin: 0 0 0.15rem;">ID Proof Document</h4>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);">Max 100 KB (JPG, PNG, PDF)</span>
                                </div>
                            </div>
                            <?php 
                            $dashIdUrl = getAdvocateIdProofUrl($advocate['id_proof'] ?? '');
                            if ($dashIdUrl): 
                            ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <a href="<?= sanitize($dashIdUrl) ?>" target="_blank" class="btn btn-outline-success btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; width: 100%; justify-content: center;">
                                        <i class="fas fa-file-circle-check me-1"></i> View Uploaded ID Document
                                    </a>
                                </div>
                            <?php else: ?>
                                <div style="margin-bottom: 0.75rem;">
                                    <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #b45309; background: #fef3c7; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-weight: 600;">
                                        <i class="fas fa-clock"></i> ID Proof Pending Upload
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <form action="dashboard" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload_id_proof">
                            <div style="margin-bottom: 0.65rem;">
                                <input type="file" name="id_proof" accept=".jpg,.jpeg,.png,.pdf" required class="filter-input" style="padding: 0.4rem 0.5rem; font-size: 0.8125rem; min-height: 38px;">
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm" style="width: 100%; justify-content: center; min-height: 36px;">
                                <i class="fas fa-shield-halved me-1"></i> Upload ID Proof
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Info Form with 3 Clean Mobile-Friendly Sections -->
            <?php 
            $savedCourts = array_map('trim', explode(',', $advocate['practicing_courts'] ?? ''));
            if (empty($savedCourts) && !empty($advocate['court'])) {
                $savedCourts[] = $advocate['court'];
            }
            $isDistrictCourt = in_array('District Court', $savedCourts) || in_array('CC', $savedCourts) || in_array('District', $savedCourts);
            $isHighCourt = in_array('High Court', $savedCourts) || in_array('HC', $savedCourts);
            $isSupremeCourt = in_array('Supreme Court', $savedCourts) || in_array('SC', $savedCourts);
            $isTribunals = in_array('Tribunals', $savedCourts) || in_array('TR', $savedCourts) || in_array('Tribunal', $savedCourts) || in_array('DRT', $savedCourts) || in_array('NCLT', $savedCourts);

            $standardCourts = ['District Court', 'CC', 'District', 'High Court', 'HC', 'Supreme Court', 'SC', 'Tribunals', 'TR', 'Tribunal', 'DRT', 'NCLT'];
            $otherCourtsList = array_diff($savedCourts, $standardCourts);
            $otherCourtVal = implode(', ', $otherCourtsList);
            ?>
            <div class="stat-box" style="padding: 1.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);" id="profile-edit">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); padding-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-user-pen" style="color: var(--brand-red);"></i> Update Advocate Profile
                        </h3>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); margin: 0.2rem 0 0;">
                            Keep your personal, professional, and address information up to date.
                        </p>
                    </div>
                </div>

                <?php if ($isLocked): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid var(--brand-red); padding: 0.85rem 1.15rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.8125rem; color: #475569; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex: 1; min-width: 260px;">
                            <i class="fas fa-shield-halved" style="color: var(--brand-red); font-size: 1.15rem; flex-shrink: 0;"></i>
                            <span><strong>Verified Bar Credentials Locked:</strong> Name, Primary Mobile, Bar Council, Enrollment Number, and Enrollment Date are locked to protect authenticity. Need to update these?</span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleChangeRequestBox()" style="white-space: nowrap; font-size: 0.78rem; font-weight: 700; padding: 0.35rem 0.75rem;">
                                <i class="fas fa-file-pen me-1"></i> Request Change from Admin
                            </button>
                        </div>
                    </div>

                    <!-- Change Request Submission Form Box (Collapsible) -->
                    <div id="changeRequestBox" style="display: <?= !empty($error) && isset($_POST['action']) && $_POST['action'] === 'submit_change_request' ? 'block' : 'none' ?>; background: #ffffff; border: 2px solid var(--brand-gold); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-md);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                            <div>
                                <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-clipboard-question" style="color: var(--brand-red);"></i> Request Verified Credential Modification
                                </h4>
                                <p style="margin: 0.25rem 0 0; font-size: 0.78rem; color: var(--text-muted);">
                                    Fill in only the specific fields you need modified. All requests are verified by portal administrators before being updated.
                                </p>
                            </div>
                            <button type="button" onclick="toggleChangeRequestBox()" style="background: none; border: none; font-size: 1.15rem; color: var(--text-muted); cursor: pointer;" aria-label="Close form">
                                <i class="fas fa-xmark"></i>
                            </button>
                        </div>

                        <form action="dashboard#profile-edit" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="submit_change_request">

                            <div class="form-grid-2" style="margin-bottom: 0.75rem;">
                                <!-- Requested Full Name -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">New Full Name (if changing)</label>
                                    <input type="text" name="req_name" class="filter-input" placeholder="e.g. <?= sanitize($advocate['name']) ?>" style="min-height: 38px; font-size: 0.85rem;">
                                </div>

                                <!-- Requested Primary Mobile -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">New Primary Mobile Number (if changing)</label>
                                    <input type="tel" name="req_mobile" class="filter-input" placeholder="10-digit mobile number" style="min-height: 38px; font-size: 0.85rem;">
                                </div>
                            </div>

                            <div class="form-grid-2" style="margin-bottom: 0.75rem;">
                                <!-- Requested Enrollment No -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">New Bar Council Enrollment No (if changing)</label>
                                    <input type="text" name="req_e_no" class="filter-input" placeholder="e.g. <?= sanitize($advocate['e_no'] ?: 'BR/1234/2018') ?>" style="min-height: 38px; font-size: 0.85rem;">
                                </div>

                                <!-- Requested Bar Council -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">New State Bar Council (if changing)</label>
                                    <select name="req_bc_id" class="filter-select" style="min-height: 38px; font-size: 0.85rem;">
                                        <option value="">Leave unchanged (Keep current)</option>
                                        <?php foreach ($barCouncils as $bc): ?>
                                            <option value="<?= sanitize($bc['id']) ?>"><?= sanitize($bc['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-grid-2" style="margin-bottom: 0.75rem;">
                                <!-- Requested Enrollment Date & Year -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">New Date of Enrollment &amp; Year (if changing)</label>
                                    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.5rem;">
                                        <input type="text" name="req_e_date" class="filter-input" placeholder="DD/MM/YYYY" style="min-height: 38px; font-size: 0.85rem;">
                                        <input type="number" name="req_e_year" class="filter-input" placeholder="YYYY" min="1950" max="<?= date('Y') ?>" style="min-height: 38px; font-size: 0.85rem;">
                                    </div>
                                </div>

                                <!-- Supporting Document -->
                                <div class="filter-group" style="margin-bottom: 0;">
                                    <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">Supporting Document (Bar Council Certificate / ID Proof)</label>
                                    <input type="file" name="change_doc" accept=".jpg,.jpeg,.png,.pdf" class="filter-input" style="min-height: 38px; padding: 0.35rem 0.5rem; font-size: 0.8rem;">
                                    <small style="font-size: 0.7rem; color: var(--text-muted);">Max 500 KB (PDF, JPG, PNG)</small>
                                </div>
                            </div>

                            <!-- Reason for Change -->
                            <div class="filter-group" style="margin-bottom: 1rem;">
                                <label class="filter-label" style="font-weight: 700; font-size: 0.8rem;">Reason / Justification for Change Request *</label>
                                <textarea name="reason" class="filter-input" rows="2" required placeholder="Explain why these credentials need to be updated (e.g. Typographical error correction, updated enrollment certificate from Bar Council, transfer of state bar, etc.)" style="font-size: 0.85rem; padding: 0.5rem 0.75rem;"></textarea>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                                <button type="button" onclick="toggleChangeRequestBox()" class="btn btn-outline btn-sm">Cancel</button>
                                <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 700; min-height: 38px;">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Request to Admin
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Request History / Status (if any exist) -->
                    <?php if (!empty($advChangeRequests)): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                            <h5 style="margin: 0 0 0.75rem; font-size: 0.85rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fas fa-history" style="color: var(--brand-red);"></i> Your Submitted Change Requests
                            </h5>
                            <div class="table-responsive" style="margin: 0;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid #cbd5e1; text-align: left; color: #64748b;">
                                            <th style="padding: 0.4rem 0.5rem;">Req ID</th>
                                            <th style="padding: 0.4rem 0.5rem;">Requested Modifications</th>
                                            <th style="padding: 0.4rem 0.5rem;">Date</th>
                                            <th style="padding: 0.4rem 0.5rem;">Status</th>
                                            <th style="padding: 0.4rem 0.5rem;">Admin Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($advChangeRequests as $cr): 
                                            $crStatus = strtoupper($cr['status'] ?? 'PENDING');
                                            $modList = [];
                                            if (!empty($cr['requested_name'])) $modList[] = 'Name: ' . $cr['requested_name'];
                                            if (!empty($cr['requested_mobile'])) $modList[] = 'Mobile: ' . $cr['requested_mobile'];
                                            if (!empty($cr['requested_e_no'])) $modList[] = 'Enr No: ' . $cr['requested_e_no'];
                                            if (!empty($cr['requested_e_year'])) $modList[] = 'Year: ' . $cr['requested_e_year'];
                                            if (!empty($cr['requested_e_date'])) $modList[] = 'Enr Date: ' . $cr['requested_e_date'];
                                        ?>
                                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                                <td style="padding: 0.5rem; font-weight: 700; color: var(--primary);">#<?= $cr['id'] ?></td>
                                                <td style="padding: 0.5rem;"><?= !empty($modList) ? sanitize(implode(', ', $modList)) : 'Credential Review' ?></td>
                                                <td style="padding: 0.5rem; color: #64748b;"><?= date('d M Y', strtotime($cr['created_at'])) ?></td>
                                                <td style="padding: 0.5rem;">
                                                    <?php if ($crStatus === 'PENDING'): ?>
                                                        <span style="background: #fef3c7; color: #b45309; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-clock"></i> Pending Review
                                                        </span>
                                                    <?php elseif ($crStatus === 'APPROVED'): ?>
                                                        <span style="background: #dcfce7; color: #15803d; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-check-circle"></i> Approved &amp; Applied
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background: #fee2e2; color: #b91c1c; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 9999px; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-circle-xmark"></i> Rejected
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.5rem; color: #475569; font-style: italic;">
                                                    <?= sanitize($cr['admin_remarks'] ?: ($crStatus === 'PENDING' ? 'Under Admin verification' : '-')) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <script>
                        function toggleChangeRequestBox() {
                            var box = document.getElementById('changeRequestBox');
                            if (box) {
                                if (box.style.display === 'none' || box.style.display === '') {
                                    box.style.display = 'block';
                                    box.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                } else {
                                    box.style.display = 'none';
                                }
                            }
                        }
                    </script>
                <?php endif; ?>
                
                <form action="dashboard#profile-edit" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- =========================================================
                         SECTION 1: PERSONAL INFORMATION
                         ========================================================= -->
                    <div class="section-card-box" style="border-left: 4px solid var(--brand-red);">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 1.15rem; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-id-badge" style="color: var(--brand-red);"></i> Personal Information
                        </h4>

                        <div class="form-grid-2">
                            <!-- Full Name -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label class="filter-label" style="font-weight: 700; margin-bottom: 0;">Full Name *</label>
                                    <?php if ($isLocked): ?>
                                        <span class="locked-badge" title="Verified Name cannot be modified"><i class="fas fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </div>
                                <input type="text" name="name" class="filter-input <?= $isLocked ? 'input-locked' : '' ?>" value="<?= sanitize($advocate['name'] ?? '') ?>" required <?= $isLocked ? 'readonly' : '' ?> placeholder="Full Name as per Bar Council" style="min-height: 42px;">
                            </div>

                            <!-- Father's Name -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Father’s Name</label>
                                <input type="text" name="r_name" class="filter-input" value="<?= sanitize($advocate['r_name'] ?? '') ?>" placeholder="Father's / Relative's Name" style="min-height: 42px;">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <!-- Gender -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700; margin-bottom: 0.4rem;">Gender</label>
                                <div class="radio-group-row">
                                    <label class="radio-card-pill">
                                        <input type="radio" name="gender" value="Male" <?= (strtolower($advocate['gender'] ?? 'male') === 'male') ? 'checked' : '' ?>>
                                        <i class="fas fa-person"></i> Male
                                    </label>
                                    <label class="radio-card-pill">
                                        <input type="radio" name="gender" value="Female" <?= (strtolower($advocate['gender'] ?? '') === 'female') ? 'checked' : '' ?>>
                                        <i class="fas fa-person-dress"></i> Female
                                    </label>
                                    <label class="radio-card-pill">
                                        <input type="radio" name="gender" value="Other" <?= (strtolower($advocate['gender'] ?? '') === 'other') ? 'checked' : '' ?>>
                                        <i class="fas fa-genderless"></i> Other
                                    </label>
                                </div>
                            </div>

                            <!-- Date of Birth -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Date of Birth (DD/MM/YYYY)</label>
                                <input type="text" name="dob" class="filter-input" value="<?= sanitize($advocate['dob'] ?? '') ?>" placeholder="DD/MM/YYYY (e.g. 15/08/1985)" style="min-height: 42px;">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <!-- Email ID -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Email ID</label>
                                <input type="email" name="email" class="filter-input" value="<?= sanitize($advocate['email'] ?? '') ?>" placeholder="advocate@example.com" style="min-height: 42px;">
                            </div>

                            <!-- Mobile Numbers -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label class="filter-label" style="font-weight: 700; margin-bottom: 0;">Mobile Numbers</label>
                                    <?php if ($isLocked): ?>
                                        <span class="locked-badge" title="Registered Mobile cannot be modified"><i class="fas fa-lock"></i> Primary Locked</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;" class="mobile-stack-gap">
                                    <div class="phone-input-group">
                                        <span class="phone-input-prefix <?= $isLocked ? 'prefix-locked' : '' ?>">+91</span>
                                        <input type="tel" name="mobile" class="filter-input phone-input-field <?= $isLocked ? 'input-locked' : '' ?>" style="font-size: 0.85rem; min-height: 42px;" value="<?= sanitize($advocate['mobile'] ?? '') ?>" <?= $isLocked ? 'readonly' : '' ?> placeholder="Primary Mobile">
                                    </div>
                                    <div class="phone-input-group">
                                        <span class="phone-input-prefix">+91</span>
                                        <input type="tel" name="alt_mobile" class="filter-input phone-input-field" style="font-size: 0.85rem; min-height: 42px;" value="<?= sanitize($advocate['alt_mobile'] ?? $advocate['whatsapp'] ?? '') ?>" placeholder="Secondary Mobile">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =========================================================
                         SECTION 2: PROFESSIONAL DETAILS
                         ========================================================= -->
                    <div class="section-card-box" style="border-left: 4px solid var(--brand-gold);">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 1.15rem; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> Professional Details
                        </h4>

                        <div class="form-grid-2">
                            <!-- Bar Council Enrollment Number -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label class="filter-label" style="font-weight: 700; margin-bottom: 0;">Bar Council Enrollment Number</label>
                                    <?php if ($isLocked): ?>
                                        <span class="locked-badge" title="Verified Enrollment Number cannot be modified"><i class="fas fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </div>
                                <input type="text" name="e_no" class="filter-input <?= $isLocked ? 'input-locked' : '' ?>" value="<?= sanitize($advocate['e_no'] ?? '') ?>" <?= $isLocked ? 'readonly' : '' ?> placeholder="e.g. BR/1234/2018 or D/567/2015" style="min-height: 42px;">
                            </div>

                            <!-- State Bar Council Name -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label class="filter-label" style="font-weight: 700; margin-bottom: 0;">State Bar Council Name</label>
                                    <?php if ($isLocked): ?>
                                        <span class="locked-badge" title="Verified Bar Council cannot be modified"><i class="fas fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($isLocked): ?>
                                    <?php 
                                    $currentBcName = 'State Bar Council';
                                    foreach ($barCouncils as $bc) {
                                        if ((string)($advocate['bc_id'] ?? '') === (string)$bc['id'] || (!empty($advocate['state_code']) && $advocate['state_code'] === $bc['state_code'] && empty($advocate['bc_id']))) {
                                            $currentBcName = $bc['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <input type="text" class="filter-input input-locked" value="<?= sanitize($currentBcName) ?>" readonly style="min-height: 42px;">
                                    <input type="hidden" name="bc_id" value="<?= sanitize($advocate['bc_id'] ?? '') ?>">
                                <?php else: ?>
                                    <select name="bc_id" class="filter-select" style="min-height: 42px;">
                                        <option value="">Select State Bar Council</option>
                                        <?php foreach ($barCouncils as $bc): ?>
                                            <option value="<?= sanitize($bc['id']) ?>" <?= ((string)$advocate['bc_id'] === (string)$bc['id'] || (!empty($advocate['state_code']) && $advocate['state_code'] === $bc['state_code'] && empty($advocate['bc_id']))) ? 'selected' : '' ?>>
                                                <?= sanitize($bc['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <!-- Date of Enrollment & Year -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                    <label class="filter-label" style="font-weight: 700; margin-bottom: 0;">Date of Enrollment &amp; Year</label>
                                    <?php if ($isLocked): ?>
                                        <span class="locked-badge" title="Verified Date & Year cannot be modified"><i class="fas fa-lock"></i> Locked</span>
                                    <?php endif; ?>
                                </div>
                                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 0.5rem;">
                                    <input type="text" name="e_date" class="filter-input <?= $isLocked ? 'input-locked' : '' ?>" value="<?= sanitize($advocate['e_date'] ?? '') ?>" <?= $isLocked ? 'readonly' : '' ?> placeholder="DD/MM/YYYY" style="min-height: 42px;">
                                    <input type="number" name="e_year" class="filter-input <?= $isLocked ? 'input-locked' : '' ?>" value="<?= sanitize($advocate['e_year'] ?? '') ?>" <?= $isLocked ? 'readonly' : '' ?> placeholder="YYYY" min="1950" max="<?= date('Y') ?>" style="min-height: 42px;">
                                </div>
                            </div>

                            <!-- Name of Associations -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Name of Associations</label>
                                <select name="ba_code" class="filter-select" style="min-height: 42px;">
                                    <option value="">Select Bar Association</option>
                                    <?php foreach ($barAssociations as $ba): ?>
                                        <option value="<?= sanitize($ba['code']) ?>" <?= ($advocate['ba_code'] ?? '') === $ba['code'] ? 'selected' : '' ?>>
                                            <?= sanitize($ba['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Practicing Courts -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-weight: 700; margin-bottom: 0.4rem;">Practicing Courts</label>
                            <div class="court-checkbox-grid">
                                <label class="court-check-card">
                                    <input type="checkbox" name="practicing_courts[]" value="District Court" <?= $isDistrictCourt ? 'checked' : '' ?>>
                                    <span>District Court</span>
                                </label>
                                <label class="court-check-card">
                                    <input type="checkbox" name="practicing_courts[]" value="High Court" <?= $isHighCourt ? 'checked' : '' ?>>
                                    <span>High Court</span>
                                </label>
                                <label class="court-check-card">
                                    <input type="checkbox" name="practicing_courts[]" value="Supreme Court" <?= $isSupremeCourt ? 'checked' : '' ?>>
                                    <span>Supreme Court</span>
                                </label>
                                <label class="court-check-card">
                                    <input type="checkbox" name="practicing_courts[]" value="Tribunals" <?= $isTribunals ? 'checked' : '' ?>>
                                    <span>Tribunals</span>
                                </label>
                            </div>
                            <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); white-space: nowrap;">Other Courts:</span>
                                <input type="text" name="other_court" class="filter-input" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; flex: 1; min-width: 180px; min-height: 38px;" value="<?= sanitize($otherCourtVal) ?>" placeholder="e.g. NGT, CAT, DRT, Family Court, Consumer Forum">
                            </div>
                        </div>

                        <!-- Specialization (Optional - Powered by advocate_config) -->
                        <div class="filter-group" style="margin-bottom: 1.25rem;">
                            <label class="filter-label" style="font-weight: 700; display: flex; justify-content: space-between; align-items: center;">
                                <span>Specialization (Practice Areas)</span>
                                <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: normal;">Click tags below or type to auto-complete</span>
                            </label>
                            <input type="text" name="practice_area" id="practiceAreaInput" list="practiceAreasDatalist" class="filter-input" value="<?= sanitize($advocate['practice_area'] ?? '') ?>" placeholder="e.g. Civil Law, Property Disputes, Criminal Defense, Family Law" style="min-height: 42px;">
                            <datalist id="practiceAreasDatalist">
                                <?php foreach ($practiceAreasList as $pa): ?>
                                    <option value="<?= sanitize($pa) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>

                            <!-- Quick Select Chips from advocate_config -->
                            <div style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.35rem; max-height: 84px; overflow-y: auto; padding: 0.25rem 0;">
                                <?php 
                                $popularAreas = array_slice($practiceAreasList, 0, 18);
                                foreach ($popularAreas as $pa): 
                                ?>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="addPracticeArea('<?= addslashes(sanitize($pa)) ?>')" style="padding: 0.15rem 0.5rem; font-size: 0.72rem; border-radius: 9999px; background: #f8fafc; border-color: #cbd5e1; color: #475569;">
                                        + <?= sanitize($pa) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <script>
                            function addPracticeArea(areaName) {
                                var input = document.getElementById('practiceAreaInput');
                                if (!input) return;
                                var current = input.value.trim();
                                if (!current) {
                                    input.value = areaName;
                                } else {
                                    var parts = current.split(',').map(function(s) { return s.trim(); });
                                    if (parts.indexOf(areaName) === -1) {
                                        input.value = current + ', ' + areaName;
                                    }
                                }
                            }
                        </script>

                        <!-- Sitting Place Address -->
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 700;">Sitting Place Address</label>
                            <input type="text" name="sitting_address" class="filter-input" value="<?= sanitize($advocate['sitting_address'] ?? '') ?>" placeholder="e.g. Chamber No. 42, Civil Court Complex / Lawyers Chamber Block" style="min-height: 42px;">
                        </div>
                    </div>

                    <!-- =========================================================
                         SECTION 3: ADDRESS DETAILS (Home/Office)
                         ========================================================= -->
                    <div class="section-card-box" style="border-left: 4px solid #3b82f6;">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 1.15rem; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-location-dot" style="color: #2563eb;"></i> Address Details (Home/Office)
                        </h4>

                        <!-- Address -->
                        <div class="filter-group" style="margin-bottom: 1rem;">
                            <label class="filter-label" style="font-weight: 700;">Address</label>
                            <textarea name="address" class="filter-input" rows="2" placeholder="Full Street Address, House/Office Number, Landmark..."><?= sanitize($advocate['address'] ?? '') ?></textarea>
                        </div>

                        <div class="form-grid-2">
                            <!-- Mohalla / Village -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Mohalla / Village</label>
                                <input type="text" name="mohalla_village" class="filter-input" value="<?= sanitize($advocate['mohalla_village'] ?? '') ?>" placeholder="Mohalla / Village Name" style="min-height: 42px;">
                            </div>

                            <!-- Pin Code -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">Pin Code</label>
                                <input type="text" name="pincode" class="filter-input" value="<?= sanitize($advocate['pincode'] ?? '') ?>" maxlength="6" pattern="[0-9]{6}" placeholder="6-digit PIN code (e.g. 110001)" style="min-height: 42px;">
                            </div>
                        </div>

                        <div class="form-grid-2">
                            <!-- State / UT -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">State / UT</label>
                                <select name="state_code" id="dash_state_select" class="filter-select state-cascade" data-target="#dash_district_select" style="min-height: 42px;">
                                    <option value="">Select State / UT</option>
                                    <?php foreach ($states as $sCode => $sName): ?>
                                        <option value="<?= sanitize($sCode) ?>" <?= ($advocate['state_code'] ?? '') === $sCode ? 'selected' : '' ?>>
                                            <?= sanitize($sName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- District -->
                            <div class="filter-group" style="margin-bottom: 0;">
                                <label class="filter-label" style="font-weight: 700;">District</label>
                                <select name="district_code" id="dash_district_select" class="filter-select" style="min-height: 42px;">
                                    <option value="">Select District</option>
                                    <?php foreach ($districts as $d): ?>
                                        <option value="<?= sanitize($d['code']) ?>" <?= ($advocate['district_code'] ?? '') === $d['code'] ? 'selected' : '' ?>>
                                            <?= sanitize($d['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Summary & Chambers Bio -->
                    <div class="section-card-box" style="border-left: 4px solid #8b5cf6;">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-file-lines" style="color: #7c3aed;"></i> Professional Summary &amp; Bio
                        </h4>
                        <div class="filter-group" style="margin-bottom: 0;">
                            <label class="filter-label" style="font-weight: 700;">About / Legal Experience</label>
                            <textarea name="about" class="filter-input" rows="4" placeholder="Describe your legal experience, notable court appearances, and practice profile..."><?= sanitize($advocate['about'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Privacy Settings -->
                    <div class="section-card-box" style="border-left: 4px solid #10b981;">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 1.15rem; display: flex; align-items: center; gap: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-shield-halved" style="color: #059669;"></i> Contact Privacy &amp; Visibility
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                            <div>
                                <label class="filter-label" style="font-weight: 600;">Mobile Visibility</label>
                                <select name="mobile_visibility" class="filter-select" style="min-height: 42px;">
                                    <option value="PUBLIC" <?= ($advocate['mobile_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public (All Users)</option>
                                    <option value="REGISTERED" <?= ($advocate['mobile_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Registered Only</option>
                                    <option value="PRIVATE" <?= ($advocate['mobile_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden (Private)</option>
                                </select>
                            </div>

                            <div>
                                <label class="filter-label" style="font-weight: 600;">Email Visibility</label>
                                <select name="email_visibility" class="filter-select" style="min-height: 42px;">
                                    <option value="PUBLIC" <?= ($advocate['email_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public (All Users)</option>
                                    <option value="REGISTERED" <?= ($advocate['email_visibility'] ?? '') === 'REGISTERED' ? 'selected' : '' ?>>Registered Only</option>
                                    <option value="PRIVATE" <?= ($advocate['email_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Hidden (Private)</option>
                                </select>
                            </div>

                            <div>
                                <label class="filter-label" style="font-weight: 600;">Address Visibility</label>
                                <select name="address_visibility" class="filter-select" style="min-height: 42px;">
                                    <option value="PUBLIC" <?= ($advocate['address_visibility'] ?? '') === 'PUBLIC' ? 'selected' : '' ?>>Public</option>
                                    <option value="PRIVATE" <?= ($advocate['address_visibility'] ?? '') === 'PRIVATE' ? 'selected' : '' ?>>Private</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;" class="mobile-action-bar">
                        <button type="submit" class="btn btn-primary btn-md" style="font-weight: 800; padding: 0.75rem 2rem; min-height: 46px; font-size: 1rem; flex: 1; max-width: 280px; justify-content: center;">
                            <i class="fas fa-floppy-disk me-1"></i> Save Profile Details
                        </button>
                        <a href="<?= $publicProfileUrl ?>" target="_blank" class="btn btn-outline btn-md" style="font-weight: 600; min-height: 46px; padding: 0.75rem 1.5rem; justify-content: center;">
                            <i class="fas fa-eye me-1"></i> Preview Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Sidebar Info -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Credentials Box -->
            <div class="stat-box" style="padding: 1.25rem; border-radius: var(--radius-lg);">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--primary);">
                    <i class="fas fa-certificate text-primary"></i> Enrollment Credentials
                </h4>
                <div style="margin-bottom: 0.75rem;">
                    <span class="badge-verification badge-verified" style="font-size: 0.8125rem;">
                        <i class="fas fa-id-card"></i> <?= sanitize($advocate['e_no']) ?><?= !empty($advocate['e_year']) ? ' / ' . sanitize($advocate['e_year']) : '' ?>
                    </span>
                </div>
                <div style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6;">
                    <div>State Bar: <strong><?= sanitize(getStateName($advocate['state_code'] ?? '')) ?: 'Not specified' ?></strong></div>
                    <div>Court: <strong><?= sanitize(getCourtsList()[$advocate['court'] ?? 'CC'] ?? 'Civil & District Court') ?></strong></div>
                </div>
            </div>

            <!-- Membership Tier Box -->
            <div class="stat-box" style="padding: 1.25rem; border-top: 3px solid var(--brand-red); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin: 0;">Membership Plan</h4>
                    <span class="badge-verification badge-verified" style="text-transform: uppercase; font-size: 0.75rem;">
                        <?= sanitize($advocate['plan_type'] ?: 'Registered') ?>
                    </span>
                </div>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Upgrade to Premium Verification to unlock verified badges, prioritized directory ranking, and enhanced profile visibility.
                </p>
                <a href="pricing" class="btn btn-outline-primary btn-sm" style="width: 100%; justify-content: center; min-height: 38px;">
                    <i class="fas fa-shield-check me-1"></i> View Verification Plans
                </a>
            </div>

            <!-- Profile Tip Box -->
            <div class="stat-box" style="background: #eff6ff; border-color: #bfdbfe; padding: 1.25rem; border-radius: var(--radius-lg);">
                <h4 style="font-size: 0.95rem; color: #1e3a8a; font-weight: 700; margin-bottom: 0.5rem;">
                    <i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Pro Tip
                </h4>
                <p style="font-size: 0.8125rem; color: #1e40af; line-height: 1.5; margin-bottom: 0;">
                    Your custom handle: <strong>myadv.in/@<?= sanitize($currentHandle ?: 'yourname') ?></strong>. Print it on visiting cards or share directly with clients.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
