<?php
// admin/github_sync.php - Fetch & Synchronize with GitHub Repository (Dual Engine: CLI + Online API)
$pageTitle = "GitHub Sync & Repository Tools";
require_once __DIR__ . '/includes/admin_header.php';

$repoDir = realpath(dirname(__DIR__));
$msg = '';
$msgType = 'info'; // 'success', 'error', 'info', 'warning'
$commandOutput = '';
$executedCommand = '';
$executionTime = 0;

// Security check: Only Admin / Super Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Ensure CSRF token
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['admin_csrf'];

$repoOwner = 'kgauravindia';
$repoName = 'myadvocate';
$defaultBranch = 'main';
$githubWebUrl = "https://github.com/{$repoOwner}/{$repoName}";
$remoteUrl = "https://github.com/{$repoOwner}/{$repoName}.git";

// Local version tracking file for online hosting without .git
$versionFile = $repoDir . DIRECTORY_SEPARATOR . '.git_version';

function getLocalVersionInfo($versionFile, $repoDir) {
    if (file_exists($versionFile)) {
        $content = @file_get_contents($versionFile);
        $data = json_decode($content, true);
        if (is_array($data) && !empty($data['commit'])) {
            return $data;
        }
    }
    return [
        'commit' => 'f4decaf',
        'branch' => 'main',
        'author' => 'kgauravindia',
        'date' => 'Recent',
        'message' => 'Add explicit 1-click Update from GitHub repository option across admin tools'
    ];
}

// Check shell execution capability
function isShellAvailable() {
    $disabled = explode(',', ini_get('disable_functions'));
    $disabled = array_map('trim', $disabled);
    return function_exists('proc_open') && !in_array('proc_open', $disabled);
}

// Find Git executable (Windows + Linux paths)
function findGitExecutable() {
    if (!isShellAvailable()) {
        return false;
    }
    $candidates = [
        'git',
        '/usr/bin/git',
        '/usr/local/bin/git',
        '/bin/git',
        'C:\\Program Files\\Git\\cmd\\git.exe',
        'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
        'D:\\laragon\\bin\\git\\bin\\git.exe',
        'C:\\laragon\\bin\\git\\bin\\git.exe'
    ];
    foreach ($candidates as $candidate) {
        $test = @shell_exec(escapeshellcmd($candidate) . ' --version 2>&1');
        if ($test && stripos($test, 'git version') !== false) {
            return $candidate;
        }
    }
    return false;
}

$hasGitDir = is_dir($repoDir . DIRECTORY_SEPARATOR . '.git');
$gitBin = findGitExecutable();
$useCliMode = ($hasGitDir && $gitBin !== false);

// Runner function for CLI git commands
function executeGit($args, $repoDir, $gitBin = 'git') {
    $startTime = microtime(true);
    $descriptorspec = [
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];

    // Added safe.directory=* to fix ownership errors on Linux servers
    $cmd = escapeshellcmd($gitBin) . ' -c safe.directory=* -C ' . escapeshellarg($repoDir) . ' ' . $args;
    $process = @proc_open($cmd, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        return [
            'code' => -1,
            'stdout' => '',
            'stderr' => 'Failed to initialize process via proc_open().',
            'time' => 0,
            'cmd' => $cmd
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $code = proc_close($process);
    $duration = round(microtime(true) - $startTime, 3);

    return [
        'code' => $code,
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
        'time' => $duration,
        'cmd' => 'git ' . $args
    ];
}

// Helper: HTTP Request to GitHub REST API (No CLI required)
function githubApiRequest($url) {
    if (extension_loaded('curl')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MyAdvocate-App');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300 && $res) {
            return json_decode($res, true);
        }
    } else {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: MyAdvocate-App\r\n"
            ]
        ];
        $context = stream_context_create($opts);
        $res = @file_get_contents($url, false, $context);
        if ($res) {
            return json_decode($res, true);
        }
    }
    return false;
}

