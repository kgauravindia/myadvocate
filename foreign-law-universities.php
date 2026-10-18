<?php
// foreign-law-universities.php - Official List of Foreign Universities Recognized by the Bar Council of India
require_once __DIR__ . '/config/app.php';

$pageTitle = "Foreign Universities Whose Degrees in Law are Recognized by the Bar Council of India (BCI)";
$pageDescription = "Official list of Foreign Universities whose Degrees in Law are recognized by the Bar Council of India (BCI) under Section 24 of the Advocates Act, 1961. Eligibility rules, UK, Australia, Canada, and official BCI notification.";

$officialBciPdfUrl = "https://bci-files.s3.amazonaws.com/docs/BCA0001X81XET1H2Z.pdf?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAV54H4AASSXJQYS3O%2F20261018%2Fap-south-1%2Fs3%2Faws4_request&X-Amz-Date=20261018T084032Z&X-Amz-Expires=3600&X-Amz-SignedHeaders=host&X-Amz-Signature=2a517acf36fce8fec1aca92dff317dccccfe7db2761dabd2cf02ae148cba3429";
$directPdfUrl = "https://bci-files.s3.amazonaws.com/docs/BCA0001X81XET1H2Z.pdf";

// Structured dataset of Foreign Universities recognized by the Bar Council of India
$foreignUniversitiesData = [
    'United Kingdom' => [
        'flag' => '🇬🇧',
        'code' => 'GB',
        'notes' => 'Recognition is granted for full-time regular LL.B. programs (under 10+2+3+3 or 10+2+5 patterns) followed by LPC/BPTC or approved Bar Training where mandated by BCI rules. External/distance degrees are not recognized.',
        'universities' => [
            ['name' => 'University of Oxford', 'location' => 'Oxford, England', 'degrees' => 'B.A. in Jurisprudence (Honours) / Senior Status Law'],
            ['name' => 'University of Cambridge', 'location' => 'Cambridge, England', 'degrees' => 'Law Tripos / B.A. in Law (Honours)'],
            ['name' => 'University of London', 'location' => 'London, England', 'degrees' => 'LL.B. (Regular Collegiate - UCL, KCL, LSE, Queen Mary, SOAS, City)'],
            ['name' => 'University College London (UCL)', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'King\'s College London (KCL)', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'London School of Economics and Political Science (LSE)', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Queen Mary University of London', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'School of Oriental and African Studies (SOAS)', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'City, University of London (The City Law School)', 'location' => 'London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Birmingham', 'location' => 'Birmingham, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Bristol', 'location' => 'Bristol, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Manchester', 'location' => 'Manchester, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Liverpool', 'location' => 'Liverpool, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Warwick', 'location' => 'Coventry, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Sheffield', 'location' => 'Sheffield, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Nottingham', 'location' => 'Nottingham, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Southampton', 'location' => 'Southampton, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Leeds', 'location' => 'Leeds, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Leicester', 'location' => 'Leicester, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Hull', 'location' => 'Hull, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Brunel University London', 'location' => 'Uxbridge, London, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Northumbria University', 'location' => 'Newcastle upon Tyne, England', 'degrees' => 'LL.B. (Honours) Regular / M.Law Program'],
            ['name' => 'University of Buckingham', 'location' => 'Buckingham, England', 'degrees' => 'LL.B. (Honours) Regular Intensive Course'],
            ['name' => 'University of Hertfordshire', 'location' => 'Hatfield, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Kingston University London', 'location' => 'Kingston upon Thames, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Swansea University', 'location' => 'Swansea, Wales', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Bangor University', 'location' => 'Bangor, Wales', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Cardiff University', 'location' => 'Cardiff, Wales', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Aberdeen', 'location' => 'Aberdeen, Scotland', 'degrees' => 'LL.B. Regular Course'],
            ['name' => 'University of Dundee', 'location' => 'Dundee, Scotland', 'degrees' => 'LL.B. (English / Dual Qualifying Law)'],
            ['name' => 'University of Edinburgh', 'location' => 'Edinburgh, Scotland', 'degrees' => 'LL.B. Regular Course'],
            ['name' => 'University of Glasgow', 'location' => 'Glasgow, Scotland', 'degrees' => 'LL.B. Regular Course'],
            ['name' => 'University of Strathclyde', 'location' => 'Glasgow, Scotland', 'degrees' => 'LL.B. Regular Course'],
            ['name' => 'Queen\'s University Belfast', 'location' => 'Belfast, Northern Ireland', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of East Anglia (UEA)', 'location' => 'Norwich, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Essex', 'location' => 'Colchester, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'University of Sussex', 'location' => 'Brighton, England', 'degrees' => 'LL.B. (Honours) Regular Course'],
            ['name' => 'Oxford Brookes University', 'location' => 'Oxford, England', 'degrees' => 'LL.B. (Honours) Regular Course']
        ]
    ],
    'Australia' => [
        'flag' => '🇦🇺',
        'code' => 'AU',
        'notes' => 'Recognized for regular campus-based LL.B. or J.D. degrees from recognized Australian faculties under the 10+2+3+3 or 10+2+5 patterns.',
        'universities' => [
            ['name' => 'University of Melbourne', 'location' => 'Melbourne, Victoria', 'degrees' => 'Melbourne Law School - Juris Doctor (J.D.) / LL.B.'],
            ['name' => 'University of Sydney', 'location' => 'Sydney, New South Wales', 'degrees' => 'Sydney Law School - LL.B. / Juris Doctor (J.D.)'],
            ['name' => 'Australian National University (ANU)', 'location' => 'Canberra, ACT', 'degrees' => 'ANU College of Law - Bachelor of Laws (LL.B. Hons) / J.D.'],
            ['name' => 'University of New South Wales (UNSW)', 'location' => 'Sydney, New South Wales', 'degrees' => 'UNSW Law - Bachelor of Laws (LL.B.) / J.D.'],
            ['name' => 'Monash University', 'location' => 'Melbourne, Victoria', 'degrees' => 'Monash Law - Bachelor of Laws (LL.B.) / J.D.'],
            ['name' => 'University of Queensland (UQ)', 'location' => 'Brisbane, Queensland', 'degrees' => 'TC Beirne School of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'University of Adelaide', 'location' => 'Adelaide, South Australia', 'degrees' => 'Adelaide Law School - Bachelor of Laws (LL.B.)'],
            ['name' => 'University of Western Australia (UWA)', 'location' => 'Perth, Western Australia', 'degrees' => 'UWA Law School - Juris Doctor (J.D.)'],
            ['name' => 'University of Technology Sydney (UTS)', 'location' => 'Sydney, New South Wales', 'degrees' => 'UTS Faculty of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'Queensland University of Technology (QUT)', 'location' => 'Brisbane, Queensland', 'degrees' => 'QUT School of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'Griffith University', 'location' => 'Brisbane & Gold Coast, Queensland', 'degrees' => 'Griffith Law School (Nathan & Gold Coast Campuses) - LL.B.'],
            ['name' => 'Bond University', 'location' => 'Gold Coast, Queensland', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B.) / Juris Doctor'],
            ['name' => 'University of Tasmania', 'location' => 'Hobart, Tasmania', 'degrees' => 'Tasmania Law School - Bachelor of Laws (LL.B.)'],
            ['name' => 'Flinders University', 'location' => 'Adelaide, South Australia', 'degrees' => 'Flinders Law - Bachelor of Laws and Legal Practice'],
            ['name' => 'Southern Cross University', 'location' => 'Lismore & Gold Coast', 'degrees' => 'School of Law and Justice - Bachelor of Laws (LL.B.)'],
            ['name' => 'Macquarie University', 'location' => 'Sydney, New South Wales', 'degrees' => 'Macquarie Law School - Bachelor of Laws (LL.B.)'],
            ['name' => 'Deakin University', 'location' => 'Melbourne & Geelong, Victoria', 'degrees' => 'Deakin Law School - Bachelor of Laws (LL.B.)'],
            ['name' => 'Murdoch University', 'location' => 'Perth, Western Australia', 'degrees' => 'Murdoch School of Law - Bachelor of Laws (LL.B.)']
        ]
    ],
    'Canada' => [
        'flag' => '🇨🇦',
        'code' => 'CA',
        'notes' => 'Recognized common law degrees (LL.B. / J.D.) following prerequisite university graduation, subject to BCI verification and clearance of qualifying examination.',
        'universities' => [
            ['name' => 'University of Alberta', 'location' => 'Edmonton, Alberta', 'degrees' => 'Faculty of Law - Juris Doctor (J.D.) / LL.B.'],
            ['name' => 'University of Saskatchewan', 'location' => 'Saskatoon, Saskatchewan', 'degrees' => 'College of Law - Juris Doctor (J.D.) / LL.B.'],
            ['name' => 'Dalhousie University', 'location' => 'Halifax, Nova Scotia', 'degrees' => 'Schulich School of Law - Juris Doctor (J.D.)'],
            ['name' => 'McGill University', 'location' => 'Montreal, Quebec', 'degrees' => 'Faculty of Law - B.C.L. / J.D. (Common Law stream)'],
            ['name' => 'University of Toronto', 'location' => 'Toronto, Ontario', 'degrees' => 'Faculty of Law - Juris Doctor (J.D.)']
        ]
    ],
    'New Zealand' => [
        'flag' => '🇳🇿',
        'code' => 'NZ',
        'notes' => 'Recognized for regular, campus-based Bachelor of Laws (LL.B.) programs accredited by the New Zealand Council of Legal Education.',
        'universities' => [
            ['name' => 'University of Auckland', 'location' => 'Auckland, New Zealand', 'degrees' => 'Auckland Law School - Bachelor of Laws (LL.B.)'],
            ['name' => 'Victoria University of Wellington', 'location' => 'Wellington, New Zealand', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'University of Canterbury', 'location' => 'Christchurch, New Zealand', 'degrees' => 'School of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'University of Otago', 'location' => 'Dunedin, New Zealand', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'University of Waikato', 'location' => 'Hamilton, New Zealand', 'degrees' => 'Te Piringa Faculty of Law - Bachelor of Laws (LL.B.)']
        ]
    ],
    'Singapore' => [
        'flag' => '🇸🇬',
        'code' => 'SG',
        'notes' => 'Regular undergraduate LL.B. / J.D. degree courses accredited by the Legal Education authorities in Singapore.',
        'universities' => [
            ['name' => 'National University of Singapore (NUS)', 'location' => 'Singapore', 'degrees' => 'NUS Faculty of Law - Bachelor of Laws (LL.B.)'],
            ['name' => 'Singapore Management University (SMU)', 'location' => 'Singapore', 'degrees' => 'Yong Pung How School of Law - Bachelor of Laws (LL.B.) / J.D.']
        ]
    ],
    'South Asia (Neighbouring Nations)' => [
        'flag' => '🌏',
        'code' => 'SA',
        'notes' => 'Recognized universities from neighboring South Asian jurisdictions under historical and bilateral reciprocity provisions.',
        'universities' => [
            ['name' => 'University of Dhaka', 'location' => 'Dhaka, Bangladesh', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B. Honours)'],
            ['name' => 'University of Chittagong', 'location' => 'Chittagong, Bangladesh', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B. Honours)'],
            ['name' => 'University of Rajshahi', 'location' => 'Rajshahi, Bangladesh', 'degrees' => 'Faculty of Law - Bachelor of Laws (LL.B. Honours)'],
            ['name' => 'Tribhuvan University', 'location' => 'Kathmandu, Nepal', 'degrees' => 'Faculty of Law / Nepal Law Campus - LL.B. Program'],
            ['name' => 'Rangoon University (University of Yangon)', 'location' => 'Yangon, Myanmar', 'degrees' => 'Department of Law - Bachelor of Laws (LL.B.)']
        ]
    ],
    'Other Recognized Foreign Universities' => [
        'flag' => '🌐',
        'code' => 'OTHER',
        'notes' => 'Selected international institutions recognized through special bilateral BCI resolutions and educational equivalency notifications.',
        'universities' => [
            ['name' => 'Handong International Law School', 'location' => 'Pohang, South Korea', 'degrees' => 'Handong Global University - J.D. Program (Common Law curriculum)'],
            ['name' => 'Makerere University', 'location' => 'Kampala, Uganda', 'degrees' => 'School of Law - Bachelor of Laws (LL.B. Regular)']
        ]
    ]
];

// Calculate totals
$totalCountries = count($foreignUniversitiesData);
$totalForeignUnivs = 0;
foreach ($foreignUniversitiesData as $c => $data) {
    $totalForeignUnivs += count($data['universities']);
}

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 1.5rem; padding-bottom: 5rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="law-college-search" style="color: var(--primary); text-decoration: none;">Law Colleges &amp; Universities</a> &bull; 
        <span>Foreign Law Universities Recognized by BCI</span>
    </nav>

    <!-- Header Hero Card -->
    <div class="stat-box" style="padding: 2.5rem 2rem; margin-bottom: 2rem; border-top: 4px solid var(--brand-red); box-shadow: var(--shadow-md); background: linear-gradient(to right, #ffffff, #fffbfb);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.5rem;">
            <div style="max-width: 860px;">
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <span class="badge-verification" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.78rem; padding: 0.25rem 0.65rem;">
                        <i class="fas fa-landmark me-1"></i> Bar Council of India (BCI) Official Gazette
                    </span>
                    <span class="badge-verification" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.78rem; padding: 0.25rem 0.65rem;">
                        <i class="fas fa-scale-balanced me-1"></i> Advocates Act, 1961 - Section 24(1)(c)
                    </span>
                    <span class="badge-verification badge-verified" style="font-size: 0.78rem; padding: 0.25rem 0.65rem;">
                        <i class="fas fa-globe me-1"></i> <?= $totalForeignUnivs ?>+ Recognized Institutions Worldwide
                    </span>
                </div>

                <h1 style="font-size: 2.15rem; font-weight: 800; color: var(--primary); margin: 0 0 0.75rem; line-height: 1.25;">
                    Foreign Universities Whose Degrees in Law are Recognized by the Bar Council of India
                </h1>

                <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.65; margin-bottom: 1rem;">
                    Under <strong>Section 24(1)(c)(iii) &amp; (iv)</strong> and <strong>Section 7(1)(i)</strong> of the Advocates Act, 1961, the <strong>Bar Council of India</strong> recognizes law degrees obtained from select foreign universities for the purpose of enrollment as an Advocate in India, subject to prescribed academic curriculum standards, regular campus attendance, and qualifying examinations.
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: #475569;">
                    <span><i class="fas fa-check-circle text-success me-1"></i> Full-Time Classroom Degrees Only</span>
                    <span><i class="fas fa-ban text-danger me-1"></i> Distance / Online Degrees Strictly Disallowed</span>
                    <span><i class="fas fa-certificate text-primary me-1"></i> BCI Qualifying Exam Mandatory</span>
                </div>
            </div>

            <!-- Download Official Gazette Notice Button -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; min-width: 220px;">
                <a href="<?= sanitize($officialBciPdfUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-md" style="justify-content: center; font-weight: 700; box-shadow: var(--shadow-sm);">
                    <i class="fas fa-file-pdf me-2" style="font-size: 1.1rem;"></i> Official BCI PDF
                </a>
                <a href="#statutory-conditions" class="btn btn-outline btn-sm" style="justify-content: center;">
                    <i class="fas fa-circle-info me-1"></i> Eligibility Rules
                </a>
                <a href="law-college-search" class="btn btn-outline btn-sm" style="justify-content: center;">
                    <i class="fas fa-building-columns me-1"></i> Indian Law Colleges
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Navigation & Live Filter -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span style="font-weight: 700; font-size: 0.85rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px;">Filter by Country:</span>
            <button type="button" class="btn btn-sm btn-country-filter active" data-country="ALL" onclick="filterCountry('ALL', this)" style="border-radius: 9999px; padding: 0.2rem 0.75rem; font-size: 0.78rem; font-weight: 700;">All Countries</button>
            <?php foreach ($foreignUniversitiesData as $cName => $cDetails): ?>
                <button type="button" class="btn btn-outline btn-sm btn-country-filter" data-country="<?= sanitize($cName) ?>" onclick="filterCountry('<?= addslashes($cName) ?>', this)" style="border-radius: 9999px; padding: 0.2rem 0.75rem; font-size: 0.78rem;">
                    <?= $cDetails['flag'] ?> <?= sanitize($cName) ?> (<?= count($cDetails['universities']) ?>)
                </button>
            <?php endforeach; ?>
        </div>

        <div style="flex: 1; max-width: 320px; min-width: 220px;">
            <input type="text" id="foreignUnivSearch" class="filter-input" placeholder="Search foreign university name..." onkeyup="searchForeignUniv(this.value)" style="min-height: 38px; font-size: 0.85rem;">
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 2rem; align-items: flex-start;" class="foreign-page-grid">
        <style>
            @media(max-width: 991px) {
                .foreign-page-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            .country-group-box {
                background: #ffffff;
                border: 1px solid var(--border-color);
                border-radius: var(--radius-lg);
                padding: 1.75rem;
                margin-bottom: 2rem;
                box-shadow: var(--shadow-sm);
            }
            .univ-item-row {
                padding: 1rem 1.15rem;
                background: var(--bg-alt);
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                margin-bottom: 0.75rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.75rem;
                transition: transform 0.2s, border-color 0.2s;
            }
            .univ-item-row:hover {
                transform: translateX(4px);
                border-color: var(--brand-gold);
                background: #ffffff;
            }
            .btn-country-filter.active {
                background: var(--primary) !important;
                color: #ffffff !important;
                border-color: var(--primary) !important;
            }
        </style>

        <!-- Left Column: Country-by-Country List -->
        <div>
            <?php foreach ($foreignUniversitiesData as $countryName => $cData): ?>
                <div class="country-group-box country-section" data-country-name="<?= sanitize($countryName) ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 0.85rem;">
                        <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--primary); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.5rem;"><?= $cData['flag'] ?></span>
                            <span><?= sanitize($countryName) ?></span>
                        </h2>
                        <span style="font-size: 0.78rem; font-weight: 700; color: #1e40af; background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.25rem 0.65rem; border-radius: var(--radius-full);">
                            <?= count($cData['universities']) ?> Recognized Universities
                        </span>
                    </div>

                    <?php if (!empty($cData['notes'])): ?>
                        <div style="background: #fffbeb; border-left: 4px solid var(--brand-gold); padding: 0.75rem 1rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; font-size: 0.825rem; color: #92400e; margin-bottom: 1.25rem; line-height: 1.5;">
                            <strong><i class="fas fa-circle-info me-1"></i> BCI Recognition Conditions for <?= sanitize($countryName) ?>:</strong> <?= sanitize($cData['notes']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="univ-list-container">
                        <?php foreach ($cData['universities'] as $u): ?>
                            <div class="univ-item-row" data-univ-name="<?= strtolower(sanitize($u['name'])) ?>" data-univ-location="<?= strtolower(sanitize($u['location'])) ?>">
                                <div>
                                    <div style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin-bottom: 0.2rem;">
                                        <?= sanitize($u['name']) ?>
                                    </div>
                                    <div style="font-size: 0.8125rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                        <span><i class="fas fa-location-dot" style="color: var(--brand-red); margin-right: 4px;"></i> <?= sanitize($u['location']) ?></span>
                                        <span>&bull;</span>
                                        <span style="color: #047857; font-weight: 600;"><i class="fas fa-graduation-cap me-1"></i> <?= sanitize($u['degrees']) ?></span>
                                    </div>
                                </div>

                                <div>
                                    <span class="badge-verification badge-verified" style="font-size: 0.75rem; padding: 0.25rem 0.55rem;">
                                        <i class="fas fa-check-circle me-1"></i> BCI Approved
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Statutory Recognition Conditions Card -->
            <div id="statutory-conditions" class="stat-box" style="padding: 2rem; margin-top: 1rem; border-left: 4px solid #2563eb;">
                <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-gavel" style="color: #2563eb;"></i> Mandatory Statutory Conditions for Enrollment in India
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1.25rem; font-size: 0.9rem; line-height: 1.65; color: var(--text-main);">
                    <div>
                        <strong style="color: var(--primary); display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            1. Regular Campus Education Only (No Distance / Online Mode)
                        </strong>
                        <p style="margin: 0; color: var(--text-muted);">
                            The Bar Council of India does <strong>NOT</strong> recognize any law degree obtained through correspondence, external, open university, or distance learning modes. The candidate must have pursued a full-time, regular classroom course at the recognized foreign university campus.
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--primary); display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            2. Educational Structure (10+2+3+3 or 10+2+5 Pattern)
                        </strong>
                        <p style="margin: 0; color: var(--text-muted);">
                            Under the Bar Council of India Legal Education Rules:
                            <br>&bull; A candidate holding a <strong>3-year LL.B.</strong> from a foreign university must have first obtained a regular 3-year Bachelor’s degree in any subject following a 10+2 pattern (totaling 10+2+3+3).
                            <br>&bull; Alternatively, the candidate must have completed a <strong>5-year integrated law degree</strong> following a 10+2 pattern (10+2+5).
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--primary); display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            3. Qualifying Examination for Foreign Law Graduates (BCI-QEFLG)
                        </strong>
                        <p style="margin: 0; color: var(--text-muted);">
                            Under the Bar Council of India Training Rules, all Indian nationals and eligible foreign nationals holding foreign law degrees must appear for and clear the <strong>Bar Council of India Qualifying Examination for Foreign Law Graduates</strong>. The examination tests proficiency in key Indian procedural and substantive laws, including:
                            <br>&bull; The Constitution of India
                            <br>&bull; Code of Civil Procedure (CPC)
                            <br>&bull; Code of Criminal Procedure (CrPC / BNSS)
                            <br>&bull; Indian Penal Code (IPC / BNS)
                            <br>&bull; Indian Evidence Act (BSA)
                            <br>&bull; Limitation Act &amp; Specific Relief Act
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--primary); display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            4. State Bar Council Provisional Enrollment &amp; All India Bar Examination (AIBE)
                        </strong>
                        <p style="margin: 0; color: var(--text-muted);">
                            Upon passing the BCI Qualifying Examination for Foreign Law Graduates, the candidate is granted an equivalency certificate and becomes eligible for provisional enrollment with the appropriate State Bar Council (e.g. Bar Council of Delhi, Bar Council of Maharashtra &amp; Goa, Bar Council of Uttar Pradesh, etc.). Subsequently, the candidate must appear for the <strong>All India Bar Examination (AIBE)</strong> to receive their permanent Certificate of Practice (COP).
                        </p>
                    </div>

                    <div>
                        <strong style="color: var(--primary); display: block; font-size: 0.95rem; margin-bottom: 0.25rem;">
                            5. Reciprocity Provisions (Section 47 of Advocates Act, 1961)
                        </strong>
                        <p style="margin: 0; color: var(--text-muted);">
                            Foreign nationals are eligible for enrollment only if their country provides reciprocal practice rights to Indian nationals holding law degrees from recognized Indian universities.
                        </p>
                    </div>
                </div>
            </div>

            <!-- OFFICIAL BCI NOTIFICATION & AWS S3 DOCUMENT LINKS SECTION (DOWN OF PAGE) -->
            <div id="official-bci-source" class="stat-box" style="padding: 2rem; margin-top: 2rem; border-top: 4px solid var(--brand-red); background: #ffffff;">
                <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;">
                    <div style="width: 52px; height: 52px; border-radius: 12px; background: #fee2e2; color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div>
                        <span class="badge-verification" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.75rem; padding: 0.2rem 0.55rem; margin-bottom: 0.35rem; display: inline-block;">
                            Official Source Document
                        </span>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); margin: 0 0 0.25rem;">
                            Bar Council of India Official Notification &amp; Recognized Foreign Universities Document
                        </h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                            Official authenticated PDF notification published by the Bar Council of India (BCI) under the Advocates Act, 1961.
                        </p>
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="font-size: 0.8125rem; font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Direct Official BCI Document Link (Amazon S3):
                    </div>
                    <div style="word-break: break-all; font-family: monospace; font-size: 0.8125rem; color: #1e40af; background: #ffffff; padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid #cbd5e1; margin-bottom: 1rem;">
                        <a href="<?= sanitize($officialBciPdfUrl) ?>" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: none;">
                            <?= sanitize($officialBciPdfUrl) ?>
                        </a>
                    </div>

                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                        <a href="<?= sanitize($officialBciPdfUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-md" style="font-weight: 700;">
                            <i class="fas fa-download me-2"></i> Download Official BCI PDF (BCA0001X81XET1H2Z.pdf)
                        </a>
                        <a href="<?= sanitize($directPdfUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-md">
                            <i class="fas fa-arrow-up-right-from-square me-2"></i> Direct PDF Link
                        </a>
                        <a href="http://www.barcouncilofindia.org/" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-md">
                            <i class="fas fa-globe me-2"></i> Official BCI Website
                        </a>
                    </div>
                </div>

                <div style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6;">
                    <strong>Disclaimer:</strong> The list of foreign universities recognized by the Bar Council of India is updated periodically by the Legal Education Committee and the Council via gazette notifications. Prospective candidates are strongly advised to verify their specific graduation cohort and syllabus with the Bar Council of India prior to seeking provisional enrollment with State Bar Councils.
                </div>
            </div>
        </div>

        <!-- Right Column: Summary Facts, Quick Links & Career Advice -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Summary Facts Box -->
            <div class="stat-box" style="padding: 1.5rem; border-top: 4px solid var(--brand-gold);">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--primary); margin-bottom: 1.25rem;">
                    <i class="fas fa-circle-info text-primary me-1"></i> Foreign Law Quick Facts
                </h3>

                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.875rem;">
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Statutory Authority:</span>
                        <strong style="color: var(--primary);">Bar Council of India</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Governing Law:</span>
                        <strong style="color: var(--primary);">Advocates Act, 1961</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Sections Applicable:</span>
                        <strong style="color: var(--primary);">Sec. 7(1)(i) &amp; 24(1)(c)</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Approved Countries:</span>
                        <strong style="color: var(--primary);"><?= $totalCountries ?> Jurisdictions</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Approved Universities:</span>
                        <strong style="color: #b45309;"><?= $totalForeignUnivs ?>+ Universities</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between; padding-bottom: 0.65rem; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-muted);">Mode of Education:</span>
                        <strong style="color: #16a34a;">Full-Time Regular Only</strong>
                    </li>
                    <li style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-muted);">Prerequisite Exam:</span>
                        <strong style="color: var(--brand-red);">BCI Qualifying Exam</strong>
                    </li>
                </ul>
            </div>

            <!-- Indian Law Colleges Directory Shortcut -->
            <div class="stat-box" style="padding: 1.5rem; background: #f8fafc;">
                <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem;">
                    <i class="fas fa-building-columns text-primary me-1"></i> Indian Law Colleges Directory
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Explore 5,000+ Bar Council of India approved domestic law colleges, universities, and seat quotas across all 28 States &amp; 8 UTs.
                </p>
                <a href="law-college-search" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; font-weight: 600;">
                    <i class="fas fa-magnifying-glass me-1"></i> Search Indian Law Colleges
                </a>
            </div>

            <!-- AIBE & Bare Acts Hub -->
            <div class="stat-box" style="padding: 1.5rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: var(--radius-md);">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <i class="fas fa-scale-balanced" style="color: var(--brand-gold); font-size: 1.25rem;"></i>
                    <h4 style="font-size: 1.05rem; font-weight: 800; color: #ffffff; margin: 0;">
                        Indian Legal Codes
                    </h4>
                </div>
                <p style="font-size: 0.8125rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1rem;">
                    Prepare for the BCI Qualifying Examination &amp; AIBE with updated Indian Bare Acts including BNS, BNSS, and BSA.
                </p>
                <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                    <a href="acts" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center; font-weight: 600;">
                        <i class="fas fa-book-bookmark me-1"></i> Explore Indian Bare Acts
                    </a>
                    <a href="aibe" class="btn btn-outline btn-sm" style="width: 100%; justify-content: center; color: #ffffff; border-color: #475569;">
                        <i class="fas fa-graduation-cap me-1"></i> AIBE Exam Portal
                    </a>
                </div>
            </div>

            <!-- Share Page Card -->
            <div class="stat-box" style="padding: 1.5rem; text-align: center;">
                <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
                    Share Foreign Universities List
                </h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1rem;">
                    Share this verified directory with students planning foreign legal education.
                </p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                    <a href="https://api.whatsapp.com/send?text=<?= urlencode("Check out Foreign Universities Whose Degrees in Law are Recognized by the Bar Council of India (BCI): " . APP_URL . "/foreign-law-universities") ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success btn-sm" style="color: #16a34a; border-color: #86efac;">
                        <i class="fab fa-whatsapp me-1"></i> WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline btn-sm btn-copy-link" data-url="<?= APP_URL ?>/foreign-law-universities">
                        <i class="fas fa-link me-1"></i> Copy Link
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterCountry(countryName, btn) {
    document.querySelectorAll('.btn-country-filter').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btn) btn.classList.add('active');

    var sections = document.querySelectorAll('.country-section');
    sections.forEach(function(sec) {
        if (countryName === 'ALL' || sec.getAttribute('data-country-name') === countryName) {
            sec.style.display = 'block';
        } else {
            sec.style.display = 'none';
        }
    });
}

function searchForeignUniv(query) {
    query = (query || '').trim().toLowerCase();
    var rows = document.querySelectorAll('.univ-item-row');
    rows.forEach(function(row) {
        var name = row.getAttribute('data-univ-name') || '';
        var loc = row.getAttribute('data-univ-location') || '';
        if (!query || name.indexOf(query) !== -1 || loc.indexOf(query) !== -1) {
            row.style.display = 'flex';
        } else {
            row.style.display = 'none';
        }
    });

    // Hide empty country sections
    document.querySelectorAll('.country-section').forEach(function(sec) {
        var visibleRows = sec.querySelectorAll('.univ-item-row:not([style*="display: none"])');
        if (visibleRows.length === 0 && query !== '') {
            sec.style.display = 'none';
        } else if (query !== '') {
            sec.style.display = 'block';
        }
    });
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
