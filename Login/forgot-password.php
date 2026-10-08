<?php 
    
    include("../Connection/connection.php");

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require '../vendor/autoload.php';
    
    $email = $_POST['user_id'];
    
    // Generate secure token
    $token = bin2hex(random_bytes(32));

    // Set expiry (1 hour)
    $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

    // Insert token into password_resets table
    $stmt = $con->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $token, $expiry);
    $stmt->execute();

    // Prepare reset link
    $resetLink = "https://assessment.mgmits.ac.in/Login/reset-password.php?token=" . $token;

    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER']; 
        $mail->Password   = $_ENV['SMTP_PASS']; // App password
        $mail->SMTPSecure = ($_ENV['SMTP_SECURE'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];


        // Sender & recipient
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'MITS Internal Assessment Portal Password Reset';
        $mail->Body    = "Click <a href='$resetLink'>here</a> to reset your password.<br>This link will expire in 15 minutes.";
        $mail->send();
        echo "<script>alert('Password reset link has been sent to your email.'); window.location.href = '../index.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Failed to send email. Please contact Admin{$mail->ErrorInfo}'); window.history.back();</script>";
    }
?>
