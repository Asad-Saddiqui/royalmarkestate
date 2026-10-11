<?php
declare(strict_types=1);
require_once __DIR__.'/mailer.php';

function resetRateLimit(PDO $db,string $bucket,int $maximum): bool
{
    $db->prepare('INSERT INTO password_reset_limits (bucket,attempts,window_start) VALUES (?,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE attempts=IF(window_start < UTC_TIMESTAMP()-INTERVAL 1 HOUR,1,attempts+1),window_start=IF(window_start < UTC_TIMESTAMP()-INTERVAL 1 HOUR,UTC_TIMESTAMP(),window_start)')->execute([$bucket]);
    $q=$db->prepare('SELECT attempts FROM password_reset_limits WHERE bucket=?');$q->execute([$bucket]);return (int)$q->fetchColumn()<=$maximum;
}
function requestPasswordReset(PDO $db,string $email,string $ip,?callable $sender=null): void
{
    $email=strtolower(trim($email));
    $ipAllowed=resetRateLimit($db,hash('sha256','ip:'.$ip),10);
    $emailAllowed=resetRateLimit($db,hash('sha256','email:'.$email),3);
    if(!$ipAllowed||!$emailAllowed)return;
    $base=rtrim(getenv('APP_URL')?:'https://royalmarkestate.com','/');
    if(!filter_var($base,FILTER_VALIDATE_URL)||parse_url($base,PHP_URL_SCHEME)!=='https')throw new RuntimeException('APP_URL must use HTTPS.');
    $q=$db->prepare('SELECT id,email FROM admin_users WHERE email=? AND is_blocked=0');$q->execute([$email]);$user=$q->fetch(PDO::FETCH_ASSOC);if(!$user)return;
    $token=bin2hex(random_bytes(32));$hash=hash('sha256',$token);
    $db->prepare('INSERT INTO password_reset_tokens (user_id,token_hash,expires_at) VALUES (?,?,UTC_TIMESTAMP()+INTERVAL 30 MINUTE) ON DUPLICATE KEY UPDATE token_hash=VALUES(token_hash),expires_at=VALUES(expires_at),created_at=UTC_TIMESTAMP()')->execute([$user['id'],$hash]);
    try{($sender??'sendPasswordResetEmail')($user['email'],$base.'/admin/reset-password?token='.$token);}
    catch(Throwable $e){$db->prepare('DELETE FROM password_reset_tokens WHERE token_hash=?')->execute([$hash]);throw new RuntimeException('Reset email delivery failed.',0,$e);}
    $db->exec('DELETE FROM password_reset_tokens WHERE expires_at < UTC_TIMESTAMP()');
    $db->exec('DELETE FROM password_reset_limits WHERE window_start < UTC_TIMESTAMP()-INTERVAL 2 DAY');
}
function passwordResetTokenValid(PDO $db,string $token): bool
{
    if(!preg_match('/^[a-f0-9]{64}$/',$token))return false;
    $q=$db->prepare('SELECT 1 FROM password_reset_tokens t JOIN admin_users u ON u.id=t.user_id WHERE t.token_hash=? AND t.expires_at>UTC_TIMESTAMP() AND u.is_blocked=0');$q->execute([hash('sha256',$token)]);return (bool)$q->fetchColumn();
}
function completePasswordReset(PDO $db,string $token,string $password): bool
{
    if(!preg_match('/^[a-f0-9]{64}$/',$token)||strlen($password)<12||strlen($password)>200)return false;
    $hash=password_hash($password,PASSWORD_DEFAULT);$db->beginTransaction();
    try{
        $q=$db->prepare('SELECT user_id FROM password_reset_tokens WHERE token_hash=? AND expires_at>UTC_TIMESTAMP() FOR UPDATE');$q->execute([hash('sha256',$token)]);$id=$q->fetchColumn();
        if(!$id){$db->rollBack();return false;}
        $update=$db->prepare('UPDATE admin_users SET password_hash=?,session_version=session_version+1 WHERE id=? AND is_blocked=0');$update->execute([$hash,$id]);
        $db->prepare('DELETE FROM password_reset_tokens WHERE user_id=?')->execute([$id]);$db->commit();return $update->rowCount()===1;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
