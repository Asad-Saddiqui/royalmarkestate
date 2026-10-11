<?php
require __DIR__.'/auth.php';require dirname(__DIR__).'/includes/bootstrap.php';require dirname(__DIR__).'/Config/password-reset.php';
header('Cache-Control: no-store');header('Referrer-Policy: no-referrer');
$sent=false;$error='';$email='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    adminCsrf();$email=is_string($_POST['email']??null)?trim($_POST['email']):'';
    if(strlen($email)>254||!filter_var($email,FILTER_VALIDATE_EMAIL))$error='Enter a valid email address.';
    else{try{requestPasswordReset(adminDb(),$email,$_SERVER['REMOTE_ADDR']??'unknown');}catch(Throwable $e){error_log('Royalmark password reset: delivery or configuration unavailable.');}$sent=true;}
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Forgot password | Royalmark Estate</title><link rel="icon" href="../assets/royalmark-logo.png"><link rel="stylesheet" href="./admin.css"><link rel="stylesheet" href="./forms.css"></head><body class="login-page"><main class="login-card"><a href="../"><img src="../assets/royalmark-logo.png" alt="Royalmark Estate" width="140" height="82"></a><h1>Forgot password?</h1><p>Enter your account email to request a secure reset link.</p><?php if($sent): ?><p class="success-note" role="status">If an active account matches this email, a reset link will be sent. Check your inbox and spam folder. The link expires in 30 minutes.</p><?php else: ?><?php if($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><label>Email address<input type="email" name="email" value="<?= e($email) ?>" required maxlength="254" autocomplete="email"></label><button class="primary-button" type="submit">Send reset link</button></form><?php endif; ?><a class="text-link" href="./login">Back to login</a></main></body></html>
