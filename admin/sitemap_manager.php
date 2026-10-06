<?php
/**
 * XML Sitemap Manager & Generator - Admin Portal
 * Real-time monitoring, inspection, and generation of search engine sitemaps
 */
$pageTitle = "XML Sitemap Manager";
require_once __DIR__ . '/includes/admin_header.php';

$rootDir = dirname(__DIR__);
$sitemapFiles = [
    'sitemap.xml' => ['title' => 'Main Sitemap Index', 'type' => 'Index', 'desc' => 'Root index referencing all sub-sitemaps (submit this URL to search engines)'],
    'sitemap-pages.xml' => ['title' => 'Portal Pages Sitemap', 'type' => 'Core Pages', 'desc' => 'Static pages, directory search portals, and legal tool pages'],
    'sitemap-districts.xml' => ['title' => 'District & State Indexes', 'type' => 'Regional Indexes', 'desc' => 'All 699 District (e.g. Saran, Patna) and 38 State Advocate directory indexes'],
    'sitemap-acts.xml' => ['title' => 'Bare Acts & Codes', 'type' => 'Legal Library', 'desc' => 'Central & State Bare Acts, BNS, BNSS, BSA, CPC, and Constitution'],
    'sitemap-notices.xml' => ['title' => 'Legal Notices & Updates', 'type' => 'Notifications', 'desc' => 'Supreme Court, High Courts, and AIBE examination notices'],
    'sitemap-advocates-1.xml' => ['title' => 'Advocates Directory - Batch 1', 'type' => 'Advocates (1-45k)', 'desc' => 'Batch 1 (1 to 45,000 advocate profiles)'],
    'sitemap-advocates-2.xml' => ['title' => 'Advocates Directory - Batch 2', 'type' => 'Advocates (45k-90k)', 'desc' => 'Batch 2 (45,001 to 90,000 advocate profiles)'],
    'sitemap-advocates-3.xml' => ['title' => 'Advocates Directory - Batch 3', 'type' => 'Advocates (90k-135k)', 'desc' => 'Batch 3 (90,001 to 135,000 advocate profiles)'],
    'sitemap-advocates-4.xml' => ['title' => 'Advocates Directory - Batch 4', 'type' => 'Advocates (135k+)', 'desc' => 'Batch 4 (135,001+ advocate profiles)'],
    'sitemap-colleges.xml' => ['title' => 'Colleges & Universities', 'type' => 'Colleges', 'desc' => '40,000+ Law Colleges, Universities, and Legal Institutes'],
];

$genMessage = '';
$genSuccess = false;

// Handle Sitemap Generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $mode = $_POST['mode'] ?? 'live';
    $genScript = $rootDir . DIRECTORY_SEPARATOR . 'generate_sitemaps.php';
    
    // Find suitable PHP binary
    $phpBin = PHP_BINARY;
    if (empty($phpBin) || !file_exists($phpBin)) {
        if (file_exists('D:\\laragon\\bin\\php\\php-8.3.28-Win32-vs16-x64\\php.exe')) {
            $phpBin = 'D:\\laragon\\bin\\php\\php-8.3.28-Win32-vs16-x64\\php.exe';
        } else {
            $phpBin = 'php';
        }
    }
    
    $output = [];
    $returnCode = 0;
    $cmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($genScript) . ($mode === 'local' ? ' --local' : '');
    
    if (function_exists('exec')) {
        @exec($cmd . ' 2>&1', $output, $returnCode);
    }
    
    // Fallback if exec failed or not allowed
    if ($returnCode !== 0 || empty($output)) {
        ob_start();
        if ($mode === 'local') {
            $_GET['local'] = 1;
        }
        try {
            include $genScript;
            $buffer = ob_get_clean();
            $genSuccess = true;
            $genMessage = "Sitemaps generated directly via internal runner!\n" . strip_tags($buffer);
        } catch (Throwable $e) {
            ob_end_clean();
            $genSuccess = false;
            $genMessage = "Direct generation error: " . $e->getMessage();
        }
    } else {
        $genSuccess = true;
        $genMessage = "Sitemaps generated successfully via CLI runner!\n" . implode("\n", $output);
    }
}

// Gather stats for all sitemap files
$fileStats = [];
$totalSize = 0;
$existingFilesCount = 0;
$totalIndexedUrls = 0;

