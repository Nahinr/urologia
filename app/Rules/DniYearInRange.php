<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DniYearInRange implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^\d{4}-(\d{4})-\d{5}$/', (string) $value, $m)) {
            $fail('El DNI debe tener el formato 0000-YYYY-00000.');
            return;
        }
        $year = (int) $m[1];
        $min  = 1900;
        $max  = (int) now()->year;
        if ($year < $min || $year > $max) {
            $fail("El año del medio debe estar entre $min y $max.");
        }
    }
}
