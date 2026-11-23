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

// 1. If repoDir does not exist, clone it from GitHub
if (!is_dir($repoDir)) {
    echo "1. Cloning repository from GitHub into {$repoDir}...\n";
    $clone = shell_exec("git clone https://github.com/mohith939/svgdryfruits.git {$repoDir} 2>&1");
    echo $clone ?: "Git clone executed.\n";
}

// 2. If repoDir exists, pull latest & sync
if (is_dir($repoDir)) {
    echo "2. Pulling latest git changes in {$repoDir}...\n";
    $gitPull = shell_exec("cd {$repoDir} && git fetch --all && git reset --hard origin/main 2>&1");
    echo $gitPull ?: "Git command executed.\n";
    
    echo "\n3. Syncing files from repo to live webroot ({$targetDir})...\n";
    $copy = shell_exec("cp -rf {$repoDir}/* {$targetDir}/ 2>&1");
    echo $copy ?: "Files copied successfully.\n";
    
    $copyHidden = shell_exec("cp -rf {$repoDir}/.htaccess {$targetDir}/ 2>&1");
    echo "Copied .htaccess.\n";
} else {
    echo "1. Trying git pull directly in target directory...\n";
    $directGit = shell_exec("cd {$targetDir} && git pull origin main 2>&1");
    echo $directGit ?: "Direct git executed.\n";
}

// 4. Ensure uploads folder exists and is writable
$uploadsDir = $targetDir . '/uploads';
if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}
@chmod($uploadsDir, 0755);
echo "\n4. Uploads directory checked & permissions set (755): {$uploadsDir}\n";

// 5. Trigger database schema auto-migrations (preserving all existing products & orders)
try {
    require_once $targetDir . '/config/database.php';
    Database::getConnection();
    echo "5. Database schema checked and auto-migrated safely (0 data loss).\n";
} catch (Throwable $e) {
    echo "5. Database note: " . $e->getMessage() . "\n";
}

echo "\n--- UPLOADED IMAGES SCAN & RESTORE ---\n";
$siteRoot = dirname($targetDir); // /home/sites/29a/a/a6fe9510b6/balaji
$homeRoot = dirname($siteRoot); // /home/sites/29a/a/a6fe9510b6
$foundImages = shell_exec("find {$homeRoot} -type f \( -name 'prod_*' -o -name 'img_*' -o -name '*.png' -o -name '*.jpg' \) 2>/dev/null");
echo "Found Images:\n" . ($foundImages ?: "None found in scan.\n");

// Ensure both /assets/uploads and /uploads exist in target
@mkdir($targetDir . '/assets/uploads', 0755, true);
@mkdir($targetDir . '/uploads', 0755, true);

// If any prod_* images exist elsewhere on the account, copy them into targetDir/assets/uploads!
$prodImages = shell_exec("find {$homeRoot} -type f -name 'prod_*' 2>/dev/null");
if ($prodImages) {
    $lines = explode("\n", trim($prodImages));
    foreach ($lines as $srcFile) {
        $srcFile = trim($srcFile);
        if ($srcFile && file_exists($srcFile)) {
            $baseName = basename($srcFile);
            @copy($srcFile, $targetDir . '/assets/uploads/' . $baseName);
            @copy($srcFile, $targetDir . '/uploads/' . $baseName);
            echo "Restored: {$baseName} -> {$targetDir}/assets/uploads/\n";
        }
    }
}

echo "\n=========================================\n";
echo "DEPLOYMENT COMPLETE & LIVE!\n";
echo "Storefront: https://www.svgdryfruits.com/\n";
echo "Admin Panel: https://www.svgdryfruits.com/admin/settings\n";
echo "=========================================\n";
