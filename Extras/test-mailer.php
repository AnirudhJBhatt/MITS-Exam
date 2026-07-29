<?php
    require __DIR__ . '/../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require_once __DIR__ . '/../Connection/connection.php';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $ENV['SMTP_USER'];
        $mail->Password   = $ENV['SMTP_PASS'];
        $mail->SMTPSecure = ($ENV['SMTP_SECURE'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $ENV['SMTP_PORT'];

        $mail->setFrom($ENV['SMTP_FROM_EMAIL'], $ENV['SMTP_FROM_NAME']);
        $mail->addAddress($ENV['SMTP_USER']); // Send to self as test

        $mail->isHTML(true);
        $mail->Subject = 'Test Mail';
        $mail->Body    = 'This is a test email sent from MITS Internal Assessment Portal.';

        $mail->send();
        echo 'Mail sent successfully';

    } catch (Exception $e) {
        echo "Mail Error: {$mail->ErrorInfo}";
    }
?>