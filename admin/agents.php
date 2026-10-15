<?php
require __DIR__.'/auth.php';
requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';
require_once dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/agents-store.php';

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    adminCsrf();
    $slug=is_string($_POST['slug']??null)?trim($_POST['slug']):'';
    $action=is_string($_POST['action']??null)?$_POST['action']:'';
    try{
        if($action==='delete'){
            if(($_POST['confirm_delete']??'')!=='1')throw new InvalidArgumentException('Confirm deletion before removing this agent.');
            agentDelete($slug);
            header('Location: ./agents?deleted=1',true,303);exit;
        }
        if($action!=='publication')throw new InvalidArgumentException('Choose a valid action.');
        $status=is_string($_POST['status']??null)?$_POST['status']:'';
        if(!in_array($status,['published','unpublished'],true))throw new InvalidArgumentException('Choose a valid visibility setting.');
        agentSetPublication($slug,$status);
        header('Location: ./agents?publication='.rawurlencode($status),true,303);exit;
    }catch(InvalidArgumentException $exception){http_response_code(422);$error=$exception->getMessage();}
    catch(Throwable $exception){http_response_code(422);error_log('Agent action failed: '.$exception->getMessage());$error='Unable to update this agent. Refresh the page and try again.';}
}
$agents=agentData(true);
$query=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,150):'';
$visibility=is_string($_GET['visibility']??null)?$_GET['visibility']:'';
if(!in_array($visibility,['','published','unpublished'],true))$visibility='';
$city=is_string($_GET['city']??null)?mb_substr(trim($_GET['city']),0,100):'';
$cities=array_values(array_unique(array_filter(array_column($agents,'city'))));sort($cities,SORT_NATURAL|SORT_FLAG_CASE);
$items=array_values(array_filter($agents,static fn($a)=>($query===''||mb_stripos($a['name'].' '.$a['position'].' '.$a['city'].' '.$a['email'].' '.implode(' ',$a['specialities']),$query)!==false)&&($visibility===''||$a['status']===$visibility)&&($city===''||$a['city']===$city)));
$layout=($_GET['layout']??'cards')==='list'?'list':'cards';
$total=count($items);$perPage=6;$pages=max(1,(int)ceil($total/$perPage));
$page=min($pages,max(1,(int)(is_scalar($_GET['page']??null)?$_GET['page']:1)));$offset=($page-1)*$perPage;$items=array_slice($items,$offset,$perPage);
$pageLink=static fn($number)=>'./agents?'.http_build_query(['q'=>$query,'visibility'=>$visibility,'city'=>$city,'layout'=>$layout,'page'=>$number]);
$view='agents';$title='Agents';$properties=listingData(true);require __DIR__.'/header.php';
$notice='';if(isset($_GET['created']))$notice='Agent created successfully.';elseif(isset($_GET['updated']))$notice='Agent updated successfully.';elseif(isset($_GET['deleted']))$notice='Agent deleted successfully.';elseif(in_array($_GET['publication']??'',['published','unpublished'],true))$notice='Agent '.$_GET['publication'].' successfully.';
$initials=static function(string $name):string{$parts=preg_split('/\s+/u',trim($name));return mb_strtoupper(mb_substr($parts[0]??'',0,1).(count($parts)>1?mb_substr(end($parts),0,1):mb_substr($parts[0]??'',1,1)));};
?>
<link rel="stylesheet" href="./agents-admin.css?v=<?= filemtime(__DIR__.'/agents-admin.css') ?>">
<script src="./agent-form.js?v=<?= filemtime(__DIR__.'/agent-form.js') ?>" defer></script>
<section class="page-heading agent-page-heading"><div><span class="eyebrow">YOUR PROPERTY TEAM</span><h1>Manage your agents.</h1><p>Add team profiles, update their information and choose who appears on the website.</p></div><a class="primary-button" href="./create-agent">+ Add agent</a></section>
<?php if($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if($notice): ?><div class="success-note" role="status"><?= e($notice) ?></div><?php endif; ?>
<section class="stats agent-stats" aria-label="Agent summary">
<?php foreach([['Total agents',count($agents),'building'],['Published',count(array_filter($agents,fn($a)=>$a['status']==='published')),'house'],['Unpublished',count(array_filter($agents,fn($a)=>$a['status']==='unpublished')),'layers'],['Cities',count($cities),'grid']] as $stat): ?><article class="stat-card"><div><span><?= e($stat[0]) ?></span><div class="stat-icon"><?= listingIcon($stat[2]) ?></div></div><strong><?= $stat[1] ?></strong></article><?php endforeach; ?>
</section>
<section class="panel agent-inventory">
<div class="panel-title"><div><h2>Agent directory</h2><p><?= $total ?> <?= $total===1?'agent':'agents' ?> found</p></div><div class="layout-switch" aria-label="Directory display"><?php foreach(['cards'=>'Cards','list'=>'List'] as $mode=>$label): ?><a href="<?= e('./agents?'.http_build_query(['q'=>$query,'visibility'=>$visibility,'city'=>$city,'layout'=>$mode])) ?>" <?= $layout===$mode?'aria-current="true"':'' ?>><?= listingIcon($mode==='cards'?'grid':'filter') ?><?= $label ?></a><?php endforeach; ?></div></div>
<form class="table-toolbar agent-directory-toolbar" method="get" action="./agents"><input type="hidden" name="layout" value="<?= e($layout) ?>"><label class="search-field"><?= listingIcon('search') ?><span class="sr-only">Search agents</span><input type="search" name="q" maxlength="150" value="<?= e($query) ?>" placeholder="Search name, city or speciality"></label><label><span class="sr-only">City</span><select name="city"><option value="">All cities</option><?php foreach($cities as $option): ?><option value="<?= e($option) ?>" <?= $city===$option?'selected':'' ?>><?= e($option) ?></option><?php endforeach; ?></select></label><label><span class="sr-only">Website visibility</span><select name="visibility"><option value="">All visibility</option><option value="published" <?= $visibility==='published'?'selected':'' ?>>Published</option><option value="unpublished" <?= $visibility==='unpublished'?'selected':'' ?>>Unpublished</option></select></label><button type="submit" class="primary-button">Filter</button><?php if($query!==''||$city!==''||$visibility!==''): ?><a href="./agents" class="text-link">Reset</a><?php endif; ?></form>
<?php if(!$items): ?><div class="empty-state"><h3>No agents match your search.</h3><p>Try another search or add a profile to your team.</p><a href="./create-agent" class="text-link">+ Add your first agent</a></div><?php else: ?>
<div class="agent-admin-grid <?= $layout==='list'?'is-list':'' ?>">
<?php foreach($items as $agent): ?>
<article class="agent-admin-card">
<div class="agent-admin-photo"><?php if($agent['photo']!==''): ?><img src="<?= e(str_starts_with($agent['photo'],'./')?'../'.substr($agent['photo'],2):$agent['photo']) ?>" alt="<?= e($agent['photo_alt']?:$agent['name']) ?>" width="600" height="480" loading="lazy"><?php else: ?><span class="agent-admin-initials" aria-label="Profile image not supplied"><?= e($initials($agent['name'])) ?></span><?php endif; ?><span class="agent-visibility <?= $agent['status']==='published'?'is-published':'' ?>"><?= ucfirst($agent['status']) ?></span></div>
<div class="agent-admin-info"><div class="agent-admin-name"><h3><?= e($agent['name']) ?></h3><?php if($agent['is_sample']): ?><span class="subtle-tag">Sample profile</span><?php endif; ?></div><p class="agent-admin-position"><?= e($agent['position']) ?></p><p class="agent-admin-location"><?= listingIcon('location') ?><?= e($agent['city']?:'City not specified') ?><?php if(!$agent['is_sample']&&(int)$agent['experience_years']>0): ?><span>&middot; <?= e((string)$agent['experience_years']) ?> years</span><?php endif; ?></p><div class="agent-admin-chips"><?php foreach(array_slice($agent['specialities'],0,3) as $speciality): ?><span><?= e($speciality) ?></span><?php endforeach; ?></div>
<div class="agent-admin-contact"><?php if($agent['email']!==''): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/></svg><?= e($agent['email']) ?></span><?php endif; ?><?php if($agent['phone']!==''): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="m5 3 4 1 1 5-3 2a13 13 0 0 0 6 6l2-3 5 1 1 4c0 2-2 3-4 2A20 20 0 0 1 3 7C2 5 3 3 5 3z"/></svg><?= e($agent['phone']) ?></span><?php endif; ?></div>
<div class="agent-admin-actions"><a class="agent-action-button" href="./create-agent?slug=<?= rawurlencode($agent['slug']) ?>">Edit</a><form method="post" action="./agents"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="publication"><input type="hidden" name="slug" value="<?= e($agent['slug']) ?>"><input type="hidden" name="status" value="<?= $agent['status']==='published'?'unpublished':'published' ?>"><button type="submit" class="agent-action-button"><?= $agent['status']==='published'?'Unpublish':'Publish' ?></button></form><?php if($agent['status']==='published'): ?><a class="agent-action-button agent-view-link" href="../agents?agent=<?= rawurlencode($agent['slug']) ?>#agent-<?= rawurlencode($agent['slug']) ?>">View &nearr;</a><?php endif; ?><form method="post" action="./agents" class="delete-agent-form" data-agent-name="<?= e($agent['name']) ?>"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="slug" value="<?= e($agent['slug']) ?>"><input type="hidden" name="confirm_delete" value="0"><button type="submit" class="agent-action-button agent-delete-button">Delete</button></form></div></div>
</article>
<?php endforeach; ?></div><?php endif; ?>
<?php if($total): ?><div class="inventory-pagination"><p>Showing <?= $offset+1 ?>&ndash;<?= $offset+count($items) ?> of <?= $total ?></p><?php if($pages>1): ?><nav aria-label="Agent directory pages"><?php if($page>1): ?><a href="<?= e($pageLink($page-1)) ?>">&lsaquo; Previous</a><?php endif; ?><?php for($i=1;$i<=$pages;$i++): ?><a href="<?= e($pageLink($i)) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor; ?><?php if($page<$pages): ?><a href="<?= e($pageLink($page+1)) ?>">Next &rsaquo;</a><?php endif; ?></nav><?php endif; ?></div><?php endif; ?>
</section>
<?php require __DIR__.'/footer.php'; ?>
