<?php
require_once dirname(__DIR__).'/Config/catalog.php';

/** Catalog options include published listing values so saved properties stay searchable. */
function listingFilterOptions(array $properties): array {
    $names=static function(array $values): array {
        $unique=[];
        foreach($values as $value){
            if(!is_string($value)||trim($value)==='')continue;
            $value=trim($value);$key=mb_strtolower($value);
            $unique[$key]??=$value;
        }
        $result=array_values($unique);natcasesort($result);
        return array_values($result);
    };
    $prices=array_map(static fn($p)=>max(0,(float)($p['price']??0)),$properties);
    return [
        'types'=>$names(array_merge(catalogNames('property_type'),array_column($properties,'type'))),
        'cities'=>$names(array_merge(catalogNames('location'),array_column($properties,'city'))),
        'priceCeiling'=>$prices?ceil(max($prices)):0.0,
    ];
}

/** Parse GET values once for both the home form and the results page. */
function listingSearchState(array $params,array $options): array {
    $text=static fn(string $key,string $fallback=''): string=>is_string($params[$key]??null)?mb_substr(trim($params[$key]),0,150):$fallback;
    $canonical=static function(string $value,array $values): string {
        foreach($values as $known)if(mb_strtolower($known)===mb_strtolower($value))return $known;
        return $value;
    };
    $purpose=strtolower($text('purpose',$text('listing-purpose')));
    $purpose=match($purpose){'rent'=>'Rent','sell','buy'=>'Sell',default=>''};
    $rawTypes=$params['types']??[];
    if(is_string($rawTypes))$rawTypes=[$rawTypes];
    if(!is_array($rawTypes))$rawTypes=[];
    $types=[];
    foreach(array_slice($rawTypes,0,100) as $type){
        if(is_string($type)&&trim($type)!=='')$types[]=$canonical(mb_substr(trim($type),0,100),$options['types']);
    }
    if(!$types){
        $legacy=$text('type',$text('property-types'));
        $aliases=['independent-house'=>'House','shop-retail'=>'Shop/Retail'];
        if($legacy!=='')$types[]= $canonical($aliases[strtolower($legacy)]??$legacy,$options['types']);
    }
    $number=static function(string $key)use($params): float|string {
        $value=$params[$key]??'';
        if(!is_string($value)&&!is_int($value)&&!is_float($value))return '';
        if(trim((string)$value)===''||!is_numeric($value))return '';
        $value=(float)$value;
        return is_finite($value)?min(1.0e15,max(0,$value)):'';
    };
    $min=$number('min_price');$max=$number('max_price');
    if($min!==''&&$max!==''&&$min>$max)[$min,$max]=[$max,$min];
    $beds=$number('beds');$baths=$number('baths');
    $sort=$text('sort','recommended');
    if(!in_array($sort,['recommended','price-asc','price-desc','area-desc'],true))$sort='recommended';
    return [
        'q'=>$text('q'),'purpose'=>$purpose,'types'=>array_values(array_unique($types)),
        'featured'=>$text('featured')==='1','beds'=>$beds===''?0:min(20,(int)$beds),'baths'=>$baths===''?0:min(20,(int)$baths),
        'min'=>$min,'max'=>$max,'city'=>$canonical($text('city'),$options['cities']),'sort'=>$sort,
        'view'=>$text('view',$text('current_view'))==='map'?'map':'grid',
    ];
}

/** Search the published database records; never fall back to static sample data. */
function listingSearchResults(array $properties,array $filters): array {
    $same=static fn($a,$b)=>mb_strtolower((string)$a)===mb_strtolower((string)$b);
    $terms=preg_split('/\s+/u',$filters['q'],-1,PREG_SPLIT_NO_EMPTY)?:[];
    $matches=static function(array $property)use($filters,$same,$terms): bool {
        if(($property['status']??'published')!=='published')return false;
        $haystack=implode(' ',array_map(static fn($key)=>(string)($property[$key]??''),['title','city','type','address','state','subtitle','description']));
        foreach($terms as $term)if(mb_stripos($haystack,$term)===false)return false;
        if($filters['purpose']!==''&&!$same($property['purpose']??'',$filters['purpose']))return false;
        if($filters['city']!==''&&!$same($property['city']??'',$filters['city']))return false;
        if($filters['types']&&!array_filter($filters['types'],static fn($type)=>$same($property['type']??'',$type)))return false;
        if($filters['featured']&&empty($property['featured']))return false;
        if((int)($property['beds']??0)<$filters['beds']||(int)($property['baths']??0)<$filters['baths'])return false;
        $price=(float)($property['price']??0);
        return ($filters['min']===''||$price>=$filters['min'])&&($filters['max']===''||$price<=$filters['max']);
    };
    $items=array_values(array_filter($properties,$matches));
    if($filters['sort']!=='recommended')usort($items,static function($a,$b)use($filters){
        return match($filters['sort']){
            'price-desc'=>(float)$b['price']<=>(float)$a['price'],
            'area-desc'=>(float)$b['area']<=>(float)$a['area'],
            default=>(float)$a['price']<=>(float)$b['price'],
        };
    });
    return $items;
}
