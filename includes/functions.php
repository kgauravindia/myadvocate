<?php
// includes/functions.php - Core Helper Functions

function sanitize($data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function getStates(): array {
    static $states = null;
    if ($states === null) {
        $db = getDB();
        try {
            $stmt = $db->query("SELECT code, name FROM state WHERE status = 'ACTIVE' ORDER BY name ASC");
            $states = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $states = [];
        }
    }
    return $states;
}

function getDistrictsByState(string $stateCode): array {
    $db = getDB();
    try {
        $stmt = $db->prepare("SELECT code, name FROM district WHERE state_code = ? AND status = 'ACTIVE' ORDER BY name ASC");
        $stmt->execute([$stateCode]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getBarAssociations(string $stateCode = '', string $districtCode = ''): array {
    $db = getDB();
    try {
        $sql = "SELECT code, name, district_code, state_code FROM ba WHERE status = 'ACTIVE'";
        $params = [];
        if (!empty($stateCode)) {
            $sql .= " AND state_code = ?";
            $params[] = $stateCode;
        }
        if (!empty($districtCode)) {
            $sql .= " AND district_code = ?";
            $params[] = $districtCode;
        }
        $sql .= " ORDER BY name ASC LIMIT 100";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getBarCouncils(): array {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT id, name, code, state_code FROM bc WHERE status = 'ACTIVE' ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getCourtsList(): array {
    return [
        'CC' => 'Civil & District Court',
        'HC' => 'High Court',
        'SC' => 'Supreme Court of India',
        'FC' => 'Family Court',
        'CJ' => 'Chief Judicial Magistrate Court',
        'DRT' => 'Debt Recovery Tribunal (DRT)',
        'NCLT' => 'National Company Law Tribunal (NCLT)',
        'CAT' => 'Central Administrative Tribunal (CAT)',
        'MACT' => 'Motor Accident Claims Tribunal (MACT)',
        'LAB' => 'Labour Court / Industrial Tribunal'
    ];
}

function getCourtName(?string $code): string {
    if (!$code) return 'Civil & District Court';
    $courts = getCourtsList();
    return $courts[strtoupper($code)] ?? $code;
}

function getStateName(?string $code): string {
    if (!$code) return '';
    $states = getStates();
    return $states[strtoupper($code)] ?? $code;
}

function getDistrictName(?string $code): string {
    if (!$code) return '';
    static $districts = [];
    if (!isset($districts[$code])) {
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT name FROM district WHERE code = ? AND status = 'ACTIVE' LIMIT 1");
            $stmt->execute([$code]);
            $res = $stmt->fetchColumn();
            $districts[$code] = $res ?: $code;
        } catch (Exception $e) {
            $districts[$code] = $code;
        }
    }
    return $districts[$code];
}

function getBarAssociationName(?string $code): string {
    if (!$code) return '';
    static $bas = [];
    if (!isset($bas[$code])) {
        $db = getDB();
        try {
            $stmt = $db->prepare("SELECT name FROM ba WHERE code = ? AND status = 'ACTIVE' LIMIT 1");
            $stmt->execute([$code]);
            $res = $stmt->fetchColumn();
            $bas[$code] = $res ?: 'Bar Association';
        } catch (Exception $e) {
            $bas[$code] = 'Bar Association';
        }
    }
    return $bas[$code];
}

function getVerificationBadge(array $advocate): array {
    $plan = strtolower($advocate['plan_type'] ?? 'basic');
    $status = strtolower($advocate['type'] ?? 'pending');
    $isClaimed = !empty($advocate['password']) || $status === 'active' || ($advocate['premium_member'] ?? 0) == 1;

    if ($plan === 'verified' || ($advocate['premium_member'] ?? 0) == 1) {
        return [
            'type' => 'verified',
            'label' => 'Verified Advocate',
            'badge_class' => 'badge-verified',
            'icon' => 'fa-certificate',
            'desc' => 'State Bar Council / Document Authenticated'
        ];
    } elseif ($isClaimed || $plan === 'registered') {
        return [
            'type' => 'registered',
            'label' => 'Registered Profile',
            'badge_class' => 'badge-registered',
            'icon' => 'fa-user-check',
            'desc' => 'Claimed & Managed by Advocate'
        ];
    } else {
        return [
            'type' => 'basic',
            'label' => 'Public Record',
            'badge_class' => 'badge-basic',
            'icon' => 'fa-id-card',
            'desc' => 'Indexed Public Directory Listing'
        ];
    }
}

function maskContactInfo(?string $info, string $visibility = 'PUBLIC', bool $isLoggedIn = false): string {
    if (empty($info)) return '';
    $vis = strtoupper(trim((string)$visibility));

    // If visibility is explicitly PUBLIC, or if the user is the profile owner viewing their own profile, show full value
    if ($vis === 'PUBLIC' || $isLoggedIn) {
        return $info;
    }

    // When visibility is NOT PUBLIC for public visitors:
    // 1. Email Masking (e.g. ad•••••@gmail.com)
    if (strpos($info, '@') !== false) {
        $parts = explode('@', $info, 2);
        $user = $parts[0];
        $domain = $parts[1] ?? '';
        $prefix = (strlen($user) > 2) ? substr($user, 0, 2) : substr($user, 0, 1);
        return $prefix . '•••••@' . $domain;
    }

    // 2. Phone / Mobile Masking (e.g. 987•••••10)
    $digits = preg_replace('/[^\d]/', '', $info);
    if (strlen($digits) >= 6) {
        return substr($digits, 0, 3) . '•••••' . substr($digits, -2);
    }

    // 3. Address / General Text Masking
    return 'Private / Confidential';
}

/**
 * Generate unique public_url for an advocate
 * Format: [state_code or district_code] + [name 4-chars] + [3-digit alphanumeric]
 * Example: 'brsarraje8k2' or 'brraje7x1'
 */
function generateAdvocatePublicUrl(string $name, ?string $stateCode = '', ?string $districtCode = '', ?PDO $db = null, ?int $excludeId = null): string {
    // 1. Location code (district_code if provided, else state_code, else default 'in')
    $loc = !empty(trim((string)$districtCode)) ? trim((string)$districtCode) : (!empty(trim((string)$stateCode)) ? trim((string)$stateCode) : 'in');
    $loc = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $loc));
    if (empty($loc)) {
        $loc = 'in';
    }

    // 2. Name code (strip common honorifics, keep alphanumeric, take first 4 chars)
    $cleanName = preg_replace('/^(advocate|adv\b|dr\b|shri\b|mr\b|mrs\b|ms\b|hon\b)\.?\s*/i', '', trim($name));
    $nameAlpha = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cleanName));
    if (empty($nameAlpha)) {
        $nameAlpha = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) ?: 'user';
    }
    $namePart = substr($nameAlpha, 0, 4);

    $base = $loc . $namePart;

    if ($db === null) {
        try {
            $db = getDB();
        } catch (Exception $e) {
            $db = null;
        }
    }

    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $charsLen = strlen($chars);

    // Try up to 30 times to generate a unique public_url
    for ($i = 0; $i < 30; $i++) {
        $rand3 = '';
        for ($j = 0; $j < 3; $j++) {
            $rand3 .= $chars[random_int(0, $charsLen - 1)];
        }
        $publicUrl = $base . $rand3;

        if ($db) {
            try {
                $sql = "SELECT id FROM advocate WHERE public_url = ?";
                $params = [$publicUrl];
                if ($excludeId) {
                    $sql .= " AND id != ?";
                    $params[] = $excludeId;
                }
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                if (!$stmt->fetch()) {
                    return $publicUrl;
                }
            } catch (Exception $e) {
                return $publicUrl;
            }
        } else {
            return $publicUrl;
        }
    }

    return $base . substr(bin2hex(random_bytes(3)), 0, 4);
}

function generateAdvocateSlug(array $adv): string {
    $name = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($adv['name'] ?? 'advocate')));
    $distCode = $adv['district_code'] ?? '';
    $distName = $distCode ? getDistrictName($distCode) : '';
    $district = $distName ? preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($distName))) : '';
    $year = !empty($adv['e_year']) ? trim($adv['e_year']) : '';
    
    $parts = array_filter(['advocate', $name, $district, $year]);
    return trim(implode('-', $parts), '-');
}

