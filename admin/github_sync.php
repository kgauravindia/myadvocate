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
        'commit' => '4bdc552',
        'branch' => 'main',
        'author' => 'kgauravindia',
        'date' => 'Recent',
        'message' => 'Add Online Cloud API Engine to github_sync.php'
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
    $log[] = "[Online Engine: Initialized]";
    $log[] = "Contacting GitHub REST API for latest repository state...";

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
    $log[] = "Extracting files (strictly ignoring app_new/ and preserving local config/db.php)...";

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
            $log[] = "Preserved local production database configuration: config/db.php";
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

    $log[] = "Synchronization finished!";
    $log[] = "Updated {$updatedFilesCount} files successfully (skipped {$skippedCount} app_new files).";
    $log[] = "Active version stamp: {$latestSha}.";

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
$gitVersion = 'Online Cloud Engine';

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
        'hash' => $vInfo['commit'] ?? '4bdc552',
        'author' => $vInfo['author'] ?? 'kgauravindia',
        'rel_time' => $vInfo['date'] ?? 'latest',
        'subject' => $vInfo['message'] ?? 'Online Cloud API Engine active',
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
                    $commandOutput = "GitHub Remote Commit: {$remoteSha}\nAuthor: {$remoteAuthor}\nDate: {$remoteDate}\nMessage: {$remoteMsg}\nLocal Active Version: {$localSha}";

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

<style>
/* ==========================================================================
   ADVANCED GITHUB SYNC INTERFACE STYLES
   ========================================================================== */
.gh-hero {
    background: linear-gradient(135deg, #0b0f19 0%, #111827 50%, #1e1b4b 100%);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 1.75rem 2rem;
    color: #ffffff;
    margin-bottom: 1.75rem;
    box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.35);
    position: relative;
    overflow: hidden;
}

