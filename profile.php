<?php
// profile.php - Official Advocate Profile Page
require_once __DIR__ . '/config/app.php';

$db = getDB();

// 1. Initial GET Parameter extraction
$id = sanitize($_GET['id'] ?? '');
$publicUrl = sanitize($_GET['url'] ?? $_GET['slug'] ?? $_GET['public'] ?? '');
$enrNo = sanitize($_GET['enr'] ?? $_GET['e_no'] ?? '');
$enrYear = sanitize($_GET['year'] ?? $_GET['e_year'] ?? '');
$stateCode = sanitize($_GET['state'] ?? $_GET['state_code'] ?? '');
$mobile = sanitize($_GET['mobile'] ?? '');

// 2. Parse multi-parameter 'link' / 'l' / 'token' (e.g. ?link=public=handle or ?link=slug&link=base64)
$candidateLinks = [];
if (!empty($_SERVER['QUERY_STRING'])) {
    $pairs = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($pairs as $pair) {
        $kv = explode('=', $pair, 2);
        $k = urldecode($kv[0] ?? '');
        $v = urldecode($kv[1] ?? '');
        if (in_array(strtolower($k), ['link', 'l', 'token', 'q', 'url', 'slug', 'public'])) {
            $candidateLinks[] = $v;
        }
    }
}
if (isset($_GET['link'])) {
    if (is_array($_GET['link'])) {
        foreach ($_GET['link'] as $lv) $candidateLinks[] = $lv;
    } else {
        $candidateLinks[] = $_GET['link'];
    }
}

foreach (array_unique(array_filter($candidateLinks)) as $linkVal) {
    $linkVal = trim($linkVal);

    // If format is 'public=username'
    if (preg_match('/^public=(.+)$/i', $linkVal, $pm)) {
        $publicUrl = trim($pm[1]);
        continue;
    }

    // Check if base64 encoded token (e.g. id=124419&name=NILAMADHAB+SAHU)
    $decoded = @base64_decode($linkVal, true);
    if ($decoded && preg_match('/[a-zA-Z0-9_]+=/', $decoded)) {
        parse_str($decoded, $parsed);
        if (!empty($parsed['id']) && is_numeric($parsed['id'])) {
            $id = (int)$parsed['id'];
        }
        if (!empty($parsed['e_no'])) $enrNo = sanitize($parsed['e_no']);
        if (!empty($parsed['e_year'])) $enrYear = sanitize($parsed['e_year']);
        if (!empty($parsed['state'])) $stateCode = sanitize($parsed['state']);
    } elseif (is_numeric($linkVal) && empty($id)) {
        $id = (int)$linkVal;
    } elseif (empty($publicUrl)) {
        $publicUrl = $linkVal;
    }
}

$advocate = null;

