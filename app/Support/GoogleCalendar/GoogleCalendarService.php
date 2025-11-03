<?php

namespace App\Support\GoogleCalendar;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    public function syncAppointment(Appointment $appointment): void
    {
        $appointment->loadMissing('doctor', 'patient');

        $doctor = $appointment->doctor;

        if (! $this->doctorCanSync($doctor)) {
            return;
        }

        $token = $this->ensureAccessToken($doctor);

        if (! $token) {
            return;
        }

        $payload = $this->eventPayload($appointment);

        if (! $payload) {
            return;
        }

        if ($appointment->google_event_id) {
            $response = Http::withToken($token)
                ->patch(
                    $this->eventUrl($doctor->googleCalendarCalendarId(), $appointment->google_event_id, ['sendUpdates' => 'all']),
                    $payload
                );

            if ($response->status() === 404) {
                $appointment->forceFill(['google_event_id' => null])->saveQuietly();
                $this->createEvent($doctor, $appointment, $token, $payload);
                return;
            }

            if (! $response->successful()) {
                Log::warning('Unable to update Google Calendar event', [
                    'response' => $response->json(),
                    'status' => $response->status(),
                    'appointment_id' => $appointment->getKey(),
                    'doctor_id' => $doctor->getKey(),
                ]);
            }

            return;
        }

        $this->createEvent($doctor, $appointment, $token, $payload);
    }

    public function deleteAppointmentEvent(Appointment $appointment, ?int $doctorId = null, ?string $eventId = null): void
    {
        $doctor = $doctorId ? User::query()->find($doctorId) : $appointment->doctor;
        $eventId = $eventId ?? $appointment->google_event_id;

        if (! $eventId || ! $this->doctorCanSync($doctor)) {
            return;
        }

        $token = $this->ensureAccessToken($doctor);

        if (! $token) {
            return;
        }

        $response = Http::withToken($token)
            ->delete($this->eventUrl($doctor->googleCalendarCalendarId(), $eventId, ['sendUpdates' => 'all']));

        if ($response->status() === 404) {
            return;
        }

        if (! $response->successful()) {
            Log::warning('Unable to delete Google Calendar event', [
                'response' => $response->json(),
                'status' => $response->status(),
                'appointment_id' => $appointment->getKey(),
                'doctor_id' => optional($doctor)->getKey(),
            ]);
        }
    }

    protected function createEvent(User $doctor, Appointment $appointment, string $token, array $payload): void
    {
        $response = Http::withToken($token)
            ->post($this->eventUrl($doctor->googleCalendarCalendarId(), null, ['sendUpdates' => 'all']), $payload);

        if (! $response->successful()) {
            Log::warning('Unable to create Google Calendar event', [
                'response' => $response->json(),
                'status' => $response->status(),
                'appointment_id' => $appointment->getKey(),
                'doctor_id' => $doctor->getKey(),
            ]);

            return;
        }

        $eventId = Arr::get($response->json(), 'id');

        if ($eventId && $eventId !== $appointment->google_event_id) {
            $appointment->forceFill(['google_event_id' => $eventId])->saveQuietly();
        }
    }

    protected function ensureAccessToken(User $doctor): ?string
    {
        if (! $doctor->hasGoogleCalendarLink()) {
            return null;
        }

        if ($doctor->google_calendar_access_token && $doctor->google_calendar_token_expires_at && $doctor->google_calendar_token_expires_at->isFuture()) {
            return $doctor->google_calendar_access_token;
        }

        return $this->refreshAccessToken($doctor);
    }

    protected function refreshAccessToken(User $doctor): ?string
    {
        $config = $this->credentials();

        if (! $config) {
            return null;
        }

        if (! $doctor->google_calendar_refresh_token) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $doctor->google_calendar_refresh_token,
        ]);

        if (! $response->successful()) {
            Log::warning('Unable to refresh Google Calendar token', [
                'response' => $response->json(),
                'status' => $response->status(),
                'doctor_id' => $doctor->getKey(),
            ]);

            return null;
        }

        $payload = $response->json();
        $accessToken = Arr::get($payload, 'access_token');

        if (! $accessToken) {
            return null;
        }

        $expiresIn = Arr::get($payload, 'expires_in');

        $doctor->forceFill([
            'google_calendar_access_token' => $accessToken,
            'google_calendar_token_expires_at' => $expiresIn ? now()->addSeconds((int) $expiresIn) : null,
        ])->saveQuietly();

        return $accessToken;
    }

    protected function doctorCanSync(?User $doctor): bool
    {
        return $doctor instanceof User
            && $doctor->hasGoogleCalendarLink()
            && $this->credentials() !== null;
    }

    protected function credentials(): ?array
    {
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');

        if (! $clientId || ! $clientSecret) {
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
    }

    protected function eventPayload(Appointment $appointment): ?array
    {
        $start = $appointment->start_datetime?->copy();
        $end = $appointment->end_datetime?->copy();

        if (! $start || ! $end) {
            return null;
        }

        $timezone = config('app.timezone', 'UTC');
        $start = $start->setTimezone($timezone);
        $end = $end->setTimezone($timezone);

        $patientName = $appointment->patient?->full_name ?? 'Paciente sin nombre';
        $doctorName = $appointment->doctor?->display_name ?? 'Doctor';
        $patientPhone = $appointment->patient?->phone;

        $description = [
            "Doctor: {$doctorName}",
            "Paciente: {$patientName}",
        ];

        if ($patientPhone) {
            $description[] = "Teléfono paciente: {$patientPhone}";
        }

        if ($appointment->observations) {
            $description[] = 'Notas: '.$appointment->observations;
        }

        return [
            'summary' => "Consulta con {$patientName}",
            'description' => implode(PHP_EOL, $description),
            'start' => [
                'dateTime' => $start->toIso8601String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $end->toIso8601String(),
                'timeZone' => $timezone,
            ],
        ];
    }

    protected function eventUrl(string $calendarId, ?string $eventId = null, array $query = []): string
    {
        $base = 'https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($calendarId).'/events';

        if ($eventId) {
            $base .= '/'.rawurlencode($eventId);
        }

        if ($query) {
            $base .= '?'.http_build_query($query);
        }

        return $base;
    }
}
