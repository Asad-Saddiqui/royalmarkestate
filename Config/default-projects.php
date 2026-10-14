<?php
// Migrate the existing illustrative project concepts once; all subsequent edits live in the database.
$records=[];
$propertySamples=json_decode(file_get_contents(__DIR__.'/properties.json'),true,512,JSON_THROW_ON_ERROR);
$sampleBySlug=array_column($propertySamples,null,'slug');
foreach(require __DIR__.'/projects.php' as $row){
    [$title,$city,$type,$stage,$progress,$imageSlug]=$row;
    $slug=trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$title)),'-');
    $records[]=[
        'slug'=>$slug,'title'=>$title,'subtitle'=>$type.' concept in '.$city,'city'=>$city,'type'=>$type,
        'development_status'=>$stage,'status'=>'published','progress'=>$progress,
        'price_from'=>0,'price_to'=>'','area_from'=>'','area_to'=>'','project_size'=>'','total_units'=>'','total_floors'=>'','completion_date'=>'',
        'address'=>'','state'=>'','zip'=>'','latitude'=>'','longitude'=>'',
        'overview'=>$title.' is an illustrative '.$type.' project concept in '.$city.'. Images and progress are examples. Contact Royalmark Estate to discuss current options and verify project details.',
        'developer_name'=>'Developer to be confirmed','developer_description'=>'Developer details have not been provided for this sample concept.','developer_email'=>'','developer_phone'=>'','developer_website'=>'',
        'noc_status'=>'Not provided','noc_authority'=>'','noc_reference'=>'','noc_description'=>'NOC details and supporting documents have not been provided. Please confirm the applicable approvals with the relevant authority.',
        'image'=>'./assets/properties/'.$imageSlug.'.png','gallery'=>$sampleBySlug[$imageSlug]['gallery']??[],
        'amenities'=>[],'nearest_locations'=>[],'videos'=>[],'noc_documents'=>[],'pricing_documents'=>[],'floor_plans'=>[],
        'featured'=>false,'is_sample'=>true,
    ];
}
return $records;
