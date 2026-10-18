<?php
/**
 * Lightweight Cross-Site Request Forgery (CSRF) Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve current session CSRF token
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden HTML input field containing the CSRF token
 * @return string
 */
function csrf_field() {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate incoming CSRF token on POST requests
 * @param bool $haltOnError Whether to terminate script execution if token is invalid
 * @return bool
 */
function verify_csrf($haltOnError = true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken   = $_SESSION['csrf_token'] ?? '';

        if (empty($sessionToken) || empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
            if ($haltOnError) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Security Error: Invalid or expired CSRF token. Please refresh the page.'
                ]);
                exit;
            }
            return false;
        }
    }
    return true;
}
