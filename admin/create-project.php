<?php
require __DIR__.'/auth.php';
requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/projects-store.php';
require_once dirname(__DIR__).'/Config/property-documents.php';
require_once dirname(__DIR__).'/Config/blog.php';

function projectFormText(string $key, array $source): string {
    return is_scalar($source[$key]??null)?trim((string)$source[$key]):'';
}
function projectFormAsset(string $url): string {
    if(str_starts_with($url,'./'))return '../'.substr($url,2);
    return $url;
}
function projectFormRows(string $key, array $source, int $limit, array &$errors): array {
    $rows=$source[$key]??[];
    if(!is_array($rows)||!array_is_list($rows)||count($rows)>$limit){$errors[]='Add up to '.$limit.' valid '.str_replace('_',' ',$key).'.';return [];}
    return $rows;
}
function projectFormRetained(string $key, array $existing, array $source, array &$errors): array {
    $saved=$existing[$key]??[];$removed=$source['remove_'.$key]??[];
    if(!is_array($saved))$saved=[];
    if(!is_array($removed)||count($removed)>count($saved)){$errors[]='Invalid saved-file selection.';return $saved;}
    $indexes=[];
    foreach($removed as $index){if(!is_scalar($index)||!ctype_digit((string)$index)||!array_key_exists((int)$index,$saved)){$errors[]='Invalid saved-file selection.';continue;}$indexes[]=(int)$index;}
    return array_values(array_filter($saved,static fn($item,$index)=>!in_array($index,$indexes,true),ARRAY_FILTER_USE_BOTH));
}

$errors=[];$editSlug=is_string($_GET['slug']??null)?trim($_GET['slug']):'';
$existing=$editSlug!==''?projectFind($editSlug,true):null;
if($editSlug!==''&&!$existing){http_response_code(404);exit('Project not found.');}
$isPost=$_SERVER['REQUEST_METHOD']==='POST';
$postSetting=trim(ini_get('post_max_size'));
$postMultiplier=match(strtolower(substr($postSetting,-1))){'g'=>1024**3,'m'=>1024**2,'k'=>1024,default=>1};
$postLimit=(float)$postSetting*$postMultiplier;
if($isPost&&$postLimit>0&&(float)($_SERVER['CONTENT_LENGTH']??0)>$postLimit){
    http_response_code(413);$errors[]='The combined upload exceeds this server\'s '.$postSetting.' request limit. Use fewer or smaller files and try again.';$isPost=false;
}
$source=$isPost?$_POST:($existing??[]);
$limits=['title'=>150,'subtitle'=>220,'city'=>100,'type'=>100,'address'=>300,'state'=>100,'zip'=>30,'overview'=>15000,'developer_name'=>150,'developer_description'=>6000,'developer_email'=>254,'developer_phone'=>40,'developer_website'=>500,'noc_authority'=>150,'noc_reference'=>150,'noc_description'=>6000,'project_size'=>120];
$fields=array_merge(array_keys($limits),['development_status','status','progress','price_from','price_to','area_from','area_to','total_units','total_floors','completion_date','latitude','longitude','noc_status']);
$data=[];foreach($fields as $field)$data[$field]=projectFormText($field,$source);
if(!$isPost&&!$existing){$data['status']='unpublished';$data['development_status']='Upcoming';$data['noc_status']='Not provided';$data['progress']='0';}
$data['featured']=$isPost?($_POST['featured']??null)==='1':projectIsFeatured($existing??[]);
$data['is_sample']=$isPost?isset($_POST['is_sample']):!empty($existing['is_sample']);
$data['amenities']=is_array($source['amenities']??null)?$source['amenities']:[];
$data['nearest_locations']=is_array($source['nearest_locations']??null)?$source['nearest_locations']:[];
$data['videos']=is_array($source['videos']??null)?$source['videos']:[];
$developmentStatuses=['Upcoming','Under construction','Completed'];
$nocStatuses=['Not provided','Pending','Approved','Not required'];
$options=projectFilterOptions(projectData(true));