function getAdvocateUrl(array $adv): string {
    if (!empty($adv['public_url'])) {
        $handle = ltrim(trim($adv['public_url']), '@');
        return '@' . $handle;
    }
    $slug = generateAdvocateSlug($adv);
    $b64 = base64_encode('id=' . ($adv['id'] ?? 0) . '&name=' . ($adv['name'] ?? ''));
    return 'profile.php?link=' . urlencode($slug) . '&link=' . urlencode($b64);
}

function calculateAdvocateIndex(array $adv): array {
    $score = 0;
    $breakdown = [];

    // 1. Advocate Name (10%)
    $hasName = !empty(trim($adv['name'] ?? ''));
    $score += $hasName ? 10 : 0;
    $breakdown[] = [
        'key' => 'name',
        'title' => 'Advocate Name & Identity',
        'weight' => 10,
        'completed' => $hasName,
        'icon' => 'fa-user-check',
        'tip' => 'Verified legal advocate name.'
    ];

    // 2. Bar Enrollment Number & Year (20%)
    $hasEnr = !empty(trim($adv['e_no'] ?? '')) && !empty(trim($adv['e_year'] ?? ''));
    $score += $hasEnr ? 20 : 0;
    $breakdown[] = [
        'key' => 'enrollment',
        'title' => 'Bar Council Enrollment & Year',
        'weight' => 20,
        'completed' => $hasEnr,
        'icon' => 'fa-id-card',
        'tip' => 'State Bar Council enrollment registration number and year.'
    ];

    // 3. State Bar Council & District (10%)
    $hasStateDist = !empty(trim($adv['state_code'] ?? '')) && !empty(trim($adv['district_code'] ?? ''));
    $score += $hasStateDist ? 10 : 0;
    $breakdown[] = [
        'key' => 'location',
        'title' => 'State Bar Council & District',
        'weight' => 10,
        'completed' => $hasStateDist,
        'icon' => 'fa-location-dot',
        'tip' => 'Registered State Bar Council jurisdiction and district.'
    ];

    // 4. Practice Specializations & Court (20%)
    $hasPractice = !empty(trim($adv['practice_area'] ?? '')) && trim($adv['practice_area']) !== 'Array';
    $score += $hasPractice ? 20 : 0;
    $breakdown[] = [
        'key' => 'practice',
        'title' => 'Practice Areas & Court Jurisdiction',
        'weight' => 20,
        'completed' => $hasPractice,
        'icon' => 'fa-scale-balanced',
        'tip' => 'Selected practice disciplines and primary court of appearance.'
    ];

    // 5. Contact Information (10%)
    $hasContact = !empty(trim($adv['mobile'] ?? '')) || !empty(trim($adv['email'] ?? ''));
    $score += $hasContact ? 10 : 0;
    $breakdown[] = [
        'key' => 'contact',
        'title' => 'Verified Contact Details',
        'weight' => 10,
        'completed' => $hasContact,
        'icon' => 'fa-phone',
        'tip' => 'Direct contact phone number and communication email.'
    ];

    // 6. Profile Photograph (15%)
    $hasPhoto = !empty(getAdvocatePhotoUrl($adv['photo'] ?? ''));
    $score += $hasPhoto ? 15 : 0;
    $breakdown[] = [
        'key' => 'photo',
        'title' => 'Profile Photograph',
        'weight' => 15,
        'completed' => $hasPhoto,
        'icon' => 'fa-camera',
        'tip' => 'High-resolution official advocate photograph.'
    ];

    // 7. ID Proof / Bar Council Certificate (15%)
    $hasIdProof = !empty(getAdvocateIdProofUrl($adv['id_proof'] ?? ''));
    $score += $hasIdProof ? 15 : 0;
    $breakdown[] = [
        'key' => 'id_proof',
        'title' => 'Bar Council ID / Certificate',
        'weight' => 15,
        'completed' => $hasIdProof,
        'icon' => 'fa-certificate',
        'tip' => 'Uploaded Bar Association ID or AIBE certificate proof.'
    ];

    $percent = min(100, max(0, $score));

    if ($percent >= 90) {
        $label = 'Excellent Profile';
        $color = '#16a34a'; // Green
        $grade = 'A+';
        $badgeClass = 'adv-index-a-plus';
    } elseif ($percent >= 70) {
        $label = 'Strong Profile';
        $color = '#ca8a04'; // Gold
        $grade = 'A';
        $badgeClass = 'adv-index-a';
    } elseif ($percent >= 50) {
        $label = 'Good Profile';
        $color = '#d97706'; // Amber
        $grade = 'B';
        $badgeClass = 'adv-index-b';
    } else {
        $label = 'Basic Profile';
        $color = '#dc2626'; // Red
        $grade = 'C';
        $badgeClass = 'adv-index-c';
    }

    $completedCount = count(array_filter($breakdown, fn($b) => $b['completed']));

    return [
        'percent' => $percent,
        'label' => $label,
        'color' => $color,
        'grade' => $grade,
        'badge_class' => $badgeClass,
        'breakdown' => $breakdown,
        'completed_count' => $completedCount,
        'total_count' => count($breakdown)
    ];
}

