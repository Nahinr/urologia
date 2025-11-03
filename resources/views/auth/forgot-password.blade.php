<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-xl rounded-2xl p-8 space-y-6">
            <div class="text-center space-y-4">
                <img src="{{ asset('images/logo-app.png') }}" alt="{{ config('app.name') }}" class="mx-auto h-16">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold text-gray-900">¿Olvidaste tu contraseña?</h1>
                    <p class="text-sm text-gray-600">
                        Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                    </p>
                </div>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full inline-flex justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-500"
                >
                    Enviar enlace
                </button>
            </form>

            <div class="text-center">
                <a href="{{ route('filament.admin.auth.login') }}" class="text-sm font-medium text-amber-600 transition hover:text-amber-700">
                    Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</body>
</html>