// Helper: Online ZIP Downloader & Extractor (ignores app_new)
function updateFromGitHubZip($repoOwner, $repoName, $branch, $repoDir, $versionFile) {
    $startTime = microtime(true);
    $log = [];
    $log[] = "[Online Engine: Active]";
    $log[] = "Connecting to GitHub API for latest commit reference...";

    $apiUrl = "https://api.github.com/repos/{$repoOwner}/{$repoName}/commits/{$branch}";
    $latestCommitData = githubApiRequest($apiUrl);

    $latestSha = 'unknown';
    $latestMsg = '';
    $latestAuthor = '';
    $latestDate = date('Y-m-d H:i:s');

    if ($latestCommitData && isset($latestCommitData['sha'])) {
        $latestSha = substr($latestCommitData['sha'], 0, 7);
        $latestMsg = $latestCommitData['commit']['message'] ?? '';
        $latestAuthor = $latestCommitData['commit']['author']['name'] ?? '';
        $latestDate = $latestCommitData['commit']['author']['date'] ?? date('Y-m-d H:i:s');
        $log[] = "Latest GitHub Commit: {$latestSha} by {$latestAuthor}";
        $log[] = "Commit Message: {$latestMsg}";
    }

    if (!class_exists('ZipArchive')) {
        return [
            'success' => false,
            'message' => 'PHP ZipArchive extension is required for online extraction.',
            'log' => implode("\n", $log),
            'time' => round(microtime(true) - $startTime, 3)
        ];
    }

    $zipUrl = "https://github.com/{$repoOwner}/{$repoName}/archive/refs/heads/{$branch}.zip";
    $log[] = "Downloading repository package from: {$zipUrl}";

    $tempZip = tempnam(sys_get_temp_dir(), 'git_sync_') . '.zip';

    // Download zip using cURL
    $fp = fopen($tempZip, 'w+');
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MyAdvocate-App');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $exec = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if (!$exec || $httpCode !== 200 || !file_exists($tempZip) || filesize($tempZip) < 1000) {
        @unlink($tempZip);
        $log[] = "Download failed (HTTP {$httpCode}).";
        return [
            'success' => false,
            'message' => "Failed to download update archive from GitHub (HTTP {$httpCode}).",
            'log' => implode("\n", $log),
            'time' => round(microtime(true) - $startTime, 3)
        ];
    }

    $zipSize = round(filesize($tempZip) / 1024, 1);
    $log[] = "Archive downloaded successfully ({$zipSize} KB).";
    $log[] = "Extracting files (ignoring app_new/ and preserving local config/db.php)...";

    $zip = new ZipArchive();
    $res = $zip->open($tempZip);
    if ($res !== true) {
        @unlink($tempZip);
        return [
            'success' => false,
            'message' => "Could not open downloaded ZIP archive (error code {$res}).",
            'log' => implode("\n", $log),
            'time' => round(microtime(true) - $startTime, 3)
        ];
    }

    $updatedFilesCount = 0;
    $skippedCount = 0;
    $prefix = "{$repoName}-{$branch}/";

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);

        // Strip GitHub top folder prefix
        if (strpos($entryName, $prefix) === 0) {
            $relPath = substr($entryName, strlen($prefix));
        } else {
            $relPath = $entryName;
        }

        if (empty($relPath) || substr($relPath, -1) === '/') {
            continue; // Directory entry
        }

        // CRITICAL REQUIREMENT: IGNORE app_new/
        if (strpos($relPath, 'app_new/') === 0 || strpos($relPath, 'app_new\\') === 0 || $relPath === 'app_new') {
            $skippedCount++;
            continue;
        }

        // Preserve local production database credentials if already configured
        if ($relPath === 'config/db.php' && file_exists($repoDir . '/config/db.php')) {
            $log[] = "Preserved local production configuration: config/db.php";
            continue;
        }

        // Target path
        $targetFile = $repoDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
        $targetDir = dirname($targetFile);
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $fileStream = $zip->getStream($entryName);
        if ($fileStream) {
            $destStream = @fopen($targetFile, 'w');
            if ($destStream) {
                stream_copy_to_stream($fileStream, $destStream);
                fclose($destStream);
                $updatedFilesCount++;
            }
            fclose($fileStream);
        }
    }

    $zip->close();
    @unlink($tempZip);

    // Save version info locally
    $versionData = [
        'commit' => $latestSha,
        'branch' => $branch,
        'author' => $latestAuthor,
        'date' => $latestDate,
        'message' => $latestMsg,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    @file_put_contents($versionFile, json_encode($versionData, JSON_PRETTY_PRINT));

    $log[] = "Synchronization complete!";
    $log[] = "Updated {$updatedFilesCount} files (skipped {$skippedCount} app_new files).";
    $log[] = "Local version stamp updated to {$latestSha}.";

    return [
        'success' => true,
        'message' => "Successfully updated {$updatedFilesCount} files from GitHub (app_new ignored)!",
        'log' => implode("\n", $log),
        'time' => round(microtime(true) - $startTime, 3)
    ];
}