foreach ($sitemapFiles as $fname => $meta) {
    $fpath = $rootDir . DIRECTORY_SEPARATOR . $fname;
    $exists = file_exists($fpath);
    $size = $exists ? filesize($fpath) : 0;
    $mtime = $exists ? filemtime($fpath) : null;
    
    $urlCount = 0;
    if ($exists) {
        $existingFilesCount++;
        $totalSize += $size;
        $content = @file_get_contents($fpath);
        if ($content) {
            $urlCount = substr_count($content, '<loc>');
            if ($fname !== 'sitemap.xml') {
                $totalIndexedUrls += $urlCount;
            }
        }
    }
    
    $fileStats[$fname] = array_merge($meta, [
        'exists' => $exists,
        'size' => $size,
        'mtime' => $mtime,
        'url_count' => $urlCount,
        'url' => APP_URL . '/' . $fname
    ]);
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--primary); margin: 0 0 0.25rem;">
            <i class="fas fa-sitemap" style="color: var(--brand-red);"></i> XML Sitemap Manager
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
            Generate, inspect, and monitor XML Sitemaps for Google, Bing, Yahoo, and DuckDuckGo crawlers.
        </p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <!-- Live Production Generate -->
        <form method="POST" class="d-inline" onsubmit="return confirm('Generate XML sitemaps with live domain (https://myadv.in/)?');">
            <input type="hidden" name="action" value="generate">
            <input type="hidden" name="mode" value="live">
            <button type="submit" class="btn btn-primary btn-sm" style="display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 1rem;">
                <i class="fas fa-rotate"></i> <strong>Generate Live Sitemaps</strong>
            </button>
        </form>

        <!-- Local Test Generate -->
        <form method="POST" class="d-inline" onsubmit="return confirm('Generate XML sitemaps with local URL (http://localhost/myadvocate/)?');">
            <input type="hidden" name="action" value="generate">
            <input type="hidden" name="mode" value="local">
            <button type="submit" class="btn btn-outline btn-sm" style="display: flex; align-items: center; gap: 0.4rem; padding: 0.55rem 0.85rem;">
                <i class="fas fa-laptop-code"></i> Generate Local Sitemaps
            </button>
        </form>
    </div>
</div>

<?php if (!empty($genMessage)): ?>
<div class="admin-card" style="border-left: 4px solid <?= $genSuccess ? '#10b981' : '#ef4444' ?>; margin-bottom: 1.5rem; padding: 1.25rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: <?= $genSuccess ? '#065f46' : '#991b1b' ?>; margin-bottom: 0.5rem;">
        <i class="fas <?= $genSuccess ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <?= $genSuccess ? 'Sitemap Generation Completed Successfully' : 'Sitemap Generation Notice' ?>
    </div>
    <pre style="margin: 0; background: #0f172a; color: #38bdf8; padding: 1rem; border-radius: var(--radius-sm); font-size: 0.8125rem; font-family: monospace; max-height: 220px; overflow-y: auto; white-space: pre-wrap;"><?= htmlspecialchars($genMessage) ?></pre>
</div>
<?php endif; ?>

<!-- Top Metrics Grid -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 2rem; gap: 1.25rem;">
    <div class="stat-box" style="border-left: 4px solid var(--brand-red);">
        <div class="stat-label"><i class="fas fa-layer-group" style="color: var(--brand-red);"></i> Sitemaps Built</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--brand-red); font-family: var(--font-heading);"><?= $existingFilesCount ?> / <?= count($sitemapFiles) ?></div>
        <small style="color: var(--text-muted);">XML files generated</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid var(--brand-gold);">
        <div class="stat-label"><i class="fas fa-link" style="color: var(--brand-gold-dark);"></i> Total Indexed URLs</div>
        <div class="stat-value" style="font-size: 1.85rem; color: var(--brand-gold-dark); font-family: var(--font-heading);"><?= number_format($totalIndexedUrls) ?></div>
        <small style="color: var(--text-muted);">Advocates, colleges, acts & pages</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid #10b981;">
        <div class="stat-label"><i class="fas fa-hdd" style="color: #10b981;"></i> Total Sitemap Size</div>
        <div class="stat-value" style="font-size: 1.85rem; color: #10b981; font-family: var(--font-heading);"><?= number_format($totalSize / (1024 * 1024), 2) ?> MB</div>
        <small style="color: var(--text-muted);"><?= number_format($totalSize / 1024, 1) ?> KB total XML disk size</small>
    </div>

    <div class="stat-box" style="border-left: 4px solid #6366f1;">
        <div class="stat-label"><i class="fas fa-clock-rotate-left" style="color: #6366f1;"></i> Last Modified</div>
        <div class="stat-value" style="font-size: 1.25rem; color: #6366f1; font-family: var(--font-heading); margin-top: 0.25rem;">
            <?= !empty($fileStats['sitemap.xml']['mtime']) ? date('d M, h:i A', $fileStats['sitemap.xml']['mtime']) : 'Not yet' ?>
        </div>
        <small style="color: var(--text-muted);">Index timestamp</small>
    </div>
