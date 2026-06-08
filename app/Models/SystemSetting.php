<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

#[Fillable(['key', 'value', 'is_secret'])]
class SystemSetting extends Model
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::query()->where('key', $key)->first();

        if ($setting === null || $setting->value === null) {
            return $default;
        }

        return $setting->decodedValue();
    }

    public function decodedValue(): mixed
    {
        $value = $this->value;

        if ($this->is_secret && is_string($value)) {
            $encryptedValue = $this->decodeJson($value);

            try {
                $value = Crypt::decryptString(is_string($encryptedValue) ? $encryptedValue : $value);
            } catch (DecryptException) {
                $value = $this->value;
            }
        }

        return is_string($value)
            ? json_decode($value, true, flags: JSON_THROW_ON_ERROR)
            : $value;
    }

    public static function put(string $key, mixed $value, bool $isSecret = false): void
    {
        $encoded = json_encode($value, JSON_THROW_ON_ERROR);

        self::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $isSecret ? json_encode(Crypt::encryptString($encoded), JSON_THROW_ON_ERROR) : $encoded,
                'is_secret' => $isSecret,
            ],
        );
    }

    private function decodeJson(string $value): mixed
    {
        try {
            return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $value;
        }
    }
}
