<?php
namespace App\Services;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class TaxonomyResolver {
 public function sync():void {
 DB::transaction(function(){
 DB::statement('LOCK TABLE public_slugs IN SHARE ROW EXCLUSIVE MODE');
 DB::table('public_slugs')->whereIn('kind',['employment','tags','markets','remote'])->delete();
 $groups=['employment'=>config('jobboard.employment'),'remote'=>['remote'=>'remote']];
 foreach(['tags'=>'featured_tags','markets'=>'markets'] as $kind=>$key){
 foreach(config('jobboard.'.$key) as $name){$groups[$kind][Str::slug($name)]=$name;}
 }
 foreach($groups as $kind=>$values){foreach($values as $slug=>$value){DB::table('public_slugs')->insert(compact('kind','slug','value'));}}
 });
 }
 public const DIRECTORIES = ['roles'=>'roles', 'locations'=>'locations', 'collections'=>'markets', 'tags'=>'tags', 'departments'=>'departments', 'employment-types'=>'employment'];

 public static function path(string $kind, string $slug): string
 {
     return match ($kind) {
         'tags', 'remote' => $slug.'-jobs',
         'markets' => 'collections/'.$slug,
         'roles', 'locations' => $kind.'/'.$slug,
         default => 'jobs/'.$slug,
     };
 }

 public function terms(string $kind)
 {
     $terms = DB::table('public_slugs as s')->where('s.kind', $kind);
     if (in_array($kind, ['roles', 'locations', 'departments'], true)) {
         return $terms->join($kind.' as t', 't.id', '=', 's.owner_id')
             ->select(['s.slug', 's.kind', 't.name']);
     }

     if ($kind === 'employment') {
         return $terms->select(['s.slug', 's.kind'])->selectRaw("initcap(replace(s.value, '_', ' ')) as name");
     }

     return $terms->select(['s.slug', 's.kind', 's.value as name']);
 }
 public function find(string $slug){return DB::table('public_slugs')->where('slug',$slug)->first();}
 public function isLocation($term):bool{return in_array($term->kind,['locations','remote'],true);}
 public function constrain($q,$term):void {
 match($term->kind){
 'departments'=>$q->where('department_id',$term->owner_id),'roles'=>$q->where('role_id',$term->owner_id),
 'employment'=>$q->where('employment_type',$term->value),'remote'=>$q->where('workplace_type','remote'),
 'tags'=>$q->whereJsonContains('tags',$term->value),'markets'=>$q->whereHas('company',fn($c)=>$c->whereJsonContains('markets',$term->value)),
 'locations'=>$q->whereRaw('EXISTS (WITH RECURSIVE area(id) AS (SELECT ?::bigint UNION SELECT l.id FROM locations l JOIN area ON l.parent_id=area.id) SELECT 1 FROM area WHERE area.id=jobs.primary_location_id OR EXISTS(SELECT 1 FROM job_location jl WHERE jl.job_id=jobs.id AND jl.location_id=area.id))',[$term->owner_id]),
 default=>abort(404)};
 }
 public function eligible($q):bool{return (clone $q)->offset(config('jobboard.min_jobs_to_index')-1)->limit(1)->first(['id'])!==null;}
}
