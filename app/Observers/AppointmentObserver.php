<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Support\Appointments\AppointmentReminderScheduler;
use App\Support\GoogleCalendar\GoogleCalendarService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppointmentObserver
{
    public function __construct(
        private readonly GoogleCalendarService $calendar,
        private readonly AppointmentReminderScheduler $reminders,
    ) {
    }

    public function created(Appointment $appointment): void
    {
        $this->safelySyncAppointment($appointment);
        $this->reminders->scheduleReminders($appointment);
    }

    public function updated(Appointment $appointment): void
    {
        $shouldSync = $appointment->wasChanged([
            'start_datetime',
            'end_datetime',
            'doctor_id',
            'patient_id',
            'observations',
        ]);

        $shouldReschedule = $appointment->wasChanged([
            'start_datetime',
            'patient_id',
        ]);

        if ($appointment->wasChanged('doctor_id')) {
            $this->safelyDeleteAppointmentEvent(
                $appointment,
                $appointment->getOriginal('doctor_id'),
                $appointment->getOriginal('google_event_id'),
                'doctor-changed'
            );

            $appointment->forceFill(['google_event_id' => null])->saveQuietly();
        }

        $appointmentRefreshed = false;

        if ($shouldSync) {
            $appointment->refresh();
            $appointmentRefreshed = true;
            $this->safelySyncAppointment($appointment, 'updated');
        }

        if ($shouldReschedule) {
            if (! $appointmentRefreshed) {
                $appointment->refresh();
                $appointmentRefreshed = true;
            }

            $this->reminders->scheduleReminders($appointment);
        }
    }

    public function deleting(Appointment $appointment): void
    {
        $this->safelyDeleteAppointmentEvent($appointment, null, null, 'deleting');
    }

    protected function safelySyncAppointment(Appointment $appointment, string $action = 'created'): void
    {
        try {
            $this->calendar->syncAppointment($appointment);
        } catch (Throwable $exception) {
            Log::error('Failed to sync appointment with Google Calendar.', [
                'action' => $action,
                'appointment_id' => $appointment->getKey(),
                'doctor_id' => $appointment->doctor_id,
                'exception' => $exception,
            ]);
        }
    }

    protected function safelyDeleteAppointmentEvent(
        Appointment $appointment,
        ?int $doctorId = null,
        ?string $eventId = null,
        string $action = 'deleting'
    ): void {
        try {
            $this->calendar->deleteAppointmentEvent($appointment, $doctorId, $eventId);
        } catch (Throwable $exception) {
            Log::error('Failed to delete appointment event from Google Calendar.', [
                'action' => $action,
                'appointment_id' => $appointment->getKey(),
                'doctor_id' => $doctorId ?? $appointment->doctor_id,
                'google_event_id' => $eventId ?? $appointment->google_event_id,
                'exception' => $exception,
            ]);
        }
    }
}
