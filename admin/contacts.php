<?php
require __DIR__.'/auth.php';
requireAdmin();
header('Cache-Control: private, no-store');
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/contacts.php';

$statuses=['new'=>'New','read'=>'Read','replied'=>'Replied','archived'=>'Archived'];
$sources=['contact'=>'Contact us','property'=>'Property enquiry','project'=>'Project enquiry','legacy'=>'Earlier enquiry'];
$contactIcon=static function(string $name):string{
    $paths=['mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>','phone'=>'<path d="M7 3H4a1 1 0 0 0-1 1 17 17 0 0 0 17 17 1 1 0 0 0 1-1v-3l-5-2-2 2a13 13 0 0 1-7-7l2-2-2-5Z"/>'];
    return isset($paths[$name])?'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$name].'</svg>':listingIcon($name);
};
$text=static fn($value,$limit=150)=>is_string($value)?mb_substr(trim($value),0,$limit):'';
$validDate=static function($value):string{
    if(!is_string($value)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value))return '';
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    return $date&&$date->format('Y-m-d')===$value?$value:'';
};
$query=$text($_GET['q']??'');$statusFilter=$text($_GET['status']??'',20);$sourceFilter=$text($_GET['source']??'',20);
if(!isset($statuses[$statusFilter]))$statusFilter='';
if(!isset($sources[$sourceFilter]))$sourceFilter='';
$from=$validDate($_GET['from']??'');$to=$validDate($_GET['to']??'');
if($from!==''&&$to!==''&&$from>$to)[$from,$to]=[$to,$from];
$requestedPage=max(1,(int)(is_scalar($_GET['page']??null)?$_GET['page']:1));
$filters=['q'=>$query,'status'=>$statusFilter,'source'=>$sourceFilter,'from'=>$from,'to'=>$to];
$link=static fn(array $changes=[])=>'./contacts?'.http_build_query(array_filter(array_merge($filters,['page'=>$requestedPage],$changes),static fn($value)=>$value!==''&&$value!==null));
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    adminCsrf();
    try{
        $id=filter_var($_POST['message_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $action=$text($_POST['action']??'',20);
        if(!$id)throw new InvalidArgumentException('Choose a valid contact message.');
        if($action==='update'){
            $status=$text($_POST['status']??'',20);
            $note=is_string($_POST['admin_note']??null)?trim($_POST['admin_note']):'';
            if(!isset($statuses[$status]))throw new InvalidArgumentException('Choose a valid message status.');
            if(mb_strlen($note)>3000)throw new InvalidArgumentException('The internal note may contain up to 3,000 characters.');
            contactUpdate((int)$id,$status,$note);
            $_SESSION['contact_notice']='Contact message updated successfully.';
        }elseif($action==='delete'){
            contactDelete((int)$id);
            $_SESSION['contact_notice']='Contact message deleted.';
        }else throw new InvalidArgumentException('Choose a valid contact action.');
        header('Location: '.$link(),true,303);exit;
    }catch(InvalidArgumentException $exception){http_response_code(422);$error=$exception->getMessage();}
    catch(Throwable $exception){http_response_code(422);error_log('Contact inbox update failed: '.$exception->getMessage());$error='This contact message could not be updated. Refresh the page and try again.';}
}
$all=[];
try{$all=contactInbox();}catch(Throwable $exception){error_log('Contact inbox unavailable: '.$exception->getMessage());$error='Contact messages are temporarily unavailable. Please refresh and try again.';}
$counts=['all'=>count($all),'new'=>0,'read'=>0,'replied'=>0,'archived'=>0];
foreach($all as $item){$key=$item['status']??'new';if(isset($counts[$key]))$counts[$key]++;}
$items=array_values(array_filter($all,static function($item)use($query,$statusFilter,$sourceFilter,$from,$to):bool{
    $date=substr($item['created_at']??'',0,10);
    $search=implode(' ',[$item['name']??'',$item['email']??'',$item['phone']??'',$item['subject']??'',$item['interest']??'',$item['message']??'']);
    return($query===''||mb_stripos($search,$query)!==false)&&($statusFilter===''||($item['status']??'new')===$statusFilter)&&($sourceFilter===''||($item['source']??'legacy')===$sourceFilter)&&($from===''||$date>=$from)&&($to===''||$date<=$to);
}));
$total=count($items);$pages=max(1,(int)ceil($total/6));$page=min($pages,$requestedPage);$offset=($page-1)*6;$items=array_slice($items,$offset,6);
$formatDate=static function($value):string{try{return(new DateTimeImmutable($value))->format('d M Y, g:i A');}catch(Throwable $exception){return 'Date unavailable';}};
$notice=$_SESSION['contact_notice']??'';unset($_SESSION['contact_notice']);
$properties=listingData(true);$view='contacts';$title='Contacts';require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./contacts.css?v=<?= filemtime(__DIR__.'/contacts.css') ?>">
<script src="./contacts.js?v=<?= filemtime(__DIR__.'/contacts.js') ?>" defer></script>
<section class="page-heading contacts-heading"><div><span class="eyebrow">CUSTOMER CONVERSATIONS</span><h1>Your contact inbox.</h1><p>Review website messages, keep internal notes and track each conversation from first contact to reply.</p></div><a class="text-link" href="../contact-us" target="_blank" rel="noopener">View contact page &nearr;</a></section>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if($notice): ?><div class="success-note" role="status"><?= e($notice) ?></div><?php endif; ?>
<section class="stats contacts-stats" aria-label="Contact message summary">
<?php foreach(['all'=>'All messages']+$statuses as $key=>$label): ?><article class="stat-card"><div><span><?= e($label) ?></span><span class="contact-stat-symbol" aria-hidden="true"><?= $contactIcon($key==='replied'?'mail':($key==='all'?'layers':'calendar')) ?></span></div><strong><?= number_format($counts[$key]) ?></strong><p><?= $key==='new'?'Waiting for your review':($key==='all'?'Across all enquiry sources':'All '.strtolower($label).' messages') ?></p></article><?php endforeach; ?>
</section>
<section class="panel contacts-inbox" aria-labelledby="contacts-title"><div class="panel-title"><div><h2 id="contacts-title">Contact messages</h2><p><?= number_format($total) ?> <?= $total===1?'message':'messages' ?> matching your filters</p></div><span class="subtle-tag">Dates in Pakistan time</span></div>
<form method="get" action="./contacts" class="contacts-filters">
<label class="contact-search">Search<input name="q" type="search" maxlength="150" value="<?= e($query) ?>" placeholder="Name, email, phone, subject or message"></label>
<label>Status<select name="status"><option value="">All statuses</option><?php foreach($statuses as $value=>$label): ?><option value="<?= $value ?>" <?= $statusFilter===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Source<select name="source"><option value="">All sources</option><?php foreach($sources as $value=>$label): ?><option value="<?= $value ?>" <?= $sourceFilter===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Created from<input type="date" name="from" value="<?= e($from) ?>"></label><label>Created to<input type="date" name="to" value="<?= e($to) ?>"></label>
<div class="contacts-filter-actions"><button class="primary-button" type="submit">Apply filters</button><a class="text-link" href="./contacts">Reset</a></div>
</form>
<?php if(!$items&&!$error): ?><div class="empty-state contacts-empty"><?= $contactIcon('mail') ?><h3><?= array_filter($filters)?'No messages match your filters.':'Your inbox is ready.' ?></h3><p><?= array_filter($filters)?'Try a different search, source or date range.':'Messages sent through your contact page will appear here.' ?></p></div><?php elseif($items): ?><div class="contact-records">
<?php foreach($items as $item):
    $id=(int)$item['id'];$status=$item['status']??'new';$source=$item['source']??'legacy';
    $subject=trim($item['subject']??$item['interest']??'');$subject=$subject?:'General enquiry';
    $initial=mb_strtoupper(mb_substr(trim($item['name'])?:'?',0,1));$email=filter_var($item['email'],FILTER_VALIDATE_EMAIL)?$item['email']:'';
    $telephone=preg_replace('/[^+0-9]/','',$item['phone']??'');
    $replySubject='Re: '.str_replace(["\r","\n"],' ',$subject);
?>
<article class="contact-record <?= $status==='new'?'is-new':'' ?>" id="contact-<?= $id ?>">
<div class="contact-record-summary"><span class="contact-person-symbol" aria-hidden="true"><?= e($initial) ?></span><div class="contact-summary-main"><div class="contact-record-title"><h3><?= e($item['name']?:'Website visitor') ?></h3><span class="contact-status is-<?= e($status) ?>"><?= e($statuses[$status]??'New') ?></span></div><p class="contact-subject"><?= e($subject) ?></p><p class="contact-message-preview"><?= e(mb_substr($item['message'],0,180)) ?><?= mb_strlen($item['message'])>180?'…':'' ?></p><div class="contact-summary-meta"><span><?= e($sources[$source]??'Earlier enquiry') ?></span><span>#<?= $id ?></span><time datetime="<?= e(str_replace(' ','T',$item['created_at']).'+05:00') ?>"><?= e($formatDate($item['created_at'])) ?> PKT</time></div></div><div class="contact-summary-links"><?php if($email): ?><a href="mailto:<?= e(rawurlencode($email)) ?>" title="Email <?= e($item['name']) ?>"><?= $contactIcon('mail') ?><span><?= e($email) ?></span></a><?php else: ?><span><?= e($item['email']) ?></span><?php endif; ?><?php if($telephone!==''): ?><a href="tel:<?= e($telephone) ?>" title="Call <?= e($item['name']) ?>"><?= $contactIcon('phone') ?><span><?= e($item['phone']) ?></span></a><?php endif; ?></div></div>
<details class="contact-details"><summary><span>View message &amp; manage</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 10 4 4 4-4"/></svg></summary><div class="contact-details-body"><div class="contact-full-message"><span class="contact-field-title">Message</span><p><?= e($item['message']) ?></p><dl class="contact-identity"><div><dt>Submitted as</dt><dd><?= !empty($item['user_id'])?'Registered account':'Guest contact' ?></dd></div><?php if(!empty($item['visitor_id'])): ?><div><dt>Browser reference</dt><dd><code><?= e($item['visitor_id']) ?></code></dd></div><?php endif; ?></dl><?php if($email): ?><a class="contact-reply-link role-button" href="mailto:<?= e(rawurlencode($email)) ?>?subject=<?= e(rawurlencode($replySubject)) ?>"><?= $contactIcon('mail') ?>Reply by email &nearr;</a><p class="contact-reply-note">Opens your email application. Mark this message as replied after sending your response.</p><?php endif; ?></div><form method="post" action="<?= e($link(['page'=>$page])) ?>" class="contact-update-form"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="message_id" value="<?= $id ?>"><label>Conversation status<select name="status"><?php foreach($statuses as $value=>$label): ?><option value="<?= $value ?>" <?= $status===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Internal note <span>(admin only)</span><textarea name="admin_note" rows="4" maxlength="3000" placeholder="Record follow-up details or the next step."><?= e($item['admin_note']??'') ?></textarea></label><button class="primary-button" type="submit">Save update</button></form></div><footer class="contact-details-footer"><span>Opening a message does not change its status.</span><form method="post" action="<?= e($link(['page'=>$page])) ?>" class="delete-user-form contact-delete-form" data-name="<?= e($item['name']) ?>"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="message_id" value="<?= $id ?>"><button class="contact-delete-button" type="submit">Delete message</button></form></footer></details></article>
<?php endforeach; ?></div><?php endif; ?>
<?php if($items): ?><div class="contacts-pagination"><p>Showing <?= $offset+1 ?>&ndash;<?= $offset+count($items) ?> of <?= number_format($total) ?> messages</p><?php if($pages>1): ?><nav aria-label="Contact message pages"><?php if($page>1): ?><a href="<?= e($link(['page'=>$page-1])) ?>">&lsaquo; Previous</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="<?= e($link(['page'=>$i])) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?><?php if($page<$pages): ?><a href="<?= e($link(['page'=>$page+1])) ?>">Next &rsaquo;</a><?php endif; ?></nav><?php endif; ?></div><?php endif; ?></section>
<dialog class="contact-delete-dialog" id="contact-delete-dialog" aria-labelledby="contact-delete-title" aria-describedby="contact-delete-description"><div class="contact-delete-dialog-body"><span class="contact-delete-symbol" aria-hidden="true"><?= $contactIcon('mail') ?></span><h2 id="contact-delete-title">Delete this message?</h2><p id="contact-delete-description">The contact message and its internal note will be permanently removed.</p><strong id="contact-delete-name"></strong><div class="contact-delete-dialog-actions"><button class="role-button" type="button" data-contact-cancel>Keep message</button><button class="primary-button" type="button" data-contact-confirm>Delete message</button></div></div></dialog>
<?php require __DIR__.'/footer.php'; ?>
