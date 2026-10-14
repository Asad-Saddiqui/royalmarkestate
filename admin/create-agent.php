<?php
require __DIR__.'/auth.php';
requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/agents-store.php';

function agentFormText(string $key,array $source):string{return is_scalar($source[$key]??null)?trim((string)$source[$key]):'';}
function agentFormEntries(string $key,array $source,array &$errors):array{
    $entries=$source[$key]??[];
    if(!is_array($entries)||!array_is_list($entries)||count($entries)>12){$errors[]='Add up to 12 valid '.str_replace('_',' ',$key).' entries.';return [];}
    $result=[];foreach($entries as $entry){if(!is_string($entry)){$errors[]='Invalid '.str_replace('_',' ',$key).' entry.';continue;}$entry=trim($entry);if($entry==='')continue;if(mb_strlen($entry)>80){$errors[]='Each '.str_replace('_',' ',$key).' entry may have up to 80 characters.';continue;}$result[]=$entry;}
    return array_values(array_unique($result));
}
$errors=[];$editSlug=is_string($_GET['slug']??null)?trim($_GET['slug']):'';$existing=$editSlug!==''?agentFind($editSlug,true):null;
if($editSlug!==''&&!$existing){http_response_code(404);exit('Agent not found.');}
$isPost=$_SERVER['REQUEST_METHOD']==='POST';$postSetting=trim(ini_get('post_max_size'));$postMultiplier=match(strtolower(substr($postSetting,-1))){'g'=>1024**3,'m'=>1024**2,'k'=>1024,default=>1};
if($isPost&&(float)$postSetting*$postMultiplier>0&&(float)($_SERVER['CONTENT_LENGTH']??0)>(float)$postSetting*$postMultiplier){http_response_code(413);$errors[]='The upload exceeds the server request limit. Use a smaller profile image and try again.';$isPost=false;}
$source=$isPost?$_POST:($existing??[]);
$limits=['slug'=>180,'name'=>120,'position'=>120,'city'=>100,'email'=>254,'phone'=>40,'whatsapp'=>40,'bio'=>6000,'photo_alt'=>200,'facebook'=>500,'instagram'=>500,'linkedin'=>500];
$data=[];foreach(array_merge(array_keys($limits),['experience_years','sort_order','status']) as $key)$data[$key]=agentFormText($key,$source);
$data['specialities']=is_array($source['specialities']??null)?$source['specialities']:[];$data['languages']=is_array($source['languages']??null)?$source['languages']:[];
$data['is_sample']=$isPost?isset($_POST['is_sample']):!empty($existing['is_sample']);
if(!$existing&&!$isPost){$data['position']='Property consultant';$data['status']='unpublished';$data['sort_order']='0';}
if($isPost){
    adminCsrf();
    foreach($limits as $key=>$limit){if(in_array($key,['name','position','city','bio'],true)&&$data[$key]==='')$errors[]=ucfirst($key).' is required.';if(mb_strlen($data[$key])>$limit)$errors[]=ucfirst(str_replace('_',' ',$key)).' is too long.';}
    if(!in_array($data['status'],['published','unpublished'],true))$errors[]='Choose a valid publication status.';
    if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email address.';
    foreach(['facebook','instagram','linkedin'] as $key)if($data[$key]!==''&&(!filter_var($data[$key],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($data[$key],PHP_URL_SCHEME)),['http','https'],true)))$errors[]='Enter a valid HTTP or HTTPS '.ucfirst($key).' profile URL.';
    if($data['experience_years']!==''&&(!ctype_digit($data['experience_years'])||(int)$data['experience_years']>80))$errors[]='Experience must be a whole number from 0 to 80 years.';
    if($data['sort_order']==='')$data['sort_order']='0';
    if(!ctype_digit($data['sort_order'])||(int)$data['sort_order']>9999)$errors[]='Display order must be a whole number from 0 to 9,999.';
    if($data['whatsapp']!==''){$digits=preg_replace('/\D/','',$data['whatsapp']);if(strlen($digits)<7||strlen($digits)>15||!preg_match('/^[+\d\s()\-]+$/',$data['whatsapp']))$errors[]='Enter a valid WhatsApp number including its country code.';}
    $data['specialities']=agentFormEntries('specialities',$_POST,$errors);$data['languages']=agentFormEntries('languages',$_POST,$errors);
    if(!$errors){
        $record=array_merge($existing??[],$data);$record['experience_years']=$data['experience_years']===''?0:(int)$data['experience_years'];$record['sort_order']=(int)$data['sort_order'];
        $record['photo']=isset($_POST['remove_photo'])?'':($existing['photo']??'');$uploaded='';$saved=false;
        try{
            if(isset($_FILES['photo'])&&!is_array($_FILES['photo']))throw new InvalidArgumentException('Invalid profile image upload.');
            $uploaded=agentUploadPhoto($_FILES['photo']??[]);if($uploaded!=='')$record['photo']=$uploaded;
            agentSave($record,$existing?$editSlug:null);$saved=true;
        }catch(InvalidArgumentException $exception){$errors[]=$exception->getMessage();}
        catch(Throwable $exception){error_log('Agent save failed: '.$exception->getMessage());$errors[]='The agent could not be saved. Please try again.';}
        if(!$saved&&$uploaded!=='')try{agentCleanupUploads([$uploaded]);}catch(Throwable $exception){error_log('Unsaved agent upload could not be removed.');}
        if($saved){header('Location: ./agents?'.($existing?'updated':'created').'=1',true,303);exit;}
    }
    if($errors)http_response_code(422);
}
$properties=listingData(true);$view='agents';$title=$existing?'Edit agent':'Create agent';require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./agents-admin.css?v=<?= filemtime(__DIR__.'/agents-admin.css') ?>">
<script src="./agent-form.js?v=<?= filemtime(__DIR__.'/agent-form.js') ?>" defer></script>
<section class="page-heading agent-page-heading"><div><span class="eyebrow">AGENT MANAGEMENT</span><h1><?= $existing?'Edit agent profile':'Add an agent' ?></h1><p>Build a complete profile for your property team.</p></div><a class="agent-back-link" href="./agents">&larr; All agents</a></section>
<?php if($errors): ?><div class="form-error" role="alert"><strong>Please check the following:</strong><ul><?php foreach(array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="./create-agent<?= $existing?'?slug='.rawurlencode($editSlug):'' ?>" enctype="multipart/form-data" class="property-form agent-form"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>">
<section class="panel form-section"><div class="agent-section-heading"><span>01</span><div><h2>Profile &amp; contact</h2><p>Introduce the agent and add their contact details.</p></div></div><div class="form-grid">
<label>Full name<input name="name" maxlength="120" value="<?= e($data['name']) ?>" required autocomplete="name"></label><label>Position / title<input name="position" maxlength="120" value="<?= e($data['position']) ?>" required placeholder="Property consultant"></label><label>City / location<input name="city" maxlength="100" value="<?= e($data['city']) ?>" required placeholder="e.g. Lahore"></label>
<label>Email address<input type="email" name="email" maxlength="254" value="<?= e($data['email']) ?>" autocomplete="email"></label><label>Phone number<input type="tel" name="phone" maxlength="40" value="<?= e($data['phone']) ?>" autocomplete="tel"></label><label>WhatsApp number<input type="tel" name="whatsapp" maxlength="40" value="<?= e($data['whatsapp']) ?>" placeholder="+92 3XX XXXXXXX"><small>Include the country code.</small></label>
<label>Profile URL slug (optional)<input name="slug" maxlength="180" value="<?= e($data['slug']) ?>" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="Generated from the agent name"><small>Lowercase letters, numbers and hyphens.</small></label><label>Experience (years, optional)<input type="number" name="experience_years" min="0" max="80" step="1" value="<?= e($data['experience_years']) ?>"></label><label>Website visibility<select name="status"><option value="unpublished" <?= $data['status']==='unpublished'?'selected':'' ?>>Unpublished</option><option value="published" <?= $data['status']==='published'?'selected':'' ?>>Published</option></select></label><label>Display order<input type="number" name="sort_order" min="0" max="9999" step="1" value="<?= e($data['sort_order']) ?>"><small>Lower numbers appear first.</small></label>
</div><label>About the agent<textarea name="bio" rows="5" maxlength="6000" required placeholder="Describe their areas of expertise and how they help clients."><?= e($data['bio']) ?></textarea></label><label class="checkbox-label"><input type="checkbox" name="is_sample" value="1" <?= $data['is_sample']?'checked':'' ?>> Sample profile / illustrative information</label><p class="agent-help">Unpublished agents stay in your dashboard and do not appear on the public website.</p></section>
<section class="panel form-section"><div class="agent-section-heading"><span>02</span><div><h2>Profile photo</h2><p>Add a clear portrait for the agent directory.</p></div></div><div class="agent-photo-editor"><div class="agent-photo-preview" aria-live="polite"><?php if(!empty($existing['photo'])): ?><img src="<?= e(str_starts_with($existing['photo'],'./')?'../'.substr($existing['photo'],2):$existing['photo']) ?>" alt="Current agent profile photo"><?php else: ?><span>No photo selected</span><?php endif; ?></div><div><label>Upload profile photo<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-agent-photo></label><p class="agent-help">JPEG, PNG or WebP, up to 5 MB. A new upload replaces the previous profile photo.</p><label>Photo description<input name="photo_alt" maxlength="200" value="<?= e($data['photo_alt']) ?>" placeholder="e.g. Portrait of the agent"></label><?php if(!empty($existing['photo'])): ?><label class="checkbox-label"><input type="checkbox" name="remove_photo" value="1" <?= isset($source['remove_photo'])?'checked':'' ?>> Remove current photo</label><?php endif; ?><p class="agent-photo-message" role="status"></p></div></div></section>
<?php foreach(['specialities'=>['03','Specialities','Add the property services or areas this agent handles.','Speciality','e.g. Residential sales'],'languages'=>['04','Languages','Add the languages clients can use to contact this agent.','Language','e.g. English, Urdu']] as $key=>$group): ?>
<section class="panel form-section"><div class="agent-section-heading"><span><?= $group[0] ?></span><div><h2><?= $group[1] ?></h2><p><?= $group[2] ?></p></div></div><div class="agent-repeater" data-agent-repeater="<?= $key ?>" data-label="<?= $group[3] ?>" data-placeholder="<?= $group[4] ?>"><div class="agent-entry-rows"><?php foreach($data[$key]?:[''] as $index=>$entry): ?><div class="agent-entry-row"><label><?= $group[3] ?><input name="<?= $key ?>[<?= $index ?>]" maxlength="80" value="<?= e(is_string($entry)?$entry:'') ?>" placeholder="<?= $group[4] ?>"></label><button type="button" class="property-entry-button" data-agent-remove aria-label="Remove <?= strtolower($group[3]) ?>">Remove</button></div><?php endforeach; ?></div><div class="agent-entry-toolbar"><button type="button" class="property-entry-button" data-agent-add>+ Add <?= strtolower($group[3]) ?></button><span class="agent-entry-count" aria-live="polite"></span></div></div></section>
<?php endforeach; ?>
<section class="panel form-section"><div class="agent-section-heading"><span>05</span><div><h2>Social profiles</h2><p>Add full profile links for the channels this agent uses.</p></div></div><div class="form-grid"><?php foreach(['facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn'] as $key=>$label): ?><label><?= $label ?><input type="url" name="<?= $key ?>" maxlength="500" value="<?= e($data[$key]) ?>" placeholder="https://"></label><?php endforeach; ?></div></section>
<div class="form-actions agent-form-actions"><a href="./agents">Cancel</a><button type="submit" class="primary-button"><?= $existing?'Save changes':'Create agent' ?></button></div></form>
<?php require __DIR__.'/footer.php'; ?>
