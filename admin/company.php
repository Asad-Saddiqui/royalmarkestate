<?php
require __DIR__.'/auth.php';requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';require dirname(__DIR__).'/includes/listings.php';
$fields=['name'=>['Company name',150,'text'],'tagline'=>['Tagline',200,'text'],'email'=>['Email address',254,'email'],'phone'=>['Phone number',40,'tel'],'whatsapp'=>['WhatsApp number',40,'tel'],'website'=>['Website URL',300,'url'],'address'=>['Office address',500,'text'],'city'=>['City',100,'text'],'state'=>['State / Province',100,'text'],'country'=>['Country',100,'text'],'zip'=>['Postal code',30,'text'],'hours'=>['Office hours',200,'text'],'facebook'=>['Facebook URL',300,'url'],'instagram'=>['Instagram URL',300,'url'],'linkedin'=>['LinkedIn URL',300,'url'],'tiktok'=>['TikTok URL',300,'url'],'description'=>['About company',5000,'textarea']];
$defaults=[
 'name'=>'Royalmark Estate',
 'tagline'=>'Your Trusted Real Estate Partner',
 'email'=>'info@example.com',
 'phone'=>'+92 300 0000000',
 'whatsapp'=>'+92 300 0000000',
 'website'=>'https://royalmarkestate.com',
 'address'=>'Office 1, Example Business Centre',
 'city'=>'Lahore',
 'state'=>'Punjab',
 'country'=>'Pakistan',
 'zip'=>'54000',
 'hours'=>'Monday - Saturday, 9:00 AM - 6:00 PM',
 'facebook'=>'https://www.facebook.com/your-company',
 'instagram'=>'https://www.instagram.com/your-company',
 'linkedin'=>'https://www.linkedin.com/company/your-company',
 'description'=>'Royalmark Estate helps clients explore residential and commercial properties, compare locations and find opportunities that suit their needs. Our focus is clear information, helpful guidance and a smooth property journey.'
];
$stored=adminDb()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();$data=$stored?json_decode($stored,true):[];$data=is_array($data)?$data:[];$usingDefaults=[];foreach($defaults as $key=>$value){if(!isset($data[$key])||!is_string($data[$key])||trim($data[$key])===''){$data[$key]=$value;$usingDefaults[]=$key;}}$errors=[];
$data['tiktok']=is_string($data['tiktok']??null)?trim($data['tiktok']):'';
if($_SERVER['REQUEST_METHOD']==='POST'){
 adminCsrf();foreach($fields as $key=>$field){$data[$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';if(mb_strlen($data[$key])>$field[1])$errors[]=$field[0].' is too long.';if($data[$key]!==''&&$field[2]==='email'&&!filter_var($data[$key],FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email address.';if($data[$key]!==''&&$field[2]==='url'&&(!filter_var($data[$key],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($data[$key],PHP_URL_SCHEME)),['https','http'],true)))$errors[]='Enter a valid HTTP or HTTPS URL for '.$field[0].'.';}
 if($data['name']==='')$errors[]='Company name is required.';
 if(!$errors){try{$query=adminDb()->prepare('INSERT INTO company_profile (id,payload) VALUES (1,?) ON DUPLICATE KEY UPDATE payload=VALUES(payload)');$query->execute([json_encode($data,JSON_THROW_ON_ERROR)]);$_SESSION['company_saved']=true;header('Location: ./company');exit;}catch(PDOException $exception){$errors[]='Company information could not be saved. Please try again.';}}
}
$saved=$_SESSION['company_saved']??false;unset($_SESSION['company_saved']);$properties=listingData();$view='company';$title='Company Details';require __DIR__.'/header.php';
?>
<div class="page-heading"><div><span class="eyebrow">COMPANY PROFILE</span><h1>Company Details</h1><p>Keep your business profile, contact details and office information in one place.</p></div></div>
<?php if($usingDefaults&&$_SERVER['REQUEST_METHOD']!=='POST'): ?><div class="success-note" role="status">Default examples are shown for missing information. Replace the sample contact details, address and social links, then save your company profile.</div><?php endif; ?>
<?php if($saved): ?><div class="success-note" role="status">Company information saved successfully.</div><?php endif; ?>
<?php if($errors): ?><div class="form-error" role="alert"><ul><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="property-form"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>">
<?php foreach(['Business information'=>['name','tagline','description'],'Contact details'=>['email','phone','whatsapp','website'],'Office location'=>['address','city','state','country','zip','hours'],'Social profiles'=>['facebook','instagram','linkedin','tiktok']] as $section=>$keys): ?><section class="panel form-section"><h2><?= e($section) ?></h2><div class="form-grid"><?php foreach($keys as $key): $field=$fields[$key]; ?><label><?= e($field[0]) ?><?php if($field[2]==='textarea'): ?><textarea name="<?= $key ?>" rows="5" maxlength="<?= $field[1] ?>"><?= e($data[$key]) ?></textarea><?php else: ?><input name="<?= $key ?>" type="<?= $field[2] ?>" maxlength="<?= $field[1] ?>" value="<?= e($data[$key]) ?>" <?= $key==='name'?'required':'' ?> <?= $key==='tiktok'?'placeholder="https://www.tiktok.com/@yourhandle"':'' ?>><?php endif; ?></label><?php endforeach; ?></div></section><?php endforeach; ?>
<div class="form-actions"><a href="./settings">Settings</a><button class="primary-button" type="submit">Save company details</button></div></form>
<?php require __DIR__.'/footer.php'; ?>
