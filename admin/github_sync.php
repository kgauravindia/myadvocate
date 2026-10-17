<?php
// admin/github_sync.php - Fetch & Synchronize with GitHub Repository
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

// Check if Git executable is callable
function findGitExecutable() {
    $candidates = [
        'git',
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
    return 'git';
}

$gitBin = findGitExecutable();

// Runner function for git commands
function executeGit($args, $repoDir, $gitBin = 'git') {
    $startTime = microtime(true);
    $descriptorspec = [
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];

    $cmd = escapeshellcmd($gitBin) . ' -C ' . escapeshellarg($repoDir) . ' ' . $args;
    $process = @proc_open($cmd, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        return [
            'code' => -1,
            'stdout' => '',
            'stderr' => 'Failed to initialize process via proc_open(). Check PHP permissions.',
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

// Inspect basic repository information
$hasGit = is_dir($repoDir . DIRECTORY_SEPARATOR . '.git');
$gitVersionRes = executeGit('--version', $repoDir, $gitBin);
$gitVersion = ($gitVersionRes['code'] === 0) ? $gitVersionRes['stdout'] : 'Not detected';

$currentBranch = 'unknown';
$remoteUrl = '';
$remoteName = 'origin';
$lastCommit = null;
$behindCount = 0;
$aheadCount = 0;
$incomingCommits = [];
$uncommittedFiles = [];
$uncommittedCount = 0;

if ($hasGit && $gitVersionRes['code'] === 0) {
    // Current branch
    $branchRes = executeGit('rev-parse --abbrev-ref HEAD', $repoDir, $gitBin);
    if ($branchRes['code'] === 0 && !empty($branchRes['stdout'])) {
        $currentBranch = $branchRes['stdout'];
    }

    // Remote URL
    $remoteRes = executeGit('remote get-url ' . escapeshellarg($remoteName), $repoDir, $gitBin);
    if ($remoteRes['code'] === 0) {
        $remoteUrl = $remoteRes['stdout'];
    }

    // Last commit details
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

    // Working directory status (uncommitted/modified)
    $statusRes = executeGit('status --porcelain', $repoDir, $gitBin);
    if ($statusRes['code'] === 0 && !empty($statusRes['stdout'])) {
        $lines = explode("\n", $statusRes['stdout']);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $statusType = substr($line, 0, 2);
                $filePath = trim(substr($line, 2));
                $uncommittedFiles[] = [
                    'type' => $statusType,
                    'file' => $filePath
                ];
            }
        }
        $uncommittedCount = count($uncommittedFiles);
    }
}

// Handle Actions (GET or POST)
$action = $_REQUEST['action'] ?? '';
$confirmed = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

if ($action && $hasGit) {
    // For POST verify CSRF; for GET allow safe inspection and confirmed actions
    $validRequest = false;
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

    if ($validRequest) {
        switch ($action) {
            case 'fetch':
            case 'check':
                $res = executeGit('fetch --prune ' . escapeshellarg($remoteName), $repoDir, $gitBin);
                $executedCommand = $res['cmd'];
                $executionTime = $res['time'];
                $commandOutput = trim($res['stdout'] . "\n" . $res['stderr']);

                if ($res['code'] === 0) {
                    $msg = "Successfully fetched latest references from GitHub ({$remoteName}/{$currentBranch})!";
                    $msgType = "success";
                } else {
                    $msg = "Git fetch finished with code {$res['code']}. See output below.";
                    $msgType = "error";
                }
                break;

            case 'update':
            case 'pull':
                // Pull latest changes from remote GitHub repository
                $pullArgs = 'pull ' . escapeshellarg($remoteName) . ' ' . escapeshellarg($currentBranch);
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
                // Stash local changes, pull, then pop stash
                $cmdChain = 'stash && git pull ' . escapeshellarg($remoteName) . ' ' . escapeshellarg($currentBranch) . ' && git stash pop';
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

        // Re-read status after action
        $statusRes = executeGit('status --porcelain', $repoDir, $gitBin);
        $uncommittedFiles = [];
        if ($statusRes['code'] === 0 && !empty($statusRes['stdout'])) {
            $lines = explode("\n", $statusRes['stdout']);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $uncommittedFiles[] = [
                        'type' => substr($line, 0, 2),
                        'file' => trim(substr($line, 2))
                    ];
                }
            }
        }
        $uncommittedCount = count($uncommittedFiles);

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
    }
}

// Calculate behind / ahead counts after potential fetch
if ($hasGit && $currentBranch !== 'unknown') {
    $remoteRef = "{$remoteName}/{$currentBranch}";
    $behindRes = executeGit("rev-list --count HEAD..{$remoteRef}", $repoDir, $gitBin);
    if ($behindRes['code'] === 0) {
        $behindCount = (int)$behindRes['stdout'];
    }

    $aheadRes = executeGit("rev-list --count {$remoteRef}..HEAD", $repoDir, $gitBin);
    if ($aheadRes['code'] === 0) {
        $aheadCount = (int)$aheadRes['stdout'];
    }

    // If behind, get list of incoming commits
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

// Convert GitHub SSH/HTTPS URL to clean web link
$githubWebUrl = '';
if ($remoteUrl) {
    $clean = $remoteUrl;
    if (strpos($clean, 'git@github.com:') === 0) {
        $clean = 'https://github.com/' . substr($clean, 15);
    }
    if (substr($clean, -4) === '.git') {
        $clean = substr($clean, 0, -4);
    }
    $githubWebUrl = $clean;
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
        </h1>
        <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.25rem 0 0;">
            Check repository status, fetch latest commits from GitHub, view commit logs, and synchronize codebase seamlessly.
        </p>
    </div>

    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <!-- 1-Click Update from GitHub -->
        <a href="github_sync.php?action=update" class="btn btn-primary btn-sm" onclick="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git now?');" style="display: inline-flex; align-items: center; gap: 0.45rem; background: var(--brand-red); border-color: var(--brand-red);">
            <i class="fas fa-cloud-arrow-down"></i> Update from GitHub
        </a>
        <!-- Fetch Button (GET quick action) -->
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
    <div class="alert-admin alert-admin-<?= $msgType === 'success' ? 'success' : ($msgType === 'error' ? 'error' : 'info') ?>" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
        <i class="fas <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-info') ?>" style="font-size: 1.1rem;"></i>
        <div>
            <strong><?= htmlspecialchars($msg) ?></strong>
            <?php if ($executionTime > 0): ?>
                <span style="font-size: 0.75rem; opacity: 0.85; margin-left: 0.5rem;">(executed in <?= $executionTime ?>s)</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php if (!$hasGit): ?>
    <div class="alert-admin alert-admin-error" style="margin-bottom: 1.5rem;">
        <i class="fas fa-circle-exclamation"></i>
        <div>
            <strong>Git repository not detected (.git directory missing in <?= htmlspecialchars($repoDir) ?>).</strong>
            <p style="margin: 0.25rem 0 0; font-size: 0.8125rem;">Please initialize Git or ensure the portal is cloned from a Git repository.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Repository Status Overview Cards -->
<div class="profile-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 1.75rem; gap: 1.25rem;">
    <!-- GitHub Remote Card -->
    <div class="stat-box" style="border-left: 4px solid #24292e;">
        <div class="stat-label"><i class="fab fa-github" style="color: #24292e;"></i> Remote Repository</div>
        <div class="stat-value" style="font-size: 1rem; color: var(--primary); font-family: var(--font-heading); margin-top: 0.35rem; word-break: break-all;">
            <?php if ($githubWebUrl): ?>
                <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;">
                    https://github.com/kgauravindia/myadvocate.git
                    <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.75rem; color: var(--brand-red);"></i>
                </a>
            <?php else: ?>
                <?= htmlspecialchars($remoteUrl ?: 'https://github.com/kgauravindia/myadvocate.git') ?>
            <?php endif; ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Remote: <?= htmlspecialchars($remoteName) ?> &bull; Branch: <?= htmlspecialchars($currentBranch) ?></small>
    </div>

    <!-- Active Branch & Sync Status -->
    <div class="stat-box" style="border-left: 4px solid <?= $behindCount > 0 ? '#ef4444' : ($aheadCount > 0 ? '#3b82f6' : '#10b981') ?>;">
        <div class="stat-label"><i class="fas fa-code-branch"></i> Current Branch & Sync</div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.35rem;">
            <span style="font-size: 1.35rem; font-weight: 800; font-family: var(--font-heading); color: var(--primary);">
                <?= htmlspecialchars($currentBranch) ?>
            </span>
            <?php if ($behindCount > 0): ?>
                <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                    <i class="fas fa-arrow-down"></i> <?= $behindCount ?> commit<?= $behindCount > 1 ? 's' : '' ?> behind
                </span>
            <?php elseif ($aheadCount > 0): ?>
                <span class="badge" style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                    <i class="fas fa-arrow-up"></i> <?= $aheadCount ?> commit<?= $aheadCount > 1 ? 's' : '' ?> ahead
                </span>
            <?php else: ?>
                <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; font-weight: 700; font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px;">
                    <i class="fas fa-check-circle"></i> Up to date
                </span>
            <?php endif; ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">
            <?= $behindCount > 0 ? 'New updates available on GitHub!' : 'Local branch matches remote origin' ?>
        </small>
    </div>

    <!-- Working Directory Changes -->
    <div class="stat-box" style="border-left: 4px solid <?= $uncommittedCount > 0 ? '#f59e0b' : '#10b981' ?>;">
        <div class="stat-label"><i class="fas fa-file-pen" style="color: #f59e0b;"></i> Local Working Tree</div>
        <div class="stat-value" style="font-size: 1.35rem; color: <?= $uncommittedCount > 0 ? '#d97706' : '#10b981' ?>; font-family: var(--font-heading); margin-top: 0.35rem;">
            <?= $uncommittedCount > 0 ? "{$uncommittedCount} modified files" : 'Clean working tree' ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">
            <?= $uncommittedCount > 0 ? 'Uncommitted changes detected' : 'No unstaged/uncommitted files' ?>
        </small>
    </div>

    <!-- Last Local Commit -->
    <div class="stat-box" style="border-left: 4px solid var(--brand-red);">
        <div class="stat-label"><i class="fas fa-clock-rotate-left" style="color: var(--brand-red);"></i> Current Local Commit</div>
        <div style="font-size: 0.95rem; font-weight: 700; color: var(--primary); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($lastCommit['subject'] ?? '') ?>">
            <?= htmlspecialchars($lastCommit['subject'] ?? 'N/A') ?>
        </div>
        <small style="color: var(--text-muted); font-size: 0.75rem; display: flex; align-items: center; gap: 0.4rem; margin-top: 0.2rem;">
            <code style="background: var(--bg-alt); padding: 0.1rem 0.3rem; border-radius: 3px; font-weight: 700; color: var(--brand-red);"><?= htmlspecialchars($lastCommit['hash'] ?? '0000000') ?></code>
            <span>by <?= htmlspecialchars($lastCommit['author'] ?? 'author') ?> &bull; <?= htmlspecialchars($lastCommit['rel_time'] ?? '') ?></span>
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
                            <i class="fas fa-cloud-arrow-down"></i> <?= $behindCount ?> New Commit<?= $behindCount > 1 ? 's' : '' ?> Available on GitHub
                        </h3>
                        <p style="color: #7f1d1d; font-size: 0.8125rem; margin: 0.25rem 0 0;">
                            Remote branch has changes that have not yet been pulled into this server.
                        </p>
                    </div>

                    <form action="github_sync.php" method="POST" onsubmit="return confirm('Pull and apply remote commits into your active codebase now?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="pull">
                        <button type="submit" class="btn btn-primary btn-sm" style="background: #dc2626; border-color: #dc2626;">
                            <i class="fas fa-download"></i> Pull Latest Commits Now
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
                <i class="fas fa-play" style="color: var(--brand-gold);"></i> Git Commands & Synchronization Actions
            </h3>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.25rem;">
                Select an operation below. <strong>Fetch</strong> is 100% safe and non-destructive — it downloads remote refs and metadata without changing your local files.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <!-- 1. Update from GitHub (Pull) -->
                <form action="github_sync.php" method="POST" style="margin: 0;" onsubmit="return confirm('Update and pull latest code from https://github.com/kgauravindia/myadvocate.git?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 0.85rem 1rem; text-align: left; display: flex; flex-direction: column; gap: 0.35rem; background: var(--brand-red); border-color: var(--brand-red);">
                        <span style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-cloud-arrow-down"></i> 1. Update from GitHub
                        </span>
                        <small style="opacity: 0.95; font-size: 0.75rem; font-weight: normal; line-height: 1.25;">
                            Pulls latest changes directly from https://github.com/kgauravindia/myadvocate.git.
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
                            Safe: Checks GitHub for latest commits without altering local files.
                        </small>
                    </button>
                </form>

                <!-- 3. Stash & Pull -->
                <form action="github_sync.php" method="POST" style="margin: 0;" onsubmit="return confirm('Stash local changes and pull from GitHub? This temporarily stashes unstaged work.');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="stash_pull">
                    <button type="submit" class="btn btn-outline w-100" style="padding: 0.85rem 1rem; text-align: left; display: flex; flex-direction: column; gap: 0.35rem;">
                        <span style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-shield-halved"></i> 3. Safe Stash & Pull
                        </span>
                        <small style="opacity: 0.85; font-size: 0.75rem; font-weight: normal; line-height: 1.25;">
                            Stashes local edits first, pulls latest remote, then pops stash.
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
                        terminal@myadvocate: <?= htmlspecialchars($repoDir) ?>
                    </span>
                </div>
                <div>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('cliOutput').innerText); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 1500);" style="background: #21262d; border: 1px solid #30363d; color: #c9d1d9; font-size: 0.7rem; padding: 0.2rem 0.55rem; border-radius: 4px; cursor: pointer;">
                        Copy
                    </button>
                </div>
            </div>
            <div class="cli-body" id="cliOutput">
                <span style="color: #7ee787;">$</span> <span style="color: #79c0ff; font-weight: 600;"><?= htmlspecialchars($executedCommand ?: 'git status --short') ?></span>
                <?php if ($executionTime > 0): ?>
                    <span style="color: #8b949e; font-size: 0.75rem;"> (<?= $executionTime ?>s)</span>
                <?php endif; ?>
                <div style="margin-top: 0.5rem; color: <?= !empty($commandOutput) ? '#c9d1d9' : '#8b949e' ?>;">
<?= htmlspecialchars(!empty($commandOutput) ? $commandOutput : ($statusRes['stdout'] ?? 'Clean working tree. Ready for Git operations.')) ?>
                </div>
            </div>
        </div>

        <!-- Local Modified Files Table (if any) -->
        <?php if ($uncommittedCount > 0): ?>
            <div class="admin-card" style="border-top: 4px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <h3 class="admin-card-title" style="margin: 0; color: #b45309; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-file-circle-exclamation"></i> Uncommitted / Modified Local Files (<?= $uncommittedCount ?>)
                    </h3>
                    <a href="github_sync.php?action=status" class="btn btn-outline btn-sm"><i class="fas fa-rotate"></i> Refresh</a>
                </div>
                <p style="color: var(--text-muted); font-size: 0.8125rem; margin-bottom: 1rem;">
                    These files contain local modifications on this server. Be cautious when pulling remote changes that affect the same files.
                </p>

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
                                        <?php if (strpos($uf['type'], 'M') !== false): ?>
                                            <span style="color: #2563eb; font-weight: 700;">Modified</span>
                                        <?php elseif (strpos($uf['type'], 'D') !== false): ?>
                                            <span style="color: #dc2626; font-weight: 700;">Deleted</span>
                                        <?php elseif (strpos($uf['type'], '?') !== false): ?>
                                            <span style="color: #10b981; font-weight: 700;">Untracked</span>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-weight: 700;"><?= htmlspecialchars($uf['type']) ?></span>
                                        <?php endif; ?>
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
                <i class="fas fa-sliders" style="color: var(--brand-gold-dark);"></i> Git Environment
            </h3>
            <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.8125rem;">
                <div>
                    <span style="color: var(--text-muted);">Git Version:</span><br>
                    <strong style="color: var(--primary);"><?= htmlspecialchars($gitVersion) ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Executable Path:</span><br>
                    <code style="font-size: 0.75rem; word-break: break-all; background: var(--bg-alt); padding: 0.2rem 0.4rem; border-radius: 3px;"><?= htmlspecialchars($gitBin) ?></code>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Repository Root:</span><br>
                    <code style="font-size: 0.75rem; word-break: break-all; background: var(--bg-alt); padding: 0.2rem 0.4rem; border-radius: 3px;"><?= htmlspecialchars($repoDir) ?></code>
                </div>
                <div>
                    <span style="color: var(--text-muted);">Remote Branch:</span><br>
                    <strong><?= htmlspecialchars("{$remoteName}/{$currentBranch}") ?></strong>
                </div>
                <?php if ($githubWebUrl): ?>
                    <div style="margin-top: 0.5rem; pt-2; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
                        <a href="<?= htmlspecialchars($githubWebUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm w-100" style="display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <i class="fab fa-github"></i> Open Repository on GitHub <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.7rem;"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- How It Works & Safety Guidelines -->
        <div class="admin-card" style="border-top: 4px solid #3b82f6;">
            <h3 class="admin-card-title" style="margin-bottom: 0.75rem; font-size: 1.05rem; color: #1e40af;">
                <i class="fas fa-circle-question" style="color: #3b82f6;"></i> How Syncing Works
            </h3>
            <ul style="padding-left: 1.15rem; margin: 0; font-size: 0.8125rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.65rem; line-height: 1.45;">
                <li>
                    <strong style="color: var(--primary);">Fetch from GitHub:</strong> Safely queries GitHub for new commits on the <code>main</code> branch without touching your working directory.
                </li>
                <li>
                    <strong style="color: var(--primary);">Pull Latest Changes:</strong> Downloads and merges the fetched commits into this server.
                </li>
                <li>
                    <strong style="color: var(--primary);">Uncommitted Files:</strong> If you edited files locally that exist in incoming commits, Git might require a stash or commit before pulling.
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
