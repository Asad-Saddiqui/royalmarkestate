<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/Config/bookings.php';
require_once dirname(__DIR__).'/Config/booking-captcha.php';

function bookingFormNonce(string $scope): string
{
    $nonce=bin2hex(random_bytes(16));
    $_SESSION['booking_form_requests'][$nonce]=['scope'=>$scope,'created'=>time()];
    return $nonce;
}

/** Prepare and process a listing request before the page sends any output. */
function bookingFormHandle(string $kind,array $listing,string $action,string $anchor): array
{
    header('Cache-Control: private, no-store');
    $scope=$kind.':'.(string)$listing['slug'];
    $csrfKey=$kind.'_csrf';
    $_SESSION[$csrfKey]??=bin2hex(random_bytes(32));
    $user=bookingCurrentUser();
    $values=['name'=>(string)($user['name']??''),'email'=>(string)($user['email']??''),'phone'=>'','message'=>'','request_type'=>'viewing','preferred_date'=>'','preferred_time'=>''];
    $state=['kind'=>$kind,'listing'=>$listing,'scope'=>$scope,'action'=>$action,'anchor'=>$anchor,'csrf'=>$_SESSION[$csrfKey],'user'=>$user,'values'=>$values,'errors'=>[],'success'=>null,'request_id'=>''];
    $requests=$_SESSION['booking_form_requests']??[];
    if(!is_array($requests))$requests=[];
    $requests=array_filter($requests,static fn($item)=>is_array($item)&&(int)($item['created']??0)>time()-7200);
    if(count($requests)>50)$requests=array_slice($requests,-50,null,true);
    $_SESSION['booking_form_requests']=$requests;
    $isPost=($_SERVER['REQUEST_METHOD']??'GET')==='POST';
    if(!$isPost){
        $state['success']=$_SESSION['booking_form_success'][$scope]??null;
        unset($_SESSION['booking_form_success'][$scope]);
        $state['request_id']=bookingFormNonce($scope);
        return $state;
    }
    foreach($values as $key=>$value)$state['values'][$key]=is_string($_POST[$key]??null)?trim($_POST[$key]):'';
    $nonce=is_string($_POST['request_id']??null)?$_POST['request_id']:'';
    $validNonce=preg_match('/^[a-f0-9]{32}$/D',$nonce)&&isset($requests[$nonce])&&($requests[$nonce]['scope']??null)===$scope;
    $state['request_id']=$validNonce?$nonce:bookingFormNonce($scope);
    $csrf=is_string($_POST['csrf']??null)?$_POST['csrf']:'';
    $status=422;
    if(!hash_equals($_SESSION[$csrfKey],$csrf)){
        $state['errors'][]='Your form session expired. Please refresh the page and try again.';
        $status=403;
    }elseif(!is_string($_POST['website']??'')||trim((string)($_POST['website']??''))!==''){
        $state['errors'][]='We could not verify this request. Please try again.';
    }elseif(!$validNonce){
        $state['errors'][]='This request form expired. Check your details and submit the new security code below.';
    }else{
        $captchaId=is_string($_POST['captcha_id']??null)?$_POST['captcha_id']:'';
        $captchaAnswer=is_string($_POST['captcha_answer']??null)?$_POST['captcha_answer']:'';
        if(!bookingCaptchaVerify($scope,$captchaId,$captchaAnswer)){
            $state['errors'][]='The security code was incorrect or expired. Please enter the new code below.';
        }else{
            try{
                $result=bookingCreate($kind,$listing,array_merge($state['values'],['request_id'=>$nonce]),$user?(int)$user['id']:null);
                $state['success']=['reference'=>(string)($result['reference']??''),'id'=>(int)($result['id']??0)];
                $state['values']=$values;
                $state['request_id']=bookingFormNonce($scope);
                $status=200;
            }catch(InvalidArgumentException $error){
                $state['errors'][]=$error->getMessage();
            }catch(Throwable $error){
                error_log('Booking request failed: '.$error->getMessage());
                $state['errors'][]='We could not save your request. Please try again shortly.';
                $status=503;
            }
        }
    }
    $isAjax=strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest'||str_contains((string)($_SERVER['HTTP_ACCEPT']??''),'application/json');
    if($isAjax){
        ob_start();bookingFormRender($state);$html=ob_get_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok'=>!$state['errors'],'message'=>$state['errors']?$state['errors'][0]:'Your request has been saved. Our team will confirm the arrangements.','reference'=>$state['success']['reference']??null,'html'=>$html],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        exit;
    }
    if($state['success']){
        $_SESSION['booking_form_success'][$scope]=$state['success'];
        header('Location: '.$action.'#'.$anchor,true,303);exit;
    }
    http_response_code($status);
    return $state;
}

