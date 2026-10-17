<?php
require __DIR__.'/includes/bootstrap.php';
require_once __DIR__.'/Config/agents-store.php';

$pageTitle='Our Agents';
$activePage='agents';
$mainClass='agents-page';
$extraCss=['./assets/agents.css'];
$extraJs=['./assets/agents.js'];
$agents=agentData(false);
$cities=[];$specialities=[];
foreach($agents as $agent){
    if(trim((string)($agent['city']??''))!=='')$cities[]=(string)$agent['city'];
    foreach(($agent['specialities']??[]) as $value){if(is_string($value)&&trim($value)!=='')$specialities[]=$value;}
}
$cities=array_values(array_unique($cities));$specialities=array_values(array_unique($specialities));
sort($cities,SORT_NATURAL|SORT_FLAG_CASE);sort($specialities,SORT_NATURAL|SORT_FLAG_CASE);
$readFilter=static fn(string $key):string=>is_string($_GET[$key]??null)?mb_substr(trim($_GET[$key]),0,150):'';
$filters=['q'=>$readFilter('q'),'city'=>$readFilter('city'),'speciality'=>$readFilter('speciality')];
$results=array_values(array_filter($agents,static function(array $agent)use($filters):bool{
    if($filters['city']!==''&&strcasecmp((string)($agent['city']??''),$filters['city'])!==0)return false;
    if($filters['speciality']!==''&&!in_array($filters['speciality'],$agent['specialities']??[],true))return false;
    if($filters['q']==='')return true;
    $haystack=implode(' ',[(string)($agent['name']??''),(string)($agent['position']??''),(string)($agent['city']??''),(string)($agent['bio']??''),implode(' ',$agent['specialities']??[]),implode(' ',$agent['languages']??[])]);
    return str_contains(mb_strtolower($haystack),mb_strtolower($filters['q']));
}));
$total=count($results);$perPage=6;$pageCount=max(1,(int)ceil($total/$perPage));
$requestedPage=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$currentPage=min($pageCount,$requestedPage?:1);
$requestedProfile=$readFilter('agent');
if(!isset($_GET['page'])&&$requestedProfile!==''&&agentValidSlug($requestedProfile)){
    foreach($results as $index=>$agent){if($agent['slug']===$requestedProfile){$currentPage=(int)floor($index/$perPage)+1;break;}}
}
$visible=array_slice($results,($currentPage-1)*$perPage,$perPage);
$filterUrl=static function(array $changes=[])use($filters):string{
    $params=array_filter(array_replace($filters,$changes),static fn($value)=>$value!==''&&$value!==null);
    return './agents'.($params?'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986):'');
};
$hasFilters=(bool)array_filter($filters,static fn($value)=>$value!=='');
$companyPayload=Database::getConnection()->query('SELECT payload FROM company_profile WHERE id=1')->fetchColumn();
$company=json_decode($companyPayload?:'{}',true);$company=is_array($company)?$company:[];
$companyEmail=trim((string)($company['email']??''));
if(!filter_var($companyEmail,FILTER_VALIDATE_EMAIL)||str_ends_with(strtolower($companyEmail),'@example.com'))$companyEmail='royalmarkestate@gmail.com';
$phoneDigits=static function($value,bool $international=true):string{
    $digits=preg_replace('/[^0-9]/','',is_scalar($value)?(string)$value:'');
    return preg_match($international?'/^[1-9][0-9]{7,14}$/':'/^[0-9]{7,15}$/',$digits)?$digits:'';
};
$companyWhatsapp=$phoneDigits($company['whatsapp']??'');
$safeUrl=static function($value,bool $asset=false):string{
    if(!is_string($value)||preg_match('/[\x00-\x20\\\\]/',$value))return '';
    if(filter_var($value,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($value,PHP_URL_SCHEME)),['http','https'],true))return $value;
    if($asset&&preg_match('~^(?:\./)?(?:assets|uploads)/[A-Za-z0-9_./%-]+$~',$value)&&!str_contains($value,'..'))return $value;
    return '';
};
$agentIcon=static function(string $name):string{
    $paths=[
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'location'=>'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'email'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'phone'=>'<path d="M8 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-4l-5-2-2 2a14 14 0 0 1-6-6l2-2-2-5Z"/>',
        'arrow'=>'<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'close'=>'<path d="m6 6 12 12M18 6 6 18"/>',
        'people'=>'<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6m3 11v-3a6 6 0 0 0-3-5"/>',
        'facebook'=>'<path d="M14 21V13h3l.5-4H14V7a2 2 0 0 1 2-2h2V2h-3a5 5 0 0 0-5 5v2H7v4h3v8"/>',
        'instagram'=>'<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
        'linkedin'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 10v7M7 7h.01M11 17v-7m0 3a3 3 0 0 1 6 0v4"/>',
    ];
    if($name==='whatsapp')return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 21 1.5-5a9 9 0 1 1 3.7 3.6L3 21Z"/><path d="m9 7-1 1c0 4 4 8 8 8l1-1-2-2-1 1a7 7 0 0 1-4-4l1-1-2-2Z"/></svg>';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
};
$contacts=static function(array $agent)use($companyEmail,$companyWhatsapp,$phoneDigits):array{
    $email=trim((string)($agent['email']??''));$directEmail=(bool)filter_var($email,FILTER_VALIDATE_EMAIL);
    $phone=$phoneDigits($agent['phone']??'',false);$whatsapp=$phoneDigits($agent['whatsapp']??'');
    $items=[];
    if($phone!=='')$items[]=['phone','tel:'.(str_starts_with(trim((string)$agent['phone']),'+')?'+':'').$phone,'Call '.$agent['name'],false];
    $items[]=['email','mailto:'.($directEmail?$email:$companyEmail),$directEmail?'Email '.$agent['name']:'Email Royalmark Estate',false];
    if($whatsapp!==''||$companyWhatsapp!=='')$items[]=['whatsapp','https://wa.me/'.($whatsapp?:$companyWhatsapp),$whatsapp!==''?'Message '.$agent['name'].' on WhatsApp':'Message Royalmark Estate on WhatsApp',true];
    return $items;
};
require __DIR__.'/includes/header.php';
?>
<header class="agents-intro">
    <div><span class="eyebrow">PEOPLE BEHIND YOUR NEXT MOVE</span><h1>Find the right person<br>for your <em>property journey.</em></h1><p>Explore agent profiles, discover their areas of focus and connect for help with buying, selling or renting a property.</p></div>
    <a class="agents-intro-link" href="./contact-us">Need a little guidance? <span>Talk to Royalmark Estate <?= $agentIcon('arrow') ?></span></a>
