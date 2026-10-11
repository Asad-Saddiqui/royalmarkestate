<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/listings.php';
$pageTitle='Projects'; $activePage='projects'; $mainClass='listing-page projects-page';
$extraCss=['./assets/projects.css']; $extraJs=['./assets/listings.js'];
$projects=[
['Royal Gardens','Ahmedabad','Residential','Under construction',65,'sunrise-3bhk-apartment'],
['Lakeview Residences','Ahmedabad','Residential','Completed',100,'lakeview-2bhk-flat'],
['Seaside Living','Mumbai','Residential','Upcoming',0,'bandra-sea-view-apartment'],
['Parkside Villas','Bengaluru','Villas','Under construction',40,'whitefield-tech-park-flat'],
['Garden Enclave','Delhi','Villas','Upcoming',0,'chattarpur-farmhouse-plot'],
['Skyline Suites','New York','Residential','Completed',100,'manhattan-modern-loft']];
function projectQuery(string $key): string {return is_string($_GET[$key]??null)?mb_substr(trim($_GET[$key]),0,150):'';}
$q=projectQuery('q'); $status=projectQuery('status'); $type=projectQuery('type'); $city=projectQuery('city'); $sort=projectQuery('sort');
$statuses=['Upcoming','Under construction','Completed']; $types=['Residential','Villas']; $cities=array_values(array_unique(array_column($projects,1)));
if(!in_array($status,$statuses,true))$status=''; if(!in_array($type,$types,true))$type=''; if(!in_array($city,$cities,true))$city=''; if(!in_array($sort,['name','progress'],true))$sort='recommended';
$visible=array_values(array_filter($projects,fn($p)=>($q===''||mb_stripos($p[0].' '.$p[1].' '.$p[2],$q)!==false)&&($status===''||$p[3]===$status)&&($type===''||$p[2]===$type)&&($city===''||$p[1]===$city)));
if($sort==='name')usort($visible,fn($a,$b)=>strcmp($a[0],$b[0])); if($sort==='progress')usort($visible,fn($a,$b)=>$b[4]<=>$a[4]);
require __DIR__.'/includes/header.php'; ?>
<div class="projects-heading"><div><span class="eyebrow">EXPLORE THE POSSIBILITIES</span><h1>Discover our <em>projects.</em></h1><p>Explore residential communities and villa concepts in one place.</p></div><span class="projects-demo">Sample project showcase</span></div>
<div class="listing-mobile-toolbar"><button class="filter-mobile-button" type="button" aria-expanded="false" aria-controls="listing-filter"><?= listingIcon('filter') ?> Filters</button><span><?= count($visible) ?> projects</span></div>
<div class="listing-layout"><aside class="listing-filter" id="listing-filter" aria-label="Project filters"><form method="get" action="./projects">
<div class="filter-heading"><h2>Find a project</h2><?= listingIcon('building') ?></div>
<label class="listing-search"><?= listingIcon('search') ?><span class="visually-hidden">Search projects</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="Project name or location"></label>
<fieldset><legend>Project status</legend><div class="project-status-options"><?php foreach(array_merge([''],$statuses) as $value): ?><label><input type="radio" name="status" value="<?= e($value) ?>" <?= $status===$value?'checked':'' ?>><span><?= e($value?:'All projects') ?></span></label><?php endforeach; ?></div></fieldset>
<?php foreach(['type'=>['Project type',$types,$type],'city'=>['Location',$cities,$city],'sort'=>['Sort by',['recommended'=>'Recommended','name'=>'Name: A to Z','progress'=>'Progress: highest first'],$sort]] as $key=>$field): ?>
<label class="filter-field"><?= e($field[0]) ?><select name="<?= e($key) ?>"><?php if($key!=='sort'): ?><option value="">All <?= $key==='city'?'locations':'types' ?></option><?php endif; ?><?php foreach($field[1] as $value=>$label): if($key!=='sort')$value=$label; ?><option value="<?= e($value) ?>" <?= $field[2]===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<?php endforeach; ?>
<div class="filter-actions"><button class="button" type="submit">Apply filters</button><a href="./projects">Reset all</a></div><p class="listing-demo-note">Illustrative concepts only. Names, locations, photos and progress are sample content, not live project announcements.</p>
</form></aside><section class="listing-results" aria-label="Project results"><div class="project-results-heading"><h2><?= count($visible) ?> projects found</h2><span>Explore &middot; Plan &middot; Connect</span></div>
<?php if(!$visible): ?><div class="listing-empty"><h2>No projects match these filters.</h2><p>Try another status, type or location.</p><a class="button" href="./projects">Reset filters</a></div><?php else: ?><div class="listing-grid">
<?php foreach($visible as $p): $statusClass=$p[3]==='Completed'?'complete':($p[3]==='Upcoming'?'upcoming':'building'); ?>
<article class="listing-card project-card"><div class="listing-photo"><img src="./assets/properties/<?= e($p[5]) ?>.png" alt="Illustrative image for <?= e($p[0]) ?>" width="512" height="340" loading="lazy"><span class="project-status <?= $statusClass ?>"><?= e($p[3]) ?></span><span class="project-sample">Sample concept</span></div>
<div class="listing-card-content"><span class="project-category"><?= listingIcon($p[2]==='Villas'?'house':'building') ?><?= e($p[2]) ?></span><h2><?= e($p[0]) ?></h2><p class="listing-city"><?= listingIcon('location') ?><?= e($p[1]) ?></p>
<div class="project-progress"><div><span>Illustrative progress</span><strong><?= $p[4] ?>%</strong></div><progress max="100" value="<?= $p[4] ?>" aria-label="<?= e($p[0]) ?> sample progress"><?= $p[4] ?>%</progress></div>
<a class="project-enquire" href="./contact-us?interest=<?= rawurlencode('Sample project: '.$p[0]) ?>">Enquire about project <span aria-hidden="true">&rarr;</span></a></div></article>
<?php endforeach; ?></div><?php endif; ?></section></div>
<?php require __DIR__.'/includes/footer.php'; ?>