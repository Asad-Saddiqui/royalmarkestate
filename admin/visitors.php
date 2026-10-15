<?php
require __DIR__.'/auth.php';
requireAdmin();
header('Cache-Control: private, no-store');
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';

$readText=static fn($value,$limit=120)=>is_string($value)?mb_substr(trim($value),0,$limit):'';
$validDate=static function($value):string{
    if(!is_string($value)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value))return '';
    $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    return $date&&$date->format('Y-m-d')===$value?$value:'';
};
$query=$readText($_GET['q']??'');
$kind=$readText($_GET['kind']??'',20);
$kinds=['page'=>'Website page','property'=>'Property','project'=>'Project','blog'=>'Blog'];
if(!isset($kinds[$kind]))$kind='';
$identity=$readText($_GET['identity']??'',20);
if(!in_array($identity,['guest','linked'],true))$identity='';
$from=$validDate($_GET['from']??'');$to=$validDate($_GET['to']??'');
if($from!==''&&$to!==''&&$from>$to)[$from,$to]=[$to,$from];
$visitorId=$readText($_GET['visitor']??'',36);
if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/Di',$visitorId))$visitorId='';
$filter=['q'=>$query,'kind'=>$kind,'identity'=>$identity,'from'=>$from,'to'=>$to];
$link=static fn(array $changes=[])=>'./visitors?'.http_build_query(array_filter(array_merge($filter,$changes),static fn($value)=>$value!==''&&$value!==null));

