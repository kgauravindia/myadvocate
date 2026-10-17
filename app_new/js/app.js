/**
 * MyAdvocate Mobile Web Application (iOS & Android)
 * Connected to Unified API: https://myadv.in/api/api.php
 */

// API Configuration
const API_CONFIG = {
  token: 'a9dcc62d6078b881d318aca0ead65ab5',
  liveEndpoint: 'https://myadv.in/api/api.php',
  localEndpoint: '../api/api.php',
  fileUploadUrl: 'https://myadv.in/upload/'
};

// Check if running on localhost or custom domain
const isLocalEnv = window.location.hostname === 'localhost' || 
                   window.location.hostname === '127.0.0.1' || 
                   window.location.hostname.endsWith('.test');

// Active API Base URL (auto-select local for local development, or user preference)
let savedApiPreference = localStorage.getItem('myadv_api_endpoint');
let BASE_API_URL = savedApiPreference || (isLocalEnv ? API_CONFIG.localEndpoint : API_CONFIG.liveEndpoint);

// Application State
const AppState = {
  currentTab: 'home',
  advocates: [],
  acts: [],
  barCouncils: [],
  barAssociations: [],
  exams: [],
  notices: [],
  user: JSON.parse(localStorage.getItem('myadv_user') || 'null'),
  theme: localStorage.getItem('myadv_theme') || 'dark',
  filterState: {
    advType: 'ALL',
    searchQuery: '',
    searchMode: 'name'
  }
};

/**
 * Universal API Request Handler
 */
async function apiRequest(task, params = {}) {
  try {
    const url = new URL(BASE_API_URL, window.location.href);
    url.searchParams.set('token', API_CONFIG.token);
    url.searchParams.set('task', task);

    const formData = new FormData();
    for (const [key, value] of Object.entries(params)) {
      formData.append(key, value);
    }

    const response = await fetch(url.toString(), {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      throw new Error(`HTTP Error ${response.status}`);
    }

    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.warn("API returned non-JSON response:", text);
      return { status: 'error', raw: text };
    }
  } catch (error) {
    console.error(`API Error on task=${task}:`, error);
    // If live endpoint failed and we're local, try fallback to local endpoint
    if (BASE_API_URL === API_CONFIG.liveEndpoint && isLocalEnv) {
      console.log("Switching to local endpoint fallback...");
      BASE_API_URL = API_CONFIG.localEndpoint;
      return apiRequest(task, params);
    }
    return { status: 'error', msg: error.message };
  }
}

/**
 * UI Toast Notification System
 */
function showToast(message, duration = 3000) {
  const toast = document.getElementById('toast');
  const toastText = document.getElementById('toast-text');
  if (!toast || !toastText) return;

  toastText.textContent = message;
  toast.classList.add('show');

  setTimeout(() => {
    toast.classList.remove('show');
  }, duration);
}

/**
 * Bottom Sheet Modal Controller
 */
