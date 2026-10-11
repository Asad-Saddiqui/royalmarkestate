<?php
require_once dirname(__DIR__).'/Config/Database.php';
$adminBase=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/admin/index.php')),'/');
$siteBase=rtrim(dirname($adminBase),'/');
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','path'=>($siteBase?:'').'/']);session_start();}
function adminDb(): PDO {return Database::getConnection();}
function adminToken(): string {return $_SESSION['admin_csrf']??=bin2hex(random_bytes(32));}
function adminCsrf(): void {if(!is_string($_POST['csrf']??null)||!hash_equals(adminToken(),$_POST['csrf'])){http_response_code(403);exit('Invalid form token. Reload and try again.');}}
function currentUser(): ?array {
    if(empty($_SESSION['admin_id']))return null;
    $statement=adminDb()->prepare('SELECT id,email,name,role,is_blocked FROM admin_users WHERE id=?');
    $statement->execute([$_SESSION['admin_id']]);$user=$statement->fetch();
    if(!$user||$user['is_blocked']){unset($_SESSION['admin_id']);return null;}
    return $user;
}
function requireLogin(): array { $user=currentUser();if(!$user){global $adminBase;header('Location: '.$adminBase.'/login');exit;}return $user; }
function requireAdmin(): void { $user=requireLogin();if($user['role']!=='admin'){http_response_code(403);exit('Administrator access required.');} }
