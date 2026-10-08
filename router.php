<?php
/**
 * PHP Development Server Router
 * Routes all requests to the correct PHP file.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly if they exist
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        require $filePath;
        return;
    }
    return false;
}

// API routes - serve directly
if (preg_match('#^/api/#', $uri)) {
    $apiFile = __DIR__ . $uri;
    if (file_exists($apiFile)) {
        require $apiFile;
        return;
    }
    if (file_exists($apiFile . '.php')) {
        require $apiFile . '.php';
        return;
    }
}

// Page routes
if ($uri === '/' || $uri === '/index.html' || $uri === '/index.php') {
    require __DIR__ . '/index.php';
    return;
}

if ($uri === '/property' || $uri === '/property.php') {
    require __DIR__ . '/property.php';
    return;
}

if (preg_match('#^/admin/?$#', $uri)) {
    require __DIR__ . '/admin/dashboard.php';
    return;
}

// Try adding .php extension
if (file_exists($filePath . '.php')) {
    require $filePath . '.php';
    return;
}

// Fallback to index.php
if (file_exists(__DIR__ . '/index.php')) {
    require __DIR__ . '/index.php';
    return;
}

return false;
