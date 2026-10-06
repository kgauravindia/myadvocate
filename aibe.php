<?php
// aibe.php - All India Bar Examination (AIBE) Resource & Prep Hub
require_once __DIR__ . '/config/app.php';

$pageTitle = "AIBE XXII & XXI Hub - Exam Pattern, Syllabus, Past Papers & Preparation";
$pageDescription = "Complete preparation hub for the All India Bar Examination (AIBE). Access syllabus weightage, previous question papers, eligibility criteria, and exam guidelines.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
        <a href="./">Home</a> &bull; <span>Legal Careers</span> &bull; <span>AIBE Hub</span>
    </nav>

    <!-- Hub Header Banner (Light Theme) -->
    <div class="stat-box" style="background: linear-gradient(135deg, #fffbeb 0%, #ffffff 50%, #fef2f2 100%); color: var(--text-main); padding: 2.5rem; border-radius: var(--radius-lg); margin-bottom: 2.5rem; border: 1px solid var(--brand-gold-border); border-left: 5px solid var(--brand-red); box-shadow: var(--shadow-sm);">
        <div style="max-width: 800px;">
            <span class="badge-verification" style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; margin-bottom: 0.75rem; box-shadow: var(--shadow-sm);">
                <i class="fas fa-graduation-cap" style="color: var(--brand-red);"></i> Bar Council of India Examination Portal
            </span>
            <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--primary); margin-bottom: 0.75rem;">
                All India Bar Examination (AIBE XXII / XXI)
            </h1>
            <p style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                Everything law graduates and newly enrolled advocates need to prepare for and clear the mandatory Certificate of Practice (COP) examination.
            </p>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="#syllabus" class="btn btn-primary btn-sm"><i class="fas fa-list-check"></i> Syllabus & Weightage</a>
                <a href="#pattern" class="btn btn-outline-primary btn-sm"><i class="fas fa-clock"></i> Exam Pattern</a>
                <a href="#faqs" class="btn btn-outline btn-sm"><i class="fas fa-circle-question"></i> AIBE FAQs</a>
            </div>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="profile-stats-grid" style="margin-bottom: 2.5rem;">
        <div class="stat-box">
            <div class="stat-label">Total Questions</div>
            <div class="stat-value">100 MCQs</div>
            <small style="color: var(--text-muted);">Objective Type Assessment</small>
        </div>
        <div class="stat-box">
            <div class="stat-label">Exam Duration</div>
            <div class="stat-value">3 Hours 30 Mins</div>
            <small style="color: var(--text-muted);">210 Minutes Allocated</small>
        </div>
        <div class="stat-box">
            <div class="stat-label">Negative Marking</div>
            <div class="stat-value" style="color: var(--brand-red);">No Negative Marking</div>
            <small style="color: var(--text-muted);">+1 Mark per correct answer</small>
        </div>
        <div class="stat-box">
            <div class="stat-label">Qualifying Cutoff</div>
            <div class="stat-value">45% (Gen/OBC) / 40% (SC/ST)</div>
            <small style="color: var(--text-muted);">Pass / Fail (No ranks)</small>
        </div>
    </div>

    <!-- Syllabus Breakdown Grid -->
    <div id="syllabus" class="stat-box" style="margin-bottom: 2.5rem;">
        <div class="section-header" style="margin-bottom: 1.5rem;">
            <div>
                <h2 class="section-title" style="font-size: 1.6rem;"><i class="fas fa-book-open-reader text-primary"></i> AIBE Official Subject-wise Marks Weightage</h2>
                <p class="section-subtitle">Based on official Bar Council of India examination guidelines</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Constitutional Law</strong><br><small style="color:var(--text-muted);">Fundamental rights, writs & judiciary</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">10 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>IPC / Bharatiya Nyaya Sanhita (BNS)</strong><br><small style="color:var(--text-muted);">Substantive criminal law</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">8 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>CrPC / BNSS 2023</strong><br><small style="color:var(--text-muted);">Criminal procedure, trial & bail</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">10 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Code of Civil Procedure (CPC)</strong><br><small style="color:var(--text-muted);">Pleadings, execution & appeals</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">10 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Evidence Act / BSA 2023</strong><br><small style="color:var(--text-muted);">Burden of proof & electronic evidence</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">8 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Family Law</strong><br><small style="color:var(--text-muted);">Hindu & Muslim personal laws</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">8 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Law of Contract, Specific Relief & Torts</strong><br><small style="color:var(--text-muted);">Commercial and civil wrongs</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">8 Qs</span>
            </div>
            <div style="background: var(--bg-alt); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <div><strong>Professional Ethics & BCI Rules</strong><br><small style="color:var(--text-muted);">Advocates Act & conduct norms</small></div>
                <span class="badge-verification badge-verified" style="font-size:0.9rem; font-weight:700;">4 Qs</span>
            </div>
        </div>
    </div>

    <!-- Bare Acts for AIBE CTA -->
    <div style="background: var(--brand-gold-light); border: 1px solid var(--brand-gold-border); border-radius: var(--radius-lg); padding: 2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.25rem;">
        <div>
            <h3 style="font-size: 1.3rem; color: var(--primary); margin-bottom: 0.35rem;">Need Bare Acts without Notes for Exam Day?</h3>
            <p style="color: var(--brand-gold-dark); font-size: 0.9375rem;">Access and review all candidate-permitted statutory texts directly on My Advocate.</p>
        </div>
        <a href="acts" class="btn btn-primary"><i class="fas fa-book"></i> Access Bare Acts Library</a>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
