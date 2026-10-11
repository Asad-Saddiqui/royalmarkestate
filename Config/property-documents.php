<?php
declare(strict_types=1);
function propertyDocumentUploads(array $files): array {
    if(!isset($files['name']))return [];
    if(!is_array($files['name'])||count($files['name'])>5)throw new InvalidArgumentException('Choose up to 5 pricing documents.');
    $documents=[];
    foreach($files['name'] as $index=>$name){
        $error=$files['error'][$index]??UPLOAD_ERR_NO_FILE;if($error===UPLOAD_ERR_NO_FILE)continue;
        if($error!==UPLOAD_ERR_OK)throw new InvalidArgumentException('A pricing document failed to upload.');
        $tmp=$files['tmp_name'][$index]??'';$size=$files['size'][$index]??0;
        if(!is_uploaded_file($tmp)||$size>10*1024*1024||$size===0)throw new InvalidArgumentException('Each pricing document must be up to 10 MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$extension=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??'';
        if(!$extension)throw new InvalidArgumentException('Pricing documents must be PDF, JPEG, PNG or WebP.');
        if($extension!=='pdf'){$dimensions=@getimagesize($tmp);if(!$dimensions||$dimensions[0]*$dimensions[1]>40000000)throw new InvalidArgumentException('Pricing image must be valid and below 40 megapixels.');}
        $documents[]=['tmp'=>$tmp,'extension'=>$extension,'name'=>mb_substr(basename(str_replace('\\','/',(string)$name)),0,150),'type'=>$extension==='pdf'?'pdf':'image'];
    }
    return $documents;
}
