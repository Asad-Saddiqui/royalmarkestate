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

function blogDeleteImage(string $image): void {
    if(!preg_match('~^\./assets/uploads/blog-[a-f0-9]{32}\.(?:jpg|png|webp)$~',$image))return;
    $path=dirname(__DIR__).'/'.substr($image,2);
    if(is_file($path))@unlink($path);
}
