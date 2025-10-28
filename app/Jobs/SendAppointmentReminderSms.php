<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Support\Sms\EasySendSmsClient;
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

    public const REMINDER_TWO_DAYS = 'two_days';
    public const REMINDER_FIVE_HOURS = 'five_hours';

    private const OFFSETS = [
        self::REMINDER_TWO_DAYS => 60 * 24 * 2,
        self::REMINDER_FIVE_HOURS => 60 * 5,
    ];

    public function __construct(
        public readonly int $appointmentId,
        public readonly string $reminderKey,
    ) {
    }

    public static function offsets(): array
    {
        return self::OFFSETS;
    }

    public function handle(EasySendSmsClient $sms): void
    {
        if (!isset(self::OFFSETS[$this->reminderKey])) {
            Log::warning('Unknown reminder key for SMS job.', ['reminder' => $this->reminderKey]);
            return;
        }

        $appointment = Appointment::query()
            ->with(['patient', 'doctor'])
            ->find($this->appointmentId);

        if (!$appointment) {
            Log::warning('Appointment reminder skipped because appointment was not found.', ['appointment_id' => $this->appointmentId]);
            return;
        }

        $start = $appointment->start_datetime;
        if (!$start instanceof Carbon) {
            return;
        }

        $now = now($start->getTimezone());

        if ($start->lessThanOrEqualTo($now)) {
            Log::info('Skipping appointment reminder because appointment is in the past.', [
                'appointment_id' => $this->appointmentId,
                'reminder' => $this->reminderKey,
            ]);
            return;
        }

        $offsetMinutes = self::OFFSETS[$this->reminderKey];
        $targetTime = $start->copy()->subMinutes($offsetMinutes);

        $minutesDifference = $targetTime->diffInMinutes($now, false);

        if ($minutesDifference > 10) {
            Log::info('Skipping appointment reminder because appointment appears to have been rescheduled.', [
                'appointment_id' => $this->appointmentId,
                'reminder' => $this->reminderKey,
            ]);
            return;
        }

        if ($minutesDifference > 0) {
            // Queue fired a bit early; push it closer to the intended window.
            $this->release($now->diffInSeconds($targetTime));
            return;
        }

        if ($minutesDifference < -90) {
            Log::info('Skipping outdated appointment reminder.', [
                'appointment_id' => $this->appointmentId,
                'reminder' => $this->reminderKey,
            ]);
            return;
        }

        $patient = $appointment->patient;
        $phone = $patient?->primary_phone;

        if (!$phone) {
            Log::warning('Appointment reminder skipped due to missing phone number.', [
                'appointment_id' => $this->appointmentId,
            ]);
            return;
        }

        $clinicName = (string) config('clinic.name', config('app.name'));
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? '')) ?: 'Paciente';
        $patientName = Str::ascii($patientName);
        $clinicName = Str::ascii($clinicName);

        $dateText = $start->format('d/m/Y');
        $timeText = $start->format('g:i a');

        $message = sprintf(
            'Hola %s, le recordamos su cita en %s el %s a las %s. Si necesita reprogramar, contacte a la clinica.',
            $patientName,
            $clinicName,
            $dateText,
            $timeText,
        );

        try {
            $sms->sendMessage($phone, $message);
        } catch (\Throwable $e) {
            Log::error('Failed to send appointment reminder SMS.', [
                'appointment_id' => $this->appointmentId,
                'reminder' => $this->reminderKey,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
