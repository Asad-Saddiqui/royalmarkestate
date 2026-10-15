<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';

function visitorValidId(string $id): bool
{
    return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $id) === 1;
}

function visitorNewId(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
    $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
    $hex = bin2hex($bytes);
    return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
}

/** The public UUID alone never proves ownership; a private cookie secret does. */
function visitorAuthenticated(): ?array
{
    $cookie = $_COOKIE['royalmark_visitor'] ?? null;
    if (!is_string($cookie) || strlen($cookie) !== 101) return null;
    [$id,$secret] = array_pad(explode('.', $cookie, 2),2,'');
    if (!visitorValidId($id) || !preg_match('/^[a-f0-9]{64}$/D',$secret)) return null;
    $query = Database::getConnection()->prepare('SELECT id,token_hash,linked_user_id,first_seen,last_seen FROM site_visitors WHERE id=?');
    $query->execute([$id]);$visitor = $query->fetch();
    return $visitor && hash_equals($visitor['token_hash'],hash('sha256',$secret)) ? $visitor : null;
}

function visitorCurrentId(): ?string
{
    return visitorAuthenticated()['id'] ?? null;
}

function visitorLoggedInUser(): ?array
{
    $id = $_SESSION['admin_id'] ?? null;
    if (!is_scalar($id) || !ctype_digit((string)$id)) return null;
    $query = Database::getConnection()->prepare('SELECT id,session_version,is_blocked FROM admin_users WHERE id=?');$query->execute([(int)$id]);$user = $query->fetch();
    return $user && !$user['is_blocked'] && (int)($_SESSION['admin_session_version'] ?? -1) === (int)$user['session_version'] ? $user : null;
}

/** Claim only unknown views; earlier views attributed to another account stay intact. */
function visitorLinkLogin(int $userId): void
{
    $current = visitorLoggedInUser();$visitor = visitorAuthenticated();
    if (!$current || (int)$current['id'] !== $userId || !$visitor) return;
    $db = Database::getConnection();$db->beginTransaction();
    try {
        $db->prepare('UPDATE site_visitors SET linked_user_id=? WHERE id=?')->execute([$userId,$visitor['id']]);
        $db->prepare('UPDATE site_page_views SET user_id=? WHERE visitor_id=? AND user_id IS NULL')->execute([$userId,$visitor['id']]);
        $db->commit();
    } catch (Throwable $error) {if ($db->inTransaction()) $db->rollBack();throw $error;}
}

function visitorBasePath(): string
{
    $path = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/visitor-track.php')),'/');
    if (str_ends_with($path,'/admin')) $path = rtrim(dirname($path),'/');
    return ($path === '.' ? '' : $path).'/';
}

function visitorResolvePage(string $kind,string $key): ?array
{
    $base = visitorBasePath();
    if ($kind === 'page') {
        $pages = ['home'=>['','Home'],'properties'=>['property-listing','Properties'],'projects'=>['projects','Projects'],'agents'=>['agents','Agents'],'contact'=>['contact-us','Contact Us'],'about'=>['about-us','About'],'blog'=>['blog','Blog']];
        return isset($pages[$key]) ? ['title'=>$pages[$key][1],'url'=>$base.$pages[$key][0]] : null;
    }
    if (!in_array($kind,['property','project','blog'],true) || strlen($key)>200 || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$key)) return null;
    $query = Database::getConnection()->prepare('SELECT payload FROM '.['property'=>'properties','project'=>'projects','blog'=>'blog_posts'][$kind].' WHERE slug=?');$query->execute([$key]);$payload = $query->fetchColumn();
    $record = $payload !== false ? json_decode($payload,true) : null;
    if (!is_array($record) || ($record['status'] ?? ($kind === 'property' ? 'published' : 'draft')) !== 'published') return null;
    if ($kind === 'blog') {
        $date=is_string($record['published_at']??null)?$record['published_at']:'';
        $published=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$date,new DateTimeZone('Asia/Karachi'));
        if(!$published||$published->format('Y-m-d\TH:i')!==$date||$published>new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi')))return null;
    }
    $path = ['property'=>'property.php?slug=','project'=>'project.php?slug=','blog'=>'blog?article='][$kind];
    return ['title'=>mb_substr((string)($record['title'] ?? $key),0,220),'url'=>$base.$path.rawurlencode($key)];
}

function visitorViewStats(string $kind,string $key): array
{
    $query = Database::getConnection()->prepare('SELECT COUNT(*) AS views,COUNT(DISTINCT visitor_id) AS visitors FROM site_page_views WHERE page_kind=? AND page_key=?');$query->execute([$kind,$key]);$stats = $query->fetch();
    return ['views'=>(int)$stats['views'],'visitors'=>(int)$stats['visitors']];
}

