<?php
    $to = "anirudhjbhatt@gmail.com"; // Replace with actual recipient email
    $subject = "Test Email from PHP";
    $message = "Hello! This is a test email sent from a PHP script.";
    $headers = "From: anirudhjbhatt@gmail.com"; // Replace with your email

    if(mail($to, $subject, $message, $headers)) {
        echo "Email sent successfully!";
    } else {
        echo "Failed to send email.";
    }
?>
