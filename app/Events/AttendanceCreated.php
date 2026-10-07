<?php

namespace App\Events;

use App\Models\AttendanceLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    public function __construct(AttendanceLog $log)
    {
        // Load relasi agar data lengkap dikirim ke frontend
        $this->log = $log->load('student.schoolClass', 'device');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('attendance'),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'AttendanceCreated';
    }
}