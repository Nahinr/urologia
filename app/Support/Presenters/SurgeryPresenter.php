<?php

namespace App\Support\Presenters;

use App\Models\Surgery;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SurgeryPresenter
{
    public function __construct(
        protected Surgery $surgery,
        protected string $timezone,
    ) {}

    public static function make(Surgery $surgery, ?string $tz = null): self
    {
        $tz = $tz ?: config('clinic.city_date_tz', config('app.timezone'));
        return new self($surgery, $tz);
    }

    public function id(): int
    {
        return (int) $this->surgery->id;
    }

    public function date(): ?string
    {
        if (!$this->surgery->fecha_cirugia) return null;
        return Carbon::parse($this->surgery->fecha_cirugia)
            ->timezone($this->timezone)
            ->format('d/m/Y');
    }

    public function lens(): string
    {
        return $this->surgery->lente_intraocular ?: '—';
    }

    public function dxPost(): string
    {
        return $this->surgery->diagnostico_postoperatorio ?: '—';
    }

    public function descriptionTitle(): string
    {
        return $this->surgery->titulo_descripcion ?: '—';
    }

    public function createdAgo(): ?string
    {
        return $this->surgery->created_at?->diffForHumans();
    }

    public function surgeon(): string
    {
        $name = trim($this->surgery->user?->name ?? '');
        return $name !== '' ? $name : '—';
    }

    public function descriptionHtml(): HtmlString
    {
        // Si decides permitir RichEditor en el form, aquí podrías limpiar HTML.
        $content = e($this->surgery->descripcion_final ?? '');
        return new HtmlString(nl2br($content));
    }
}
