<?php
// Complete illustrative concepts. These figures and plans are samples, not live offers or approvals.
$records=[];
$propertySamples=json_decode(file_get_contents(__DIR__.'/properties.json'),true,512,JSON_THROW_ON_ERROR);
$sampleBySlug=array_column($propertySamples,null,'slug');
$specs=[
    [4500000,12000000,850,2200,'12 acres',240,12,'2028-12-31','Gujarat',23.0225,72.5714],
    [6500000,16000000,1100,2600,'8 acres',160,10,'2025-06-30','Gujarat',23.0225,72.5714],
    [9000000,24000000,1250,3200,'16 acres',320,16,'2029-06-30','Maharashtra',19.0760,72.8777],
    [12000000,30000000,1800,4200,'20 acres',80,2,'2028-12-31','Karnataka',12.9716,77.5946],
    [8000000,20000000,1600,3500,'15 acres',120,2,'2029-06-30','Delhi',28.6139,77.2090],
    [14000000,38000000,1350,3000,'6 acres',180,18,'2025-06-30','New York',40.7128,-74.0060],
];
foreach(require __DIR__.'/projects.php' as $index=>$row){
    [$title,$city,$type,$stage,$progress,$imageSlug]=$row;
    [$priceFrom,$priceTo,$areaFrom,$areaTo,$size,$units,$floors,$completion,$state,$latitude,$longitude]=$specs[$index];
    $slug=trim(strtolower(preg_replace('/[^a-z0-9]+/i','-',$title)),'-');
    $base='./assets/documents/projects/'.$slug;
    $records[]=[
        'slug'=>$slug,'title'=>$title,'subtitle'=>$type.' concept in '.$city,'city'=>$city,'type'=>$type,
        'development_status'=>$stage,'status'=>'published','progress'=>$progress,
        'price_from'=>$priceFrom,'price_to'=>$priceTo,'area_from'=>$areaFrom,'area_to'=>$areaTo,'project_size'=>$size.' (sample)',
        'total_units'=>$units,'total_floors'=>$floors,'completion_date'=>$completion,
        'address'=>'Sample development site - city centre','state'=>$state,'zip'=>'To be confirmed','latitude'=>$latitude,'longitude'=>$longitude,
        'overview'=>$title.' is a sample '.$type.' community concept in '.$city.'. It brings together thoughtfully planned homes, landscaped shared spaces and everyday facilities in one place.' . "\n\n" . 'The concept includes a selection of unit sizes, parking, security and recreation areas. Browse the sample pricing guide and indicative floor plan to explore the presentation, then speak with Royalmark Estate about current project options.' . "\n\n" . 'All prices, unit counts, areas, dates, facilities, maps and distances are illustrative examples. They do not describe a verified development or an available offer.',
        'developer_name'=>'Royalmark Concept Studio (sample)',
        'developer_description'=>'A sample developer profile for this concept presentation. Royalmark Estate is the enquiry contact; the actual developer, delivery record and project specifications should be confirmed for any real development.',
        'developer_email'=>'royalmarkestate@gmail.com','developer_phone'=>'Phone details available on request','developer_website'=>'https://royalmarkestate.com',
        'noc_status'=>'Not provided','noc_authority'=>'Authority confirmation pending','noc_reference'=>'Official reference not supplied',
        'noc_description'=>'No approval or NOC is claimed for this sample concept. The attached information sheet explains which approval details have not been supplied. Request the official project approval documents from the developer before treating any NOC information as confirmed.',
        'image'=>'./assets/properties/'.$imageSlug.'.png','gallery'=>$sampleBySlug[$imageSlug]['gallery']??[],
        'amenities'=>['Landscaped gardens (sample)','Dedicated parking (sample)','Security and controlled access (sample)','Community recreation area (sample)','Power backup (sample)','Water supply (sample)'],
        'nearest_locations'=>[
            ['name'=>'School (sample)','distance'=>'Illustrative distance: 1 km'],
            ['name'=>'Hospital (sample)','distance'=>'Illustrative distance: 2 km'],
            ['name'=>'Shopping centre (sample)','distance'=>'Illustrative distance: 1.5 km'],
            ['name'=>'Public transport (sample)','distance'=>'Illustrative distance: 500 m'],
        ],
        'videos'=>$sampleBySlug[$imageSlug]['videos']??$propertySamples[0]['videos'],
        'videos_are_inspiration'=>true,
        'noc_documents'=>[['url'=>$base.'-noc-information.pdf','name'=>$title.' - NOC information (sample)','type'=>'pdf']],
        'pricing_documents'=>[['url'=>$base.'-sample-pricing.pdf','name'=>$title.' - sample pricing guide','type'=>'pdf']],
        'floor_plans'=>[['url'=>$base.'-sample-floor-plan.pdf','name'=>$title.' - indicative floor plan','type'=>'pdf']],
        'featured'=>$index<3,'is_sample'=>true,
    ];
}
return $records;
