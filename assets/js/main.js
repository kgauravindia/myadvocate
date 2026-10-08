// assets/js/main.js - Interactive features for My Advocate
document.addEventListener('DOMContentLoaded', () => {
    // 1. Public Mobile Navigation Drawer & Backdrop
    const mobileToggle = document.getElementById('mobileToggle') || document.querySelector('.mobile-toggle');
    const navMenu = document.getElementById('navMenu') || document.querySelector('.nav-menu');
    const navCloseBtn = document.getElementById('navCloseBtn');
    const mobileBackdrop = document.getElementById('mobileBackdrop');

    function openMobileMenu() {
        if (navMenu) navMenu.classList.add('mobile-open');
        if (mobileBackdrop) mobileBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        if (navMenu) navMenu.classList.remove('mobile-open');
        if (mobileBackdrop) mobileBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (navMenu && navMenu.classList.contains('mobile-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });
    }

    if (navCloseBtn) {
        navCloseBtn.addEventListener('click', closeMobileMenu);
    }

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', closeMobileMenu);
    }

    if (navMenu) {
        navMenu.querySelectorAll('.nav-item > a').forEach(parentLink => {
            parentLink.addEventListener('click', (e) => {
                if (window.innerWidth < 992) {
                    const dropdown = parentLink.nextElementSibling;
                    if (dropdown && (dropdown.classList.contains('nav-dropdown') || dropdown.classList.contains('dropdown-mega'))) {
                        e.preventDefault();
                        const isOpen = dropdown.style.display === 'block';
                        // Close other open dropdowns
                        navMenu.querySelectorAll('.nav-dropdown, .dropdown-mega').forEach(d => {
                            d.style.display = 'none';
                        });
                        dropdown.style.display = isOpen ? 'none' : 'block';
                        const caret = parentLink.querySelector('.nav-caret');
                        if (caret) {
                            caret.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                        }
                    } else {
                        closeMobileMenu();
                    }
                }
            });
        });

        navMenu.querySelectorAll('.dropdown-link, .nav-menu-footer a').forEach(childLink => {
            childLink.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    closeMobileMenu();
                }
            });
        });
    }

    // 2. Admin Mobile Sidebar Drawer
    const adminSidebarToggle = document.getElementById('adminSidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const adminSidebarClose = document.getElementById('adminSidebarClose');
    const adminMobileBackdrop = document.getElementById('adminMobileBackdrop');

    function openAdminSidebar() {
        if (adminSidebar) adminSidebar.classList.add('mobile-open');
        if (adminMobileBackdrop) adminMobileBackdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAdminSidebar() {
        if (adminSidebar) adminSidebar.classList.remove('mobile-open');
        if (adminMobileBackdrop) adminMobileBackdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (adminSidebarToggle) {
        adminSidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            if (adminSidebar && adminSidebar.classList.contains('mobile-open')) {
                closeAdminSidebar();
            } else {
                openAdminSidebar();
            }
        });
    }

    if (adminSidebarClose) {
        adminSidebarClose.addEventListener('click', closeAdminSidebar);
    }

    if (adminMobileBackdrop) {
        adminMobileBackdrop.addEventListener('click', closeAdminSidebar);
    }

    // 3. Mobile Filter Drawer Toggle on Search Pages
    const mobileFilterToggle = document.querySelector('.mobile-filter-toggle');
    const filterSidebarContent = document.querySelector('.filter-sidebar-content');
    if (mobileFilterToggle && filterSidebarContent) {
        mobileFilterToggle.addEventListener('click', function() {
            filterSidebarContent.classList.toggle('open');
            const icon = this.querySelector('i');
            if (icon) {
                if (filterSidebarContent.classList.contains('open')) {
                    this.innerHTML = '<i class="fas fa-times"></i> Close Filters';
                } else {
                    this.innerHTML = '<i class="fas fa-sliders"></i> Filter Advocates';
                }
            }
        });
    }

    // 4. Dynamic State -> District Cascading Dropdowns
    const stateSelects = document.querySelectorAll('.state-cascade');
    stateSelects.forEach(stateSelect => {
        stateSelect.addEventListener('change', function() {
            const targetDistrict = document.querySelector(this.dataset.target || '#district_select');
            if (!targetDistrict) return;

            const stateCode = this.value;
            targetDistrict.innerHTML = '<option value="">Loading districts...</option>';

            if (!stateCode) {
                targetDistrict.innerHTML = '<option value="">All Districts</option>';
                return;
            }

            fetch(`api/get_districts.php?state=${encodeURIComponent(stateCode)}`)
                .then(res => res.json())
                .then(data => {
                    let html = '<option value="">All Districts</option>';
                    if (Array.isArray(data)) {
                        data.forEach(d => {
                            html += `<option value="${d.code}">${d.name}</option>`;
                        });
                    }
                    targetDistrict.innerHTML = html;
                })
                .catch(() => {
                    targetDistrict.innerHTML = '<option value="">All Districts</option>';
                });
        });
    });

    // 5. Act Section In-Page Search
    const actSearchInput = document.getElementById('actSectionSearch');
    if (actSearchInput) {
        actSearchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const sectionCards = document.querySelectorAll('.act-section-card');
            
            sectionCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 6. Interactive Legal Calculators
    // Court Fee Calculator
    const courtFeeForm = document.getElementById('courtFeeForm');
    if (courtFeeForm) {
        courtFeeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const claimAmount = parseFloat(document.getElementById('claimAmount').value) || 0;
            const suitType = document.getElementById('suitType').value;
            let fee = 0;

            if (suitType === 'money') {
                if (claimAmount <= 50000) fee = Math.max(200, claimAmount * 0.05);
                else if (claimAmount <= 500000) fee = 2500 + (claimAmount - 50000) * 0.035;
                else fee = 18250 + (claimAmount - 500000) * 0.02;
            } else if (suitType === 'partition') {
                fee = Math.min(5000, Math.max(500, claimAmount * 0.01));
            } else if (suitType === 'injunction') {
                fee = 250;
            } else if (suitType === 'declaration') {
                fee = 500;
            }

            const resBox = document.getElementById('courtFeeResult');
            if (resBox) {
                resBox.style.display = 'block';
                resBox.innerHTML = `
                    <div class="stat-box" style="background:#f0fdf4; border-color:#86efac;">
                        <div class="stat-label" style="color:#166534;">Estimated Court Fee</div>
                        <div class="stat-value" style="color:#15803d;">₹${Math.round(fee).toLocaleString('en-IN')}</div>
                        <small style="color:#166534; display:block; margin-top:0.35rem;">*Subject to specific State Court Fees Act schedule and local rules.</small>
                    </div>
                `;
            }
        });
    }

    // Limitation Calculator
    const limitationForm = document.getElementById('limitationForm');
    if (limitationForm) {
        limitationForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const causeDateStr = document.getElementById('causeDate').value;
            const articleDays = parseInt(document.getElementById('limitationArticle').value) || 0;
            
            if (!causeDateStr || !articleDays) return;

            const causeDate = new Date(causeDateStr);
            const expiryDate = new Date(causeDate);
            expiryDate.setDate(expiryDate.getDate() + articleDays);

            const resBox = document.getElementById('limitationResult');
            if (resBox) {
                resBox.style.display = 'block';
                const today = new Date();
                const isExpired = expiryDate < today;

                resBox.innerHTML = `
                    <div class="stat-box" style="background:${isExpired ? '#fef2f2' : '#fffbeb'}; border-color:${isExpired ? '#fca5a5' : '#fde68a'};">
                        <div class="stat-label" style="color:${isExpired ? '#991b1b' : '#92400e'};">Last Date to File (Limitation Expiry)</div>
                        <div class="stat-value" style="color:${isExpired ? '#c00000' : '#b45309'};">${expiryDate.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}</div>
                        <span class="badge-verification ${isExpired ? 'badge-basic' : 'badge-verified'}" style="margin-top:0.5rem;">
                            ${isExpired ? '⚠️ Limitation May Have Expired' : '✅ Within Limitation Period'}
                        </span>
                    </div>
                `;
            }
        });
    }

    // 7. Copy Link to Clipboard
    const copyBtns = document.querySelectorAll('.btn-copy-link');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const textToCopy = this.dataset.url || window.location.href;
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Link Copied!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
    });

    // 8. User Profile Header Dropdown (Click & Touch Toggle)
    const userDropdownWrapper = document.getElementById('userDropdownWrapper');
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    if (userDropdownBtn && userDropdownWrapper) {
        userDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdownWrapper.classList.toggle('active');
            const isExpanded = userDropdownWrapper.classList.contains('active');
            userDropdownBtn.setAttribute('aria-expanded', isExpanded);
        });

        document.addEventListener('click', (e) => {
            if (!userDropdownWrapper.contains(e.target)) {
                userDropdownWrapper.classList.remove('active');
                userDropdownBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 9. Night Mode / Dark Theme Switcher
    function initNightMode() {
        const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
        
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            toggleBtns.forEach(btn => {
                const label = btn.querySelector('.theme-toggle-label');
                if (label) {
                    label.textContent = theme === 'dark' ? 'Light Mode' : 'Night Mode';
                }
            });
        }

        const currentTheme = localStorage.getItem('theme') || 'light';
        applyTheme(currentTheme);

        toggleBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const newTheme = isDark ? 'light' : 'dark';
                applyTheme(newTheme);
            });
        });
    }
    initNightMode();

    // 10. First-Time BCI Compliance Disclaimer Popup
    function initBciModal() {
        const modal = document.getElementById('bciDisclaimerModal');
        const acceptBtn = document.getElementById('bciAcceptBtn');
        if (!modal || !acceptBtn) return;

        const STORAGE_KEY = 'bci_disclaimer_accepted_v1';
        const hasAccepted = localStorage.getItem(STORAGE_KEY);

        if (!hasAccepted) {
            // Show modal smoothly after brief delay
            setTimeout(() => {
                modal.style.display = 'flex';
                // Trigger reflow for animation
                void modal.offsetWidth;
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }, 350);
        }

        acceptBtn.addEventListener('click', () => {
            localStorage.setItem(STORAGE_KEY, 'true');
            // Also store in cookie for server-side if needed
            document.cookie = STORAGE_KEY + "=true; path=/; max-age=" + (365 * 24 * 60 * 60) + "; SameSite=Lax";
            modal.classList.remove('show');
            document.body.style.overflow = '';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        });
    }
    initBciModal();
});
