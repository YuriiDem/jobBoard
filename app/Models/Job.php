<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Job extends Model {
 use HasFactory;
 protected $guarded=['id'];
 protected $casts=['tags'=>'array','published_at'=>'datetime','expires_at'=>'datetime'];
 public function company(){return $this->belongsTo(Company::class);}
 public function department(){return $this->belongsTo(Department::class);}
 public function role(){return $this->belongsTo(Role::class);}
 public function primaryLocation(){return $this->belongsTo(Location::class,'primary_location_id');}
 public function locations(){return $this->belongsToMany(Location::class);}
 public function scopeActive($q){return $q->where('status','published')->where('published_at','<=',now())->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()));}
 public function scopeListing($q){return $q->select(['id','title','slug','company_id','primary_location_id','raw_location','salary_min','salary_max','salary_currency','published_at'])->with(['company:id,name,slug','primaryLocation:id,name']);}
 public function track(string $column):void {
 if(!in_array($column,['views_count','apply_clicks_count'],true)){throw new \InvalidArgumentException();}
 \Illuminate\Support\Facades\DB::statement("INSERT INTO job_metrics(job_id,date,$column) VALUES(?,CURRENT_DATE,1) ON CONFLICT(job_id,date) DO UPDATE SET $column=job_metrics.$column+1",[$this->id]);
 }
}
