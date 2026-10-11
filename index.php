<?php
// index.php - Modernized Homepage for My Advocate (myadv.in)
require_once __DIR__ . '/config/app.php';

$pageTitle = "My Advocate - India's Premier Legal Information & Advocate Directory Platform";
$pageDescription = "Explore India's largest advocate directory, search Bare Acts (BNS, BNSS, BSA), AIBE exam resources, Indian Courts, and Legal Calculators.";

$states = getStates();
$db = getDB();

// Dynamic Statistics (Filtered by status = 'ACTIVE')
$advocateCount = 103000;
$actsCount = 33;
$noticesCount = 80;
$collegesCount = 40000;
$baCount = 340;
$hcCount = 24;

try {
    $advocateCount = (int)$db->query("SELECT COUNT(*) FROM advocate WHERE type IN ('ACTIVE', 'VERIFIED') AND type NOT IN ('PENDING', 'BLOCK', 'DIED') AND (status != 'BLOCK' OR status IS NULL)")->fetchColumn();
    $actsCount = (int)$db->query("SELECT COUNT(*) FROM acts WHERE status = 'ACTIVE'")->fetchColumn();
    $noticesCount = (int)$db->query("SELECT COUNT(*) FROM notice WHERE status = 'ACTIVE'")->fetchColumn();
    $collegesCount = (int)$db->query("SELECT COUNT(*) FROM law_college WHERE (status = 'ACTIVE' OR status = '' OR status IS NULL) AND name != ''")->fetchColumn();
    $baCount = (int)$db->query("SELECT COUNT(*) FROM ba WHERE status = 'ACTIVE'")->fetchColumn();
    $hcCount = (int)$db->query("SELECT COUNT(*) FROM hc WHERE status = 'ACTIVE'")->fetchColumn();
} catch (Exception $e) {}

