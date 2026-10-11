<?php
require __DIR__.'/auth.php';requireAdmin();
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/listings.php';
$kinds=['property_type'=>'Property Types','location'=>'Locations'];
$kind=is_string($_GET['kind']??null)&&isset($kinds[$_GET['kind']])?$_GET['kind']:'property_type';
$error='';$properties=listingData();
if($_SERVER['REQUEST_METHOD']==='POST'){
    adminCsrf();
    $action=$_POST['action']??'';$id=filter_var($_POST['id']??0,FILTER_VALIDATE_INT);
    $name=is_string($_POST['name']??null)?trim($_POST['name']):'';
    try {
        if(!in_array($action,['create','edit','delete'],true))throw new RuntimeException('Invalid action.');
        if($action!=='delete'&&($name===''||mb_strlen($name)>100))throw new RuntimeException('Enter a name of up to 100 characters.');
        if($action!=='create'){
            $query=adminDb()->prepare('SELECT name FROM catalog_entries WHERE id=? AND kind=?');$query->execute([$id,$kind]);$old=$query->fetchColumn();
            if($old===false)throw new RuntimeException('This entry no longer exists.');
            foreach($properties as $property){
                if(($property[$kind==='location'?'city':'type']??'')===$old&&($action==='delete'||$name!==$old))throw new RuntimeException('This entry is used by a property. Keep it and add a new entry instead.');
            }
        }
        if($action==='create'){$query=adminDb()->prepare('INSERT INTO catalog_entries (kind,name) VALUES (?,?)');$query->execute([$kind,$name]);}
        elseif($action==='edit'){$query=adminDb()->prepare('UPDATE catalog_entries SET name=? WHERE id=? AND kind=?');$query->execute([$name,$id,$kind]);}
        else{$query=adminDb()->prepare('DELETE FROM catalog_entries WHERE id=? AND kind=?');$query->execute([$id,$kind]);}
        $_SESSION['catalog_notice']=$action==='delete'?'Entry deleted.':($action==='edit'?'Entry updated.':'Entry added.');
        header('Location: ./catalog?kind='.$kind);exit;
    }catch(PDOException $exception){$error=($exception->errorInfo[1]??0)===1062?'That name already exists.':'Could not save the entry. Please try again.';}
    catch(RuntimeException $exception){$error=$exception->getMessage();}
}
$search=is_string($_GET['q']??null)?trim($_GET['q']):'';
$query=adminDb()->prepare('SELECT id,name FROM catalog_entries WHERE kind=? ORDER BY name');$query->execute([$kind]);$entries=$query->fetchAll();
if($search!=='')$entries=array_values(array_filter($entries,fn($entry)=>mb_stripos($entry['name'],$search)!==false));
$notice=$_SESSION['catalog_notice']??'';unset($_SESSION['catalog_notice']);
$view='catalog';$title='Catalog';require __DIR__.'/header.php';
?>
<link rel="stylesheet" href="./catalog.css"><script src="./catalog.js" defer></script>
<div class="page-heading"><div><span class="eyebrow">MANAGE YOUR CATALOG</span><h1>Catalog</h1><p>Add property types and locations for your property forms.</p></div><button class="primary-button" type="button" id="catalog-add">+ Add <?= $kind==='location'?'location':'property type' ?></button></div>
<?php if($error||$notice): ?><div class="catalog-notice <?= $error?'is-error':'' ?>" role="<?= $error?'alert':'status' ?>"><?= e($error?:$notice) ?><button type="button" aria-label="Dismiss notification">&times;</button></div><?php endif; ?>
<section class="panel catalog-panel"><nav class="catalog-tabs" aria-label="Catalog categories"><?php foreach($kinds as $key=>$label): ?><a href="./catalog?kind=<?= $key ?>" <?= $kind===$key?'aria-current="page"':'' ?>><?= listingIcon($key==='location'?'location':'layers') ?><?= e($label) ?></a><?php endforeach; ?></nav>
<form class="catalog-search" method="get"><input type="hidden" name="kind" value="<?= $kind ?>"><input name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($kinds[$kind])) ?>" aria-label="Search catalog"><button class="primary-button">Search</button><?php if($search!==''): ?><a href="./catalog?kind=<?= $kind ?>">Reset</a><?php endif; ?></form>
<div class="catalog-count"><?= count($entries) ?> <?= e(strtolower($kinds[$kind])) ?></div>
<div class="catalog-rows"><?php foreach($entries as $entry): ?><div class="catalog-row"><span><?= e($entry['name']) ?></span><div><button type="button" class="catalog-edit" data-id="<?= $entry['id'] ?>" data-name="<?= e($entry['name']) ?>" aria-label="Edit <?= e($entry['name']) ?>">Edit</button><button type="button" class="catalog-delete" data-id="<?= $entry['id'] ?>" data-name="<?= e($entry['name']) ?>" aria-label="Delete <?= e($entry['name']) ?>">Delete</button></div></div><?php endforeach; ?><?php if(!$entries): ?><div class="catalog-empty"><h2><?= $search?'No matching entries':'Your catalog is ready' ?></h2><p><?= $search?'Try another search.':'Use the Add button to create your first entry.' ?></p></div><?php endif; ?></div></section>
<dialog id="catalog-modal" class="catalog-modal" aria-labelledby="catalog-modal-title" aria-describedby="catalog-description"><form method="post"><input type="hidden" name="csrf" value="<?= e(adminToken()) ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="id" value=""><div class="catalog-modal-heading"><h2 id="catalog-modal-title">Add entry</h2><button type="button" data-close aria-label="Close">&times;</button></div><p id="catalog-description">Use a clear, unique name.</p><label id="catalog-name-label">Name<input name="name" maxlength="100" required autocomplete="off"></label><div class="catalog-modal-actions"><button type="button" data-close>Cancel</button><button class="primary-button" type="submit">Save entry</button></div></form></dialog>
<?php require __DIR__.'/footer.php'; ?>
