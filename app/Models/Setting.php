<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Dapatkan nilai setting berdasarkan key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $setting = static::where('key', $key)->first();
            return $setting !== null ? $setting->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Simpan atau perbarui nilai setting.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'group' => $group,
            ]
        );
    }

    /**
     * Simpan beberapa setting sekaligus.
     */
    public static function setMany(array $data, string $group = 'general'): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value, $group);
        }
    }

    /**
     * Dapatkan seluruh setting dalam satu grup sebagai associative array.
     */
    public static function getGroup(string $group): array
    {
        try {
            return static::where('group', $group)->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
