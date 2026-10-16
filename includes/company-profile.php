<?php
require_once dirname(__DIR__).'/Config/Database.php';

/** Prepare saved company details for display on public pages. */
function publicCompanyDetails(array $record): array
{
    $value=static fn(string $key):string=>is_string($record[$key]??null)?trim($record[$key]):'';
    $defaults=[
        'name'=>'Royalmark Estate',
        'tagline'=>'Your Trusted Real Estate Partner',
        'description'=>'Royalmark Estate helps clients explore residential and commercial properties, compare locations and find opportunities that suit their needs. Our focus is clear information, helpful guidance and a smooth property journey.',
    ];
    $details=[];
    foreach($defaults as $key=>$fallback)$details[$key]=$value($key)?:$fallback;
    foreach(['address','city','state','country','zip','hours'] as $key)$details[$key]=$value($key);

    $isExample=static fn(string $text):bool=>(bool)preg_match('~\b(?:example|sample|placeholder)\b|your[-_ ]company~i',$text);
    if($isExample($details['address']))$details['address']='';
    foreach(['city','state','country','zip','hours'] as $key){
        if($isExample($details[$key]))$details[$key]='';
    }
    $details['location']=implode(', ',array_values(array_unique(array_filter([
        $details['city'],$details['state'],$details['country'],
    ],static fn(string $part):bool=>$part!==''))));
    $email=$value('email');
    $details['email']=filter_var($email,FILTER_VALIDATE_EMAIL)&&!$isExample($email)?$email:'royalmarkestate@gmail.com';

    foreach(['phone','whatsapp'] as $key){
        $number=$value($key);
        $digits=preg_replace('/[^0-9]/','',$number);
        $valid=preg_match('/^[+0-9().\s-]+$/D',$number)
            &&preg_match($key==='whatsapp'?'/^[1-9][0-9]{7,14}$/D':'/^[0-9]{7,15}$/D',$digits)
            &&!preg_match('/0{5,}|([0-9])\1{6}/',$digits);
        $details[$key]=$valid?$number:'';
        $details[$key.'_url']=$valid?($key==='whatsapp'?'https://wa.me/'.$digits:'tel:'.(str_starts_with($number,'+')?'+':'').$digits):'';
    }

    foreach(['website','facebook','instagram','linkedin','tiktok'] as $key){
        $url=$value($key);
        $valid=filter_var($url,FILTER_VALIDATE_URL)
            &&in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)
            &&parse_url($url,PHP_URL_USER)===null&&parse_url($url,PHP_URL_PASS)===null
            &&!$isExample($url);
        $details[$key]=$valid?$url:'';
    }
    $details['website_label']=$details['website']!==''?(string)parse_url($details['website'],PHP_URL_HOST):'';
    return $details;
}

function publicCompanyProfile(): array
{
    $payload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
    $record=is_string($payload)?json_decode($payload,true):[];
    return publicCompanyDetails(is_array($record)?$record:[]);
}