</header>
<form class="agents-filters" method="get" action="./agents" aria-label="Find an agent">
    <label class="agents-search" for="agent-search"><span>Search agents</span><span class="agents-input-wrap"><?= $agentIcon('search') ?><input id="agent-search" name="q" type="search" maxlength="150" value="<?= e($filters['q']) ?>" placeholder="Name, area or expertise"></span></label>
    <label for="agent-city"><span>Location</span><select id="agent-city" name="city"><option value="">All locations</option><?php foreach($cities as $city): ?><option value="<?= e($city) ?>" <?= $filters['city']===$city?'selected':'' ?>><?= e($city) ?></option><?php endforeach; ?><?php if($filters['city']!==''&&!in_array($filters['city'],$cities,true)): ?><option selected value="<?= e($filters['city']) ?>"><?= e($filters['city']) ?></option><?php endif; ?></select></label>
    <label for="agent-speciality"><span>Area of focus</span><select id="agent-speciality" name="speciality"><option value="">All specialities</option><?php foreach($specialities as $value): ?><option value="<?= e($value) ?>" <?= $filters['speciality']===$value?'selected':'' ?>><?= e($value) ?></option><?php endforeach; ?><?php if($filters['speciality']!==''&&!in_array($filters['speciality'],$specialities,true)): ?><option selected value="<?= e($filters['speciality']) ?>"><?= e($filters['speciality']) ?></option><?php endif; ?></select></label>
    <button class="button" type="submit"><?= $agentIcon('search') ?> Find an agent</button>
