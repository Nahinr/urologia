<?php

namespace App\Support\Presenters;

use App\Models\ClinicalBackground;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ClinicalBackgroundPresenter
{
    public function __construct(
        protected ClinicalBackground $background,
        protected string $timezone,
    ) {
    }

    public static function make(ClinicalBackground $background, ?string $timezone = null): self
    {
        $timezone ??= config('app.timezone', 'America/Tegucigalpa');

        return new self($background, $timezone);
    }

    public function description(): HtmlString
    {
        $creator = $this->background->user?->display_name ?? '—';
        $createdDate = $this->formattedCreatedDate();
        $createdRelative = $this->createdRelative();
        $lastEditorLine = $this->lastEditorLine();

        $line = "Creado por: {$creator}";
        if ($createdDate) {
            $line .= " · {$createdDate}";
        }

        if ($createdRelative) {
            $line .= "<div class='mt-0.5 text-gray-500 text-sm'>Creado hace: {$createdRelative}</div>";
        }

        if ($lastEditorLine) {
            $line .= $lastEditorLine;
        }

        return new HtmlString($line);
    }

    protected function formattedCreatedDate(): ?string
    {
        $date = $this->background->created_at?->timezone($this->timezone);

        if (! $date) {
            return null;
        }

        return Str::ucfirst($date->locale('es')->translatedFormat('l, j \d\e F, Y'));
    }

    protected function createdRelative(): ?string
    {
        $date = $this->background->created_at?->timezone($this->timezone);

        if (! $date) {
            return null;
        }

        $relative = $date->locale('es')->diffForHumans([
            'parts' => 2,
            'join' => true,
            'short' => false,
        ]);

        return $relative ? Str::replaceFirst('hace ', '', $relative) : null;
    }

    protected function lastEditorLine(): ?string
    {
        if (! $this->background->updated_at || ! $this->background->created_at) {
            return null;
        }

        if ($this->background->updated_at->lte($this->background->created_at)) {
            return null;
        }

        if (! $this->background->updated_by) {
            return null;
        }

        $updatedAt = $this->background->updated_at->timezone($this->timezone);
        $relative = $updatedAt->locale('es')->diffForHumans([
            'parts' => 2,
            'join' => true,
            'short' => false,
        ]);
        $relative = $relative ? Str::replaceFirst('hace ', '', $relative) : null;

        $lastEditor = $this->background->updatedBy?->display_name ?? '—';

        $line = "<div class='mt-0.5 text-gray-500 text-sm'>Última actualización por: {$lastEditor}";
        if ($relative) {
            $line .= " · {$relative}";
        }

        return $line . '</div>';
    }
}
