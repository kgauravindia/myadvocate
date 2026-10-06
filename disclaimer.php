<?php
// disclaimer.php - Complete Official BCI & Legal Disclaimer
require_once __DIR__ . '/config/app.php';

$pageTitle = "Website / App Disclaimer - My Advocate";
$pageDescription = "Official website and app disclaimer under Bar Council of India rules regarding non-solicitation, external links, professional advice, and public legal resources.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./">Home</a> &bull; <span>Legal & Compliance</span> &bull; <span>Disclaimer</span>
    </nav>

    <div style="max-width: 900px; margin: 0 auto;">
        <!-- Legal Switcher Tabs -->
        <div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; overflow-x: auto; padding-bottom: 4px;">
            <a href="disclaimer" class="btn btn-primary btn-sm">
                <i class="fas fa-gavel"></i> BCI Disclaimer
            </a>
            <a href="privacy" class="btn btn-outline btn-sm">
                <i class="fas fa-user-shield"></i> Privacy Policy
            </a>
            <a href="terms" class="btn btn-outline btn-sm">
                <i class="fas fa-file-contract"></i> Terms and Conditions
            </a>
        </div>

        <div class="stat-box" style="padding: 2.5rem 2rem; border-top: 4px solid var(--brand-red);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <span class="badge-verification badge-verified" style="margin-bottom: 0.5rem;">
                        <i class="fas fa-scale-balanced"></i> Bar Council of India Compliance
                    </span>
                    <h1 style="font-size: 2rem; color: var(--primary); margin-top: 0.25rem;">
                        Website / App Disclaimer
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
                <!-- 1. WEBSITE DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        1. WEBSITE DISCLAIMER
                    </h3>
                    <p style="margin-bottom: 0.75rem;">
                        The information provided by <strong>My Advocate (perviously MyAdv India)</strong> an initiative by <strong>OfferPlant Technologies Private Limited</strong> (“Company”, “we”, “our”, “us”) on <strong>https://myadv.in</strong> (the “Site”) is for general informational purposes only. All information on the Site is provided in good faith, however we make no representation or warranty of any kind, express or implied, regarding the accuracy, adequacy, validity, reliability, availability, or completeness of any information on the Site.
                    </p>
                    <div style="background: #fef2f2; border-left: 4px solid var(--brand-red); padding: 0.85rem 1rem; border-radius: 0 var(--radius-sm) var(--radius-sm) 0; font-size: 0.875rem; color: #991b1b; font-weight: 600;">
                        UNDER NO CIRCUMSTANCE SHALL WE HAVE ANY LIABILITY TO YOU FOR ANY LOSS OR DAMAGE OF ANY KIND INCURRED AS A RESULT OF THE USE OF THE SITE OR RELIANCE ON ANY INFORMATION PROVIDED ON THE SITE. YOUR USE OF THE SITE AND YOUR RELIANCE ON ANY INFORMATION ON THE SITE IS SOLELY AT YOUR OWN RISK.
                    </div>
                </div>

                <!-- 2. EXTERNAL LINKS DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        2. EXTERNAL LINKS DISCLAIMER
                    </h3>
                    <p style="margin-bottom: 0.75rem;">
                        The Site may contain (or you may be sent through the Site) links to other websites or content belonging to or originating from third parties or links to websites and features. Such external links are not investigated, monitored, or checked for accuracy, adequacy, validity, reliability, availability, or completeness by us.
                    </p>
                    <p>
                        WE DO NOT WARRANT, ENDORSE, GUARANTEE, OR ASSUME RESPONSIBILITY FOR THE ACCURACY OR RELIABILITY OF ANY INFORMATION OFFERED BY THIRD-PARTY WEBSITES LINKED THROUGH THE SITE OR ANY WEBSITE OR FEATURE LINKED IN ANY BANNER OR OTHER ADVERTISING. WE WILL NOT BE A PARTY TO OR IN ANY WAY BE RESPONSIBLE FOR MONITORING ANY TRANSACTION BETWEEN YOU AND THIRD-PARTY PROVIDERS OF PRODUCTS OR SERVICES.
                    </p>
                </div>

                <!-- 3. PROFESSIONAL DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        3. PROFESSIONAL LEGAL DISCLAIMER
                    </h3>
                    <p style="margin-bottom: 0.75rem;">
                        The Site can not and does not contain legal advice. The information is provided for general informational and educational purposes only and is not a substitute for professional legal advice. Accordingly, before taking any actions based upon such information, we encourage you to consult with the appropriate licensed professionals. We do not provide any kind of legal advice.
                    </p>
                    <p style="margin-bottom: 0.75rem;">
                        Content published on <strong>https://myadv.in</strong> is intended to be used and must be used for informational purposes only. It is very important to do your own analysis before making any decision based on your own personal circumstances. You should take independent legal advice from a professional or independently research and verify any information that you find on our Website and wish to rely upon.
                    </p>
                    <p style="font-weight: 700; color: var(--brand-red);">
                        THE USE OR RELIANCE OF ANY INFORMATION CONTAINED ON THIS SITE IS SOLELY AT YOUR OWN RISK.
                    </p>
                </div>

                <!-- 4. TESTIMONIALS DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        4. TESTIMONIALS DISCLAIMER
                    </h3>
                    <p style="margin-bottom: 0.75rem;">
                        The Site may contain testimonials by users of our products and/or services. These testimonials reflect the real-life experiences and opinions of such users. However, the experiences are personal to those particular users, and may not necessarily be representative of all users of our products and/or services. We do not claim, and you should not assume that all users will have the same experiences.
                    </p>
                    <p style="margin-bottom: 0.75rem; font-weight: 600;">
                        YOUR INDIVIDUAL RESULTS MAY VARY.
                    </p>
                    <p>
                        The testimonials on the Site are submitted in various forms such as text, audio and/or video, and are reviewed by us before being posted. They appear on the Site verbatim as given by the users, except for the correction of grammar or typing errors. Some testimonials may have been shortened for the sake of brevity, where the full testimonial contained extraneous information not relevant to the general public. The views and opinions contained in the testimonials belong solely to the individual user and do not reflect our views and opinions.
                    </p>
                </div>

                <!-- 5. ERRORS AND OMISSIONS DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        5. ERRORS AND OMISSIONS DISCLAIMER
                    </h3>
                    <p style="margin-bottom: 0.75rem;">
                        While we have made every attempt to ensure that the information contained in this site has been obtained from reliable sources, My Advocate (perviously MyAdv India) an initiative by OfferPlant Technologies Private Limited is not responsible for any errors or omissions or for the results obtained from the use of this information. All information in this site is provided “as is”, with no guarantee of completeness, accuracy, timeliness or of the results obtained from the use of this information, and without warranty of any kind, express or implied, including, but not limited to warranties of performance, merchantability, and fitness for a particular purpose.
                    </p>
                    <p>
                        In no event will My Advocate (perviously MyAdv India) an initiative by OfferPlant Technologies Private Limited, its related partnerships or corporations, or the partners, agents or employees thereof be liable to you or anyone else for any decision made or action taken in reliance on the information in this Site or for any consequential, special or similar damages, even if advised of the possibility of such damages.
                    </p>
                </div>

                <!-- 6. GUEST CONTRIBUTORS DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        6. GUEST CONTRIBUTORS DISCLAIMER
                    </h3>
                    <p>
                        This Site may include content from guest contributors and any views or opinions expressed in such posts are personal and do not represent those of My Advocate (perviously MyAdv India) an initiative by OfferPlant Technologies Private Limited or any of its staff or affiliates unless explicitly stated.
                    </p>
                </div>

                <!-- 7. LOGOS AND TRADEMARKS DISCLAIMER -->
                <div>
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        7. LOGOS AND TRADEMARKS DISCLAIMER
                    </h3>
                    <p>
                        All logos and trademarks of third parties referenced on <strong>https://myadv.in</strong> are the trademarks and logos of their respective owners. Any inclusion of such trademarks or logos does not imply or constitute any approval, endorsement or sponsorship of My Advocate (perviously MyAdv India) an initiative by OfferPlant Technologies Private Limited by such owners.
                    </p>
                </div>

                <!-- 8. CONTACT US -->
                <div style="background: #f8fafc; padding: 1.25rem 1.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; color: var(--primary); margin-bottom: 0.5rem;">
                        8. CONTACT US
                    </h3>
                    <p style="margin-bottom: 0.5rem;">
                        Should you have any feedback, comments, requests for technical support or other inquiries, please contact us:
                    </p>
                    <p style="margin-bottom: 0;">
                        <strong>Email:</strong> <a href="mailto:help@myadv.in">help@myadv.in</a> &bull; 
                        <strong>Website:</strong> <a href="./">https://myadv.in</a> &bull; 
                        <strong>Grievance Form:</strong> <a href="contact">Contact & Grievance Desk</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
