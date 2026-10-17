<?php
require_once __DIR__.'/Database.php';
require_once __DIR__.'/catalog.php';

function projectDefaults(): array {
    return ['slug'=>'','title'=>'','subtitle'=>'','city'=>'','type'=>'','development_status'=>'Upcoming','status'=>'unpublished','progress'=>0,'price_from'=>0,'price_to'=>'','area_from'=>'','area_to'=>'','project_size'=>'','total_units'=>'','total_floors'=>'','completion_date'=>'','address'=>'','state'=>'','zip'=>'','latitude'=>'','longitude'=>'','overview'=>'','developer_name'=>'','developer_description'=>'','developer_email'=>'','developer_phone'=>'','developer_website'=>'','noc_status'=>'Not provided','noc_authority'=>'','noc_reference'=>'','noc_description'=>'','image'=>'','gallery'=>[],'amenities'=>[],'nearest_locations'=>[],'videos'=>[],'noc_documents'=>[],'pricing_documents'=>[],'floor_plans'=>[],'featured'=>false,'is_sample'=>false];
}
function projectIsFeatured(array $record): bool {
    return filter_var($record['featured']??false,FILTER_VALIDATE_BOOLEAN);
}
function projectRecord(array $row): array {
    $record=array_merge(projectDefaults(),json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR));
    $record['slug']=$row['slug'];$record['id']=(int)$row['id'];
    $record['featured']=projectIsFeatured($record);
    return $record;
}
function projectData(bool $includeUnpublished=false): array {
    $items=[];
    foreach(Database::getConnection()->query('SELECT id,slug,payload FROM projects ORDER BY id DESC') as $row){
        $record=projectRecord($row);
        if($includeUnpublished||$record['status']==='published')$items[]=$record;
    }
    return $items;
}
/** The home page only promotes projects explicitly featured and published. */
function projectFeaturedData(array $projects,int $limit=6): array {
    $items=array_values(array_filter($projects,static fn($record)=>is_array($record)&&($record['status']??'unpublished')==='published'&&projectIsFeatured($record)));
    usort($items,static fn($a,$b)=>(int)($b['id']??0)<=>(int)($a['id']??0));
    return array_slice($items,0,max(0,$limit));
}
function projectFind(string $slug,bool $includeUnpublished=false): ?array {
    $statement=Database::getConnection()->prepare('SELECT id,slug,payload FROM projects WHERE slug=?');$statement->execute([$slug]);
    $row=$statement->fetch();if(!$row)return null;
    $record=projectRecord($row);
    return $includeUnpublished||$record['status']==='published'?$record:null;
}
function projectSave(array $record,?string $existingSlug=null): void {
    $db=Database::getConnection();
    $slug=(string)($record['slug']??'');
    if(!preg_match('/^[a-z0-9][a-z0-9-]{0,199}$/',$slug)||!in_array($record['status']??'', ['published','unpublished'],true))throw new InvalidArgumentException('Invalid project.');
    $record['featured']=projectIsFeatured($record);
    unset($record['id']);$payload=json_encode($record,JSON_THROW_ON_ERROR);
    $db->beginTransaction();
    try{
        if($existingSlug!==null){
            if($slug!==$existingSlug)throw new InvalidArgumentException('Project slug cannot be changed.');
            $lock=$db->prepare('SELECT id FROM projects WHERE slug=? FOR UPDATE');$lock->execute([$existingSlug]);
            if(!$lock->fetchColumn())throw new RuntimeException('Project no longer exists.');
            $db->prepare('UPDATE projects SET payload=? WHERE slug=?')->execute([$payload,$existingSlug]);
        }else $db->prepare('INSERT INTO projects (slug,payload) VALUES (?,?)')->execute([$slug,$payload]);
        foreach(['project_type'=>'type','location'=>'city'] as $kind=>$field){
            $name=trim((string)($record[$field]??''));
            if($name!==''&&mb_strlen($name)<=100)$db->prepare('INSERT IGNORE INTO catalog_entries (kind,name) VALUES (?,?)')->execute([$kind,$name]);
        }
        $db->commit();
    }catch(Throwable $exception){if($db->inTransaction())$db->rollBack();throw $exception;}
}
function projectSetPublication(string $slug,string $status): void {
    if(!in_array($status,['published','unpublished'],true))throw new InvalidArgumentException('Invalid project visibility.');
    $db=Database::getConnection();$db->beginTransaction();
    try{
        $query=$db->prepare('SELECT payload FROM projects WHERE slug=? FOR UPDATE');$query->execute([$slug]);$payload=$query->fetchColumn();
        if($payload===false)throw new RuntimeException('Project not found.');
        $record=json_decode($payload,true,512,JSON_THROW_ON_ERROR);$record['status']=$status;
        $db->prepare('UPDATE projects SET payload=? WHERE slug=?')->execute([json_encode($record,JSON_THROW_ON_ERROR),$slug]);$db->commit();
    }catch(Throwable $exception){if($db->inTransaction())$db->rollBack();throw $exception;}
}
function projectSetFeatured(string $slug,bool $featured): void {
    if(!preg_match('/^[a-z0-9][a-z0-9-]{0,199}$/',$slug))throw new InvalidArgumentException('Invalid project.');
    $db=Database::getConnection();$db->beginTransaction();
    try{
        $query=$db->prepare('SELECT payload FROM projects WHERE slug=? FOR UPDATE');$query->execute([$slug]);$payload=$query->fetchColumn();
        if($payload===false)throw new RuntimeException('Project not found.');
        $record=json_decode($payload,true,512,JSON_THROW_ON_ERROR);$record['featured']=$featured;
        $db->prepare('UPDATE projects SET payload=? WHERE slug=?')->execute([json_encode($record,JSON_THROW_ON_ERROR),$slug]);$db->commit();
    }catch(Throwable $exception){if($db->inTransaction())$db->rollBack();throw $exception;}
}
/** Remove replaced managed uploads only after their last database reference disappears. */
function projectCleanupUploads(array $old,array $kept): void {
    $urls=static function(array $record): array {
        $found=[];
        array_walk_recursive($record,static function($value)use(&$found){if(is_string($value)&&preg_match('~^\./assets/uploads/project-(?:cover|gallery|noc|pricing|floor)-[a-f0-9]{32}\.(?:jpg|png|webp|pdf)$~',$value))$found[]=$value;});
        return array_unique($found);
    };
    $base=realpath(dirname(__DIR__).'/assets/uploads');if(!$base)return;
    foreach(array_diff($urls($old),$urls($kept)) as $url){
        try{
            $referenced=false;
            foreach(['projects','properties','blog_posts','company_profile'] as $table){
                $query=Database::getConnection()->prepare('SELECT COUNT(*) FROM '.$table.' WHERE payload LIKE ?');$query->execute(['%'.basename($url).'%']);
                if((int)$query->fetchColumn()){$referenced=true;break;}
            }
            if($referenced)continue;
            $path=realpath($base.'/'.basename($url));
            if($path&&str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/')&&is_file($path))unlink($path);
        }catch(Throwable $exception){error_log('Project upload cleanup could not complete.');}
    }
}
function projectFilterOptions(array $projects): array {
    $names=static function(array $values): array {
        $unique=[];foreach($values as $value){if(!is_string($value)||trim($value)==='')continue;$value=trim($value);$unique[mb_strtolower($value)]??=$value;}
        $values=array_values($unique);natcasesort($values);return array_values($values);
    };
    return ['types'=>$names(array_merge(catalogNames('project_type'),array_column($projects,'type'))),'cities'=>$names(array_merge(catalogNames('location'),array_column($projects,'city'))),'developers'=>$names(array_column($projects,'developer_name')),'development_statuses'=>$names(array_column($projects,'development_status'))];
}
function projectSearchState(array $params,array $options): array {
    $text=static fn($key,$fallback='')=>is_string($params[$key]??null)?mb_substr(trim($params[$key]),0,150):$fallback;
    $known=static function(string $value,array $values): string {foreach($values as $name)if(mb_strtolower($name)===mb_strtolower($value))return $name;return $value;};
    $price=static function($key)use($params){$value=$params[$key]??'';if(!is_scalar($value)||trim((string)$value)===''||!is_numeric($value))return '';return is_finite((float)$value)?min(1.0e15,max(0,(float)$value)):'';};
    $min=$price('min_price');$max=$price('max_price');if($min!==''&&$max!==''&&$min>$max)[$min,$max]=[$max,$min];
    $sort=$text('sort','recommended');if(!in_array($sort,['recommended','name','progress','price-asc','price-desc'],true))$sort='recommended';
    $noc=$text('noc_status');if(!in_array($noc,['','Not provided','Pending','Approved','Not required'],true))$noc='';
    return ['q'=>$text('q'),'city'=>$known($text('city'),$options['cities']),'type'=>$known($text('type'),$options['types']),'developer'=>$known($text('developer'),$options['developers']),'development_status'=>$known($text('development_status',$text('status')),$options['development_statuses']),'noc_status'=>$noc,'min'=>$min,'max'=>$max,'sort'=>$sort];
}
function projectSearchResults(array $projects,array $filters): array {
    $same=static fn($a,$b)=>mb_strtolower((string)$a)===mb_strtolower((string)$b);
    $terms=preg_split('/\s+/u',$filters['q'],-1,PREG_SPLIT_NO_EMPTY)?:[];
    $items=array_values(array_filter($projects,static function($p)use($filters,$same,$terms){
        if(($p['status']??'unpublished')!=='published')return false;
        $haystack=implode(' ',array_map(static fn($key)=>(string)($p[$key]??''),['title','city','type','developer_name','address','state','overview']));
        foreach($terms as $term)if(mb_stripos($haystack,$term)===false)return false;
        foreach(['city'=>'city','type'=>'type','developer'=>'developer_name','development_status'=>'development_status','noc_status'=>'noc_status'] as $key=>$field)if($filters[$key]!==''&&!$same($p[$field]??'',$filters[$key]))return false;
        if($filters['min']!==''||$filters['max']!==''){
            if((float)$p['price_from']<=0)return false;
            if($filters['min']!==''&&(float)$p['price_from']<$filters['min'])return false;
            if($filters['max']!==''&&(float)$p['price_from']>$filters['max'])return false;
        }
        return true;
    }));
    if($filters['sort']!=='recommended')usort($items,static fn($a,$b)=>match($filters['sort']){'name'=>strnatcasecmp($a['title'],$b['title']),'progress'=>(int)$b['progress']<=>(int)$a['progress'],'price-desc'=>(float)$b['price_from']<=>(float)$a['price_from'],default=>((float)$a['price_from']?:INF)<=>((float)$b['price_from']?:INF)});
    return $items;
}
