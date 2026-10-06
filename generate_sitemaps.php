<?php
/**
 * Dynamic XML Sitemap Generator for My Advocate
 * Generates XML Sitemaps conforming to sitemaps.org protocol 0.9
 */
require_once __DIR__ . '/config/app.php';

// Enable error reporting, increase memory/time limits for large datasets
ini_set('memory_limit', '1024M');
set_time_limit(600);

$db = getDB();
$baseUrl = (isset($_GET['local']) || (isset($argv[1]) && $argv[1] === '--local')) ? rtrim(APP_URL, '/') . '/' : 'https://myadv.in/';

function log_msg($msg) {
    if (php_sapi_name() === 'cli') {
        echo "[" . date('H:i:s') . "] " . $msg . "\n";
    } else {
        echo "<p style='font-family:monospace; margin:4px 0;'>[" . date('H:i:s') . "] " . htmlspecialchars($msg) . "</p>\n";
        @flush();
    }
}

function xml_escape($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function create_sitemap_file($filename, $urls) {
    log_msg("Generating $filename (" . count($urls) . " URLs)...");
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    
    foreach ($urls as $item) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . xml_escape($item['loc']) . "</loc>\n";
        if (!empty($item['lastmod'])) {
            $xml .= "    <lastmod>" . xml_escape($item['lastmod']) . "</lastmod>\n";
        }
        if (!empty($item['changefreq'])) {
            $xml .= "    <changefreq>" . xml_escape($item['changefreq']) . "</changefreq>\n";
        }
        if (isset($item['priority'])) {
            $xml .= "    <priority>" . xml_escape($item['priority']) . "</priority>\n";
        }
        $xml .= "  </url>\n";
    }
    
    $xml .= '</urlset>';
    file_put_contents(__DIR__ . '/' . $filename, $xml);
}

$today = date('Y-m-d');

// -------------------------------------------------------------
// 1. Static & Core Portal Pages
// -------------------------------------------------------------
log_msg("Collecting core static and portal tool pages...");

$static_pages = [
    ['', '1.0', 'daily'],
    ['index', '1.0', 'daily'],
    ['about', '0.8', 'monthly'],
    ['services', '0.8', 'weekly'],
    ['pricing', '0.7', 'monthly'],
    ['faq', '0.8', 'weekly'],
    ['contact', '0.7', 'monthly'],
    ['disclaimer', '0.5', 'yearly'],
    ['privacy', '0.5', 'yearly'],
    ['terms', '0.5', 'yearly'],
    ['refund-policy', '0.5', 'yearly'],
    ['advocates', '0.9', 'daily'],
    ['advocate-search-result', '0.9', 'daily'],
    ['advocate-search-result-by-mobile', '0.8', 'weekly'],
    ['public-service-commission', '0.8', 'weekly'],
    ['state-bar-council', '0.8', 'monthly'],
    ['courts', '0.8', 'weekly'],
    ['tools', '0.8', 'monthly'],
    ['law-college-search', '0.9', 'weekly'],
    ['college', '0.9', 'weekly'],
    ['law-college', '0.9', 'weekly'],
    ['acts', '0.9', 'weekly'],
    ['bare-acts', '0.9', 'weekly'],
    ['aibe', '0.8', 'weekly'],
    ['calendars', '0.8', 'monthly'],
    ['notice', '0.8', 'daily'],
    ['signin', '0.6', 'monthly'],
    ['signup', '0.7', 'monthly'],
    ['register', '0.8', 'monthly'],
    ['claim-profile', '0.8', 'monthly']
];

$pages_urls = [];
foreach ($static_pages as $sp) {
    $pages_urls[] = [
        'loc' => $baseUrl . $sp[0],
        'lastmod' => $today,
        'changefreq' => $sp[2],
        'priority' => $sp[1]
    ];
}
create_sitemap_file('sitemap-pages.xml', $pages_urls);

// -------------------------------------------------------------
// 2. District & State Advocate Index Sitemap (e.g. Saran, Patna, etc.)
// -------------------------------------------------------------
log_msg("Querying District & State Advocate Indexes...");
$district_urls = [];