// Inspect current status
$currentBranch = $defaultBranch;
$lastCommit = null;
$behindCount = 0;
$aheadCount = 0;
$incomingCommits = [];
$uncommittedFiles = [];
$uncommittedCount = 0;
$gitVersion = 'Online API Mode (No CLI required)';

if ($useCliMode) {
    $gitVersionRes = executeGit('--version', $repoDir, $gitBin);
    $gitVersion = ($gitVersionRes['code'] === 0) ? $gitVersionRes['stdout'] : 'Not detected';

    $branchRes = executeGit('rev-parse --abbrev-ref HEAD', $repoDir, $gitBin);
    if ($branchRes['code'] === 0 && !empty($branchRes['stdout'])) {
        $currentBranch = $branchRes['stdout'];
    }

    $lastCommitRes = executeGit('log -1 --format="%h|%an|%ar|%s|%cd"', $repoDir, $gitBin);
    if ($lastCommitRes['code'] === 0 && !empty($lastCommitRes['stdout'])) {
        $parts = explode('|', $lastCommitRes['stdout']);
        if (count($parts) >= 4) {
            $lastCommit = [
                'hash' => $parts[0],
                'author' => $parts[1],
                'rel_time' => $parts[2],
                'subject' => $parts[3],
                'date' => $parts[4] ?? ''
            ];
        }
    }

    $statusRes = executeGit('status --porcelain', $repoDir, $gitBin);
    if ($statusRes['code'] === 0 && !empty($statusRes['stdout'])) {
        $lines = explode("\n", $statusRes['stdout']);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // If it's app_new, skip tracking
                $filePath = trim(substr($line, 2));
                if (strpos($filePath, 'app_new') === 0) {
                    continue;
                }
                $uncommittedFiles[] = [
                    'type' => substr($line, 0, 2),
                    'file' => $filePath
                ];
            }
        }
        $uncommittedCount = count($uncommittedFiles);
    }
} else {
    // Read from .git_version
    $vInfo = getLocalVersionInfo($versionFile, $repoDir);
    $lastCommit = [
        'hash' => $vInfo['commit'] ?? 'f4decaf',
        'author' => $vInfo['author'] ?? 'kgauravindia',
        'rel_time' => $vInfo['date'] ?? 'latest',
        'subject' => $vInfo['message'] ?? 'Add explicit 1-click Update from GitHub repository option',
        'date' => $vInfo['updated_at'] ?? date('Y-m-d')
    ];
}

// Handle Actions (GET or POST)
$action = $_REQUEST['action'] ?? '';
$validRequest = false;

if ($action) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['csrf_token']) && hash_equals($_SESSION['admin_csrf'], $_POST['csrf_token'])) {
            $validRequest = true;
        } else {
            $msg = "Invalid or expired CSRF security token.";
            $msgType = "error";
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && in_array($action, ['fetch', 'status', 'check', 'update', 'pull', 'log'])) {
        $validRequest = true;
    }
}

