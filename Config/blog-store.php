<?php
declare(strict_types=1);
require_once __DIR__.'/Database.php';
require_once __DIR__.'/blog.php';

function blogDefaults(): array {
    return ['title'=>'','slug'=>'','excerpt'=>'','content'=>'','category'=>'','tags'=>'','author'=>'Royalmark Estate','type'=>'article','youtube'=>'','youtube_id'=>'','image'=>'','gallery'=>[],'alt'=>'','status'=>'draft','published_at'=>'','featured'=>'0','seo_title'=>'','seo_description'=>'','is_sample'=>false];
}
function blogRecord(array $row): array {
    $payload=json_decode((string)($row['payload']??''),true);if(!is_array($payload))$payload=[];
    $record=array_merge(blogDefaults(),$payload);
    foreach(blogDefaults() as $key=>$default)if(is_string($default)&&!is_string($record[$key]))$record[$key]=$default;
    $record['id']=(int)($row['id']??0);$record['slug']=(string)($row['slug']??$record['slug']);
    $record['type']=in_array($record['type'],['article','image','video','short'],true)?$record['type']:'article';
    $record['image']=blogImageUrl($record['image']);
    $record['gallery']=array_slice(array_values(array_unique(array_filter(array_map('blogImageUrl',is_array($record['gallery'])?$record['gallery']:[])))),0,12);
    $record['youtube_id']=blogYoutubeId($record['youtube'])??'';
    $record['featured']=in_array($record['featured'],['1',1,true],true)?'1':'0';
    return $record;
}
function blogIsPublic(array $record): bool {
    if(($record['status']??'')!=='published')return false;
    $date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',(string)($record['published_at']??''),new DateTimeZone('Asia/Karachi'));
    return $date&&$date->format('Y-m-d\TH:i')===$record['published_at']&&$date<=new DateTimeImmutable('now',new DateTimeZone('Asia/Karachi'));
}
function blogPublicPosts(): array {
    $posts=[];
    foreach(Database::getConnection()->query('SELECT id,slug,payload FROM blog_posts ORDER BY id DESC') as $row){$record=blogRecord($row);if(blogIsPublic($record))$posts[]=$record;}
    usort($posts,static fn($a,$b)=>strcmp($b['published_at'],$a['published_at'])?:$b['id']<=>$a['id']);return $posts;
}
function blogPublicFind(string $slug): ?array {
    $q=Database::getConnection()->prepare('SELECT id,slug,payload FROM blog_posts WHERE slug=?');$q->execute([$slug]);$row=$q->fetch();if(!$row)return null;
    $record=blogRecord($row);return blogIsPublic($record)?$record:null;
}
