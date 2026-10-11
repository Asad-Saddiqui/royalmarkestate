<?php
declare(strict_types=1);

// Run from a terminal only. Never expose account reset through a public URL.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!in_array('--delete-other-users', $argv, true)) {
    fwrite(STDERR, "Usage: php Config/reset-admin.php --delete-other-users\nReplaces all accounts with royalmarkestate@gmail.com as the active administrator.\n");
    exit(1);
}
require_once __DIR__.'/Database.php';
$email = 'royalmarkestate@gmail.com';
$password = 'Rm!'.bin2hex(random_bytes(14));
$db = Database::getConnection();
try {
    $db->beginTransaction();
    $query = $db->prepare("INSERT INTO admin_users (email,password_hash,name,role,is_blocked) VALUES (?,?,?,'admin',0) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),name=VALUES(name),role='admin',is_blocked=0");
    $query->execute([$email,password_hash($password,PASSWORD_DEFAULT),'Royalmark Administrator']);
    $query = $db->prepare('DELETE FROM admin_users WHERE email <> ?');
    $query->execute([$email]);
    $deleted = $query->rowCount();
    $db->commit();
    fwrite(STDOUT, "Admin account ready.\nEmail: $email\nPassword: $password\nOther accounts deleted: $deleted\nStore this password privately. Running this script again generates a new password.\n");
} catch (Throwable $exception) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "Account reset failed; changes were rolled back. Check the database connection and permissions.\n");
    exit(1);
}
