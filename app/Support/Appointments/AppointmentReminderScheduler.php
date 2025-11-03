<?php

namespace App\Support\Appointments;

use App\Jobs\SendAppointmentReminderSms;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentReminderScheduler
{
    public function scheduleReminders(Appointment $appointment): void
    {
        $start = $appointment->start_datetime;

        if (!$start instanceof Carbon) {
            Log::warning('Reminder scheduling skipped: missing start.', [
                'appointment_id' => $appointment->id,
            ]);
            return;
        }

        $tz    = config('app.timezone', 'UTC');
        $now   = now($tz);
        $start = $start->clone()->timezone($tz);

        if ($start->lessThanOrEqualTo($now)) {
            // Cita en pasado → no programar
            return;
        }

        // ÚNICO recordatorio: 24 h antes
        $sendAt = $start->clone()->subDay();

        if ($sendAt->lessThan($now)) {
            // Si el target ya pasó, pero el retraso es razonable (≤ 90 min), envía ahora
            if ($now->diffInMinutes($sendAt) <= 90) {
                SendAppointmentReminderSms::dispatch(
                    $appointment->id,
                    SendAppointmentReminderSms::REMINDER_24_HOURS
                );
            }
            // Si el retraso es mayor, no enviar (caducado)
            return;
        }

        // Programar exacto por delay
        SendAppointmentReminderSms::dispatch(
            $appointment->id,
            SendAppointmentReminderSms::REMINDER_24_HOURS
        )->delay($sendAt);
    }
}