</form>
<section class="agents-results" aria-labelledby="agent-results-title">
    <div class="agents-results-heading"><div><h2 id="agent-results-title"><?= $hasFilters?'Your search results':'Meet our agents' ?></h2><p><?= $total ?> <?= $total===1?'profile':'profiles' ?><?= $hasFilters?' matching your search.':' to explore.' ?></p></div><?php if($hasFilters): ?><a class="agents-clear" href="./agents">Clear filters <?= $agentIcon('close') ?></a><?php endif; ?></div>
    <?php if(!$visible): ?>
    <div class="agents-empty"><?= $agentIcon('people') ?><h3><?= !$agents?'Agent profiles are coming soon.':'No agents match your search.' ?></h3><p><?= !$agents?'Let us know what you are looking for and our team will help you take the next step.':'Try another location or speciality, or search with fewer words.' ?></p><a class="button" href="<?= !$agents?'./contact-us':'./agents' ?>"><?= !$agents?'Contact our team':'View all agents' ?></a></div>
    <?php else: ?>
    <div class="agents-grid">
        <?php foreach($visible as $index=>$agent):
            $slug=(string)$agent['slug'];$name=(string)$agent['name'];$photo=$safeUrl($agent['photo']??'',true);
            $bio=(string)($agent['bio']??'');$excerpt=mb_strlen($bio)>155?rtrim(mb_substr($bio,0,155)).'…':$bio;
            $agentContacts=$contacts($agent);$sample=!empty($agent['is_sample']);
            $initials=mb_strtoupper(mb_substr($name,0,1));
        ?>
        <article class="agent-card" id="agent-<?= e($slug) ?>" style="--agent-order:<?= $index ?>">
            <div class="agent-portrait"><?php if($photo!==''): ?><img src="<?= e($photo) ?>" alt="<?= e($agent['photo_alt']??$name) ?>" width="560" height="440" loading="lazy"><?php else: ?><span class="agent-photo-placeholder" aria-label="Profile photo not provided"><?= e($initials) ?></span><?php endif; ?><?php if($sample): ?><span class="agent-sample-label">Sample profile</span><?php endif; ?></div>
            <div class="agent-card-body"><span class="agent-position"><?= e($agent['position']??'Property agent') ?></span><h3><?= e($name) ?></h3><?php if(!empty($agent['city'])): ?><p class="agent-location"><?= $agentIcon('location') ?><?= e($agent['city']) ?></p><?php endif; ?><p class="agent-excerpt"><?= e($excerpt?:'Connect with Royalmark Estate to learn more about this agent.') ?></p>
                <?php if(!empty($agent['specialities'])): ?><ul class="agent-tags" aria-label="Areas of focus"><?php foreach(array_slice($agent['specialities'],0,3) as $value): ?><li><?= e($value) ?></li><?php endforeach; ?><?php if(count($agent['specialities'])>3): ?><li class="agent-tag-more">+<?= count($agent['specialities'])-3 ?> more</li><?php endif; ?></ul><?php endif; ?>
                <div class="agent-card-footer"><a class="agent-profile-link" href="#profile-<?= e($slug) ?>" data-agent-open="profile-<?= e($slug) ?>" aria-haspopup="dialog" aria-controls="profile-<?= e($slug) ?>" aria-label="View <?= e($name) ?>'s profile">View profile <?= $agentIcon('arrow') ?></a><div class="agent-contact-icons" aria-label="Contact options for <?= e($name) ?>"><?php foreach($agentContacts as [$kind,$url,$label,$external]): ?><a class="agent-contact-<?= e($kind) ?>" <?= $kind==='email'?'data-email-kind="agent" data-email-slug="'.e($slug).'"':'' ?> href="<?= e($url) ?>" aria-label="<?= e($label) ?>" title="<?= e($label) ?>" <?= $external?'target="_blank" rel="noopener noreferrer"':'' ?>><?= $agentIcon($kind) ?></a><?php endforeach; ?></div></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <div class="agents-pagination"><p>Showing <?= ($currentPage-1)*$perPage+1 ?>&ndash;<?= min($total,$currentPage*$perPage) ?> of <?= $total ?> profiles</p><?php if($pageCount>1): ?><nav aria-label="Agent pagination"><?php if($currentPage>1): ?><a href="<?= e($filterUrl(['page'=>$currentPage-1])) ?>" rel="prev" aria-label="Previous page"><?= $agentIcon('arrow') ?></a><?php endif; ?><?php for($n=1;$n<=$pageCount;$n++): if($n!==1&&$n!==$pageCount&&abs($n-$currentPage)>2){if($n===$currentPage-3||$n===$currentPage+3)echo '<span aria-hidden="true">&hellip;</span>';continue;} ?><a href="<?= e($filterUrl(['page'=>$n])) ?>" <?= $n===$currentPage?'aria-current="page"':'' ?> aria-label="Page <?= $n ?>"><?= $n ?></a><?php endfor; ?><?php if($currentPage<$pageCount): ?><a href="<?= e($filterUrl(['page'=>$currentPage+1])) ?>" rel="next" aria-label="Next page"><?= $agentIcon('arrow') ?></a><?php endif; ?></nav><?php endif; ?></div>
    <?php endif; ?>
