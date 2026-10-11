<?php
$pricingDocuments=[];
foreach(is_array($property['pricing_documents']??null)?$property['pricing_documents']:[] as $document){
    if(!is_array($document)||!is_string($document['url']??null))continue;
    $url=$document['url'];
    if(!preg_match('~^\./assets/(?:uploads|documents)/[a-zA-Z0-9_-]+\.(pdf|png|jpg|jpeg|webp)$~i',$url,$match))continue;
    $document['type']=strtolower($match[1])==='pdf'?'pdf':'image';$pricingDocuments[]=$document;
}
$nearby=$property['nearest_locations']??[];
if(is_string($nearby)){$nearby=array_map(static function($line){$parts=explode('|',$line,2);return ['name'=>trim($parts[0]),'distance'=>trim($parts[1]??'')];},preg_split('/\R/',trim($nearby)));}
if(!is_array($nearby))$nearby=[];
?>
<section class="pd-section" aria-labelledby="pd-pricing-title"><h2 id="pd-pricing-title">Pricing Documents</h2>
<?php if($pricingDocuments): ?><div class="pd-document-grid"><?php foreach($pricingDocuments as $document): $name=is_string($document['name']??null)?$document['name']:'Pricing document'; ?><article class="pd-document-card"><?php if($document['type']==='image'): ?><a href="<?= e($document['url']) ?>" target="_blank" rel="noopener" aria-label="Open <?= e($name) ?>"><img src="<?= e($document['url']) ?>" alt="<?= e($name) ?>" loading="lazy"></a><?php else: ?><div class="pd-pdf-preview"><span aria-hidden="true">PDF</span><p>Pricing document</p></div><?php endif; ?><h3><?= e($name) ?></h3><div><a href="<?= e($document['url']) ?>" target="_blank" rel="noopener">View <?= $document['type']==='pdf'?'PDF':'image' ?> &nearr;</a><a href="<?= e($document['url']) ?>" download>Download</a></div></article><?php endforeach; ?></div><?php else: ?><p>Pricing documents have not been added yet. <a href="#contact-property">Request pricing information</a> from Royalmark Estate.</p><?php endif; ?></section>
<section class="pd-section" aria-labelledby="pd-nearest-title"><h2 id="pd-nearest-title">Nearest Locations</h2><div class="pd-nearby-grid"><?php $nearbyCount=0;foreach($nearby as $place): if(!is_array($place)||!is_string($place['name']??null)||trim($place['name'])==='')continue;$nearbyCount++; ?><article class="pd-nearby-place"><?= listingIcon('location') ?><div><h3><?= e($place['name']) ?></h3><?php if(is_string($place['distance']??null)&&$place['distance']!==''): ?><p><?= e($place['distance']) ?></p><?php endif; ?></div></article><?php endforeach; ?></div><?php if(!$nearbyCount): ?><p>Nearby places and distances have not been provided yet.</p><?php endif; ?></section>