function bookingFormRender(array $state): void
{
    $values=$state['values'];$anchor=$state['anchor'];$kind=$state['kind'];
    $today=new DateTimeImmutable('today');
    $bookingCaptchaScope=$state['scope'];
    ?>
<section class="pd-contact-card booking-card" id="<?= e($anchor) ?>" aria-labelledby="<?= e($anchor) ?>-title">
    <span class="booking-eyebrow">LET US PLAN YOUR NEXT STEP</span><h2 id="<?= e($anchor) ?>-title">Request a viewing or callback</h2><p class="booking-intro">Choose a preferred day to discuss this <?= $kind==='project'?'project':'property' ?> with Royalmark Estate.</p>
    <?php if($state['success']): ?><div class="booking-notice booking-success" role="status" tabindex="-1"><strong>Your request has been saved.</strong><?php if($state['success']['reference']!==''): ?><span>Reference: <b><?= e($state['success']['reference']) ?></b></span><?php endif; ?><p>Your preferred date is a request. Our team will confirm availability and arrangements.</p><?php if($state['user']): ?><a href="<?= $state['user']['role']==='user'?'./user/bookings':'./admin/bookings' ?>">View my requests &rarr;</a><?php else: ?><p><a href="./admin/login">Sign in</a> before your next request to track it in your dashboard.</p><?php endif; ?></div><?php endif; ?>
    <?php if($state['errors']): ?><div class="booking-notice booking-error" role="alert" tabindex="-1"><strong>Please check your request.</strong><ul><?php foreach($state['errors'] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form class="booking-form" method="post" action="<?= e($state['action'].'#'.$anchor) ?>" data-booking-form>
        <input type="hidden" name="csrf" value="<?= e($state['csrf']) ?>"><input type="hidden" name="request_id" value="<?= e($state['request_id']) ?>">
        <div class="booking-website" aria-hidden="true"><label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <label for="<?= e($anchor) ?>-name"><span class="booking-field-label">Your name <b aria-hidden="true">*</b></span><input id="<?= e($anchor) ?>-name" name="name" type="text" maxlength="100" value="<?= e($values['name']) ?>" autocomplete="name" placeholder="Your full name" required></label>
        <label for="<?= e($anchor) ?>-email"><span class="booking-field-label">Email address <b aria-hidden="true">*</b></span><input id="<?= e($anchor) ?>-email" name="email" type="email" maxlength="254" value="<?= e($values['email']) ?>" autocomplete="email" placeholder="you@example.com" required></label>
        <label for="<?= e($anchor) ?>-phone"><span class="booking-field-label">Phone number <b aria-hidden="true">*</b></span><input id="<?= e($anchor) ?>-phone" name="phone" type="tel" maxlength="40" value="<?= e($values['phone']) ?>" autocomplete="tel" placeholder="+92 3XX XXXXXXX" required></label>
        <label for="<?= e($anchor) ?>-type"><span class="booking-field-label">How can we help? <b aria-hidden="true">*</b></span><select id="<?= e($anchor) ?>-type" name="request_type" required><?php foreach(['viewing'=>'Arrange a viewing','call'=>'Request a callback','information'=>'Discuss more information'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $values['request_type']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <div class="booking-schedule"><label for="<?= e($anchor) ?>-date"><span class="booking-field-label">Preferred date <b aria-hidden="true">*</b></span><input id="<?= e($anchor) ?>-date" name="preferred_date" type="date" min="<?= $today->format('Y-m-d') ?>" max="<?= $today->modify('+365 days')->format('Y-m-d') ?>" value="<?= e($values['preferred_date']) ?>" required></label><label for="<?= e($anchor) ?>-time"><span class="booking-field-label">Preferred time <small>(optional)</small></span><input id="<?= e($anchor) ?>-time" name="preferred_time" type="time" value="<?= e($values['preferred_time']) ?>"></label></div>
        <p class="booking-time-note">All times are Pakistan Standard Time (PKT).</p>
        <label for="<?= e($anchor) ?>-message"><span class="booking-field-label">Your message <small>(optional)</small></span><textarea id="<?= e($anchor) ?>-message" name="message" rows="3" maxlength="5000" placeholder="Tell us what you would like to know"><?= e($values['message']) ?></textarea></label>
        <?php require __DIR__.'/booking-captcha.php'; ?>
        <button class="button booking-submit" type="submit"><svg class="booking-send-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 3-7 18-4-7-7-4L21 3ZM21 3 10 14"/></svg><span>Send request</span></button>
        <small class="booking-confirmation-note">Our team will review your request and confirm the details with you.</small>
    </form>
</section>
    <?php
}
