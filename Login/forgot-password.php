<?php 
/**
 * Forgot Password Request Handler
 * Generates single-use 15-minute reset token and sends link via PHPMailer
 */

require_once __DIR__ . "/../Connection/connection.php";
require_once __DIR__ . "/../Common/auth.php";
require_once __DIR__ . "/../Common/csrf.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

if (!verify_csrf(false)) {
    $_SESSION['auth_flash_error'] = "Security session expired. Please try again.";
    header("Location: ../index.php");
    exit;
}

$identifier = trim($_POST['user_id'] ?? '');

if (empty($identifier)) {
    $_SESSION['auth_flash_error'] = "Please enter your username or registered email.";
    header("Location: ../index.php");
    exit;
}

// 1. Verify user exists in login table
$userRow = null;
if ($pdo) {
    $stmt = $pdo->prepare("SELECT User_ID FROM login WHERE User_ID = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $userRow = $stmt->fetch();
} elseif ($con) {
    $stmt = $con->prepare("SELECT User_ID FROM login WHERE User_ID = ? LIMIT 1");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $userRow = $stmt->get_result()->fetch_assoc();
}

// Even if user does not exist, return generic success message to prevent user enumeration
if (!$userRow) {
    $_SESSION['flash_success'] = "If that username/email exists in our records, a password reset link has been dispatched.";
    header("Location: ../index.php");
    exit;
}

$email = $userRow['User_ID'];

// 2. Generate cryptographically secure token & 15-minute expiration
$token  = bin2hex(random_bytes(32));
$expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

// 3. Store token
if ($pdo) {
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$email, $token, $expiry]);
} elseif ($con) {
    $stmt = $con->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expiry);
    $stmt->execute();
}

// 4. Construct Dynamic Reset URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseDir = rtrim(dirname(dirname($_SERVER['REQUEST_URI'] ?? '/')), '/\\');
$resetLink = $protocol . $host . $baseDir . "/Login/reset-password.php?token=" . $token;

// 5. Send Email via PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'] ?? ''; 
    $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
    $mail->SMTPSecure = (($_ENV['SMTP_SECURE'] ?? 'tls') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

    $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@mgmits.ac.in';
    $fromName  = $_ENV['SMTP_FROM_NAME'] ?? 'MITS Internal Assessment Portal';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request - MITS Assessment Portal';
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px;'>
            <h2 style='color: #d1202d; margin-top: 0;'>MITS Internal Assessment Portal</h2>
            <p>You requested a password reset for your account (<strong>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</strong>).</p>
            <p style='margin: 24px 0;'>
                <a href='{$resetLink}' style='background-color: #d1202d; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset Password</a>
            </p>
            <p style='color: #64748b; font-size: 0.9em;'>This link will expire in 15 minutes. If you did not request this change, you can safely ignore this email.</p>
        </div>
    ";
    
    $mail->send();
    $_SESSION['flash_success'] = "Password reset instructions have been dispatched to your email.";
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    $_SESSION['auth_flash_error'] = "Unable to dispatch email. Please contact the Examination Cell administrator.";
}

header("Location: ../index.php");
exit;
