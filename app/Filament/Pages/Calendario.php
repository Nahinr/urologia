<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Calendario extends Page
{
    protected static ?string $navigationGroup = 'Agenda';
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Calendario';
    protected static ?string $title           = 'Calendario';

    protected static string $view = 'filament.pages.calendario';

    public ?int $doctorId = null;

    public function mount(): void
    {

        $this->handleGoogleCalendarStatus();
        $this->promptGoogleCalendarConnection();
    }

    protected function doctorOptions(): Collection
    {
        $query = User::query()
            ->select(['id', 'name', 'last_name', 'email'])
            ->whereHas('roles', fn ($q) => $q->where('name', 'Doctor'))
            ->orderBy('name')
            ->orderBy('last_name');

        if (! $this->canViewAllDoctors()) {
            $currentDoctorId = Auth::id();
            if ($currentDoctorId) {
                $query->whereKey($currentDoctorId);
            }
        }

        return $query
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->display_name]);
    }

    protected function getViewData(): array
    {
        $options = $this->doctorOptions();

        if (! $this->canViewAllDoctors()) {
            $this->doctorId = Auth::id();
        } elseif ($this->doctorId !== null && $options->doesntContain(fn ($_, $key) => (int) $key === (int) $this->doctorId)) {
            // Si el doctor seleccionado ya no existe en las opciones, volver a "Todos"
            $this->doctorId = null;
        }

        return [
            'doctorOptions' => $options,
            'canViewAllDoctors' => $this->canViewAllDoctors(),
            'currentDoctorName' => Auth::user()?->display_name,
        ];
    }

    public function updatedDoctorId($value): void
    {
        if (! $this->canViewAllDoctors()) {
            $this->doctorId = Auth::id();

            return;
        }

        $this->doctorId = ($value !== null && $value !== '') ? (int) $value : null;
    }

    protected function canViewAllDoctors(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrator') || $user->hasRole('Receptionist');
    }

    protected function handleGoogleCalendarStatus(): void
    {
        $status = session()->pull('google-calendar-status');

        if (! $status) {
            return;
        }

        $notification = Notification::make()->duration(5000);

        $notification = match ($status) {
            'connected' => $notification
                ->title('Google Calendar conectado correctamente.')
                ->success(),
            'denied' => $notification
                ->title('Se canceló la autorización de Google Calendar.')
                ->warning(),
            default => $notification
                ->title('No se pudo vincular Google Calendar.')
                ->danger(),
        };

        $notification->send();
    }

    protected function promptGoogleCalendarConnection(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('Doctor') || $user->hasGoogleCalendarLink()) {
            return;
        }

        Notification::make('connect-google-calendar')
            ->title('Conecta tu Google Calendar')
            ->body('Vincula tu cuenta de Google para recibir actualizaciones de las citas que se creen, editen o eliminen en esta agenda.')
            ->info()
            ->persistent()
            ->actions([
                NotificationAction::make('connect')
                    ->label('Conectar ahora')
                    ->url(route('google-calendar.connect', [
                        'doctor' => $user->getKey(),
                        'redirect' => request()->fullUrl(),
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->send();
    }
}
