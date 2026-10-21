<?php
// router.php - PHP built-in server router with security controls

require_once __DIR__ . '/vendor/autoload.php';

// Get the requested path
$requestedPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestedPath = urldecode($requestedPath);

// Define blocked patterns (case-insensitive, ~ delimiter)
$blockedPatterns = [
    // Version control
    '~/\.git(/|$)~i',
    '~/\.svn(/|$)~i',
    '~/\.hg(/|$)~i',
    '~/\.bzr(/|$)~i',
    '~/CVS(/|$)~i',

    // Hidden files at any level (dotfiles)
    '~/\.[^/]+$~i',

    // Common sensitive files
    '~/\.env(\.|$)~i',
    '~/\.htaccess$~i',
    '~/\.htpasswd$~i',
    '~/\.gitignore$~i',
    '~/\.gitattributes$~i',
    '~/\.gitmodules$~i',
    '~/composer\.(json|lock)$~i',
    '~/package(-lock)?\.json$~i',
    '~/yarn\.lock$~i',
    '~/\.DS_Store$~i',
    '~/Thumbs\.db$~i',
    '~/web\.config$~i',

    // Backup / editor / config files
    '~/\.(bak|backup|old|orig|swp|swo|tmp|log|sql|sqlite|db)$~i',
    '~/\.(dist|sample|example)$~i',

    // Sensitive directories
    '~/(vendor|node_modules|cache|logs?|tmp|storage|backups?)(/|$)~i',

    // Internal class directory
    '~/src(/|$)~i',

    // PHP files we don't want to serve directly (optional)
    // '~/\.(inc|conf|config|ini|yml|yaml)$~i',

    // If router.php itself is in the web root
    '~/router\.php$~i',
];

// Check against blocked patterns
foreach ($blockedPatterns as $pattern) {
    if (preg_match($pattern, $requestedPath)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo "403 Forbidden";
        exit;
    }
}

// Normalize path and prevent directory traversal
$documentRoot = realpath(__DIR__);
$fullPath = realpath($documentRoot . $requestedPath);

// If realpath fails, the file doesn't exist
if ($fullPath === false) {
    // Let PHP's built-in server try to handle it (or return 404)
    return false;
}

// Ensure the resolved path is still inside the document root
if (strpos($fullPath, $documentRoot) !== 0) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "403 Forbidden";
    exit;
}

// If it's a directory, let the server handle it (for index.php, etc.)
if (is_dir($fullPath)) {
    // Look for an index file
    foreach (['index.php', 'index.html', 'index.htm'] as $index) {
        if (file_exists($fullPath . '/' . $index)) {
            // Let the built-in server serve the index
            return false;
        }
    }
    // No index - return 404
    http_response_code(404);
    echo "404 Not found";
    exit;
}

// File exists and is safe - let the built-in server serve it
return false;