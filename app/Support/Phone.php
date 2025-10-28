<?php

namespace App\Support;

use Filament\Forms\Get;

class Phone
{
    public static function countryOptions(): array
    {
        return collect(config('phone.countries'))
            ->mapWithKeys(fn($v, $k) => [$k => $v['name']])
            ->all();
    }

    public static function mask(Get $get, string $countryField = 'phone_country'): string
    {
        $iso = $get($countryField) ?: config('phone.default_iso');
        return config("phone.countries.$iso.mask", '9999-9999');
    }

    // 👇 ahora acepta los nombres de campos de país y nacional
    public static function composeE164(
        Get $get,
        string $countryField = 'phone_country',
        string $nationalField = 'phone_national'
    ): ?string {
        $iso = $get($countryField) ?: config('phone.default_iso');
        $cc  = config("phone.countries.$iso.cc");
        $digits = preg_replace('/\D/', '', (string) $get($nationalField));
        return $digits ? ('+' . $cc . $digits) : null;
    }

    public static function splitE164(?string $e164): array
    {
        if (!$e164 || !str_starts_with($e164, '+')) {
            return ['iso' => config('phone.default_iso'), 'national' => null];
        }
        $raw = ltrim($e164, '+');
        foreach (config('phone.countries') as $iso => $meta) {
            $cc = $meta['cc'];
            if (str_starts_with($raw, $cc)) {
                return ['iso' => $iso, 'national' => substr($raw, strlen($cc))];
            }
        }
        return ['iso' => config('phone.default_iso'), 'national' => $raw];
    }

    public static function validateLength(string $iso, string $digits): bool
    {
        $min = (int) config("phone.countries.$iso.min", 0);
        $max = (int) config("phone.countries.$iso.max", PHP_INT_MAX);
        $len = strlen($digits);
        return $len >= $min && $len <= $max;
    }
}