// Fetch Active Legal Notices
$latestNotices = [];
try {
    $stmt = $db->query("SELECT id, name, details, last_date, url_1, status FROM notice WHERE status = 'ACTIVE' ORDER BY (CASE WHEN last_date >= CURDATE() THEN 1 ELSE 2 END), id DESC LIMIT 4");
    $latestNotices = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Featured Central Bare Acts
$featuredActs = [];
try {
    $stmt = $db->query("SELECT id, name, year, english FROM acts WHERE status = 'ACTIVE' ORDER BY id ASC LIMIT 6");
    $featuredActs = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch Recently Joined Advocates (Newest legal professionals registered on the platform)
$recentAdvocates = [];
try {
    $stmt = $db->query("SELECT id, name, photo, type, status, public_url, practice_area, created_at, e_no, e_year, state_code, district_code
                        FROM advocate
                        WHERE (status != 'BLOCK' OR status IS NULL)
                          AND (type NOT IN ('BLOCK', 'DIED') OR type IS NULL)
                          AND name IS NOT NULL AND TRIM(name) != ''
                        ORDER BY id DESC
                        LIMIT 4");
    $recentAdvocates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($recentAdvocates)) {
        $districtCodes = array_filter(array_unique(array_column($recentAdvocates, 'district_code')));
        $districtMap = [];
        if (!empty($districtCodes)) {
            try {
                $inClause = implode(',', array_fill(0, count($districtCodes), '?'));
                $dStmt = $db->prepare("SELECT code, name FROM district WHERE code IN ($inClause)");
                $dStmt->execute(array_values($districtCodes));
                $districtMap = $dStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } catch (Exception $de) {}
        }

        foreach ($recentAdvocates as &$ra) {
            $sCode = $ra['state_code'] ?? '';
            $dCode = $ra['district_code'] ?? '';
            $ra['state_name'] = $states[$sCode] ?? $sCode;
            $ra['district_name'] = $districtMap[$dCode] ?? $dCode;
        }
        unset($ra);
    }
} catch (Exception $e) {
    $recentAdvocates = [];
}

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-tag">
                <i class="fas fa-sparkles" style="color: var(--brand-gold);"></i> Verified Legal Intelligence &bull; myadv.in
            </div>
            <h1 class="hero-title">
                Find Advocates. Explore Law.<br>
                <span>Access Legal Intelligence.</span>
            </h1>
            <p class="hero-lead">
                India's premier digital legal platform connecting citizens, legal counsel, and law students with verified enrollment directories, new criminal codes (BNS, BNSS, BSA), and judicial portals.
            </p>

            <!-- 1-Click Unified Search Card -->
            <div class="hero-search-card">
                <form action="advocate-search-result" method="GET" class="search-form-unified">
                    <div class="search-input-group">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" name="name" placeholder="Search Advocate Name, Enrollment No, Court, or Practice Area..." required>
                    </div>
                    <select name="state" class="search-select-state">
                        <option value="">All States of India</option>
                        <?php foreach ($states as $code => $name): ?>
                            <option value="<?= sanitize($code) ?>"><?= sanitize($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
            </div>

            <!-- Quick Search Tags -->
            <div class="hero-search-tags">
                <span class="hero-search-label"><i class="fas fa-bolt" style="color: var(--brand-gold-dark);"></i> Popular Searches:</span>
                <a href="advocate-search-result"><i class="fas fa-calendar-check"></i> Year Search</a>
                <a href="advocate-search-result?state=DL">Advocates in Delhi</a>
                <a href="advocate-search-result?state=UP">Advocates in UP</a>
                <a href="advocate-search-result?state=BR">Advocates in Bihar</a>
                <a href="acts?q=Bharatiya">BNS & BNSS (2023)</a>
                <a href="aibe">AIBE Exam</a>
            </div>
        </div>
    </div>
</section>

<!-- Quick Action Grid (8 Pillars) -->
<section class="quick-actions-bar">
    <div class="container">
        <div class="quick-actions-grid">
            <a href="advocate-search-result" class="quick-action-card">
                <div class="action-icon icon-red"><i class="fas fa-user-tie"></i></div>
                <div class="action-title">Advocates</div>
                <div class="action-subtitle">Directory & Search</div>
            </a>
            <a href="acts" class="quick-action-card">
                <div class="action-icon icon-gold"><i class="fas fa-book-open"></i></div>
                <div class="action-title">Bare Acts</div>
                <div class="action-subtitle">BNS, BNSS, BSA</div>
            </a>
            <a href="courts" class="quick-action-card">
                <div class="action-icon icon-black"><i class="fas fa-landmark"></i></div>
                <div class="action-title">Indian Courts</div>
                <div class="action-subtitle">SC, 25 HCs & District</div>
            </a>
            <a href="bar-associations" class="quick-action-card">
                <div class="action-icon icon-gold"><i class="fas fa-users-rectangle"></i></div>
                <div class="action-title">Bar Assocs</div>
                <div class="action-subtitle">340+ Associations</div>
            </a>
            <a href="aibe" class="quick-action-card">
                <div class="action-icon icon-red"><i class="fas fa-certificate"></i></div>
                <div class="action-title">AIBE Hub</div>
                <div class="action-subtitle">Exam & Syllabus</div>
            </a>
            <a href="college" class="quick-action-card">
                <div class="action-icon icon-black"><i class="fas fa-building-columns"></i></div>
                <div class="action-title">Law Colleges</div>
                <div class="action-subtitle">LL.B. & Universities</div>
            </a>
            <a href="tools" class="quick-action-card">
                <div class="action-icon icon-gold"><i class="fas fa-calculator"></i></div>
                <div class="action-title">Legal Tools</div>
                <div class="action-subtitle">Court Fee & Limitation</div>
            </a>
            <a href="notice" class="quick-action-card">
                <div class="action-icon icon-red"><i class="fas fa-bell"></i></div>
                <div class="action-title">Notices</div>
                <div class="action-subtitle">Court & Exams</div>
            </a>
        </div>
    </div>
</section>

<!-- Dynamic Platform Statistics -->
<section style="padding: 1.5rem 0 3.5rem;">
    <div class="container">
        <div class="profile-stats-grid">
            <div class="stat-box" style="text-align: center;">
                <div class="stat-label"><i class="fas fa-users" style="color: var(--brand-red);"></i> Active Advocates</div>
                <div class="stat-value" style="font-size: 2rem; color: var(--brand-red);"><?= number_format($advocateCount) ?>+</div>
            </div>
            <div class="stat-box" style="text-align: center;">
                <div class="stat-label"><i class="fas fa-scale-balanced" style="color: var(--brand-gold-dark);"></i> States & UTs</div>
                <div class="stat-value" style="font-size: 2rem; color: var(--brand-gold-dark);"><?= count($states) ?: 36 ?></div>
            </div>
            <div class="stat-box" style="text-align: center;">
                <div class="stat-label"><i class="fas fa-building-columns" style="color: var(--primary);"></i> Law Colleges & Univs</div>
                <div class="stat-value" style="font-size: 2rem; color: var(--primary);"><?= number_format($collegesCount) ?>+</div>
            </div>
            <div class="stat-box" style="text-align: center;">
                <div class="stat-label"><i class="fas fa-users-rectangle" style="color: var(--brand-red);"></i> Bar Associations</div>
                <div class="stat-value" style="font-size: 2rem; color: var(--brand-red);"><?= number_format($baCount) ?>+</div>
            </div>
        </div>
    </div>
</section>

<!-- Recently Joined Advocates Section (as in advocateindex) -->
<?php if (!empty($recentAdvocates)): ?>
<style>
/* Scoped styles for Recently Joined Advocates to ensure immediate styling regardless of browser cache */
.recent-adv-section {
    padding: 3.5rem 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border-top: 1px solid var(--border-color, #e2e8f0);
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    position: relative;
}
.recent-adv-header-wrap {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 2.25rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.recent-adv-tag-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.12), rgba(16, 185, 129, 0.08));
    color: #15803d;
    padding: 0.4rem 0.95rem;
    border-radius: 999px;
    font-size: 0.775rem;
    font-weight: 800;
    margin-bottom: 0.65rem;
    border: 1px solid rgba(22, 163, 74, 0.25);
    letter-spacing: 0.03em;
    text-transform: uppercase;
}
.recent-adv-heading {
    font-size: 1.95rem;
    font-weight: 800;
    color: var(--primary, #0f172a);
    margin-bottom: 0.35rem;
    letter-spacing: -0.02em;
}
.recent-adv-subheading {
    color: var(--text-muted, #64748b);
    font-size: 0.95rem;
    margin-bottom: 0;
}
.recent-adv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
}
.recent-adv-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 1.5rem 1.35rem;
    display: flex;
    flex-direction: column;
    text-align: left;
    box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}
.recent-adv-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.12);
    border-color: #fde047;
}
.recent-adv-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.1rem;
    font-size: 0.725rem;
}
.recent-adv-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #16a34a;
    font-weight: 700;
    background: #ecfdf5;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    border: 1px solid #bbf7d0;
}
.recent-adv-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #16a34a;
    display: inline-block;
}
.recent-adv-enr-tag {
    color: #64748b;
    font-weight: 600;
    background: #f1f5f9;
    padding: 0.2rem 0.55rem;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}
