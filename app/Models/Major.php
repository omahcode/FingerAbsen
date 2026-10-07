<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Major extends Model {
    protected $fillable = ['name'];
    public function schoolClasses() { return $this->hasMany(SchoolClass::class); }
    public function students() { return $this->hasManyThrough(Student::class, SchoolClass::class); }
}