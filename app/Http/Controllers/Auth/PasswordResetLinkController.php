<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset request view.
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', $this->successMessage($status));
        }

        throw ValidationException::withMessages([
            'email' => [$this->errorMessage($status)],
        ]);
    }

    protected function successMessage(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Te enviamos un enlace de restablecimiento. Revisa tu bandeja de entrada.',
            default => __($status),
        };
    }

    protected function errorMessage(string $status): string
    {
        return match ($status) {
            'passwords.throttled' => 'Por favor espera antes de solicitar otro enlace.',
            'passwords.user' => 'No encontramos un usuario registrado con ese correo electrónico.',
            default => __($status),
        };
    }
}
