<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Department extends Model {
 use HasFactory;
 protected $guarded=['id'];
 public function roles(){return $this->hasMany(Role::class);}
}
