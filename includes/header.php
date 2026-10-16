<?php
$versionAsset=static function(string $asset): string {
    $path=explode('?', $asset, 2)[0];
    $local=dirname(__DIR__).'/'.ltrim($path,'./');
    return is_file($local)?$path.'?v='.filemtime($local):$asset;
};
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<base href="<?= e(rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')),'/').'/') ?>"><title><?= e($pageTitle) ?> | Royalmark Estate</title>
<script src="<?= e($versionAsset('./assets/theme-preference.js')) ?>" data-theme-scope="site"></script>
<link rel="stylesheet" href="./assets/pages.css">
<link rel="stylesheet" href="./assets/listings.css"><link rel="stylesheet" href="<?= e($versionAsset('./assets/mobile-drawer.css')) ?>">
<script src="<?= e($versionAsset('./assets/pages.js')) ?>" defer></script><script src="<?= e($versionAsset('./assets/mobile-drawer.js')) ?>" defer></script>
<?php foreach (($extraCss ?? []) as $asset): ?><link rel="stylesheet" href="<?= e($versionAsset($asset)) ?>"><?php endforeach; ?>
<?php foreach (($extraJs ?? []) as $asset): ?><script src="<?= e($versionAsset($asset)) ?>" defer></script><?php endforeach; ?>
<link rel="stylesheet" href="./assets/custom-select.css"><script src="./assets/custom-select.js" defer></script><link rel="icon" type="image/png" href="./assets/royalmark-logo.png?v=brand-1"><link rel="apple-touch-icon" href="./assets/royalmark-logo.png?v=brand-1"><script src="./assets/branding.js?v=1" defer></script><link rel="stylesheet" href="<?= e($versionAsset('./assets/shared-header.css')) ?>"><link rel="stylesheet" href="<?= e($versionAsset('./assets/account-menu.css')) ?>"><script src="<?= e($versionAsset('./assets/account-menu.js')) ?>" defer></script><link rel="stylesheet" href="<?= e($versionAsset('./assets/company-footer.css')) ?>"><?php require __DIR__.'/visitor-tracking.php'; ?></head><body>
<a class="skip-link" href="#page-content">Skip to content</a>
<?php require __DIR__.'/site-header.php'; ?>
<main id="page-content" class="page-content <?= e($mainClass ?? '') ?>">
