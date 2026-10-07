<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name', 'ip_address', 'port', 'status', 'last_seen_at'];
}
