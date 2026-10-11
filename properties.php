<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/listings.php';
$pageTitle='Properties'; $activePage='properties';
$extraJs=['./assets/listings.js']; $mainClass='listing-page';
$properties=listingData(); $priceCeiling=max(58000000,...array_column($properties,'price'));
function queryText(string $key, string $fallback=''): string { return is_string($_GET[$key]??null)?trim($_GET[$key]):$fallback; }
$q=mb_substr(queryText('q'),0,150);
$purpose=ucfirst(strtolower(queryText('purpose',queryText('listing-purpose')))); if(!in_array($purpose,['Rent','Sell'],true))$purpose='';
$availableTypes=array_values(array_unique(array_merge(['Apartment','Villa','Duplex','House','Studio','Penthouse','Shop/Retail'],array_column($properties,'type'))));
$types=$_GET['types']??[]; if(!is_array($types))$types=[]; $types=array_values(array_intersect(array_filter($types, 'is_string'),$availableTypes));
$legacyType=queryText('type',queryText('property-types')); $aliases=['apartment'=>'Apartment','independent-house'=>'House','house'=>'House','villa'=>'Villa','penthouse'=>'Penthouse']; if(!$types&&isset($aliases[strtolower($legacyType)]))$types=[$aliases[strtolower($legacyType)]];
$featured=queryText('featured')==='1'; $beds=max(0,min(20,(int)queryText('beds'))); $baths=max(0,min(20,(int)queryText('baths')));
$min=max(0,min($priceCeiling,(float)queryText('min_price'))); $max=queryText('max_price')!==''?max(0,min($priceCeiling,(float)queryText('max_price'))):$priceCeiling;
$city=queryText('city'); $cities=array_values(array_unique(array_column($properties,'city'))); if(!in_array($city,$cities,true))$city='';
$sort=queryText('sort','recommended'); if(!in_array($sort,['recommended','price-asc','price-desc','area-desc'],true))$sort='recommended';
$view=queryText('view',queryText('current_view'))==='map'?'map':'grid';
$visible=array_values(array_filter($properties,function($p)use($q,$purpose,$types,$featured,$beds,$baths,$min,$max,$city){return ($q===''||mb_stripos($p['title'].' '.$p['city'].' '.$p['type'],$q)!==false)&&($purpose===''||$p['purpose']===$purpose)&&(!$types||in_array($p['type'],$types,true))&&(!$featured||$p['featured'])&&($beds===0||$p['beds']>=$beds)&&($baths===0||$p['baths']>=$baths)&&$p['price']>=$min&&$p['price']<=$max&&($city===''||$p['city']===$city); }));
if($sort!=='recommended')usort($visible,fn($a,$b)=>$sort==='price-desc'?$b['price']<=>$a['price']:($sort==='area-desc'?$b['area']<=>$a['area']:$a['price']<=>$b['price']));
$typeIcons=['Apartment'=>'building','Villa'=>'house','Duplex'=>'layers','House'=>'house','Studio'=>'studio','Penthouse'=>'crown','Shop/Retail'=>'shop'];
foreach($availableTypes as $availableType)$typeIcons[$availableType]=$typeIcons[$availableType]??'building';
require __DIR__.'/includes/header.php';
?>
<h1 class="visually-hidden">Properties</h1>
<div class="listing-mobile-toolbar"><button class="filter-mobile-button" type="button" aria-expanded="false" aria-controls="listing-filter"><?= listingIcon('filter') ?> Filters</button><span><?= count($visible) ?> properties</span></div>
<div class="listing-layout">
<aside class="listing-filter" id="listing-filter" aria-label="Property filters"><form method="get" action="./property-listing" id="listing-filter-form">
<div class="filter-heading"><h2>Filter</h2><div class="listing-view-buttons"><button name="view" value="grid" aria-label="Grid view" aria-pressed="<?= $view==='grid'?'true':'false' ?>"><?= listingIcon('grid') ?></button><button name="view" value="map" aria-label="Map view" aria-pressed="<?= $view==='map'?'true':'false' ?>"><?= listingIcon('map') ?></button></div></div>
<label class="listing-search"><?= listingIcon('search') ?><span class="visually-hidden">Search properties</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search..."></label>
<label class="featured-toggle"><input type="checkbox" name="featured" value="1" <?= $featured?'checked':'' ?>> Show Only Featured Property</label>
<fieldset><legend>Property For</legend><div class="purpose-options"><?php foreach([''=>'All','Rent'=>'Rent','Sell'=>'Sell'] as $value=>$label): ?><label><input type="radio" name="purpose" value="<?= e($value) ?>" <?= $purpose===$value?'checked':'' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></fieldset>
<fieldset><legend>Property Type</legend><div class="type-options"><?php foreach($typeIcons as $type=>$icon): ?><label><input type="checkbox" name="types[]" value="<?= e($type) ?>" <?= in_array($type,$types,true)?'checked':'' ?>><span><?= listingIcon($icon) ?><span><?= e($type) ?></span></span></label><?php endforeach; ?></div></fieldset>
<label class="filter-field">Bedrooms<input type="number" name="beds" min="0" max="20" value="<?= $beds?:'' ?>" placeholder="ex.3 bhk"><small>Minimum bedrooms</small></label>
<fieldset><legend class="price-legend">Price <span>$<?= number_format($min) ?> - $<?= number_format($max) ?></span></legend><div class="price-inputs"><label><span class="visually-hidden">Minimum price</span><input type="number" name="min_price" min="0" max="<?= e($priceCeiling) ?>" value="<?= $min?:'' ?>" placeholder="Min price"></label><label><span class="visually-hidden">Maximum price</span><input type="number" name="max_price" min="0" max="<?= e($priceCeiling) ?>" value="<?= $max==$priceCeiling?'':e($max) ?>" placeholder="Max price"></label></div></fieldset>
<label class="filter-field">Location<select name="city"><option value="">All locations</option><?php foreach($cities as $location): ?><option <?= $city===$location?'selected':'' ?>><?= e($location) ?></option><?php endforeach; ?></select></label>
<label class="filter-field">Bathrooms<input type="number" name="baths" min="0" max="20" value="<?= $baths?:'' ?>" placeholder="Any bathrooms"></label>
<label class="filter-field">Sort by<select name="sort"><?php foreach(['recommended'=>'Recommended','price-asc'=>'Price: low to high','price-desc'=>'Price: high to low','area-desc'=>'Largest area'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $sort===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<div class="filter-actions"><button class="button" type="submit">Apply filters</button><a href="./property-listing">Reset all</a></div><input type="hidden" name="current_view" value="<?= e($view) ?>">
<p class="listing-demo-note">Includes sample listings and properties added through admin. Confirm availability with Royalmark.</p>
</form></aside>
<section class="listing-results" aria-label="Property results">
<?php if($view==='map'&&$visible): ?><section class="listing-map" aria-label="Location overview"><div><h2>City overview</h2><label>Location <select id="listing-map-city"><?php foreach(array_unique(array_column($visible,'city')) as $location): ?><option><?= e($location) ?></option><?php endforeach; ?></select></label></div><iframe id="listing-map-frame" title="Property city overview map" loading="lazy" referrerpolicy="no-referrer" src="about:blank"></iframe><p>Approximate city locations, not exact property addresses. Map requires internet.</p></section><?php endif; ?>
<?php if(!$visible): ?><div class="listing-empty"><h2><?= !$properties?'No properties available right now.':'No properties match these filters.' ?></h2><p><?= !$properties?'Please check back soon for new listings.':'Try a different location, price or property type.' ?></p><a class="button" href="./property-listing">Reset filters</a></div><?php else: ?><div class="listing-grid"><?php foreach($visible as $property)listingCard($property); ?></div><?php endif; ?>
<p class="listing-count"><?= count($visible) ?> <?= count($visible)===1?'property':'properties' ?> found <span>&middot; Property showcase</span></p>
</section></div>
<?php require __DIR__.'/includes/footer.php'; ?>
