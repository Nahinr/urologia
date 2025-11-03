<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Support\Sms\TwilioSmsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendAppointmentReminderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ÚNICO recordatorio
    public const REMINDER_24_HOURS = '24_hours';

    // Minutos de antelación (24 h)
    private const OFFSET_MINUTES = 60 * 24;

    // Reintentos ante errores reales del proveedor (no por release)
    public $tries = 5;

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $reminderKey = self::REMINDER_24_HOURS,
    ) {
    }

    // Limita la ventana de reintentos a 30 min después del target
    public function retryUntil(): \DateTimeInterface
    {
        $start = optional(Appointment::find($this->appointmentId))->start_datetime;

        return $start instanceof Carbon
            ? $start->clone()->subMinutes(self::OFFSET_MINUTES)->addMinutes(30)
            : now()->addMinutes(30);
    }

    public function handle(TwilioSmsClient $sms): void
    {
        if ($this->reminderKey !== self::REMINDER_24_HOURS) {
            Log::warning('Unknown reminder key for SMS job.', ['reminder' => $this->reminderKey]);
            return;
        }

        $appointment = Appointment::query()
            ->with(['patient', 'doctor'])
            ->find($this->appointmentId);

        if (!$appointment) {
            Log::warning('Appointment reminder skipped: appointment not found.', ['appointment_id' => $this->appointmentId]);
            return;
        }

        $start = $appointment->start_datetime;
        if (!$start instanceof Carbon) {
            Log::warning('Appointment reminder skipped: start_datetime missing.', ['appointment_id' => $appointment->id]);
            return;
        }

        $tz   = config('app.timezone', 'UTC');
        $now  = now($tz);
        $start = $start->clone()->timezone($tz);

        // Cita en pasado → no enviar
        if ($start->lessThanOrEqualTo($now)) {
            Log::info('Skipping reminder: appointment is in the past.', ['appointment_id' => $appointment->id]);
            return;
        }

        $target  = $start->clone()->subMinutes(self::OFFSET_MINUTES);
        $diffMin = $target->diffInMinutes($now, false); // negativo = faltan minutos para el target

        // Demasiado temprano (>60 min antes del target): NO reencolar → el job correcto llegará por delay.
        if ($diffMin < -60) {
            Log::info('Too early for reminder, skipping (no requeue).', [
                'appointment_id'    => $appointment->id,
                'minutes_to_target' => abs($diffMin),
            ]);
            return;
        }

        // Un poco temprano (entre 0 y 60 min): reencolar SOLO UNA vez con pequeño margen
        if ($diffMin < 0) {
            if (method_exists($this, 'attempts') && $this->attempts() >= 2) {
                Log::info('Early pick multiple times, deferring to delayed job.', [
                    'appointment_id' => $appointment->id,
                    'attempts'       => $this->attempts(),
                ]);
                return;
            }

            $seconds = max(30, $now->diffInSeconds($target)) + 10; // +10s colchón
            $this->release($seconds);
            return;
        }

        // Demasiado tarde (> 90 min después del target): ya no tiene sentido
        if ($diffMin > 90) {
            Log::info('Skipping outdated reminder (too late after target).', [
                'appointment_id'      => $appointment->id,
                'minutes_after_target'=> $diffMin,
            ]);
            return;
        }

        // ——— Enviar SMS ———
        $patient = $appointment->patient;
        $phone   = $patient?->primary_phone;

        if (!$phone) {
            Log::warning('Reminder skipped: missing patient phone.', ['appointment_id' => $appointment->id]);
            return;
        }

        $clinicName  = Str::ascii((string) config('clinic.name', config('app.name')));
        $patientName = Str::ascii(trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Paciente');

        $dateText = $start->format('d/m/Y');
        $timeText = $start->format('g:i a');

        $message = sprintf(
            'Hola %s, le recordamos su cita en %s el %s a las %s. Si necesita reprogramar, contacte a la clinica.',
            $patientName, $clinicName, $dateText, $timeText
        );

        try {
            // Pequeño jitter para ráfagas
            usleep(random_int(50_000, 200_000));

            $sms->sendMessage($phone, $message);
            Log::info('Reminder SMS sent.', ['appointment_id' => $appointment->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send reminder SMS.', [
                'appointment_id' => $appointment->id,
                'error'          => $e->getMessage(),
            ]);
            throw $e; // para que el worker aplique reintentos/backoff si el proveedor falla
        }
    }
}
