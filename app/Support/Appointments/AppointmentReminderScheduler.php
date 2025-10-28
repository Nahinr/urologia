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
            Log::warning('Appointment reminder scheduling skipped due to missing start time.', [
                'appointment_id' => $appointment->id,
            ]);
            return;
        }

        $start = $start->copy();
        $now = now($start->getTimezone());

        if ($start->lessThanOrEqualTo($now)) {
            return;
        }

        foreach (SendAppointmentReminderSms::offsets() as $reminder => $minutes) {
            $sendAt = $start->copy()->subMinutes($minutes);

            if ($sendAt->lessThan($now)) {
                // If the reminder time already passed but the appointment is still upcoming, send immediately.
                if ($start->greaterThan($now)) {
                    SendAppointmentReminderSms::dispatch($appointment->id, $reminder);
                }
                continue;
            }

            SendAppointmentReminderSms::dispatch($appointment->id, $reminder)->delay($sendAt);
        }
    }
}
