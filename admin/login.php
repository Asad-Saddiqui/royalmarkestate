<?php
require __DIR__.'/auth.php';
require dirname(__DIR__).'/includes/bootstrap.php';
try {
 $hasAccount=(int)adminDb()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn()>0;
} catch (PDOException $exception) {
 error_log('Royalmark admin login: '.$exception->getMessage());
 http_response_code(503);
 exit('Admin database setup is incomplete. Check the hosting .env credentials and import Config/schema.sql into that database. See the hosting PHP error log for the exact cause.');
}
if(currentUser()){header('Location: ./');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 adminCsrf();$email=is_string($_POST['email']??null)?trim($_POST['email']):'';$password=is_string($_POST['password']??null)?$_POST['password']:'';
 if(($_SESSION['login_retry_after']??0)>time())$error='Too many attempts. Try again in a few minutes.';
 elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)||(strlen($password)<12||strlen($password)>200))$error='Enter a valid email and a password of at least 12 characters.';
 elseif(!$hasAccount){
  try{adminDb()->prepare('INSERT INTO admin_users (id,email,password_hash) VALUES (1,?,?)')->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['admin_id']=1;header('Location: ./');exit;}catch(PDOException $e){$error='An administrator already exists. Reload to sign in.';}
 }else{
  $statement=adminDb()->prepare('SELECT id,password_hash,is_blocked,session_version FROM admin_users WHERE email=?');$statement->execute([$email]);$user=$statement->fetch();
  if($user&&!$user['is_blocked']&&password_verify($password,$user['password_hash'])){session_regenerate_id(true);$_SESSION['admin_id']=$user['id'];$_SESSION['admin_session_version']=(int)$user['session_version'];unset($_SESSION['login_failures'],$_SESSION['login_retry_after']);header('Location: ./');exit;}
  $_SESSION['login_failures']=($_SESSION['login_failures']??0)+1;if($_SESSION['login_failures']>=5)$_SESSION['login_retry_after']=time()+300;$error='Email or password is incorrect.';
 }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Admin login | Royalmark Estate</title><link rel="stylesheet" href="./admin.css"><link rel="stylesheet" href="./forms.css"><link rel="icon" type="image/png" href="../assets/royalmark-logo.png?v=brand-1"><link rel="apple-touch-icon" href="../assets/royalmark-logo.png?v=brand-1"><script src="../assets/branding.js?v=1" defer></script></head><body class="login-page"><main class="login-card"><a href="../"><img src="../assets/royalmark-logo.png" alt="Royalmark" width="140" height="82"></a><h1><?= $hasAccount?'Welcome back.':'Set up your admin.' ?></h1><p><?= $hasAccount?'Sign in to manage Royalmark Estate.':'First visit: create your administrator email and password. This setup closes after the first account.' ?></p><?php if($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?><?php if(isset($_GET['reset'])): ?><p class="success-note" role="status">Password updated. Sign in with your new password.</p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><label>Email<input type="email" name="email" autocomplete="username" required maxlength="254"></label><label>Password<input type="password" name="password" minlength="12" maxlength="200" autocomplete="<?= $hasAccount?'current-password':'new-password' ?>" required></label><button class="primary-button" type="submit"><?= $hasAccount?'Login':'Create admin account' ?></button></form><a class="text-link" href="./forgot-password">Forgot your password?</a><br><br><a class="text-link" href="../">&larr; Back to website</a></main></body></html>
