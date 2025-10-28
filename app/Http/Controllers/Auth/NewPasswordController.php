<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $payload = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'password_confirmation' => $request->string('password_confirmation')->toString(),
            'token' => $validated['token'],
        ];

        $status = Password::reset(
            $payload,
            function ($user) use ($validated): void {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('filament.admin.auth.login')
                ->with('status', 'Tu contraseña se restableció correctamente. Ya puedes iniciar sesión.');
        }

        throw ValidationException::withMessages([
            'email' => [$this->errorMessage($status)],
        ]);
    }

    protected function errorMessage(string $status): string
    {
        return match ($status) {
            'passwords.token' => 'El enlace de restablecimiento expiró. Solicita uno nuevo.',
            'passwords.user' => 'No encontramos un usuario registrado con ese correo electrónico.',
            default => __($status),
        };
    }
}
