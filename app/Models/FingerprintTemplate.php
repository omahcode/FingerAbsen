<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FingerprintTemplate extends Model
{
    protected $fillable = ['student_id', 'device_user_id', 'finger_index', 'size', 'valid', 'template_data'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
