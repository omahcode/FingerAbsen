<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFingerprintTemplate extends Model
{
    protected $fillable = [
        'device_id',
        'device_user_id',
        'finger_index',
        'fingerprint_template_id',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
        'finger_index' => 'integer',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function template()
    {
        return $this->belongsTo(FingerprintTemplate::class, 'fingerprint_template_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'device_user_id', 'device_user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'device_user_id', 'device_user_id');
    }
}
