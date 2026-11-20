<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class JobMetric extends Model {
 use HasFactory;
 protected $guarded=['id'];
 public $timestamps=false; public function job(){return $this->belongsTo(Job::class);}
}
