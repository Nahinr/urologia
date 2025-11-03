<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Support\GoogleCalendar\GoogleCalendarService;

class AppointmentObserver
{
    public function __construct(private readonly GoogleCalendarService $calendar)
    {
    }

    public function created(Appointment $appointment): void
    {
        $this->calendar->syncAppointment($appointment);
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

        if ($appointment->wasChanged('doctor_id')) {
            $this->calendar->deleteAppointmentEvent(
                $appointment,
                $appointment->getOriginal('doctor_id'),
                $appointment->getOriginal('google_event_id')
            );

            $appointment->forceFill(['google_event_id' => null])->saveQuietly();
        }

        if ($shouldSync) {
            $appointment->refresh();
            $this->calendar->syncAppointment($appointment);
        }
    }

    public function deleting(Appointment $appointment): void
    {
        $this->calendar->deleteAppointmentEvent($appointment);
    }
}
