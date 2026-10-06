<?php
// mission-vision.php - Mission & Vision of My Advocate Platform
require_once __DIR__ . '/config/app.php';

$pageTitle = "Mission & Vision - My Advocate";
$pageDescription = "Discover the mission, vision, and core values driving My Advocate (myadv.in) to democratize legal access and empower India's legal ecosystem.";

require_once INCLUDES_PATH . '/header.php';
?>

<div class="container" style="padding-top: 2rem; padding-bottom: 4rem;">
    <!-- Breadcrumbs -->
    <nav style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.25rem;">
        <a href="./" style="color: var(--primary); text-decoration: none;"><i class="fas fa-home"></i> Home</a> &bull; 
        <a href="about" style="color: var(--primary); text-decoration: none;">About</a> &bull; 
        <span>Mission & Vision</span>
    </nav>

    <div style="max-width: 960px; margin: 0 auto;">
        <!-- Header Switcher Navigation Tabs -->
        <div class="legal-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 4px;">
            <a href="about" class="btn btn-outline btn-sm">
                <i class="fas fa-circle-info"></i> About Us
            </a>
            <a href="mission-vision" class="btn btn-primary btn-sm">
                <i class="fas fa-compass"></i> Mission & Vision
            </a>
            <a href="pricing" class="btn btn-outline btn-sm">
                <i class="fas fa-shield-halved"></i> Verification Plans
            </a>
            <a href="disclaimer" class="btn btn-outline btn-sm">
                <i class="fas fa-gavel"></i> BCI Disclaimer
            </a>
            <a href="contact" class="btn btn-outline btn-sm">
                <i class="fas fa-headset"></i> Contact Support
            </a>
        </div>

        <!-- Hero Header Card -->
        <div class="stat-box" style="padding: 2.5rem 2rem; margin-bottom: 2rem; background: linear-gradient(135deg, rgba(254, 242, 242, 0.6) 0%, rgba(255, 251, 235, 0.6) 100%); border: 1px solid var(--brand-gold-border); border-top: 4px solid var(--brand-red);">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; padding: 0.35rem 0.85rem; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 700; color: var(--brand-red); margin-bottom: 1rem; border: 1px solid #fecaca; box-shadow: var(--shadow-sm);">
                <i class="fas fa-landmark"></i> Empowering India's Legal Fraternity & Citizens
            </div>
            <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--primary); margin-bottom: 1rem; line-height: 1.2;">
                Our Mission & <span style="background: linear-gradient(120deg, #c00000, #b45309); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Vision</span>
            </h1>
            <p style="font-size: 1.125rem; color: var(--text-main); line-height: 1.7; margin-bottom: 0;">
                At <strong>My Advocate (myadv.in)</strong>, we are committed to building India's most trusted, accessible, and comprehensive legal intelligence platform. We bridge the critical gap between citizens seeking lawful redressal and verified legal professionals across the nation.
            </p>
        </div>

        <!-- Vision & Mission Dual Pillars -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
            <!-- Vision Card -->
            <div class="stat-box" style="border-top: 4px solid var(--brand-gold); height: 100%; display: flex; flex-direction: column;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
                    <i class="fas fa-eye"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 0.85rem;">
                    Our Vision
                </h2>
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--text-main); flex: 1;">
                    To establish a transparent, digitally integrated, and equitable legal ecosystem in India where justice is accessible to all, legal knowledge is demystified, and every practicing advocate is recognized through authenticated digital credentials.
                </p>
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
                    <i class="fas fa-check-circle" style="color: #16a34a; margin-right: 0.35rem;"></i> Transparent • Accessible • Technologically Advanced
                </div>
            </div>

            <!-- Mission Card -->
            <div class="stat-box" style="border-top: 4px solid var(--brand-red); height: 100%; display: flex; flex-direction: column;">
                <div style="width: 50px; height: 50px; border-radius: 12px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1.25rem;">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 0.85rem;">
                    Our Mission
                </h2>
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--text-main); flex: 1;">
                    To democratize legal access across India by organizing public bar council records, providing free and up-to-date Central Bare Acts (including Bharatiya Nyaya Sanhita, BNSS, and BSA), empowering law students with exam preparation tools, and furnishing legal practitioners with modern verification infrastructure.
                </p>
                <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px dashed var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
                    <i class="fas fa-check-circle" style="color: #16a34a; margin-right: 0.35rem;"></i> Comprehensive • Reliable • BCI Compliant
                </div>
            </div>
        </div>

        <!-- 3 Key Stakeholders Impact -->
        <div style="margin-bottom: 3rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-bottom: 0.5rem;">
                    How We Serve India's Legal Ecosystem
                </h2>
                <p style="color: var(--text-muted); font-size: 1rem; max-width: 650px; margin: 0 auto;">
                    Tailored resources built for the three foundational pillars of Indian jurisprudence.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                <!-- For Citizens -->
                <div class="stat-box" style="padding: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(192, 0, 0, 0.1); color: var(--brand-red); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary); margin: 0;">For Citizens & Litigants</h3>
                    </div>
                    <ul style="padding-left: 1.25rem; line-height: 1.8; color: var(--text-main); font-size: 0.9rem; margin-bottom: 0;">
                        <li>Fast discovery of verified advocates across all 36 States and UTs.</li>
                        <li>Search by State Bar Council enrollment number, practice court, and specialization.</li>
                        <li>Free access to Indian Court details, High Court benches, and District Courts.</li>
                        <li>Direct access to court fee and limitation period calculators.</li>
                    </ul>
                </div>

                <!-- For Advocates -->
                <div class="stat-box" style="padding: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(180, 83, 9, 0.1); color: var(--brand-gold-dark); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary); margin: 0;">For Legal Practitioners</h3>
                    </div>
                    <ul style="padding-left: 1.25rem; line-height: 1.8; color: var(--text-main); font-size: 0.9rem; margin-bottom: 0;">
                        <li>Claim and manage official digital profile cards with verified badges.</li>
                        <li>Showcase verified practice areas, court admissions, and educational background.</li>
                        <li>Complete compliance with Bar Council of India non-solicitation guidelines.</li>
                        <li>Digital tools to track legal notices, recruitment, and cause lists.</li>
                    </ul>
                </div>

                <!-- For Law Students -->
                <div class="stat-box" style="padding: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(15, 23, 42, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.15rem;">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--primary); margin: 0;">For Law Students & Aspirants</h3>
                    </div>
                    <ul style="padding-left: 1.25rem; line-height: 1.8; color: var(--text-main); font-size: 0.9rem; margin-bottom: 0;">
                        <li>Comprehensive AIBE (All India Bar Examination) preparation portal.</li>
                        <li>Full Bare Acts repository with search and cross-referencing capabilities.</li>
                        <li>Directory of over 4,000+ approved law colleges and universities in India.</li>
                        <li>Up-to-date legal updates, amendments, and notification gazettes.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div class="stat-box" style="padding: 2rem; margin-bottom: 2.5rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-shield-heart" style="color: var(--brand-red);"></i> Our Guiding Principles & Core Values
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; background: var(--bg-card);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; font-size: 1rem;">
                        <i class="fas fa-scale-balanced" style="color: var(--brand-gold); margin-right: 0.35rem;"></i> 1. Legal Integrity
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 0;">
                        We uphold strict adherence to the Advocates Act, 1961 and Bar Council of India regulations against advertisement and solicitation.
                    </p>
                </div>

                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; background: var(--bg-card);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; font-size: 1rem;">
                        <i class="fas fa-shield-halved" style="color: var(--brand-red); margin-right: 0.35rem;"></i> 2. Truth in Verification
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 0;">
                        Every claimed profile undergoes structured verification against public roll records, OTP verification, and certificate validation.
                    </p>
                </div>

                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; background: var(--bg-card);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; font-size: 1rem;">
                        <i class="fas fa-universal-access" style="color: #2563eb; margin-right: 0.35rem;"></i> 3. Universal Access
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 0;">
                        Bare Acts, court directories, and legal search capabilities remain openly accessible to all Indian citizens without paywalls.
                    </p>
                </div>

                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; background: var(--bg-card);">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; font-size: 1rem;">
                        <i class="fas fa-lightbulb" style="color: #16a34a; margin-right: 0.35rem;"></i> 4. Continuous Innovation
                    </div>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 0;">
                        We consistently adopt state-of-the-art web performance, high security standards, and responsive design for seamless utility.
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Call to Action -->
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: var(--radius-lg); padding: 2.5rem 2rem; color: #ffffff; text-align: center; box-shadow: var(--shadow-md);">
            <h2 style="font-size: 1.6rem; font-weight: 800; color: #ffffff; margin-bottom: 0.75rem;">
                Are You a Practicing Advocate in India?
            </h2>
            <p style="font-size: 1rem; color: #94a3b8; max-width: 600px; margin: 0 auto 1.5rem; line-height: 1.6;">
                Claim your official profile today, verify your credentials, and establish your authenticated presence in India's legal directory.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="claim-profile" class="btn btn-primary btn-lg">
                    <i class="fas fa-id-badge"></i> Claim Your Profile
                </a>
                <a href="advocates" class="btn btn-outline-white btn-lg" style="border: 1px solid rgba(255,255,255,0.3); color: #fff;">
                    <i class="fas fa-search"></i> Search Advocate Directory
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