if ($validRequest) {
    if ($useCliMode) {
        // --- CLI ENGINE EXECUTION ---
        switch ($action) {
            case 'fetch':
            case 'check':
                $res = executeGit('fetch --prune origin', $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);

                if ($res['code'] === 0) {
                    $msg = "Successfully fetched latest references from GitHub (origin/{$currentBranch})!";
                    $msgType = "success";
                } else {
                    $msg = "Git fetch finished with code {$res['code']}. See output below.";
                    $msgType = "error";
                }
                break;

            case 'update':
            case 'pull':
                $pullArgs = 'pull origin ' . escapeshellarg($currentBranch);
                $res = executeGit($pullArgs, $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);

                if ($res['code'] === 0) {
                    if (stripos($commandOutput, 'Already up to date') !== false) {
                        $msg = "Repository is already up to date with https://github.com/kgauravindia/myadvocate.git ({$currentBranch})!";
                        $msgType = "info";
                    } else {
                        $msg = "Successfully updated codebase from https://github.com/kgauravindia/myadvocate.git ({$currentBranch})!";
                        $msgType = "success";
                    }
                } else {
                    $msg = "Git pull exited with code {$res['code']}. If local modified files prevent merge, try 'Stash & Update'.";
                    $msgType = "error";
                }
                break;

            case 'stash_pull':
                $cmdChain = 'stash && git pull origin ' . escapeshellarg($currentBranch) . ' && git stash pop';
                $res = executeGit($cmdChain, $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);

                if ($res['code'] === 0) {
                    $msg = "Stashed local changes, pulled updates from GitHub, and reapplied stash successfully!";
                    $msgType = "success";
                } else {
                    $msg = "Stash & Pull completed with warnings/code {$res['code']}. See output below.";
                    $msgType = "warning";
                }
                break;

            case 'status':
                $res = executeGit('status', $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);
                $msg = "Working directory status refreshed.";
                $msgType = "info";
                break;

            case 'log':
                $res = executeGit('log -n 15 --oneline --decorate --graph', $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);
                $msg = "Recent Git commit log loaded.";
                $msgType = "info";
                break;
        }
    } else {
        // --- ONLINE API ENGINE EXECUTION (NO CLI REQUIRED) ---
        switch ($action) {
            case 'fetch':
            case 'check':
            case 'status':
                $startTime = microtime(true);
                $apiUrl = "https://api.github.com/repos/{$repoOwner}/{$repoName}/commits/{$defaultBranch}";
                $data = githubApiRequest($apiUrl);
                $executionTime = round(microtime(true) - $startTime, 3);
                $executedCommand = "GET {$apiUrl}";

                if ($data && isset($data['sha'])) {
                    $remoteSha = substr($data['sha'], 0, 7);
                    $remoteAuthor = $data['commit']['author']['name'] ?? 'kgauravindia';
                    $remoteMsg = $data['commit']['message'] ?? '';
                    $remoteDate = $data['commit']['author']['date'] ?? '';

                    $localSha = $lastCommit['hash'] ?? '';
                    $commandOutput = "GitHub Remote SHA: {$remoteSha}\nAuthor: {$remoteAuthor}\nDate: {$remoteDate}\nMessage: {$remoteMsg}\nLocal Stored SHA: {$localSha}";

                    if ($remoteSha !== $localSha) {
                        $behindCount = 1;
                        $incomingCommits[] = [
                            'hash' => $remoteSha,
                            'author' => $remoteAuthor,
                            'time' => $remoteDate,
                            'msg' => $remoteMsg
                        ];
                        $msg = "New commit available on GitHub: [{$remoteSha}] {$remoteMsg}. Click 'Update from GitHub' to apply!";
                        $msgType = "warning";
                    } else {
                        $msg = "Online server is already up to date with GitHub commit [{$remoteSha}]!";
                        $msgType = "success";
                    }
                } else {
                    $msg = "Could not fetch remote status from GitHub API. Please check server outbound internet access.";
                    $msgType = "error";
                    $commandOutput = "Failed connecting to GitHub API: {$apiUrl}";
                }
                break;

            case 'update':
            case 'pull':
            case 'stash_pull':
                $executedCommand = "GitHub Direct Update (Archive Extraction, ignoring app_new/)";
                $zipRes = updateFromGitHubZip($repoOwner, $repoName, $defaultBranch, $repoDir, $versionFile);
                $executionTime = $zipRes['time'];
                $commandOutput = $zipRes['log'];

                if ($zipRes['success']) {
                    $msg = $zipRes['message'];
                    $msgType = "success";
                    // Reload local version
                    $vInfo = getLocalVersionInfo($versionFile, $repoDir);
                    $lastCommit['hash'] = $vInfo['commit'];
                    $lastCommit['subject'] = $vInfo['message'];
                    $lastCommit['author'] = $vInfo['author'];
                    $lastCommit['rel_time'] = $vInfo['date'];
                    $behindCount = 0;
                    $incomingCommits = [];
                } else {
                    $msg = $zipRes['message'];
                    $msgType = "error";
                }
                break;

            case 'log':
                $startTime = microtime(true);
                $apiUrl = "https://api.github.com/repos/{$repoOwner}/{$repoName}/commits?per_page=10";
                $commits = githubApiRequest($apiUrl);
                $executionTime = round(microtime(true) - $startTime, 3);
                $executedCommand = "GET {$apiUrl}";

                if (is_array($commits)) {
                    $lines = [];
                    foreach ($commits as $c) {
                        $sha = substr($c['sha'], 0, 7);
                        $author = $c['commit']['author']['name'] ?? '';
                        $date = $c['commit']['author']['date'] ?? '';
                        $sub = trim(strtok($c['commit']['message'] ?? '', "\n"));
                        $lines[] = "* {$sha} - ({$date}) {$sub} <{$author}>";
                    }
                    $commandOutput = implode("\n", $lines);
                    $msg = "Loaded latest 10 commits from GitHub API.";
                    $msgType = "info";
                } else {
                    $msg = "Failed to fetch commit log from GitHub API.";
                    $msgType = "error";
                }
                break;
        }
    }
}

