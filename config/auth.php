<?php
/**
 * Authentication & Security Helper Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Generate CSRF token if not set
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Check if admin is authenticated
function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email']);
}

// Enforce admin authentication
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

// Safe XSS output escape
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format Pakistani Rupee in Lacs / Crores
function formatPKR($amount) {
    $num = floatval($amount);
    if ($num >= 10000000) { // 1 Crore = 10,000,000
        $crore = $num / 10000000;
        return 'PKR ' . (round($crore, 2) == round($crore, 0) ? number_format($crore, 0) : number_format($crore, 2)) . ' Crore';
    } elseif ($num >= 100000) { // 1 Lac = 100,000
        $lac = $num / 100000;
        return 'PKR ' . (round($lac, 2) == round($lac, 0) ? number_format($lac, 0) : number_format($lac, 2)) . ' Lac';
    } else {
        return 'PKR ' . number_format($num);
    }
}
