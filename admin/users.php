<?php
require __DIR__.'/auth.php';requireAdmin();require dirname(__DIR__).'/includes/bootstrap.php';require dirname(__DIR__).'/includes/listings.php';
$error='';$success=$_SESSION['users_success']??'';unset($_SESSION['users_success']);$values=['name'=>'','email'=>'','role'=>'user'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 adminCsrf();$action=is_string($_POST['action']??null)?$_POST['action']:'';
 try{
 if($action==='create'){
 foreach($values as $key=>$value)$values[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
 $password=is_string($_POST['password']??null)?$_POST['password']:'';
 if($values['name']===''||mb_strlen($values['name'])>100||!filter_var($values['email'],FILTER_VALIDATE_EMAIL)||strlen($values['email'])>254||!in_array($values['role'],['user','admin'],true)||strlen($password)<12||strlen($password)>200)throw new InvalidArgumentException('Enter a name, valid email, role and password of 12–200 characters.');
 adminDb()->prepare('INSERT INTO admin_users(name,email,password_hash,role) VALUES(?,?,?,?)')->execute([$values['name'],strtolower($values['email']),password_hash($password,PASSWORD_DEFAULT),$values['role']]);
 $_SESSION['users_success']='User created successfully.';
 }elseif(in_array($action,['block','unblock','role'],true)){
 $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);if(!$id||$id===$_SESSION['admin_id'])throw new InvalidArgumentException('You cannot block or change your own role.');
 if($action==='role'){$role=is_string($_POST['role']??null)?$_POST['role']:'';if(!in_array($role,['admin','user'],true))throw new InvalidArgumentException('Invalid role.');adminDb()->prepare('UPDATE admin_users SET role=? WHERE id=?')->execute([$role,$id]);}
 else adminDb()->prepare('UPDATE admin_users SET is_blocked=? WHERE id=?')->execute([$action==='block'?1:0,$id]);
 $_SESSION['users_success']='User updated successfully.';
 }else throw new InvalidArgumentException('Invalid action.');
 header('Location: ./users',true,303);exit;
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(PDOException $e){$error=($e->errorInfo[1]??0)===1062?'That email already has an account.':'Unable to save changes. Please try again.';}
}
$query=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,150):'';
$statement=adminDb()->prepare('SELECT id,name,email,role,is_blocked FROM admin_users WHERE name LIKE ? OR email LIKE ? ORDER BY id');$statement->execute(['%'.$query.'%','%'.$query.'%']);$users=$statement->fetchAll();
$properties=listingData();$view='users';$title='Users';require __DIR__.'/header.php'; ?>
<section class="page-heading"><div><span class="eyebrow">ACCESS MANAGEMENT</span><h1>Users</h1><p>Admins manage properties and users. Users can browse the dashboard without making changes.</p></div></section>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="success-note" role="status"><?= e($success) ?></div><?php endif; ?>
<section class="panel form-section"><h2>Create user</h2><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="create"><div class="form-grid user-fields"><label>Name<input name="name" maxlength="100" required value="<?= e($values['name']) ?>"></label><label>Email<input type="email" name="email" maxlength="254" required value="<?= e($values['email']) ?>"></label><label>Password<input type="password" name="password" minlength="12" maxlength="200" autocomplete="new-password" required></label><label>Role<select name="role"><?php foreach(['user','admin'] as $role): ?><option <?= $values['role']===$role?'selected':'' ?>><?= $role ?></option><?php endforeach; ?></select></label></div><button class="primary-button" type="submit">Create user</button></form></section>
<section class="panel"><div class="panel-title"><h2>All users (<?= count($users) ?>)</h2></div><form class="table-toolbar" method="get" action="./users"><label class="search-field"><span class="sr-only">Search users</span><input type="search" name="q" value="<?= e($query) ?>" placeholder="Search name or email"></label><button class="primary-button">Search</button><a class="text-link" href="./users">Reset</a></form><div class="table-scroll" tabindex="0" role="region" aria-label="User list"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php foreach($users as $user): ?><tr><td><?= e($user['name']?:'Administrator') ?><?= $user['id']===$_SESSION['admin_id']?' (You)':'' ?></td><td><?= e($user['email']) ?></td><td><?= e(ucfirst($user['role'])) ?></td><td><span class="purpose-badge <?= $user['is_blocked']?'sale':'rent' ?>"><?= $user['is_blocked']?'Blocked':'Active' ?></span></td><td><?php if($user['id']!==$_SESSION['admin_id']): ?><div class="user-actions"><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="id" value="<?= $user['id'] ?>"><input type="hidden" name="action" value="<?= $user['is_blocked']?'unblock':'block' ?>"><button class="primary-button"><?= $user['is_blocked']?'Unblock':'Block' ?></button></form><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="id" value="<?= $user['id'] ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="role" value="<?= $user['role']==='admin'?'user':'admin' ?>"><button class="role-button">Make <?= $user['role']==='admin'?'User':'Admin' ?></button></form></div><?php else: ?>Protected account<?php endif; ?></td></tr><?php endforeach; ?><?php if(!$users): ?><tr><td colspan="5" class="empty-state">No matching users.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__.'/footer.php'; ?>
