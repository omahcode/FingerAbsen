<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'school_class_id', 'major_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return empty($this->role) || $this->role === 'admin';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function isKepalaSekolah(): bool
    {
        return $this->role === 'kepala_sekolah';
    }

    public function canAccessTeachers(): bool
    {
        return $this->isAdmin() || $this->isKepalaSekolah();
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function announcements()
    {
        return $this->hasMany(TvAnnouncement::class);
    }
}
