<?php
require __DIR__.'/auth.php';requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/company-team.php';
$items=companyTeam(true);$error='';$edit=is_string($_GET['edit']??null)?$_GET['edit']:'';
$data=['name'=>'','position'=>'','kind'=>'member','status'=>'published','bio'=>'','email'=>'','linkedin'=>'','sort_order'=>0,'photo'=>''];
foreach($items as $item)if($item['id']===$edit)$data=$item;
if($edit!==''&&!isset($data['id'])){http_response_code(404);exit('Team member not found.');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
    adminCsrf();
    try {
        $id=is_string($_POST['id']??null)?$_POST['id']:'';
        $existing=null;foreach($items as $item)if($item['id']===$id)$existing=$item;
        if(($_POST['action']??'')==='delete'){
            if(($_POST['confirm_delete']??'')!=='1')throw new InvalidArgumentException('Confirm deletion before removing this profile.');
            companyTeamWrite('delete',$id);
        }else{
            $data=array_merge($data,array_intersect_key($_POST,$data));
            $record=companyTeamValidate($_POST);$record['photo']=$existing['photo']??'';
            if(isset($_POST['remove_photo']))$record['photo']='';
            $upload=$_FILES['photo']??[];
            if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){
                if(($upload['error']??null)!==UPLOAD_ERR_OK||!is_uploaded_file($upload['tmp_name']??'')||($upload['size']??0)>5*1024*1024)throw new InvalidArgumentException('Upload a portrait up to 5 MB.');
                $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name'])]??'';
                $size=@getimagesize($upload['tmp_name']);
                if(!$ext||!$size||$size[0]*$size[1]>40000000)throw new InvalidArgumentException('Use a valid JPG, PNG or WebP portrait.');
                $filename='team-'.bin2hex(random_bytes(16)).'.'.$ext;
                if(!move_uploaded_file($upload['tmp_name'],dirname(__DIR__).'/assets/uploads/'.$filename))throw new RuntimeException('Photo could not be saved.');
                $record['photo']='./assets/uploads/'.$filename;
            }
            companyTeamWrite('save',$id,$record);
        }
        header('Location: ./team?saved=1',true,303);exit;
    }catch(InvalidArgumentException $exception){http_response_code(422);$error=$exception->getMessage();}
    catch(Throwable $exception){http_response_code(500);error_log('Team update failed: '.$exception->getMessage());$error='Unable to save this profile. Please try again.';}
}
$view='team';$title='CEO & Team';$properties=listingData(true);require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./team.css?v=<?= filemtime(__DIR__.'/team.css') ?>">
<div class="page-heading"><div><span class="eyebrow">ABOUT US</span><h1>CEO &amp; Team</h1><p>Manage the people shown on your About Us page.</p></div><a class="primary-button" href="./team#team-editor">+ Add team member</a></div>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if(isset($_GET['saved'])): ?><div class="success-note" role="status">Team updated successfully.</div><?php endif; ?>
<div class="team-admin-layout"><section class="panel"><h2><?= $edit!==''?'Edit profile':'Add a profile' ?></h2><form id="team-editor" method="post" enctype="multipart/form-data" class="team-editor"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="id" value="<?= e($edit) ?>"><input type="hidden" name="action" value="save">
<label>Name<input name="name" maxlength="100" value="<?= e($data['name']) ?>" required></label><label>Job title<input name="position" maxlength="120" value="<?= e($data['position']) ?>" placeholder="e.g. Chief Executive Officer" required></label>
<label>Profile role<select name="kind"><option value="member" <?= $data['kind']==='member'?'selected':'' ?>>Team member</option><option value="ceo" <?= $data['kind']==='ceo'?'selected':'' ?>>CEO</option></select></label>
<label>Visibility<select name="status"><option value="published" <?= $data['status']==='published'?'selected':'' ?>>Published</option><option value="unpublished" <?= $data['status']==='unpublished'?'selected':'' ?>>Unpublished</option></select></label>
<label>Biography / CEO message<textarea name="bio" rows="5" maxlength="3000"><?= e($data['bio']) ?></textarea></label><label>Email (optional)<input type="email" name="email" maxlength="254" value="<?= e($data['email']) ?>"></label><label>Professional profile link (optional)<input type="url" name="linkedin" maxlength="500" placeholder="https://..." value="<?= e($data['linkedin']) ?>"></label><label>Display order<input type="number" name="sort_order" min="0" max="9999" value="<?= (int)$data['sort_order'] ?>"></label><label>Portrait (JPG, PNG, WebP; up to 5 MB)<input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
<?php if($data['photo']): ?><img class="team-editor-photo" src="<?= e('../'.substr($data['photo'],2)) ?>" alt="Current portrait"><label><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label><?php endif; ?>
<button class="primary-button" type="submit">Save profile</button><?php if($edit!==''): ?><a href="./team">Cancel editing</a><?php endif; ?></form></section>
<section class="panel"><h2>Team directory (<?= count($items) ?>)</h2><p><a href="../about-us#about-team" target="_blank" rel="noopener">Preview About Us &nearr;</a></p><?php if(!$items): ?><p>Add your CEO and team members to start building this section.</p><?php endif; ?><div class="team-admin-cards"><?php foreach($items as $item): ?><article><div class="team-admin-person"><?php if($item['photo']): ?><img src="<?= e('../'.substr($item['photo'],2)) ?>" alt="" loading="lazy"><?php else: ?><span><?= e(mb_strtoupper(mb_substr($item['name'],0,1))) ?></span><?php endif; ?><div><h3><?= e($item['name']) ?></h3><p><?= e($item['position']) ?></p><small><?= $item['kind']==='ceo'?'CEO · ':'' ?><?= e(ucfirst($item['status'])) ?></small></div></div><a href="./team?edit=<?= e($item['id']) ?>#team-editor">Edit profile</a><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="action" value="delete"><label><input type="checkbox" name="confirm_delete" value="1" required> Confirm removal</label><button type="submit">Delete profile</button></form></article><?php endforeach; ?></div></section></div>
<?php require __DIR__.'/footer.php'; ?>
