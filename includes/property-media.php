<?php
require_once dirname(__DIR__).'/Config/blog.php';
$mediaImages=array_values(array_unique(array_filter($gallery,'is_string')));
$mediaVideos=[];
foreach(is_array($property['videos']??null)?$property['videos']:[] as $video){
    $url=is_string($video)?$video:(is_array($video)?($video['url']??''):'');
    if(!is_string($url))continue;
    $id=blogYoutubeId($url);if(!$id)continue;
    $mediaVideos[]=['id'=>$id,'short'=>is_array($video)?($video['type']??'')==='short':str_contains($url,'/shorts/'),'title'=>is_array($video)&&is_string($video['title']??null)?$video['title']:'Property video'];
}
?>
<section class="pd-section pd-media-section" aria-labelledby="pd-images-title"><h2 id="pd-images-title">Property Images</h2>
<div id="pd-images-panel" class="pd-media-grid"><?php foreach($mediaImages as $index=>$photo): ?><button type="button" class="pd-media-photo" data-gallery-index="<?= $index ?>" data-gallery-src="<?= e($photo) ?>" aria-label="Open <?= e($property['title']) ?> photo <?= $index+1 ?>"><img src="<?= e($photo) ?>" alt="<?= e($property['title']) ?> — photo <?= $index+1 ?>" loading="lazy" width="600" height="400"><span>View photo &nearr;</span></button><?php endforeach; ?></div>
</section>
<section class="pd-section pd-media-section" aria-labelledby="pd-videos-title"><h2 id="pd-videos-title">Property Videos</h2><div id="pd-videos-panel" class="pd-media-grid"><?php foreach($mediaVideos as $video): ?><article class="pd-media-video <?= $video['short']?'is-short':'' ?>"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($video['id']) ?>" title="<?= e($video['title']) ?>" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe><h3><?= e($video['title']) ?></h3></article><?php endforeach; ?><?php if(!$mediaVideos): ?><div class="pd-media-empty"><h3>Videos coming soon</h3><p>No video has been added for this property yet. Explore the photos or contact Royalmark Estate for more details.</p></div><?php endif; ?></div></section>
<dialog class="pd-lightbox" id="pd-lightbox" aria-label="Property image viewer"><button type="button" class="pd-lightbox-close" aria-label="Close image viewer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button><button type="button" class="pd-lightbox-prev" aria-label="Previous image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg></button><figure><div class="pd-lightbox-stage"><img alt=""></div><figcaption></figcaption></figure><button type="button" class="pd-lightbox-next" aria-label="Next image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m10 6 6 6-6 6"/></svg></button></dialog>
