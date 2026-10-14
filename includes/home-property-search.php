<section class="rm-home-property-search" id="find-property" aria-labelledby="home-property-search-title">
<div class="rm-home-search-heading"><div><span>YOUR NEXT ADDRESS</span><h3 id="home-property-search-title">Find a property that fits.</h3></div><p><?= count($homeProperties) ?> <?= count($homeProperties)===1?'property':'properties' ?> to explore</p></div>
<form action="./property-listing" method="get" class="rm-home-property-search-form">
<div class="rm-home-search-fields">
<label><span><?= listingIcon('search') ?>Keyword</span><input type="search" name="q" maxlength="150" placeholder="Property name or area" autocomplete="off"></label>
<label><span><?= listingIcon('location') ?>Location</span><select name="city"><option value="">All locations</option><?php foreach($homeFilterOptions['cities'] as $city): ?><option value="<?= e($city) ?>"><?= e($city) ?></option><?php endforeach; ?></select></label>
<label><span><?= listingIcon('building') ?>Property type</span><select name="types[]"><option value="">All types</option><?php foreach($homeFilterOptions['types'] as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select></label>
<label><span><?= listingIcon('house') ?>Purpose</span><select name="purpose"><option value="">Buy or rent</option><option value="Sell">Buy</option><option value="Rent">Rent</option></select></label>
</div>
<div class="rm-home-search-bottom"><div class="rm-home-search-budget"><label><span>Min price (Rs)</span><input type="number" name="min_price" min="0" step="any" placeholder="No minimum" inputmode="decimal"></label><span class="rm-price-divider" aria-hidden="true">&ndash;</span><label><span>Max price (Rs)</span><input type="number" name="max_price" min="0" step="any" placeholder="No maximum" inputmode="decimal"></label></div><div class="rm-home-search-actions"><button type="reset" class="rm-search-reset">Clear</button><button type="submit" class="rm-search-submit"><?= listingIcon('search') ?>Search properties <span aria-hidden="true">&rarr;</span></button></div></div>
</form></section>
