<?php
// law-college.php - Detailed Law College Profile & Institution Page
require_once __DIR__ . '/config/app.php';

$db = getDB();
$collegeId = 0;
$slugQuery = '';

if (!empty($_GET['id'])) {
    $collegeId = (int)$_GET['id'];
}

// Parse link parameters (supports multiple &link=... and base64 encoded id params like aWQ9MjIy)
if (!empty($_SERVER['QUERY_STRING'])) {
    $pairs = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($pairs as $pair) {
        $kv = explode('=', $pair, 2);
        $k = urldecode($kv[0] ?? '');
        $v = urldecode($kv[1] ?? '');
        if (in_array(strtolower($k), ['link', 'l', 'id'])) {
            $decoded = @base64_decode($v, true);
            if ($decoded && preg_match('/[a-zA-Z0-9_]+=/', $decoded)) {
                parse_str($decoded, $p);
                if (!empty($p['id'])) {
                    $collegeId = (int)$p['id'];
                }
            } elseif (is_numeric($v)) {
                $collegeId = (int)$v;
            } elseif (empty($slugQuery) && strlen($v) > 3 && !preg_match('/^[a-zA-Z0-9+\/]+=*$/', $v)) {
                $slugQuery = $v;
            }
        }
    }
}

// Also check direct $_GET['link'] if it's base64 encoded
if (empty($collegeId) && !empty($_GET['link'])) {
    $decoded = @base64_decode($_GET['link'], true);
    if ($decoded && preg_match('/[a-zA-Z0-9_]+=/', $decoded)) {
        parse_str($decoded, $p);
        if (!empty($p['id'])) {
            $collegeId = (int)$p['id'];
        }
    } elseif (is_numeric($_GET['link'])) {
        $collegeId = (int)$_GET['link'];
    }
}

$college = null;
if ($collegeId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM law_college WHERE id = ? LIMIT 1");
        $stmt->execute([$collegeId]);
        $college = $stmt->fetch();
    } catch (Exception $e) {
        $college = null;
    }
}

// Fallback search by slug if ID not resolved
if (!$college && !empty($slugQuery)) {
    try {
        $cleanSlug = str_replace(['law-college-', 'law-college', '-'], [' ', ' ', ' '], $slugQuery);
        $cleanSlug = trim(preg_replace('/\s+/', ' ', $cleanSlug));
        if (strlen($cleanSlug) >= 3) {
            $stmt = $db->prepare("SELECT * FROM law_college WHERE name LIKE ? OR (name LIKE ? AND state LIKE ?) LIMIT 1");
            $stmt->execute(["%" . $cleanSlug . "%", "%" . substr($cleanSlug, 0, 15) . "%", "%" . substr($cleanSlug, -10) . "%"]);
            $college = $stmt->fetch();
        }
    } catch (Exception $e) {
        $college = null;
    }
}

// If no specific college is found, gracefully route to the law college search directory
if (!$college) {
    require_once __DIR__ . '/law-college-search.php';
    exit;
}

// Fetch 4 other law colleges in the same state for related recommendations
$relatedColleges = [];
try {
    if (!empty($college['state'])) {
        $relStmt = $db->prepare("SELECT * FROM law_college WHERE state = ? AND id != ? AND (status = 'ACTIVE' OR status = '' OR status IS NULL) AND name != '' ORDER BY RAND() LIMIT 3");
        $relStmt->execute([$college['state'], $college['id']]);
        $relatedColleges = $relStmt->fetchAll();
    }
} catch (Exception $e) {
    $relatedColleges = [];
}

$collegeName = trim($college['name'] ?? 'Law College');
$collegeState = trim($college['state'] ?? 'India');
$univName = trim(preg_replace('/\s+/', ' ', $college['affiliating_university'] ?? 'University'));
$courseName = trim($college['course_name'] ?? '');
$approvalTill = trim($college['approval_till'] ?? '');
$estYear = trim($college['e_year'] ?? '');
$remarks = trim($college['remarks'] ?? '');

$pageTitle = sanitize($collegeName) . " | " . sanitize($univName) . " - " . sanitize($collegeState) . " | My Advocate";
$pageDescription = sanitize($collegeName) . ", " . sanitize($collegeState) . " information and legal courses available on My Advocate.";

