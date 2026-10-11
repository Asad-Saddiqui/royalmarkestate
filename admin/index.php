<?php
require __DIR__.'/auth.php';requireLogin();
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/listings.php';
$properties=listingData();
$view=$adminPage ?? (is_string($_GET['view']??null)?$_GET['view']:'overview');
if(!in_array($view,['overview','properties','projects'],true))$view='overview';
$query=is_string($_GET['q']??null)?mb_substr(trim($_GET['q']),0,150):'';
$purpose=is_string($_GET['purpose']??null)?$_GET['purpose']:'';
if(!in_array($purpose,['Rent','Sell'],true))$purpose='';
$filtered=array_values(array_filter($properties,fn($p)=>($query===''||mb_stripos($p['title'].' '.$p['city'],$query)!==false)&&($purpose===''||$p['purpose']===$purpose)));
$rent=count(array_filter($properties,fn($p)=>$p['purpose']==='Rent'));
$featured=count(array_filter($properties,fn($p)=>$p['featured']));
$title=['overview'=>'Overview','properties'=>'Properties','projects'=>'Projects'][$view];
require __DIR__.'/header.php'; ?><section class="page-heading"><div><span class="eyebrow">ROYALMARK ESTATE · ADMIN</span><h1><?= $view==='overview'?'Your workspace, at a glance.':e($title) ?></h1><p><?= $view==='overview'?'A clear view of your property showcase and project concepts.':($view==='properties'?'Manage your database properties, details and media.':'Explore the project showcase and its illustrative milestones.') ?></p></div><span class="preview-badge">Property management</span></section>
<?php if($view!=='projects'): ?><?php if(currentUser()['role']==='admin'): ?><a class="primary-button admin-create-link" href="./create-property">+ Add property</a><?php endif; ?><?php if(isset($_GET['updated'])): ?><div class="success-note" role="status">Property updated successfully.</div><?php endif; ?><?php if(isset($_GET['created'])): ?><div class="success-note" role="status">Property created successfully.</div><?php endif; ?>
<section class="stats" aria-label="Property summary"><?php foreach([['Total properties',count($properties),'building','Sample listings on the website'],['For rent',$rent,'house','Rental property showcase'],['For sale',count($properties)-$rent,'layers','Sale property showcase'],['Featured',$featured,'crown','Highlighted on the homepage']] as $stat): ?><article class="stat-card"><div><span><?= e($stat[0]) ?></span><div class="stat-icon"><?= listingIcon($stat[2]) ?></div></div><strong><?= $stat[1] ?></strong><p><?= e($stat[3]) ?></p></article><?php endforeach; ?></section>
<?php if($view==='overview'): ?><section class="overview-panels"><article class="panel distribution"><div class="panel-title"><div><h2>Listing mix</h2><p>Breakdown of sample inventory</p></div><span class="subtle-tag">By purpose</span></div><div class="mix-content"><div class="donut" style="--rent:<?= round($rent/count($properties)*100) ?>%"><div><strong><?= count($properties) ?></strong><span>properties</span></div></div><div class="mix-legend"><div><span><i></i>For rent</span><strong><?= $rent ?></strong></div><div><span><i class="sale-dot"></i>For sale</span><strong><?= count($properties)-$rent ?></strong></div><p>Counts reflect the website's demo listings.</p></div></div></article><article class="workspace-card"><span class="eyebrow">YOUR DIGITAL SHOWCASE</span><h2>A place for every<br>new beginning.</h2><p>Keep your listings and project presentation connected to the Royalmark website.</p><a href="../property-listing">Explore property showcase <span>&rarr;</span></a></article></section><?php endif; ?>
<?php require __DIR__.'/inventory.php'; ?>
<?php else: require __DIR__.'/inventory.php'; endif; ?>
<?php require __DIR__.'/footer.php'; ?>