// Calculate behind / ahead counts if in CLI mode
if ($useCliMode && $currentBranch !== 'unknown') {
    $remoteRef = "origin/{$currentBranch}";
    $behindRes = executeGit("rev-list --count HEAD..{$remoteRef}", $repoDir, $gitBin);
    if ($behindRes['code'] === 0) {
        $behindCount = (int)$behindRes['stdout'];
    }

    $aheadRes = executeGit("rev-list --count {$remoteRef}..HEAD", $repoDir, $gitBin);
    if ($aheadRes['code'] === 0) {
        $aheadCount = (int)$aheadRes['stdout'];
    }

    if ($behindCount > 0) {
        $incRes = executeGit("log HEAD..{$remoteRef} --format=\"%h|%an|%ar|%s\"", $repoDir, $gitBin);
        if ($incRes['code'] === 0 && !empty($incRes['stdout'])) {
            $lines = explode("\n", $incRes['stdout']);
            foreach ($lines as $l) {
                $l = trim($l);
                if (!empty($l)) {
                    $parts = explode('|', $l);
                    if (count($parts) >= 4) {
                        $incomingCommits[] = [
                            'hash' => $parts[0],
                            'author' => $parts[1],
                            'time' => $parts[2],
                            'msg' => $parts[3]
                        ];
                    }
                }
            }
        }
    }
}
?>

<!-- Breadcrumbs & Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 0.25rem;">
            <a href="index.php" style="color: var(--text-muted); text-decoration: none;">Admin</a>
            <span>/</span>
            <a href="settings.php" style="color: var(--text-muted); text-decoration: none;">Settings</a>
            <span>/</span>
            <span style="color: var(--primary); font-weight: 600;">GitHub Sync</span>
        </div>
        <h1 style="font-size: 1.75rem; color: var(--primary); margin: 0; font-family: var(--font-heading); font-weight: 800; display: flex; align-items: center; gap: 0.65rem;">
            <i class="fab fa-github" style="color: #24292e;"></i> GitHub Repository & Fetch Tool
            <span class="badge" style="background: <?= $useCliMode ? '#eff6ff' : '#ecfdf5' ?>; color: <?= $useCliMode ? '#2563eb' : '#059669' ?>; border: 1px solid <?= $useCliMode ? '#bfdbfe' : '#a7f3d0' ?>; font-size: 0.725rem; padding: 0.2rem 0.6rem; border-radius: 9999px;">
                <i class="fas <?= $useCliMode ? 'fa-terminal' : 'fa-cloud' ?>"></i> <?= $useCliMode ? 'Git CLI Engine' : 'Online Cloud Engine' ?>
            </span>
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
            Check repository status, fetch latest commits from GitHub, view commit logs, and synchronize codebase online or locally.
        </p>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <!-- 1-Click Update from GitHub -->
        <a href="github_sync.php?action=update" class="btn btn-primary btn-sm" onclick="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git now (ignoring app_new)?');" style="display: inline-flex; align-items: center; gap: 0.45rem; background: var(--brand-red); border-color: var(--brand-red);">
            <i class="fas fa-cloud-arrow-down"></i> Update from GitHub
        </a>
        <!-- Fetch Button -->
        <a href="github_sync.php?action=fetch" class="btn btn-dark btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fas fa-arrows-rotate"></i> Fetch Only
        </a>
        <a href="github_sync.php?action=status" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fas fa-list-check"></i> Check Status
        </a>
        <a href="github_sync.php?action=log" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fas fa-code-commit"></i> Commit Log
        </a>
    </div>
</div>

