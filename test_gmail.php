<?php
require 'mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    
    // Testez d'abord avec VOTRE propre adresse email
    $mail->addAddress('votre_email_personnel@gmail.com'); // CHANGEZ ICI
    
    $mail->Subject = 'Test CraftLink - Configuration SMTP';
    $mail->Body    = '✅ Votre configuration email fonctionne parfaitement !';
    
    $mail->send();
    echo "✅ SUCCÈS : L'email a été envoyé avec le mot de passe d'application !";
    
} catch (Exception $e) {
    echo "❌ ÉCHEC : " . $mail->ErrorInfo;
}
?>