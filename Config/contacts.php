<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/bookings.php';
require_once __DIR__.'/visitor-tracking.php';

function contactDb(): PDO
{
    $db=Database::getConnection();
    // TIMESTAMP values from both old and new enquiries display in Pakistan time.
    $db->exec("SET time_zone='+05:00'");
    return $db;
}

function contactCurrentUser(): ?array
{
    return bookingCurrentUser();
}

function contactRequireAdmin(): void
{
    $user=contactCurrentUser();
    if(!$user||$user['role']!=='admin')throw new InvalidArgumentException('Administrator access is required.');
}

/** Preserve the existing inbox while storing ownership and workflow separately. */
function contactCreate(array $input,?int $userId): array
{
    $user=contactCurrentUser();
    if(($userId!==null&&(!$user||(int)$user['id']!==$userId))||($user&&$userId===null))throw new InvalidArgumentException('Your account session changed. Reload the form and try again.');
    $values=[];
    foreach(['name'=>100,'email'=>254,'phone'=>40,'interest'=>120,'message'=>5000,'request_id'=>32] as $key=>$limit){
        $value=$input[$key]??'';
        if(!is_string($value)||mb_strlen(trim($value))>$limit||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value))throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$key).'.');
        $values[$key]=trim($value);
    }
    if($values['name']==='')throw new InvalidArgumentException('Enter your full name.');
    if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email address.');
    if($values['phone']!==''&&(!preg_match('/^[+0-9 ()\.\-]+$/D',$values['phone'])||!preg_match('/^[0-9]{7,15}$/D',preg_replace('/\D/','',$values['phone']))))throw new InvalidArgumentException('Enter a valid phone number or leave it empty.');
    if(mb_strlen($values['message'])<10)throw new InvalidArgumentException('Write a message of at least 10 characters.');
    if(!preg_match('/^[a-f0-9]{32}$/D',$values['request_id']))throw new InvalidArgumentException('This form expired. Reload and try again.');
    $secret=$_SESSION['contact_request_secret']??=bin2hex(random_bytes(32));
    $requestKey=hash_hmac('sha256',$values['request_id'].':'.($userId??'guest'),$secret);
    $bucket=hash('sha256','contact:'.($userId!==null?'user:'.$userId:'address:'.($_SERVER['REMOTE_ADDR']??'local')));
    $db=contactDb();$zone=new DateTimeZone('Asia/Karachi');$now=new DateTimeImmutable('now',$zone);$stamp=$now->format('Y-m-d H:i:s');
    $db->beginTransaction();
    try{
        $duplicate=$db->prepare('SELECT message_id FROM contact_message_meta WHERE request_key=? FOR UPDATE');$duplicate->execute([$requestKey]);$oldId=$duplicate->fetchColumn();
        if($oldId!==false){$db->commit();return ['id'=>(int)$oldId,'reference'=>'RM-C-'.$oldId,'created'=>false];}
        $db->prepare('INSERT IGNORE INTO booking_limits (bucket,window_start,attempts) VALUES (?,?,0)')->execute([$bucket,$stamp]);
        $read=$db->prepare('SELECT window_start,attempts FROM booking_limits WHERE bucket=? FOR UPDATE');$read->execute([$bucket]);$limit=$read->fetch();
        $fresh=(new DateTimeImmutable($limit['window_start'],$zone))>$now->modify('-15 minutes');
        if($fresh&&(int)$limit['attempts']>=5)throw new InvalidArgumentException('You have sent several messages. Please wait 15 minutes before sending another.');
        $db->prepare('UPDATE booking_limits SET attempts=?,window_start=? WHERE bucket=?')->execute([$fresh?(int)$limit['attempts']+1:1,$fresh?$limit['window_start']:$stamp,$bucket]);
        $db->prepare('INSERT INTO contact_messages (name,email,phone,interest,message,created_at) VALUES (?,?,?,?,?,?)')->execute([$values['name'],$values['email'],$values['phone'],$values['interest'],$values['message'],$stamp]);
        $id=(int)$db->lastInsertId();$visitorId=visitorCurrentId();
        $db->prepare("INSERT INTO contact_message_meta (message_id,user_id,visitor_id,source,status,admin_note,request_key,updated_at) VALUES (?,?,?,'contact','new','',?,?)")->execute([$id,$userId,$visitorId,$requestKey,$stamp]);
        $db->commit();return ['id'=>$id,'reference'=>'RM-C-'.$id,'created'=>true];
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}

function contactInbox(): array
{
    contactRequireAdmin();
    return contactDb()->query("SELECT c.id,c.name,c.email,c.phone,c.interest,c.message,c.created_at,m.user_id,m.visitor_id,COALESCE(m.source,'legacy') AS source,COALESCE(m.status,'new') AS status,COALESCE(m.admin_note,'') AS admin_note,m.updated_at FROM contact_messages c LEFT JOIN contact_message_meta m ON m.message_id=c.id ORDER BY c.created_at DESC,c.id DESC")->fetchAll();
}

function contactFind(int $id): ?array
{
    contactRequireAdmin();
    $query=contactDb()->prepare("SELECT c.id,c.name,c.email,c.phone,c.interest,c.message,c.created_at,m.user_id,m.visitor_id,COALESCE(m.source,'legacy') AS source,COALESCE(m.status,'new') AS status,COALESCE(m.admin_note,'') AS admin_note,m.updated_at FROM contact_messages c LEFT JOIN contact_message_meta m ON m.message_id=c.id WHERE c.id=?");
    $query->execute([$id]);return $query->fetch()?:null;
}

function contactUpdate(int $id,string $status,string $note): void
{
    contactRequireAdmin();
    if(!in_array($status,['new','read','replied','archived'],true))throw new InvalidArgumentException('Choose a valid message status.');
    if(mb_strlen($note)>3000||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$note))throw new InvalidArgumentException('Write an internal note of up to 3,000 characters.');
    $db=contactDb();$db->beginTransaction();
    try{
        $query=$db->prepare('SELECT id FROM contact_messages WHERE id=? FOR UPDATE');$query->execute([$id]);if(!$query->fetchColumn())throw new InvalidArgumentException('This message is no longer available.');
        $stamp=(new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
        $db->prepare("INSERT INTO contact_message_meta (message_id,source,status,admin_note,updated_at) VALUES (?,'legacy',?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),admin_note=VALUES(admin_note),updated_at=VALUES(updated_at)")->execute([$id,$status,trim($note),$stamp]);
        $db->commit();
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}

function contactDelete(int $id): void
{
    contactRequireAdmin();$db=contactDb();$db->beginTransaction();
    try{
        $query=$db->prepare('SELECT id FROM contact_messages WHERE id=? FOR UPDATE');$query->execute([$id]);if(!$query->fetchColumn())throw new InvalidArgumentException('This message is no longer available.');
        $db->prepare('DELETE FROM contact_message_meta WHERE message_id=?')->execute([$id]);
        $db->prepare('DELETE FROM contact_messages WHERE id=?')->execute([$id]);$db->commit();
    }catch(Throwable $error){if($db->inTransaction())$db->rollBack();throw $error;}
}
