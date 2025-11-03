<?php

namespace App\Rules;

use App\Models\Patient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueDniDigits implements ValidationRule
{
    public function __construct(protected ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) !== 13) return; // otra regla se encargará

        $exists = Patient::query()
            ->where('dni', $digits)
            ->when($this->ignoreId, fn($q) => $q->where('id', '!=', $this->ignoreId))
            ->exists();

        if ($exists) {
            $fail('El número de identidad ya está registrado y no puede duplicarse.');
        }
    }
}
