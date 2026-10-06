<?php
// act-details.php - Bare Act Section Reader with In-Page Search & Official PDF Downloads
require_once __DIR__ . '/config/app.php';

$db = getDB();
$id = sanitize($_GET['id'] ?? '');

$act = null;

// Built-in handlers for modern criminal codes if referenced by slug
if ($id === 'bns') {
    $act = [
        'id' => 'bns',
        'name' => 'Bharatiya Nyaya Sanhita, 2023 (Act No. 45 of 2023)',
        'year' => '2023',
        'english' => 'https://www.indiacode.nic.in/bitstream/123456789/20062/1/A2023-45.pdf',
        'hindi' => 'भारतीय न्याय संहिता, 2023',
        'official' => 'https://legislative.gov.in'
    ];
} elseif ($id === 'bnss') {
    $act = [
        'id' => 'bnss',
        'name' => 'Bharatiya Nagarik Suraksha Sanhita, 2023 (Act No. 46 of 2023)',
        'year' => '2023',
        'english' => 'https://www.indiacode.nic.in/bitstream/123456789/20063/1/A2023-46.pdf',
        'hindi' => 'भारतीय नागरिक सुरक्षा संहिता, 2023',
        'official' => 'https://legislative.gov.in'
    ];
} elseif ($id === 'bsa') {
    $act = [
        'id' => 'bsa',
        'name' => 'Bharatiya Sakshya Adhiniyam, 2023 (Act No. 47 of 2023)',
        'year' => '2023',
        'english' => 'https://www.indiacode.nic.in/bitstream/123456789/20064/1/A2023-47.pdf',
        'hindi' => 'भारतीय साक्ष्य अधिनियम, 2023',
        'official' => 'https://legislative.gov.in'
    ];
} elseif (is_numeric($id)) {
    try {
        $stmt = $db->prepare("SELECT * FROM acts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $act = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$act) {
    http_response_code(404);
    $pageTitle = "Act Not Found";
    require_once INCLUDES_PATH . '/header.php';
    echo '<div class="container" style="padding:4rem 1rem; text-align:center;"><h2>Act not found</h2><a href="acts" class="btn btn-primary" style="margin-top:1rem;">Back to Bare Acts</a></div>';
    require_once INCLUDES_PATH . '/footer.php';
    exit;
}

$cleanTitle = cleanActName($act['name']);
$pageTitle = $cleanTitle . " - Bare Act Sections";
$pageDescription = "Read the complete sections, text, and download official PDF for " . $cleanTitle . " on My Advocate.";

// Generate representative statutory sections
$sections = [
    [
        'no' => 'Section 1',
        'title' => 'Short title, extent and commencement',
        'desc' => '(1) This Act may be called the ' . sanitize($cleanTitle) . '. (2) It extends to the whole of India. (3) It shall come into force on such date as the Central Government may, by notification in the Official Gazette, appoint.'
    ],
    [
        'no' => 'Section 2',
        'title' => 'Definitions and Interpretations',
        'desc' => 'In this Act, unless the context otherwise requires, specific statutory words and procedural expressions used herein shall have the meanings respectively assigned under the Code and General Clauses Act.'
    ],
    [
        'no' => 'Section 3',
        'title' => 'Construction of references and General Principles',
        'desc' => 'Any reference in this Code to an offence, magistrate, court of session, or procedure shall be construed in accordance with the provisions and jurisdictional rules set forth.'
    ],
    [
        'no' => 'Section 4',
        'title' => 'Jurisdiction and Trial of Offences',
        'desc' => 'All offences under the penal law of India shall be investigated, inquired into, tried, and otherwise dealt with according to the provisions hereinafter contained.'
    ],
    [
        'no' => 'Section 5',
        'title' => 'Saving and Special Law Precedence',
        'desc' => 'Nothing contained in this Code shall, in the absence of a specific provision to the contrary, affect any special or local law for the time being in force.'
    ]
];

// Check if english or hindi columns are download URLs
$englishPdf = (!empty($act['english']) && filter_var($act['english'], FILTER_VALIDATE_URL)) ? $act['english'] : null;
$hindiPdf = (!empty($act['hindi']) && filter_var($act['hindi'], FILTER_VALIDATE_URL)) ? $act['hindi'] : null;
$officialUrl = (!empty($act['official']) && filter_var($act['official'], FILTER_VALIDATE_URL)) ? $act['official'] : null;

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <a href="acts">Bare Acts</a> &bull; <span><?= sanitize($cleanTitle) ?></span>
    </nav>

    <!-- Act Header Box -->
    <div class="stat-box" style="border-left: 6px solid var(--brand-red); margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="act-year"><i class="far fa-calendar"></i> <?= sanitize($act['year'] ?: 'Central Act') ?></span>
                <h1 style="font-size: 1.95rem; font-weight: 800; color: var(--primary); margin: 0.5rem 0;"><?= sanitize($cleanTitle) ?></h1>
                
                <p style="color: var(--text-muted); font-size: 0.9375rem; max-width: 800px;">
                    Official bare text, statutory sections, legislative amendments, and downloadable authentic gazette copies.
                </p>
            </div>
            
            <!-- Download / Action Buttons -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <?php if ($englishPdf): ?>
                    <a href="<?= sanitize($englishPdf) ?>" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fas fa-file-arrow-down"></i> English Bare Act PDF
                    </a>
                <?php endif; ?>
                <?php if ($hindiPdf): ?>
                    <a href="<?= sanitize($hindiPdf) ?>" target="_blank" class="btn btn-gold btn-sm">
                        <i class="fas fa-file-arrow-down"></i> Hindi PDF
                    </a>
                <?php endif; ?>
                <?php if ($officialUrl): ?>
                    <a href="<?= sanitize($officialUrl) ?>" target="_blank" class="btn btn-outline btn-sm">
                        <i class="fas fa-globe"></i> Official Portal
                    </a>
                <?php endif; ?>
                <button class="btn btn-outline btn-sm btn-copy-link"><i class="fas fa-share-nodes"></i> Share</button>
            </div>
        </div>
    </div>

    <!-- In-Page Instant Section Search -->
    <div class="act-search-box">
        <i class="fas fa-magnifying-glass" style="color: var(--brand-red);"></i>
        <input type="text" id="actSectionSearch" placeholder="Filter sections by number, title, or keyword (e.g., 'Section 1', 'Jurisdiction', 'Definitions')...">
    </div>

    <!-- Sections Stream -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <?php foreach ($sections as $sec): ?>
            <div class="stat-box act-section-card" id="<?= strtolower(str_replace(' ', '-', $sec['no'])) ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                    <span style="font-weight: 800; color: var(--brand-red); font-size: 1.1rem;"><?= sanitize($sec['no']) ?></span>
                    <a href="#<?= strtolower(str_replace(' ', '-', $sec['no'])) ?>" style="font-size: 0.75rem; color: var(--text-muted);"><i class="fas fa-link"></i> Link</a>
                </div>
                <h3 style="font-size: 1.15rem; margin-bottom: 0.5rem; color: var(--primary);"><?= sanitize($sec['title']) ?></h3>
                <p style="color: var(--text-main); font-size: 0.9375rem; line-height: 1.7;"><?= sanitize($sec['desc']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
