<?php
declare(strict_types=1);
require_once __DIR__.'/mailer.php';
require_once dirname(__DIR__).'/includes/company-profile.php';

function enquiryContext(string $kind, string $slug=''): array {
    $company=publicCompanyProfile();
    $context=['kind'=>$kind,'slug'=>$slug,'title'=>$company['name'],'recipient'=>$company['email'],'details'=>[]];
    if($kind==='company')return $context;
    if(!in_array($kind,['project','property','agent'],true)||!preg_match('/^[a-z0-9][a-z0-9-]{0,199}$/D',$slug))throw new InvalidArgumentException('This email option is unavailable.');
    if($kind==='project'){require_once __DIR__.'/projects-store.php';$record=projectFind($slug);}
    elseif($kind==='property'){$query=Database::getConnection()->prepare('SELECT payload FROM properties WHERE slug=?');$query->execute([$slug]);$raw=$query->fetchColumn();$record=$raw?json_decode($raw,true,512,JSON_THROW_ON_ERROR):null;if($record&&($record['status']??'published')!=='published')$record=null;}
    else{require_once __DIR__.'/agents-store.php';$record=agentFind($slug);}
    if(!$record)throw new InvalidArgumentException('This listing is no longer available.');
    $context['title']=(string)($record[$kind==='agent'?'name':'title']);
    $context['details']['Location']=implode(', ',array_filter([$record['city']??'',$record['state']??'']));
    if($kind==='agent'){
        $context['details']['Role']=(string)($record['position']??'');
        $email=(string)($record['email']??'');
        if(filter_var($email,FILTER_VALIDATE_EMAIL))$context['recipient']=$email;
        else $context['details']['Contact route']='Company inbox — this agent has no direct email configured';
    }else{
        $context['details']['Type']=(string)($record['type']??'');
        $price=(float)($record[$kind==='project'?'price_from':'price']??0);
        $context['details']['Price']=$price>0?($kind==='project'?'From ':'').'PKR '.number_format($price):'Price on request';
        $context['details'][$kind==='project'?'Development status':'Purpose']=(string)($record[$kind==='project'?'development_status':'purpose']??'');
    }
    $context['details']=array_filter($context['details'],static fn($v)=>$v!=='');
    return $context;
}
function enquiryValues(array $input): array {
    $values=[];
    foreach(['name'=>100,'email'=>254,'phone'=>40,'subject'=>150,'message'=>5000] as $key=>$max){
        $value=$input[$key]??'';
        if(!is_string($value)||mb_strlen(trim($value))>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)||($key!=='message'&&preg_match('/[\r\n]/',$value)))throw new InvalidArgumentException('Enter a valid '.$key.'.');
        $values[$key]=trim($value);
    }
    if($values['name']==='')throw new InvalidArgumentException('Enter your name.');
    if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email address.');
    if(mb_strlen($values['message'])<10)throw new InvalidArgumentException('Write a message of at least 10 characters.');
    return $values;
}
function enquiryBuildMail(array $context,array $values): PHPMailer\PHPMailer\PHPMailer {
    $mail=royalmarkMailer();$mail->addAddress($context['recipient']);$mail->addReplyTo($values['email'],$values['name']);
    $subject=$values['subject']?:'Enquiry about '.$context['title'];
    $mail->Subject='Royalmark enquiry: '.preg_replace('/[\r\n]+/',' ',mb_substr($subject,0,180));
    $lines=['New website enquiry','Regarding: '.$context['title'],'Category: '.$context['kind']];
    foreach($context['details'] as $label=>$value)$lines[]=$label.': '.$value;
    $lines=array_merge($lines,['','Name: '.$values['name'],'Email: '.$values['email'],'Phone: '.($values['phone']?:'Not provided'),'','Message:',$values['message']]);
    $mail->AltBody=implode("\n",$lines);$mail->isHTML(true);
    $mail->Body='<div style="font:15px/1.7 Arial;color:#202020;max-width:640px;margin:auto;padding:24px;border-top:4px solid #d70000"><h2>Royalmark Estate enquiry</h2><div style="white-space:pre-wrap">'.htmlspecialchars($mail->AltBody,ENT_QUOTES,'UTF-8').'</div></div>';
    return $mail;
}
function sendEnquiryEmail(array $context,array $values): void {enquiryBuildMail($context,$values)->send();}