/**
 * Render advocate index percentage badge HTML
 */
function renderAdvocateIndexBadge(array $advIndex, string $format = 'badge'): string {
    $pct = (int)($advIndex['percent'] ?? 0);
    $grade = sanitize($advIndex['grade'] ?? 'B');
    $badgeClass = sanitize($advIndex['badge_class'] ?? 'adv-index-b');
    $label = sanitize($advIndex['label'] ?? 'Profile Index');

    if ($format === 'compact') {
        return '<span class="adv-index-badge ' . $badgeClass . '" title="Advocate Index: ' . $pct . '% (' . $label . ')"><i class="fas fa-bolt"></i> ' . $pct . '%</span>';
    }

    if ($format === 'pill') {
        return '<span class="adv-index-pill ' . $badgeClass . '"><i class="fas fa-bolt"></i> <strong>' . $pct . '%</strong> Advocate Index <span class="adv-index-grade-tag">' . $grade . '</span></span>';
    }

    return '<span class="adv-index-badge ' . $badgeClass . '" title="Advocate Index: ' . $pct . '% (' . $label . ')"><i class="fas fa-bolt"></i> <strong>' . $pct . '%</strong> Index</span>';
}

function getPracticeAreaIcon(string $practice): string {
    $p = strtolower(trim($practice));
    if (strpos($p, 'crim') !== false) return 'fa-gavel';
    if (strpos($p, 'civil') !== false) return 'fa-landmark';
    if (strpos($p, 'prop') !== false || strpos($p, 'real') !== false || strpos($p, 'estate') !== false || strpos($p, 'land') !== false) return 'fa-house-chimney';
    if (strpos($p, 'corp') !== false || strpos($p, 'comp') !== false || strpos($p, 'busin') !== false || strpos($p, 'contract') !== false) return 'fa-building-columns';
    if (strpos($p, 'fam') !== false || strpos($p, 'matrimon') !== false || strpos($p, 'divorce') !== false || strpos($p, 'child') !== false) return 'fa-people-roof';
    if (strpos($p, 'tax') !== false || strpos($p, 'gst') !== false || strpos($p, 'custom') !== false || strpos($p, 'revenue') !== false) return 'fa-file-invoice-dollar';
    if (strpos($p, 'const') !== false || strpos($p, 'writ') !== false || strpos($p, 'pil') !== false) return 'fa-book-atlas';
    if (strpos($p, 'consumer') !== false) return 'fa-shield-halved';
    if (strpos($p, 'cyber') !== false || strpos($p, 'it') !== false || strpos($p, 'intellectual') !== false || strpos($p, 'ipr') !== false || strpos($p, 'patent') !== false) return 'fa-laptop-code';
    if (strpos($p, 'bank') !== false || strpos($p, 'finance') !== false || strpos($p, 'drf') !== false || strpos($p, 'cheque') !== false || strpos($p, '138') !== false) return 'fa-vault';
    if (strpos($p, 'labour') !== false || strpos($p, 'employ') !== false || strpos($p, 'service') !== false) return 'fa-user-tie';
    if (strpos($p, 'arbit') !== false || strpos($p, 'mediat') !== false || strpos($p, 'dispute') !== false) return 'fa-handshake';
    if (strpos($p, 'motor') !== false || strpos($p, 'mact') !== false || strpos($p, 'accid') !== false) return 'fa-car-burst';
    return 'fa-scale-balanced';
}

