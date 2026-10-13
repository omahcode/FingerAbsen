<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TvAnnouncement extends Model
{
    protected $fillable = [
        'user_id',
        'school_class_id',
        'title',
        'content',
        'type',
        'start_date',
        'end_date',
        'status',
        'approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Scope pengumuman yang disetujui (Approved)
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope pengumuman yang sedang aktif tayang hari ini
     */
    public function scopeActiveToday($query)
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        return $query->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Label tipe pengumuman dalam Bahasa Indonesia
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'exam' => 'Ulangan / Asesmen',
            'reminder' => 'Pengingat Tugas',
            'urgent' => 'Penting & Mendesak',
            default => 'Informasi Umum',
        };
    }

    /**
     * Badge CSS class untuk tipe pengumuman
     */
    public function getTypeBadgeAttribute(): array
    {
        return match ($this->type) {
            'exam' => ['bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/30', 'icon' => 'fa-file-signature', 'color' => '#f59e0b'],
            'reminder' => ['bg' => 'bg-purple-500/20 text-purple-300 border-purple-500/30', 'icon' => 'fa-bell', 'color' => '#a855f7'],
            'urgent' => ['bg' => 'bg-red-500/20 text-red-300 border-red-500/30', 'icon' => 'fa-exclamation-triangle', 'color' => '#ef4444'],
            default => ['bg' => 'bg-blue-500/20 text-blue-300 border-blue-500/30', 'icon' => 'fa-info-circle', 'color' => '#38bdf8'],
        };
    }
}
