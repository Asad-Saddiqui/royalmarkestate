<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';

function companyTeam(bool $all=false): array {
    $json=Database::getConnection()->query('SELECT payload FROM company_team WHERE id=1')->fetchColumn();
    $items=$json?json_decode($json,true,512,JSON_THROW_ON_ERROR):[];
    if(!$all)$items=array_values(array_filter($items,static fn($item)=>$item['status']==='published'));
    usort($items,static fn($a,$b)=>(($a['kind']==='ceo'?0:1)<=>($b['kind']==='ceo'?0:1))?:($a['sort_order']<=>$b['sort_order'])?:strcmp($a['name'],$b['name']));
    return $items;
}
function companyTeamValidate(array $input): array {
    $record=[];
    foreach(['name'=>100,'position'=>120,'bio'=>3000,'email'=>254,'linkedin'=>500] as $field=>$max){
        $value=$input[$field]??'';
        if(!is_string($value)||mb_strlen(trim($value))>$max)throw new InvalidArgumentException('Enter a valid '.$field.' (up to '.$max.' characters).');
        $record[$field]=trim($value);
    }
    if($record['name']===''||$record['position']==='')throw new InvalidArgumentException('Name and job title are required.');
    if($record['email']!==''&&!filter_var($record['email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email.');
    if($record['linkedin']!==''&&(!filter_var($record['linkedin'],FILTER_VALIDATE_URL)||strtolower((string)parse_url($record['linkedin'],PHP_URL_SCHEME))!=='https'))throw new InvalidArgumentException('Use a valid HTTPS profile link.');
    $record['kind']=$input['kind']??'';$record['status']=$input['status']??'';
    if(!in_array($record['kind'],['ceo','member'],true)||!in_array($record['status'],['published','unpublished'],true))throw new InvalidArgumentException('Choose a valid role and visibility.');
    $order=filter_var($input['sort_order']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>9999]]);
    if($order===false)throw new InvalidArgumentException('Display order must be between 0 and 9999.');
    $record['sort_order']=$order;return $record;
}
function companyTeamWrite(string $action,string $id,array $record=[]): void {
    $db=Database::getConnection();$db->exec("INSERT IGNORE INTO company_team (id,payload) VALUES (1,'[]')");$db->beginTransaction();
    try {
        $items=json_decode($db->query('SELECT payload FROM company_team WHERE id=1 FOR UPDATE')->fetchColumn(),true,512,JSON_THROW_ON_ERROR);
        $index=null;foreach($items as $i=>$item)if($item['id']===$id)$index=$i;
        if($action==='delete'){if($index===null)throw new InvalidArgumentException('Profile no longer exists.');array_splice($items,$index,1);}
        elseif($action==='save'){
            if($id!==''&&$index===null)throw new InvalidArgumentException('Profile no longer exists.');
            foreach($items as $item)if($record['kind']==='ceo'&&$item['kind']==='ceo'&&$item['id']!==$id)throw new InvalidArgumentException('A CEO profile already exists. Edit that profile, or change its role first.');
            $record['id']=$id!==''?$id:bin2hex(random_bytes(8));
            if($index===null)$items[]=$record;else $items[$index]=$record;
        }else throw new InvalidArgumentException('Invalid action.');
        $db->prepare('UPDATE company_team SET payload=? WHERE id=1')->execute([json_encode(array_values($items),JSON_THROW_ON_ERROR)]);$db->commit();
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}
