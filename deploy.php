<?php
/**
 * SVG Dry Fruits - One-Click Server Sync & Deployer
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$secretKey = 'svg_deploy_2026';
$provided = $_GET['key'] ?? '';

if ($provided !== $secretKey) {
    http_response_code(403);
    die("<h2 style='font-family:sans-serif;color:#c00;'>Access Denied. Pass ?key=svg_deploy_2026 to execute.</h2>");
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== SVG DRY FRUITS DEPLOYMENT & SYNC ===\n\n";

$targetDir = __DIR__;
$repoDir = '/home/sites/29a/a/a6fe9510b6/svgdryfruits-repo';

echo "Target Directory: {$targetDir}\n";
echo "Repo Directory:   {$repoDir}\n\n";

// 1. If repoDir exists, pull latest
if (is_dir($repoDir)) {
    echo "1. Pulling latest git changes in {$repoDir}...\n";
    $gitPull = shell_exec("cd {$repoDir} && git fetch --all && git reset --hard origin/main 2>&1");
    echo $gitPull ?: "Git command executed.\n";
    
    echo "\n2. Syncing files from repo to live webroot ({$targetDir})...\n";
    $copy = shell_exec("cp -rf {$repoDir}/* {$targetDir}/ 2>&1");
    echo $copy ?: "Files copied successfully.\n";
    
    $copyHidden = shell_exec("cp -rf {$repoDir}/.htaccess {$targetDir}/ 2>&1");
    echo "Copied .htaccess.\n";
} else {
    echo "1. Trying git pull directly in target directory...\n";
    $directGit = shell_exec("cd {$targetDir} && git pull origin main 2>&1");
    echo $directGit ?: "Direct git executed.\n";
}

// 3. Ensure uploads folder exists and is writable
$uploadsDir = $targetDir . '/uploads';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}
@chmod($uploadsDir, 0755);
echo "\n3. Uploads directory checked & permissions set (755): {$uploadsDir}\n";

// 4. Clean lock file if needed to allow clean database install
$lock = $targetDir . '/database/.installed';
if (file_exists($lock)) {
    @unlink($lock);
    echo "4. Unlocked database installer for fresh setup.\n";
}

echo "\n=========================================\n";
echo "DEPLOYMENT COMPLETE!\n";
echo "Now visit: https://www.svgdryfruits.com/database/install.php\n";
echo "=========================================\n";
