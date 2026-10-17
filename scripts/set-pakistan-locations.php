<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/Config/Database.php';
$locations = require dirname(__DIR__).'/Config/pakistan-locations.php';
$assignments = [
    'sunrise-3bhk-apartment'=>'Lahore','lakeview-2bhk-flat'=>'Islamabad',
    'bandra-sea-view-apartment'=>'Karachi','whitefield-tech-park-flat'=>'Rawalpindi',
    'chattarpur-farmhouse-plot'=>'Lahore','manhattan-modern-loft'=>'Islamabad',
    'rainey-street-condo'=>'Rawalpindi','coconut-grove-waterfront'=>'Karachi','fitzroy-terrace-house'=>'Lahore',
    'royal-gardens'=>'Lahore','lakeview-residences'=>'Islamabad','seaside-living'=>'Karachi',
    'parkside-villas'=>'Rawalpindi','garden-enclave'=>'Lahore','skyline-suites'=>'Islamabad',
];
$rename = ['bandra-sea-view-apartment'=>'Karachi Sea View Apartment','whitefield-tech-park-flat'=>'Rawalpindi Park View Apartment','chattarpur-farmhouse-plot'=>'Lahore Farmhouse Plot','manhattan-modern-loft'=>'Islamabad Modern Loft','rainey-street-condo'=>'Rawalpindi City Apartment','coconut-grove-waterfront'=>'Karachi Waterfront Residence','fitzroy-terrace-house'=>'Lahore Terrace House'];
$convert = static function(array $record, string $slug) use ($locations,$assignments,$rename): array {
    $city=$assignments[$slug];$oldCity=(string)($record['city']??'');$oldState=(string)($record['state']??'');$oldTitle=(string)($record['title']??'');
    $record['city']=$city;$record['state']=$locations[$city]['state'];$record['country']='Pakistan';
    $record['latitude']=$locations[$city]['latitude'];$record['longitude']=$locations[$city]['longitude'];
    $record['location_precision']='city';$record['address']='Sample location - '.$city.', Pakistan';$record['zip']='';
    if(isset($rename[$slug]))$record['title']=$rename[$slug];
    foreach(['description','overview','subtitle'] as $field)if(is_string($record[$field]??null)) {
        foreach([$oldTitle=>($record['title']??$oldTitle),$oldCity=>$city,$oldState=>$record['state']] as $from=>$to)if($from!=='')$record[$field]=str_replace($from,$to,$record[$field]);
    }
    return $record;
};
$db=Database::getConnection();$db->beginTransaction();
try {
    foreach(['properties','projects'] as $table) {
        $update=$db->prepare('UPDATE '.$table.' SET payload=? WHERE slug=?');$count=0;
        foreach($db->query('SELECT slug,payload FROM '.$table.' ORDER BY id') as $row) {
            $record=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);
            if(!isset($assignments[$row['slug']]))throw new RuntimeException('Location assignment needed for '.$row['slug']);
            $record=$convert($record,$row['slug']);
            $update->execute([json_encode($record,JSON_THROW_ON_ERROR),$row['slug']]);$count++;
        }
        echo $table.': '.$count.' locations updated.'.PHP_EOL;
    }
    $insert=$db->prepare("INSERT IGNORE INTO catalog_entries (kind,name) VALUES ('location',?)");
    foreach(array_keys($locations) as $city)$insert->execute([$city]);
    $db->commit();
} catch(Throwable $error) {if($db->inTransaction())$db->rollBack();throw $error;}
// Keep fresh-install property fixtures consistent with the database.
$file=dirname(__DIR__).'/Config/properties.json';$records=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
foreach($records as &$record)if(isset($assignments[$record['slug']]))$record=$convert($record,$record['slug']);unset($record);
file_put_contents($file,json_encode($records,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL);