function openBottomSheet(sheetId) {
  closeAllBottomSheets();
  const backdrop = document.getElementById('sheet-backdrop');
  const sheet = document.getElementById(sheetId);
  if (backdrop && sheet) {
    backdrop.classList.add('active');
    sheet.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeAllBottomSheets() {
  const backdrop = document.getElementById('sheet-backdrop');
  if (backdrop) backdrop.classList.remove('active');
  document.querySelectorAll('.bottom-sheet').forEach(sheet => {
    sheet.classList.remove('active');
  });
  document.body.style.overflow = '';
}

/**
 * Tab Navigation Router
 */
function switchTab(tabName) {
  AppState.currentTab = tabName;

  // Update Bottom Nav Items
  document.querySelectorAll('.nav-tab-item').forEach(item => {
    item.classList.toggle('active', item.dataset.tab === tabName);
  });

  // Update View Containers
  document.querySelectorAll('.view-screen').forEach(screen => {
    screen.classList.toggle('active', screen.id === `view-${tabName}`);
  });

  // Scroll to top
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Load Tab Data if needed
  if (tabName === 'advocates' && AppState.advocates.length === 0) {
    loadAdvocates();
  } else if (tabName === 'acts' && AppState.acts.length === 0) {
    loadBareActs();
  } else if (tabName === 'institutions' && AppState.barCouncils.length === 0) {
    loadInstitutions();
  } else if (tabName === 'profile') {
    renderProfileView();
  }
}

/**
 * Theme Manager
 */
function setTheme(theme) {
  AppState.theme = theme;
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('myadv_theme', theme);

  const themeIcon = document.getElementById('theme-toggle-icon');
  if (themeIcon) {
    themeIcon.innerHTML = theme === 'dark' 
      ? '<path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>'
      : '<path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" fill="currentColor"/>';
  }
}

function toggleTheme() {
  const nextTheme = AppState.theme === 'dark' ? 'light' : 'dark';
  setTheme(nextTheme);
  showToast(`Switched to ${nextTheme.toUpperCase()} mode`);
}

/**
 * Fetch and Display Notifications Count
 */
async function fetchNotifications() {
  const res = await apiRequest('notification');
  const badge = document.getElementById('notif-badge');
  if (res && res.total !== undefined && badge) {
    badge.textContent = res.total;
    badge.style.display = res.total > 0 ? 'flex' : 'none';
  }
}

/**
 * Fetch and Display Latest Notice on Home Screen
 */
async function fetchLatestNotices() {
  const res = await apiRequest('notice_info');
  if (res && res.data && res.data.length > 0) {
    AppState.notices = res.data;
    const banner = document.getElementById('home-notice-text');
    if (banner) {
      banner.textContent = res.data[0].title || res.data[0].name || "New legal updates available";
    }
  }
}

/**
 * Advocates Controller
 */
async function loadAdvocates(query = '', searchType = 'name') {
  const container = document.getElementById('advocates-list');
  if (!container) return;

  container.innerHTML = `
    <div class="skeleton skeleton-card"></div>
    <div class="skeleton skeleton-card"></div>
    <div class="skeleton skeleton-card"></div>
  `;

  let task = 'search_advocate';
  let paramObj = { name: query };

  if (searchType === 'mobile') {
    task = 'search_advocate_mobile';
    paramObj = { mobile: query };
  } else if (searchType === 'pa') {
    task = 'search_advocate_pa';
    paramObj = { pa: query };
  } else if (searchType === 'eno') {
    task = 'search_advocate_eno';
    paramObj = { e_no: query };
  }

  const res = await apiRequest(task, paramObj);
  if (res && res.data) {
    AppState.advocates = res.data;
    renderAdvocatesList(res.data);
  } else {
    container.innerHTML = `
      <div class="empty-state">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
        <div class="empty-state-title">No Advocates Found</div>
        <p>Try searching with another name, practice area or mobile.</p>
      </div>
    `;
  }
}

function renderAdvocatesList(advocates) {
  const container = document.getElementById('advocates-list');
  if (!container) return;

  // Apply status filter
  let filtered = advocates;
  if (AppState.filterState.advType !== 'ALL') {
    filtered = advocates.filter(a => (a.type || '').toUpperCase() === AppState.filterState.advType);
  }

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-title">No Advocates Found</div>
        <p>No lawyers match the selected filter category.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(adv => {
    const initials = (adv.name || 'Advocate').trim().split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    const type = (adv.type || 'ACTIVE').toLowerCase();
    const typeLabel = type.toUpperCase();
    const city = adv.district_name || adv.court || 'Bar Member';
    const state = adv.state_name || '';
    const location = [city, state].filter(Boolean).join(', ');
    const pa = adv.practice_area && adv.practice_area !== 'Array' ? adv.practice_area.split(',').slice(0, 2) : ['Civil & Criminal'];

    return `
      <div class="advocate-card" onclick="viewAdvocateDetails('${adv.id}')">
        <div class="adv-top-row">
          <div class="adv-avatar">
            ${adv.photo ? `<img src="${API_CONFIG.fileUploadUrl}${adv.photo}" alt="${adv.name}">` : initials}
          </div>
          <div class="adv-info-main">
            <div class="adv-name-wrap">
              <span class="adv-name">${adv.name || 'Advocate'}</span>
              <span class="status-pill ${type}">${typeLabel}</span>
            </div>
            <div class="adv-meta">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
              <span>${location}</span>
            </div>
          </div>
        </div>
        
        <div class="adv-pa-tags">
          ${pa.map(p => `<span class="pa-tag">${p.trim()}</span>`).join('')}
        </div>

        <div class="adv-bottom-actions" onclick="event.stopPropagation()">
          <span class="adv-enrolment">Enr: ${adv.e_no || 'Registered'}</span>
          <div class="action-buttons-group">
            ${adv.mobile ? `
              <a href="tel:${adv.mobile}" class="btn-sm-action btn-sm-outline">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.26-.26.35-.65.24-1.01A11.36 11.36 0 018.96 4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.5c0-.55-.45-1-1-.12z"/></svg>
                Call
              </a>
              <a href="https://wa.me/91${adv.mobile}" target="_blank" class="btn-sm-action btn-sm-primary">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/></svg>
                WhatsApp
              </a>
            ` : ''}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function viewAdvocateDetails(advId) {
  const adv = AppState.advocates.find(a => String(a.id) === String(advId));
  if (!adv) return;

  const detailBody = document.getElementById('adv-detail-content');
  if (!detailBody) return;

  const initials = (adv.name || 'Advocate').trim().split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

  detailBody.innerHTML = `
    <div style="text-align: center; margin-bottom: 20px;">
      <div class="adv-avatar" style="width: 76px; height: 76px; margin: 0 auto 12px auto; font-size: 28px; border-radius: 20px;">
        ${adv.photo ? `<img src="${API_CONFIG.fileUploadUrl}${adv.photo}" alt="${adv.name}">` : initials}
      </div>
      <h3 style="font-family: var(--font-display); font-size: 20px; font-weight: 700;">${adv.name}</h3>
      <span class="status-pill ${(adv.type || 'ACTIVE').toLowerCase()}" style="margin-top: 4px; display: inline-block;">
        ${(adv.type || 'ACTIVE').toUpperCase()}
      </span>
      <p style="color: var(--text-secondary); font-size: 13px; margin-top: 6px;">
        ${adv.district_name || ''} ${adv.state_name ? `, ${adv.state_name}` : ''}
      </p>
    </div>

    <div style="background: var(--bg-card); border-radius: var(--radius-lg); padding: 16px; margin-bottom: 16px; border: 1px solid var(--border-subtle);">
      <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px;">
        <span style="color: var(--text-muted);">Enrollment No:</span>
        <span style="font-weight: 600;">${adv.e_no || 'Registered with State Bar'}</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px;">
        <span style="color: var(--text-muted);">Bar Council:</span>
        <span style="font-weight: 600;">${adv.bc_name || 'State Bar Council'}</span>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px;">
        <span style="color: var(--text-muted);">Practicing Court:</span>
        <span style="font-weight: 600;">${adv.court || 'District & Sessions Court'}</span>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 13px;">
        <span style="color: var(--text-muted);">Languages:</span>
        <span style="font-weight: 600;">${adv.languages || 'Hindi, English'}</span>
      </div>
    </div>

    <div style="margin-bottom: 20px;">
      <h4 style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">Practice Areas</h4>
      <div class="adv-pa-tags">
        ${(adv.practice_area || 'Civil, Criminal, Corporate, Matrimonial').split(',').map(pa => `<span class="pa-tag" style="padding: 4px 12px; font-size: 12px;">${pa.trim()}</span>`).join('')}
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
      <a href="tel:${adv.mobile}" class="btn-primary-block" style="text-decoration: none; margin: 0; background: var(--bg-card); border: 1px solid var(--border-subtle); color: var(--text-main);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.26-.26.35-.65.24-1.01A11.36 11.36 0 018.96 4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.5c0-.55-.45-1-1-.12z"/></svg>
        Call Advocate
      </a>
      <a href="https://wa.me/91${adv.mobile}?text=Hello%20${encodeURIComponent(adv.name)}%2C%20found%20your%20profile%20on%20MyAdvocate" target="_blank" class="btn-primary-block" style="text-decoration: none; margin: 0;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2z"/></svg>
        WhatsApp
      </a>
    </div>
  `;

  openBottomSheet('sheet-adv-detail');
}

/**
 * Bare Acts Controller
 */
async function loadBareActs(query = '') {
  const container = document.getElementById('acts-list');
  if (!container) return;

  if (AppState.acts.length === 0) {
    container.innerHTML = `
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
      <div class="skeleton skeleton-card"></div>
    `;

    const res = await apiRequest('acts_info');
    if (res && res.data) {
      AppState.acts = res.data;
    }
  }

  let filtered = AppState.acts;
  if (query) {
    const q = query.toLowerCase();
    filtered = AppState.acts.filter(act => 
      (act.name && act.name.toLowerCase().includes(q)) || 
      (act.year && act.year.includes(q))
    );
  }

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-title">No Bare Acts Found</div>
        <p>Try searching with another Act name or year.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(act => {
    return `
      <div class="act-card">
        <div class="act-title-row">
          <h4 class="act-title">${act.name}</h4>
          ${act.year ? `<span class="act-year-badge">${act.year}</span>` : ''}
        </div>
        <div class="act-downloads-row">
          ${act.english ? `
            <a href="${act.english}" target="_blank" class="btn-download-pdf english">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
              English PDF
            </a>
          ` : ''}
          ${act.hindi ? `
            <a href="${act.hindi}" target="_blank" class="btn-download-pdf hindi">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
              Hindi PDF
            </a>
          ` : ''}
          ${act.official ? `
            <a href="${act.official}" target="_blank" class="btn-download-pdf" style="flex: 0 0 42px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            </a>
          ` : ''}
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Institutions Controller (Bar Councils & Bar Associations)
 */
async function loadInstitutions() {
  const container = document.getElementById('institutions-list');
  if (!container) return;

  container.innerHTML = `
    <div class="skeleton skeleton-card"></div>
    <div class="skeleton skeleton-card"></div>
  `;

  const [bcRes, baRes] = await Promise.all([
    apiRequest('bc_all_list'),
    apiRequest('ba_all_list')
  ]);

  if (bcRes && bcRes.data) AppState.barCouncils = bcRes.data;
  if (baRes && baRes.data) AppState.barAssociations = baRes.data;

  renderInstitutionsList('BC');
}

function renderInstitutionsList(type = 'BC') {
  const container = document.getElementById('institutions-list');
  if (!container) return;

  const list = type === 'BC' ? AppState.barCouncils : AppState.barAssociations;

  if (list.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-title">No Institutions Found</div>
      </div>
    `;
    return;
  }

  container.innerHTML = list.map(item => `
    <div class="advocate-card">
      <div class="adv-name-wrap">
        <h4 class="adv-name" style="white-space: normal;">${item.name}</h4>
      </div>
      <p style="font-size: 12.5px; color: var(--text-secondary); margin-top: 4px;">
        ${item.address || 'State Bar Headquarters'}
      </p>
      ${item.phone || item.mobile || item.email ? `
        <div class="adv-bottom-actions">
          <span style="font-size: 11.5px; color: var(--text-muted);">${item.email || item.phone || ''}</span>
          ${item.phone || item.mobile ? `
            <a href="tel:${item.phone || item.mobile}" class="btn-sm-action btn-sm-primary">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.44-5.15-3.75-6.59-6.59l1.97-1.57c.26-.26.35-.65.24-1.01A11.36 11.36 0 018.96 4c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1 0 9.39 7.61 17 17 17 .55 0 1-.45 1-1v-3.5c0-.55-.45-1-1-.12z"/></svg>
              Contact
            </a>
          ` : ''}
        </div>
      ` : ''}
    </div>
  `).join('');
}

/**
 * IFSC & Bank Code Lookup
 */
async function searchIfsc() {
  const code = (document.getElementById('ifsc-input')?.value || '').trim().toUpperCase();
  const resContainer = document.getElementById('ifsc-results');
  if (!code || !resContainer) return;

  resContainer.innerHTML = '<div class="skeleton skeleton-card"></div>';
  const res = await apiRequest('search_ifsc', { ifsc: code });

  if (res && res.data && res.data.length > 0) {
    const bank = res.data[0];
    resContainer.innerHTML = `
      <div class="advocate-card" style="margin-top: 14px;">
        <h4 style="font-weight: 700; color: var(--text-main);">${bank.bank_name || 'Bank Details'}</h4>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">Branch: <strong>${bank.branch || ''}</strong></p>
        <p style="font-size: 13px; color: var(--text-secondary);">IFSC: <strong style="color: var(--primary);">${bank.ifsc}</strong></p>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">${bank.address || ''}</p>
      </div>
    `;
  } else {
    resContainer.innerHTML = '<div class="empty-state"><div class="empty-state-title">IFSC Code Not Found</div></div>';
  }
}

/**
 * HSN / SAC Code Lookup
 */
async function searchHsn() {
  const code = (document.getElementById('hsn-input')?.value || '').trim();
  const resContainer = document.getElementById('hsn-results');
  if (!code || !resContainer) return;

  resContainer.innerHTML = '<div class="skeleton skeleton-card"></div>';
  const res = await apiRequest('search_hsnsac', { hsnsaccode: code });

  if (res && res.data && res.data.length > 0) {
    resContainer.innerHTML = res.data.map(item => `
      <div class="advocate-card" style="margin-top: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <h4 style="font-weight: 700; color: var(--primary);">${item.code}</h4>
          <span class="status-pill active">${item.rate ? item.rate + '% GST' : 'GST CODE'}</span>
        </div>
        <p style="font-size: 13px; color: var(--text-secondary); margin-top: 6px;">${item.description || ''}</p>
      </div>
    `).join('');
  } else {
    resContainer.innerHTML = '<div class="empty-state"><div class="empty-state-title">No HSN / SAC Code Found</div></div>';
  }
}

/**
 * AIBE Exams & Question Papers
 */
async function loadAibeHub() {
  openBottomSheet('sheet-aibe-hub');
  const examContainer = document.getElementById('aibe-exams-list');
  if (!examContainer) return;

  examContainer.innerHTML = '<div class="skeleton skeleton-card"></div>';
  const res = await apiRequest('exam_list');

  if (res && res.data && res.data.length > 0) {
    examContainer.innerHTML = res.data.map(exam => `
      <div class="advocate-card" style="margin-bottom: 10px;">
        <h4 style="font-weight: 700; color: var(--text-main);">${exam.name}</h4>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Date: ${exam.exam_date || 'Annual AIBE'}</p>
        <button class="btn-sm-action btn-sm-primary" style="margin-top: 8px; width: fit-content;" onclick="loadQuestionsForExam('${exam.id}', '${exam.name}')">
          View Questions & Syllabus
        </button>
      </div>
    `).join('');
  } else {
    examContainer.innerHTML = '<div class="empty-state"><div class="empty-state-title">No Exams Listed</div></div>';
  }
}

async function loadQuestionsForExam(examId, examName) {
  const examContainer = document.getElementById('aibe-exams-list');
  examContainer.innerHTML = `<div class="skeleton skeleton-card"></div>`;

  const res = await apiRequest('question_list_exam', { exam_id: examId });
  if (res && res.data && res.data.length > 0) {
    examContainer.innerHTML = `
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <h4 style="font-size: 14px; font-weight: 700;">${examName} (${res.data.length} Questions)</h4>
        <button class="btn-sm-action btn-sm-outline" onclick="loadAibeHub()">Back</button>
      </div>
      ${res.data.map((q, idx) => {
        const questionText = q.details || q.question || 'Legal Objective Question';
        const optA = q.option_a || q.opt1 || '';
        const optB = q.option_b || q.opt2 || '';
        const optC = q.option_c || q.opt3 || '';
        const optD = q.option_d || q.opt4 || '';
        const answer = q.ans || q.answer || 'A';
        const exp = q.answer_description || q.question_description || '';

        return `
          <div class="advocate-card" style="margin-bottom: 12px;">
            <p style="font-weight: 600; font-size: 13.5px; margin-bottom: 8px;">Q${idx + 1}. ${questionText}</p>
            <div style="font-size: 12.5px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 4px;">
              ${optA ? `<div>A) ${optA}</div>` : ''}
              ${optB ? `<div>B) ${optB}</div>` : ''}
              ${optC ? `<div>C) ${optC}</div>` : ''}
              ${optD ? `<div>D) ${optD}</div>` : ''}
            </div>
            <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed var(--border-subtle); color: var(--accent-emerald); font-size: 12px; font-weight: 600;">
              Correct Answer: <strong>Option ${answer}</strong>
            </div>
            ${exp ? `<div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">${exp}</div>` : ''}
          </div>
        `;
      }).join('')}
    `;
  } else {
    examContainer.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-title">No Questions Added Yet</div>
        <button class="btn-sm-action btn-sm-outline" style="margin-top: 10px;" onclick="loadAibeHub()">Back</button>
      </div>
    `;
  }
}

/**
 * Profile & Authentication View
 */
function renderProfileView() {
  const container = document.getElementById('profile-content-wrap');
  if (!container) return;

  if (AppState.user) {
    container.innerHTML = `
      <div style="text-align: center; margin-bottom: 24px;">
        <div class="adv-avatar" style="width: 80px; height: 80px; margin: 0 auto 12px auto; font-size: 32px; border-radius: 24px;">
          ${AppState.user.name ? AppState.user.name[0].toUpperCase() : 'U'}
        </div>
        <h3 style="font-family: var(--font-display); font-size: 20px; font-weight: 700;">${AppState.user.name}</h3>
        <p style="color: var(--text-secondary); font-size: 13px;">${AppState.user.mobile} &bull; ${AppState.user.user_type || 'Member'}</p>
      </div>

      <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 20px;">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="toggleTheme()">
          <span>Appearance Mode</span>
          <span style="font-weight: 600; color: var(--primary); text-transform: uppercase;">${AppState.theme}</span>
        </div>
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="openBottomSheet('sheet-settings')">
          <span>API Connection Server</span>
          <span style="font-size: 12px; color: var(--text-muted);">${BASE_API_URL.includes('localhost') ? 'Local API' : 'myadv.in (Live)'}</span>
        </div>
        <div style="padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #EF4444;" onclick="logoutUser()">
          <span style="font-weight: 600;">Sign Out</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
        </div>
      </div>
    `;
  } else {
    container.innerHTML = `
      <div style="text-align: center; padding: 24px 0 32px 0;">
        <div style="width: 72px; height: 72px; border-radius: 20px; background: rgba(201, 0, 22, 0.1); border: 1px solid var(--border-active); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; color: var(--primary);">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <h3 style="font-family: var(--font-display); font-size: 20px; font-weight: 700; margin-bottom: 6px;">Sign in to My Advocate</h3>
        <p style="font-size: 13px; color: var(--text-secondary); max-width: 280px; margin: 0 auto 20px auto;">
          Access verified legal directories, case trackers, court forms, and sync with your advocate e-diary.
        </p>
        <button class="btn-primary-block" onclick="openBottomSheet('sheet-auth')" style="max-width: 280px; margin: 0 auto;">
          Sign In with OTP
        </button>
      </div>

      <div style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 20px;">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="toggleTheme()">
          <span>Display Mode</span>
          <span style="font-weight: 600; color: var(--primary); text-transform: uppercase;">${AppState.theme}</span>
        </div>
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="openBottomSheet('sheet-settings')">
          <span>API Gateway</span>
          <span style="font-size: 12px; color: var(--text-muted);">${BASE_API_URL.includes('localhost') ? 'Local (Laragon)' : 'Live (myadv.in)'}</span>
        </div>
        <div style="padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; cursor: pointer;" onclick="shareApp()">
          <span>Share App with Friends</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92c0-1.61-1.31-2.92-2.92-2.92z"/></svg>
        </div>
      </div>
    `;
  }
}

/**
 * Authentication (Login with OTP & Password)
 */
let currentOtpMobile = '';

function switchAuthMode(mode) {
  const isOtp = mode === 'otp';
  document.getElementById('tab-auth-otp')?.classList.toggle('active', isOtp);
  document.getElementById('tab-auth-pass')?.classList.toggle('active', !isOtp);
  const otpContainer = document.getElementById('auth-otp-container');
  const passContainer = document.getElementById('auth-pass-container');
  const title = document.getElementById('auth-sheet-title');
  if (otpContainer) otpContainer.style.display = isOtp ? 'block' : 'none';
  if (passContainer) passContainer.style.display = isOtp ? 'none' : 'block';
  if (title) title.textContent = isOtp ? 'Sign In with OTP' : 'Sign In with Password';
}

function resetOtpStep() {
  const step1 = document.getElementById('otp-step-1');
  const step2 = document.getElementById('otp-step-2');
  if (step1) step1.style.display = 'block';
  if (step2) step2.style.display = 'none';
  const codeInput = document.getElementById('otp-code-input');
  if (codeInput) codeInput.value = '';
}

async function handleSendOtp(isResend = false) {
  const mobileInput = document.getElementById('otp-mobile-input');
  const mobile = (isResend ? currentOtpMobile : (mobileInput?.value || '')).trim().replace(/\D/g, '');

  if (mobile.length !== 10) {
    showToast("Please enter a valid 10-digit mobile number");
    return;
  }

  currentOtpMobile = mobile;
  showToast(isResend ? "Resending OTP..." : "Sending OTP...");

  const res = await apiRequest('send_login_otp', { mobile });
  if (res && res.status === 'success') {
    const step1 = document.getElementById('otp-step-1');
    const step2 = document.getElementById('otp-step-2');
    if (step1) step1.style.display = 'none';
    if (step2) step2.style.display = 'block';

    const sentInfo = document.getElementById('otp-sent-info');
    if (sentInfo) sentInfo.textContent = `OTP sent to ${res.mobile_masked || '+91 ' + mobile}`;
    
    // Auto-fill in dev/test for instantaneous seamless test
    if (res.otp) {
      const codeInput = document.getElementById('otp-code-input');
      if (codeInput) codeInput.value = res.otp;
      showToast(`OTP Sent! (Code: ${res.otp})`, 4000);
    } else {
      showToast("OTP sent to your mobile!", 3000);
    }
  } else {
    showToast(res.msg || "Failed to send OTP. Number might not be registered.");
  }
}

async function handleVerifyOtp() {
  const otp = (document.getElementById('otp-code-input')?.value || '').trim();
  if (otp.length < 4) {
    showToast("Please enter the verification OTP");
    return;
  }

  showToast("Verifying code...");
  const res = await apiRequest('verify_login_otp', {
    mobile: currentOtpMobile,
    otp: otp
  });

  if (res && res.status === 'success' && res.data && res.data.length > 0) {
    AppState.user = res.data[0];
    localStorage.setItem('myadv_user', JSON.stringify(AppState.user));
    closeAllBottomSheets();
    resetOtpStep();
    showToast(`Welcome back, ${AppState.user.name || 'Advocate'}!`);
    renderProfileView();
  } else {
    showToast(res.msg || "Invalid OTP code. Please try again.");
  }
}

async function submitLogin(e) {
  e.preventDefault();
  const mobile = document.getElementById('login-mobile').value.trim();
  const pass = document.getElementById('login-password').value.trim();

  if (!mobile || !pass) {
    showToast("Please enter mobile and password");
    return;
  }

  showToast("Signing in...");
  const res = await apiRequest('login_user', {
    user_name: mobile,
    user_pass: pass
  });

  if (res && res.data && res.data.length > 0) {
    AppState.user = res.data[0];
    localStorage.setItem('myadv_user', JSON.stringify(AppState.user));
    closeAllBottomSheets();
    showToast(`Welcome back, ${AppState.user.name}!`);
    renderProfileView();
  } else {
    showToast(res.msg || "Invalid mobile number or password");
  }
}

function logoutUser() {
  AppState.user = null;
  localStorage.removeItem('myadv_user');
  showToast("You have been signed out");
  renderProfileView();
}

/**
 * Native Share
 */
function shareApp() {
  if (navigator.share) {
    navigator.share({
      title: 'My Advocate India',
      text: 'Search Verified Advocates, Bare Acts, Bar Councils and Legal Tools',
      url: window.location.href
    }).catch(() => {});
  } else {
    navigator.clipboard.writeText(window.location.href);
    showToast("App URL copied to clipboard!");
  }
}

/**
 * Change API Endpoint
 */
function changeApiServer(type) {
  if (type === 'live') {
    BASE_API_URL = API_CONFIG.liveEndpoint;
  } else {
    BASE_API_URL = API_CONFIG.localEndpoint;
  }
  localStorage.setItem('myadv_api_endpoint', BASE_API_URL);
  closeAllBottomSheets();
  showToast(`Connected to: ${BASE_API_URL}`);
  
  // Refresh current view
  if (AppState.currentTab === 'advocates') loadAdvocates();
  if (AppState.currentTab === 'acts') loadBareActs();
  fetchNotifications();
  renderProfileView();
}

/**
 * App Initialization
 */
document.addEventListener('DOMContentLoaded', () => {
  // Apply saved theme
  setTheme(AppState.theme);

  // Initialize notifications & notices
  fetchNotifications();
  fetchLatestNotices();

  // Search Input Debounce for Advocates
  const advSearchInput = document.getElementById('adv-search-input');
  let debounceTimeout = null;
  if (advSearchInput) {
    advSearchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        loadAdvocates(e.target.value.trim(), AppState.filterState.searchMode);
      }, 400);
    });
  }

  // Bare Acts Live Filter
  const actSearchInput = document.getElementById('act-search-input');
  if (actSearchInput) {
    actSearchInput.addEventListener('input', (e) => {
      loadBareActs(e.target.value.trim());
    });
  }

  // Filter Chips Click Listeners
  document.querySelectorAll('.filter-chips-row .chip-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const parent = btn.parentElement;
      parent.querySelectorAll('.chip-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      if (btn.dataset.filterType) {
        AppState.filterState.advType = btn.dataset.filterType;
        renderAdvocatesList(AppState.advocates);
      } else if (btn.dataset.instType) {
        renderInstitutionsList(btn.dataset.instType);
      }
    });
  });

  // Carousel Swiping Dots Auto-update
  const carousel = document.querySelector('.carousel-slides');
  if (carousel) {
    carousel.addEventListener('scroll', () => {
      const index = Math.round(carousel.scrollLeft / carousel.offsetWidth);
      document.querySelectorAll('.carousel-dots .dot').forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
      });
    });
  }

  // Register PWA Service Worker if supported
  if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    navigator.serviceWorker.register('./sw.js').catch(() => {});
  }
});
