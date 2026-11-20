<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Company extends Model {
 use HasFactory;
 protected $guarded=['id'];
 protected $casts=['markets'=>'array','investors'=>'array','social_links'=>'array']; public function jobs(){return $this->hasMany(Job::class);}
}
