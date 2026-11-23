<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Mengambil nilai setting dengan cache otomatis.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        }) ?? $default;
    }

    /**
     * Mengambil nilai setting sebagai integer.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    /**
     * Menyimpan nilai setting dan memperbarui cache.
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public static function set(string $key, mixed $value): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Cache::forget("setting.{$key}");
        Cache::put("setting.{$key}", (string) $value, 3600);

        return $setting;
    }
}
