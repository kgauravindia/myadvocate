<?php
// includes/footer.php - Clean & Simple Global Footer
?>
</main>

<footer class="site-footer">
    <div class="footer-top" style="padding: 3rem 0 2rem;">
        <div class="container">
            <div class="footer-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem;">
                <!-- Brand Column -->
                <div class="footer-col-brand">
                    <div class="footer-logo" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.85rem;">
                        <img src="assets/images/logo-circle.png" alt="<?= APP_NAME ?>" style="height: 44px; width: 44px; border-radius: 50%; object-fit: cover; border: 2px solid var(--brand-gold-light);">
                        <span style="font-family: var(--font-heading); font-size: 1.35rem; font-weight: 800; color: var(--primary); letter-spacing: -0.02em;">
                            MY <span style="color: var(--brand-red);">ADVOCATE</span>
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6; margin-bottom: 1.25rem;">
                        India's digital advocate directory, Bare Acts repository, and legal assistance platform.
                    </p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($fb = getSiteConfig('facebook', 'https://facebook.com/MyAdvocateAI')): ?>
                            <a href="<?= sanitize($fb) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #1877f2;" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if ($ig = getSiteConfig('instagram', 'https://instagram.com/MyAdvocateAI')): ?>
                            <a href="<?= sanitize($ig) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #e4405f;" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if ($tw = getSiteConfig('twitter', 'https://x.com/MyAdvocateAI')): ?>
                            <a href="<?= sanitize($tw) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #0f172a;" title="Twitter / X"><i class="fab fa-x-twitter"></i></a>
                        <?php endif; ?>
                        <?php if ($tg = getSiteConfig('telegram', 'https://t.me/MyAdvocateAI')): ?>
                            <a href="<?= sanitize($tg) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #0088cc;" title="Telegram"><i class="fab fa-telegram"></i></a>
                        <?php endif; ?>
                        <?php if ($li = getSiteConfig('linkedin', 'https://linkedin.com/company/MyAdvocateAI')): ?>
                            <a href="<?= sanitize($li) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #0077b5;" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if ($wa = getSiteConfig('whatsapp', 'https://whatsapp.com/channel/0029VaA5aAnL7UVOohTGS31P')): ?>
                            <a href="<?= sanitize($wa) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm" style="width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; color: #16a34a; background: #f0fdf4;" title="WhatsApp Channel"><i class="fab fa-whatsapp"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Column 1: Advocates Directory -->
                <div>
                    <h4 class="footer-title">Advocate Services</h4>
                    <ul class="footer-links">
                        <li><a href="advocates"><i class="fas fa-search" style="font-size: 0.75rem; color: var(--brand-red);"></i> Find Advocates</a></li>
                        <li><a href="claim-profile"><i class="fas fa-id-badge" style="font-size: 0.75rem; color: var(--brand-gold-dark);"></i> Claim Profile</a></li>
                        <li><a href="register"><i class="fas fa-user-plus" style="font-size: 0.75rem;"></i> Register as Advocate</a></li>
                        <li><a href="login"><i class="fas fa-right-to-bracket" style="font-size: 0.75rem;"></i> Advocate Login</a></li>
                        <li><a href="pricing"><i class="fas fa-shield-check" style="font-size: 0.75rem;"></i> Verification Plans</a></li>
                    </ul>
                </div>

                <!-- Column 2: Legal Resources -->
                <div>
                    <h4 class="footer-title">Legal Resources</h4>
                    <ul class="footer-links">
                        <li><a href="bare-acts"><i class="fas fa-book-bookmark" style="font-size: 0.75rem; color: var(--brand-red);"></i> Central Bare Acts</a></li>
                        <li><a href="act-details?id=bns"><i class="fas fa-scale-balanced" style="font-size: 0.75rem;"></i> BNS, BNSS & BSA 2023</a></li>
                        <li><a href="courts"><i class="fas fa-landmark" style="font-size: 0.75rem;"></i> Supreme & High Courts</a></li>
                        <li><a href="tools"><i class="fas fa-calculator" style="font-size: 0.75rem; color: var(--brand-gold-dark);"></i> Court Fee & Limitation</a></li>
                        <li><a href="aibe"><i class="fas fa-graduation-cap" style="font-size: 0.75rem;"></i> AIBE Exam Prep</a></li>
                    </ul>
                </div>

                <!-- Column 3: Quick Links & Information -->
                <div>
                    <h4 class="footer-title">Company & Info</h4>
                    <ul class="footer-links">
                        <li><a href="about"><i class="fas fa-circle-info" style="font-size: 0.75rem;"></i> About Us</a></li>
                        <li><a href="mission-vision"><i class="fas fa-compass" style="font-size: 0.75rem; color: var(--brand-red);"></i> Mission &amp; Vision</a></li>
                        <li><a href="state-bar-council"><i class="fas fa-scale-unbalanced" style="font-size: 0.75rem;"></i> State Bar Councils</a></li>
                        <li><a href="public-service-commission"><i class="fas fa-landmark-flag" style="font-size: 0.75rem;"></i> Public Service Commissions</a></li>
                        <li><a href="faq"><i class="fas fa-circle-question" style="font-size: 0.75rem;"></i> FAQs &amp; Help</a></li>
                    </ul>
                </div>
            </div>

            <!-- Simple BCI Note -->
            <div style="margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); font-size: 0.75rem; color: var(--text-light); line-height: 1.6; text-align: center;">
                <i class="fas fa-scale-balanced text-primary" style="margin-right: 4px;"></i>
                <strong>Statutory Note:</strong> As per Bar Council of India (BCI) rules, advocates are not permitted to advertise or solicit clients. This website is purely an informational directory and legal knowledge platform.
            </div>
        </div>
    </div>

    <!-- Simple Bottom Copyright Bar (Left & Right Single Line) -->
    <div class="footer-bottom">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; font-size: 0.8125rem;">
            <div style="white-space: nowrap;">
                &copy; <?= date('Y') ?> <strong><?= APP_NAME ?></strong> (myadv.in). All rights reserved.
            </div>
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: nowrap; white-space: nowrap;">
                <a href="privacy-policy" style="color: inherit; text-decoration: none;">Privacy</a>
                <span style="opacity: 0.35;">|</span>
                <a href="terms" style="color: inherit; text-decoration: none;">Terms</a>
                <span style="opacity: 0.35;">|</span>
                <a href="disclaimer" style="color: inherit; text-decoration: none;">Disclaimer</a>
                <span style="opacity: 0.35;">|</span>
                <a href="contact" style="color: inherit; text-decoration: none;">Helpdesk</a>
            </div>
        </div>
    </div>
