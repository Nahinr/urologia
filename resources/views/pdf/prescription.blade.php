@php
    $clinic = config('clinic');
    
    // 1) Ruta absoluta esperada dentro del contenedor (Sail)
    $logoPath = $clinic['logo_path'] ?? public_path('images/clinic-logo.png');

    // 2) Preparar base64 (si existe y es legible)
    $logoData = null; $logoMime = null;
    if (is_string($logoPath) && file_exists($logoPath) && is_readable($logoPath)) {
        $logoMime = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
        $logoData = base64_encode(file_get_contents($logoPath));
    }

    $issued = optional($rx->issued_at)->timezone($tz);
    $fecha  = $issued?->locale('es')->translatedFormat('j \\de F \\de Y');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Receta de {{ $rx->patient?->display_name }}</title>
<style>
    *{ font-family: DejaVu Sans, sans-serif; }
    body{ font-size: 12px; color:#111; margin:24px; }
    .header{ display:flex; align-items:flex-start; gap:14px; padding-bottom:0; margin-bottom:10px;}
    .logo{ width:100px; height:auto; margin-left:auto; } /* a la derecha */
    .clinic h1{ margin:0; font-size:18px; }
    .clinic .meta{ color:#555; font-size:11px; line-height:1.35; }
    .title{ text-align:center; font-weight:bold; font-size:14px; margin:6px 0 10px; }
    .grid{ width:100%; border-collapse:collapse; margin:6px 0 12px; }
    .grid td{ border:1px solid #ddd; padding:6px; vertical-align:top; }
    .label{ font-weight:bold; color:#333; }
    .section-title{ font-weight:bold; text-transform:uppercase; margin:14px 0 6px; font-size:12px;}
    .prose{ line-height:1.5; }
    .footer{ margin-top:18px; border-top:1px dashed #ccc; padding-top:8px; font-size:10px; color:#666; }
    /* Quitar cualquier espaciamiento extra que se veía como línea bajo cabecera */
    hr{ display:none; }
</style>
</head>
<body>

{{-- Encabezado: clínica (izq) + logo y fecha (der) --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
        <tr>
            {{-- Columna izquierda: datos de la clínica --}}
            <td style="vertical-align:top; padding-right:12px;">
                <div class="clinic">
                    <h1 style="margin:0; font-size:18px;">{{ $clinic['name'] }}</h1>
                    <div class="meta" style="color:#555; font-size:11px; line-height:1.35;">
                        {{ $clinic['slogan'] }}<br>
                        {{ $clinic['address'] }}<br>
                        Tel: {{ $clinic['phone'] }}<br>
                        Email: {{ $clinic['email'] }}
                    </div>
                </div>
            </td>

            {{-- Columna derecha: logo y fecha --}}
            <td style="vertical-align:top; width:220px; text-align:right;">
                @if ($logoData)
                    <img src="data:{{ $logoMime }};base64,{{ $logoData }}"
                        alt="Logo" style="width:90px; height:auto; ">
                @endif
                <div><span class="label" style="font-weight:bold; color:#333;">Fecha:</span> {{ $fecha }}</div>
            </td>
        </tr>
    </table>

    <div class="title" style="margin-top:40px">RECETA MÉDICA</div>

    {{-- Paciente + Médico --}}
    <table class="grid">
        <tr>
            <td style="width:60%">
                <div><span class="label">Paciente:</span> {{ $rx->patient?->display_name ?? '—' }}</div>
                <div><span class="label">DNI:</span> {{ $rx->patient?->dni ?? '—' }}</div>

            </td>
            <td style="width:40%">
                <div>
                    <span class="label">Edad:</span> {{ $rx->patient?->age_full ?? '—' }}<br>
                    @if($rx->patient?->gender_label)<span class="label">Género:</span> {{ $rx->patient?->gender_label }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- PRIMERO: Medicamentos / Solicitud --}}
    <div class="section-title">Medicamentos e indicaciones / Solicitud de exámenes</div>
    <div class="prose">{!! $rx->medications_html ?? e($rx->medications_description) !!}</div>

    {{-- DESPUÉS: Diagnóstico --}}
    <div class="section-title">Diagnóstico</div>
    <div class="prose">{!! $rx->diagnosis_html ?? e($rx->diagnosis) !!}</div>

    <div style="margin-top: 28px; width: 100%; text-align: right;">
        <div style="display: inline-block; width: 260px; text-align: center;">
            <div style="border-top: 1px solid #333; margin-bottom: 4px;"></div>
            Dr.{{ ($rx->user?->name . ' ' . ($rx->user?->last_name ?? '')) ?: ($rx->doctor_name ?? '—') }}<br>
            @if($rx->doctor_specialty)
                <span style="font-size:10px; color:#666;">{{ $rx->doctor_specialty }}</span>
            @endif
        </div>
    </div>

    <div class="footer">
    </div>
</body>
</html>
