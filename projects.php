<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/listings.php';
require_once __DIR__.'/Config/projects-store.php';
$pageTitle='Projects'; $activePage='projects'; $mainClass='listing-page projects-page';
$extraCss=['./assets/property-filters.css','./assets/projects.css']; $extraJs=['./assets/listings.js'];
$projects=projectData();
$options=projectFilterOptions($projects);
$filters=projectSearchState($_GET,$options);
$results=projectSearchResults($projects,$filters);
$total=count($results); $perPage=6; $pageCount=max(1,(int)ceil($total/$perPage));
$requestedPage=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$currentPage=min($pageCount,$requestedPage?:1);
$visible=array_slice($results,($currentPage-1)*$perPage,$perPage);
$query=['q'=>$filters['q'],'city'=>$filters['city'],'type'=>$filters['type'],'developer'=>$filters['developer'],'development_status'=>$filters['development_status'],'noc_status'=>$filters['noc_status'],'min_price'=>$filters['min'],'max_price'=>$filters['max'],'sort'=>$filters['sort']];
$filterUrl=static function(array $changes=[])use($query):string{
    $params=array_filter(array_replace($query,$changes),static fn($value)=>$value!==''&&$value!==null);
    return './projects'.($params?'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986):'');
};
$chips=[];
foreach(['q'=>'Search','city'=>'Location','type'=>'Type','developer'=>'Developer','development_status'=>'Development','noc_status'=>'NOC'] as $key=>$label){if($filters[$key]!=='')$chips[]=[$label.': '.$filters[$key],$filterUrl([$key=>''])];}
if($filters['min']!=='')$chips[]=['From Rs '.number_format((float)$filters['min']),$filterUrl(['min_price'=>''])];
if($filters['max']!=='')$chips[]=['Up to Rs '.number_format((float)$filters['max']),$filterUrl(['max_price'=>''])];
$nocOptions=array_values(array_unique(array_filter(array_map(static fn($p)=>(string)($p['noc_status']??'Not provided'),$projects))));
sort($nocOptions,SORT_NATURAL|SORT_FLAG_CASE);
require __DIR__.'/includes/header.php';
?>
<header class="listing-intro">
    <div><span class="eyebrow">EXPLORE YOUR NEXT OPPORTUNITY</span><h1>Discover projects with <em>possibility.</em></h1><p>Explore communities and developments. Compare locations, developers, plans and project information in one place.</p></div>
    <span class="listing-total"><?= listingIcon('building') ?><strong><?= count($projects) ?></strong> <?= count($projects)===1?'project':'projects' ?></span>
</header>
<div class="listing-mobile-toolbar"><button class="filter-mobile-button" type="button" aria-expanded="false" aria-controls="listing-filter"><?= listingIcon('filter') ?> Filters<?php if($chips): ?><span class="filter-count"><?= count($chips) ?></span><?php endif; ?></button><span><?= $total ?> <?= $total===1?'project':'projects' ?> found</span></div>
<div class="listing-layout">
<aside class="listing-filter" id="listing-filter" aria-label="Project filters">
<form method="get" action="./projects" id="listing-filter-form">
    <div class="filter-heading"><h2><?= listingIcon('filter') ?> Find your project</h2><a class="filter-reset" href="./projects">Reset</a></div>
    <label class="filter-label" for="project-keyword">Keyword</label>
    <div class="listing-search"><?= listingIcon('search') ?><input id="project-keyword" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Project, developer or area" maxlength="150"></div>
    <?php foreach(['city'=>['Location','cities','All locations'],'type'=>['Project type','types','All types'],'developer'=>['Developer','developers','All developers'],'development_status'=>['Development status','development_statuses','Any development status']] as $key=>[$label,$optionKey,$placeholder]): ?>
    <label class="filter-field" for="project-<?= e($key) ?>"><?= e($label) ?><select id="project-<?= e($key) ?>" name="<?= e($key) ?>"><option value=""><?= e($placeholder) ?></option><?php foreach($options[$optionKey] as $value): ?><option value="<?= e($value) ?>" <?= $filters[$key]===$value?'selected':'' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
    <?php endforeach; ?>
    <label class="filter-field" for="project-noc">NOC status<select id="project-noc" name="noc_status"><option value="">Any NOC status</option><?php foreach($nocOptions as $value): ?><option value="<?= e($value) ?>" <?= $filters['noc_status']===$value?'selected':'' ?>><?= e($value) ?></option><?php endforeach; ?></select></label>
    <fieldset class="filter-group"><legend>Starting price <span class="filter-unit">PKR / Rs</span></legend><div class="price-inputs"><label for="project-min-price">Minimum<input id="project-min-price" type="number" name="min_price" min="0" step="any" inputmode="decimal" value="<?= e($filters['min']) ?>" placeholder="No minimum"></label><label for="project-max-price">Maximum<input id="project-max-price" type="number" name="max_price" min="0" step="any" inputmode="decimal" value="<?= e($filters['max']) ?>" placeholder="No maximum"></label></div><p class="filter-help">Compare the advertised starting price for each project.</p></fieldset>
    <div class="filter-actions"><button class="button" type="submit"><?= listingIcon('search') ?> Search projects</button></div>
</form>
</aside>
<section class="listing-results" aria-labelledby="project-results-title">
    <div class="listing-results-toolbar"><div><h2 id="project-results-title"><?= $total ?> <?= $total===1?'project':'projects' ?> found</h2><p><?= $chips?'Selected to match your search.':'Browse our projects.' ?></p></div><div class="listing-result-controls"><label class="listing-sort" for="property-sort">Sort by<select id="property-sort" name="sort" form="listing-filter-form"><?php foreach(['recommended'=>'Recommended','name'=>'Name: A to Z','price-asc'=>'Price: low to high','price-desc'=>'Price: high to low','progress'=>'Progress: highest first'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $filters['sort']===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label></div></div>
    <?php if($chips): ?><div class="listing-active-filters" aria-label="Applied filters"><?php foreach($chips as [$label,$url]): ?><a href="<?= e($url) ?>" aria-label="<?= e('Remove filter: '.$label) ?>"><span><?= e($label) ?></span><svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8"/></svg></a><?php endforeach; ?><a class="listing-clear-filters" href="./projects">Clear all</a></div><?php endif; ?>
    <?php if(!$visible): ?><div class="listing-empty"><?= listingIcon('building') ?><h2><?= !$projects?'New projects are on the way.':'No projects match your search.' ?></h2><p><?= !$projects?'Contact our team to discuss the type of development you are looking for.':'Try a different location, a wider price range or fewer filters.' ?></p><a class="button" href="<?= !$projects?'./contact-us':'./projects' ?>"><?= !$projects?'Contact us':'Clear filters' ?></a></div><?php else: ?>
    <div class="listing-grid project-grid">
        <?php foreach($visible as $project):
            $url='./project.php?slug='.rawurlencode($project['slug']);
            $development=(string)($project['development_status']??'Upcoming');
            $statusClass=$development==='Completed'?'complete':($development==='Upcoming'?'upcoming':'building');
            $progress=max(0,min(100,(int)($project['progress']??0)));
        ?>
        <article class="listing-card project-card">
            <a class="listing-photo" href="<?= e($url) ?>" aria-label="View <?= e($project['title']) ?>"><?php if(!empty($project['image'])): ?><img src="<?= e($project['image']) ?>" alt="<?= e($project['title']) ?>" width="512" height="340" loading="lazy"><?php else: ?><span class="project-image-placeholder"><?= listingIcon('building') ?>Photo coming soon</span><?php endif; ?><span class="project-status <?= e($statusClass) ?>"><?= e($development) ?></span><?php if(!empty($project['is_sample'])): ?><span class="project-sample">Sample concept</span><?php endif; ?></a>
            <div class="listing-card-content"><span class="project-category"><?= listingIcon('building') ?><?= e($project['type']??'Project') ?></span><h2><a href="<?= e($url) ?>"><?= e($project['title']) ?></a></h2><?php listingCardRating('project', (string)$project['slug']); ?><p class="listing-city"><?= listingIcon('location') ?><?= e($project['city']??'Location to be confirmed') ?></p>
                <?php if(!empty($project['developer_name'])): ?><p class="project-developer">By <?= e($project['developer_name']) ?></p><?php endif; ?>
                <div class="project-card-price"><?php if((float)($project['price_from']??0)>0): ?><small>Starting from</small><strong>Rs <?= number_format((float)$project['price_from']) ?></strong><?php else: ?><strong>Price on request</strong><?php endif; ?></div>
                <div class="project-progress"><div><span><?= !empty($project['is_sample'])?'Illustrative progress':'Development progress' ?></span><strong><?= $progress ?>%</strong></div><progress max="100" value="<?= $progress ?>" aria-label="<?= e($project['title']) ?> development progress"><?= $progress ?>%</progress></div>
                <a class="project-enquire" href="<?= e($url) ?>">Explore project <span aria-hidden="true">&rarr;</span></a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <div class="project-pagination"><p>Showing <?= ($currentPage-1)*$perPage+1 ?>&ndash;<?= min($total,$currentPage*$perPage) ?> of <?= $total ?> projects</p><?php if($pageCount>1): ?><nav aria-label="Project pagination"><?php if($currentPage>1): ?><a href="<?= e($filterUrl(['page'=>$currentPage-1])) ?>" rel="prev" aria-label="Previous page">&larr;</a><?php endif; ?><?php for($n=1;$n<=$pageCount;$n++): if($n!==1&&$n!==$pageCount&&abs($n-$currentPage)>2){if($n===$currentPage-3||$n===$currentPage+3)echo '<span aria-hidden="true">&hellip;</span>';continue;} ?><a href="<?= e($filterUrl(['page'=>$n])) ?>" <?= $n===$currentPage?'aria-current="page"':'' ?> aria-label="Page <?= $n ?>"><?= $n ?></a><?php endfor; ?><?php if($currentPage<$pageCount): ?><a href="<?= e($filterUrl(['page'=>$currentPage+1])) ?>" rel="next" aria-label="Next page">&rarr;</a><?php endif; ?></nav><?php endif; ?></div>
    <?php endif; ?>
</section>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
