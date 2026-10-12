<?php
require __DIR__.'/auth.php';$account=requireLogin();
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/listings.php';
$properties=listingData();$view='settings';$title='Settings';
require __DIR__.'/header.php';
?>
<script src="./settings.js?v=1" defer></script>
<div class="page-heading"><div><span class="eyebrow">YOUR WORKSPACE</span><h1>Settings</h1><p>Manage your dashboard appearance and view your account information.</p></div></div>
<div class="settings-grid">
<section class="panel form-section"><h2>Appearance</h2><p>Choose the dashboard theme for this browser. Your preference saves automatically.</p><div class="settings-theme" role="group" aria-label="Dashboard appearance"><button type="button" data-theme-choice="light" aria-pressed="false"><span aria-hidden="true">☀</span>Light mode</button><button type="button" data-theme-choice="dark" aria-pressed="false"><span aria-hidden="true">☾</span>Dark mode</button></div><p id="settings-status" role="status" aria-live="polite"></p></section>
<section class="panel form-section"><h2>My account</h2><dl class="settings-details"><div><dt>Name</dt><dd><?= e($account['name']?:'Administrator') ?></dd></div><div><dt>Email</dt><dd><?= e($account['email']) ?></dd></div><div><dt>Role</dt><dd><?= e(ucfirst($account['role'])) ?></dd></div><div><dt>Status</dt><dd>Active</dd></div></dl><?php if($account['role']==='admin'): ?><a class="text-link" href="./users">Manage users &nearr;</a><?php endif; ?></section>
<?php if($account['role']==='admin'): ?><section class="panel form-section"><h2>Database connection</h2><p class="settings-connection"><i class="<?= $adminDbConnected?'connected':'disconnected' ?>" aria-hidden="true"></i><?= $adminDbConnected?'Connected':'Disconnected' ?></p><p>Connection status is checked when this page opens.</p></section><?php endif; ?>
<?php if($account['role']==='admin'): ?><section class="panel form-section"><h2>Property catalog</h2><p>Manage property types and locations used by the property creation form.</p><a class="text-link" href="./catalog">Open catalog &nearr;</a></section><?php endif; ?>
</div>
<?php require __DIR__.'/footer.php'; ?>
