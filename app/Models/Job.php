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
 public function salaryLabel(): ?string
 {
     $symbol = match (strtoupper((string) $this->salary_currency)) {
         'EUR' => '€',
         'USD' => '$',
         default => null,
     };
     if ($symbol === null || ($this->salary_min === null && $this->salary_max === null)) {
         return null;
     }

     $format = static function (int $amount) use ($symbol): string {
         if ($amount < 1000) {
             return $symbol.$amount;
         }
         $thousands = rtrim(rtrim(number_format($amount / 1000, 3, '.', ''), '0'), '.');
         return $symbol.$thousands.'k';
     };

     $range = match (true) {
         $this->salary_min === null => 'Up to '.$format($this->salary_max),
         $this->salary_max === null => 'From '.$format($this->salary_min),
         (int) $this->salary_min === (int) $this->salary_max => $format($this->salary_min),
         default => $format($this->salary_min).' – '.$format($this->salary_max),
     };
     return $range.match ($this->salary_period) {
         'hour' => ' / hr',
         'month' => ' / mo',
         default => '',
     };
 }
 public function scopeActive($q){return $q->where('status','published')->where('published_at','<=',now())->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()));}
 public function scopeListing($q){return $q->select(['id','title','slug','company_id','primary_location_id','raw_location','salary_min','salary_max','salary_currency','salary_period','workplace_type','employment_type','seniority_level','tags','published_at','expires_at'])
 ->selectRaw("left(coalesce(nullif(description_plain,''), regexp_replace(description_html,'<[^>]*>',' ','g')), 240) as excerpt")->with(['company:id,name,slug,logo_url','primaryLocation:id,name,slug']);}
 public function track(string $column):void {
 if(!in_array($column,['views_count','apply_clicks_count'],true)){throw new \InvalidArgumentException();}
 \Illuminate\Support\Facades\DB::statement("INSERT INTO job_metrics(job_id,date,$column) VALUES(?,CURRENT_DATE,1) ON CONFLICT(job_id,date) DO UPDATE SET $column=job_metrics.$column+1",[$this->id]);
 }
}
