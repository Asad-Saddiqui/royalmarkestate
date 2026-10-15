<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/Config/contacts.php';
require_once __DIR__.'/Config/booking-captcha.php';
require_once __DIR__.'/includes/contact-form.php';

header('Cache-Control: private, no-store');
$pageTitle='Contact Us';
$activePage='contact';
$mainClass='contact-page';
$extraCss=['./assets/booking-captcha.css','./assets/contact.css'];
$extraJs=['./assets/booking-captcha.js','./assets/contact.js'];
$_SESSION['contact_csrf']??=bin2hex(random_bytes(32));
$contactUser=contactCurrentUser();
$emptyValues=['name'=>(string)($contactUser['name']??''),'email'=>(string)($contactUser['email']??''),'phone'=>'','interest'=>'','message'=>''];
$contactState=['values'=>$emptyValues,'errors'=>[],'success'=>null,'csrf'=>$_SESSION['contact_csrf'],'request_id'=>''];
$contactRequests=$_SESSION['contact_form_requests']??[];
if(!is_array($contactRequests))$contactRequests=[];
$contactRequests=array_filter($contactRequests,static fn($item)=>is_array($item)&&(int)($item['created']??0)>time()-7200);
$_SESSION['contact_form_requests']=array_slice($contactRequests,-50,null,true);
$newContactNonce=static function():string {
    $nonce=bin2hex(random_bytes(16));
    $_SESSION['contact_form_requests'][$nonce]=['created'=>time()];
    $_SESSION['contact_form_requests']=array_slice($_SESSION['contact_form_requests'],-50,null,true);
    return $nonce;
};
$isContactPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
if(!$isContactPost){
    if(is_string($_GET['interest']??null))$contactState['values']['interest']=mb_substr(trim($_GET['interest']),0,120);
    $contactState['success']=$_SESSION['contact_success']??null;
    unset($_SESSION['contact_success']);
    $contactState['request_id']=$newContactNonce();
}else{
    foreach($emptyValues as $key=>$value)$contactState['values'][$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
    $nonce=is_string($_POST['request_id']??null)?$_POST['request_id']:'';
    $validNonce=preg_match('/^[a-f0-9]{32}$/D',$nonce)===1&&isset($_SESSION['contact_form_requests'][$nonce]);
    $contactState['request_id']=$validNonce?$nonce:$newContactNonce();
    $csrf=is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:'';
    $status=422;
    if(!hash_equals($_SESSION['contact_csrf'],$csrf)){
        $contactState['errors'][]='Your form session expired. Please enter the new security code and try again.';
        $status=403;
    }elseif(!is_string($_POST['website']??'')||trim((string)($_POST['website']??''))!==''){
        $contactState['errors'][]='We could not verify this message. Please try again.';
    }elseif(!$validNonce){
        $contactState['errors'][]='This form expired. Check your details and enter the new security code below.';
    }else{
        $captchaId=is_string($_POST['captcha_id']??null)?$_POST['captcha_id']:'';
        $captchaAnswer=is_string($_POST['captcha_answer']??null)?$_POST['captcha_answer']:'';
        if(!bookingCaptchaVerify('contact:general',$captchaId,$captchaAnswer))$contactState['errors'][]='The security code was incorrect or expired. Please enter the new code below.';
        $values=$contactState['values'];
        if($values['name']===''||mb_strlen($values['name'])>100)$contactState['errors'][]='Enter your full name using up to 100 characters.';
        if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL)||strlen($values['email'])>254)$contactState['errors'][]='Enter a valid email address.';
        if(mb_strlen($values['phone'])>40)$contactState['errors'][]='Keep your phone number within 40 characters.';
        if(mb_strlen($values['interest'])>120)$contactState['errors'][]='Keep your subject within 120 characters.';
        if(mb_strlen($values['message'])<10||mb_strlen($values['message'])>5000)$contactState['errors'][]='Write a message between 10 and 5,000 characters.';
        if(!$contactState['errors']){
            try{
                $saved=contactCreate($values+['request_id'=>$nonce],$contactUser?(int)$contactUser['id']:null);
                $contactState['success']=['id'=>(int)$saved['id'],'reference'=>(string)($saved['reference']??'')];
                $contactState['values']=$emptyValues;
                $contactState['request_id']=$newContactNonce();
                $status=200;
            }catch(InvalidArgumentException $error){
                $contactState['errors'][]=$error->getMessage();
            }catch(Throwable $error){
                error_log('Contact message failed: '.$error->getMessage());
                $contactState['errors'][]='We could not save your message. Please try again shortly.';
                $status=503;
            }
        }
    }
    $isContactAjax=strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest'||str_contains((string)($_SERVER['HTTP_ACCEPT']??''),'application/json');
    if($isContactAjax){
        ob_start();contactFormRender($contactState);$html=ob_get_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>!$contactState['errors'],'message'=>$contactState['errors']?$contactState['errors'][0]:'Message received. Our team will review it.','reference'=>$contactState['success']['reference']??null,'html'=>$html],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        exit;
    }
    if($contactState['success']){
        $_SESSION['contact_success']=$contactState['success'];
        header('Location: ./contact-us#contact-message',true,303);
        exit;
    }
    http_response_code($status);
}