function visitorTrackView(string $candidateId,string $eventId,string $kind,string $key): array
{
    if (!preg_match('/^[a-f0-9]{32}$/D',$eventId)) throw new InvalidArgumentException('Invalid page view.');
    $page = visitorResolvePage($kind,$key);
    if (!$page) throw new InvalidArgumentException('This page cannot be recorded.');
    $attempts=$_SESSION['visitor_tracking_attempts']??[];
    $attempts=is_array($attempts)?array_values(array_filter($attempts,static fn($stamp)=>is_int($stamp)&&$stamp>time()-60)):[];
    if(count($attempts)>=90)throw new InvalidArgumentException('Too many page views. Try again shortly.');
    $attempts[]=time();$_SESSION['visitor_tracking_attempts']=$attempts;
    $visitor = visitorAuthenticated();$db = Database::getConnection();
    $stamp = (new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi')))->format('Y-m-d H:i:s');
    $cookieToSet=null;
    $db->beginTransaction();
    try {
    if (!$visitor) {
        $id = visitorValidId($candidateId) ? $candidateId : visitorNewId();
        $exists = $db->prepare('SELECT 1 FROM site_visitors WHERE id=?');$exists->execute([$id]);
        // Copying someone else's localStorage ID cannot claim their browser history.
        if ($exists->fetchColumn()) $id = visitorNewId();
        $secret = bin2hex(random_bytes(32));
        try {$db->prepare('INSERT INTO site_visitors (id,token_hash,linked_user_id,first_seen,last_seen) VALUES (?,?,NULL,?,?)')->execute([$id,hash('sha256',$secret),$stamp,$stamp]);}
        catch (PDOException $error) {
            if (($error->errorInfo[1] ?? null)!==1062) throw $error;
            $id = visitorNewId();$db->prepare('INSERT INTO site_visitors (id,token_hash,linked_user_id,first_seen,last_seen) VALUES (?,?,NULL,?,?)')->execute([$id,hash('sha256',$secret),$stamp,$stamp]);
        }
        $cookieToSet = $id.'.'.$secret;
        $visitor=['id'=>$id,'linked_user_id'=>null];
    }
    $lock = $db->prepare('SELECT id,linked_user_id FROM site_visitors WHERE id=? FOR UPDATE');$lock->execute([$visitor['id']]);$visitor=$lock->fetch();
    if(!$visitor)throw new InvalidArgumentException('This browser is no longer registered.');
    $loggedIn = visitorLoggedInUser();
    if ($loggedIn) {
        $db->prepare('UPDATE site_visitors SET linked_user_id=? WHERE id=?')->execute([(int)$loggedIn['id'],$visitor['id']]);
        $db->prepare('UPDATE site_page_views SET user_id=? WHERE visitor_id=? AND user_id IS NULL')->execute([(int)$loggedIn['id'],$visitor['id']]);
        $visitor['linked_user_id']=(int)$loggedIn['id'];
    }
    $owner = $visitor['linked_user_id'] !== null ? (int)$visitor['linked_user_id'] : null;
    if ($owner !== null) {
        $validOwner = $db->prepare('SELECT 1 FROM admin_users WHERE id=?');$validOwner->execute([$owner]);
        if (!$validOwner->fetchColumn()) $owner=null;
    }
        $duplicate = $db->prepare('SELECT visitor_id,page_kind,page_key FROM site_page_views WHERE view_key=?');$duplicate->execute([$eventId]);$prior=$duplicate->fetch();
        if ($prior && ($prior['visitor_id'] !== $visitor['id']||$prior['page_kind']!==$kind||$prior['page_key']!==$key)) throw new InvalidArgumentException('Invalid page view token.');
        if (!$prior) {
            $since=(new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi')))->modify('-1 minute')->format('Y-m-d H:i:s');
            $rate=$db->prepare('SELECT COUNT(*) FROM site_page_views WHERE visitor_id=? AND viewed_at>?');$rate->execute([$visitor['id'],$since]);
            if ((int)$rate->fetchColumn()>=60) throw new InvalidArgumentException('Too many page views. Try again shortly.');
            $db->prepare('INSERT INTO site_page_views (visitor_id,user_id,page_kind,page_key,page_title,page_url,view_key,viewed_at) VALUES (?,?,?,?,?,?,?,?)')->execute([$visitor['id'],$owner,$kind,$key,$page['title'],$page['url'],$eventId,$stamp]);
        }
        $db->prepare('UPDATE site_visitors SET last_seen=? WHERE id=?')->execute([$stamp,$visitor['id']]);
        $db->commit();
    } catch (Throwable $error) {if ($db->inTransaction()) $db->rollBack();throw $error;}
    if($cookieToSet!==null){
        setcookie('royalmark_visitor',$cookieToSet,['expires'=>time()+365*86400,'path'=>visitorBasePath(),'httponly'=>true,'secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off','samesite'=>'Lax']);
        $_COOKIE['royalmark_visitor']=$cookieToSet;
    }
    return ['visitor_id'=>$visitor['id'],'stats'=>visitorViewStats($kind,$key)];
}