</div>

<!-- Sitemaps List -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="fas fa-file-code" style="color: var(--brand-red);"></i> Active Sitemaps Registry</h3>
        <a href="../sitemap.xml" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">
            <i class="fas fa-arrow-up-right-from-square"></i> Open Root sitemap.xml
        </a>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sitemap File</th>
                    <th>Classification</th>
                    <th>Total URLs</th>
                    <th>File Size</th>
                    <th>Last Generated</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fileStats as $fname => $stat): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--primary); font-family: monospace; font-size: 0.95rem;"><?= htmlspecialchars($fname) ?></div>
                        <small style="color: var(--text-muted);"><?= htmlspecialchars($stat['desc']) ?></small>
                    </td>
                    <td>
                        <span class="badge-verification badge-basic" style="font-size: 0.72rem; padding: 0.2rem 0.5rem; font-weight: 700;">
                            <?= htmlspecialchars($stat['type']) ?>
                        </span>
                    </td>
                    <td>
                        <strong style="color: var(--primary); font-size: 0.95rem;"><?= number_format($stat['url_count']) ?></strong>
                        <small style="color: var(--text-muted);">links</small>
                    </td>
                    <td>
                        <span style="color: var(--text-main); font-weight: 600;">
                            <?= $stat['exists'] ? number_format($stat['size'] / 1024, 1) . ' KB' : '0 KB' ?>
                        </span>
                    </td>
                    <td>
                        <small style="color: var(--text-muted); font-weight: 500;">
                            <?= $stat['mtime'] ? date('d M Y, h:i A', $stat['mtime']) : 'Never' ?>
                        </small>
                    </td>
                    <td>
                        <?php if ($stat['exists']): ?>
                            <span class="badge-verification badge-verified" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                                <i class="fas fa-circle-check"></i> Ready
                            </span>
                        <?php else: ?>
                            <span class="badge-verification badge-basic" style="font-size: 0.7rem; padding: 0.2rem 0.5rem; color: #ef4444; border-color: #ef4444;">
                                <i class="fas fa-circle-xmark"></i> Missing
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($stat['exists']): ?>
                            <a href="../<?= htmlspecialchars($fname) ?>" target="_blank" class="btn btn-outline btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;" title="View XML in new tab">
                                <i class="fas fa-eye"></i> View
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline btn-sm" style="opacity: 0.5;" disabled>Missing</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Search Engine Submission Info -->
<div class="admin-card" style="border-left: 4px solid var(--brand-gold);">
    <h3 class="admin-card-title" style="margin-bottom: 0.5rem;">
        <i class="fas fa-bullhorn" style="color: var(--brand-gold-dark);"></i> Search Engine Submissions & Crawl Health
    </h3>
    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
        Submit your root sitemap index (<code>https://myadv.in/sitemap.xml</code>) to Google and Bing webmaster tools. Search engine bots will automatically discover and crawl all sub-sitemaps for all 160,000+ advocate profiles, colleges, and bare acts.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fab fa-google" style="color: #ea4335;"></i> Google Search Console
            </div>
            <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                Submit root sitemap: <br><code>https://myadv.in/sitemap.xml</code>
            </p>
            <a href="https://search.google.com/search-console" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">
                <i class="fas fa-arrow-up-right-from-square"></i> Open Google Console
            </a>
        </div>

        <div style="background: var(--bg-alt); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
            <div style="font-weight: 700; color: var(--primary); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fab fa-microsoft" style="color: #00a4ef;"></i> Bing Webmaster Tools
            </div>
            <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.75rem;">
                Submit root sitemap: <br><code>https://myadv.in/sitemap.xml</code>
            </p>
            <a href="https://www.bing.com/webmasters" target="_blank" class="btn btn-outline btn-sm" style="font-size: 0.75rem;">
                <i class="fas fa-arrow-up-right-from-square"></i> Open Bing Webmasters
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
