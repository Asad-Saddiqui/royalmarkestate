<?php
declare(strict_types=1);

require_once __DIR__.'/Database.php';
require_once __DIR__.'/bookings.php';

function listingReviewCsrf(): string
{
    return $_SESSION['listing_review_csrf'] ??= bin2hex(random_bytes(32));
}

function listingReviewSummary(string $kind, string $slug): array
{
    $query = Database::getConnection()->prepare("SELECT id,name,rating,body,created_at FROM listing_reviews WHERE kind=? AND listing_slug=? AND status='approved' ORDER BY created_at DESC,id DESC");
    $query->execute([$kind,$slug]);
    $reviews = $query->fetchAll();
    $count = count($reviews);
    $average = $count ? array_sum(array_column($reviews,'rating')) / $count : 0.0;
    return ['reviews'=>$reviews,'count'=>$count,'average'=>$average];
}

function listingReviewSubmit(string $kind, array $listing, array $input): void
{
    if (!in_array($kind,['property','project'],true)) throw new InvalidArgumentException('Invalid review type.');
    $slug = is_string($listing['slug'] ?? null) ? $listing['slug'] : '';
    if (!preg_match('/^[a-z0-9][a-z0-9-]{0,199}$/D',$slug)) throw new InvalidArgumentException('This listing cannot accept reviews.');
    $csrf = $input['review_csrf'] ?? null;
    if (!is_string($csrf) || !hash_equals(listingReviewCsrf(),$csrf)) throw new InvalidArgumentException('Your form expired. Reload the page and try again.');
    if (!is_string($input['website'] ?? '') || trim($input['website']) !== '') throw new InvalidArgumentException('Your review could not be accepted.');
    $rating = filter_var($input['rating'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>5]]);
    if ($rating === false) throw new InvalidArgumentException('Choose a rating from 1 to 5 stars.');
    $values=[];
    foreach(['name'=>100,'email'=>254,'body'=>1500] as $field=>$limit){
        $value=$input[$field]??'';
        if(!is_string($value)||mb_strlen(trim($value))>$limit||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value)) throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$field).'.');
        $values[$field]=trim($value);
    }
    $user=bookingCurrentUser();
    $userId=$user?(int)$user['id']:null;
    if($user){$values['name']=$user['name']?:'Royalmark member';$values['email']=$user['email'];}
    if($values['name']==='') throw new InvalidArgumentException('Enter your name.');
    if(!filter_var($values['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
    if(mb_strlen($values['body'])<10) throw new InvalidArgumentException('Write a review of at least 10 characters.');
    $recent=$_SESSION['listing_review_times']??[];
    $recent=is_array($recent)?array_values(array_filter($recent,static fn($time)=>is_int($time)&&$time>time()-3600)):[];
    if(count($recent)>=3) throw new InvalidArgumentException('Please wait before adding another review.');
    $visitorId=function_exists('visitorCurrentId')?visitorCurrentId():null;
    if(!is_string($visitorId)||!preg_match('/^[a-f0-9-]{36}$/D',$visitorId))$visitorId=null;
    $sessionReviewer=$_SESSION['listing_reviewer_id']??=bin2hex(random_bytes(32));
    $identity=$userId!==null?'user:'.$userId:($visitorId!==null?'visitor:'.$visitorId:'session:'.$sessionReviewer);
    $reviewerKey=hash('sha256',$kind.'|'.$slug.'|'.$identity);
    $db=Database::getConnection();
    $duplicate=$db->prepare('SELECT 1 FROM listing_reviews WHERE reviewer_key=? LIMIT 1');$duplicate->execute([$reviewerKey]);
    if($duplicate->fetchColumn()) throw new InvalidArgumentException('You have already rated and reviewed this listing.');
    $stamp=date('Y-m-d H:i:s');
    $insert=$db->prepare("INSERT INTO listing_reviews (kind,listing_slug,reviewer_key,user_id,visitor_id,name,email,rating,body,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,'approved',?,?)");
    $insert->execute([$kind,$slug,$reviewerKey,$userId,$visitorId,$values['name'],$values['email'],$rating,$values['body'],$stamp,$stamp]);
    $recent[]=time();$_SESSION['listing_review_times']=$recent;
}

function listingReviewAlreadySubmitted(string $kind,string $slug): bool
{
    $user=bookingCurrentUser();$visitorId=function_exists('visitorCurrentId')?visitorCurrentId():null;
    $sessionReviewer=$_SESSION['listing_reviewer_id']??=bin2hex(random_bytes(32));
    $identity=$user?'user:'.(int)$user['id']:(is_string($visitorId)&&preg_match('/^[a-f0-9-]{36}$/D',$visitorId)?'visitor:'.$visitorId:'session:'.$sessionReviewer);
    $query=Database::getConnection()->prepare('SELECT 1 FROM listing_reviews WHERE reviewer_key=? LIMIT 1');
    $query->execute([hash('sha256',$kind.'|'.$slug.'|'.$identity)]);return(bool)$query->fetchColumn();
}
