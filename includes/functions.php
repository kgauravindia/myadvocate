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
        return 'profile.php?link=public=' . urlencode(trim($adv['public_url']));
    }
    $slug = generateAdvocateSlug($adv);
    $b64 = base64_encode('id=' . ($adv['id'] ?? 0) . '&name=' . ($adv['name'] ?? ''));
    return 'profile.php?link=' . urlencode($slug) . '&link=' . urlencode($b64);
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



