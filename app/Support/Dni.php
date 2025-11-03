<?php

namespace App\Support;

class Dni
{
    public static function format13(string $digits): string
    {
        // 13 dígitos => 0000-0000-00000
        return substr($digits, 0, 4) . '-' . substr($digits, 4, 4) . '-' . substr($digits, 8, 5);
    }

    public static function onlyDigits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}