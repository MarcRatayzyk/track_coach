<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\AthleteProgramAssignment;
use App\Support\ProgramBlockPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProgramPdfExportController extends Controller
{
    public function __invoke(AthleteProgramAssignment $assignment): Response
    {
        $this->authorize('manage', $assignment);

        $block = ProgramBlockPresenter::forAssignment($assignment);

        abort_if($block === null, 404);

        $pdf = Pdf::loadView('pdf.program-block', [
            'block' => $block,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'programme-%s-%s.pdf',
            str($block['athlete_name'] ?? 'athlete')->slug(),
            str($block['name'] ?? 'bloc')->slug(),
        );

        return $pdf->download($filename);
    }
}
