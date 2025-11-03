<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SurgeryPdfController extends Controller
{
    use AuthorizesRequests;

    public function show(Surgery $surgery)
    {
        $this->authorize('print', $surgery);

        $surgery->load(['patient', 'user']);
        $tz = config('clinic.city_date_tz', config('app.timezone'));

        return Pdf::loadView('pdf.surgery', [
            'surgery' => $surgery,
            'tz'      => $tz,
        ])->setPaper('letter')->stream('Nota-postoperatoria-'.$surgery->id.'.pdf');
    }
}
