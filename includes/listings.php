<?php
function listingData(): array {
    $items=json_decode(file_get_contents(dirname(__DIR__).'/Config/properties.json'), true, 512, JSON_THROW_ON_ERROR);
    require_once dirname(__DIR__).'/Config/Database.php';
    foreach(Database::getConnection()->query('SELECT payload FROM properties ORDER BY id DESC') as $row)$items[]=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
    return $items;
}
function listingIcon(string $name): string {
    $paths=[
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'location'=>'<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0z"/><circle cx="12" cy="10" r="2.5"/>',
        'bed'=>'<path d="M3 18V7m18 11V9M3 14h18M3 9h6v5m0-4h12v4M3 18v3m18-3v3"/>',
        'bath'=>'<path d="M3 12h18v3a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5zm2 8v2m14-2v2M5 12V5a3 3 0 0 1 6 0M9 5h4"/>',
        'area'=>'<path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/>',
        'grid'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'map'=>'<path d="m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2zm6-2v16m6-14v16"/>',
        'filter'=>'<path d="M4 6h16M7 12h10M10 18h4"/>',
        'house'=>'<path d="m3 10 9-7 9 7v10h-6v-7H9v7H3z"/>',
        'building'=>'<path d="M4 21V3h12v18m0-12h4v12M2 21h20M8 7h4M8 11h4M8 15h4"/>',
        'layers'=>'<path d="m3 7 9-4 9 4-9 4zM3 12l9 4 9-4M3 17l9 4 9-4"/>',
        'studio'=>'<path d="M4 4h16v16H4zM4 12h8V4m0 8h8"/>',
        'crown'=>'<path d="m3 7 5 4 4-7 4 7 5-4-2 12H5zM5 21h14"/>',
        'shop'=>'<path d="M3 9 5 3h14l2 6M3 9v3h18V9M5 12v9h14v-9M9 21v-6h6v6"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['house']).'</svg>';
}
function listingPrice(array $property): string { return '$'.number_format($property['price'],2); }
function listingCard(array $property): void { ?>
<article class="listing-card">
<a class="listing-photo" href="./property.php?slug=<?= rawurlencode($property['slug']) ?>" aria-label="View <?= e($property['title']) ?>"><img src="<?= e($property['image']) ?>" alt="<?= e($property['title']) ?>" width="512" height="340" loading="lazy"><span class="listing-badge"><?= e($property['purpose']) ?></span></a>
<div class="listing-card-content"><h2><a href="./property.php?slug=<?= rawurlencode($property['slug']) ?>"><?= e($property['title']) ?></a></h2><p class="listing-city"><?= listingIcon('location') ?><?= e($property['city']) ?></p><div class="listing-facts"><span><?= listingIcon('bed') ?><?= e($property['beds']) ?> BHK</span><span><?= listingIcon('bath') ?><?= e($property['baths']) ?> Bath</span><span><?= listingIcon('area') ?>Sq.ft : <?= e($property['area']) ?></span></div><div class="listing-price"><p><?= e(listingPrice($property)) ?><?php if($property['purpose']==='Rent'): ?><small> /Months</small><?php endif; ?></p><span><?= e($property['type']) ?></span></div></div>
</article>
<?php }