$conditions=[];$bindings=[];
if($query!==''){
    // Escape LIKE metacharacters so search text is treated literally.
    $term='%'.str_replace(['!','%','_'],['!!','!%','!_'],$query).'%';
    $conditions[]="(r.id LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!' OR v.page_title LIKE ? ESCAPE '!' OR v.page_url LIKE ? ESCAPE '!')";
    array_push($bindings,$term,$term,$term,$term,$term);
}
if($kind!==''){$conditions[]='v.page_kind=?';$bindings[]=$kind;}
if($identity==='guest')$conditions[]='r.linked_user_id IS NULL';
elseif($identity==='linked')$conditions[]='r.linked_user_id IS NOT NULL';
if($from!==''){$conditions[]='v.viewed_at>=?';$bindings[]=$from.' 00:00:00';}
if($to!==''){$conditions[]='v.viewed_at<?';$bindings[]=(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d').' 00:00:00';}
$where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
$joins=' FROM site_page_views v INNER JOIN site_visitors r ON r.id=v.visitor_id LEFT JOIN admin_users u ON u.id=r.linked_user_id';
$summary=['views'=>0,'visitors'=>0,'linked'=>0];$items=[];$selected=null;$history=[];$historyTotal=0;$error='';
$page=1;$pages=1;$offset=0;
try{
    $db=adminDb();
    $statement=$db->prepare('SELECT COUNT(*) AS views,COUNT(DISTINCT r.id) AS visitors,COUNT(DISTINCT CASE WHEN r.linked_user_id IS NOT NULL THEN r.id END) AS linked'.$joins.$where);
    $statement->execute($bindings);$summary=$statement->fetch(PDO::FETCH_ASSOC);
    $total=(int)$summary['visitors'];$pages=max(1,(int)ceil($total/6));
    $page=min($pages,max(1,(int)(is_scalar($_GET['page']??null)?$_GET['page']:1)));$offset=($page-1)*6;
    $statement=$db->prepare('SELECT r.id,r.first_seen,r.last_seen,r.linked_user_id,u.name,u.email,COUNT(*) AS views,COUNT(DISTINCT CONCAT(v.page_kind,\':\',v.page_key)) AS pages_seen,MIN(v.viewed_at) AS first_view,MAX(v.viewed_at) AS last_view'.$joins.$where.' GROUP BY r.id,r.first_seen,r.last_seen,r.linked_user_id,u.name,u.email ORDER BY last_view DESC,r.id ASC LIMIT 6 OFFSET '.$offset);
    $statement->execute($bindings);$items=$statement->fetchAll(PDO::FETCH_ASSOC);
    if($visitorId!==''){
        $statement=$db->prepare('SELECT r.id,r.first_seen,r.last_seen,r.linked_user_id,u.name,u.email FROM site_visitors r LEFT JOIN admin_users u ON u.id=r.linked_user_id WHERE r.id=?');
        $statement->execute([$visitorId]);$selected=$statement->fetch(PDO::FETCH_ASSOC)?:null;
        if($selected){
            $historyWhere=$where.($where?' AND ':' WHERE ').'r.id=?';
            $historyBindings=[...$bindings,$visitorId];
            $statement=$db->prepare('SELECT COUNT(*)'.$joins.$historyWhere);$statement->execute($historyBindings);$historyTotal=(int)$statement->fetchColumn();
            $statement=$db->prepare('SELECT v.page_kind,v.page_title,v.page_url,v.viewed_at,v.user_id,eu.name AS event_user_name,eu.email AS event_user_email'.$joins.' LEFT JOIN admin_users eu ON eu.id=v.user_id'.$historyWhere.' ORDER BY v.viewed_at DESC,v.id DESC LIMIT 100');
            $statement->execute($historyBindings);$history=$statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}catch(Throwable $exception){
    error_log('Visitor report unavailable: '.$exception->getMessage());
    $error='Visitor activity is temporarily unavailable. Please refresh and try again.';
}
$formatDate=static function($value):string{
    if(!$value)return 'Not available';
    try{return (new DateTimeImmutable($value))->format('d M Y, g:i A');}catch(Throwable $exception){return 'Not available';}
};
$safeHref=static function($value):string{
    // Activity links remain on this website, even if a stored URL is invalid.
    global $siteBase;
    if(!is_string($value)||preg_match('/[\x00-\x20\\\\]/',$value)||!str_starts_with($value,($siteBase?:'').'/')||str_starts_with($value,'//'))return '';
    return $value;
};
$properties=listingData(true);$view='visitors';$title='Visitor activity';require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./visitors.css?v=<?= filemtime(__DIR__.'/visitors.css') ?>">
<section class="page-heading visitor-heading"><div><span class="eyebrow">WEBSITE ACTIVITY</span><h1>Understand your visitors.</h1><p>See which pages, properties, projects and blogs visitors explore, with their visit dates and account history.</p></div><span class="subtle-tag">Administrator access</span></section>
<p class="visitor-explainer">A browser visitor represents one saved browser ID. One person may use several browsers or devices. Shared browsers may represent more than one person. Account links reflect the last signed-in account associated with that browser.</p>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<section class="stats visitor-stats" aria-label="Activity summary">
<?php foreach([['Page views',(int)$summary['views'],'Recorded visits matching your filters','layers'],['Browser visitors',(int)$summary['visitors'],'Distinct saved browser IDs','building'],['Linked browsers',(int)$summary['linked'],'Browsers associated with an account','house'],['Anonymous browsers',(int)$summary['visitors']-(int)$summary['linked'],'No account associated yet','grid']] as $stat): ?>
<article class="stat-card"><div><span><?= e($stat[0]) ?></span><div class="stat-icon"><?= listingIcon($stat[3]) ?></div></div><strong><?= number_format($stat[1]) ?></strong><p><?= e($stat[2]) ?></p></article>
<?php endforeach; ?>
</section>
<section class="panel visitor-report"><div class="panel-title"><div><h2>Browser visitors</h2><p><?= number_format((int)$summary['visitors']) ?> <?= (int)$summary['visitors']===1?'browser':'browsers' ?> matching your filters</p></div><span class="subtle-tag">Dates in Pakistan time</span></div>
<form method="get" action="./visitors" class="visitor-filters">
<label class="visitor-search">Search<input name="q" type="search" maxlength="120" value="<?= e($query) ?>" placeholder="Name, email, browser ID or page"></label>
<label>Page type<select name="kind"><option value="">All pages</option><?php foreach($kinds as $key=>$label): ?><option value="<?= $key ?>" <?= $kind===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Identity<select name="identity"><option value="">All visitors</option><option value="guest" <?= $identity==='guest'?'selected':'' ?>>Anonymous</option><option value="linked" <?= $identity==='linked'?'selected':'' ?>>Linked to an account</option></select></label>
<label>From date<input name="from" type="date" value="<?= e($from) ?>"></label><label>To date<input name="to" type="date" value="<?= e($to) ?>"></label>
<div class="visitor-filter-actions"><button type="submit" class="primary-button">Apply filters</button><a href="./visitors" class="text-link">Reset</a></div>
</form>
<?php if(!$error&&!$items): ?><div class="empty-state"><h3>No activity found.</h3><p><?= array_filter($filter)?'Try a different date range or search.':'Website visits will appear here as visitors browse your public pages.' ?></p></div><?php elseif($items): ?><div class="visitor-grid">
<?php foreach($items as $item): $isLinked=$item['linked_user_id']!==null;$displayName=$isLinked?($item['name']?:'Linked account'):'Unknown visitor'; ?>
<article class="visitor-card"><header><span class="visitor-symbol" aria-hidden="true"><?= listingIcon($isLinked?'house':'building') ?></span><div><h3><?= e($displayName) ?></h3><p><?= $isLinked?e($item['email']?:'Account no longer available'):'Anonymous browser' ?></p></div><span class="visitor-identity <?= $isLinked?'is-linked':'' ?>"><?= $isLinked?'Linked':'Anonymous' ?></span></header>
<div class="visitor-browser-id"><span>Browser ID</span><code><?= e($item['id']) ?></code></div>
<dl class="visitor-counts"><div><dt>Page views</dt><dd><?= number_format((int)$item['views']) ?></dd></div><div><dt>Different pages</dt><dd><?= number_format((int)$item['pages_seen']) ?></dd></div></dl>
<dl class="visitor-dates"><div><dt>First seen</dt><dd><?= e($formatDate($item['first_seen'])) ?></dd></div><div><dt>Last seen</dt><dd><?= e($formatDate($item['last_seen'])) ?></dd></div></dl>
<footer><span>Counts follow current filters</span><a href="<?= e($link(['visitor'=>$item['id'],'page'=>$page])) ?>#visitor-history" class="text-link" <?= $visitorId===$item['id']?'aria-current="true"':'' ?>>View history &rarr;</a></footer></article>
<?php endforeach; ?></div><?php endif; ?>
<?php if($items): ?><div class="visitor-pagination"><p>Showing <?= $offset+1 ?>&ndash;<?= $offset+count($items) ?> of <?= number_format((int)$summary['visitors']) ?> browsers</p><?php if($pages>1): ?><nav aria-label="Visitor pages"><?php if($page>1): ?><a href="<?= e($link(['page'=>$page-1])) ?>">&lsaquo; Previous</a><?php endif; ?><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="<?= e($link(['page'=>$i])) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?><?php if($page<$pages): ?><a href="<?= e($link(['page'=>$page+1])) ?>">Next &rsaquo;</a><?php endif; ?></nav><?php endif; ?></div><?php endif; ?>
</section>
<?php if($visitorId!==''&&!$error): ?><section class="panel visitor-history-panel" id="visitor-history" aria-labelledby="history-title"><div class="panel-title"><div><h2 id="history-title"><?= $selected?e(($selected['name']?:'Unknown visitor').' — browsing history'):'Browser not found' ?></h2><?php if($selected): ?><p><?= e($selected['id']) ?> &middot; <?= number_format($historyTotal) ?> matching <?= $historyTotal===1?'view':'views' ?></p><?php endif; ?></div><a href="<?= e($link(['page'=>$page])) ?>" class="text-link">Close history</a></div>
<?php if($selected): ?><div class="visitor-history-account"><span><?= $selected['linked_user_id']!==null?'Account linked to this browser':'No account linked yet' ?></span><?php if($selected['linked_user_id']!==null): ?><strong><?= e($selected['name']?:'Linked account') ?></strong><span><?= e($selected['email']??'') ?></span><?php endif; ?><p>First seen <?= e($formatDate($selected['first_seen'])) ?> &middot; Last seen <?= e($formatDate($selected['last_seen'])) ?> PKT</p></div>
<?php if(!$history): ?><div class="empty-state"><p>No views match these filters for this browser.</p></div><?php else: ?><ol class="visitor-history">
<?php foreach($history as $event): $href=$safeHref($event['page_url']); ?><li><span class="visitor-history-dot" aria-hidden="true"></span><div class="visitor-event"><div class="visitor-event-meta"><span class="visitor-kind"><?= e($kinds[$event['page_kind']]??'Website page') ?></span><time datetime="<?= e(str_replace(' ','T',$event['viewed_at']).'+05:00') ?>"><?= e($formatDate($event['viewed_at'])) ?> PKT</time></div><h3><?php if($href!==''): ?><a href="<?= e($href) ?>" target="_blank" rel="noopener"><?= e($event['page_title']?:'Untitled page') ?> &nearr;</a><?php else: ?><?= e($event['page_title']?:'Untitled page') ?><?php endif; ?></h3><p class="visitor-event-url"><?= e($event['page_url']) ?></p><small><?= $event['user_id']!==null?'Linked account: '.e(($event['event_user_name']?:'Account').' · '.($event['event_user_email']?:'No longer available')):'Anonymous view' ?></small></div></li><?php endforeach; ?>
</ol><?php if($historyTotal>100): ?><p class="visitor-history-limit">Showing the latest 100 matching views. Narrow the date or page filters to explore earlier activity.</p><?php endif; ?><?php endif; ?>
<?php else: ?><div class="empty-state"><p>This browser ID is not in the activity records.</p></div><?php endif; ?></section><?php endif; ?>
<?php require __DIR__.'/footer.php'; ?>
