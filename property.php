<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/listings.php';
$slug=is_string($_GET['slug']??null)?$_GET['slug']:'';
$property=null;foreach(listingData() as $item){if($item['slug']===$slug){$property=$item;break;}}
$activePage='properties';$pageTitle=$property?$property['title']:'Property not found';
if(!$property)http_response_code(404);
require __DIR__.'/includes/header.php';
?>
<?php if(!$property): ?><section class="page-intro"><h1>Property not found.</h1><a class="button" href="./property-listing">View all properties</a></section><?php else: ?>
<section class="property-detail"><a class="text-link" href="./property-listing">&larr; All properties</a><div class="property-detail-heading"><div><span class="pill">Demo listing &middot; <?= e($property['purpose']) ?></span><h1><?= e($property['title']) ?></h1><p class="listing-city"><?= listingIcon('location') ?><?= e($property['city']) ?></p></div><div class="detail-price"><?= e(listingPrice($property)) ?><?php if($property['purpose']==='Rent'): ?><small>/Months</small><?php endif; ?></div></div><img class="property-detail-image" src="<?= e($property['image']) ?>" alt="<?= e($property['title']) ?>"><div class="property-detail-bottom"><div><h2>Property overview</h2><div class="detail-facts"><span><?= listingIcon('bed') ?><?= e($property['beds']) ?> bedrooms</span><span><?= listingIcon('bath') ?><?= e($property['baths']) ?> bathrooms</span><span><?= listingIcon('area') ?><?= number_format($property['area']) ?> sq.ft</span><span><?= listingIcon('house') ?><?= e($property['type']) ?></span></div><p class="muted">This sample listing uses the content supplied on the homepage. Its availability, location and price have not been verified.</p></div><a class="button" href="./contact-us?interest=<?= rawurlencode($property['title']) ?>">Enquire about this property &rarr;</a></div></section>
<?php endif; ?>
<?php require __DIR__.'/includes/footer.php'; ?>
