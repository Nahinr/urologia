<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PrescriptionPdfController extends Controller
{
    use AuthorizesRequests;
    
    public function show(Request $request, Prescription $prescription)
    {
        $this->authorize('print', $prescription);

        $prescription->load(['patient','user']);
        $tz = config('clinic.city_date_tz', config('app.timezone'));

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.prescription', [
            'rx' => $prescription,
            'tz' => $tz,
        ])->setPaper('letter')->stream('Receta-'.$prescription->id.'.pdf');
    }
}