// 2a. State-level Advocate Indexes
$stateAdvStmt = $db->query("
    SELECT state_code, COUNT(*) as cnt, MAX(COALESCE(updated_at, created_at)) as last_act
    FROM advocate
    WHERE (status != 'BLOCK' OR status IS NULL) AND state_code != ''
    GROUP BY state_code
");
$stateAdvMap = [];
while ($row = $stateAdvStmt->fetch(PDO::FETCH_ASSOC)) {
    $stateAdvMap[$row['state_code']] = $row;
}

$states = $db->query("SELECT code, name FROM state WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($states as $st) {
    $code = $st['code'];
    $cnt = $stateAdvMap[$code]['cnt'] ?? 0;
    $dt = !empty($stateAdvMap[$code]['last_act']) ? $stateAdvMap[$code]['last_act'] : '2026-08-01';
    
    $district_urls[] = [
        'loc' => $baseUrl . "advocate-search-result?state=" . urlencode($code),
        'lastmod' => date('Y-m-d', strtotime($dt)),
        'changefreq' => ($cnt > 100) ? 'daily' : 'weekly',
        'priority' => '0.9'
    ];
}

// 2b. District-level Advocate Indexes (e.g. Saran BRSAR, Patna BRPAT, etc.)
$distAdvStmt = $db->query("
    SELECT district_code, COUNT(*) as cnt, MAX(COALESCE(updated_at, created_at)) as last_act
    FROM advocate
    WHERE (status != 'BLOCK' OR status IS NULL) AND district_code != ''
    GROUP BY district_code
");
$distAdvMap = [];
while ($row = $distAdvStmt->fetch(PDO::FETCH_ASSOC)) {
    $distAdvMap[$row['district_code']] = $row;
}

$districts = $db->query("SELECT code, name, state_code FROM district WHERE status = 'ACTIVE' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($districts as $d) {
    $code = $d['code'];
    $cnt = $distAdvMap[$code]['cnt'] ?? 0;
    $dt = !empty($distAdvMap[$code]['last_act']) ? $distAdvMap[$code]['last_act'] : '2026-08-01';
    
    $district_urls[] = [
        'loc' => $baseUrl . "advocate-search-result?district=" . urlencode($code),
        'lastmod' => date('Y-m-d', strtotime($dt)),
        'changefreq' => ($cnt > 50) ? 'daily' : 'weekly',
        'priority' => ($cnt > 0) ? '0.85' : '0.6'
    ];
}
create_sitemap_file('sitemap-districts.xml', $district_urls);

// -------------------------------------------------------------
// 3. Bare Acts Sitemap
// -------------------------------------------------------------
log_msg("Querying Bare Acts & Central Legislation...");
$acts_urls = [];
try {
    $act_stmt = $db->query("SELECT id, name, year, english, hindi, updated_at, created_at FROM acts WHERE status = 'ACTIVE' ORDER BY id ASC");
    while ($ar = $act_stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = $baseUrl . "acts?q=" . urlencode($ar['name']);
        $dt = !empty($ar['updated_at']) ? $ar['updated_at'] : (!empty($ar['created_at']) ? $ar['created_at'] : '2026-08-01');
        $acts_urls[] = [
            'loc' => $loc,
            'lastmod' => date('Y-m-d', strtotime($dt)),
            'changefreq' => 'monthly',
            'priority' => '0.8'
        ];
    }
} catch (Exception $e) {}
create_sitemap_file('sitemap-acts.xml', $acts_urls);

// -------------------------------------------------------------
// 4. Legal Notices Sitemap
// -------------------------------------------------------------
log_msg("Querying Legal Notices & Updates...");
$notices_urls = [];
try {
    $notice_stmt = $db->query("SELECT id, name, last_date, created_at, updated_at FROM notice WHERE status = 'ACTIVE' ORDER BY id DESC");
    while ($nr = $notice_stmt->fetch(PDO::FETCH_ASSOC)) {
        $loc = $baseUrl . "notice?id=" . $nr['id'];
        $dt = !empty($nr['updated_at']) ? $nr['updated_at'] : (!empty($nr['created_at']) ? $nr['created_at'] : '2026-08-01');
        $notices_urls[] = [
            'loc' => $loc,
            'lastmod' => date('Y-m-d', strtotime($dt)),
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ];
    }
} catch (Exception $e) {}
create_sitemap_file('sitemap-notices.xml', $notices_urls);

// -------------------------------------------------------------
// 5. Advocates Sitemaps (Batched in chunks of 45,000 max)
// -------------------------------------------------------------
log_msg("Querying active advocates...");

$noindex_ids = [148584, 147839, 142446, 59522];
$noindex_clause = !empty($noindex_ids) ? "AND a.id NOT IN (" . implode(',', $noindex_ids) . ")" : "";

$adv_sql = "
    SELECT a.id, a.name, a.public_url, a.e_year, a.type, 
           COALESCE(a.updated_at, a.created_at) as last_activity,
           d.name as dist_name
    FROM advocate a 
    LEFT JOIN district d ON a.district_code = d.code 
    WHERE (a.status != 'BLOCK' OR a.status IS NULL) $noindex_clause
    ORDER BY a.id ASC
";

$adv_stmt = $db->query($adv_sql);
$advocates = [];

while ($row = $adv_stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $name = trim((string)($row['name'] ?? ''));
    $public_url = trim((string)($row['public_url'] ?? ''));
    
    if (!empty($public_url)) {
        $loc = $baseUrl . "profile?url=" . rawurlencode($public_url);
    } else {
        $slug = generateAdvocateSlug([
            'name' => $name,
            'district_code' => '',
            'district_name' => $row['dist_name'] ?? '',
            'e_year' => $row['e_year'] ?? ''
        ]);
        $encoded = base64_encode('id=' . $id . '&name=' . $name);
        $loc = $baseUrl . "profile.php?link=" . urlencode($slug) . "&link=" . urlencode($encoded);
    }

    $lastmod = !empty($row['last_activity']) ? date('Y-m-d', strtotime($row['last_activity'])) : '2026-08-01';
    $priority = (isset($row['type']) && in_array($row['type'], ['PREMIUM', 'VERIFIED'])) ? '0.8' : '0.6';

    $advocates[] = [
        'loc' => $loc,
        'lastmod' => $lastmod,
        'changefreq' => 'weekly',
        'priority' => $priority
    ];
}

$chunk_size = 45000;
$adv_chunks = array_chunk($advocates, $chunk_size);
$adv_sitemaps = [];

foreach ($adv_chunks as $i => $chunk) {
    $filename = "sitemap-advocates-" . ($i + 1) . ".xml";
    create_sitemap_file($filename, $chunk);
    $adv_sitemaps[] = $filename;
}

// -------------------------------------------------------------
// 6. Law Colleges Sitemaps
// -------------------------------------------------------------
log_msg("Querying law colleges...");
$colleges = [];

$col_stmt = $db->query("SELECT id, name, state, year, updated_at, created_at FROM college WHERE (status != 'BLOCK' OR status IS NULL) ORDER BY id ASC");
while ($row = $col_stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $name = trim((string)($row['name'] ?? ''));
    $state = trim((string)($row['state'] ?? ''));
    $year = trim((string)($row['year'] ?? ''));
    
    $slug = 'law-college-' . preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower(trim($name . ' ' . $state . ' ' . $year)));
    $encoded = base64_encode('id=' . $id);
    $loc = $baseUrl . "college?link=" . urlencode(trim($slug, '-')) . "&link=" . urlencode($encoded);

    $dt = !empty($row['updated_at']) ? $row['updated_at'] : (!empty($row['created_at']) ? $row['created_at'] : '2026-08-01');
    $lastmod = date('Y-m-d', strtotime($dt));

    $colleges[] = [
        'loc' => $loc,
        'lastmod' => $lastmod,
        'changefreq' => 'monthly',
        'priority' => '0.7'
    ];
}
create_sitemap_file('sitemap-colleges.xml', $colleges);

// -------------------------------------------------------------
// 7. Root Sitemap Index (`sitemap.xml`)
// -------------------------------------------------------------
log_msg("Generating root index (sitemap.xml)...");

$all_sub_sitemaps = array_merge(['sitemap-pages.xml', 'sitemap-districts.xml', 'sitemap-acts.xml', 'sitemap-notices.xml'], $adv_sitemaps, ['sitemap-colleges.xml']);

$index_xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$index_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($all_sub_sitemaps as $sm) {
    $index_xml .= "  <sitemap>\n";
    $index_xml .= "    <loc>" . $baseUrl . $sm . "</loc>\n";
    $index_xml .= "    <lastmod>" . $today . "</lastmod>\n";
    $index_xml .= "  </sitemap>\n";
}

$index_xml .= '</sitemapindex>';
file_put_contents(__DIR__ . '/sitemap.xml', $index_xml);

log_msg("All sitemaps updated successfully!");
if (php_sapi_name() !== 'cli') {
    echo "<h3>Sitemap generation finished!</h3>";
    echo "<p><a href='sitemap.xml' target='_blank'>View sitemap.xml</a></p>";
}
