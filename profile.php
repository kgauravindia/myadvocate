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
        $stmt = $db->prepare("SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                              FROM advocate a 
                              LEFT JOIN state s ON a.state_code = s.code 
                              LEFT JOIN district d ON a.district_code = d.code 
                              LEFT JOIN bc ON a.bc_id = bc.id 
                              LEFT JOIN ba ON a.ba_code = ba.code 
                              WHERE a.id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $advocate = $stmt->fetch();
    } elseif (!empty($publicUrl)) {
        $stmt = $db->prepare("SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                              FROM advocate a 
                              LEFT JOIN state s ON a.state_code = s.code 
                              LEFT JOIN district d ON a.district_code = d.code 
                              LEFT JOIN bc ON a.bc_id = bc.id 
                              LEFT JOIN ba ON a.ba_code = ba.code 
                              WHERE a.public_url = ? LIMIT 1");
        $stmt->execute([$publicUrl]);
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
                    $st = $db->prepare("SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                                        FROM advocate a 
                                        LEFT JOIN state s ON a.state_code = s.code 
                                        LEFT JOIN district d ON a.district_code = d.code 
                                        LEFT JOIN bc ON a.bc_id = bc.id 
                                        LEFT JOIN ba ON a.ba_code = ba.code 
                                        WHERE (a.name LIKE ? OR a.name LIKE ?) AND a.e_year = ? LIMIT 1");
                    $st->execute(['%' . $tokens[0] . ' ' . $tokens[1] . '%', '%' . str_replace('-', ' ', $slugClean) . '%', $slugYear]);
                    $advocate = $st->fetch();
                }
            }
        }
    } elseif (!empty($enrNo) && !empty($enrYear)) {
        $sql = "SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                FROM advocate a 
                LEFT JOIN state s ON a.state_code = s.code 
                LEFT JOIN district d ON a.district_code = d.code 
                LEFT JOIN bc ON a.bc_id = bc.id 
                LEFT JOIN ba ON a.ba_code = ba.code 
                WHERE a.e_no = ? AND a.e_year = ?";
        $args = [$enrNo, $enrYear];
        if (!empty($stateCode)) {
            $sql .= " AND a.state_code = ?";
            $args[] = $stateCode;
        }
        $sql .= " LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        $advocate = $stmt->fetch();
    } elseif (!empty($mobile)) {
        $stmt = $db->prepare("SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                              FROM advocate a 
                              LEFT JOIN state s ON a.state_code = s.code 
                              LEFT JOIN district d ON a.district_code = d.code 
                              LEFT JOIN bc ON a.bc_id = bc.id 
                              LEFT JOIN ba ON a.ba_code = ba.code 
                              WHERE a.mobile = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $advocate = $stmt->fetch();
    } elseif (!empty($_SESSION['advocate_id'])) {
        $stmt = $db->prepare("SELECT a.*, s.name as state_name, d.name as district_name, bc.name as bar_council_name, ba.name as association_name 
                              FROM advocate a 
                              LEFT JOIN state s ON a.state_code = s.code 
                              LEFT JOIN district d ON a.district_code = d.code 
                              LEFT JOIN bc ON a.bc_id = bc.id 
                              LEFT JOIN ba ON a.ba_code = ba.code 
                              WHERE a.id = ? LIMIT 1");
        $stmt->execute([$_SESSION['advocate_id']]);
        $advocate = $stmt->fetch();
    }
} catch (Exception $e) {
    $advocate = null;
}

if ($advocate && !empty($advocate['public_url'])) {
    $hasPublicParam = false;
    if (!empty($_SERVER['QUERY_STRING']) && (stripos($_SERVER['QUERY_STRING'], 'public=') !== false || stripos($_SERVER['QUERY_STRING'], 'link=public=') !== false)) {
        $hasPublicParam = true;
    }
    if (!$hasPublicParam && !empty($_GET['id'])) {
        header("Location: profile.php?link=public=" . urlencode(trim($advocate['public_url'])), true, 301);
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

// Check if currently logged in user is viewing their own profile
$isOwnProfile = (!empty($_SESSION['advocate_id']) && !empty($advocate['id']) && (int)$_SESSION['advocate_id'] === (int)$advocate['id']);

// Prepare profile display variables
$badge = getVerificationBadge($advocate);
$stateName = $advocate['state_name'] ?: getStateName($advocate['state_code'] ?? '');
$districtName = $advocate['district_name'] ?: getDistrictName($advocate['district_code'] ?? '');
$courtName = getCourtName($advocate['court'] ?? '');
$baName = $advocate['association_name'] ?: getBarAssociationName($advocate['ba_code'] ?? '');
$bcName = $advocate['bar_council_name'] ?? ($stateName ? "Bar Council of " . $stateName : "State Bar Council");
$practices = parsePracticeAreas($advocate['practice_area'] ?? '');

$maskedMobile = maskContactInfo($advocate['mobile'] ?? '', $advocate['mobile_visibility'] ?? 'REGISTERED', $isOwnProfile);
$maskedEmail = maskContactInfo($advocate['email'] ?? '', $advocate['email_visibility'] ?? 'REGISTERED', $isOwnProfile);
$maskedAddress = maskContactInfo($advocate['address'] ?? '', $advocate['address_visibility'] ?? 'PRIVATE', $isOwnProfile);

// Education & Experience & Enrollment logic
$educationDegree = getEducationDegree($advocate['education'] ?? '');
$experienceStr = formatPracticeExperience($advocate['e_year'] ?? '');
$enrollmentStatus = formatEnrollmentNumber($advocate['e_no'] ?? '');

$canonicalUrl = APP_URL . '/' . getAdvocateUrl($advocate);
$pageTitle = ($advocate['name'] ?: 'Advocate Profile') . " - Advocate Directory";
$pageDescription = "Official digital profile for Advocate " . ($advocate['name'] ?? '') . ", practicing at " . $courtName . ", " . $districtName . ", " . $stateName . ".";

// Fetch Related Advocates (from same district or state)
$relatedAdvocates = [];
try {
    $rStmt = $db->prepare("SELECT id, name, photo, practice_area, public_url, plan_type, type, state_code, district_code 
                           FROM advocate 
                           WHERE status = 'ACTIVE' AND id != ? AND (district_code = ? OR state_code = ?) 
                           ORDER BY (district_code = ?) DESC, id DESC LIMIT 3");
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
                <?php if (!empty($advocate['photo'])): ?>
                    <img src="<?= sanitize($advocate['photo']) ?>" alt="<?= sanitize($advocate['name']) ?>" style="width:100%; height:100%; object-fit:cover; border-radius:inherit;">
                <?php else: ?>
                    <?= strtoupper(substr(trim($advocate['name'] ?: 'A'), 0, 1)) ?>
                <?php endif; ?>
            </div>

            <div class="profile-title-area">
                <div style="margin-bottom: 0.5rem;">
                    <span class="badge-verification <?= $badge['badge_class'] ?>">
                        <i class="fas <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                    </span>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;">
                        (<?= $badge['desc'] ?>)
                    </span>
                </div>
                <h1><?= sanitize($advocate['name'] ?: 'Advocate') ?></h1>
                
                <div class="profile-subhead">
                    <span><i class="fas fa-id-card" style="color: var(--brand-red);"></i> Enrollment: <strong><?= sanitize($enrollmentStatus) ?></strong></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-location-dot" style="color: var(--brand-accent);"></i> <?= sanitize($districtName ?: 'District') ?><?= $stateName ? ', ' . sanitize($stateName) : '' ?></span>
                    <span>&bull;</span>
                    <span><i class="fas fa-gavel"></i> <?= sanitize($courtName) ?></span>
                </div>

                <div class="practice-tags" style="margin-bottom: 0;">
                    <?php foreach ($practices as $p): ?>
                        <span class="practice-pill"><i class="fas fa-scale-balanced" style="font-size: 0.7rem; color: var(--brand-red);"></i> <?= sanitize($p) ?></span>
                    <?php endforeach; ?>
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
                    <div>
                        <div class="stat-label">Languages Known</div>
                        <div style="font-weight: 600;"><?= sanitize($advocate['languages'] ?? 'Hindi, English') ?></div>
                    </div>
                    <div>
                        <div class="stat-label">Education / Degree</div>
                        <div style="font-weight: 600;"><?= sanitize($educationDegree) ?></div>
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

            <!-- Areas of Practice Card -->
            <div class="stat-box">
                <h2 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-scale-balanced" style="color: var(--brand-red);"></i> Areas of Specialization</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                    <?php foreach ($practices as $practice): ?>
                        <div style="background: var(--bg-alt); padding: 0.75rem 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check-circle" style="color: #059669;"></i>
                            <span style="font-weight: 600; font-size: 0.875rem;"><?= sanitize($practice) ?></span>
                        </div>
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

                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Connect with Advocate " . $advocate['name'] . " on My Advocate: " . $canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="width: 100%; justify-content: center; color: #16a34a; border-color: #86efac;">
                        <i class="fab fa-whatsapp me-1"></i> Share on WhatsApp
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
                                <?php if (!empty($rel['photo'])): ?>
                                    <img src="<?= sanitize($rel['photo']) ?>" alt="<?= sanitize($rel['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
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
