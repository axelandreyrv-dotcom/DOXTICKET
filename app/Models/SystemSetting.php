<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'is_secret'])]
class SystemSetting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key', $key)->first();

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        if (is_string($setting->value)) {
            return json_decode($setting->value, true, flags: JSON_THROW_ON_ERROR);
        }

        return $setting->value;
    }

    public static function put(string $key, mixed $value, bool $isSecret = false): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value, JSON_THROW_ON_ERROR),
                'is_secret' => $isSecret,
            ],
        );
    }
}