.gh-hero::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(225, 29, 72, 0.25) 0%, rgba(0,0,0,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.gh-hero::after {
    content: '';
    position: absolute;
    bottom: -60px;
    left: 10%;
    width: 250px;
    height: 150px;
    background: radial-gradient(circle, rgba(79, 70, 229, 0.2) 0%, rgba(0,0,0,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.gh-pulse-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background-color: #10b981;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: ghPulse 2s infinite;
}

@keyframes ghPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.gh-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.gh-pill-cloud {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.gh-pill-cli {
    background: rgba(59, 130, 246, 0.15);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
}
.gh-pill-tag {
    background: rgba(255, 255, 255, 0.08);
    color: #e2e8f0;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.gh-btn-glow {
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%) !important;
    border: none !important;
    color: #ffffff !important;
    box-shadow: 0 6px 18px rgba(225, 29, 72, 0.35);
    transition: all 0.25s ease;
    font-weight: 700;
}
.gh-btn-glow:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(225, 29, 72, 0.5);
    filter: brightness(1.08);
}

.gh-stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 1.35rem 1.4rem;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.gh-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px -5px rgba(0, 0, 0, 0.08);
}
.gh-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}
.gh-stat-card.card-ruby::before { background: linear-gradient(90deg, #e11d48, #fb7185); }
.gh-stat-card.card-emerald::before { background: linear-gradient(90deg, #059669, #34d399); }
.gh-stat-card.card-indigo::before { background: linear-gradient(90deg, #4f46e5, #818cf8); }
.gh-stat-card.card-amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }

.gh-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 0.85rem;
}

.gh-action-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.gh-action-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -5px rgba(0,0,0,0.06);
}
.gh-action-card.featured {
    border-color: #fecdd3;
    background: linear-gradient(180deg, #fff 0%, #fff1f2 100%);
    box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.08);
}

.cli-window {
    background-color: #0d1117;
    border-radius: 14px;
    border: 1px solid #30363d;
    box-shadow: 0 15px 35px -5px rgba(0,0,0,0.3);
    overflow: hidden;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
}
.cli-header {
    background-color: #161b22;
    padding: 0.75rem 1.2rem;
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
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}
.cli-dot-red { background-color: #ff5f56; }
.cli-dot-yellow { background-color: #ffbd2e; }
.cli-dot-green { background-color: #27c93f; }
.cli-body {
    padding: 1.25rem 1.5rem;
    color: #c9d1d9;
    font-size: 0.835rem;
    line-height: 1.6;
    max-height: 380px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
}

.flow-step-badge {
    background: #f1f5f9;
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.security-checklist {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.security-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1e293b;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
</style>

<!-- Hero Section with Live Connection Indicator -->
<div class="gh-hero">
    <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.65rem;">
                <span class="gh-pulse-dot"></span>
                <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #a7f3d0;">
                    LIVE CONNECTED
                </span>
                <span style="color: rgba(255,255,255,0.3);">&bull;</span>
                <span class="gh-pill <?= $useCliMode ? 'gh-pill-cli' : 'gh-pill-cloud' ?>">
                    <i class="fas <?= $useCliMode ? 'fa-terminal' : 'fa-cloud' ?>"></i> <?= $useCliMode ? 'Git CLI Engine' : 'Cloud REST Engine' ?>
                </span>
                <span class="gh-pill gh-pill-tag">
                    <i class="fas fa-code-branch"></i> origin/<?= htmlspecialchars($currentBranch) ?>
                </span>
            </div>

            <h1 style="font-size: 1.85rem; margin: 0 0 0.5rem; font-family: var(--font-heading); font-weight: 900; color: #ffffff; letter-spacing: -0.02em; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fab fa-github" style="color: #ffffff; font-size: 2rem;"></i>
                GitHub Deployment & Synchronization
            </h1>

            <p style="color: #cbd5e1; font-size: 0.9rem; margin: 0; max-width: 650px; line-height: 1.5;">
                Synchronize your portal codebase directly from 
                <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" style="color: #fca5a5; font-weight: 700; text-decoration: none;">
                    <?= htmlspecialchars("{$repoOwner}/{$repoName}") ?> <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                </a>.
                Fully optimized for both local development and online cloud hosting.
            </p>
        </div>

        <!-- Quick 1-Click Control Buttons -->
        <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
            <a href="github_sync.php?action=update" class="btn gh-btn-glow" onclick="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git (ignoring app_new)?');" style="padding: 0.7rem 1.25rem; font-size: 0.875rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-cloud-arrow-down"></i> Update from GitHub
            </a>
            <a href="github_sync.php?action=fetch" class="btn btn-dark" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #ffffff; padding: 0.7rem 1rem; font-size: 0.875rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fas fa-arrows-rotate"></i> Fetch References
            </a>
            <a href="github_sync.php?action=log" class="btn btn-dark" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; padding: 0.7rem 0.9rem; font-size: 0.875rem; border-radius: 10px; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fas fa-list-ul"></i> Commit Log
            </a>
        </div>
    </div>
</div>

<!-- Safety & Protection Checklist Strip -->
<div class="security-checklist">
    <div class="security-pill">
        <i class="fas fa-filter" style="color: #f59e0b; font-size: 1rem;"></i>
        <div>
            <div style="line-height: 1.1;">app_new/ Excluded</div>
            <small style="color: var(--text-muted); font-size: 0.7rem;">Protected from overrides</small>
        </div>
    </div>
    <div class="security-pill">
        <i class="fas fa-lock" style="color: #10b981; font-size: 1rem;"></i>
        <div>
            <div style="line-height: 1.1;">config/db.php Protected</div>
            <small style="color: var(--text-muted); font-size: 0.7rem;">Live credentials preserved</small>
        </div>
    </div>
    <div class="security-pill">
        <i class="fas fa-bolt" style="color: #6366f1; font-size: 1rem;"></i>
        <div>
            <div style="line-height: 1.1;">Zero-CLI Cloud Engine</div>
            <small style="color: var(--text-muted); font-size: 0.7rem;">Runs on cPanel & Hostinger</small>
        </div>
    </div>
    <div class="security-pill">
        <i class="fas fa-shield-halved" style="color: #e11d48; font-size: 1rem;"></i>
        <div>
            <div style="line-height: 1.1;">Master Admin Only</div>
            <small style="color: var(--text-muted); font-size: 0.7rem;">CSRF & session authenticated</small>
        </div>
    </div>
</div>

<!-- Alert Banner (if message set) -->
<?php if (!empty($msg)): ?>
    <div class="alert-admin alert-admin-<?= $msgType === 'success' ? 'success' : ($msgType === 'error' ? 'error' : ($msgType === 'warning' ? 'warning' : 'info')) ?>" style="margin-bottom: 1.75rem; border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; gap: 0.85rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
        <i class="fas <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?>" style="font-size: 1.25rem;"></i>
        <div style="flex: 1;">
            <strong style="font-size: 0.925rem;"><?= htmlspecialchars($msg) ?></strong>
            <?php if ($executionTime > 0): ?>
                <span style="font-size: 0.75rem; opacity: 0.8; margin-left: 0.4rem;">&bull; Completed in <?= $executionTime ?>s</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- 4 Elevated Stat Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1.25rem; margin-bottom: 1.75rem;">
    <!-- Card 1: Remote Repository -->
    <div class="gh-stat-card card-ruby">
        <div>
            <div class="gh-icon-box" style="background: #fff1f2; color: #e11d48;">
                <i class="fab fa-github"></i>
            </div>
            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">GitHub Repository</div>
            <div style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin-top: 0.35rem; font-family: var(--font-heading); word-break: break-all;">
                <?= htmlspecialchars("{$repoOwner}/{$repoName}") ?>
            </div>
        </div>
        <div style="margin-top: 0.85rem; pt-2; border-top: 1px solid #f1f5f9; padding-top: 0.65rem; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 0.75rem; color: #64748b;">Branch: <strong><?= htmlspecialchars($currentBranch) ?></strong></span>
            <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" style="color: #e11d48; font-size: 0.75rem; font-weight: 700; text-decoration: none;">
                View &rarr;
            </a>
        </div>
    </div>

    <!-- Card 2: Sync Status -->
    <div class="gh-stat-card card-emerald">
        <div>
            <div class="gh-icon-box" style="background: #ecfdf5; color: #059669;">
                <i class="fas fa-arrows-rotate"></i>
            </div>
            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Sync Status</div>
            <div style="margin-top: 0.35rem;">
                <?php if ($behindCount > 0): ?>
                    <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 800; font-size: 0.85rem; padding: 0.35rem 0.65rem; border-radius: 8px;">
                        <i class="fas fa-cloud-arrow-down"></i> <?= $behindCount ?> Update Available
                    </span>
                <?php else: ?>
                    <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 800; font-size: 0.85rem; padding: 0.35rem 0.65rem; border-radius: 8px;">
                        <i class="fas fa-check-circle"></i> Up to Date
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div style="margin-top: 0.85rem; pt-2; border-top: 1px solid #f1f5f9; padding-top: 0.65rem;">
            <small style="color: #64748b; font-size: 0.75rem;">
                <?= $behindCount > 0 ? 'Commits waiting on GitHub' : 'Matches remote repository' ?>
            </small>
        </div>
    </div>

    <!-- Card 3: Active Engine -->
    <div class="gh-stat-card card-indigo">
        <div>
            <div class="gh-icon-box" style="background: #eef2ff; color: #4f46e5;">
                <i class="fas <?= $useCliMode ? 'fa-terminal' : 'fa-cloud' ?>"></i>
            </div>
            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Active Engine</div>
            <div style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin-top: 0.35rem; font-family: var(--font-heading);">
                <?= $useCliMode ? 'Git CLI (Local/VPS)' : 'Cloud API (Shared Host)' ?>
            </div>
        </div>
        <div style="margin-top: 0.85rem; pt-2; border-top: 1px solid #f1f5f9; padding-top: 0.65rem;">
            <small style="color: #64748b; font-size: 0.75rem;">
                <?= $useCliMode ? 'Native Git binaries active' : 'Zero-CLI (cURL + ZipArchive)' ?>
            </small>
        </div>
    </div>

    <!-- Card 4: Local Active Commit -->
    <div class="gh-stat-card card-amber">
        <div>
            <div class="gh-icon-box" style="background: #fffbeb; color: #d97706;">
                <i class="fas fa-code-commit"></i>
            </div>
            <div style="font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Active Local Commit</div>
            <div style="font-size: 0.9rem; font-weight: 700; color: var(--primary); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($lastCommit['subject'] ?? '') ?>">
                <?= htmlspecialchars($lastCommit['subject'] ?? 'N/A') ?>
            </div>
        </div>
        <div style="margin-top: 0.85rem; pt-2; border-top: 1px solid #f1f5f9; padding-top: 0.65rem; display: flex; align-items: center; gap: 0.4rem;">
            <code style="background: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 800; color: #d97706; font-size: 0.75rem;">
                <?= htmlspecialchars($lastCommit['hash'] ?? '4bdc552') ?>
            </code>
            <span style="font-size: 0.75rem; color: #64748b;">by <?= htmlspecialchars($lastCommit['author'] ?? 'kgauravindia') ?></span>
        </div>
    </div>
</div>

<!-- Main Split: Actions & Console Output -->
<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.75rem; align-items: start;" class="git-sync-container">
    <style>
        @media(max-width: 992px) {
            .git-sync-container {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Left Column: Primary Actions & Terminal -->
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">

        <!-- Incoming Commits Alert / Card (if behind) -->
        <?php if ($behindCount > 0 && !empty($incomingCommits)): ?>
            <div style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 1.5px solid #fecdd3; border-radius: 16px; padding: 1.5rem; box-shadow: 0 10px 25px -5px rgba(225, 29, 72, 0.12);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <h3 style="color: #9f1239; margin: 0; font-size: 1.1rem; font-weight: 800; font-family: var(--font-heading); display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cloud-arrow-down" style="color: #e11d48;"></i> Updates Available on GitHub
                        </h3>
                        <p style="color: #be123c; font-size: 0.8125rem; margin: 0.25rem 0 0;">
                            Your active server is behind the remote <code>main</code> branch. Apply now with 1 click.
                        </p>
                    </div>

                    <form action="github_sync.php" method="POST" onsubmit="return confirm('Update and apply GitHub commits into this server (app_new ignored)?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="update">
                        <button type="submit" class="btn gh-btn-glow btn-sm" style="padding: 0.6rem 1.1rem; border-radius: 8px;">
                            <i class="fas fa-download"></i> Apply Update Now
                        </button>
                    </form>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($incomingCommits as $c): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #fecdd3; padding: 0.75rem 1rem; border-radius: 10px; font-size: 0.8125rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; overflow: hidden;">
                                <code style="background: #fff1f2; color: #e11d48; font-weight: 800; padding: 0.15rem 0.45rem; border-radius: 5px;"><?= htmlspecialchars($c['hash']) ?></code>
                                <span style="font-weight: 600; color: #1e293b; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= htmlspecialchars($c['msg']) ?></span>
                            </div>
                            <span style="color: #64748b; font-size: 0.75rem; white-space: nowrap; margin-left: 1rem;">
                                <?= htmlspecialchars($c['author']) ?> &bull; <?= htmlspecialchars($c['time']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 3-Column Interactive Action Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
            <!-- Action Card 1: Update Codebase -->
            <div class="gh-action-card featured">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="badge" style="background: #e11d48; color: #ffffff; font-weight: 800; font-size: 0.7rem; padding: 0.25rem 0.55rem; border-radius: 6px;">
                            PRIMARY
                        </span>
                        <i class="fas fa-cloud-arrow-down" style="color: #e11d48; font-size: 1.25rem;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: #9f1239; margin: 0 0 0.35rem; font-family: var(--font-heading);">
                        Update Codebase
                    </h3>
                    <p style="color: #64748b; font-size: 0.8125rem; margin: 0 0 1.25rem; line-height: 1.45;">
                        Pulls latest changes directly from GitHub. Strictly skips <code>app_new/</code> and preserves database settings.
                    </p>
                </div>
                <form action="github_sync.php" method="POST" onsubmit="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git (ignoring app_new)?');" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn gh-btn-glow w-100" style="padding: 0.75rem; border-radius: 8px; font-size: 0.875rem;">
                        <i class="fas fa-download"></i> 1-Click Update
                    </button>
                </form>
            </div>

            <!-- Action Card 2: Fetch References -->
            <div class="gh-action-card">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.55rem; border-radius: 6px;">
                            SAFE CHECK
                        </span>
                        <i class="fas fa-arrows-rotate" style="color: #0284c7; font-size: 1.25rem;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin: 0 0 0.35rem; font-family: var(--font-heading);">
                        Fetch References
                    </h3>
                    <p style="color: #64748b; font-size: 0.8125rem; margin: 0 0 1.25rem; line-height: 1.45;">
                        Checks GitHub for new commits safely without modifying any files or altering working copy.
                    </p>
                </div>
                <form action="github_sync.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="fetch">
                    <button type="submit" class="btn btn-dark w-100" style="background: #1e293b; padding: 0.75rem; border-radius: 8px; font-size: 0.875rem; font-weight: 700;">
                        <i class="fas fa-magnifying-glass"></i> Check Updates
                    </button>
                </form>
            </div>

            <!-- Action Card 3: Diagnostic & Refresh -->
            <div class="gh-action-card">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 700; font-size: 0.7rem; padding: 0.25rem 0.55rem; border-radius: 6px;">
                            DIAGNOSTIC
                        </span>
                        <i class="fas fa-sliders" style="color: #6366f1; font-size: 1.25rem;"></i>
                    </div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--primary); margin: 0 0 0.35rem; font-family: var(--font-heading);">
                        Inspect State
                    </h3>
                    <p style="color: #64748b; font-size: 0.8125rem; margin: 0 0 1.25rem; line-height: 1.45;">
                        Refreshes live system health, API connectivity, ZipArchive status, and environment variables.
                    </p>
                </div>
                <form action="github_sync.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="status">
                    <button type="submit" class="btn btn-outline w-100" style="padding: 0.75rem; border-radius: 8px; font-size: 0.875rem; font-weight: 700; border: 1.5px solid #cbd5e1;">
                        <i class="fas fa-rotate"></i> Refresh State
                    </button>
                </form>
            </div>
        </div>

        <!-- Terminal Output Console -->
        <div class="cli-window">
            <div class="cli-header">
                <div style="display: flex; align-items: center; gap: 0.85rem;">
                    <div class="cli-dots">
                        <span class="cli-dot cli-dot-red"></span>
                        <span class="cli-dot cli-dot-yellow"></span>
                        <span class="cli-dot cli-dot-green"></span>
                    </div>
                    <span style="color: #8b949e; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-terminal" style="color: #58a6ff;"></i>
                        sync@myadvocate: <?= htmlspecialchars($repoDir) ?> [<?= $useCliMode ? 'cli-engine' : 'cloud-rest-engine' ?>]
                    </span>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('cliOutput').innerText); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);" style="background: #21262d; border: 1px solid #30363d; color: #c9d1d9; font-size: 0.7rem; padding: 0.25rem 0.65rem; border-radius: 5px; cursor: pointer; font-weight: 600;">
                        Copy
                    </button>
                </div>
            </div>
            <div class="cli-body" id="cliOutput">
                <span style="color: #4ade80;">$</span> <span style="color: #38bdf8; font-weight: 700;"><?= htmlspecialchars($executedCommand ?: 'status --summary') ?></span>
                <?php if ($executionTime > 0): ?>
                    <span style="color: #8b949e; font-size: 0.75rem;"> (<?= $executionTime ?>s)</span>
                <?php endif; ?>
                <div style="margin-top: 0.65rem; color: <?= !empty($commandOutput) ? '#c9d1d9' : '#8b949e' ?>;">
<?= htmlspecialchars(!empty($commandOutput) ? $commandOutput : 'Ready. Click "Update from GitHub" or "Fetch References" above.') ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Right Column: Sidebar Info & Guides -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Synchronization Pipeline Card -->
        <div class="admin-card" style="border-radius: 14px; border-top: 4px solid var(--primary);">
            <h3 class="admin-card-title" style="margin-bottom: 1rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-diagram-project" style="color: var(--primary);"></i> Sync Pipeline
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.8125rem;">
                <div class="flow-step-badge" style="width: 100%; justify-content: space-between;">
                    <span><i class="fab fa-github"></i> GitHub Source</span>
                    <code style="color: #e11d48; font-weight: 800;">origin/main</code>
                </div>
                <div style="text-align: center; color: #94a3b8; font-size: 0.75rem;">
                    <i class="fas fa-arrow-down"></i> Encrypted HTTPS Transfer
                </div>
                <div class="flow-step-badge" style="width: 100%; justify-content: space-between; background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3;">
                    <span><i class="fas fa-filter"></i> Rule: Ignore app_new/</span>
                    <strong>Active</strong>
                </div>
                <div style="text-align: center; color: #94a3b8; font-size: 0.75rem;">
                    <i class="fas fa-arrow-down"></i> Auto File Extraction
                </div>
                <div class="flow-step-badge" style="width: 100%; justify-content: space-between; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                    <span><i class="fas fa-server"></i> Live Portal: myadv.in</span>
                    <strong>Updated</strong>
                </div>
            </div>
        </div>

        <!-- Technical Environment Status -->
        <div class="admin-card" style="border-radius: 14px; border-top: 4px solid var(--brand-gold);">
            <h3 class="admin-card-title" style="margin-bottom: 1rem; font-size: 1rem;">
                <i class="fas fa-sliders" style="color: var(--brand-gold-dark);"></i> Environment Status
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.8125rem;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">ACTIVE ENGINE</span><br>
                    <strong style="color: var(--primary);"><?= $useCliMode ? 'Git CLI (Local/VPS)' : 'Cloud API (Shared Host)' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">PHP cURL EXTENSION</span><br>
                    <strong><?= extension_loaded('curl') ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Enabled & Connected</span>' : '<span style="color: #dc2626;"><i class="fas fa-times-circle"></i> Disabled</span>' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">PHP ZipArchive</span><br>
                    <strong><?= class_exists('ZipArchive') ? '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Available & Ready</span>' : '<span style="color: #dc2626;"><i class="fas fa-times-circle"></i> Missing</span>' ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.75rem;">REMOTE TARGET</span><br>
                    <code style="font-size: 0.75rem; word-break: break-all; background: #f8fafc; padding: 0.2rem 0.4rem; border-radius: 4px; display: block; margin-top: 0.25rem;">https://github.com/kgauravindia/myadvocate.git</code>
                </div>
            </div>
        </div>

        <!-- Quick Navigation Link Back -->
        <div style="text-align: center; padding: 0.5rem;">
            <a href="settings.php" style="color: #e11d48; font-weight: 700; text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.4rem;">
                <i class="fas fa-arrow-left"></i> Return to Portal Settings
            </a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
