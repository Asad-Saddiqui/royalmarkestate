<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/Config/company-team.php';

$profiles=[
    ['Ahmed Khan (Demo)','Chief Executive Officer','ceo',0,'Our aim is to make each property journey clear, informed and personal. We believe in listening carefully, presenting useful information and staying available at every step. This is a fictional CEO profile for demonstration; replace it with your actual CEO and message.'],
    ['Sara Ahmed (Demo)','Head of Sales','member',1,'Helps buyers explore properties, compare their options and plan viewings. This is a fictional team profile for demonstration.'],
    ['Ali Raza (Demo)','Property Consultant','member',2,'Helps clients discuss locations, property requirements and their next steps. This is a fictional team profile for demonstration.'],
    ['Ayesha Malik (Demo)','Client Relations Manager','member',3,'Supports client enquiries, coordinates follow-ups and assists with viewing arrangements. This is a fictional team profile for demonstration.'],
];
$existing=companyTeam(true);$names=array_column($existing,'name');$hasCeo=in_array('ceo',array_column($existing,'kind'),true);$added=0;
foreach($profiles as [$name,$position,$kind,$order,$bio]){
    if(in_array($name,$names,true)||($kind==='ceo'&&$hasCeo))continue;
    $record=companyTeamValidate(['name'=>$name,'position'=>$position,'kind'=>$kind,'sort_order'=>$order,'bio'=>$bio,'status'=>'published','email'=>'','linkedin'=>'']);
    $portraits=['Ahmed Khan (Demo)'=>'ahmed-demo','Sara Ahmed (Demo)'=>'sara-demo','Ali Raza (Demo)'=>'ali-demo','Ayesha Malik (Demo)'=>'ayesha-demo'];
    $record['photo']='./assets/team/'.$portraits[$name].'.jpg';companyTeamWrite('save','',$record);$added++;
}
echo "Added $added published demo profiles. Existing profiles preserved.".PHP_EOL;
