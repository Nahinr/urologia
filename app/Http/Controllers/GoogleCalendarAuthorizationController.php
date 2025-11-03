<?php

namespace App\Http\Controllers;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;

class GoogleCalendarAuthorizationController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $doctor = $this->resolveDoctor($request);
        $config = $this->credentials();

        $redirectUrl = $this->sanitizeRedirectUrl((string) $request->string('redirect'), $doctor);

        $statePayload = [
            'doctor_id' => $doctor->getKey(),
            'acting_id' => Auth::id(),
            'redirect' => $redirectUrl,
        ];

        $state = Crypt::encryptString(json_encode($statePayload, JSON_THROW_ON_ERROR));

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => route('google-calendar.callback'),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'scope' => implode(' ', $config['scopes']),
            'state' => $state,
        ];

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->string('error')->isNotEmpty()) {
            return $this->redirectWithStatus($request, 'denied');
        }

        $statePayload = $this->decodeState((string) $request->string('state'));

        if (! Auth::check() || (int) ($statePayload['acting_id'] ?? 0) !== Auth::id()) {
            abort(403);
        }

        $doctor = User::query()->findOrFail($statePayload['doctor_id'] ?? null);
        $this->authorizeDoctor($doctor);

        $code = (string) $request->string('code');

        if ($code === '') {
            return $this->redirectWithStatus($request, 'error');
        }

        $config = $this->credentials();

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => route('google-calendar.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            Log::warning('Failed to exchange Google authorization code', [
                'status' => $tokenResponse->status(),
                'response' => $tokenResponse->json(),
                'doctor_id' => $doctor->getKey(),
            ]);

            return $this->redirectWithStatus($request, 'error');
        }

        $tokenPayload = $tokenResponse->json();
        $accessToken = $tokenPayload['access_token'] ?? null;

        if (! $accessToken) {
            return $this->redirectWithStatus($request, 'error');
        }

        $refreshToken = $tokenPayload['refresh_token'] ?? $doctor->google_calendar_refresh_token;

        if (! $refreshToken) {
            Log::warning('Google did not provide a refresh token for calendar sync', [
                'doctor_id' => $doctor->getKey(),
            ]);

            return $this->redirectWithStatus($request, 'error');
        }

        $expiresAt = isset($tokenPayload['expires_in'])
            ? now()->addSeconds((int) $tokenPayload['expires_in'])
            : null;

        $email = $this->fetchGoogleEmail($accessToken);

        $doctor->forceFill([
            'google_calendar_email' => $email ?? $doctor->google_calendar_email,
            'google_calendar_access_token' => $accessToken,
            'google_calendar_refresh_token' => $refreshToken,
            'google_calendar_token_expires_at' => $expiresAt,
        ])->save();

        return $this->redirectWithStatus($request, 'connected');
    }

    protected function fetchGoogleEmail(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        if (! $response->successful()) {
            return null;
        }

        return $response->json('email');
    }

    protected function redirectWithStatus(Request $request, string $status): RedirectResponse
    {
        $redirectUrl = route('filament.admin.pages.calendario');
        $state = (string) $request->string('state');

        if ($state !== '') {
            try {
                $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($payload) && isset($payload['redirect'])) {
                    $redirectUrl = $payload['redirect'];
                }
            } catch (DecryptException|JsonException $exception) {
                // ignore and fallback to default redirect
            }
        }

        return redirect($redirectUrl)->with('google-calendar-status', $status);
    }

    protected function resolveDoctor(Request $request): User
    {
        $doctorId = $request->integer('doctor');
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if (! $doctorId || $doctorId === $user->getKey()) {
            $doctor = $user;
        } else {
            $doctor = User::query()->findOrFail($doctorId);
        }

        $this->authorizeDoctor($doctor);

        return $doctor;
    }

    protected function authorizeDoctor(User $doctor): void
    {
        $actor = Auth::user();

        if (! $actor) {
            abort(403);
        }

        if (! $doctor->hasRole('Doctor')) {
            abort(404);
        }

        if ($actor->is($doctor)) {
            return;
        }

        if (! $actor->hasRole('Administrator')) {
            abort(403);
        }
    }

    protected function sanitizeRedirectUrl(?string $redirect, User $doctor): string
    {
        if (! $redirect) {
            return UserResource::getUrl('edit', ['record' => $doctor]);
        }

        if (Str::startsWith($redirect, ['http://', 'https://'])) {
            return Str::startsWith($redirect, url('/'))
                ? $redirect
                : UserResource::getUrl('edit', ['record' => $doctor]);
        }

        $normalized = '/'.ltrim($redirect, '/');

        return url($normalized);
    }

    protected function decodeState(string $state): array
    {
        try {
            return json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            abort(400);
        }
    }

    protected function credentials(): array
    {
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');
        $scopes = config('services.google_calendar.scopes', []);

        if (! $clientId || ! $clientSecret || empty($scopes)) {
            abort(500, 'Google Calendar is not configured.');
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scopes' => is_array($scopes) ? $scopes : [$scopes],
        ];
    }
}
