<?php
declare(strict_types=1);

function blogYoutubeId(string $value): ?string {
    $url=parse_url($value);
    if(!$url||!in_array(strtolower($url['scheme']??''),['http','https'],true))return null;
    $host=strtolower($url['host']??'');$path=$url['path']??'';$id='';
    if(in_array($host,['youtu.be','www.youtu.be'],true))$id=trim($path,'/');
    elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com'],true)){
        if($path==='/watch'){parse_str($url['query']??'',$query);$id=is_string($query['v']??null)?$query['v']:'';}
        elseif(preg_match('~^/(?:shorts|embed|live)/([^/]+)/?$~',$path,$match))$id=$match[1];
    }
    return preg_match('/^[a-zA-Z0-9_-]{11}$/',$id)?$id:null;
}

/** Only accepted media paths are rendered in blog content. */
function blogImageUrl($value): string {
    if(!is_string($value)||strlen($value)>2048)return '';
    if(preg_match('~^\./assets/[a-zA-Z0-9_./-]+\.(?:jpg|jpeg|png|webp)$~i',$value)&&!str_contains($value,'..'))return $value;
    $url=parse_url($value);
    return $url&&($url['scheme']??'')==='https'&&!empty($url['host'])&&empty($url['user'])&&empty($url['pass'])? $value:'';
}

/** Called after a committed change. Keep every upload still referenced anywhere. */
function blogCleanupUploads(array $old,array $kept=[]): void {
    require_once __DIR__.'/Database.php';
    $collect=static function(array $record): array {
        $urls=[];array_walk_recursive($record,static function($value)use(&$urls){
            if(is_string($value)&&preg_match('~^\./assets/uploads/blog-[a-f0-9]{32}\.(?:jpg|png|webp)$~',$value))$urls[]=$value;
        });return array_unique($urls);
    };
    $base=realpath(dirname(__DIR__).'/assets/uploads');if(!$base)return;
    foreach(array_diff($collect($old),$collect($kept)) as $url){
        try{
            $referenced=false;
            foreach(['blog_posts','properties','projects','company_profile','agents'] as $table){
                $query=Database::getConnection()->prepare('SELECT COUNT(*) FROM '.$table.' WHERE payload LIKE ?');
                $query->execute(['%'.basename($url).'%']);if((int)$query->fetchColumn()){$referenced=true;break;}
            }
            $booking=Database::getConnection()->prepare('SELECT COUNT(*) FROM bookings WHERE listing_image LIKE ?');$booking->execute(['%'.basename($url).'%']);if((int)$booking->fetchColumn())$referenced=true;
            if($referenced)continue;
            $path=realpath($base.'/'.basename($url));
            if($path&&str_starts_with(str_replace('\\','/',$path),str_replace('\\','/',$base).'/')&&is_file($path))@unlink($path);
        }catch(Throwable $exception){error_log('Blog upload cleanup could not complete.');}
    }
}
function blogDeleteImage(string $image): void {blogCleanupUploads(['image'=>$image]);}
