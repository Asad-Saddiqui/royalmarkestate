<?php
$visitorKind='page';$visitorKey=$activePage??'';
$visitorScript=basename($_SERVER['SCRIPT_FILENAME']??'');
if (in_array($visitorScript,['property.php','project.php'],true)&&is_string($_GET['slug']??null)) {$visitorKind=$visitorScript==='property.php'?'property':'project';$visitorKey=$_GET['slug'];}
elseif (($activePage??'')==='blog'&&is_string($_GET['article']??null)&&$_GET['article']!=='') {$visitorKind='blog';$visitorKey=$_GET['article'];}
if (in_array($visitorKey,['home','properties','projects','agents','contact','about','blog'],true)||$visitorKind!=='page'):
$visitorCsrf=$_SESSION['visitor_tracking_csrf']??=bin2hex(random_bytes(32));
?>
<script id="rm-visit-config" type="application/json"><?= json_encode(['endpoint'=>'./visitor-track.php','csrf'=>$visitorCsrf,'kind'=>$visitorKind,'key'=>$visitorKey],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="./assets/visitor-tracking.js?v=<?= filemtime(dirname(__DIR__).'/assets/visitor-tracking.js') ?>" defer></script>
<?php endif; ?>
