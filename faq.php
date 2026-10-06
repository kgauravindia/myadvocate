<?php
// faq.php - Frequently Asked Questions & Legal Help
require_once __DIR__ . '/config/app.php';

$pageTitle = "Frequently Asked Questions (FAQ) - My Advocate";
$pageDescription = "Answers to frequently asked questions about advocate profile claiming, verification badges, BCI non-solicitation compliance, and digital legal tools.";

$faqCategories = [
    'General' => [
        [
            'q' => 'What is My Advocate (myadv.in)?',
            'a' => 'My Advocate (formerly MyAdv India) is India’s dedicated digital legal repository and directory platform. It unites over 1.6 Lakh advocate public listings, Central & State Bare Acts, AIBE examination preparation materials, Law Colleges directory, and Indian court case status portals.'
        ],
        [
            'q' => 'Does My Advocate provide legal advice or lawyer hiring services?',
            'a' => 'No. In strict compliance with the Bar Council of India (BCI) rules, My Advocate is purely an informational and educational portal. We do not provide legal advice, solicit clients, or charge commissions on advocate engagements.'
        ],
        [
            'q' => 'How can citizens find an advocate in their district?',
            'a' => 'You can use our unified <a href="advocate-search-result">Advocate Search Directory</a> to filter by State, District, Practice Area (e.g. Criminal Law, Civil Disputes, Family Law, Constitutional), or search by Enrollment Year and Name.'
        ]
    ],
    'Advocates & Verification' => [
        [
            'q' => 'How can an advocate claim their listed profile?',
            'a' => 'Advocates can visit the <a href="claim-profile">Claim Profile</a> portal, enter their State Bar Council Enrollment Number and Year, and complete verification via OTP sent to their registered mobile or email.'
        ],
        [
            'q' => 'What are the different verification badges?',
            'a' => 'We maintain 3 verification tiers: <strong>Basic Profile</strong> (Public gazetted record), <strong>Registered Profile</strong> (Claimed and managed by the advocate), and <strong>Verified Profile</strong> (Authenticated via Bar Council ID or official credential validation).'
        ],
        [
            'q' => 'How do I control my phone number and email privacy?',
            'a' => 'Once logged into your <a href="dashboard">Advocate Dashboard</a>, you can toggle contact privacy settings between <em>Public</em>, <em>Masked / Registered Only</em>, or <em>Private / Hidden</em> at any time.'
        ]
    ],
    'Bare Acts & Legal Tools' => [
        [
            'q' => 'Are the new 2023 Criminal Codes (BNS, BNSS, BSA) available?',
            'a' => 'Yes. The full sections, comparative guides, and amendments for Bharatiya Nyaya Sanhita (BNS), Bharatiya Nagarik Suraksha Sanhita (BNSS), and Bharatiya Sakshya Adhiniyam (BSA) are fully indexed in our <a href="acts">Bare Acts Library</a>.'
        ],
        [
            'q' => 'How accurate are the Court Fee and Limitation Calculators?',
            'a' => 'Our <a href="tools">Legal Calculators</a> provide accurate estimates based on standard schedules of the Court Fees Act and the Limitation Act, 1963 for standard suits (money recovery, partition, declarations).'
        ]
    ],
    'Compliance & Grievances' => [
        [
            'q' => 'How do I report inaccurate information or request profile removal?',
            'a' => 'You can submit a formal rectification request through our <a href="contact">Contact & Grievance Desk</a>. Our administrative team reviews and updates verified requests within 24-48 business hours.'
        ]
    ]
];

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Support</span> &bull; <span>Frequently Asked Questions</span>
    </nav>

    <!-- Header Banner -->
    <div class="stat-box" style="margin-bottom: 2rem; background: linear-gradient(135deg, #ffffff 0%, #fffdf0 100%); border-left: 5px solid var(--brand-gold);">
        <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
            <i class="fas fa-circle-question"></i> Help & FAQs
        </span>
        <h1 style="font-size: 2.1rem; color: var(--primary); margin-top: 0.25rem;">
            Frequently Asked Questions
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 820px; margin-top: 0.35rem; line-height: 1.6;">
            Find quick answers regarding advocate profile claiming, verification badges, contact privacy, Bare Acts navigation, and Bar Council of India non-solicitation compliance.
        </p>
    </div>

    <!-- FAQ Accordion Sections -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <?php foreach ($faqCategories as $category => $items): ?>
            <div class="stat-box" style="padding: 1.75rem 2rem;">
                <h2 style="font-size: 1.25rem; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-folder-open" style="color: var(--brand-red);"></i> <?= sanitize($category) ?>
                </h2>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach ($items as $idx => $faq): ?>
                        <details class="faq-item" style="background: var(--bg-alt); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.85rem 1.15rem; transition: var(--transition);">
                            <summary style="font-weight: 700; color: var(--primary); cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none; font-size: 0.95rem;">
                                <span><i class="fas fa-circle-chevron-right" style="color: var(--brand-red); font-size: 0.85rem; margin-right: 0.5rem;"></i> <?= sanitize($faq['q']) ?></span>
                                <i class="fas fa-plus faq-icon" style="font-size: 0.8rem; color: var(--text-muted);"></i>
                            </summary>
                            <div style="padding-top: 0.85rem; margin-top: 0.75rem; border-top: 1px solid var(--border-color); color: var(--text-main); font-size: 0.9rem; line-height: 1.7;">
                                <?= $faq['a'] ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Need More Help Banner -->
    <div class="stat-box" style="margin-top: 2rem; background: #f8fafc; text-align: center; padding: 2.5rem 1.5rem;">
        <h3 style="font-size: 1.4rem; color: var(--primary); margin-bottom: 0.5rem;">Still have questions?</h3>
        <p style="color: var(--text-muted); font-size: 0.9375rem; max-width: 500px; margin: 0 auto 1.25rem;">
            Our support desk is available to assist advocates and citizens with any platform inquiries.
        </p>
        <a href="contact" class="btn btn-primary">
            <i class="fas fa-envelope"></i> Contact Support Desk
        </a>
    </div>
</div>

<style>
details[open] .faq-icon {
    transform: rotate(45deg);
}
details[open] {
    background: #ffffff !important;
    border-color: var(--brand-red-border) !important;
    box-shadow: var(--shadow-sm);
}
</style>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
