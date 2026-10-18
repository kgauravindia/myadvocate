<?php
// law-college.php - Comprehensive Official Law College Profile & Institution Directory
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
    if (is_array($_GET['link'])) {
        foreach ($_GET['link'] as $candidate) {
            $decoded = @base64_decode($candidate, true);
            if ($decoded && preg_match('/[a-zA-Z0-9_]+=/', $decoded)) {
                parse_str($decoded, $p);
                if (!empty($p['id'])) {
                    $collegeId = (int)$p['id'];
                    break;
                }
            } elseif (is_numeric($candidate)) {
                $collegeId = (int)$candidate;
                break;
            }
        }
    } else {
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

// Normalize strings from law_college record
$rawName = $college['name'] ?? 'Law College';
$collegeName = preg_replace('/\s+/', ' ', trim($rawName));
$collegeState = preg_replace('/\s+/', ' ', trim($college['state'] ?? 'India'));

$univRaw = $college['affiliating_university'] ?? 'University';
$cleanUniv = preg_replace('/\s+/', ' ', trim($univRaw));
$historicalAffiliation = '';
if (preg_match('/\((Earlier.*)\)/i', $cleanUniv, $mHist)) {
    $historicalAffiliation = trim($mHist[1]);
    $cleanUniv = trim(str_replace($mHist[0], '', $cleanUniv));
}

// 1. Fetch ALL approved courses offered by this college
$allCoursesRaw = [];
try {
    $cStmt = $db->prepare("SELECT id, course_name, approval_till, remarks, e_year 
                           FROM law_college 
                           WHERE name = ? OR TRIM(name) = ? 
                           ORDER BY id ASC");
    $cStmt->execute([$rawName, trim($rawName)]);
    $allCoursesRaw = $cStmt->fetchAll();
    if (empty($allCoursesRaw)) {
        $allCoursesRaw = [$college];
    }
} catch (Exception $e) {
    $allCoursesRaw = [$college];
}

// Parse courses and sum up annual seat intake
$totalIntake = 0;
$distinctCourses = [];
$latestApproval = trim($college['approval_till'] ?? '');
$earliestEstYear = trim($college['e_year'] ?? '');
$allRemarks = [];

if (!empty($college['remarks'])) {
    $allRemarks[] = trim($college['remarks']);
}

foreach ($allCoursesRaw as $cRow) {
    $cName = preg_replace('/\s+/', ' ', trim($cRow['course_name'] ?? ''));
    if (empty($cName)) continue;

    $appr = trim($cRow['approval_till'] ?? '');
    if (!empty($appr) && empty($latestApproval)) {
        $latestApproval = $appr;
    }

    $yr = trim($cRow['e_year'] ?? '');
    if (!empty($yr) && (empty($earliestEstYear) || (int)$yr < (int)$earliestEstYear)) {
        $earliestEstYear = $yr;
    }

    if (!empty($cRow['remarks']) && !in_array(trim($cRow['remarks']), $allRemarks)) {
        $allRemarks[] = trim($cRow['remarks']);
    }

    // Extract seat numbers (e.g., "(180)", "(120)", "(180 + 60)")
    $intakeStr = '';
    if (preg_match('/\((\d+(?:\s*\+\s*\d+)?)\)/', $cName, $mIntake)) {
        $intakeStr = trim($mIntake[1]) . ' Seats';
        $parts = explode('+', $mIntake[1]);
        foreach ($parts as $p) {
            $totalIntake += (int)trim($p);
        }
        $displayCourseName = trim(str_replace($mIntake[0], '', $cName));
    } else {
        $displayCourseName = $cName;
    }

    $displayCourseName = trim(preg_replace('/^(course|degree)\s*/i', '', $displayCourseName));
    $displayCourseName = trim(preg_replace('/\s*(course|degree)$/i', '', $displayCourseName));

    $is5Year = (stripos($cName, '5') !== false || stripos($cName, 'five') !== false || stripos($cName, 'integrated') !== false);
    $is3Year = (stripos($cName, '3') !== false || stripos($cName, 'three') !== false);
    $isLlm = (stripos($cName, 'llm') !== false || stripos($cName, 'll.m') !== false || stripos($cName, 'master') !== false);
    $isBba = (stripos($cName, 'bba') !== false);
    $isBcom = (stripos($cName, 'b.com') !== false || stripos($cName, 'bcom') !== false);
    $isBsc = (stripos($cName, 'b.sc') !== false || stripos($cName, 'bsc') !== false);

    $duration = $is5Year ? '5 Years Integrated' : ($isLlm ? '2 Years Postgraduate' : '3 Years Professional');
    $icon = $isLlm ? 'fa-award' : ($isBba ? 'fa-briefcase' : ($isBcom ? 'fa-chart-line' : ($isBsc ? 'fa-flask' : 'fa-scale-balanced')));
    $badge = $is5Year ? '10+2 / Intermediate' : ($isLlm ? 'Master of Laws' : 'Graduates in Any Stream');
    $degreeType = $is5Year ? 'Integrated Dual Degree' : ($isLlm ? 'Master Degree' : 'Bachelor of Laws');

    $distinctCourses[] = [
        'id' => $cRow['id'],
        'raw_name' => $cName,
        'clean_name' => $displayCourseName ?: $cName,
        'intake' => $intakeStr,
        'approval' => $appr ?: $latestApproval,
        'duration' => $duration,
        'icon' => $icon,
        'badge' => $badge,
        'degree_type' => $degreeType
    ];
}

// 2. Fetch sister colleges under the same Affiliating University
$sisterColleges = [];
try {
    if (!empty($cleanUniv)) {
        $univSearchKey = substr($cleanUniv, 0, 35);
        $sStmt = $db->prepare("SELECT MIN(id) as id, name, state, MIN(course_name) as course_name, MIN(approval_till) as approval_till 
                               FROM law_college 
                               WHERE affiliating_university LIKE ? AND name != ? AND (status = 'ACTIVE' OR status = '' OR status IS NULL)
                               GROUP BY name, state 
                               ORDER BY id ASC LIMIT 4");
        $sStmt->execute(['%' . $univSearchKey . '%', $rawName]);
        $sisterColleges = $sStmt->fetchAll();
    }
} catch (Exception $e) {
    $sisterColleges = [];
}

// 3. Fetch other law colleges in the same State
$relatedColleges = [];
try {
    if (!empty($collegeState)) {
        $stStmt = $db->prepare("SELECT MIN(id) as id, name, state, MIN(affiliating_university) as affiliating_university, MIN(course_name) as course_name 
                                FROM law_college 
                                WHERE state = ? AND name != ? AND (status = 'ACTIVE' OR status = '' OR status IS NULL)
                                GROUP BY name, state 
                                ORDER BY id ASC LIMIT 4");
        $stStmt->execute([$college['state'], $rawName]);
        $relatedColleges = $stStmt->fetchAll();
    }
} catch (Exception $e) {
    $relatedColleges = [];
}

// 4. Resolve State Code and fetch active practicing advocates in the state
$allStates = getStates();
$stateCode = '';
foreach ($allStates as $sc => $sn) {
    if (strcasecmp($sn, $collegeState) === 0 || stripos($collegeState, $sn) !== false || stripos($sn, $collegeState) !== false) {
        $stateCode = $sc;
        break;
    }
}

$stateAdvocates = [];
if (!empty($stateCode)) {
    try {
        $advStmt = $db->prepare("SELECT id, name, photo, practice_area, public_url, court, e_year 
                                 FROM advocate 
                                 WHERE state_code = ? AND type IN ('ACTIVE', 'VERIFIED') AND (status != 'BLOCK' OR status IS NULL) 
                                 ORDER BY (photo IS NOT NULL AND photo != '' AND photo != '0') DESC, id DESC LIMIT 3");
        $advStmt->execute([$stateCode]);
        $stateAdvocates = $advStmt->fetchAll();
    } catch (Exception $e) {
        $stateAdvocates = [];
    }
}

// Page Metadata & Canonical URLs
$pageTitle = sanitize($collegeName) . " - " . sanitize($collegeState) . " | BCI Approved Law College | My Advocate";
$pageDescription = sanitize($collegeName) . " in " . sanitize($collegeState) . " affiliated with " . sanitize($cleanUniv) . ". Explore BCI approval status, legal courses, seat intake, and bar enrollment details.";

$canonicalUrl = APP_URL . "/law-college?id=" . $college['id'];
$encodedId = base64_encode("id=" . $college['id']);
$cSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $collegeName));
$shareUrl = APP_URL . "/law-college?link=" . urlencode($cSlug) . "&link=" . urlencode($encodedId);

require_once INCLUDES_PATH . '/header.php';
?>

<!-- JSON-LD Structured Data Schema for Search Engines -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollegeOrUniversity",
  "name": <?= json_encode($collegeName) ?>,
  "description": <?= json_encode($pageDescription) ?>,
  "url": <?= json_encode($canonicalUrl) ?>,
  "address": {
    "@type": "PostalAddress",
    "addressRegion": <?= json_encode($collegeState) ?>,
    "addressCountry": "IN"
  },
  "parentOrganization": {
    "@type": "EducationalOrganization",
    "name": <?= json_encode($cleanUniv) ?>
  }
}
</script>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="law-college-search" style="color: var(--primary); text-decoration: none;">Law Colleges</a> &bull; 
        <?php if (!empty($collegeState)): ?>
            <a href="law-college-search?state=<?= urlencode($collegeState) ?>" style="color: var(--primary); text-decoration: none;"><?= sanitize($collegeState) ?></a> &bull; 
        <?php endif; ?>
        <span><?= sanitize($collegeName) ?></span>
    </nav>

    <!-- Main College Hero Banner Card -->
    <div class="stat-box" style="padding: 2.25rem 2rem; margin-bottom: 1.75rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            
            <div style="display: flex; gap: 1.25rem; align-items: flex-start; max-width: 820px;">
                <!-- College Seal Icon -->
                <div style="width: 72px; height: 72px; border-radius: 18px; background: linear-gradient(135deg, #fee2e2 0%, #fef3c7 100%); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; border: 1px solid #fecaca; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-building-columns"></i>
                </div>

                <div>
                    <!-- Regulatory & Accreditation Badges -->
                    <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.65rem;">
                        <span class="badge-verification badge-verified" style="font-size: 0.78rem; padding: 0.25rem 0.65rem; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                            <i class="fas fa-shield-halved"></i> Bar Council of India (BCI) Recognized
                        </span>
                        <?php if (!empty($earliestEstYear)): ?>
                            <span class="badge-verification badge-basic" style="font-size: 0.78rem; padding: 0.25rem 0.65rem;">
                                <i class="fas fa-calendar-check"></i> Est. <?= sanitize($earliestEstYear) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($latestApproval)): ?>
                            <span class="badge-verification" style="font-size: 0.78rem; padding: 0.25rem 0.65rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                <i class="fas fa-clock"></i> Approval: <?= sanitize($latestApproval) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($totalIntake > 0): ?>
                            <span class="badge-verification" style="font-size: 0.78rem; padding: 0.25rem 0.65rem; background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">
                                <i class="fas fa-users"></i> <?= $totalIntake ?> Annual Approved Intake
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- College Title -->
                    <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem; line-height: 1.3;">
                        <?= sanitize($collegeName) ?>
                    </h1>

                    <!-- Location & Affiliation Summary -->
                    <div style="display: flex; flex-wrap: wrap; gap: 1.25rem; color: var(--text-muted); font-size: 0.9375rem; margin-top: 0.5rem;">
                        <span>
                            <i class="fas fa-location-dot" style="color: var(--brand-red); margin-right: 5px;"></i>
                            <strong><?= sanitize($collegeState) ?></strong>, India
                        </span>
                        <?php if (!empty($cleanUniv)): ?>
                        <span>
                            <i class="fas fa-university" style="color: var(--brand-gold-dark); margin-right: 5px;"></i>
                            Affiliation: <strong><?= sanitize($cleanUniv) ?></strong>
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($historicalAffiliation)): ?>
                        <div style="margin-top: 0.4rem; font-size: 0.8125rem; color: var(--text-muted); font-style: italic;">
                            <i class="fas fa-history" style="margin-right: 4px;"></i> <?= sanitize($historicalAffiliation) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div style="display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center;">
                <a href="foreign-law-universities" class="btn btn-outline btn-sm" title="Foreign Universities whose degrees in law recognized by BCI">
                    <i class="fas fa-globe me-1"></i> Foreign Universities (BCI)
                </a>
                <a href="https://api.whatsapp.com/send?text=<?= urlencode("Check out $collegeName ($cleanUniv) on My Advocate: $shareUrl") ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm" style="background: #25d366; color: #ffffff; border: none; font-weight: 600;">
                    <i class="fab fa-whatsapp me-1"></i> WhatsApp
                </a>
                <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= sanitize($shareUrl) ?>" title="Copy link to clipboard">
                    <i class="fas fa-share-nodes me-1"></i> Share
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="window.print();" title="Print institution details">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Institution Jump Search Bar -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1.25rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 0.875rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-magnifying-glass" style="color: var(--brand-red);"></i>
            <span>Looking for another institution?</span>
        </div>
        <form action="law-college-search" method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 580px; min-width: 260px;">
            <input type="text" name="q" class="filter-input" placeholder="Search 5,000+ Law Colleges by name, university, or city..." style="min-height: 38px; font-size: 0.85rem;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0 1rem; font-weight: 600;">
                Search
            </button>
        </form>
    </div>

    <!-- Main Grid Content: 2 Columns -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start;" class="college-profile-grid">
        <style>
            @media(max-width: 991px) {
                .college-profile-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            .course-feature-card {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                padding: 1.25rem;
                transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            }
            .course-feature-card:hover {
                transform: translateY(-2px);
                border-color: var(--brand-gold);
                box-shadow: var(--shadow-sm);
            }
            @media print {
                header, footer, nav, .btn, form {
                    display: none !important;
                }
                .stat-box {
                    border: 1px solid #ccc !important;
                    box-shadow: none !important;
                }
            }
        </style>
        
        <!-- Left Column: Courses, Recognition, and Institution Overview -->
        <div style="display: flex; flex-direction: column; gap: 1.75rem;">
            
            <!-- Legal Education Programs Card -->
            <div class="stat-box" style="padding: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-graduation-cap" style="color: var(--brand-red);"></i> Approved Legal Education Programs
                    </h2>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--brand-red); background: var(--brand-red-light); border: 1px solid var(--brand-red-border); padding: 0.25rem 0.65rem; border-radius: var(--radius-full);">
                        <?= count($distinctCourses) ?> <?= count($distinctCourses) === 1 ? 'Approved Program' : 'Approved Programs' ?>
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($distinctCourses as $crs): ?>
                        <div class="course-feature-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 42px; height: 42px; border-radius: 10px; background: #fee2e2; color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; flex-shrink: 0;">
                                        <i class="fas <?= $crs['icon'] ?>"></i>
                                    </div>
                                    <div>
                                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin: 0 0 0.15rem;">
                                            <?= sanitize($crs['clean_name']) ?>
                                        </h3>
                                        <div style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;">
                                            <span><i class="fas fa-clock me-1"></i> <?= $crs['duration'] ?></span> &bull; 
                                            <span><?= $crs['degree_type'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <?php if (!empty($crs['intake'])): ?>
                                        <span class="badge-verification" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 700; font-size: 0.8rem; padding: 0.2rem 0.6rem;">
                                            <i class="fas fa-users me-1"></i> <?= sanitize($crs['intake']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.2rem 0.55rem;">
                                        <i class="fas fa-check-circle me-1"></i> BCI Approved
                                    </span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; background: var(--bg-alt); padding: 0.85rem 1rem; border-radius: var(--radius-sm); font-size: 0.8125rem;">
                                <div>
                                    <span style="color: var(--text-muted); display: block;">Entry Eligibility:</span>
                                    <strong style="color: var(--primary);"><?= $crs['badge'] ?></strong>
                                </div>
                                <div>
                                    <span style="color: var(--text-muted); display: block;">Approval Status:</span>
                                    <strong style="color: #16a34a;"><?= !empty($crs['approval']) ? 'Valid ' . sanitize($crs['approval']) : 'Approved by BCI' ?></strong>
                                </div>
                                <div>
                                    <span style="color: var(--text-muted); display: block;">Statutory Recognition:</span>
                                    <strong style="color: var(--primary);">Advocates Act, 1961</strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Affiliation & State Bar Council Eligibility -->
            <div class="stat-box" style="padding: 1.75rem;">
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-scale-balanced" style="color: var(--brand-gold);"></i> Affiliation &amp; State Bar Council Eligibility
                </h2>
                
                <div style="line-height: 1.7; color: var(--text-main); font-size: 0.9375rem;">
                    <p style="margin-bottom: 1rem;">
                        <strong><?= sanitize($collegeName) ?></strong> operates under the regulatory standards of the <strong>Bar Council of India (BCI)</strong> and is affiliated with <strong><?= sanitize($cleanUniv) ?></strong>.
                    </p>

                    <?php if (!empty($allRemarks)): ?>
                        <div style="background: #fffbeb; border-left: 4px solid var(--brand-gold); padding: 0.85rem 1.15rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; margin-bottom: 1.25rem; font-size: 0.875rem; color: #92400e;">
                            <strong style="display: block; margin-bottom: 0.25rem;"><i class="fas fa-circle-exclamation me-1"></i> Official BCI Gazette Note / Remark:</strong>
                            <?= nl2br(sanitize(implode("\n\n", $allRemarks))) ?>
                        </div>
                    <?php endif; ?>

                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem;">
                            <i class="fas fa-id-card-clip" style="color: var(--brand-red);"></i> Bar Enrollment Eligibility for Graduates
                        </h4>
                        <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0; line-height: 1.6;">
                            Graduates qualifying with an LL.B. degree from this BCI-approved institution are entitled to apply for enrollment with the <strong>State Bar Council of <?= sanitize($collegeState) ?></strong> (or any other State Bar Council in India) under Section 24 of the Advocates Act, 1961. Following provisional enrollment, advocates are eligible to appear for the <strong>All India Bar Examination (AIBE)</strong> to obtain their permanent Certificate of Practice (COP).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Sister Colleges under Same University -->
            <?php if (!empty($sisterColleges)): ?>
                <div class="stat-box" style="padding: 1.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-university" style="color: var(--brand-gold-dark);"></i> Other Colleges Affiliated with <?= sanitize($cleanUniv) ?>
                        </h3>
                        <a href="law-college-search?q=<?= urlencode($cleanUniv) ?>" style="font-size: 0.8125rem; font-weight: 600; text-decoration: none;">View All &rarr;</a>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                        <?php foreach ($sisterColleges as $sc): ?>
                            <?php 
                            $scSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($sc['name'])));
                            $scEncoded = base64_encode("id=" . $sc['id']);
                            ?>
                            <div style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h4 style="font-size: 0.925rem; font-weight: 800; color: var(--primary); margin: 0 0 0.35rem; line-height: 1.35;">
                                        <?= sanitize(trim($sc['name'])) ?>
                                    </h4>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.65rem;">
                                        <i class="fas fa-location-dot" style="color: var(--brand-red);"></i> <?= sanitize($sc['state']) ?>
                                    </div>
                                </div>
                                <a href="law-college?link=law-college-<?= $scSlug ?>&link=<?= $scEncoded ?>" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; font-size: 0.8rem;">
                                    View College <i class="fas fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Other Law Colleges in Same State -->
            <?php if (!empty($relatedColleges)): ?>
                <div class="stat-box" style="padding: 1.75rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-compass" style="color: var(--brand-red);"></i> More Law Colleges in <?= sanitize($collegeState) ?>
                        </h3>
                        <a href="law-college-search?state=<?= urlencode($collegeState) ?>" style="font-size: 0.8125rem; font-weight: 600; text-decoration: none;">All <?= sanitize($collegeState) ?> Colleges &rarr;</a>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                        <?php foreach ($relatedColleges as $rc): ?>
                            <?php 
                            $rcSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($rc['name'])));
                            $rcEncoded = base64_encode("id=" . $rc['id']);
                            ?>
                            <div style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; display: flex; flex-direction: column; justify-content: space-between;">
                                <div>
                                    <h4 style="font-size: 0.925rem; font-weight: 800; color: var(--primary); margin: 0 0 0.35rem; line-height: 1.35;">
                                        <?= sanitize(trim($rc['name'])) ?>
                                    </h4>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.65rem;">
                                        <i class="fas fa-university" style="color: var(--brand-gold-dark);"></i> <?= sanitize(trim(preg_replace('/\s+/', ' ', $rc['affiliating_university'] ?? ''))) ?>
                                    </div>
                                </div>
                                <a href="law-college?link=law-college-<?= $rcSlug ?>&link=<?= $rcEncoded ?>" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; font-size: 0.8rem;">
                                    View College <i class="fas fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column: Quick Facts, Legal Mentorship & Exam Resources -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Quick Facts Card -->
            <div class="stat-box" style="padding: 1.5rem; border-top: 4px solid var(--brand-gold);">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-circle-info" style="color: var(--brand-red);"></i> Institution Quick Facts
                </h3>

                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.9rem; font-size: 0.875rem;">
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-hashtag me-1"></i> BCI Listing ID:</span>
                        <strong style="color: var(--primary);">#<?= (int)$college['id'] ?></strong>
                    </li>

                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-shield-halved me-1"></i> Accreditation:</span>
                        <strong style="color: #16a34a;"><i class="fas fa-check-circle"></i> BCI Approved</strong>
                    </li>

                    <?php if (!empty($cleanUniv)): ?>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-university me-1"></i> University:</span>
                        <strong style="color: var(--primary); text-align: right; max-width: 170px; font-size: 0.8125rem;"><?= sanitize($cleanUniv) ?></strong>
                    </li>
                    <?php endif; ?>

                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-map me-1"></i> State Jurisdiction:</span>
                        <strong style="color: var(--primary);"><?= sanitize($collegeState) ?></strong>
                    </li>

                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-book-open me-1"></i> Approved Courses:</span>
                        <strong style="color: var(--primary);"><?= count($distinctCourses) ?> Courses</strong>
                    </li>

                    <?php if ($totalIntake > 0): ?>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-users me-1"></i> Total Annual Intake:</span>
                        <strong style="color: #b45309;"><?= $totalIntake ?> Seats</strong>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($latestApproval)): ?>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);"><i class="fas fa-calendar-check me-1"></i> Approval Validity:</span>
                        <strong style="color: var(--primary);"><?= sanitize($latestApproval) ?></strong>
                    </li>
                    <?php endif; ?>

                    <?php if (!empty($earliestEstYear)): ?>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);"><i class="fas fa-calendar me-1"></i> Established:</span>
                        <strong style="color: var(--primary);"><?= sanitize($earliestEstYear) ?></strong>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Practicing Advocates & Legal Network in this State -->
            <?php if (!empty($stateAdvocates)): ?>
                <div class="stat-box" style="padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0;">
                            <i class="fas fa-user-tie" style="color: var(--brand-red);"></i> Advocates in <?= sanitize($collegeState) ?>
                        </h4>
                        <a href="advocate-search-result?state=<?= urlencode($stateCode) ?>" style="font-size: 0.75rem; font-weight: 600;">Explore &rarr;</a>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($stateAdvocates as $advItem): 
                            $advPhoto = getAdvocatePhotoUrl($advItem['photo'] ?? '');
                            $advUrl = getAdvocateUrl($advItem);
                        ?>
                            <a href="<?= $advUrl ?>" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem; background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-sm); text-decoration: none; color: var(--text-main); transition: var(--transition);" onmouseover="this.style.borderColor='var(--brand-gold)';" onmouseout="this.style.borderColor='var(--border-color)';">
                                <div style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #475569; flex-shrink: 0;">
                                    <?php if (!empty($advPhoto)): ?>
                                        <img src="<?= sanitize($advPhoto) ?>" alt="<?= sanitize($advItem['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr(trim($advItem['name'] ?: 'A'), 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="overflow: hidden; flex: 1;">
                                    <div style="font-weight: 700; font-size: 0.875rem; color: var(--primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        Advocate <?= sanitize($advItem['name']) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= sanitize($advItem['court'] ?: 'District & High Court') ?>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right" style="font-size: 0.7rem; color: var(--text-muted);"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All India Bar Examination (AIBE) Hub -->
            <div class="stat-box" style="padding: 1.5rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: var(--radius-md);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-scale-balanced" style="color: var(--brand-gold); font-size: 1.25rem;"></i>
                    <h4 style="font-size: 1.05rem; font-weight: 800; color: #ffffff; margin: 0;">
                        Preparing for AIBE?
                    </h4>
                </div>
                <p style="font-size: 0.8125rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem;">
                    Access official syllabus weightage, Bare Acts library, and Bar Examination guidance for law graduates.
                </p>
                <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                    <a href="aibe" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center; font-weight: 600;">
                        <i class="fas fa-graduation-cap me-1"></i> AIBE Examination Portal
                    </a>
                    <a href="acts" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; color: #ffffff; border-color: #475569;">
                        <i class="fas fa-book-bookmark me-1"></i> Browse Bare Acts Library
                    </a>
                </div>
            </div>

            <!-- Foreign Law Universities BCI List Card -->
            <div class="stat-box" style="padding: 1.5rem; background: #fffbfb; border: 1px solid #fee2e2; border-radius: var(--radius-md);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span style="font-size: 1.25rem;">🌐</span>
                    <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin: 0;">
                        Foreign Law Degrees (BCI)
                    </h4>
                </div>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.85rem;">
                    Check foreign universities in UK, Australia, Canada whose law degrees are recognized by the Bar Council of India.
                </p>
                <a href="foreign-law-universities" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; font-weight: 700; color: var(--brand-red); border-color: #fca5a5;">
                    <i class="fas fa-globe me-1"></i> Recognized Foreign Universities
                </a>
            </div>

            <!-- Share & Save Card -->
            <div class="stat-box" style="padding: 1.5rem; text-align: center;">
                <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
                    Share Institution Profile
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Share this verified legal institution with law students, legal aspirants, and faculty.
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Check out $collegeName ($cleanUniv) on My Advocate: $shareUrl") ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="color: #16a34a; border-color: #86efac;">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= sanitize($shareUrl) ?>">
                        <i class="fas fa-link me-1"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
