<?php
declare(strict_types=1);

function emailComposeUrl(string $recipient,string $subject='',string $body=''): string
{
    if(!filter_var($recipient,FILTER_VALIDATE_EMAIL))return './contact-us';
    $domain=strtolower(substr(strrchr($recipient,'@'),1));
    if(in_array($domain,['gmail.com','googlemail.com'],true))return 'https://mail.google.com/mail/?'.http_build_query(['view'=>'cm','fs'=>'1','to'=>$recipient,'su'=>$subject,'body'=>$body],'','&',PHP_QUERY_RFC3986);
    if(in_array($domain,['outlook.com','hotmail.com','live.com','msn.com'],true))return 'https://outlook.live.com/mail/0/deeplink/compose?'.http_build_query(['to'=>$recipient,'subject'=>$subject,'body'=>$body],'','&',PHP_QUERY_RFC3986);
    if(in_array($domain,['yahoo.com','ymail.com','rocketmail.com'],true))return 'https://compose.mail.yahoo.com/?'.http_build_query(['to'=>$recipient,'subject'=>$subject,'body'=>$body],'','&',PHP_QUERY_RFC3986);
    return 'mailto:'.$recipient.'?'.http_build_query(['subject'=>$subject,'body'=>$body],'','&',PHP_QUERY_RFC3986);
}
