<?php
$headerUser=null;
if(!empty($_SESSION['admin_id'])){
    $headerQuery=Database::getConnection()->prepare('SELECT name,email,role,is_blocked,session_version FROM admin_users WHERE id=?');
    $headerQuery->execute([$_SESSION['admin_id']]);$headerUser=$headerQuery->fetch(PDO::FETCH_ASSOC);
    if(!$headerUser||$headerUser['is_blocked']||(int)($_SESSION['admin_session_version']??0)!==(int)$headerUser['session_version'])$headerUser=null;
}
$headerCsrf=$_SESSION['admin_csrf']??=bin2hex(random_bytes(32));
$headerName=$headerUser?(trim($headerUser['name'])?:$headerUser['email']):'';
$headerNameParts=preg_split('/\s+/u',$headerName,-1,PREG_SPLIT_NO_EMPTY)?:[];
$headerInitials=count($headerNameParts)>1?mb_substr($headerNameParts[0],0,1).mb_substr(end($headerNameParts),0,1):mb_substr($headerName,0,2);
$headerDashboard=$headerUser&&$headerUser['role']==='user'?'./user':'./admin/';
$headerBookings=$headerUser&&$headerUser['role']==='user'?'./user/bookings':'./admin/bookings';
?>
<header class="rm-header rm-page-header"><div class="page-header-inner">
<a class="page-logo" href="./" aria-label="Royalmark Estate home"><img src="./assets/royalmark-logo.png" alt="Royalmark Estate" width="130" height="76"></a>
<nav class="page-nav" aria-label="Main navigation">
<?php foreach ($siteRoutes as $key => [$href, $label]): ?>
<a href="./<?= e($href) ?>" <?= $activePage === $key ? 'aria-current="page"' : '' ?>><?= menuIcon($key) ?><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<div class="page-actions">
<button class="rm-page-theme" type="button" title="Switch to dark mode" aria-label="Switch to dark mode"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg></button>
<?php if($headerUser): ?>
<details class="rm-account">
<summary class="page-account rm-avatar-toggle" data-user-name="<?= e($headerName) ?>" data-user-email="<?= e($headerUser['email']) ?>" data-dashboard="<?= e($headerDashboard) ?>" data-bookings="<?= e($headerBookings) ?>" aria-label="Account menu for <?= e($headerName) ?>" aria-controls="rm-account-panel" aria-expanded="false"><span class="rm-avatar-initials" aria-hidden="true"><?= e(mb_strtoupper($headerInitials)) ?></span><svg class="rm-avatar-chevron" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m5 8 5 5 5-5"/></svg></summary>
<div class="rm-account-menu" id="rm-account-panel">
<div class="rm-account-profile"><span class="rm-avatar-initials" aria-hidden="true"><?= e(mb_strtoupper($headerInitials)) ?></span><div><strong><?= e($headerName) ?></strong><span><?= e($headerUser['email']) ?></span><small><?= $headerUser['role']==='admin'?'Administrator':'User' ?></small></div></div>
<nav aria-label="Account navigation"><a href="<?= e($headerDashboard) ?>"><?= menuIcon('properties') ?>My dashboard</a><a href="<?= e($headerBookings) ?>"><?= menuIcon('calendar') ?>My bookings</a><a href="./admin/settings"><?= menuIcon('about') ?>My profile &amp; settings</a></nav>
<form class="page-logout logout-form" action="./admin/logout" method="post"><input type="hidden" name="csrf" value="<?= e($headerCsrf) ?>"><button type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/></svg><span>Logout</span></button></form>
</div></details>
<?php else: ?><a class="page-login" href="./admin/login">Login</a><?php endif; ?>
</div>
<button class="rm-toggle page-menu-toggle" type="button" aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
</div></header>
