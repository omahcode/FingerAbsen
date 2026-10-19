<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentPermission extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'date',
        'notes',
        'attachment',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
