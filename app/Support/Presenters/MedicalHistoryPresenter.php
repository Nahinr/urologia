<?php

namespace App\Support\Presenters;

use App\Models\MedicalHistory;
use Illuminate\Support\Str;

class MedicalHistoryPresenter
{
    public function __construct(
        protected MedicalHistory $history,
        protected string $timezone,
    ) {
    }

    public static function make(MedicalHistory $history, ?string $timezone = null): self
    {
        $timezone ??= config('app.timezone', 'America/Tegucigalpa');

        return new self($history, $timezone);
    }

    public function formattedVisitDate(): ?string
    {
        $date = $this->history->visit_date?->timezone($this->timezone);

        if (! $date) {
            return null;
        }

        $formatted = $date->locale('es')->translatedFormat('l, j \d\e F, Y');

        return $formatted ? Str::ucfirst($formatted) : null;
    }

    public function formattedVisitTime(): ?string
    {
        $date = $this->history->visit_date?->timezone($this->timezone);

        return $date?->format('g:i a');
    }

    public function createdAgo(): ?string
    {
        $created = $this->history->created_at?->timezone($this->timezone);

        if (! $created) {
            return null;
        }

        $relative = $created->locale('es')->diffForHumans([
            'parts' => 2,
            'join' => true,
            'short' => false,
        ]);

        return $relative ? Str::replaceFirst('hace ', '', $relative) : null;
    }

    public function doctorName(): string
    {
        return $this->history->user?->display_name
            ?? $this->history->user?->name
            ?? '—';
    }

    /** NUEVO: resumen de Evolución */
    public function evolution(int $limit = 200): string
    {
        return Str::limit((string) $this->history->evolution, $limit) ?: '—';
    }

    /** NUEVO: resumen de Examen Físico */
    public function physicalExam(int $limit = 200): string
    {
        return Str::limit((string) $this->history->physical_exam, $limit) ?: '—';
    }
}
