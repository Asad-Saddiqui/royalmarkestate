<?php
require __DIR__.'/auth.php';
$userEntryPath=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$isPublicUserRoute=(bool)preg_match('~/user/?$~',$userEntryPath);
if($isPublicUserRoute){$adminBase=preg_replace('~/user/?$~','/admin',$userEntryPath);}
$account=requireLogin();
if(!$isPublicUserRoute){header('Location: ../user');exit;}
if($account['role']==='admin'){header('Location: '.$adminBase.'/');exit;}
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/listings.php';
$properties=listingData();$view='overview';$title='My dashboard';
$rent=count(array_filter($properties,fn($p)=>$p['purpose']==='Rent'));
require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./user-dashboard.css?v=1">
<section class="page-heading"><div><span class="eyebrow">ROYALMARK ESTATE &middot; YOUR ACCOUNT</span><h1>Welcome, <?= e($account['name']?:'there') ?>.</h1><p>Explore properties, discover projects and find your next place.</p></div><span class="preview-badge">Active account</span></section>
<section class="stats" aria-label="Available properties">
<?php foreach([['Available properties',count($properties),'house','Explore published listings'],['For rent',$rent,'building','Find a place to call home'],['For sale',count($properties)-$rent,'layers','Discover your next investment']] as $stat): ?>
<article class="stat-card"><div><span><?= e($stat[0]) ?></span><div class="stat-icon"><?= listingIcon($stat[2]) ?></div></div><strong><?= $stat[1] ?></strong><p><?= e($stat[3]) ?></p></article>
<?php endforeach; ?></section>
<section class="user-dashboard-panels"><article class="workspace-card"><span class="eyebrow">FIND YOUR NEXT ADDRESS</span><h2>Your next chapter<br>starts with a place.</h2><p>Browse property photos, pricing documents and nearby locations, all in one place.</p><a href="./properties">Browse properties <span>&rarr;</span></a></article>
<article class="panel user-account-panel"><div class="panel-title"><div><h2>My account</h2><p>Your Royalmark profile</p></div><span class="subtle-tag">User</span></div><dl><div><dt>Name</dt><dd><?= e($account['name']?:'Not provided') ?></dd></div><div><dt>Email</dt><dd><?= e($account['email']) ?></dd></div><div><dt>Account status</dt><dd>Active</dd></div></dl><a class="text-link" href="./settings">Account settings &nearr;</a></article></section>
<section class="panel user-quick-links"><div class="panel-title"><div><h2>Explore Royalmark</h2><p>Everything you need for your property search.</p></div></div><div><a href="./properties"><?= listingIcon('house') ?><span><strong>Properties</strong><small>View photos, prices and details</small></span><b>&rarr;</b></a><a href="./projects"><?= listingIcon('building') ?><span><strong>Projects</strong><small>Explore our project showcase</small></span><b>&rarr;</b></a><a href="../contact-us"><?= listingIcon('search') ?><span><strong>Contact our team</strong><small>Get help with your property search</small></span><b>&rarr;</b></a></div></section>
<?php require __DIR__.'/footer.php'; ?>
