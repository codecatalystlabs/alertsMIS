<?php

$to = "your-email@example.com";
$subject = "Test Mail";
$message = "Hello, this is a test email from Apache server.";
$headers = "From: no-reply@yourdomain.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully.";
} else {
    echo "Failed to send email.";
}
?>
