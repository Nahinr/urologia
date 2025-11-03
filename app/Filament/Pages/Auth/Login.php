<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        if (session()->has('status')) {
            Notification::make()
                ->title('¡Listo!')
                ->body(session('status'))
                ->success()
                ->send();
        }
    }

    public function authenticate(): ?LoginResponse
    {
        // Rate limiting (protege contra intentos excesivos)
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        // Credenciales desde el formulario
        $data = $this->form->getState();

        // Intentar login (usa el guard de Filament)
        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            // Usuario o contraseña incorrectos (mensaje estándar de Filament)
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        // Chequeo estándar: ¿puede acceder a ESTE panel? (por si usas canAccessPanel)
        if (($user instanceof FilamentUser) && (! $user->canAccessPanel(Filament::getCurrentPanel()))) {
            Filament::auth()->logout();
            $this->throwFailureValidationException();
        }

        // Regla de negocio: si el usuario está INACTIVO, bloquear con mensaje claro
        if ($user->status !== 'active') {
            Filament::auth()->logout();

            // Nota: usamos 'data.email' porque el formulario está namespaced como 'data[...]'
            throw ValidationException::withMessages([
                'data.email' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        // OK
        session()->regenerate();
        return app(LoginResponse::class);
    }

protected function getFormActions(): array
{
    $actions = [];

    if (is_callable([BaseLogin::class, 'getFormActions'])) {
        $actions = parent::getFormActions();
    }

    if (Route::has('password.request')) {
        $actions[] = Action::make('forgotPassword')
            ->label('¿Olvidaste tu contraseña?')
            ->url(route('password.request'))
            ->link()
            ->color('gray')
            ->extraAttributes([
                'class' => 'justify-center text-sm font-medium',
            ]);
    }

    return $actions;
}


    public function getHeading(): string
    {
        return '';
    }
}