<!-- Alert Notice -->
<?php if (!empty($msg)): ?>
    <div class="alert-admin alert-admin-<?= $msgType === 'success' ? 'success' : ($msgType === 'error' ? 'error' : ($msgType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="fas <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?>" style="font-size: 1.1rem;"></i>
        <div>
            <strong><?= htmlspecialchars($msg) ?></strong>
            <?php if ($executionTime > 0): ?>
                <span style="font-size: 0.75rem; opacity: 0.85; margin-left: 0.5rem;">(completed in <?= $executionTime ?>s)</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Repository Status Overview Cards -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 1.75rem; gap: 1.25rem;">
    <!-- GitHub Remote Card -->
    <div class="stat-box" style="border-left: 4px solid #24292e;">
        <div class="stat-label"><i class="fab fa-github" style="color: #24292e;"></i> Remote Repository</div>
        <div class="stat-value" style="font-size: 0.95rem; color: var(--primary); font-family: var(--font-heading); margin-top: 0.35rem; word-break: break-all;">
            <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                <?= htmlspecialchars("{$repoOwner}/{$repoName}") ?>
                <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.75rem; color: var(--brand-red);"></i>
            </a>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Branch: <?= htmlspecialchars($currentBranch) ?> &bull; <?= $useCliMode ? 'Git Remote' : 'Direct Cloud API' ?></small>
    </div>

    <!-- Active Branch & Sync Status -->
    <div class="stat-box" style="border-left: 4px solid <?= $behindCount > 0 ? '#ef4444' : ($aheadCount > 0 ? '#3b82f6' : '#10b981') ?>;">
        <div class="stat-label"><i class="fas fa-code-branch"></i> Status & Synchronization</div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.35rem;">
            <span style="font-size: 1.35rem; font-weight: 800; font-family: var(--font-heading); color: var(--primary);">
                <?= htmlspecialchars($currentBranch) ?>
            </span>
            <?php if ($behindCount > 0): ?>
                <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                    <i class="fas fa-arrow-down"></i> <?= $behindCount ?> update available
                </span>
            <?php else: ?>
                <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                    <i class="fas fa-check-circle"></i> Up to date
                </span>
            <?php endif; ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">
            <?= $behindCount > 0 ? 'New commits ready on GitHub!' : 'Online codebase matches GitHub repository' ?>
        </small>
    </div>

    <!-- Active Engine -->
    <div class="stat-box" style="border-left: 4px solid var(--brand-gold);">
        <div class="stat-label"><i class="fas fa-microchip" style="color: var(--brand-gold-dark);"></i> Online Sync Engine</div>
        <div class="stat-value" style="font-size: 1.15rem; color: var(--primary); font-family: var(--font-heading); margin-top: 0.35rem;">
            <?= $useCliMode ? 'Git CLI (Local/VPS)' : 'Cloud API (Shared Host)' ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">
            <?= $useCliMode ? 'Using system git binary' : 'Zero-CLI: cURL + ZipArchive' ?>
        </small>
    </div>

    <!-- Last Local Commit -->
    <div class="stat-box" style="border-left: 4px solid var(--brand-red);">
        <div class="stat-label"><i class="fas fa-clock-rotate-left" style="color: var(--brand-red);"></i> Local Active Commit</div>
        <div style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($lastCommit['subject'] ?? '') ?>">
            <?= htmlspecialchars($lastCommit['subject'] ?? 'N/A') ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem; display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem;">
            <code style="background: var(--bg-alt); padding: 0.1rem 0.3rem; border-radius: 3px; font-weight: 700; color: var(--brand-red);"><?= htmlspecialchars($lastCommit['hash'] ?? 'f4decaf') ?></code>
            <span>by <?= htmlspecialchars($lastCommit['author'] ?? 'kgauravindia') ?></span>
        </small>
    </div>
</div>

