<?php
namespace App\Services;
class Seo {
 public static function url(string $path):string{return rtrim(config('app.url'),'/').'/'.ltrim($path,'/');}
 public static function meta(string $category,string $country='',bool $eligible=true):array{
 $r=request(); $params=$r->query(); $pagination=count($params)===1 && isset($params['page']) && is_string($params['page']) && ctype_digit($params['page']) && (int)$params['page']>1;
 $parts=[':category'=>$category,':country'=>$country ? 'in '.$country : '',':niche'=>config('jobboard.niche_name'),':site_name'=>config('jobboard.site_name')];
 return ['title'=>trim(strtr(config('jobboard.seo.title'),$parts)),'description'=>trim(strtr(config('jobboard.seo.description'),$parts)),
 'robots'=>$eligible && (!$params || $pagination)?'index, follow':'noindex, follow',
 'canonical'=>self::url($r->path()).($pagination?'?page='.(int)$params['page']:'')];
 }
}
