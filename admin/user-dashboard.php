<?php
require __DIR__.'/auth.php';
$userEntryPath=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$isPublicUserRoute=(bool)preg_match('~/user/?$~',$userEntryPath);
if($isPublicUserRoute){$adminBase=preg_replace('~/user/?$~','/admin',$userEntryPath);}
$account=requireLogin();
header('Cache-Control: private, no-store');
if(!$isPublicUserRoute){header('Location: ../user');exit;}
if($account['role']==='admin'){header('Location: '.$adminBase.'/');exit;}
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/listings.php';
require_once dirname(__DIR__).'/Config/bookings.php';
$myBookings=bookingList((int)$account['id']);$recentBookings=array_slice($myBookings,0,3);
$properties=[];$view='overview';$title='My dashboard';
require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./user-dashboard.css?v=<?= filemtime(__DIR__.'/user-dashboard.css') ?>">
<link rel="stylesheet" href="./bookings.css?v=<?= filemtime(__DIR__.'/bookings.css') ?>">
<section class="page-heading"><div><span class="eyebrow">ROYALMARK ESTATE &middot; YOUR ACCOUNT</span><h1>Welcome, <?= e($account['name']?:'there') ?>.</h1><p>Track your requests, view team updates and manage your account.</p></div><span class="preview-badge">Active account</span></section>
<section class="user-dashboard-panels user-account-only"><article class="panel user-account-panel"><div class="panel-title"><div><h2>My account</h2><p>Your Royalmark profile</p></div><span class="subtle-tag">User</span></div><dl><div><dt>Name</dt><dd><?= e($account['name']?:'Not provided') ?></dd></div><div><dt>Email</dt><dd><?= e($account['email']) ?></dd></div><div><dt>Account status</dt><dd>Active</dd></div></dl><a class="text-link" href="./settings">Account settings &nearr;</a></article></section>
<section class="panel booking-summary-panel"><div class="panel-title"><div><h2>My bookings</h2><p>Your property and project visit requests.</p></div><a class="text-link" href="../user/bookings">View all <?= count($myBookings) ?> bookings &nearr;</a></div><div class="booking-summary-stats"><?php foreach(['pending'=>'Pending','confirmed'=>'Confirmed','completed'=>'Completed'] as $status=>$label): ?><div><strong><?= count(array_filter($myBookings,fn($booking)=>$booking['status']===$status)) ?></strong><span><?= $label ?></span></div><?php endforeach; ?></div><?php if($recentBookings): ?><div class="booking-recent-list"><?php foreach($recentBookings as $booking): ?><a href="../user/bookings?kind=<?= e($booking['kind']) ?>#booking-<?= (int)$booking['id'] ?>"><span><strong><?= e($booking['listing_title']) ?></strong><small><?= e($booking['reference']) ?> &middot; <?= ucfirst(e($booking['kind'])) ?></small></span><span class="booking-status is-<?= e($booking['status']) ?>"><?= ucfirst(e($booking['status'])) ?></span></a><?php endforeach; ?></div><?php else: ?><div class="booking-summary-empty">No bookings yet. Explore a property or project and send a request to arrange a visit. <a href="../user/bookings">Open my bookings &nearr;</a></div><?php endif; ?></section>
<section class="panel user-quick-links"><div class="panel-title"><div><h2>Need a hand?</h2><p>Get help with an appointment or your account.</p></div></div><div><a href="../contact-us"><?= listingIcon('search') ?><span><strong>Contact our team</strong><small>Ask a question or discuss your request</small></span><b>&rarr;</b></a><a href="./settings"><?= listingIcon('agents') ?><span><strong>Account settings</strong><small>View your profile and appearance preferences</small></span><b>&rarr;</b></a></div></section>
<?php require __DIR__.'/footer.php'; ?>
