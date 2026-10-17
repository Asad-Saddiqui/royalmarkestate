<?php
declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/Config/enquiry-email.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store');
try {
    $kind=is_string($_GET['kind']??null)?$_GET['kind']:'company';
    $slug=is_string($_GET['slug']??null)?$_GET['slug']:'';
    $context=enquiryContext($kind,$slug);
    $_SESSION['email_enquiry_csrf']??=bin2hex(random_bytes(32));
    $requests=$_SESSION['email_enquiry_requests']??[];
    $requests=array_filter($requests,static fn($item)=>($item['created']??0)>time()-7200);
    $_SESSION['email_enquiry_requests']=$requests;
    if($_SERVER['REQUEST_METHOD']==='GET'){
        $id=bin2hex(random_bytes(16));$requests[$id]=['created'=>time(),'kind'=>$kind,'slug'=>$slug,'sent'=>false];
        $_SESSION['email_enquiry_requests']=array_slice($requests,-100,null,true);
        echo json_encode(['context'=>$context,'csrf'=>$_SESSION['email_enquiry_csrf'],'request_id'=>$id],JSON_THROW_ON_ERROR|JSON_HEX_TAG);exit;
    }
    if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: GET, POST');exit;}
    if(!is_string($_POST['csrf']??null)||!hash_equals($_SESSION['email_enquiry_csrf'],$_POST['csrf'])){http_response_code(403);throw new InvalidArgumentException('Your session expired. Close and reopen the email form.');}
    $id=is_string($_POST['request_id']??null)?$_POST['request_id']:'';$request=$requests[$id]??null;
    if(!$request||$request['kind']!==$kind||$request['slug']!==$slug)throw new InvalidArgumentException('This email form expired. Close and reopen it.');
    if($request['sent']){echo json_encode(['ok'=>true,'message'=>'Your email has already been sent.']);exit;}
    $values=enquiryValues($_POST);
    $recent=array_values(array_filter($_SESSION['email_enquiry_times']??[],static fn($stamp)=>$stamp>time()-900));
    if(count($recent)>=5){http_response_code(429);throw new InvalidArgumentException('Please wait 15 minutes before sending another email.');}
    // Only session metadata is retained; messages are never inserted into the database.
    $recent[]=time();$_SESSION['email_enquiry_times']=$recent;
    sendEnquiryEmail($context,$values);
    $_SESSION['email_enquiry_requests'][$id]['sent']=true;
    echo json_encode(['ok'=>true,'message'=>'Your email has been sent successfully.']);
}catch(InvalidArgumentException $error){if(http_response_code()<400)http_response_code(422);echo json_encode(['ok'=>false,'message'=>$error->getMessage()]);}
catch(Throwable $error){error_log('Email enquiry failed: '.$error->getMessage());http_response_code(503);echo json_encode(['ok'=>false,'message'=>'Email could not be sent right now. Please try again shortly.']);}