function parsePracticeAreas(?string $raw): array {
    if (empty($raw)) return ['General Practice', 'Civil Law'];
    if (trim($raw) === 'Array') return ['Civil Law', 'Criminal Law', 'Constitutional Law'];
    $parts = explode(',', $raw);
    $cleaned = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p && $p !== 'Array') $cleaned[] = $p;
    }
    return !empty($cleaned) ? $cleaned : ['Civil Law', 'Property Law', 'Criminal Litigation'];
}

function cleanActName(?string $name): string {
    if (empty($name)) return '';
    // Strip trailing / leading corrupted question marks inside parentheses or newlines
    $clean = preg_replace('/\s*\(\s*\?+\s*\)/u', '', $name);
    $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
    $clean = preg_replace('/\s*\/\s*\?+/u', '', $clean);
    return trim($clean);
}

function renderPagination(int $currentPage, int $totalPages, string $baseUrl, array $params = []): string {
    if ($totalPages <= 1) return '';

    unset($params['page']);
    $queryString = http_build_query($params);
    $separator = $queryString ? '&' : '';
    $urlPattern = $baseUrl . '?' . ($queryString ? $queryString . '&' : '') . 'page=';

    $html = '<nav class="pagination-wrapper" aria-label="Page navigation"><ul class="pagination">';

    // Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $urlPattern . ($currentPage - 1) . '"><i class="fas fa-chevron-left"></i> Prev</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i> Prev</span></li>';
    }

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $urlPattern . '1">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $urlPattern . $i . '">' . $i . '</a></li>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $urlPattern . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $urlPattern . ($currentPage + 1) . '">Next <i class="fas fa-chevron-right"></i></a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next <i class="fas fa-chevron-right"></i></span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

function getSiteConfig(string $key, string $default = ''): string {
    static $configs = null;
    if ($configs === null) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT option_name, option_value FROM op_config");
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $configs = [];
        }
    }
    return !empty($configs[$key]) ? $configs[$key] : $default;
}

function getEducationDegree(?string $edu): string {
    if (empty($edu) || $edu === 'Select Education') {
        return 'LL.B. / Legal Graduate';
    }
    
    $trimmed = trim((string)$edu);
    
    // Direct known numeric mappings
    $idMap = [
        '15' => 'Graduate (LL.B.)',
        '17' => 'Post Graduate (LL.M.)',
        '18' => 'Intermediate / Pre-Law',
        '19' => 'Doctorate / Ph.D. in Law',
        '2'  => 'Graduate (LL.B.)',
        '1'  => 'Non Matric',
        '3'  => 'Intermediate',
        '4'  => 'Graduate (LL.B.)',
        '5'  => 'Post Graduate (LL.M.)'
    ];
    
    if (isset($idMap[$trimmed])) {
        return $idMap[$trimmed];
    }
    
    // Check op_config qualification_list
    $qualListStr = getSiteConfig('qualification_list', 'Non Matric,Matric,Intermediate,Graduate,Post Graduate');
    $quals = array_map('trim', explode(',', $qualListStr));
    
    if (is_numeric($trimmed)) {
        $idx = (int)$trimmed;
        if (isset($quals[$idx])) {
            return $quals[$idx] . ($quals[$idx] === 'Post Graduate' ? ' (LL.M.)' : ' (LL.B.)');
        }
        if (isset($quals[$idx - 1])) {
            return $quals[$idx - 1] . ($quals[$idx - 1] === 'Post Graduate' ? ' (LL.M.)' : ' (LL.B.)');
        }
        return 'Graduate (LL.B.)';
    }
    
    if (strcasecmp($trimmed, 'PG') === 0 || stripos($trimmed, 'Post Graduate') !== false || stripos($trimmed, 'LL.M') !== false) {
        return 'Post Graduate (LL.M.)';
    }
    if (strcasecmp($trimmed, 'Graduate') === 0 || stripos($trimmed, 'LL.B') !== false) {
        return 'Graduate (LL.B.)';
    }
    
    return sanitize($trimmed);
}

