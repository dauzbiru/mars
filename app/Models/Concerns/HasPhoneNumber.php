<?php

namespace App\Models\Concerns;

trait HasPhoneNumber
{
    public static function normalizePhone($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $digits = preg_replace('/\D/', '', (string) $value);
        if ($digits === '') {
            return $value;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    public function setNoTeleponAttribute($value)
    {
        $this->attributes['no_telepon'] = static::normalizePhone($value);
    }
}
