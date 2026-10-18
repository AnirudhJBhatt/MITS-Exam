<?php
/**
 * Authentication & Authorization Helpers
 * Enforces Role-Based Access Control (RBAC) and secure session policies
 */

// Initialize hardened session if not already active
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $serverPort = $_SERVER['SERVER_PORT'] ?? 80;
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $serverPort == 443;
        
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    session_start();
}

/**
 * Determine if a user is currently authenticated
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['LoginAdmin']) || 
           !empty($_SESSION['LoginDeptAdmin']) || 
           !empty($_SESSION['LoginFaculty']) || 
           !empty($_SESSION['LoginStudent']);
}

/**
 * Get current user's role identifier
 * @return string|null 'Admin'|'DeptAdmin'|'Faculty'|'Student'|null
 */
function get_logged_in_role() {
    if (!empty($_SESSION['LoginAdmin'])) return 'Admin';
    if (!empty($_SESSION['LoginDeptAdmin'])) return 'DeptAdmin';
    if (!empty($_SESSION['LoginFaculty'])) return 'Faculty';
    if (!empty($_SESSION['LoginStudent'])) return 'Student';
    return null;
}

/**
 * Get current user's session identifier
 * @return string|null
 */
function get_logged_in_user_id() {
    return $_SESSION['LoginAdmin'] ?? 
           $_SESSION['LoginDeptAdmin'] ?? 
           $_SESSION['LoginFaculty'] ?? 
           $_SESSION['LoginStudent'] ?? null;
}

/**
 * Guard restricted pages by role
 * Redirects to index.php if unauthorized
 * @param string|array $allowedRoles E.g. 'Admin' or ['Admin', 'DeptAdmin']
 * @param string $loginRedirect Relative path to index.php
 */
function check_role($allowedRoles, $loginRedirect = '../index.php') {
    $currentRole = get_logged_in_role();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    if (!$currentRole || !in_array($currentRole, $roles, true)) {
        $_SESSION['auth_flash_error'] = 'Access denied: You are not authorized to view that page.';
        header("Location: {$loginRedirect}");
        exit;
    }
}

/**
 * Safely escape text for HTML output to prevent XSS
 * @param mixed $value
 * @return string
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
