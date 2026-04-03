<?php
    require __DIR__ . '/../vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true);
    echo "PHPMailer installed successfully";
?>
<?php
require __DIR__ . '/vendor/autoload.php';


$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'assessment@mgits.ac.in';
    $mail->Password   = 'tdpxzzmsueexuayb'; // Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('assessment@mgits.ac.in', 'MITS Exam Portal');
    $mail->addAddress('23mca08@mgits.ac.in'); // Recipient email

    $mail->isHTML(true);
    $mail->Subject = 'Test Mail';
    $mail->Body    = 'This is a test email sent from MITS Internal Assessment Portal.';

    $mail->send();
    echo 'Mail sent successfully';

} catch (Exception $e) {
    echo "Mail Error: {$mail->ErrorInfo}";
}
?>