</footer>

<!-- BCI Compliance Mandatory Disclaimer Modal (First-Time Visit Popup) -->
<div class="bci-modal-overlay" id="bciDisclaimerModal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="bciModalTitle">
    <div class="bci-modal-dialog">
        <div class="bci-modal-header">
            <div class="bci-modal-icon">
                <i class="fas fa-scale-balanced"></i>
            </div>
            <div>
                <span class="badge-verification badge-verified" style="font-size: 0.7rem; padding: 0.2rem 0.6rem; margin-bottom: 0.35rem; display: inline-flex;">
                    <i class="fas fa-shield-halved"></i> Statutory Compliance
                </span>
                <h3 class="bci-modal-title" id="bciModalTitle">BCI Compliance Disclaimer & Confirmation</h3>
            </div>
        </div>
        <div class="bci-modal-body">
            <p class="bci-modal-lead">
                As per the rules of the <strong>Bar Council of India (BCI)</strong>, advocates and legal directory platforms are strictly prohibited from soliciting work or advertising in any manner.
            </p>
            <div class="bci-modal-points">
                <div class="bci-point-item">
                    <i class="fas fa-circle-check text-success"></i>
                    <span>You are accessing <strong><?= APP_NAME ?> (myadv.in)</strong> voluntarily for your own information, knowledge, and educational purposes.</span>
                </div>
                <div class="bci-point-item">
                    <i class="fas fa-circle-check text-success"></i>
                    <span>There has been <strong>no advertisement, personal communication, solicitation, invitation, or inducement</strong> of any sort whatsoever from us or any advocate listed on this website.</span>
                </div>
                <div class="bci-point-item">
                    <i class="fas fa-circle-check text-success"></i>
                    <span>The information provided does not constitute legal advice and does not create an advocate-client relationship.</span>
                </div>
                <div class="bci-point-item">
                    <i class="fas fa-circle-check text-success"></i>
                    <span>All directory records are curated from public State Bar Council rolls and verified member registrations.</span>
                </div>
            </div>
            <p class="bci-modal-note">
                By clicking <strong>"I Agree & Proceed"</strong>, you acknowledge and agree to the above terms.
            </p>
        </div>
        <div class="bci-modal-footer">
            <a href="disclaimer" class="btn btn-outline btn-sm">
                <i class="fas fa-circle-info"></i> Full Disclaimer
            </a>
            <button type="button" class="btn btn-primary btn-md" id="bciAcceptBtn">
                <i class="fas fa-check"></i> I Agree &amp; Proceed
            </button>
        </div>
    </div>
</div>

<script src="assets/js/main.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
