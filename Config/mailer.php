<?php
declare(strict_types=1);
require_once __DIR__.'/environment.php';
require_once __DIR__.'/email-template.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/Exception.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/SMTP.php';

function royalmarkMailer(): PHPMailer\PHPMailer\PHPMailer
{
    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    $host=getenv('MAIL_HOST')?:'';$username=getenv('MAIL_USERNAME')?:'';$password=getenv('MAIL_PASSWORD')?:'';
    $from=getenv('MAIL_FROM_ADDRESS')?:$username;
    if(!$host||!$username||!$password||!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Mail configuration incomplete.');
    $encryption=strtolower(getenv('MAIL_ENCRYPTION')?:'tls');
    if(!in_array($encryption,['tls','ssl'],true))throw new RuntimeException('SMTP encryption must be tls or ssl.');
    $mail->isSMTP();$mail->Host=$host;$mail->SMTPAuth=true;$mail->Username=$username;$mail->Password=$password;
    $mail->Port=(int)(getenv('MAIL_PORT')?:587);$mail->SMTPSecure=$encryption==='ssl'?PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout=15;$mail->CharSet='UTF-8';$mail->setFrom($from,getenv('MAIL_FROM_NAME')?:'Royalmark Estate');
    return $mail;
}

function sendPasswordResetEmail(string $recipient, string $link): void
{
    $mail=royalmarkMailer();$mail->addAddress($recipient);
    $mail->isHTML(true);$mail->Subject='Reset your Royalmark Estate password';
    $mail->addEmbeddedImage(dirname(__DIR__).'/assets/royalmark-logo.png','royalmark-logo','royalmark-logo.png');
    $mail->Body=passwordResetEmailHtml($link);
    $mail->AltBody="Reset your Royalmark Estate password:\n$link\n\nThis link expires in 30 minutes and works once. If you did not request it, ignore this email.";
    $mail->send();
}
