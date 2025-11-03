<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $status = session()->pull('google-calendar-status');

        if (! $status) {
            return;
        }

        $notification = Notification::make()->duration(5000);

        $notification = match ($status) {
            'connected' => $notification->title('Google Calendar conectado correctamente.')->success(),
            'denied'    => $notification->title('Se canceló la autorización de Google Calendar.')->warning(),
            default     => $notification->title('No se pudo vincular Google Calendar.')->danger(),
        };

        $notification->send();
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('connectGoogleCalendar')
                ->label(fn (): string => $this->record?->hasGoogleCalendarLink()
                    ? 'Actualizar Google Calendar'
                    : 'Conectar Google Calendar')
                ->icon('heroicon-m-calendar')
                ->color('primary')
                ->visible(fn (): bool => $this->record?->hasRole('Doctor') ?? false)
                ->url(fn (): string => route('google-calendar.connect', [
                    'doctor' => $this->record->getKey(),
                    'redirect' => request()->fullUrl(),
                ]))
                ->openUrlInNewTab(),

            Actions\Action::make('disconnectGoogleCalendar')
                ->label('Desconectar Google Calendar')
                ->icon('heroicon-m-link-slash')
                ->color('gray')
                ->visible(fn (): bool => ($this->record?->hasRole('Doctor') ?? false) && $this->record->hasGoogleCalendarLink())
                ->requiresConfirmation()
                ->action('disconnectGoogleCalendar'),

            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $record */
        $record = $this->record;

        // --- 1) Proteger el rol "Administrator" ---

        // El field "roles" envía IDs (por relationship)
        $incomingRoleIds = $data['roles'] ?? null;
        if (is_array($incomingRoleIds)) {

            $adminRoleId = Role::query()->where('name', 'Administrator')->value('id');

            $recordEsAdminAhora   = $record->hasRole('Administrator');
            $formIncluyeAdminRole = in_array($adminRoleId, $incomingRoleIds, true);

            // a) ¿intentan quitar "Administrator" a este usuario?
            $quitanAdministrator = $recordEsAdminAhora && ! $formIncluyeAdminRole;

            if ($quitanAdministrator) {
                // ¿es el único admin ACTIVO?
                $adminsActivos = User::role('Administrator')->where('status', 'active')->count();

                // i) Bloquea si es el único admin activo
                if ($adminsActivos <= 1) {
                    Notification::make()
                        ->title('No puedes quitar el rol "Administrator" del único Administrador activo.')
                        ->danger()->send();

                    // Reponer el rol en los datos que se guardarán
                    $incomingRoleIds[] = $adminRoleId;
                    $data['roles'] = array_values(array_unique($incomingRoleIds));
                }

                // ii) (Recomendado) Bloquea que un usuario se quite su propio rol admin
                if (Auth::id() === $record->id) {
                    Notification::make()
                        ->title('No puedes quitarte tu propio rol "Administrator".')
                        ->danger()->send();

                    $incomingRoleIds[] = $adminRoleId;
                    $data['roles'] = array_values(array_unique($incomingRoleIds));
                }
            }
        }

        // --- 2) (Opcional) Si también permites cambiar "status", evita dejar inactivo al único admin o auto-inactivarse ---
        if (($data['status'] ?? $record->status) === 'inactive') {
            if (Auth::id() === $record->id) {
                Notification::make()->title('No puedes inactivarte a ti mismo.')->danger()->send();
                $data['status'] = 'active';
            } else {
                $adminsActivos = User::role('Administrator')->where('status', 'active')->count();
                if ($record->hasRole('Administrator') && $adminsActivos <= 1) {
                    Notification::make()->title('No puedes inactivar al único Administrador activo.')->danger()->send();
                    $data['status'] = 'active';
                }
            }
        }

        return $data;
    }

    public function disconnectGoogleCalendar(): void
    {
        /** @var User $record */
        $record = $this->record;

        $record->forceFill([
            'google_calendar_email' => null,
            'google_calendar_access_token' => null,
            'google_calendar_refresh_token' => null,
            'google_calendar_token_expires_at' => null,
            'google_calendar_id' => null,
        ])->save();

        $record->refresh();

        if (method_exists($this, 'fillForm')) {
            $this->fillForm();
        }

        Notification::make()
            ->title('Se desconectó Google Calendar para este usuario.')
            ->success()
            ->send();
    }
}
