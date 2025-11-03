<?php

namespace App\Support\Presenters;

use App\Models\Prescription;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PrescriptionPresenter
{
    public function __construct(
        protected Prescription $prescription,
        protected string $timezone,
    ) {
    }

    public static function make(Prescription $prescription, ?string $timezone = null): self
    {
        $timezone ??= config('app.timezone', 'America/Tegucigalpa');

        return new self($prescription, $timezone);
    }

    public function issuedDate(): ?string
    {
        $date = $this->prescription->issued_at?->timezone($this->timezone);

        if (! $date) {
            return null;
        }

        $formatted = $date->locale('es')->translatedFormat('l, j \d\e F, Y');

        return $formatted ? Str::ucfirst($formatted) : null;
    }

    public function issuedTime(): ?string
    {
        $date = $this->prescription->issued_at?->timezone($this->timezone);

        return $date?->format('g:i a');
    }

    public function createdAgo(): ?string
    {
        $created = $this->prescription->created_at?->timezone($this->timezone);

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
        $user = $this->prescription->user;
        $name = trim(($user?->name ?? '') . ' ' . ($user?->last_name ?? ''));

        return $name !== '' ? $name : '—';
    }

    public function diagnosis(): HtmlString
    {
        $content = $this->prescription->diagnosis_html ?? $this->prescription->diagnosis ?? '—';

        return new HtmlString($content);
    }

    public function medications(): HtmlString
    {
        $content = $this->prescription->medications_html
            ?? $this->prescription->medications_description
            ?? '—';

        return new HtmlString($content);
    }
}
