<?php
$mapCities=require dirname(__DIR__).'/Config/pakistan-locations.php';
$mapListings=[];
foreach(['property'=>($homeProperties??[]),'project'=>($homeProjects??[])] as $kind=>$records) {
    foreach($records as $record) {
        if(($record['status']??'published')!=='published')continue;
        $city=trim((string)($record['city']??''));$centre=$mapCities[$city]??null;
        $lat=$record['latitude']??null;$lng=$record['longitude']??null;
        $valid=is_numeric($lat)&&is_numeric($lng)&&is_finite((float)$lat)&&is_finite((float)$lng)&&abs((float)$lat)<=90&&abs((float)$lng)<=180;
        if(!$valid&&!$centre)continue;
        $mapPrice=(float)($record[$kind==='project'?'price_from':'price']??0);
        $mapImage=is_string($record['image']??null)?$record['image']:'';
        if(!preg_match('~^\./assets/[a-zA-Z0-9_./-]+\.(?:jpg|jpeg|png|webp)$~i',$mapImage)&&!preg_match('~^https://~i',$mapImage))$mapImage='';
        $mapListings[]=['kind'=>$kind,'slug'=>$record['slug'],'title'=>$record['title'],'city'=>$city,'image'=>$mapImage,'price'=>$mapPrice>0?($kind==='project'?'From ':'').'PKR '.number_format($mapPrice).($kind==='property'&&($record['purpose']??'')==='Rent'?' / month':''):'Price on request','lat'=>$valid?(float)$lat:$centre['latitude'],'lng'=>$valid?(float)$lng:$centre['longitude'],'approximate'=>!$valid||($record['location_precision']??'')==='city','url'=>'./'.$kind.'.php?slug='.rawurlencode($record['slug'])];
    }
}
?>
<section class="rm-native-section rm-home-map" id="home-map" aria-labelledby="home-map-title">
    <div class="rm-native-heading"><span>EXPLORE BY LOCATION</span><h2 id="home-map-title">Find your place on the map</h2><p>Explore properties and projects across Pakistan. Select a location to see the listings there.</p></div>
    <div class="rm-map-toolbar" role="group" aria-label="Filter map listings"><button type="button" data-map-filter="all" aria-pressed="true">All listings</button><button type="button" data-map-filter="property" aria-pressed="false">Properties</button><button type="button" data-map-filter="project" aria-pressed="false">Projects</button><label>City <select data-map-city><option value="">All cities</option><?php foreach(array_unique(array_column($mapListings,'city')) as $city): ?><option value="<?= e($city) ?>"><?= e($city) ?></option><?php endforeach; ?></select></label><button type="button" data-map-reset>Reset map</button></div>
    <p class="rm-map-status" data-map-status role="status"><?= count($mapListings) ?> listings on the map</p>
    <div class="rm-map-layout"><div id="rm-listings-map" class="rm-map-canvas" aria-label="Map of properties and projects in Pakistan"></div><div class="rm-map-results" aria-label="Listings on the map"><?php foreach($mapListings as $item): ?><article data-map-result="<?= e($item['kind'].':'.$item['slug']) ?>"><span class="rm-map-kind <?= e($item['kind']) ?>"><?= $item['kind']==='project'?'Project':'Property' ?></span><h3><a href="<?= e($item['url']) ?>"><?= e($item['title']) ?></a></h3><p><?= e($item['city']) ?><?= $item['approximate']?' · Approximate city location':'' ?></p><button type="button" data-map-locate="<?= e($item['kind'].':'.$item['slug']) ?>">Show on map</button></article><?php endforeach; ?></div></div>
    <p class="rm-map-note">Pins sharing a location are grouped. Sample listings use approximate city locations; exact site addresses should be confirmed with our team.</p>
    <script type="application/json" id="rm-map-data"><?= json_encode($mapListings,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
</section>