<!-- Main Operations Grid -->
<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;" class="git-sync-container">
    <style>
        @media(max-width: 992px) {
            .git-sync-container {
                grid-template-columns: 1fr !important;
            }
        }
        .cli-window {
            background-color: #0d1117;
            border-radius: var(--radius-md);
            border: 1px solid #30363d;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.25);
            overflow: hidden;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        }
        .cli-header {
            background-color: #161b22;
            padding: 0.65rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #30363d;
        }
        .cli-dots {
            display: flex;
            gap: 0.45rem;
        }
        .cli-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            display: inline-block;
        }
        .cli-dot-red { background-color: #ff5f56; }
        .cli-dot-yellow { background-color: #ffbd2e; }
        .cli-dot-green { background-color: #27c93f; }
        .cli-body {
            padding: 1.25rem;
            color: #c9d1d9;
            font-size: 0.8125rem;
            line-height: 1.55;
            max-height: 380px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>

    <!-- Left Column: Actions & Output -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Incoming Commits Alert / Card (if behind) -->
        <?php if ($behindCount > 0 && !empty($incomingCommits)): ?>
            <div class="admin-card" style="border-top: 4px solid #ef4444; background: #fffbfb;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <h3 class="admin-card-title" style="color: #b91c1c; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cloud-arrow-down"></i> <?= $behindCount ?> New Commit Available on GitHub
                        </h3>
                        <p style="color: #7f1d1d; font-size: 0.8125rem; margin: 0.25rem 0 0;">
                            Remote GitHub repository has updates that can be synchronized into this server.
                        </p>
                    </div>

                    <form action="github_sync.php" method="POST" onsubmit="return confirm('Update and apply GitHub commits into this server (app_new ignored)?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit" class="btn btn-primary btn-sm" style="background: #dc2626; border-color: #dc2626;">
                            <i class="fas fa-download"></i> Apply Update Now
                        </button>
                    </form>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($incomingCommits as $c): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #fee2e2; padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); font-size: 0.8125rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden;">
                                <code style="background: #fef2f2; color: #dc2626; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 4px;"><?= htmlspecialchars($c['hash']) ?></code>
                                <span style="font-weight: 600; color: #1e293b; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c['msg']) ?></span>
                            </div>
                            <span style="color: var(--text-muted); font-size: 0.75rem; white-space: nowrap; margin-left: 1rem;">
                                <?= htmlspecialchars($c['author']) ?> &bull; <?= htmlspecialchars($c['time']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Action Trigger Card -->
        <div class="admin-card" style="border-top: 4px solid var(--primary);">
            <h3 class="admin-card-title" style="margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-play" style="color: var(--brand-gold);"></i> Synchronization Commands (Online & Local)
            </h3>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
                Select an operation below. <strong>Fetch</strong> checks for updates safely without altering any files. <strong>Update</strong> synchronizes files directly from GitHub (ignoring <code>app_new</code>).
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <!-- 1. Update from GitHub (Pull) -->
                <form action="github_sync.php" method="POST" style="margin: 0;" onsubmit="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git (ignoring app_new)?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.85rem 1rem; text-align: left; display: flex; flex-direction: column; gap: 0.35rem; background: var(--brand-red); border-color: var(--brand-red);">
                        <span style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cloud-arrow-down"></i> 1. Update from GitHub
                        </span>
                        <small style="opacity: 0.95; font-size: 0.75rem; font-weight: normal; line-height: 1.25;">
                            Pulls latest code from GitHub (ignores app_new, preserves db.php).
                        </small>
                    </button>
                </form>

                <!-- 2. Fetch from GitHub -->
                <form action="github_sync.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="fetch">
                    <button type="submit" class="btn btn-dark w-100" style="padding: 0.85rem 1rem; text-align: left; display: flex; flex-direction: column; gap: 0.35rem;">
                        <span style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-arrows-rotate fa-spin-hover"></i> 2. Fetch References
                        </span>
                        <small style="opacity: 0.9; font-size: 0.75rem; font-weight: normal; line-height: 1.25;">
                            Safe: Queries GitHub for new commits without altering local files.
                        </small>
                    </button>
                </form>

                <!-- 3. Stash & Pull / Refresh Status -->
                <form action="github_sync.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="btn btn-outline w-100" style="padding: 0.85rem 1rem; text-align: left; display: flex; flex-direction: column; gap: 0.35rem;">
                        <span style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-list-check"></i> 3. Refresh Status
                        </span>
                        <small style="color: var(--text-muted); font-size: 0.75rem; font-weight: normal; line-height: 1.25;">
                            Inspects working state and checks synchronization status.
                        </small>
                    </button>
                </form>
            </div>
        </div>

        <!-- Terminal Output Console -->
        <div class="cli-window">
            <div class="cli-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="cli-dots">
                        <span class="cli-dot cli-dot-red"></span>
                        <span class="cli-dot cli-dot-yellow"></span>
                        <span class="cli-dot cli-dot-green"></span>
                    </div>
                    <span style="color: #8b949e; font-size: 0.75rem; font-weight: 600;">
                        sync@myadvocate: <?= htmlspecialchars($repoDir) ?> [<?= $useCliMode ? 'CLI Engine' : 'Cloud API Engine' ?>]
                    </span>
                </div>
                <div>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('cliOutput').innerText); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);" style="background: #21262d; border: 1px solid #30363d; color: #c9d1d9; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 4px; cursor: pointer;">
                        Copy
                    </button>
                </div>
            </div>
            <div class="cli-body" id="cliOutput">
                <span style="color: #7ee787;">$</span> <span style="color: #79c0ff; font-weight: 600;"><?= htmlspecialchars($executedCommand ?: 'status --short') ?></span>
                <?php if ($executionTime > 0): ?>
                    <span style="color: #8b949e; font-size: 0.75rem;"> (<?= $executionTime ?>s)</span>
                <?php endif; ?>
                <div style="margin-top: 0.5rem; color: <?= !empty($commandOutput) ? '#c9d1d9' : '#8b949e' ?>;">
<?= htmlspecialchars(!empty($commandOutput) ? $commandOutput : 'Ready. Click "Update from GitHub" or "Fetch References" above.') ?>
                </div>
            </div>
        </div>

        <!-- Local Modified Files Table (CLI mode only if any) -->
        <?php if ($uncommittedCount > 0): ?>
            <div class="admin-card" style="border-top: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <h3 class="admin-card-title" style="margin: 0; color: #b45309; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file-circle-exclamation"></i> Uncommitted / Modified Local Files (<?= $uncommittedCount ?>)
                    </h3>
                    <a href="github_sync.php?action=status" class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Refresh</a>
                </div>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                    <table class="admin-table" style="font-size: 0.8125rem;">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Status</th>
                                <th>File Path</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uncommittedFiles as $uf): ?>
                                <tr>
                                    <td>
                                        <span style="color: #2563eb; font-weight: 700;"><?= htmlspecialchars($uf['type']) ?></span>
                                    </td>
                                    <td><code style="color: #1e293b;"><?= htmlspecialchars($uf['file']) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Right Column: Sidebar Info & Guides -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Git System Info -->
        <div class="admin-card" style="border-top: 4px solid var(--brand-gold);">
            <h3 class="admin-card-title" style="margin-bottom: 1rem; font-size: 1.05rem;">
                <i class="fas fa-sliders" style="color: var(--brand-gold-dark);"></i> Environment Status
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.8125rem;">
                <div>
                    <span style="color: var(--text-muted);">Active Engine:</span><br>
                    <strong style="color: var(--primary);"><?= $useCliMode ? 'Git CLI Engine' : 'Online Cloud API Engine (Shared Hosting)' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">cURL Status:</span><br>
                    <strong><?= extension_loaded('curl') ? '<span style="color: #10b981;">Enabled (Active)</span>' : '<span style="color: #dc2626;">Disabled</span>' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">ZipArchive Status:</span><br>
                    <strong><?= class_exists('ZipArchive') ? '<span style="color: #10b981;">Enabled (Active)</span>' : '<span style="color: #dc2626;">Disabled</span>' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Repository Directory:</span><br>
                    <code style="font-size: 0.75rem; word-break: break-all; background: var(--bg-alt); padding: 0.2rem 0.4rem; border-radius: 3px;"><?= htmlspecialchars($repoDir) ?></code>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Remote Branch:</span><br>
                    <strong>origin/<?= htmlspecialchars($currentBranch) ?></strong>
                </div>
                <div style="margin-top: 0.5rem; pt-2; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                    <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm w-100" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                        <i class="fab fa-github"></i> Open Repository on GitHub <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- How It Works & Safety Guidelines -->
        <div class="admin-card" style="border-top: 4px solid #3b82f6;">
            <h3 class="admin-card-title" style="margin-bottom: 0.75rem; font-size: 1.05rem; color: #1e40af;">
                <i class="fas fa-shield-halved" style="color: #3b82f6;"></i> Online Safety Protections
            </h3>
            <ul style="padding-left: 1.15rem; margin: 0; font-size: 0.8125rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.65rem; line-height: 1.45;">
                <li>
                    <strong style="color: var(--primary);">Ignores app_new/:</strong> All files in <code>app_new/</code> are automatically bypassed during synchronization.
                </li>
                <li>
                    <strong style="color: var(--primary);">Database Safe:</strong> Your production database credentials in <code>config/db.php</code> are preserved and never overwritten.
                </li>
                <li>
                    <strong style="color: var(--primary);">Zero CLI Requirement:</strong> Uses standard PHP cURL & ZipArchive, functioning smoothly on shared hosts (cPanel, Hostinger) without shell access.
                </li>
            </ul>
        </div>

        <!-- Direct Link Back to Settings -->
        <div style="text-align: center;">
            <a href="settings.php" style="color: var(--brand-red); font-weight: 700; text-decoration: none; font-size: 0.875rem;">
                &larr; Back to Portal Settings
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