if($isPost){
    adminCsrf();
    foreach($limits as $field=>$limit){
        if(in_array($field,['title','city','type','overview','developer_name'],true)&&$data[$field]==='')$errors[]=ucfirst(str_replace('_',' ',$field)).' is required.';
        if(mb_strlen($data[$field])>$limit)$errors[]=ucfirst(str_replace('_',' ',$field)).' is too long.';
    }
    if(!in_array($data['status'],['published','unpublished'],true))$errors[]='Choose a valid publication status.';
    if(!in_array($data['development_status'],$developmentStatuses,true))$errors[]='Choose a valid development stage.';
    if(!in_array($data['noc_status'],$nocStatuses,true))$errors[]='Choose a valid NOC status.';
    foreach(['price_from'=>100000000000,'price_to'=>100000000000,'area_from'=>100000000,'area_to'=>100000000] as $key=>$maximum){
        if($data[$key]===''&&$key!=='price_from')continue;
        if(!is_numeric($data[$key])||!is_finite((float)$data[$key])||(float)$data[$key]<0||(float)$data[$key]>$maximum)$errors[]='Enter a valid '.str_replace('_',' ',$key).'.';
    }
    foreach(['price','area'] as $prefix)if($data[$prefix.'_from']!==''&&$data[$prefix.'_to']!==''&&is_numeric($data[$prefix.'_from'])&&is_numeric($data[$prefix.'_to'])&&(float)$data[$prefix.'_to']<(float)$data[$prefix.'_from'])$errors[]=ucfirst($prefix).' maximum must be greater than or equal to the minimum.';
    foreach(['progress'=>100,'total_units'=>1000000,'total_floors'=>1000] as $key=>$maximum){
        if($data[$key]===''&&$key!=='progress')continue;
        if(!ctype_digit($data[$key])||(int)$data[$key]>$maximum)$errors[]='Enter a whole number from 0 to '.$maximum.' for '.str_replace('_',' ',$key).'.';
    }
    if($data['completion_date']!==''){
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$data['completion_date']);
        if(!$date||$date->format('Y-m-d')!==$data['completion_date'])$errors[]='Enter a valid completion date.';
    }
    if($data['developer_email']!==''&&!filter_var($data['developer_email'],FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid developer email.';
    if($data['developer_website']!==''&&(!filter_var($data['developer_website'],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($data['developer_website'],PHP_URL_SCHEME)),['https','http'],true)))$errors[]='Developer website must be a valid HTTP or HTTPS URL.';
    if(($data['latitude']==='')!==($data['longitude']===''))$errors[]='Enter both latitude and longitude, or leave both empty.';
    foreach(['latitude'=>90,'longitude'=>180] as $key=>$maximum)if($data[$key]!==''&&(!is_numeric($data[$key])||!is_finite((float)$data[$key])||abs((float)$data[$key])>$maximum))$errors[]='Enter a valid '.$key.'.';

    $amenities=[];
    foreach(projectFormRows('amenities',$_POST,30,$errors) as $value){
        if(!is_string($value)){$errors[]='Invalid amenity.';continue;}
        $value=trim($value);if($value==='')continue;
        if(mb_strlen($value)>100){$errors[]='Amenity names may contain up to 100 characters.';continue;}$amenities[]=$value;
    }
    $amenities=array_values(array_unique($amenities));
    $nearby=[];
    foreach(projectFormRows('nearest_locations',$_POST,20,$errors) as $entry){
        if(!is_array($entry)||!is_string($entry['name']??null)||!is_string($entry['distance']??null)){$errors[]='Invalid nearby location.';continue;}
        $name=trim($entry['name']);$distance=trim($entry['distance']);if($name===''&&$distance==='')continue;
        if($name===''||mb_strlen($name)>150||mb_strlen($distance)>80){$errors[]='Nearby places need a name up to 150 characters and distance up to 80 characters.';continue;}$nearby[]=['name'=>$name,'distance'=>$distance];
    }
    $videos=[];
    foreach(projectFormRows('videos',$_POST,10,$errors) as $entry){
        if(!is_array($entry)||!is_string($entry['url']??null)||!is_string($entry['title']??null)||!in_array($entry['type']??'', ['video','short'],true)){$errors[]='Invalid YouTube entry.';continue;}
        $url=trim($entry['url']);$title=trim($entry['title']);if($url===''&&$title==='')continue;
        $youtubeId=blogYoutubeId($url);
        if(!$youtubeId||mb_strlen($url)>500||mb_strlen($title)>150){$errors[]='Use a valid YouTube video or Shorts link and a title up to 150 characters.';continue;}
        $videos[]=['url'=>'https://www.youtube.com/watch?v='.$youtubeId,'type'=>$entry['type'],'title'=>$title?:($entry['type']==='short'?'Project short':'Project video')];
    }

    $cover=$_FILES['photo']??null;$coverExtension='';$uploads=[];$retained=[];
    if(!is_array($cover)||($cover['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){if(!$existing)$errors[]='Upload a project cover image.';}
    elseif(!is_int($cover['error']??null)||$cover['error']!==UPLOAD_ERR_OK||!is_string($cover['tmp_name']??null)||!is_uploaded_file($cover['tmp_name']))$errors[]='Cover image could not be uploaded.';
    else{
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($cover['tmp_name']);$coverExtension=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??'';
        $dimensions=@getimagesize($cover['tmp_name']);
        if(!$coverExtension||!$dimensions||($cover['size']??0)<=0||$cover['size']>5*1024*1024||$dimensions[0]*$dimensions[1]>40000000)$errors[]='Cover must be a valid JPEG, PNG or WebP up to 5 MB and 40 megapixels.';
    }
    foreach(['gallery'=>'Gallery','noc_documents'=>'NOC documents','pricing_documents'=>'Pricing documents','floor_plans'=>'Floor plans'] as $key=>$label){
        $retained[$key]=projectFormRetained($key,$existing??[],$_POST,$errors);
        try{
            if(isset($_FILES[$key])&&!is_array($_FILES[$key]))throw new InvalidArgumentException('Invalid upload.');
            $uploads[$key]=propertyDocumentUploads($_FILES[$key]??[]);
            if($key==='gallery')foreach($uploads[$key] as $file)if($file['type']!=='image')throw new InvalidArgumentException('Use JPEG, PNG or WebP images only.');
        }catch(InvalidArgumentException $error){$errors[]=$label.': '.$error->getMessage();$uploads[$key]=[];}
    }
    if(!$errors){
        $record=array_merge($existing??[],$data);
        $baseSlug=trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$data['title'])),'-')?:'project';
        $record['slug']=$existing['slug']??($baseSlug.'-'.bin2hex(random_bytes(4)));
        foreach(['price_from','price_to','area_from','area_to','latitude','longitude'] as $key)$record[$key]=$data[$key]===''?'':(float)$data[$key];
        foreach(['progress','total_units','total_floors'] as $key)$record[$key]=$data[$key]===''?'':(int)$data[$key];
        $record['amenities']=$amenities;$record['nearest_locations']=$nearby;$record['videos']=$videos;
        if($videos!==($existing['videos']??[]))$record['videos_are_inspiration']=false;
        $record['featured']=(bool)$data['featured'];$record['is_sample']=(bool)$data['is_sample'];
        $savedFiles=[];$saved=false;
        try{
            $uploadDir=dirname(__DIR__).'/assets/uploads';
            if(!is_dir($uploadDir)&&!mkdir($uploadDir,0755,true)&&!is_dir($uploadDir))throw new RuntimeException('Upload directory could not be created.');
            if($coverExtension!==''){
                $name='project-cover-'.bin2hex(random_bytes(16)).'.'.$coverExtension;$target=$uploadDir.'/'.$name;
                if(!move_uploaded_file($cover['tmp_name'],$target))throw new RuntimeException('Cover upload failed.');$savedFiles[]=$target;$record['image']='./assets/uploads/'.$name;
            }
            foreach(['gallery'=>'gallery','noc_documents'=>'noc','pricing_documents'=>'pricing','floor_plans'=>'floor'] as $key=>$category){
                $record[$key]=$retained[$key];
                if(!$uploads[$key])continue;
                $record[$key]=[];
                foreach($uploads[$key] as $file){
                    $name='project-'.$category.'-'.bin2hex(random_bytes(16)).'.'.$file['extension'];$target=$uploadDir.'/'.$name;
                    if(!move_uploaded_file($file['tmp'],$target))throw new RuntimeException('Document or gallery upload failed.');$savedFiles[]=$target;$url='./assets/uploads/'.$name;
                    $record[$key][]=$key==='gallery'?$url:['url'=>$url,'name'=>$file['name'],'type'=>$file['type']];
                }
            }
            projectSave($record,$existing?$editSlug:null);$saved=true;
        }catch(Throwable $error){foreach($savedFiles as $file)if(is_file($file))@unlink($file);error_log('Project save failed: '.$error->getMessage());$errors[]='Project could not be saved. Please try again.';}
        if($saved){
            if($existing)try{projectCleanupUploads($existing,$record);}catch(Throwable $error){error_log('Project upload cleanup could not complete.');}
            header('Location: ./projects?'.($existing?'updated':'created').'=1');exit;
        }
    }
}
$properties=listingData(true);$view='projects';$title=$existing?'Edit project':'Create project';
require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./project-form.css?v=<?= filemtime(__DIR__.'/project-form.css') ?>">
<script src="./project-form.js?v=<?= filemtime(__DIR__.'/project-form.js') ?>" defer></script>
<div class="page-heading"><div><span class="eyebrow">PROJECT MANAGEMENT</span><h1><?= $existing?'Edit project':'Create a project' ?></h1><p>Add project details, developer information, documents, media and nearby places.</p></div><a class="project-back-link" href="./projects">&larr; All projects</a></div>
<?php if($errors): ?><div class="form-error" role="alert"><strong>Please check the following:</strong><ul><?php foreach(array_unique($errors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="./create-project<?= $existing?'?slug='.rawurlencode($editSlug):'' ?>" enctype="multipart/form-data" class="property-form project-form">
<input type="hidden" name="csrf" value="<?= e(adminToken()) ?>">
<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">01</span><div><h2>Project details</h2><p>Give visitors a clear introduction to the development.</p></div></div>
<div class="form-grid">
<?php foreach(['title'=>['Project title',150,true],'subtitle'=>['Short introduction',220,false],'type'=>['Project type',100,true],'city'=>['City / location',100,true]] as $key=>$field): ?><label><?= e($field[0]) ?><input name="<?= $key ?>" maxlength="<?= $field[1] ?>" value="<?= e($data[$key]) ?>" <?= $field[2]?'required':'' ?> <?= in_array($key,['type','city'],true)?'list="project-'.$key.'-options"':'' ?>></label><?php endforeach; ?>
<datalist id="project-type-options"><?php foreach($options['types']??[] as $option): ?><option value="<?= e($option) ?>"><?php endforeach; ?></datalist><datalist id="project-city-options"><?php foreach($options['cities']??[] as $option): ?><option value="<?= e($option) ?>"><?php endforeach; ?></datalist>
<label>Development stage<select name="development_status"><?php foreach($developmentStatuses as $stage): ?><option <?= $data['development_status']===$stage?'selected':'' ?>><?= e($stage) ?></option><?php endforeach; ?></select></label>
<label>Visibility<select name="status"><option value="unpublished" <?= $data['status']==='unpublished'?'selected':'' ?>>Unpublished</option><option value="published" <?= $data['status']==='published'?'selected':'' ?>>Published</option></select></label>
<label>Starting price (PKR)<input type="number" name="price_from" min="0" max="100000000000" step="0.01" value="<?= e($data['price_from']) ?>" required></label>
<label>Maximum price (PKR, optional)<input type="number" name="price_to" min="0" max="100000000000" step="0.01" value="<?= e($data['price_to']) ?>"></label>
<?php foreach(['area_from'=>'Minimum area (sq.ft)','area_to'=>'Maximum area (sq.ft)'] as $key=>$label): ?><label><?= e($label) ?><input type="number" name="<?= $key ?>" min="0" max="100000000" step="0.01" value="<?= e($data[$key]) ?>"></label><?php endforeach; ?>
<label>Project size / land area<input name="project_size" maxlength="120" placeholder="e.g. 15 acres" value="<?= e($data['project_size']) ?>"></label>
<?php foreach(['total_units'=>['Total units',1000000],'total_floors'=>['Total floors',1000],'progress'=>['Construction progress (%)',100]] as $key=>$field): ?><label><?= e($field[0]) ?><input name="<?= $key ?>" type="number" min="0" max="<?= $field[1] ?>" step="1" value="<?= e($data[$key]) ?>" <?= $key==='progress'?'required':'' ?>></label><?php endforeach; ?>
<label>Expected completion<input type="date" name="completion_date" value="<?= e($data['completion_date']) ?>"></label>
</div><label>Project overview<textarea name="overview" rows="6" maxlength="15000" required><?= e($data['overview']) ?></textarea></label>
<div class="project-featured-setting"><label class="checkbox-label"><input type="checkbox" id="project-featured" name="featured" value="1" aria-describedby="project-featured-help" <?= $data['featured']?'checked':'' ?>> Featured project on home page</label><p id="project-featured-help">Show this project in the home page's Featured Projects section. It must also be published; unpublished projects stay hidden.</p></div>
<label class="checkbox-label"><input type="checkbox" name="is_sample" value="1" <?= $data['is_sample']?'checked':'' ?>> Sample project / illustrative content</label>
<p class="project-help">Unpublished projects remain available in your dashboard and are hidden from the public website.</p></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">02</span><div><h2>Developer information</h2><p>Help buyers identify and contact the developer.</p></div></div><div class="form-grid">
<label>Developer name<input name="developer_name" maxlength="150" value="<?= e($data['developer_name']) ?>" required></label><label>Developer email<input type="email" name="developer_email" maxlength="254" value="<?= e($data['developer_email']) ?>"></label><label>Developer phone<input type="tel" name="developer_phone" maxlength="40" value="<?= e($data['developer_phone']) ?>"></label><label>Developer website<input type="url" name="developer_website" maxlength="500" placeholder="https://" value="<?= e($data['developer_website']) ?>"></label></div><label>About the developer<textarea name="developer_description" rows="4" maxlength="6000"><?= e($data['developer_description']) ?></textarea></label></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">03</span><div><h2>NOC &amp; approvals</h2><p>Record the approval status and supporting documents.</p></div></div><div class="form-grid"><label>NOC status<select name="noc_status"><?php foreach($nocStatuses as $status): ?><option <?= $data['noc_status']===$status?'selected':'' ?>><?= e($status) ?></option><?php endforeach; ?></select></label><label>Issuing authority<input name="noc_authority" maxlength="150" value="<?= e($data['noc_authority']) ?>"></label><label>NOC / approval reference<input name="noc_reference" maxlength="150" value="<?= e($data['noc_reference']) ?>"></label></div><label>NOC description<textarea name="noc_description" rows="4" maxlength="6000"><?= e($data['noc_description']) ?></textarea></label>
<?php $uploadGroups=['noc_documents'=>['NOC documents','PDF, JPEG, PNG or WebP'], 'pricing_documents'=>['Pricing plan documents','PDF, JPEG, PNG or WebP'], 'floor_plans'=>['Floor plans','PDF, JPEG, PNG or WebP']]; $renderDocuments=function(string $key,array $group)use($existing,$source){ ?><div class="project-document-upload"><h3><?= e($group[0]) ?></h3><p>Upload up to 5 files, up to 10 MB each. New uploads replace this group; other groups stay unchanged.</p><?php if(!empty($existing[$key])): ?><div class="project-saved-documents"><?php foreach($existing[$key] as $index=>$document): ?><div class="project-saved-document"><a href="<?= e(projectFormAsset($document['url'])) ?>" target="_blank" rel="noopener"><?= listingIcon('layers') ?><span><?= e($document['name']) ?><small><?= strtoupper(e($document['type'])) ?></small></span></a><label class="checkbox-label"><input type="checkbox" name="remove_<?= $key ?>[]" value="<?= $index ?>" <?= in_array((string)$index,(array)($source['remove_'.$key]??[]),true)?'checked':'' ?>> Remove</label></div><?php endforeach; ?></div><?php endif; ?><label>Upload <?= e(strtolower($group[0])) ?><input type="file" name="<?= $key ?>[]" multiple accept="application/pdf,image/jpeg,image/png,image/webp" data-project-preview="documents"></label><div class="project-file-previews" aria-live="polite"></div></div><?php };$renderDocuments('noc_documents',$uploadGroups['noc_documents']); ?>
</section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">04</span><div><h2>Images &amp; gallery</h2><p>A cover image and additional photos bring your project to life.</p></div></div>
<div class="project-cover-upload"><label>Cover image<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-project-preview="cover" <?= $existing?'':'required' ?>></label><p>JPEG, PNG or WebP, up to 5 MB. The current cover stays unless you select a replacement.</p><div class="project-file-previews" aria-live="polite"><?php if($existing): ?><img class="project-cover-preview" src="<?= e(projectFormAsset($existing['image'])) ?>" alt="Current project cover"><?php endif; ?></div></div>
<div class="project-gallery-upload"><h3>Gallery images</h3><p>Up to 5 additional photos, up to 10 MB each. New uploads replace the gallery.</p><?php if(!empty($existing['gallery'])): ?><div class="project-saved-gallery"><?php foreach($existing['gallery'] as $index=>$image): ?><figure><img src="<?= e(projectFormAsset($image)) ?>" alt="Saved project gallery image <?= $index+1 ?>" loading="lazy"><label class="checkbox-label"><input type="checkbox" name="remove_gallery[]" value="<?= $index ?>" <?= in_array((string)$index,(array)($source['remove_gallery']??[]),true)?'checked':'' ?>> Remove photo</label></figure><?php endforeach; ?></div><?php endif; ?><label>Upload gallery images<input type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp" data-project-preview="gallery"></label><div class="project-file-previews" aria-live="polite"></div></div>
<p class="project-help">If validation reports an error, re-select any new files before saving again.</p></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">05</span><div><h2>Pricing plan &amp; floor plans</h2><p>Add documents visitors can preview and download.</p></div></div><?php $renderDocuments('pricing_documents',$uploadGroups['pricing_documents']);$renderDocuments('floor_plans',$uploadGroups['floor_plans']); ?></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">06</span><div><h2>Amenities</h2><p>Add facilities individually, up to 30 amenities.</p></div></div><div class="project-repeater" data-project-repeater="amenities" data-limit="30"><div class="project-entry-rows">
<?php foreach($data['amenities']?:[''] as $index=>$amenity): ?><div class="project-entry-row"><label>Amenity<input name="amenities[<?= $index ?>]" maxlength="100" value="<?= e(is_string($amenity)?$amenity:'') ?>" placeholder="e.g. Security, gym, swimming pool"></label><button type="button" class="property-entry-button" data-project-remove aria-label="Remove amenity">Remove</button></div><?php endforeach; ?>
</div><button type="button" class="property-entry-button" data-project-add>+ Add amenity</button><span class="project-entry-count" aria-live="polite"></span></div></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">07</span><div><h2>YouTube videos &amp; Shorts</h2><p>Use a separate entry for each video or Short, up to 10 entries.</p></div></div><div class="project-repeater" data-project-repeater="videos" data-limit="10"><div class="project-entry-rows">
<?php foreach($data['videos']?:[['title'=>'','url'=>'','type'=>'video']] as $index=>$entry): $entry=is_array($entry)?$entry:[]; ?><div class="project-entry-row is-video"><label>Video title<input name="videos[<?= $index ?>][title]" maxlength="150" value="<?= e(projectFormText('title',$entry)) ?>" placeholder="e.g. Project walkthrough"></label><label>YouTube link<input type="url" name="videos[<?= $index ?>][url]" maxlength="500" value="<?= e(projectFormText('url',$entry)) ?>" placeholder="https://www.youtube.com/watch?v=..."></label><label>Video type<select name="videos[<?= $index ?>][type]" data-native-select><option value="video" <?= ($entry['type']??'video')==='video'?'selected':'' ?>>Video</option><option value="short" <?= ($entry['type']??'')==='short'?'selected':'' ?>>Short</option></select></label><button type="button" class="property-entry-button" data-project-remove aria-label="Remove video">Remove</button></div><?php endforeach; ?>
</div><button type="button" class="property-entry-button" data-project-add>+ Add video / Short</button><span class="project-entry-count" aria-live="polite"></span></div></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">08</span><div><h2>Map location</h2><p>Provide the address and optional coordinates for an accurate map.</p></div></div><div class="form-grid"><label>Street address<input name="address" maxlength="300" value="<?= e($data['address']) ?>"></label><label>Province / state<input name="state" maxlength="100" value="<?= e($data['state']) ?>"></label><label>Postal code<input name="zip" maxlength="30" value="<?= e($data['zip']) ?>"></label><label>Latitude<input type="number" name="latitude" min="-90" max="90" step="any" value="<?= e($data['latitude']) ?>" placeholder="e.g. 31.5204"></label><label>Longitude<input type="number" name="longitude" min="-180" max="180" step="any" value="<?= e($data['longitude']) ?>" placeholder="e.g. 74.3587"></label></div></section>

<section class="panel form-section"><div class="project-section-heading"><span class="project-section-number">09</span><div><h2>Nearest locations</h2><p>Add nearby places and their distance or travel time, up to 20 entries.</p></div></div><div class="project-repeater" data-project-repeater="nearest_locations" data-limit="20"><div class="project-entry-rows">
<?php foreach($data['nearest_locations']?:[['name'=>'','distance'=>'']] as $index=>$entry): $entry=is_array($entry)?$entry:[]; ?><div class="project-entry-row is-nearby"><label>Place name<input name="nearest_locations[<?= $index ?>][name]" maxlength="150" value="<?= e(projectFormText('name',$entry)) ?>" placeholder="e.g. Hospital, school, motorway"></label><label>Distance / travel time<input name="nearest_locations[<?= $index ?>][distance]" maxlength="80" value="<?= e(projectFormText('distance',$entry)) ?>" placeholder="e.g. 2 km / 5 minutes"></label><button type="button" class="property-entry-button" data-project-remove aria-label="Remove nearby location">Remove</button></div><?php endforeach; ?>
</div><button type="button" class="property-entry-button" data-project-add>+ Add nearby location</button><span class="project-entry-count" aria-live="polite"></span></div></section>
<div class="form-actions project-form-actions"><a href="./projects">Cancel</a><button class="primary-button" type="submit"><?= $existing?'Save changes':'Create project' ?></button></div>
</form>
<?php require __DIR__.'/footer.php'; ?>
