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
<section class="pd-media pd-reveal" aria-labelledby="pd-media-title"><div class="pd-media-heading"><div><span class="pill">PROPERTY GALLERY</span><h2 id="pd-media-title">Explore this property</h2><p>Take a closer look through photos and videos.</p></div><div class="pd-media-tabs" role="tablist" aria-label="Property media"><button type="button" role="tab" id="pd-images-tab" aria-controls="pd-images-panel" aria-selected="true">Images (<?= count($mediaImages) ?>)</button><button type="button" role="tab" id="pd-videos-tab" aria-controls="pd-videos-panel" aria-selected="false" tabindex="-1">Videos (<?= count($mediaVideos) ?>)</button></div></div>
<div id="pd-images-panel" role="tabpanel" aria-labelledby="pd-images-tab" class="pd-media-grid"><?php foreach($mediaImages as $index=>$photo): ?><button type="button" class="pd-media-photo" data-gallery-index="<?= $index ?>" data-gallery-src="<?= e($photo) ?>" aria-label="Open <?= e($property['title']) ?> photo <?= $index+1 ?>"><img src="<?= e($photo) ?>" alt="<?= e($property['title']) ?> — photo <?= $index+1 ?>" loading="lazy" width="600" height="400"><span>View photo &nearr;</span></button><?php endforeach; ?></div>
<div id="pd-videos-panel" role="tabpanel" aria-labelledby="pd-videos-tab" class="pd-media-grid" hidden><?php foreach($mediaVideos as $video): ?><article class="pd-media-video <?= $video['short']?'is-short':'' ?>"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($video['id']) ?>" title="<?= e($video['title']) ?>" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe><h3><?= e($video['title']) ?></h3></article><?php endforeach; ?><?php if(!$mediaVideos): ?><div class="pd-media-empty"><h3>Videos coming soon</h3><p>No video has been added for this property yet. Explore the photos or contact Royalmark Estate for more details.</p></div><?php endif; ?></div></section>
<dialog class="pd-lightbox" id="pd-lightbox" aria-label="Property image viewer"><button type="button" class="pd-lightbox-close" aria-label="Close image viewer">&times;</button><button type="button" class="pd-lightbox-prev" aria-label="Previous image">&lsaquo;</button><figure><img alt=""><figcaption></figcaption></figure><button type="button" class="pd-lightbox-next" aria-label="Next image">&rsaquo;</button></dialog>
