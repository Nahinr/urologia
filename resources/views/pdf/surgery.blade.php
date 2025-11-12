@php
    use Carbon\Carbon;

    // Datos de clínica centralizados
    $clinic = config('clinic');
    $clinicName = $clinic['name'] ?? 'Centro Oftalmológico Los Próceres';
    $clinicAddr = $clinic['address'] ?? "Bo. Rio De Piedras, 22 y 23 ave, 3 calle, S.O.\nTel: 2516-2517 / 9619-2914";
    $logoPath = $clinic['logo_path'] ?? public_path('images/clinic-logo.png');
    $tz = $clinic['city_date_tz'] ?? config('app.timezone');

    // Logo base64
    $logoData = null;
    $logoMime = null;
    if (is_string($logoPath) && file_exists($logoPath) && is_readable($logoPath)) {
        $logoMime = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
        $logoData = base64_encode(file_get_contents($logoPath));
    }

    // Paciente
    $patientName = $surgery->patient?->display_name
        ?? trim(($surgery->patient->first_name ?? '').' '.($surgery->patient->last_name ?? ''));

    // Fecha
    $fechaLegible = optional(Carbon::parse($surgery->fecha_cirugia)->timezone($tz))
        ?->isoFormat('D [de] MMMM [de] YYYY')
        ?? Carbon::parse($surgery->fecha_cirugia)->format('d/m/Y');

    // Doctor
    $user = $surgery->user;                                   // <-- TODO viene de user
    $userName = $user?->display_name ?? $user?->name ?? '—';  // usa accessor del modelo User
    $userSpecialty = $user?->specialty;    
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Nota post-operatoria</title>
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 10px; color:#111; margin:24px; }

    .header {
        text-align: left;
        margin-bottom: 8px;
    }

    .logo {
        width: 110px;
        height: auto;
        display: block;

    }

    .clinic-name {
        font-weight: 700;
        font-size: 18px;
        margin: 0;
    }

    .clinic-addr {
        color: #555;
        font-size: 10px;
        line-height: 0.6;
        white-space: pre-line;
    }

    .title {
        font-weight: 700;
        text-transform: uppercase;
        text-align: center;
        font-size: 14px;
        margin: 30px 0 12px;
    }

    table.meta {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    table.meta td {
        border: 1px solid #111;
        padding: 4px 6px;
        vertical-align: top;
        font-size: 11px;
    }

    .label { font-weight: 600; width: 32%; }

    .desc-title {
        font-weight: 700;
        text-transform: uppercase;
        text-align: center;
        font-size: 14px;
        margin: 30px 0 8px;
    }

    .desc-body {
        text-align: justify;
        line-height: 1.55;
        white-space: pre-wrap;
    }

    .sign {
        margin-top: 120px;
        text-align: center;
    }

    .sign .line {
        margin: 0 auto 6px auto;
        border-top: 1px solid #111;
        width: 60%;
    }

    .small { font-size: 10px; }
</style>
</head>
<body>

    {{-- Encabezado alineado a la izquierda --}}
    <div class="header">
        <img class="logo" src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="logo">

        <h1 class="clinic-name">{{ $clinicName }}</h1>

        <div class="clinic-addr">
            {{ $clinic['address'] ?? $clinicAddr }}<br>
            Tel: {{ $clinic['phone'] ?? '—' }}
        </div>
    </div>

    <div class="title">Nota post-operatoria</div>

    <table class="meta">
        <tr>
            <td class="label">Paciente</td>
            <td>{{ $patientName ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Diagnóstico pre-operatorio</td>
            <td>{{ $surgery->diagnostico_preoperatorio }}</td>
        </tr>
        <tr>
            <td class="label">Diagnóstico post-operatorio</td>
            <td>{{ $surgery->diagnostico_postoperatorio }}</td>
        </tr>
        <tr>
            <td class="label">Anestesia</td>
            <td>{{ $surgery->anestesia }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de cirugía</td>
            <td>{{ $fechaLegible }}</td>
        </tr>
        <tr>
            <td class="label">Lente intraocular</td>
            <td>{{ $surgery->lente_intraocular }}</td>
        </tr>
        @if($surgery->hallazgos_complicaciones)
            <tr>
                <td class="label">Hallazgos/Complicaciones</td>
                <td>{{ $surgery->hallazgos_complicaciones }}</td>
            </tr>
        @endif
        @if($surgery->otros_procedimientos)
            <tr>
                <td class="label">Otros procedimientos</td>
                <td>{{ $surgery->otros_procedimientos }}</td>
            </tr>
        @endif
    </table>

    <div class="desc-title">{{ mb_strtoupper($surgery->titulo_descripcion, 'UTF-8') }}</div>
    <div class="desc-body">{!! nl2br(e($surgery->descripcion_final)) !!}</div>

    <div class="sign">
        <div class="line"></div>
        <div class="small"><strong>Dr. {{ $userName }}</strong></div>
        <div class="small">Cirujano </div>
        @if(!empty($userSpecialty))
            <div class="small">{{ $userSpecialty }}</div>
        @endif
    </div>

</body>
</html>

