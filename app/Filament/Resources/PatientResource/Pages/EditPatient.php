<?php

namespace App\Filament\Resources\PatientResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PatientResource;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterValidate(): void
    {
        $data     = $this->form->getState();
        $birth    = $data['birth_date'] ?? null;
        $isMinor  = $birth ? \Carbon\Carbon::parse($birth)->age < 18 : false;
        $hasTgl   = (bool) ($data['has_guardian'] ?? false);
        $contacts = $data['contacts'] ?? [];

        if (($isMinor || $hasTgl) && count($contacts) !== 1) {
            $this->addError('contacts', 'Debe haber exactamente 1 contacto.');
        }
        if (!($isMinor || $hasTgl) && count($contacts) > 0) {
            $this->addError('contacts', 'Para adultos sin encargado no debe registrar contactos.');
        }
    }
        protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

}
