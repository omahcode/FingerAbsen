<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentPushSubscription extends Model
{
    protected $fillable = [
        'student_id',
        'endpoint_hash',
        'endpoint',
        'p256dh',
        'auth',
        'device_name',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
