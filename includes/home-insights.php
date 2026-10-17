<?php
require_once dirname(__DIR__).'/Config/blog-store.php';

// Use the same publication rules as the public journal; drafts and future posts stay private.
$homeInsightPosts=blogPublicPosts();
usort($homeInsightPosts,static function(array $a,array $b):int{
    return (int)($b['featured']==='1')<=>(int)($a['featured']==='1')
        ?: strcmp($b['published_at'],$a['published_at'])
        ?: $b['id']<=>$a['id'];
});
$homeInsightPosts=array_slice($homeInsightPosts,0,3);
$homeInsightArrow='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16m-6-6 6 6-6 6"/></svg>';
$homeInsightPlay='<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m9 5 11 7-11 7z"/></svg>';
$homeInsightCover=static function(array $post):string{
    foreach(array_merge([$post['image']],$post['gallery']) as $candidate){
        $image=blogImageUrl($candidate);
        if($image==='')continue;
        if(str_starts_with($image,'./assets/')&&!is_file(dirname(__DIR__).'/'.substr($image,2)))continue;
        return $image;
    }
    if(in_array($post['type'],['video','short'],true)&&preg_match('/^[a-zA-Z0-9_-]{11}$/D',$post['youtube_id']))return 'https://i.ytimg.com/vi/'.$post['youtube_id'].'/hqdefault.jpg';
    return './assets/blog/QCyBxcZtSH2AT8GExbe3Qe3WqA.png';
};
?>
<section class="rm-native-section rm-home-insights" id="home-insights" aria-labelledby="home-insights-title">
    <header class="rm-home-insights-heading"><div class="rm-native-heading"><span>THE ROYALMARK JOURNAL</span><h2 id="home-insights-title">Insights That Help You Move Forward</h2><p>Explore top real estate trends, buying tips, and expert advice for your next property decision.</p></div><a class="rm-home-insights-all" href="./blog">View all stories <?= $homeInsightArrow ?></a></header>
    <?php if($homeInsightPosts): ?><div class="rm-home-insights-grid">
        <?php foreach($homeInsightPosts as $homeInsight):
            $insightUrl='./blog?article='.rawurlencode($homeInsight['slug']);
            $insightTitle=trim($homeInsight['title'])?:'A new perspective on property';
            $insightType=$homeInsight['type'];
            $insightFormat=['article'=>'Article','image'=>'Photo story','video'=>'Video','short'=>'YouTube Short'][$insightType];
            $insightDate=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$homeInsight['published_at'],new DateTimeZone('Asia/Karachi'));
            $insightExcerpt=trim($homeInsight['excerpt'])?:trim($homeInsight['content']);
            $insightExcerpt=preg_replace('/\s+/u',' ',$insightExcerpt)??$insightExcerpt;
            if(mb_strlen($insightExcerpt)>170)$insightExcerpt=rtrim(mb_substr($insightExcerpt,0,167)).'…';
            $insightAction=match($insightType){'video'=>'Watch video','short'=>'Watch Short',default=>'Read story'};
            $insightCategory=trim($homeInsight['category'])?:'Property insights';
            $insightCategoryUrl=trim($homeInsight['category'])!==''?'./blog?category='.rawurlencode($homeInsight['category']):'./blog';
        ?>
        <article class="rm-home-insight-card">
            <a class="rm-home-insight-image" href="<?= e($insightUrl) ?>" aria-label="<?= e($insightAction.': '.$insightTitle) ?>"><img src="<?= e($homeInsightCover($homeInsight)) ?>" alt="<?= e(trim($homeInsight['alt'])?:$insightTitle) ?>" width="640" height="420" loading="lazy" decoding="async"><span class="rm-home-insight-format"><?= in_array($insightType,['video','short'],true)?$homeInsightPlay:'' ?><?= e($insightFormat) ?></span><?php if($homeInsight['featured']==='1'): ?><span class="rm-home-insight-featured">Featured</span><?php endif; ?></a>
            <div class="rm-home-insight-body"><div class="rm-home-insight-meta"><a href="<?= e($insightCategoryUrl) ?>"><?= e($insightCategory) ?></a><time datetime="<?= e($insightDate->format('Y-m-d')) ?>"><?= e($insightDate->format('M j, Y')) ?></time></div><h3><a href="<?= e($insightUrl) ?>"><?= e($insightTitle) ?></a></h3><p><?= e($insightExcerpt?:'Explore this story for more property ideas and information.') ?></p><div class="rm-home-insight-foot"><span><?= e(trim($homeInsight['author'])?:'Royalmark Estate') ?></span><a href="<?= e($insightUrl) ?>"><?= e($insightAction) ?> <?= $homeInsightArrow ?></a></div></div>
        </article>
        <?php endforeach; ?>
    </div><?php else: ?>
    <div class="rm-home-insights-empty"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg><h3>New stories are on the way.</h3><p>Check back for property guides and updates. Our team can help with your questions in the meantime.</p><a href="./contact-us">Talk to our team <?= $homeInsightArrow ?></a></div>
    <?php endif; ?>
</section>
