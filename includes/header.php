<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> | Royalmark Estate</title>
<link rel="stylesheet" href="./assets/pages.css">
<link rel="stylesheet" href="./assets/listings.css"><link rel="stylesheet" href="./assets/mobile-drawer.css">
<script src="./assets/pages.js" defer></script><script src="./assets/mobile-drawer.js" defer></script>
<?php foreach (($extraCss ?? []) as $asset): ?><link rel="stylesheet" href="<?= e($asset) ?>"><?php endforeach; ?>
<?php foreach (($extraJs ?? []) as $asset): ?><script src="<?= e($asset) ?>" defer></script><?php endforeach; ?>
</head><body>
<a class="skip-link" href="#page-content">Skip to content</a>
<header class="rm-header rm-page-header"><div class="page-header-inner">
<a class="page-logo" href="./" aria-label="Royalmark Estate home"><img src="./assets/royalmark-logo.png" alt="Royalmark Estate" width="130" height="76"></a>
<nav class="page-nav" aria-label="Main navigation">
<?php foreach ($siteRoutes as $key => [$href, $label]): ?>
<a href="./<?= e($href) ?>" <?= $activePage === $key ? 'aria-current="page"' : '' ?>><?= menuIcon($key) ?><?= e($label) ?></a>
<?php endforeach; ?>
</nav>
<div class="page-actions"><button class="rm-page-theme" type="button" title="Switch to dark mode" aria-label="Switch to dark mode">☀</button><a class="page-login" href="./sign-in">Login</a><a class="page-add" href="./sign-in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Add Properties</a></div>
<button class="rm-toggle page-menu-toggle" type="button" aria-label="Open menu"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
</div></header>
<main id="page-content" class="page-content <?= e($mainClass ?? '') ?>">