.recent-adv-identity-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}
.recent-adv-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.recent-adv-avatar-img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: block;
}
.recent-adv-avatar-fallback {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 800;
    font-family: var(--font-heading, sans-serif);
    border: 3px solid #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.recent-adv-check-badge {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #16a34a;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6rem;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.12);
}
.recent-adv-name-link {
    font-family: var(--font-heading, sans-serif);
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--primary, #0f172a);
    text-decoration: none;
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    transition: color 0.2s ease;
}
.recent-adv-name-link:hover {
    color: var(--brand-red, #c00000);
}
.recent-adv-loc-text {
    font-size: 0.775rem;
    color: var(--text-muted, #64748b);
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin-top: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.recent-adv-practices-wrap {
    margin-bottom: 1.15rem;
    min-height: 48px;
}
.recent-adv-practices-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.4rem;
}
.recent-adv-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
}
.recent-adv-chip {
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-size: 0.725rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    white-space: nowrap;
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.recent-adv-chip-more {
    background: #fef9c3;
    color: #854d0e;
    border: 1px solid #fde047;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.5rem;
    border-radius: 999px;
}
.recent-adv-card-footer {
    margin-top: auto;
    padding-top: 0.85rem;
    border-top: 1px dashed #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.recent-adv-time-tag {
    font-size: 0.75rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}
.recent-adv-btn-view {
    padding: 0.4rem 0.95rem;
    font-size: 0.775rem;
    font-weight: 700;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    white-space: nowrap;
    transition: all 0.2s ease;
}
.recent-adv-btn-view:hover i {
    transform: translateX(3px);
}
.recent-adv-btn-view i {
    transition: transform 0.2s ease;
}

/* Dark mode overrides */
[data-theme="dark"] .recent-adv-section {
    background: #0f172a;
    border-color: #1e293b;
}
[data-theme="dark"] .recent-adv-heading {
    color: #f8fafc;
}
[data-theme="dark"] .recent-adv-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
[data-theme="dark"] .recent-adv-card:hover {
    border-color: var(--brand-gold, #ffc000);
    box-shadow: 0 15px 30px rgba(0,0,0,0.5);
}
[data-theme="dark"] .recent-adv-name-link {
    color: #f8fafc;
}
[data-theme="dark"] .recent-adv-name-link:hover {
    color: var(--brand-gold, #ffc000);
}
[data-theme="dark"] .recent-adv-enr-tag {
    background: #0f172a;
    border-color: #334155;
    color: #94a3b8;
}
[data-theme="dark"] .recent-adv-chip {
    background: #0f172a;
    border-color: #334155;
    color: #cbd5e1;
}
[data-theme="dark"] .recent-adv-chip-more {
    background: rgba(254, 240, 138, 0.15);
    border-color: rgba(254, 240, 138, 0.3);
    color: #fef08a;
}
[data-theme="dark"] .recent-adv-card-footer {
    border-top-color: #334155;
}
</style>

<section class="recent-adv-section">
    <div class="container">
        <!-- Section Header -->
        <div class="recent-adv-header-wrap">
            <div>
                <div class="recent-adv-tag-pill">
                    <i class="fas fa-sparkles" style="color: #eab308;"></i> New Members Directory
                </div>
                <h2 class="recent-adv-heading">Recently Joined Advocates</h2>
                <p class="recent-adv-subheading">Welcome our newest verified legal counsel and advocate profiles registered on My Advocate</p>
            </div>
            <div>
                <a href="advocate-search-result" class="btn btn-outline-primary" style="border-radius: 999px; font-weight: 700; padding: 0.55rem 1.35rem; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                    Browse Directory (1.6L+) <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- 4-Card Responsive Grid -->
        <div class="recent-adv-grid">
            <?php foreach ($recentAdvocates as $ra):
                $profileLink = getAdvocateUrl($ra);
                $rawName = trim($ra['name'] ?? 'Advocate');
                $formattedName = mb_convert_case($rawName, MB_CASE_TITLE, "UTF-8");
                $locParts = array_filter([$ra['district_name'] ?? '', $ra['state_name'] ?? '']);
                $locStr = !empty($locParts) ? implode(', ', $locParts) : 'Not available';
                
                $initials = strtoupper(substr($rawName, 0, 1));
                $nameParts = preg_split('/\s+/', $rawName);
                if (count($nameParts) > 1 && !empty($nameParts[0]) && !empty(end($nameParts))) {
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
                }
                
                $joinedAgo = (!empty($ra['created_at']) && function_exists('timeAgo')) ? timeAgo($ra['created_at']) : 'Recently';
                $photoUrl = getAdvocatePhotoUrl($ra['photo'] ?? '');
                $allPractices = parsePracticeAreas($ra['practice_area'] ?? '');
                $primaryPractices = array_slice($allPractices, 0, 2);
                $remainingCount = count($allPractices) - 2;
                $enrYear = !empty($ra['e_year']) ? $ra['e_year'] : (!empty($ra['e_no']) ? $ra['e_no'] : 'Registered');
            ?>
            <div class="recent-adv-card">
                <!-- Top Status Bar inside Card -->
                <div class="recent-adv-card-meta">
                    <span class="recent-adv-status-badge">
                        <span class="recent-adv-status-dot"></span> Active Counsel
                    </span>
                    <span class="recent-adv-enr-tag">
                        Enr: <?= sanitize($enrYear) ?>
                    </span>
                </div>

                <!-- Avatar & Identity Row -->
                <div class="recent-adv-identity-row">
                    <div class="recent-adv-avatar-wrap">
                        <?php if (!empty($photoUrl)): ?>
                            <img src="<?= sanitize($photoUrl) ?>" alt="<?= sanitize($formattedName) ?>" class="recent-adv-avatar-img" loading="lazy">
                        <?php else: ?>
                            <div class="recent-adv-avatar-fallback">
                                <span><?= sanitize($initials) ?></span>
                            </div>
                        <?php endif; ?>
                        <span class="recent-adv-check-badge" title="Registered Professional">
                            <i class="fas fa-check"></i>
                        </span>
                    </div>

                    <div style="min-width: 0; flex: 1;">
                        <a href="<?= $profileLink ?>" class="recent-adv-name-link" title="<?= sanitize($formattedName) ?>">
                            <?= sanitize($formattedName) ?>
                        </a>
                        <div class="recent-adv-loc-text" title="<?= sanitize($locStr) ?>">
                            <i class="fas fa-location-dot" style="color: var(--brand-red, #c00000); font-size: 0.75rem; flex-shrink: 0;"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis;"><?= sanitize($locStr) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Practice Specialty Chips -->
                <div class="recent-adv-practices-wrap">
                    <div class="recent-adv-practices-label">
                        Practice Disciplines
                    </div>
                    <div class="recent-adv-chip-list">
                        <?php foreach ($primaryPractices as $p): ?>
                            <span class="recent-adv-chip" title="<?= sanitize($p) ?>">
                                <?= sanitize($p) ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if ($remainingCount > 0): ?>
                            <span class="recent-adv-chip-more" title="<?= sanitize(implode(', ', array_slice($allPractices, 2))) ?>">
                                +<?= $remainingCount ?> more
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Divider & Footer Action -->
                <div class="recent-adv-card-footer">
                    <div class="recent-adv-time-tag">
                        <i class="far fa-clock" style="color: #cbd5e1;"></i>
                        <span><?= sanitize($joinedAgo) ?></span>
                    </div>

                    <a href="<?= $profileLink ?>" class="btn btn-outline-primary recent-adv-btn-view">
                        View Profile <i class="fas fa-chevron-right" style="font-size: 0.65rem;"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Bare Acts & New Criminal Legislation -->
<section class="section" style="background: #ffffff; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><i class="fas fa-book-bookmark text-primary"></i> Essential Bare Acts & New Criminal Laws</h2>
                <p class="section-subtitle">Read, search sections, and download official bare acts, codes, and 2023 criminal legislations</p>
            </div>
            <a href="acts" class="btn btn-outline-primary">View All Bare Acts <i class="fas fa-arrow-right"></i></a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;">
            <!-- Special Feature: BNS 2023 -->
            <div class="act-card" style="border-left: 4px solid var(--brand-red);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <span class="act-year">Act of 2023</span>
                    <span class="badge-verification badge-verified">New Law</span>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;"><a href="act-details?id=bns">Bharatiya Nyaya Sanhita, 2023 (BNS)</a></h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                    Replaces the Indian Penal Code, 1860 with modernized substantive criminal provisions, community service sanctions, and organized chapters.
                </p>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="act-details?id=bns" class="btn btn-outline-primary btn-sm"><i class="fas fa-book-open"></i> Read Sections</a>
                </div>
            </div>

            <!-- Special Feature: BNSS 2023 -->
            <div class="act-card" style="border-left: 4px solid var(--brand-gold);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <span class="act-year">Act of 2023</span>
                    <span class="badge-verification badge-registered">New Procedure</span>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;"><a href="act-details?id=bnss">Bharatiya Nagarik Suraksha Sanhita (BNSS)</a></h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                    Replaces the Code of Criminal Procedure, 1973 with updated investigation timelines, mandatory videography, digital summons, and bail norms.
                </p>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="act-details?id=bnss" class="btn btn-outline-primary btn-sm"><i class="fas fa-book-open"></i> Read Sections</a>
                </div>
            </div>

            <!-- Special Feature: BSA 2023 -->
            <div class="act-card" style="border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                    <span class="act-year">Act of 2023</span>
                    <span class="badge-verification badge-basic">New Evidence</span>
                </div>
                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;"><a href="act-details?id=bsa">Bharatiya Sakshya Adhiniyam (BSA)</a></h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                    Replaces the Indian Evidence Act, 1872 establishing comprehensive legal frameworks for electronic records and forensic testimony.
                </p>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="act-details?id=bsa" class="btn btn-outline-primary btn-sm"><i class="fas fa-book-open"></i> Read Sections</a>
                </div>
            </div>

            <?php foreach ($featuredActs as $act): ?>
                <div class="act-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                        <span class="act-year"><?= sanitize($act['year'] ?: 'Central Act') ?></span>
                    </div>
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">
                        <a href="act-details?id=<?= $act['id'] ?>"><?= sanitize(cleanActName($act['name'])) ?></a>
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1rem;">
                        Official bare text, legislative statement of objects, and section-by-section breakdown.
                    </p>
                    <a href="act-details?id=<?= $act['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-book-open"></i> View Act</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Advocate Profile Claiming CTA Banner (Light Luxury Theme) -->
<section style="padding: 4rem 0; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 50%, #fef2f2 100%); color: var(--text-main); border-top: 2px solid var(--brand-gold-border); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; align-items: center;">
            <div>
                <div class="badge-verification" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; margin-bottom: 1rem; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-id-badge" style="color: var(--brand-red);"></i> For Practicing Advocates & Legal Counsel
                </div>
                <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 800; line-height: 1.25; margin-bottom: 1rem;">
                    Are you an Advocate listed in our statutory directory?
                </h2>
                <p style="color: #475569; font-size: 1.025rem; line-height: 1.6; margin-bottom: 1.5rem;">
                    Claim your official profile today to update your practice areas, manage client contact privacy settings, showcase your Bar Association, and obtain a verified digital presence.
                </p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="claim-profile" class="btn btn-primary btn-lg"><i class="fas fa-check-circle"></i> Claim My Profile Now</a>
                    <a href="register" class="btn btn-outline-primary btn-lg"><i class="fas fa-user-plus"></i> New Registration</a>
                </div>
            </div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-left: 4px solid var(--brand-red);">
                <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-shield-check" style="color: var(--brand-gold);"></i> Verified Membership Benefits
                </h3>
                <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.9375rem; color: #334155;">
                    <li style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <i class="fas fa-circle-check" style="color: #16a34a; margin-top: 0.25rem;"></i>
                        <span><strong>State Bar Council Recognition:</strong> Verified enrollment number and official bar details.</span>
                    </li>
                    <li style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <i class="fas fa-circle-check" style="color: #16a34a; margin-top: 0.25rem;"></i>
                        <span><strong>Privacy Controls:</strong> Keep your mobile and email public, masked, or private.</span>
                    </li>
                    <li style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <i class="fas fa-circle-check" style="color: #16a34a; margin-top: 0.25rem;"></i>
                        <span><strong>Direct Client Inquiries:</strong> Zero intermediary commission or agency involvement.</span>
                    </li>
                    <li style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <i class="fas fa-circle-check" style="color: #16a34a; margin-top: 0.25rem;"></i>
                        <span><strong>Custom Public URL:</strong> Share your professional profile link with clients and colleagues.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Latest Notifications Feed -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><i class="fas fa-bullhorn text-primary"></i> Legal Updates & Official Examination Notices</h2>
                <p class="section-subtitle">Real-time alerts for Judicial Services, High Court announcements, and BCI notifications</p>
            </div>
            <a href="notice" class="btn btn-outline-primary">View All Notices <i class="fas fa-arrow-right"></i></a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
            <?php if (!empty($latestNotices)): ?>
                <?php foreach ($latestNotices as $n): ?>
                    <div class="stat-box" style="display: flex; flex-direction: column; justify-content: space-between; border-left: 3px solid var(--brand-red);">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span class="badge-verification badge-registered">🟢 Active Notice</span>
                                <?php if (!empty($n['last_date']) && $n['last_date'] !== '0000-00-00'): ?>
                                    <small style="color: var(--text-muted);"><i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($n['last_date'])) ?></small>
                                <?php endif; ?>
                            </div>
                            <h3 style="font-size: 1.05rem; margin-bottom: 0.5rem; line-height: 1.35;"><?= sanitize($n['name']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.8125rem; line-height: 1.5;"><?= sanitize(substr($n['details'] ?? 'Official notification details, examination dates, and guidelines.', 0, 110)) ?>...</p>
                        </div>
                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                            <?php if (!empty($n['url_1'])): ?>
                                <a href="<?= sanitize($n['url_1']) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm" style="flex: 1;"><i class="fas fa-arrow-up-right-from-square"></i> Official PDF</a>
                            <?php endif; ?>
                            <a href="notice" class="btn btn-outline btn-sm" style="flex: 1;">All Notices <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="stat-box"><p>No active notices available currently.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- State-Wise Advocate Directory Directory Grid -->
<section class="section" style="background: var(--bg-alt); border-top: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title"><i class="fas fa-map-location-dot text-primary"></i> Browse Advocates by State & Union Territory</h2>
                <p class="section-subtitle">Find verified advocates practicing in High Courts and District Courts across India</p>
            </div>
            <a href="advocate-search-result" class="btn btn-outline-primary">Full Directory <i class="fas fa-arrow-right"></i></a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 0.75rem;">
            <?php foreach ($states as $code => $name): ?>
                <a href="advocate-search-result?state=<?= sanitize($code) ?>" class="stat-box" style="padding: 0.85rem 1rem; display: flex; align-items: center; justify-content: space-between; text-decoration: none; transition: var(--transition); border-radius: var(--radius-sm);">
                    <span style="font-weight: 600; font-size: 0.875rem; color: var(--text-main);"><?= sanitize($name) ?></span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem; color: var(--text-muted);"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
