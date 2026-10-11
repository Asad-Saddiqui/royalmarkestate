<?php
require_once dirname(__DIR__).'/Config/Database.php';
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','path'=>'/Royalmarkestate/']);session_start();}
function adminDb(): PDO {return Database::getConnection();}
function adminToken(): string {return $_SESSION['admin_csrf']??=bin2hex(random_bytes(32));}
function adminCsrf(): void {if(!is_string($_POST['csrf']??null)||!hash_equals(adminToken(),$_POST['csrf'])){http_response_code(403);exit('Invalid form token. Reload and try again.');}}
function requireAdmin(): void {if(empty($_SESSION['admin_id'])){header('Location: /Royalmarkestate/admin/login');exit;}}
