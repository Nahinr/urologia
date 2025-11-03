<?php

namespace App\Filament\Forms\Fields;

use App\Support\Phone;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;


class PhoneField
{
    public static function schema(
        string $countryField = 'phone_country',
        string $nationalField = 'phone_national',
        string $e164Field = 'phone',
        int $countrySpan = 2,
        int $numberSpan = 4
    ): array {
        return [
            Select::make($countryField)
                ->label('País')
                ->options(Phone::countryOptions())
                ->default(config('phone.default_iso'))
                ->live()
                ->dehydrated(false)
                ->columnSpan($countrySpan)
                ->afterStateHydrated(function (Set $set, $state, $record) use ($countryField, $nationalField, $e164Field) {
                    if ($record?->{$e164Field}) {
                        ['iso' => $iso, 'national' => $national] = Phone::splitE164($record->{$e164Field});
                        $set($countryField, $iso);
                        $set($nationalField, $national);
                    }
                }),

            TextInput::make($nationalField)
                ->label('Teléfono')
                ->placeholder(fn (Get $get) => Phone::mask($get, $countryField))
                ->mask(fn (Get $get) => Phone::mask($get, $countryField))
                ->live()
                ->dehydrated(false)
                ->rule(function (Get $get) use ($countryField) {
                    return function (string $attribute, $value, \Closure $fail) use ($get, $countryField) {
                        $iso = $get($countryField) ?: config('phone.default_iso');
                        $digits = preg_replace('/\D/', '', (string) $value);
                        if ($digits && !\App\Support\Phone::validateLength($iso, $digits)) {
                            $min = config("phone.countries.$iso.min");
                            $fail("El número debe tener $min dígitos para el país seleccionado.");
                        }
                    };
                })
                ->columnSpan($numberSpan),

            Hidden::make($e164Field)
                ->dehydrateStateUsing(fn (Get $get) => Phone::composeE164($get, $countryField, $nationalField)),
        ];
    }
}
