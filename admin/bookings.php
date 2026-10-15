<?php
require __DIR__.'/auth.php';
$bookingEntryPath=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
$isPublicUserRoute=(bool)preg_match('~/user/bookings/?$~',$bookingEntryPath);
if($isPublicUserRoute)$adminBase=preg_replace('~/user/bookings/?$~','/admin',$bookingEntryPath);
$account=requireLogin();header('Cache-Control: private, no-store');$isAdmin=$account['role']==='admin';$ownerId=$isAdmin?null:(int)$account['id'];$bookingRoute=$isAdmin?'./bookings':'../user/bookings';
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/projects-store.php';
require_once dirname(__DIR__).'/Config/bookings.php';

$kind=is_string($_GET['kind']??null)&&$_GET['kind']==='project'?'project':'property';
$requestTypes=['viewing'=>'Viewing request','call'=>'Callback request','information'=>'Information request'];
$statuses=['pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed','cancelled'=>'Cancelled'];$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    adminCsrf();
    $action=is_string($_POST['action']??null)?$_POST['action']:'';
    if(!$isAdmin&&$action!=='cancel'){http_response_code(403);exit('This action requires administrator access.');}
    try{
        $id=is_scalar($_POST['booking_id']??null)?(string)$_POST['booking_id']:'';
        if(!ctype_digit($id)||(int)$id<1)throw new InvalidArgumentException('Choose a valid booking.');
        if($isAdmin&&$action==='update'){
            $status=is_string($_POST['status']??null)?$_POST['status']:'';
            $note=is_string($_POST['admin_note']??null)?trim($_POST['admin_note']):'';
            if(!isset($statuses[$status]))throw new InvalidArgumentException('Choose a valid booking status.');
            if(mb_strlen($note)>3000)throw new InvalidArgumentException('The team update may contain up to 3,000 characters.');
            bookingAdminUpdate((int)$id,$status,$note);
        }elseif($action==='cancel'){
            bookingUpdateStatus((int)$id,'cancelled',$ownerId);
        }else throw new InvalidArgumentException('Choose a valid booking action.');
        header('Location: '.$bookingRoute.'?kind='.$kind.'&'.($action==='cancel'?'cancelled':'updated').'=1',true,303);exit;
    }catch(InvalidArgumentException $exception){http_response_code(422);$error=$exception->getMessage();}
    catch(Throwable $exception){http_response_code(422);error_log('Booking update failed: '.$exception->getMessage());$error='This booking could not be updated. Refresh the page and try again.';}
}
$allBookings=bookingList($ownerId);
$kindCounts=['property'=>0,'project'=>0];foreach($allBookings as $booking)if(isset($kindCounts[$booking['kind']]))$kindCounts[$booking['kind']]++;
$query=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,150):'';
$statusFilter=is_string($_GET['status']??null)&&isset($statuses[$_GET['status']])?$_GET['status']:'';
$dateFilter=static function($value):string{if(!is_string($value)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value))return '';$date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $date&&$date->format('Y-m-d')===$value?$value:'';};
$from=$dateFilter($_GET['from']??'');$to=$dateFilter($_GET['to']??'');
$kindBookings=array_values(array_filter($allBookings,static fn($booking)=>$booking['kind']===$kind));
$items=array_values(array_filter($kindBookings,static function($booking)use($query,$statusFilter,$from,$to):bool{
    $date=substr($booking['created_at'],0,10);
    return($query===''||mb_stripos($booking['reference'].' '.$booking['listing_title'].' '.$booking['name'].' '.$booking['email'].' '.$booking['phone'],$query)!==false)&&($statusFilter===''||$booking['status']===$statusFilter)&&($from===''||$date>=$from)&&($to===''||$date<=$to);
}));
$total=count($items);$perPage=6;$pages=max(1,(int)ceil($total/$perPage));$page=min($pages,max(1,(int)(is_scalar($_GET['page']??null)?$_GET['page']:1)));$offset=($page-1)*$perPage;$items=array_slice($items,$offset,$perPage);
$pageLink=static fn($number)=>$bookingRoute.'?'.http_build_query(['kind'=>$kind,'q'=>$query,'status'=>$statusFilter,'from'=>$from,'to'=>$to,'page'=>$number]);
$properties=listingData($isAdmin);$publicPropertySlugs=[];foreach($properties as $property)if(($property['status']??'published')==='published')$publicPropertySlugs[$property['slug']]=true;
$publicProjectSlugs=array_fill_keys(array_column(projectData(),'slug'),true);
$formatDate=static function(string $value,string $format='d M Y'):string{try{return(new DateTimeImmutable($value))->format($format);}catch(Throwable $exception){return $value;}};
$formatTime=static function(string $value):string{$time=DateTimeImmutable::createFromFormat('!H:i:s',$value)?:DateTimeImmutable::createFromFormat('!H:i',$value);return $time?$time->format('g:i A'):$value;};
$view='bookings';$title=$isAdmin?'Bookings':'My bookings';require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./bookings.css?v=<?= filemtime(__DIR__.'/bookings.css') ?>">
<section class="page-heading booking-heading"><div><span class="eyebrow"><?= $isAdmin?'CUSTOMER REQUESTS':'YOUR PROPERTY JOURNEY' ?></span><h1><?= $isAdmin?'Manage bookings.':'My bookings.' ?></h1><p><?= $isAdmin?'Review property and project visit requests, confirm appointments and send customer updates.':'Track your property and project requests, visit preferences and updates from our team.' ?></p></div><a href="<?= $isAdmin?'../property-listing':'./properties' ?>" class="text-link">Explore properties &nearr;</a></section>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><div class="success-note" role="status">Booking updated successfully.</div><?php elseif(isset($_GET['cancelled'])): ?><div class="success-note" role="status">Your booking has been cancelled.</div><?php endif; ?>
<nav class="booking-tabs" aria-label="Booking type"><a href="<?= e($bookingRoute) ?>?kind=property" <?= $kind==='property'?'aria-current="page"':'' ?>><?= listingIcon('house') ?>Properties <span><?= $kindCounts['property'] ?></span></a><a href="<?= e($bookingRoute) ?>?kind=project" <?= $kind==='project'?'aria-current="page"':'' ?>><?= listingIcon('building') ?>Projects <span><?= $kindCounts['project'] ?></span></a></nav>
<section class="stats" aria-label="<?= ucfirst($kind) ?> booking summary"><?php foreach($statuses as $status=>$label): ?><article class="stat-card"><div><span><?= $label ?></span><div class="stat-icon"><?= listingIcon($status==='confirmed'?'house':($status==='completed'?'crown':'layers')) ?></div></div><strong><?= count(array_filter($kindBookings,fn($booking)=>$booking['status']===$status)) ?></strong></article><?php endforeach; ?></section>
<section class="panel booking-inventory"><div class="panel-title"><div><h2><?= ucfirst($kind) ?> bookings</h2><p><?= $total ?> <?= $total===1?'booking':'bookings' ?> found</p></div><span class="subtle-tag">Visits in Pakistan time</span></div>
<form method="get" action="<?= e($bookingRoute) ?>" class="table-toolbar booking-toolbar"><input type="hidden" name="kind" value="<?= $kind ?>"><label class="search-field"><?= listingIcon('search') ?><span class="sr-only">Search bookings</span><input name="q" type="search" maxlength="150" value="<?= e($query) ?>" placeholder="Search reference, listing or contact"></label><label class="booking-filter-label">Status<select name="status"><option value="">All statuses</option><?php foreach($statuses as $status=>$label): ?><option value="<?= $status ?>" <?= $statusFilter===$status?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label><label class="booking-filter-label">Created from<input type="date" name="from" value="<?= e($from) ?>"></label><label class="booking-filter-label">Created to<input type="date" name="to" value="<?= e($to) ?>"></label><button type="submit" class="primary-button">Filter</button><?php if($query!==''||$statusFilter!==''||$from!==''||$to!==''): ?><a class="text-link" href="<?= e($bookingRoute) ?>?kind=<?= $kind ?>">Reset</a><?php endif; ?></form>
<?php if(!$items): ?><div class="empty-state"><h3><?= $query!==''||$statusFilter!==''||$from!==''||$to!==''?'No bookings match your filters.':'No '.$kind.' bookings yet.' ?></h3><p><?= $isAdmin?'Customer requests will appear here when someone submits a booking.':'Explore a listing and send a booking request to arrange your next visit.' ?></p><a href="<?= $kind==='project'?'../projects':'../property-listing' ?>" class="text-link">Explore <?= $kind==='project'?'projects':'properties' ?> &nearr;</a></div><?php else: ?><div class="booking-records">
<?php foreach($items as $booking): $published=$booking['kind']==='project'?isset($publicProjectSlugs[$booking['listing_slug']]):isset($publicPropertySlugs[$booking['listing_slug']]);$listingHref='../'.($booking['kind']==='project'?'project.php':'property.php').'?slug='.rawurlencode($booking['listing_slug']); ?>
<article class="booking-record" id="booking-<?= (int)$booking['id'] ?>"><header class="booking-record-header"><div class="booking-listing-intro"><?php if(!empty($booking['listing_image'])): ?><img class="booking-listing-photo" src="<?= e(str_starts_with($booking['listing_image'],'./')?'../'.substr($booking['listing_image'],2):$booking['listing_image']) ?>" alt="<?= e($booking['listing_title']) ?>" width="90" height="72" loading="lazy"><?php endif; ?><div><span class="booking-reference"><?= e($booking['reference']) ?></span><h3><?php if($published): ?><a href="<?= e($listingHref) ?>"><?= e($booking['listing_title']) ?> &nearr;</a><?php else: ?><?= e($booking['listing_title']) ?><?php endif; ?></h3><p><?= ucfirst($booking['kind']) ?> &middot; <?= e($requestTypes[$booking['request_type']]??'Booking request') ?><?php if(!empty($booking['listing_city'])): ?> &middot; <?= e($booking['listing_city']) ?><?php endif; ?><?php if(!$published): ?> &middot; <em class="booking-listing-unavailable">Listing is currently unavailable</em><?php endif; ?></p><?php if(!empty($booking['listing_price'])): ?><p class="booking-listing-price"><?= e($booking['listing_price']) ?></p><?php endif; ?></div></div><span class="booking-status is-<?= e($booking['status']) ?>"><?= e($statuses[$booking['status']]??ucfirst($booking['status'])) ?></span></header>
<div class="booking-record-body"><div><dl class="booking-contact-grid"><div><dt>Name</dt><dd><?= e($booking['name']) ?></dd></div><div><dt>Email</dt><dd><a href="mailto:<?= e(rawurlencode($booking['email'])) ?>"><?= e($booking['email']) ?></a></dd></div><div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^+0-9]/','',$booking['phone'])) ?>"><?= e($booking['phone']) ?></a></dd></div></dl><p class="booking-message-label">Message</p><p class="booking-message"><?= e($booking['message']?:'No additional message provided.') ?></p><span class="booking-created">Requested <?= e($formatDate($booking['created_at'],'d M Y, g:i A')) ?> PKT</span></div><div><dl class="booking-visit"><div><dt>Preferred date</dt><dd><?= !empty($booking['preferred_date'])?e($formatDate($booking['preferred_date'])):'To be arranged' ?></dd></div><div><dt>Preferred time</dt><dd><?= !empty($booking['preferred_time'])?e($formatTime($booking['preferred_time'])).' PKT':'To be arranged' ?></dd></div></dl><p class="booking-visit-note">Dates and times are subject to confirmation by the team.</p><?php if(!empty($booking['admin_note'])): ?><p class="booking-team-note"><strong>Team update</strong><?= e($booking['admin_note']) ?></p><?php endif; ?></div></div>
<?php if($isAdmin): ?><div class="booking-record-actions"><form method="post" action="./bookings?kind=<?= $kind ?>" class="booking-admin-form"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>"><label>Booking status<select name="status"><?php foreach($statuses as $status=>$label): ?><option value="<?= $status ?>" <?= $booking['status']===$status?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label><label>Team update (visible to customer)<textarea name="admin_note" rows="2" maxlength="3000" placeholder="Confirm visit details or share the next step."><?= e($booking['admin_note']) ?></textarea></label><button type="submit" class="primary-button">Save update</button></form></div><?php elseif(in_array($booking['status'],['pending','confirmed'],true)): ?><div class="booking-record-actions"><p>Need a different visit? Contact our team, or cancel this request.</p><form method="post" action="./bookings?kind=<?= $kind ?>"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int)$booking['id'] ?>"><button type="submit" class="booking-cancel-button">Cancel booking</button></form></div><?php endif; ?></article>
<?php endforeach; ?></div><?php endif; ?>
<?php if($total): ?><div class="inventory-pagination"><p>Showing <?= $offset+1 ?>&ndash;<?= $offset+count($items) ?> of <?= $total ?></p><?php if($pages>1): ?><nav aria-label="Booking pages"><?php if($page>1): ?><a href="<?= e($pageLink($page-1)) ?>">&lsaquo; Previous</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="<?= e($pageLink($i)) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?><?php if($page<$pages): ?><a href="<?= e($pageLink($page+1)) ?>">Next &rsaquo;</a><?php endif; ?></nav><?php endif; ?></div><?php endif; ?></section>
<?php require __DIR__.'/footer.php'; ?>
