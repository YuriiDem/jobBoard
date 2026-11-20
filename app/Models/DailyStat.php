<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class DailyStat extends Model {
 use HasFactory;
 protected $guarded=['id'];
 public $timestamps=false; protected $casts=['top_tags'=>'array','top_roles'=>'array'];
}
