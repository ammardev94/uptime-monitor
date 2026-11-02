<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'alert_emails' => '',
        'timeout_seconds' => '20',
        'max_pages_per_site' => '200',
        'concurrency' => '10',
        'ssl_warning_days' => '14',
        'treat_403_as_up' => '0',
    ];

    public static function get(string $key, $default = null)
    {
        $value = static::query()->find($key)?->value;

        return $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function put(string $key, $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
