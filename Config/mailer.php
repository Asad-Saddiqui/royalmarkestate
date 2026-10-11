<?php
declare(strict_types=1);
require_once __DIR__.'/environment.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/Exception.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/PHPMailer.php';
require_once dirname(__DIR__).'/vendor/phpmailer/src/SMTP.php';

function sendPasswordResetEmail(string $recipient, string $link): void
{
    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    $host=getenv('MAIL_HOST')?:'';$username=getenv('MAIL_USERNAME')?:'';$password=getenv('MAIL_PASSWORD')?:'';
    $from=getenv('MAIL_FROM_ADDRESS')?:$username;
    if(!$host||!$username||!$password||!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Mail configuration incomplete.');
    $encryption=strtolower(getenv('MAIL_ENCRYPTION')?:'tls');
    if(!in_array($encryption,['tls','ssl'],true))throw new RuntimeException('SMTP encryption must be tls or ssl.');
    $mail->isSMTP();$mail->Host=$host;$mail->SMTPAuth=true;$mail->Username=$username;$mail->Password=$password;
    $mail->Port=(int)(getenv('MAIL_PORT')?:587);$mail->SMTPSecure=$encryption==='ssl'?PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS:PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Timeout=15;$mail->CharSet='UTF-8';$mail->setFrom($from,getenv('MAIL_FROM_NAME')?:'Royalmark Estate');$mail->addAddress($recipient);
    $safe=htmlspecialchars($link,ENT_QUOTES,'UTF-8');$mail->isHTML(true);$mail->Subject='Reset your Royalmark Estate password';
    $mail->Body='<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:32px;color:#222"><h2>Reset your password</h2><p>We received a request to reset your Royalmark Estate account password.</p><p style="margin:28px 0"><a href="'.$safe.'" style="background:#d70000;color:#fff;padding:13px 22px;text-decoration:none">Reset password</a></p><p>This link expires in 30 minutes and can only be used once.</p><p>If you did not request this, ignore this email. Your password remains unchanged.</p><hr><p style="font-size:12px">Button not working? Open this link:<br><a href="'.$safe.'">'.$safe.'</a></p></div>';
    $mail->AltBody="Reset your Royalmark Estate password:\n$link\n\nThis link expires in 30 minutes and works once. If you did not request it, ignore this email.";
    $mail->send();
}
