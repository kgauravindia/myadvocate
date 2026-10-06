<?php
// terms.php - Complete Official Terms and Conditions & Platform Usage
require_once __DIR__ . '/config/app.php';

$pageTitle = "Terms and Conditions - My Advocate";
$pageDescription = "Official Terms and Conditions governing access and usage of My Advocate digital legal platform, directory, Bare Acts, and services.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Legal & Compliance</span> &bull; <span>Terms and Conditions</span>
    </nav>

    <div style="max-width: 900px; margin: 0 auto;">
        <!-- Legal Switcher Tabs -->
        <div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 4px;">
            <a href="disclaimer" class="btn btn-outline btn-sm">
                <i class="fas fa-gavel"></i> BCI Disclaimer
            </a>
            <a href="privacy" class="btn btn-outline btn-sm">
                <i class="fas fa-user-shield"></i> Privacy Policy
            </a>
            <a href="terms" class="btn btn-primary btn-sm">
                <i class="fas fa-file-contract"></i> Terms and Conditions
            </a>
        </div>

        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
                        <i class="fas fa-file-signature"></i> Terms of Service
                    </span>
                    <h1 style="font-size: 2rem; color: var(--primary); margin-top: 0.25rem;">
                        Terms and Conditions
                    </h1>
                </div>
                <div style="font-size: 0.8125rem; color: var(--text-muted); background: var(--bg-alt); padding: 0.4rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); font-weight: 600;">
                    <i class="fas fa-calendar-check" style="color: var(--brand-gold);"></i> Last Update : 02 Oct 2026
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.75rem;">
                Effective from: 02 Oct 2026
            </p>

            <div style="line-height: 1.8; color: var(--text-main); font-size: 0.9375rem; display: flex; flex-direction: column; gap: 1.75rem;">
                <!-- 1. Introduction -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">1. Introduction</h3>
                    <p style="margin-bottom: 0.75rem;">
                        Welcome to <strong>My Advocate (perviously MyAdv India)</strong>, an initiative by <strong>OfferPlant Technologies Private Limited</strong> (“Company”, “we”, “our”, “us”, “My Advocate”, “MyAdv India”)!
                    </p>
                    <p style="margin-bottom: 0.75rem;">
                        These Terms of Service (“Terms”, “Terms of Service”) govern your use of our website located at <strong>https://myadv.in</strong> (together or individually “Service”) operated by <strong>OfferPlant Technologies Private Limited</strong>, Registered Office: <em>2B, Kumar Bhawan, Umanagar, Chapra, District - Saran, State - Bihar, India 841301</em>.
                    </p>
                    <p style="margin-bottom: 0.75rem;">
                        Our Privacy Policy also governs your use of our Service and explains how we collect, safeguard, and disclose information resulting from your use of our web pages.
                    </p>
                    <p>
                        Your agreement with us includes these Terms and our Privacy Policy (“Agreements”). You acknowledge that you have read and understood Agreements, and agree to be bound by them.
                    </p>
                </div>

                <!-- 2. Communications -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">2. Communications</h3>
                    <p>
                        By using our Service, you agree to receive essential transactional notifications, verification codes, and administrative updates. You may opt out of promotional communications at any time.
                    </p>
                </div>

                <!-- 3. Purchases & Payments -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">3. Purchases & Subscriptions</h3>
                    <p style="margin-bottom: 0.75rem;">
                        If you wish to purchase any premium listing, subscription tier, or service made available through the platform, you represent that you have the legal right to use payment methods provided. All financial transactions are processed securely through certified third-party payment gateways.
                    </p>
                    <p>
                        Subscriptions renew automatically at the end of each billing cycle unless cancelled prior to renewal through your dashboard.
                    </p>
                </div>

                <!-- 4. Refunds -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">4. Refund Policy</h3>
                    <p>
                        We issue refunds for eligible premium contracts and subscriptions requested within <strong>7 days</strong> of the original transaction date, subject to verification.
                    </p>
                </div>

                <!-- 5. Content & User Submissions -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">5. Content & Profiles</h3>
                    <p style="margin-bottom: 0.75rem;">
                        Our Service allows advocates and members to manage profile summaries, practice specializations, and professional contact visibility (“Content”). You represent and warrant that the information submitted is truthful, accurate, and does not misrepresent bar council credentials or infringe on third-party rights.
                    </p>
                    <p>
                        OfferPlant Technologies Private Limited reserves the right to monitor, review, edit, or remove content deemed inaccurate, deceptive, or violating BCI standards.
                    </p>
                </div>

                <!-- 6. Prohibited Uses -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">6. Prohibited Uses</h3>
                    <p style="margin-bottom: 0.5rem;">You agree NOT to use the Service:</p>
                    <ul style="padding-left: 1.25rem; display: flex; flex-direction: column; gap: 0.35rem;">
                        <li>In any way that violates applicable central, state, or international laws or BCI regulations.</li>
                        <li>To transmit unsolicited promotional spam, bulk marketing, or chain communications.</li>
                        <li>To impersonate any advocate, bar council official, judge, court, or legal entity.</li>
                        <li>To engage in automated crawling, scraping, scraping tools, or harvesting our database without prior written authorization.</li>
                        <li>To introduce malware, viruses, trojans, or launch denial-of-service attacks against platform infrastructure.</li>
                    </ul>
                </div>

                <!-- 7. Accounts & Security -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">7. Accounts</h3>
                    <p>
                        When creating an account or claiming an advocate profile, you guarantee that you are authorized to manage that legal identity. You are responsible for maintaining the confidentiality of your credentials and all activity occurring under your account.
                    </p>
                </div>

                <!-- 8. Intellectual Property -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">8. Intellectual Property</h3>
                    <p>
                        The Service, including original platform design, UI elements, database schema, and tools (excluding official public statutory text), are the exclusive property of OfferPlant Technologies Private Limited and protected by copyright and intellectual property laws.
                    </p>
                </div>

                <!-- 9. Disclaimer of Warranty & Limitation of Liability -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">9. Limitation of Liability</h3>
                    <p style="margin-bottom: 0.75rem;">
                        THE SERVICE IS PROVIDED ON AN “AS IS” AND “AS AVAILABLE” BASIS. OFFERPLANT TECHNOLOGIES PRIVATE LIMITED MAKES NO WARRANTIES REGARDING ABSOLUTE ACCURACY OF PUBLIC COURT ROSTERS OR CONTINUOUS UNINTERRUPTED AVAILABILITY.
                    </p>
                    <p>
                        IN NO EVENT SHALL THE COMPANY, ITS DIRECTORS, EMPLOYEES, OR AGENTS BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, OR CONSEQUENTIAL DAMAGES ARISING OUT OF THE USE OR INABILITY TO USE THE PLATFORM.
                    </p>
                </div>

                <!-- 10. Governing Law -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">10. Governing Law & Jurisdiction</h3>
                    <p>
                        These Terms shall be governed and construed in accordance with the laws of the Republic of India. Any disputes arising in connection with the platform shall be subject to the exclusive jurisdiction of the competent courts in Bihar, India.
                    </p>
                </div>

                <!-- 11. Contact Us -->
                <div style="background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        11. Contact & Grievance
                    </h3>
                    <p style="margin-bottom: 0.5rem;">
                        For questions, notices, or inquiries regarding these Terms and Conditions:
                    </p>
                    <p style="margin-bottom: 0;">
                        <strong>OfferPlant Technologies Private Limited</strong> &bull; 
                        <strong>Email:</strong> <a href="mailto:help@myadv.in">help@myadv.in</a> &bull; 
                        <strong>Desk:</strong> <a href="contact">Contact & Grievance Desk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
