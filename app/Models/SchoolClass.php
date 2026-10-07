<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchoolClass extends Model {
    protected $fillable = ['major_id', 'name'];
    public function major() { return $this->belongsTo(Major::class); }
    public function students() { return $this->hasMany(Student::class); }
}