function formatPracticeExperience(?string $year): string {
    $rawYear = trim((string)$year);
    if (empty($rawYear) || !is_numeric($rawYear)) {
        return 'Not Available';
    }
    
    $enrYear = (int)$rawYear;
    $currentYear = (int)date('Y');
    
    // Boundary check: cannot be future year or before 1900
    if ($enrYear < 1900 || $enrYear > $currentYear) {
        return 'Not Available';
    }
    
    $diff = $currentYear - $enrYear;
    // Cannot be negative and not more than 100 years
    if ($diff < 0 || $diff > 100) {
        return 'Not Available';
    }
    
    if ($diff === 0) {
        return "1st Year Experience";
    } elseif ($diff === 1) {
        return "1 Year Experience";
    } else {
        return $diff . " Years Experience";
    }
}

function formatEnrollmentNumber(?string $eNo): string {
    $no = trim((string)$eNo);
    
    if (empty($no) || strtolower($no) === 'null' || strtolower($no) === 'n/a') {
        return 'Not Available';
    }
    
    return 'Available';
}

function getAdvocatePhotoUrl(?string $photo): string {
    $p = trim((string)$photo);
    if (empty($p) || $p === '0' || strtolower($p) === 'null') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $p)) {
        return $p;
    }
    if (strpos($p, 'upload/advocate-image/') === 0 || strpos($p, 'upload/') === 0) {
        return $p;
    }
    if (file_exists(ROOT_PATH . '/upload/advocate-image/' . $p)) {
        return 'upload/advocate-image/' . $p;
    }
    if (file_exists(ROOT_PATH . '/upload/' . $p)) {
        return 'upload/' . $p;
    }
    return 'upload/advocate-image/' . $p;
}

function getAdvocateIdProofUrl(?string $idProof): string {
    $ip = trim((string)$idProof);
    if (empty($ip) || $ip === '0' || strtolower($ip) === 'null') {
        return '';
    }
    if (preg_match('/^https?:\/\//i', $ip)) {
        return $ip;
    }
    if (strpos($ip, 'upload/id-proof/') === 0 || strpos($ip, 'upload/') === 0) {
        return $ip;
    }
    if (file_exists(ROOT_PATH . '/upload/id-proof/' . $ip)) {
        return 'upload/id-proof/' . $ip;
    }
    if (file_exists(ROOT_PATH . '/upload/' . $ip)) {
        return 'upload/' . $ip;
    }
    return 'upload/id-proof/' . $ip;
}

function uploadAdvocatePhoto(array $file, int $advocateId): array {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'No photo file selected.',
        ];
        return ['success' => false, 'error' => $errMap[$file['error'] ?? 0] ?? 'Photo upload failed.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid photo format. Please upload JPG or PNG.'];
    }

    if ($file['size'] > 50 * 1024) {
        return ['success' => false, 'error' => 'Photo size exceeds maximum limit of 50 KB.'];
    }

    $targetDir = ROOT_PATH . '/upload/advocate-image/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $filename = 'photo_' . $advocateId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save photo file on server.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE advocate SET photo = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$filename, $advocateId]);
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Photo saved, but database update failed: ' . $e->getMessage()];
    }

    return ['success' => true, 'filename' => $filename, 'url' => getAdvocatePhotoUrl($filename)];
}

function uploadAdvocateIdProof(array $file, int $advocateId): array {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'No ID proof document selected.',
        ];
        return ['success' => false, 'error' => $errMap[$file['error'] ?? 0] ?? 'ID proof upload failed.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid document format. Please upload JPG, PNG, or PDF.'];
    }

    if ($file['size'] > 100 * 1024) {
        return ['success' => false, 'error' => 'ID proof document exceeds maximum limit of 100 KB.'];
    }

    $targetDir = ROOT_PATH . '/upload/id-proof/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $filename = 'id_proof_' . $advocateId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save ID proof document on server.'];
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE advocate SET id_proof = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$filename, $advocateId]);
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Document saved, but database update failed: ' . $e->getMessage()];
    }

    return ['success' => true, 'filename' => $filename, 'url' => getAdvocateIdProofUrl($filename)];
}

function getChangeRequestDocUrl(?string $filename): ?string {
    if (empty($filename)) {
        return null;
    }
    $clean = trim($filename);
    if (empty($clean)) {
        return null;
    }
    if (preg_match('/^https?:\/\//i', $clean)) {
        return $clean;
    }
    return APP_URL . '/upload/change-requests/' . rawurlencode($clean);
}

