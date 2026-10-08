<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'label'];

    /**
     * Ambil nilai pengaturan berdasarkan key dengan nilai default dan caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set nilai pengaturan dan perbarui cache
     */
    public static function set(string $key, $value, ?string $label = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'label' => $label]
        );
        Cache::forget("setting_{$key}");
    }
}