$contactCompanyPayload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
$contactCompany=json_decode($contactCompanyPayload?:'{}',true);
if(!is_array($contactCompany))$contactCompany=[];
$companyValue=static fn(string $key):string=>is_string($contactCompany[$key]??null)?trim($contactCompany[$key]):'';
$companyEmail=$companyValue('email');
if(!filter_var($companyEmail,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($companyEmail),'@example.com'))$companyEmail='royalmarkestate@gmail.com';
$companyNumber=static function(string $number):string {
    $digits=preg_replace('/[^0-9]/','',$number);
    return preg_match('/^[1-9][0-9]{7,14}$/D',$digits)&&!preg_match('/0{5,}|([0-9])\1{6}$/D',$digits)?$digits:'';
};
$companyPhone=$companyNumber($companyValue('phone'));
$companyWhatsapp=$companyNumber($companyValue('whatsapp'));
$companyAddress=$companyValue('address');
if(preg_match('/\b(?:example|sample|placeholder)\b/i',$companyAddress))$companyAddress='';
$companyHours=$companyValue('hours');
require __DIR__.'/includes/header.php';
?>
<section class="page-intro contact-intro"><span class="eyebrow">CONTACT ROYALMARK ESTATE</span><h1>Let us help with<br><em>your next move.</em></h1><p>Looking for a property, exploring a project or have a question? Share what you need and our team will help you take the next step.</p></section>
<div class="contact-layout">
<aside class="contact-aside" aria-labelledby="contact-details-title">
    <span class="contact-kicker">LET'S CONNECT</span><h2 id="contact-details-title">A conversation starts here.</h2><p>Send us a message or get in touch using the company contact details below.</p>
    <div class="contact-channels">
        <a class="contact-channel" href="mailto:<?= e($companyEmail) ?>"><span class="contact-channel-icon"><?= menuIcon('contact') ?></span><span><small>Email us</small><strong><?= e($companyEmail) ?></strong></span><span class="contact-channel-arrow" aria-hidden="true">&nearr;</span></a>
        <?php if($companyPhone!==''): ?><a class="contact-channel" href="tel:+<?= e($companyPhone) ?>"><span class="contact-channel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2Z"/></svg></span><span><small>Call the team</small><strong><?= e($companyValue('phone')) ?></strong></span><span class="contact-channel-arrow" aria-hidden="true">&nearr;</span></a><?php endif; ?>
        <?php if($companyWhatsapp!==''): ?><a class="contact-channel contact-whatsapp" href="https://wa.me/<?= e($companyWhatsapp) ?>" target="_blank" rel="noopener"><span class="contact-channel-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 11.5a8.5 8.5 0 0 1-12.6 7.4L3 20l1.2-4.8a8.5 8.5 0 1 1 16.3-3.7Z"/><path d="M8 7.5c-.5 2.5 3 6 5.5 6l1-1.5-2-1-1 1-2-2 1-1-1-2Z"/></svg></span><span><small>Chat on WhatsApp</small><strong><?= e($companyValue('whatsapp')) ?></strong></span><span class="contact-channel-arrow" aria-hidden="true">&nearr;</span></a><?php endif; ?>
    </div>
    <?php if($companyAddress!==''||$companyHours!==''): ?><div class="contact-office"><?php if($companyAddress!==''): ?><div><span class="contact-kicker">OFFICE ADDRESS</span><p><?= nl2br(e($companyAddress)) ?></p></div><?php endif; ?><?php if($companyHours!==''): ?><div><span class="contact-kicker">OFFICE HOURS</span><p><?= e($companyHours) ?></p></div><?php endif; ?></div><?php endif; ?>
    <div class="contact-guidance"><h3>Have a property in mind?</h3><p>Include the property or project name, your preferred area and any questions you would like us to answer.</p><div><a class="text-link" href="./property-listing">Explore properties &rarr;</a><a class="text-link" href="./projects">Explore projects &rarr;</a></div></div>
</aside>
<?php contactFormRender($contactState); ?>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