function uploadChangeRequestDoc(array $file, int $advocateId): array {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server size limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
            UPLOAD_ERR_NO_FILE => 'No document file selected.',
        ];
        return ['success' => false, 'error' => $errMap[$file['error'] ?? 0] ?? 'Document upload failed.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid document format. Please upload JPG, PNG, or PDF.'];
    }

    if ($file['size'] > 500 * 1024) {
        return ['success' => false, 'error' => 'Document exceeds maximum size limit of 500 KB.'];
    }

    $targetDir = ROOT_PATH . '/upload/change-requests/';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $filename = 'req_' . $advocateId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $targetDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to save document on server.'];
    }

    return ['success' => true, 'filename' => $filename, 'url' => getChangeRequestDocUrl($filename)];
}

/**
 * Retrieve active configuration values from advocate_config table
 */
function getAdvocateConfigs(?string $key = null): array {
    static $cache = [];
    $cacheKey = $key ?? '__ALL__';
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    try {
        $db = getDB();
        if ($key !== null) {
            $stmt = $db->prepare("SELECT * FROM advocate_config WHERE config_key = ? AND status = 'ACTIVE' ORDER BY display_order ASC, config_value ASC");
            $stmt->execute([$key]);
        } else {
            $stmt = $db->query("SELECT * FROM advocate_config WHERE status = 'ACTIVE' ORDER BY config_key ASC, display_order ASC, config_value ASC");
        }
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cache[$cacheKey] = $results;
        return $results;
    } catch (Exception $e) {
        return [];
    }
}

function getAdvocatePracticeAreas(): array {
    $rows = getAdvocateConfigs('practice_area');
    $areas = [];
    foreach ($rows as $r) {
        $val = trim($r['config_value']);
        if (!empty($val) && !in_array($val, $areas)) {
            $areas[] = $val;
        }
    }
    if (empty($areas)) {
        $areas = ['Civil Law', 'Criminal Defense', 'Corporate Law', 'Family & Matrimonial', 'Property & Real Estate', 'Tax Law', 'Constitutional Law', 'Cyber Law', 'Consumer Protection', 'Labour & Service', 'Banking & Debt Recovery', 'Arbitration & Mediation'];
    }
    return $areas;
}

function getAdvocateCourtTypes(): array {
    $rows = getAdvocateConfigs('court_type');
    $courts = [];
    foreach ($rows as $r) {
        $val = trim($r['config_value']);
        if (str_contains($val, '|')) {
            [$code, $name] = explode('|', $val, 2);
            $courts[] = ['code' => trim($code), 'name' => trim($name)];
        } else {
            $courts[] = ['code' => $val, 'name' => $val];
        }
    }
    if (empty($courts)) {
        $courts = [
            ['code' => 'CC', 'name' => 'District / Civil Court'],
            ['code' => 'HC', 'name' => 'High Court'],
            ['code' => 'SC', 'name' => 'Supreme Court of India'],
            ['code' => 'EC', 'name' => 'Executive Court'],
            ['code' => 'OC', 'name' => 'Other Court']
        ];
    }
    return $courts;
}

function getAdvocateEducationLevels(): array {
    $rows = getAdvocateConfigs('education_level');
    $levels = [];
    foreach ($rows as $r) {
        $val = trim($r['config_value']);
        if (str_contains($val, '|')) {
            [$code, $name] = explode('|', $val, 2);
            $levels[] = ['code' => trim($code), 'name' => trim($name)];
        } else {
            $levels[] = ['code' => $val, 'name' => $val];
        }
    }
    return $levels;
}

/**
 * Detect client platform (web / app)
 */
function detectClientPlatform(): string {
    if (!empty($_GET['source']) && strtolower($_GET['source']) === 'app') return 'app';
    if (!empty($_GET['platform']) && strtolower($_GET['platform']) === 'app') return 'app';
    if (!empty($_SESSION['client_platform']) && $_SESSION['client_platform'] === 'app') return 'app';
    
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    
    if (str_contains($requestedWith, 'app') || str_contains($requestedWith, 'myadvocate')) {
        return 'app';
    }
    if (str_contains($userAgent, 'wv') || str_contains($userAgent, 'flutter') || str_contains($userAgent, 'reactnative') || str_contains($userAgent, 'cordova') || str_contains($userAgent, 'myadvocateapp')) {
        return 'app';
    }
    
    return 'web';
}

/**
 * Record advocate seen event in advocate_data table (1 row per advocate)
 */
function recordAdvocateSeen(int $advocateId, ?string $platform = null): void {
    if ($advocateId <= 0) return;
    $platform = $platform ?: detectClientPlatform();
    
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO advocate_data 
            (advocate_id, last_seen_from, seen_count, last_seen_at, created_at) 
            VALUES (?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                last_seen_from = VALUES(last_seen_from),
                seen_count = seen_count + 1,
                last_seen_at = NOW()");
        $stmt->execute([$advocateId, $platform]);
    } catch (Exception $e) {}
}

/**
 * Record advocate profile update details in advocate_data table
 */