// Generate base64 link for sharing
$encodedId = base64_encode("id=" . $college['id']);
$shareUrl = "https://myadv.in/law-college?link=" . urlencode(strtolower(preg_replace('/[^a-z0-9]+/i', '-', $collegeName))) . "&link=" . urlencode($encodedId);

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="law-college-search" style="color: var(--primary); text-decoration: none;">Law Colleges</a> &bull; 
        <?php if (!empty($collegeState)): ?>
            <a href="law-college-search?state=<?= urlencode($collegeState) ?>" style="color: var(--primary); text-decoration: none;"><?= sanitize($collegeState) ?></a> &bull; 
        <?php endif; ?>
        <span><?= sanitize($collegeName) ?></span>
    </nav>

    <!-- Main College Header Hero Card -->
    <div class="stat-box" style="padding: 2.25rem 2rem; margin-bottom: 2rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            
            <div style="display: flex; gap: 1.25rem; align-items: flex-start; max-width: 780px;">
                <!-- College Emblem Icon -->
                <div style="width: 64px; height: 64px; border-radius: 16px; background: linear-gradient(135deg, #fee2e2 0%, #fef3c7 100%); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.85rem; flex-shrink: 0; border: 1px solid #fecaca; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-building-columns"></i>
                </div>

                <div>
                    <!-- Badges -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.5rem;">
                        <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                            <i class="fas fa-shield-halved"></i> Bar Council of India (BCI) Approved
                        </span>
                        <?php if (!empty($estYear)): ?>
                            <span class="badge-verification badge-basic" style="font-size: 0.75rem; padding: 0.2rem 0.6rem;">
                                <i class="fas fa-calendar-check"></i> Est. <?= sanitize($estYear) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($approvalTill)): ?>
                            <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.6rem; background: #f0fdf4; color: #166534; border-color: #86efac;">
                                <i class="fas fa-clock"></i> Approval: <?= sanitize($approvalTill) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- College Title -->
                    <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 0.35rem; line-height: 1.25;">
                        <?= sanitize($collegeName) ?>
                    </h1>

                    <!-- Location & Affiliation -->
                    <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; color: var(--text-muted); font-size: 0.9rem; margin-top: 0.65rem;">
                        <span>
                            <i class="fas fa-location-dot" style="color: var(--brand-red); margin-right: 4px;"></i>
                            <strong><?= sanitize($collegeState) ?></strong>
                        </span>
                        <?php if (!empty($univName)): ?>
                        <span>
                            <i class="fas fa-university" style="color: var(--brand-gold-dark); margin-right: 4px;"></i>
                            Affiliation: <strong><?= sanitize($univName) ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Actions -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="law-college-search?state=<?= urlencode($collegeState) ?>" class="btn btn-primary btn-md">
                    <i class="fas fa-list"></i> <?= sanitize($collegeState) ?> Colleges
                </a>
                <a href="law-college-search" class="btn btn-outline btn-md">
                    <i class="fas fa-search"></i> Search All Colleges
                </a>
            </div>
        </div>
    </div>

    <!-- 2-Column Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.75rem; align-items: flex-start;" class="dash-grid">
        <style>
            @media(max-width: 991px) {
                .dash-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
        
        <!-- Left Column: Academic Programs & Details -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Academic Programs Card -->
            <div class="stat-box" style="padding: 1.75rem;">
                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-graduation-cap" style="color: var(--brand-red);"></i> Legal Education Programs Offered
                </h2>
                
                <?php if (!empty($courseName)): ?>
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.25rem;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: #1e40af; text-transform: uppercase; margin-bottom: 0.25rem;">Approved Course / Seat Intake</div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #1e3a8a; line-height: 1.4;">
                            <?= sanitize($courseName) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                    <!-- 3-Year LL.B. -->
                    <div style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: var(--primary); font-size: 1.05rem;">3-Year LL.B.</span>
                            <span class="badge-verification badge-verified" style="font-size: 0.7rem;">Graduate</span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                            Three-year Bachelor of Laws professional curriculum for graduates in any academic stream.
                        </p>
                        <div style="font-size: 0.75rem; color: var(--text-main); font-weight: 600;">
                            <i class="fas fa-check text-success" style="margin-right: 3px;"></i> Bar Council of India Recognized
                        </div>
                    </div>

                    <!-- 5-Year Integrated B.A. LL.B. -->
                    <div style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span style="font-weight: 700; color: var(--primary); font-size: 1.05rem;">5-Year B.A. LL.B.</span>
                            <span class="badge-verification badge-verified" style="font-size: 0.7rem;">Integrated</span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                            Five-year comprehensive dual degree program in Arts and Jurisprudence for 10+2 / Intermediate candidates.
                        </p>
                        <div style="font-size: 0.75rem; color: var(--text-main); font-weight: 600;">
                            <i class="fas fa-check text-success" style="margin-right: 3px;"></i> Dual Degree Course
                        </div>
                    </div>
                </div>
            </div>

            <!-- Affiliation & Legal Context -->
            <div class="stat-box" style="padding: 1.75rem;">
                <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> Affiliation &amp; State Bar Council Eligibility
                </h2>
                <div style="line-height: 1.7; color: var(--text-main); font-size: 0.9375rem;">
                    <p style="margin-bottom: 1rem;">
                        <strong><?= sanitize($collegeName) ?></strong> is affiliated with <strong><?= sanitize($univName) ?></strong> and operates under the statutory oversight of the <strong>Bar Council of India (BCI)</strong>.
                    </p>
                    <?php if (!empty($remarks)): ?>
                        <div style="background: #fffbeb; border-left: 4px solid var(--brand-gold); padding: 0.85rem 1rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 1rem; font-size: 0.875rem; color: #92400e;">
                            <strong>Official Gazette / Remarks:</strong> <?= sanitize($remarks) ?>
                        </div>
                    <?php endif; ?>
                    <p style="margin-bottom: 0;">
                        Candidates graduating with an LL.B. degree from this institution are fully eligible to apply for provisional enrollment with the State Bar Council of <strong><?= sanitize($collegeState) ?></strong> and sit for the All India Bar Examination (AIBE).
                    </p>
                </div>
            </div>

            <!-- Related Colleges in the State -->
            <?php if (!empty($relatedColleges)): ?>
                <div class="stat-box" style="padding: 1.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-compass" style="color: var(--brand-red);"></i> Other Law Colleges in <?= sanitize($collegeState) ?>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <?php foreach ($relatedColleges as $rc): ?>
                            <?php 
                            $rcEncoded = base64_encode("id=" . $rc['id']);
                            $rcSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($rc['name'])));
                            ?>
                            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; background: var(--bg-card); display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; line-height: 1.3;">
                                        <?= sanitize(trim($rc['name'])) ?>
                                    </h4>
                                    <div style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                                        <i class="fas fa-location-dot" style="color: var(--brand-red);"></i> <?= sanitize($rc['state']) ?>
                                    </div>
                                </div>
                                <a href="law-college?link=law-college-<?= $rcSlug ?>&link=<?= $rcEncoded ?>" class="btn btn-outline-primary btn-sm" style="width: 100%; justify-content: center; font-size: 0.8125rem;">
                                    View College Details <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin-left: 3px;"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Key Facts & Contact Box -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Quick Facts Sidebar -->
            <div class="stat-box" style="padding: 1.5rem; border-top: 4px solid var(--brand-gold);">
                <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem;">
                    <i class="fas fa-circle-info text-primary"></i> Institution Quick Facts
                </h3>

                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-hashtag" style="width: 16px;"></i> Registry ID:</span>
                        <strong style="color: var(--primary);">#<?= (int)$college['id'] ?></strong>
                    </li>

                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-landmark" style="width: 16px;"></i> Recognition:</span>
                        <strong style="color: #16a34a;"><i class="fas fa-check-circle"></i> BCI Approved</strong>
                    </li>

                    <?php if (!empty($univName)): ?>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-university" style="width: 16px;"></i> University:</span>
                        <strong style="color: var(--primary); text-align: right; max-width: 140px; font-size: 0.8125rem;"><?= sanitize($univName) ?></strong>
                    </li>
                    <?php endif; ?>

                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-map" style="width: 16px;"></i> State:</span>
                        <strong style="color: var(--primary);"><?= sanitize($collegeState) ?></strong>
                    </li>

                    <?php if (!empty($approvalTill)): ?>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-clock" style="width: 16px;"></i> Approval Validity:</span>
                        <strong style="color: var(--primary);"><?= sanitize($approvalTill) ?></strong>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($estYear)): ?>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);"><i class="fas fa-calendar" style="width: 16px;"></i> Established:</span>
                        <strong style="color: var(--primary);"><?= sanitize($estYear) ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Share College Profile -->
            <div class="stat-box" style="padding: 1.5rem; text-align: center;">
                <h4 style="font-size: 1rem; font-weight: 700; color: var(--primary); margin-bottom: 0.75rem;">
                    Share College Information
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Share this verified legal institution with law aspirants and students.
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Check out $collegeName on My Advocate: $shareUrl") ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="color: #16a34a; border-color: #86efac;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= sanitize($shareUrl) ?>">
                        <i class="fas fa-link"></i> Copy Link
                    </button>
                </div>
            </div>

            <!-- Student / Exam Quick Links -->
            <div class="stat-box" style="padding: 1.5rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff;">
                <h4 style="font-size: 1.05rem; font-weight: 800; color: #ffffff; margin-bottom: 0.5rem;">
                    <i class="fas fa-scale-balanced" style="color: var(--brand-gold);"></i> Preparing for AIBE?
                </h4>
                <p style="font-size: 0.8125rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem;">
                    Access our dedicated All India Bar Examination preparation hub with free Bare Acts and subject weightage.
                </p>
                <a href="aibe" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                    <i class="fas fa-graduation-cap"></i> AIBE Exam Hub
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
