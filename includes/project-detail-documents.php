<?php foreach($documentGroups as [$field,$heading,$sectionId]):
$documents=[];
foreach(is_array($project[$field]??null)?$project[$field]:[] as $document){
    if(!is_array($document))continue;
    $url=$safeMedia($document['url']??'');if($url==='')continue;
    $documents[]=['url'=>$url,'name'=>is_string($document['name']??null)&&trim($document['name'])!==''?$document['name']:$heading,'type'=>str_ends_with(strtolower($url),'.pdf')?'pdf':'image'];
}
$isNoc=$field==='noc_documents';
?>
<?php if(!$isNoc): ?><section class="pd-section" id="<?= e($sectionId) ?>"><h2><?= e($heading) ?></h2><?php elseif($documents): ?><h3 class="project-noc-documents-title"><?= e($heading) ?></h3><?php endif; ?>
<?php if($documents): ?><div class="pd-document-list"><?php foreach($documents as $document): ?><article class="pd-document-sheet">
    <?php if($document['type']==='image'): ?><a href="<?= e($document['url']) ?>" target="_blank" rel="noopener" data-document-view data-document-type="image" data-document-title="<?= e($document['name']) ?>" aria-label="Open <?= e($document['name']) ?>"><img src="<?= e($document['url']) ?>" alt="<?= e($document['name']) ?>" loading="lazy"></a><?php else: ?><div class="pd-document-preview"><iframe src="<?= e($document['url']) ?>#toolbar=0&amp;navpanes=0&amp;view=FitH" title="Preview: <?= e($document['name']) ?>" loading="lazy" tabindex="-1"></iframe><a class="pd-document-preview-open" href="<?= e($document['url']) ?>" data-document-view data-document-type="pdf" data-document-title="<?= e($document['name']) ?>" aria-label="View <?= e($document['name']) ?>"><span>View PDF document &nearr;</span></a></div><?php endif; ?>
    <h3><?= e($document['name']) ?></h3><div><a href="<?= e($document['url']) ?>" target="_blank" rel="noopener" data-document-view data-document-type="<?= e($document['type']) ?>" data-document-title="<?= e($document['name']) ?>">View <?= $document['type']==='pdf'?'PDF':'image' ?> &nearr;</a><a href="<?= e($document['url']) ?>" download>Download</a></div>
</article><?php endforeach; ?></div><?php elseif(!$isNoc): ?><p><?= $field==='floor_plans'?'Floor plans have not been uploaded yet.':'Pricing documents have not been uploaded yet.' ?> <a href="#contact-project">Contact our team</a> for the latest information.</p><?php endif; ?>
<?php if(!$isNoc): ?></section><?php endif; ?>
<?php endforeach; ?>
