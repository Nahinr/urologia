<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Support\Appointments\AppointmentReminderScheduler;
use App\Support\GoogleCalendar\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleCalendarEvents extends Command
{
    protected $signature = 'google-calendar:sync-updates
        {--doctor= : Limit the sync to a single doctor ID}
        {--from= : ISO8601 date/time to start scanning from}
        {--to= : ISO8601 date/time to stop scanning at}';

    protected $description = 'Synchronize appointment changes made in Google Calendar back into the local application.';

    public function handle(GoogleCalendarService $calendar, AppointmentReminderScheduler $reminders): int
    {
        $doctorId = $this->option('doctor');
        $from = $this->parseDateOption($this->option('from'), now()->subDays(30));
        $to = $this->parseDateOption($this->option('to'), now()->addMonths(6));

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $query = Appointment::query()
            ->with('doctor')
            ->whereNotNull('google_event_id')
            ->whereBetween('start_datetime', [$from, $to])
            ->whereHas('doctor', function (Builder $builder): void {
                $builder->whereNotNull('google_calendar_refresh_token');
            });

        if ($doctorId !== null && $doctorId !== '') {
            $query->where('doctor_id', (int) $doctorId);
        }

        $appointments = $query->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments require synchronization.');

            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($appointments as $appointment) {
            try {
                if (! $calendar->syncAppointmentFromGoogle($appointment)) {
                    continue;
                }

                $appointment->refresh();
                $reminders->scheduleReminders($appointment);
                $updated++;

                $this->line(sprintf('Synced appointment %d from Google Calendar.', $appointment->getKey()));
            } catch (Throwable $exception) {
                Log::warning('Failed to synchronize appointment from Google Calendar.', [
                    'appointment_id' => $appointment->getKey(),
                    'doctor_id' => $appointment->doctor_id,
                    'exception' => $exception,
                ]);
            }
        }

        $this->info(sprintf('Synchronization complete. %d appointment(s) updated.', $updated));

        return self::SUCCESS;
    }

    protected function parseDateOption(mixed $value, Carbon $default): Carbon
    {
        if ($value === null || $value === '') {
            return $default->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            $this->warn(sprintf('Could not parse "%s". Using default window instead.', $value));

            return $default->copy();
        }
    }
}
