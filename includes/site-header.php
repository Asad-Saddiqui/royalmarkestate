<?php
$headerUser=null;if(!empty($_SESSION['admin_id'])){$headerQuery=Database::getConnection()->prepare('SELECT name,email,role,is_blocked,session_version FROM admin_users WHERE id=?');$headerQuery->execute([$_SESSION['admin_id']]);$headerUser=$headerQuery->fetch(PDO::FETCH_ASSOC);if(!$headerUser||$headerUser['is_blocked']||(int)($_SESSION['admin_session_version']??0)!==(int)$headerUser['session_version'])$headerUser=null;}
$headerCsrf=$_SESSION['admin_csrf']??=bin2hex(random_bytes(32));
?>
<header class="rm-header rm-page-header"><div class="page-header-inner">
<a class="page-logo" href="./" aria-label="Royalmark Estate home"><img src="./assets/royalmark-logo.png" alt="Royalmark Estate" width="130" height="76"></a>
<nav class="page-nav" aria-label="Main navigation">
<?php foreach ($siteRoutes as $key => [$href, $label]): ?>
<a href="./<?= e($href) ?>" <?= $activePage === $key ? 'aria-current="page"' : '' ?>><?= menuIcon($key) ?><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<div class="page-actions"><button class="rm-page-theme" type="button" title="Switch to dark mode" aria-label="Switch to dark mode">☀</button><?php if($headerUser): ?><a class="page-account" href="./admin/" aria-label="Your dashboard" title="<?= e($headerUser['name']?:$headerUser['email']) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><span class="account-name"><?= e($headerUser['name']?:'My account') ?></span></a><form class="page-logout logout-form" action="./admin/logout" method="post"><input type="hidden" name="csrf" value="<?= e($headerCsrf) ?>"><button type="submit" aria-label="Logout" title="Logout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/></svg></button></form><?php else: ?><a class="page-login" href="./admin/login">Login</a><?php endif; ?><a class="page-add" href="<?= $headerUser?($headerUser['role']==='admin'?'./admin/create-property':'./admin/'):'./admin/login' ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add Properties</a></div>
<button class="rm-toggle page-menu-toggle" type="button" aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
</div></header>