</section>
<aside class="agents-help"><div><?= $agentIcon('people') ?><div><h2>A conversation is a good place to start.</h2><p>Tell us your preferred location and what you need. We will help you find your next step.</p></div></div><a class="button" href="./contact-us">Contact our team <?= $agentIcon('arrow') ?></a></aside>
<?php foreach($visible as $agent):
    $slug=(string)$agent['slug'];$name=(string)$agent['name'];$photo=$safeUrl($agent['photo']??'',true);$sample=!empty($agent['is_sample']);$agentContacts=$contacts($agent);
?>
<dialog class="agent-profile" id="profile-<?= e($slug) ?>" aria-labelledby="profile-title-<?= e($slug) ?>">
    <button class="agent-profile-close" type="button" data-agent-close aria-label="Close agent profile"><?= $agentIcon('close') ?></button>
    <div class="agent-profile-layout"><div class="agent-profile-photo"><?php if($photo!==''): ?><img src="<?= e($photo) ?>" alt="<?= e($agent['photo_alt']??$name) ?>" width="420" height="600" loading="lazy"><?php else: ?><span class="agent-photo-placeholder"><?= e(mb_strtoupper(mb_substr($name,0,1))) ?></span><?php endif; ?><?php if($sample): ?><span class="agent-sample-label">Sample profile</span><?php endif; ?></div>
    <div class="agent-profile-info"><span class="agent-position"><?= e($agent['position']??'Property agent') ?></span><h2 id="profile-title-<?= e($slug) ?>"><?= e($name) ?></h2><?php if(!empty($agent['city'])): ?><p class="agent-location"><?= $agentIcon('location') ?><?= e($agent['city']) ?></p><?php endif; ?>
        <?php if((int)($agent['experience_years']??0)>0): ?><p class="agent-experience"><?= (int)$agent['experience_years'] ?> <?= (int)$agent['experience_years']===1?'year':'years' ?> of experience</p><?php endif; ?>
        <div class="agent-profile-about"><h3>About</h3><p><?= nl2br(e($agent['bio']??'Contact Royalmark Estate for more information about this agent.')) ?></p></div>
        <?php if(!empty($agent['specialities'])): ?><section class="agent-profile-section"><h3>Areas of focus</h3><ul class="agent-tags"><?php foreach($agent['specialities'] as $value): ?><li><?= e($value) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
        <?php if(!empty($agent['languages'])): ?><section class="agent-profile-section"><h3>Languages</h3><p><?= e(implode(' · ',$agent['languages'])) ?></p></section><?php endif; ?>
        <?php if($sample): ?><p class="agent-profile-note">This is an illustrative sample profile. For property enquiries, please contact Royalmark Estate.</p><?php endif; ?>
        <div class="agent-profile-contact"><a class="button" href="./contact-us">Make an enquiry <?= $agentIcon('arrow') ?></a><div class="agent-contact-icons"><?php foreach($agentContacts as [$kind,$url,$label,$external]): ?><a class="agent-contact-<?= e($kind) ?>" <?= $kind==='email'?'data-email-kind="agent" data-email-slug="'.e($slug).'"':'' ?> href="<?= e($url) ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>" <?= $external?'target="_blank" rel="noopener noreferrer"':'' ?>><?= $agentIcon($kind) ?></a><?php endforeach; ?></div></div>
        <?php $socials=[];foreach(['facebook'=>'Facebook','instagram'=>'Instagram','linkedin'=>'LinkedIn'] as $key=>$label){$url=$safeUrl($agent[$key]??'');if($url!=='')$socials[]=[$key,$label,$url];}if($socials): ?><nav class="agent-socials" aria-label="<?= e($name) ?>'s social profiles"><?php foreach($socials as [$key,$label,$url]): ?><a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($name.' on '.$label) ?>" title="<?= e($label) ?>"><?= $agentIcon($key) ?></a><?php endforeach; ?></nav><?php endif; ?>
    </div></div>
</dialog>
<?php endforeach; ?>
<?php require __DIR__.'/includes/footer.php'; ?>
