<?php

namespace App\Filament\Forms\Fields;

use App\Support\Dni;
use App\Models\Patient;
use Filament\Forms\Get;
use App\Rules\DniYearInRange;
use App\Rules\UniqueDniDigits;
use Filament\Forms\Components\TextInput;

class DniField
{
    public static function make(string $name = 'dni'): TextInput
    {
        return TextInput::make($name)
            ->label('DNI')
            ->mask('9999-9999-99999')
            ->placeholder('____-____-_____')
            ->nullable() // ← permite vacío desde el form
            ->formatStateUsing(function ($state) {
                if (!$state) return null;
                $digits = Dni::onlyDigits($state);
                return strlen($digits) === 13 ? Dni::format13($digits) : $state;
            })
            ->dehydrateStateUsing(fn($state) => $state ? Dni::onlyDigits($state) : null) // ← NULL si vacío

            // Reglas SOLO si hay valor:
            ->rule(function (Get $get) {
                return $get('dni') ? 'regex:/^\d{4}-\d{4}-\d{5}$/' : null;
            })
            ->rule(function (Get $get) {
                return $get('dni') ? new DniYearInRange() : null;
            })
            ->rule(function (Get $get, ?Patient $record) {
                $value = $get('dni');
                if (!$value) {
                    return null; // sin DNI => sin unique
                }

                return new UniqueDniDigits($record?->getKey());
            });
    }
}