function recordAdvocateProfileUpdate(int $advocateId, array|string $changes, string $updatedBy = 'advocate'): void {
    if ($advocateId <= 0) return;
    
    $summary = '';
    $fieldsJson = '';
    
    if (is_array($changes)) {
        $cleanChanges = [];
        $fieldNames = [];
        foreach ($changes as $k => $v) {
            $fieldNames[] = is_numeric($k) ? $v : $k;
            $cleanChanges[$k] = $v;
        }
        $summary = "Updated " . implode(', ', array_unique($fieldNames));
        $fieldsJson = json_encode($cleanChanges, JSON_UNESCAPED_UNICODE);
    } else {
        $summary = (string)$changes;
        $fieldsJson = json_encode(['summary' => $summary], JSON_UNESCAPED_UNICODE);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'updated_by' => $updatedBy,
        'summary' => $summary,
        'platform' => detectClientPlatform()
    ];
    
    try {
        $db = getDB();
        
        // Fetch existing history
        $existingHistory = [];
        $chk = $db->prepare("SELECT update_history FROM advocate_data WHERE advocate_id = ? LIMIT 1");
        $chk->execute([$advocateId]);
        $row = $chk->fetch();
        if ($row && !empty($row['update_history'])) {
            $decoded = json_decode($row['update_history'], true);
            if (is_array($decoded)) {
                $existingHistory = $decoded;
            }
        }
        
        // Prepend new entry and keep last 25 logs
        array_unshift($existingHistory, $logEntry);
        $existingHistory = array_slice($existingHistory, 0, 25);
        $historyJson = json_encode($existingHistory, JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare("INSERT INTO advocate_data 
            (advocate_id, last_seen_from, seen_count, last_seen_at, last_updated_at, last_update_fields, last_update_summary, update_history, created_at) 
            VALUES (?, ?, 1, NOW(), NOW(), ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                last_updated_at = NOW(),
                last_update_fields = VALUES(last_update_fields),
                last_update_summary = VALUES(last_update_summary),
                update_history = VALUES(update_history)");
        $stmt->execute([
            $advocateId,
            detectClientPlatform(),
            $fieldsJson,
            $summary,
            $historyJson
        ]);
    } catch (Exception $e) {}
}

/**
 * Get advocate metadata and tracking from advocate_data table
 */
function getAdvocateData(int $advocateId): ?array {
    if ($advocateId <= 0) return null;
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM advocate_data WHERE advocate_id = ? LIMIT 1");
        $stmt->execute([$advocateId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Record when a logged-in member views an advocate's profile
 */
function recordMemberAdvocateView(int $memberId, int $advocateId, ?string $advocateName = null, ?string $memberName = null): void {
    if ($memberId <= 0 || $advocateId <= 0) return;
    
    try {
        $db = getDB();
        
        // Fetch names if not passed
        if (empty($advocateName)) {
            $aStmt = $db->prepare("SELECT name FROM advocate WHERE id = ? LIMIT 1");
            $aStmt->execute([$advocateId]);
            $advocateName = $aStmt->fetchColumn() ?: 'Advocate';
        }
        if (empty($memberName)) {
            if (!empty($_SESSION['member_name'])) {
                $memberName = $_SESSION['member_name'];
            } else {
                $mStmt = $db->prepare("SELECT name FROM member WHERE id = ? LIMIT 1");
                $mStmt->execute([$memberId]);
                $memberName = $mStmt->fetchColumn() ?: 'Member';
            }
        }
        
        $stmt = $db->prepare("INSERT INTO member_advocate_views 
            (member_id, advocate_id, advocate_name, member_name, view_count, last_viewed_at, created_at) 
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                advocate_name = VALUES(advocate_name),
                member_name = VALUES(member_name),
                view_count = view_count + 1,
                last_viewed_at = NOW()");
        $stmt->execute([$memberId, $advocateId, $advocateName, $memberName]);
    } catch (Exception $e) {}
}

/**
 * Get advocate profile views for a member
 */
function getMemberAdvocateViews(int $memberId, int $limit = 30): array {
    if ($memberId <= 0) return [];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT v.*, a.photo, a.court, a.practicing_courts, a.practice_area, a.e_no, a.e_year, a.public_url, a.plan_type, a.type, a.state_code, a.district_code, s.name as state_name, d.name as district_name 
            FROM member_advocate_views v
            LEFT JOIN advocate a ON v.advocate_id = a.id
            LEFT JOIN state s ON a.state_code = s.code
            LEFT JOIN district d ON a.district_code = d.code
            WHERE v.member_id = ? 
            ORDER BY v.last_viewed_at DESC 
            LIMIT ?");
        $stmt->bindValue(1, $memberId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Dispatch SMS message via MSG Club / MSG91 Gateway (using myadvindia integration)
 */
function sendSMSMessage(string $mobile, string $message, ?string $dltTeId = null): array {
    $mobileClean = preg_replace('/[^0-9]/', '', $mobile);
    if (strlen($mobileClean) === 12 && str_starts_with($mobileClean, '91')) {
        $mobileClean = substr($mobileClean, 2);
    }
    if (strlen($mobileClean) !== 10) {
        return ['success' => false, 'message' => 'Invalid 10-digit mobile number.'];
    }

    $authKeyMsg = defined('SMS_AUTH_KEY_MSG') ? SMS_AUTH_KEY_MSG : 'b0e99bea1fa7d15e27e1c5fd8e3c868';
    $senderId   = defined('SMS_SENDER_ID') ? SMS_SENDER_ID : 'EMYADV';
    $dltTeId    = $dltTeId ?: (defined('SMS_DLT_TE_ID') ? SMS_DLT_TE_ID : '1207173652433489449');
    $authKeySms = defined('SMS_AUTH_KEY_SMS') ? SMS_AUTH_KEY_SMS : '180367At8cchpCRSTV59ed9c10';

    $msgEncoded = substr(urlencode($message), 0, 340);
    $mobileWithCountry = '91' . urlencode($mobileClean);

    // Primary Gateway: MSG Club (Direct HTTP GET)
    $urlMsg = "http://msg.morg.in/rest/services/sendSMS/sendGroupSms?AUTH_KEY={$authKeyMsg}&message={$msgEncoded}&senderId={$senderId}&routeId=1&mobileNos={$mobileWithCountry}&smsContentType=english";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $urlMsg);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    $isSent = false;
    if (!empty($response)) {
        $json = @json_decode($response, true);
        if (isset($json['responseCode']) && ($json['responseCode'] === '3001' || $json['responseCode'] == 3001)) {
            $isSent = true;
        }
    }

    // Fallback Gateway: MSG91
    if (!$isSent) {
        $urlSms = "http://sms.morg.in/api/sendhttp.php?authkey={$authKeySms}&mobiles={$mobileClean}&message={$msgEncoded}&sender={$senderId}&route=4&country=91&DLT_TE_ID={$dltTeId}";
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $urlSms);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 6);
        $res2 = curl_exec($ch2);
        curl_close($ch2);
        if (!empty($res2)) {
            $isSent = true;
        }
    }

    return [
        'success'  => true,
        'gateway'  => $isSent ? 'online' : 'simulated',
        'response' => $response ?: $curlErr
    ];
}

/**
 * Send OTP SMS using approved DLT template from myadvindia
 */
function sendOTPSMS(string $mobile, string $otp, string $name = 'User'): array {
    $displayName = trim($name) !== '' ? $name : 'User';
    // Exact DLT template format approved in myadvindia
    $smsText = "Dear " . $displayName . ", \nYour MyAdv India OTP / EVC / Password is: " . $otp . " \nVisit https://myadv.in \nRegards \nEMYADV \nOfferPlant";
    return sendSMSMessage($mobile, $smsText);
}

/**
 * Find user (Advocate or Member) by mobile, email, or enrollment number
 */
function findUserByIdentifier(string $identifier, ?PDO $db = null): ?array {
    $identifier = trim($identifier);
    if ($identifier === '') return null;
    $db = $db ?: getDB();

    $digits = preg_replace('/[^0-9]/', '', $identifier);
    $last10 = (strlen($digits) >= 10) ? substr($digits, -10) : $digits;

    // 1. Check Advocate Table
    try {
        $sql = "SELECT id, name, mobile, email, e_no, status, plan_type, 'advocate' as user_type 
            FROM advocate 
            WHERE (status != 'BLOCK' OR status IS NULL) AND (
                mobile = ? 
                OR email = ? 
                OR e_no = ? 
                OR TRIM(mobile) = ? 
                OR TRIM(email) = ? 
                OR TRIM(e_no) = ?";
        $params = [$identifier, $identifier, $identifier, $identifier, $identifier, $identifier];

        if (strlen($last10) >= 10) {
            $sql .= " OR mobile LIKE ? OR mobile LIKE ? OR mobile LIKE ?";
            $params[] = '%' . $last10;
            $params[] = '+91' . $last10;
            $params[] = '91' . $last10;
        }
        $sql .= ") ORDER BY id DESC LIMIT 1";

        $stmtAdv = $db->prepare($sql);
        $stmtAdv->execute($params);
        $adv = $stmtAdv->fetch(PDO::FETCH_ASSOC);
        if ($adv) {
            $mDigits = preg_replace('/[^0-9]/', '', $adv['mobile'] ?? '');
            if (strlen($mDigits) >= 10) {
                $adv['mobile'] = substr($mDigits, -10);
            }
            return $adv;
        }
    } catch (Exception $e) {}

    // 2. Check Member Table
    try {
        $sql = "SELECT id, name, mobile, email, status, 'member' as user_type 
            FROM member 
            WHERE (status != 'BLOCK' OR status IS NULL) AND (
                mobile = ? 
                OR email = ? 
                OR TRIM(mobile) = ? 
                OR TRIM(email) = ?";
        $params = [$identifier, $identifier, $identifier, $identifier];

        if (strlen($last10) >= 10) {
            $sql .= " OR mobile LIKE ? OR mobile LIKE ? OR mobile LIKE ?";
            $params[] = '%' . $last10;
            $params[] = '+91' . $last10;
            $params[] = '91' . $last10;
        }
        $sql .= ") ORDER BY id DESC LIMIT 1";

        $stmtMem = $db->prepare($sql);
        $stmtMem->execute($params);
        $member = $stmtMem->fetch(PDO::FETCH_ASSOC);
        if ($member) {
            $mDigits = preg_replace('/[^0-9]/', '', $member['mobile'] ?? '');
            if (strlen($mDigits) >= 10) {
                $member['mobile'] = substr($mDigits, -10);
            }
            return $member;
        }
    } catch (Exception $e) {}

    return null;
}