try {
    if (!empty($id) && is_numeric($id)) {
        $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $advocate = $stmt->fetch();
    } elseif (!empty($publicUrl)) {
        $cleanPublicUrl = ltrim(trim($publicUrl), '@');
        $stmt = $db->prepare("SELECT * FROM advocate WHERE public_url = ? OR public_url = ? OR public_url = ? LIMIT 1");
        $stmt->execute([$publicUrl, $cleanPublicUrl, '@' . $cleanPublicUrl]);
        $advocate = $stmt->fetch();

        // If not matched directly on public_url, try parsing the slug components
        if (!$advocate) {
            $slugClean = preg_replace('/^advocate-/i', '', $publicUrl);
            $slugYear = null;
            if (preg_match('/-(\d{4})$/', $slugClean, $ym)) {
                $slugYear = $ym[1];
                $slugClean = substr($slugClean, 0, -strlen($ym[0]));
            }
            $tokens = explode('-', $slugClean);
            if (count($tokens) >= 2) {
                if ($slugYear) {
                    $st = $db->prepare("SELECT * FROM advocate WHERE (name LIKE ? OR name LIKE ?) AND e_year = ? LIMIT 1");
                    $st->execute(['%' . $tokens[0] . ' ' . $tokens[1] . '%', '%' . str_replace('-', ' ', $slugClean) . '%', $slugYear]);
                    $advocate = $st->fetch();
                }
            }
        }
    } elseif (!empty($enrNo) && !empty($enrYear)) {
        $sql = "SELECT * FROM advocate WHERE e_no = ? AND e_year = ?";
        $args = [$enrNo, $enrYear];
        if (!empty($stateCode)) {
            $sql .= " AND state_code = ?";
            $args[] = $stateCode;
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        $advocate = $stmt->fetch();
    } elseif (!empty($mobile)) {
        $stmt = $db->prepare("SELECT * FROM advocate WHERE mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $advocate = $stmt->fetch();
    } elseif (!empty($_SESSION['advocate_id'])) {
        $stmt = $db->prepare("SELECT * FROM advocate WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['advocate_id']]);
        $advocate = $stmt->fetch();
    }

    if ($advocate) {
        $allStates = getStates();
        $sCode = $advocate['state_code'] ?? '';
        $advocate['state_name'] = $allStates[$sCode] ?? $sCode;
        if (!empty($advocate['district_code'])) {
            try {
                $dStmt = $db->prepare("SELECT name FROM district WHERE code = ? LIMIT 1");
                $dStmt->execute([$advocate['district_code']]);
                $advocate['district_name'] = $dStmt->fetchColumn() ?: '';
            } catch (Exception $de) {}
        }
        if (!empty($advocate['bc_id'])) {
            try {
                $bcStmt = $db->prepare("SELECT name FROM bc WHERE id = ? OR bc_id = ? LIMIT 1");
                $bcStmt->execute([$advocate['bc_id'], $advocate['bc_id']]);
                $advocate['bar_council_name'] = $bcStmt->fetchColumn() ?: '';
            } catch (Exception $be) {}
        }
        if (!empty($advocate['ba_code'])) {
            try {
                $baStmt = $db->prepare("SELECT name FROM ba WHERE code = ? LIMIT 1");
                $baStmt->execute([$advocate['ba_code']]);
                $advocate['association_name'] = $baStmt->fetchColumn() ?: '';
            } catch (Exception $bae) {}
        }
        if (!empty($advocate['hc_id'])) {
            try {
                $hcStmt = $db->prepare("SELECT name FROM hc WHERE id = ? LIMIT 1");
                $hcStmt->execute([(int)$advocate['hc_id']]);
                $advocate['hc_name'] = $hcStmt->fetchColumn() ?: '';
            } catch (Exception $he) {}
        }
        if (!empty($advocate['univ_code'])) {
            try {
                $uStmt = $db->prepare("SELECT TRIM(TRAILING ',' FROM TRIM(name)) FROM university WHERE code = ? OR name = ? LIMIT 1");
                $uStmt->execute([$advocate['univ_code'], $advocate['univ_code']]);
                $advocate['univ_name'] = $uStmt->fetchColumn() ?: $advocate['univ_code'];
            } catch (Exception $ue) {
                $advocate['univ_name'] = $advocate['univ_code'];
            }
        }
        if (!empty($advocate['college_code'])) {
            try {
                $cStmt = $db->prepare("SELECT name FROM college WHERE code = ? LIMIT 1");
                $cStmt->execute([$advocate['college_code']]);
                $advocate['college_name'] = $cStmt->fetchColumn() ?: $advocate['college_code'];
            } catch (Exception $ce) {
                $advocate['college_name'] = $advocate['college_code'];
            }
        }
    }
} catch (Exception $e) {
    $advocate = null;
}

if ($advocate) {
    $advType = strtoupper(trim((string)($advocate['type'] ?? '')));
    $advStatus = strtoupper(trim((string)($advocate['status'] ?? '')));
    $isOwner = !empty($_SESSION['advocate_id']) && (int)$_SESSION['advocate_id'] === (int)$advocate['id'];
    if (!$isOwner && ($advStatus === 'BLOCK' || in_array($advType, ['BLOCK', 'DIED']))) {
        $advocate = null;
    }
}

if ($advocate && !empty($advocate['public_url'])) {
    $cleanHandle = ltrim(trim($advocate['public_url']), '@');
    if (!empty($_GET['id']) && empty($_GET['url'])) {
        header("Location: @" . urlencode($cleanHandle), true, 301);
        exit;
    }
}

if (!$advocate) {
    http_response_code(404);
    $pageTitle = "Advocate Not Found - My Advocate";
    require_once INCLUDES_PATH . '/header.php';
    ?>
    <div class="container" style="padding: 5rem 1rem; text-align: center;">
        <div style="font-size: 3.5rem; color: var(--text-light); margin-bottom: 1rem;"><i class="fas fa-user-slash"></i></div>
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Advocate Profile Not Found</h1>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
            The requested advocate profile record does not exist or may have been updated.
        </p>
        <a href="advocate-search-result" class="btn btn-primary"><i class="fas fa-magnifying-glass me-2"></i> Search Directory</a>
    </div>
    <?php
    require_once INCLUDES_PATH . '/footer.php';
    exit;
}

// Track advocate profile view in advocate_data table
recordAdvocateSeen((int)$advocate['id']);

// If logged in as member, record in member_advocate_views table
if (!empty($_SESSION['member_id'])) {
    recordMemberAdvocateView((int)$_SESSION['member_id'], (int)$advocate['id'], $advocate['name'] ?? 'Advocate');
}

// Check if currently logged in user is viewing their own profile
$isOwnProfile = (!empty($_SESSION['advocate_id']) && !empty($advocate['id']) && (int)$_SESSION['advocate_id'] === (int)$advocate['id']);

// Prepare profile display variables
$badge = getVerificationBadge($advocate);
$advIndex = calculateAdvocateIndex($advocate);
$stateName = $advocate['state_name'] ?: getStateName($advocate['state_code'] ?? '');
$districtName = $advocate['district_name'] ?: getDistrictName($advocate['district_code'] ?? '');
$courtName = getCourtName($advocate['court'] ?? '');
$baName = $advocate['association_name'] ?: getBarAssociationName($advocate['ba_code'] ?? '');
$bcName = $advocate['bar_council_name'] ?? ($stateName ? "Bar Council of " . $stateName : "State Bar Council");
$practices = parsePracticeAreas($advocate['practice_area'] ?? '');

$maskedMobile = maskContactInfo($advocate['mobile'] ?? '', $advocate['mobile_visibility'] ?? 'REGISTERED', $isOwnProfile);
$maskedEmail = maskContactInfo($advocate['email'] ?? '', $advocate['email_visibility'] ?? 'REGISTERED', $isOwnProfile);
$maskedAddress = maskContactInfo($advocate['address'] ?? '', $advocate['address_visibility'] ?? 'PRIVATE', $isOwnProfile);

// Experience & Enrollment logic
$experienceStr = formatPracticeExperience($advocate['e_year'] ?? '');
$enrollmentStatus = formatEnrollmentNumber($advocate['e_no'] ?? '');

$canonicalUrl = APP_URL . '/' . getAdvocateUrl($advocate);
$pageTitle = ($advocate['name'] ?: 'Advocate Profile') . " - Advocate Directory";
$pageDescription = "Official digital profile for Advocate " . ($advocate['name'] ?? '') . ", practicing at " . $courtName . ", " . $districtName . ", " . $stateName . ".";

// Fetch Related Advocates (from same district or state) with Profile Completeness Priority
$relatedAdvocates = [];
try {
    $rStmt = $db->prepare("SELECT id, name, photo, practice_area, public_url, plan_type, type, state_code, district_code, e_no, e_year, court, mobile, email, about, id_proof 
                           FROM advocate 
                           WHERE type IN ('ACTIVE', 'VERIFIED') AND type NOT IN ('PENDING', 'BLOCK', 'DIED') AND (status != 'BLOCK' OR status IS NULL) AND id != ? AND (district_code = ? OR state_code = ?) 
                           ORDER BY (district_code = ?) DESC,
                           (
                               (CASE WHEN photo IS NOT NULL AND photo != '' AND photo != '0' THEN 20 ELSE 0 END) +
                               (CASE WHEN id_proof IS NOT NULL AND id_proof != '' AND id_proof != '0' THEN 20 ELSE 0 END) +
                               (CASE WHEN public_url IS NOT NULL AND public_url != '' THEN 15 ELSE 0 END) +
                               (CASE WHEN practice_area IS NOT NULL AND practice_area != '' AND practice_area != 'Array' THEN 15 ELSE 0 END)
                           ) DESC, id DESC LIMIT 3");
    $rStmt->execute([(int)$advocate['id'], $advocate['district_code'] ?? '', $advocate['state_code'] ?? '', $advocate['district_code'] ?? '']);
    $relatedAdvocates = $rStmt->fetchAll();
} catch (Exception $e) {
    $relatedAdvocates = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; 
        <a href="advocate-search-result">Advocates</a> &bull; 
        <?php if ($stateName): ?><a href="advocate-search-result?state=<?= sanitize($advocate['state_code']) ?>"><?= sanitize($stateName) ?></a> &bull; <?php endif; ?>
        <span><?= sanitize($advocate['name'] ?: 'Profile') ?></span>
    </nav>

    <?php if ($isOwnProfile): ?>
        <!-- Logged-in Advocate Notification Bar -->
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; color: #1e40af;">
                <i class="fas fa-circle-check" style="font-size: 1.25rem; color: #2563eb;"></i>
                <div>
                    <strong style="display: block; font-size: 0.9375rem;">You are signed in to this profile</strong>
                    <span style="font-size: 0.8125rem; color: #3b82f6;">You can edit your details, practice areas, and privacy settings from your dashboard.</span>
                </div>
            </div>
            <div>
                <a href="dashboard" class="btn btn-primary btn-sm"><i class="fas fa-gauge"></i> Dashboard</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Profile Top Header Card -->
    <div class="profile-header-card">
        <div class="profile-header-grid">
            <div class="profile-large-avatar">
                <?php 
                $advPhotoUrl = getAdvocatePhotoUrl($advocate['photo'] ?? '');
                if (!empty($advPhotoUrl)): 
                ?>
                    <img src="<?= sanitize($advPhotoUrl) ?>" alt="<?= sanitize($advocate['name']) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                <?php else: ?>
                    <?= strtoupper(substr(trim($advocate['name'] ?: 'A'), 0, 1)) ?>
                <?php endif; ?>
            </div>

            <div class="profile-title-area">
                <div style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span class="badge-verification <?= $badge['badge_class'] ?>">
                        <i class="fas <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                    </span>
                    <?php if (strtoupper(trim((string)($advocate['aor'] ?? ''))) === 'YES'): ?>
                        <span class="badge-verification" style="background: #fdf2f8; color: #be185d; border-color: #fbcfe8;">
                            <i class="fas fa-landmark"></i> Advocate-on-Record (AOR)
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($advocate['hc_name'])): ?>
                        <span class="badge-verification" style="background: #f0fdf4; color: #15803d; border-color: #bbf7d0;">
                            <i class="fas fa-building-columns"></i> <?= sanitize($advocate['hc_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1><?= sanitize($advocate['name'] ?: 'Advocate') ?></h1>
                
                <div class="profile-subhead" style="margin-bottom: 0;">
                    <span><i class="fas fa-id-card" style="color: var(--brand-red);"></i> Enrollment: <strong><?= sanitize($enrollmentStatus) ?></strong></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-location-dot" style="color: var(--brand-accent);"></i> <?= sanitize($districtName ?: 'District') ?><?= $stateName ? ', ' . sanitize($stateName) : '' ?></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-gavel"></i> <?= sanitize($courtName) ?></span>
                </div>
            </div>

            <!-- Profile Action Buttons -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php if ($isOwnProfile): ?>
                    <a href="dashboard" class="btn btn-primary btn-sm">
                        <i class="fas fa-pen-to-square"></i> Edit My Profile
                    </a>
                <?php else: ?>
                    <a href="claim-profile?id=<?= $advocate['id'] ?>" class="btn btn-gold btn-sm">
                        <i class="fas fa-shield-halved"></i> Claim This Profile
                    </a>
                <?php endif; ?>
                <button class="btn btn-outline btn-sm btn-copy-link" data-url="<?= $canonicalUrl ?>">
                    <i class="fas fa-share-nodes"></i> Share Profile
                </button>
            </div>
        </div>
    </div>

    <!-- Main Profile Content Columns -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;" class="profile-main-grid">
        <style>
            @media(min-width: 992px) {
                .profile-main-grid {
                    grid-template-columns: 2fr 1fr;
                }
            }
        </style>

        <!-- Left Column: Details & Practice -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Professional Summary Card -->
            <div class="stat-box">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-user-graduate" style="color: var(--brand-red);"></i> Professional Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    <div>
                        <div class="stat-label">Bar Council</div>
                        <div style="font-weight: 600;"><?= sanitize($bcName) ?></div>
                    </div>
                    <div>
                        <div class="stat-label">Practice Experience</div>
                        <div style="font-weight: 600;"><?= sanitize($experienceStr) ?></div>
                    </div>
                    <div>
                        <div class="stat-label">Bar Association</div>
                        <div style="font-weight: 600;"><?= sanitize($baName ?: 'Bar Association') ?></div>
                    </div>
                    <div>
                        <div class="stat-label">Primary Court Jurisdiction</div>
                        <div style="font-weight: 600;"><?= sanitize($courtName) ?></div>
                    </div>
                    <?php if (!empty($advocate['hc_name'])): ?>
                    <div>
                        <div class="stat-label">Affiliated High Court</div>
                        <div style="font-weight: 600; color: #15803d;"><i class="fas fa-building-columns me-1"></i> <?= sanitize($advocate['hc_name']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($advocate['practicing_courts'])): ?>
                    <div>
                        <div class="stat-label">Practicing Courts</div>
                        <div style="font-weight: 600;"><?= sanitize($advocate['practicing_courts']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($advocate['sitting_address'])): ?>
                    <div>
                        <div class="stat-label">Sitting Place / Chamber</div>
                        <div style="font-weight: 600;"><?= sanitize($advocate['sitting_address']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($advocate['education']) || !empty($advocate['univ_name'])): ?>
                    <div>
                        <div class="stat-label">Legal Qualifications</div>
                        <div style="font-weight: 600;">
                            <?= sanitize($advocate['education'] ?: 'Law Degree') ?>
                            <?php if (!empty($advocate['univ_name'])): ?>
                                <span style="font-size: 0.8125rem; font-weight: 400; color: var(--text-muted); display: block; margin-top: 0.15rem;">
                                    <i class="fas fa-graduation-cap me-1"></i><?= sanitize($advocate['univ_name']) ?>
                                    <?= !empty($advocate['college_name']) && $advocate['college_name'] !== $advocate['univ_name'] ? ' (' . sanitize($advocate['college_name']) . ')' : '' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="stat-label">Languages Known</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.35rem;">
                            <?php 
                            $langs = array_filter(array_map('trim', explode(',', $advocate['languages'] ?? 'Hindi, English')));
                            if (empty($langs)) $langs = ['Hindi', 'English'];
                            foreach ($langs as $lng):
                            ?>
                                <span style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.6rem; background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 600; color: var(--text-main);">
                                    <i class="fas fa-language" style="color: var(--brand-red); font-size: 0.75rem;"></i> <?= sanitize($lng) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- About / Bio Card -->
            <div class="stat-box">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-file-lines" style="color: var(--brand-gold);"></i> About & Practice Overview</h2>
                <p style="color: var(--text-main); line-height: 1.7; font-size: 0.9375rem;">
                    <?= !empty($advocate['about']) ? nl2br(sanitize($advocate['about'])) : "Advocate " . sanitize($advocate['name'] ?: 'The Advocate') . " is a registered legal practitioner at the " . sanitize($courtName) . " in " . sanitize($districtName ?: 'their respective district') . ". With expertise across multiple branches of Indian Law including " . implode(', ', $practices) . ", they represent clients in trial court matters, dispute resolution, and legal documentation." ?>
                </p>
            </div>

            <!-- Areas of Practice / Specialization Card -->
            <div class="stat-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h2 style="font-size: 1.25rem; margin: 0; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-scale-balanced" style="color: var(--brand-red);"></i> Areas of Specialization
                    </h2>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--brand-red); background: var(--brand-red-light); border: 1px solid var(--brand-red-border); padding: 0.2rem 0.6rem; border-radius: var(--radius-full);">
                        <?= count($practices) ?> <?= count($practices) === 1 ? 'Practice Field' : 'Practice Fields' ?>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem;">
                    <?php foreach ($practices as $practice): 
                        $pIcon = getPracticeAreaIcon($practice);
                        $searchLink = "advocate-search-result?" . (!empty($advocate['state_code']) ? 'state=' . urlencode($advocate['state_code']) . '&' : '') . "practice_area=" . urlencode($practice);
                    ?>
                        <a href="<?= $searchLink ?>" style="background: #ffffff; padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: var(--text-main); box-shadow: var(--shadow-sm); transition: var(--transition);" onmouseover="this.style.borderColor='var(--brand-gold)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border-color)'; this.style.transform='none';">
                            <div style="display: flex; align-items: center; gap: 0.65rem;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--brand-red-light); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0;">
                                    <i class="fas <?= $pIcon ?>"></i>
                                </div>
                                <span style="font-weight: 700; font-size: 0.9rem;"><?= sanitize($practice) ?></span>
                            </div>
                            <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-muted);"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Contact & Directory Information -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Contact & Privacy Card -->
            <div class="stat-box" style="border-top: 4px solid var(--brand-red);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.15rem; color: var(--primary);"><i class="fas fa-address-book"></i> Contact Details</h3>
                    <span class="badge-verification badge-basic"><i class="fas fa-lock"></i> Privacy Guarded</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.9375rem;">
                    <div>
                        <div class="stat-label"><i class="fas fa-phone"></i> Mobile Phone</div>
                        <div style="font-weight: 600;"><?= sanitize($maskedMobile ?: 'Not Publicly Listed') ?></div>
                    </div>
                    <div>
                        <div class="stat-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div style="font-weight: 600;"><?= sanitize($maskedEmail ?: 'Not Publicly Listed') ?></div>
                    </div>
                    <div>
                        <div class="stat-label"><i class="fas fa-location-dot"></i> Chamber / Office Address</div>
                        <div style="font-weight: 600;"><?= sanitize($maskedAddress ?: ($districtName ? $districtName . ', ' . $stateName : 'Address on file with Bar Association')) ?></div>
                    </div>
                </div>

                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 0.6rem;">
                    <?php if (!empty($advocate['whatsapp'])): 
                        $waNum = preg_replace('/[^0-9]/', '', $advocate['whatsapp']);
                        if (strlen($waNum) === 10) $waNum = '91' . $waNum;
                    ?>
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= urlencode("Hello Advocate " . $advocate['name'] . ", I found your profile on My Advocate: " . $canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="width: 100%; justify-content: center; background: #25d366; color: #ffffff; border: none; font-weight: 600;">
                            <i class="fab fa-whatsapp me-1"></i> Message on WhatsApp
                        </a>
                    <?php endif; ?>
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Connect with Advocate " . $advocate['name'] . " on My Advocate: " . $canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="width: 100%; justify-content: center; color: #16a34a; border-color: #86efac;">
                        <i class="fas fa-share-nodes me-1"></i> Share via WhatsApp
                    </a>
                </div>
            </div>

            <!-- Verification Methodology Info -->
            <div class="stat-box" style="background: #f8fafc;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--primary);">
                    <i class="fas fa-certificate" style="color: var(--brand-gold);"></i> About Profile Verification
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                    Profiles on My Advocate reflect public enrollment records from State Bar Councils and advocate-managed listings.
                </p>
                <a href="about" style="font-size: 0.8125rem; font-weight: 600;">Learn about verification methodology &rarr;</a>
            </div>

            <!-- Local Directory Navigation -->
            <div class="stat-box">
                <h4 style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.75rem;">
                    Legal Directory
                </h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php if (!empty($advocate['district_code'])): ?>
                        <a href="advocate-search-result?state=<?= sanitize($advocate['state_code']) ?>&district=<?= sanitize($advocate['district_code']) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: var(--bg-alt); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-main); font-size: 0.8125rem; font-weight: 600;">
                            <span>Advocates in <?= sanitize($districtName ?: 'District') ?></span>
                            <i class="fas fa-chevron-right" style="color: var(--brand-red); font-size: 0.7rem;"></i>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($advocate['state_code'])): ?>
                        <a href="advocate-search-result?state=<?= sanitize($advocate['state_code']) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: var(--bg-alt); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-main); font-size: 0.8125rem; font-weight: 600;">
                            <span>Experts in <?= sanitize($stateName ?: 'State') ?></span>
                            <i class="fas fa-chevron-right" style="color: var(--brand-red); font-size: 0.7rem;"></i>
                        </a>
                    <?php endif; ?>
                    <a href="advocate-search-result?year=<?= sanitize($advocate['e_year'] ?: date('Y')) ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: var(--bg-alt); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-main); font-size: 0.8125rem; font-weight: 600;">
                        <span>Enrolled in <?= sanitize($advocate['e_year'] ?: 'Year') ?></span>
                        <i class="fas fa-chevron-right" style="color: var(--brand-red); font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Advocates Section -->
    <?php if (!empty($relatedAdvocates)): ?>
        <div style="margin-top: 3.5rem; padding-top: 2.5rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--primary); margin: 0 0 0.25rem 0;">Related Legal Practitioners</h3>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">Other advocates practicing in <?= sanitize($districtName ?: $stateName ?: 'India') ?></p>
                </div>
                <a href="advocate-search-result?state=<?= sanitize($advocate['state_code']) ?>" class="btn btn-outline-primary btn-sm">
                    View All in <?= sanitize($stateName ?: 'State') ?> <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <?php foreach ($relatedAdvocates as $rel): 
                    $relUrl = getAdvocateUrl($rel);
                    $relBadge = getVerificationBadge($rel);
                    $relDist = getDistrictName($rel['district_code'] ?? '');
                ?>
                    <div class="advocate-card">
                        <div class="card-top">
                            <div class="advocate-avatar" style="background: linear-gradient(135deg, #fee2e2 0%, #fef3c7 100%); color: var(--brand-red);">
                                <?php 
                                $relPhotoUrl = getAdvocatePhotoUrl($rel['photo'] ?? '');
                                if (!empty($relPhotoUrl)): 
                                ?>
                                    <img src="<?= sanitize($relPhotoUrl) ?>" alt="<?= sanitize($rel['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                                <?php else: ?>
                                    <?= strtoupper(substr(trim($rel['name'] ?: 'A'), 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <div class="advocate-meta">
                                <div style="margin-bottom: 0.25rem;">
                                    <span class="badge-verification <?= $relBadge['badge_class'] ?>" style="font-size: 0.7rem; padding: 0.15rem 0.5rem;">
                                        <i class="fas <?= $relBadge['icon'] ?>"></i> <?= $relBadge['label'] ?>
                                    </span>
                                </div>
                                <h4 class="advocate-name" style="font-size: 1.05rem;">
                                    <a href="<?= $relUrl ?>"><?= sanitize($rel['name'] ?: 'Advocate') ?></a>
                                </h4>
                                <div class="advocate-enr" style="font-size: 0.775rem;">
                                    <span><i class="fas fa-location-dot" style="color: var(--brand-red);"></i> <?= sanitize($relDist ?: 'District') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-bottom" style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                            <a href="<?= $relUrl ?>" class="btn btn-outline-primary btn-sm" style="font-size: 0.8125rem;">
                                View Profile <i class="fas fa-chevron-right" style="font-size: 0.65rem; margin-left: 2px;"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
