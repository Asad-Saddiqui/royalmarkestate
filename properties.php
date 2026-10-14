<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/listings.php';
require_once __DIR__.'/includes/property-search.php';
$pageTitle='Properties'; $activePage='properties'; $mainClass='listing-page';
$extraCss=['./assets/property-filters.css']; $extraJs=['./assets/listings.js'];
$properties=listingData();
$options=listingFilterOptions($properties);
$filters=listingSearchState($_GET,$options);
$visible=listingSearchResults($properties,$filters);
$q=$filters['q']; $purpose=$filters['purpose']; $types=$filters['types'];
$featured=$filters['featured']; $beds=$filters['beds']; $baths=$filters['baths'];
$min=$filters['min']; $max=$filters['max']; $city=$filters['city'];
$sort=$filters['sort']; $view=$filters['view'];
$availableTypes=$options['types']; $cities=$options['cities'];
$typeCounts=[];
foreach($properties as $property){$type=mb_strtolower(trim((string)($property['type']??'')));$typeCounts[$type]=($typeCounts[mb_strtolower($type)]??0)+1;}
$typeIcons=['Apartment'=>'building','Villa'=>'house','Duplex'=>'layers','House'=>'house','Studio'=>'studio','Penthouse'=>'crown','Shop/Retail'=>'shop'];
$query=['q'=>$q,'purpose'=>$purpose,'types'=>$types,'featured'=>$featured?'1':'','beds'=>$beds?:'','baths'=>$baths?:'','min_price'=>$min,'max_price'=>$max,'city'=>$city,'sort'=>$sort,'view'=>$view];
$filterUrl=static function(array $changes=[])use($query):string{
    $params=array_replace($query,$changes);
    $params=array_filter($params,static fn($value)=>$value!==''&&$value!==null&&$value!==[]);
    return './property-listing'.($params?'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986):'');
};
$chips=[];
if($q!=='')$chips[]=['Search: '.$q,$filterUrl(['q'=>''])];
if($purpose!=='')$chips[]=[$purpose==='Rent'?'For rent':'For sale',$filterUrl(['purpose'=>''])];
if($city!=='')$chips[]=[$city,$filterUrl(['city'=>''])];
foreach($types as $type)$chips[]=[$type,$filterUrl(['types'=>array_values(array_diff($types,[$type]))])];
if($featured)$chips[]=['Featured',$filterUrl(['featured'=>''])];
if($beds)$chips[]=[$beds.'+ bedrooms',$filterUrl(['beds'=>''])];
if($baths)$chips[]=[$baths.'+ bathrooms',$filterUrl(['baths'=>''])];
if($min!=='')$chips[]=['From Rs '.number_format((float)$min),$filterUrl(['min_price'=>''])];
if($max!=='')$chips[]=['Up to Rs '.number_format((float)$max),$filterUrl(['max_price'=>''])];
$resultCount=count($visible); $allCount=count($properties);
require __DIR__.'/includes/header.php';
?>
<header class="listing-intro">
    <div><span class="eyebrow">FIND YOUR NEXT ADDRESS</span><h1>Find a property that <em>feels right.</em></h1><p>Explore available homes and spaces. Refine your search by location, budget and the features that matter to you.</p></div>
    <span class="listing-total"><?= listingIcon('house') ?><strong><?= $allCount ?></strong> <?= $allCount===1?'property':'properties' ?></span>
</header>
<div class="listing-mobile-toolbar"><button class="filter-mobile-button" type="button" aria-expanded="false" aria-controls="listing-filter"><?= listingIcon('filter') ?> Filters<?php if($chips): ?><span class="filter-count"><?= count($chips) ?></span><?php endif; ?></button><span><?= $resultCount ?> <?= $resultCount===1?'property':'properties' ?> found</span></div>
<div class="listing-layout">
<aside class="listing-filter" id="listing-filter" aria-label="Property filters">
<form method="get" action="./property-listing" id="listing-filter-form">
    <div class="filter-heading"><h2><?= listingIcon('filter') ?> Refine your search</h2><a class="filter-reset" href="./property-listing">Reset</a></div>
    <label class="filter-label" for="property-keyword">Keyword</label>
    <div class="listing-search"><?= listingIcon('search') ?><input id="property-keyword" type="search" name="q" value="<?= e($q) ?>" placeholder="Name, area or city" maxlength="150"></div>
    <fieldset class="filter-group"><legend>Listing purpose</legend><div class="purpose-options"><?php foreach([''=>'Any','Rent'=>'For rent','Sell'=>'For sale'] as $value=>$label): ?><label><input type="radio" name="purpose" value="<?= e($value) ?>" <?= $purpose===$value?'checked':'' ?>><span><?= e($label) ?></span></label><?php endforeach; ?></div></fieldset>
    <label class="filter-field" for="property-city">Location<select id="property-city" name="city"><option value="">All locations</option><?php foreach($cities as $location): ?><option value="<?= e($location) ?>" <?= $city===$location?'selected':'' ?>><?= e($location) ?></option><?php endforeach; ?></select></label>
    <fieldset class="filter-group"><legend>Property type</legend><div class="property-type-options"><?php foreach($availableTypes as $type): ?><label class="property-type-option"><input type="checkbox" name="types[]" value="<?= e($type) ?>" <?= in_array($type,$types,true)?'checked':'' ?>><?= listingIcon($typeIcons[$type]??'building') ?><span><?= e($type) ?></span><small aria-label="<?= (int)($typeCounts[mb_strtolower($type)]??0) ?> properties"><?= (int)($typeCounts[mb_strtolower($type)]??0) ?></small></label><?php endforeach; ?><?php if(!$availableTypes): ?><p class="filter-empty-note">Property types will appear as listings are added.</p><?php endif; ?></div></fieldset>
    <fieldset class="filter-group"><legend>Budget <span class="filter-unit">PKR / Rs</span></legend><div class="price-inputs"><label for="property-min-price">Minimum<input id="property-min-price" type="number" name="min_price" min="0" step="any" inputmode="decimal" value="<?= e($min) ?>" placeholder="No minimum"></label><label for="property-max-price">Maximum<input id="property-max-price" type="number" name="max_price" min="0" step="any" inputmode="decimal" value="<?= e($max) ?>" placeholder="No maximum"></label></div><p class="filter-help">Rental prices are per month.</p></fieldset>
    <fieldset class="filter-group"><legend>Rooms</legend><div class="room-inputs"><label class="filter-field" for="property-beds">Bedrooms<select id="property-beds" name="beds"><option value="">Any</option><?php foreach(range(1,20) as $amount): ?><option value="<?= $amount ?>" <?= $beds===$amount?'selected':'' ?>><?= $amount ?>+</option><?php endforeach; ?></select></label><label class="filter-field" for="property-baths">Bathrooms<select id="property-baths" name="baths"><option value="">Any</option><?php foreach(range(1,20) as $amount): ?><option value="<?= $amount ?>" <?= $baths===$amount?'selected':'' ?>><?= $amount ?>+</option><?php endforeach; ?></select></label></div></fieldset>
    <label class="featured-toggle"><input type="checkbox" name="featured" value="1" <?= $featured?'checked':'' ?>><span>Featured properties only</span></label>
    <input type="hidden" name="current_view" value="<?= e($view) ?>">
    <div class="filter-actions"><button class="button" type="submit"><?= listingIcon('search') ?> Search properties</button></div>
</form>
</aside>
<section class="listing-results" aria-labelledby="listing-results-title">
    <div class="listing-results-toolbar"><div><h2 id="listing-results-title"><?= $resultCount ?> <?= $resultCount===1?'property':'properties' ?> found</h2><p><?= $chips?'Selected to match your search.':'Browse all properties.' ?></p></div><div class="listing-result-controls"><label class="listing-sort" for="property-sort">Sort by<select id="property-sort" name="sort" form="listing-filter-form"><?php foreach(['recommended'=>'Recommended','price-asc'=>'Price: low to high','price-desc'=>'Price: high to low','area-desc'=>'Largest area'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $sort===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><div class="listing-view-buttons" aria-label="Results view"><a href="<?= e($filterUrl(['view'=>'grid'])) ?>" aria-label="Grid view" <?= $view==='grid'?'aria-current="true"':'' ?>><?= listingIcon('grid') ?></a><a href="<?= e($filterUrl(['view'=>'map'])) ?>" aria-label="Map view" <?= $view==='map'?'aria-current="true"':'' ?>><?= listingIcon('map') ?></a></div></div></div>
    <?php if($chips): ?><div class="listing-active-filters" aria-label="Applied filters"><?php foreach($chips as [$label,$url]): ?><a href="<?= e($url) ?>" aria-label="<?= e('Remove filter: '.$label) ?>"><span><?= e($label) ?></span><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8"/></svg></a><?php endforeach; ?><a class="listing-clear-filters" href="./property-listing">Clear all</a></div><?php endif; ?>
    <?php if($view==='map'&&$visible): $mapCities=array_values(array_unique(array_column($visible,'city'))); $mapCity=$city!==''?$city:(string)($mapCities[0]??''); ?><section class="listing-map" aria-label="Location overview"><div><h2>Explore the location</h2><label for="listing-map-city">Location<select id="listing-map-city"><?php foreach($mapCities as $location): ?><option value="<?= e($location) ?>" <?= $location===$mapCity?'selected':'' ?>><?= e($location) ?></option><?php endforeach; ?></select></label></div><iframe id="listing-map-frame" title="<?= e($mapCity) ?> city overview map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?= e('https://www.google.com/maps?q='.rawurlencode($mapCity).'&output=embed') ?>"></iframe><p>City overview. Contact us to confirm the property's exact address.</p></section><?php endif; ?>
    <?php if(!$visible): ?><div class="listing-empty"><?= listingIcon('search') ?><h2><?= !$properties?'New properties are on the way.':'No properties match your search.' ?></h2><p><?= !$properties?'Check back soon, or contact us to discuss what you are looking for.':'Try another location, a wider budget or fewer filters.' ?></p><a class="button" href="<?= !$properties?'./contact-us':'./property-listing' ?>"><?= !$properties?'Contact us':'Clear filters' ?></a></div><?php else: ?><div class="listing-grid"><?php foreach($visible as $property)listingCard($property); ?></div><?php endif; ?>
</section>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
