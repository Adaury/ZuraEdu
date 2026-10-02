<?php

namespace App\Http\Controllers;

use App\Models\CalendarioAcademico;
use App\Services\Calendario\IcsBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Descarga del .ics y salto a "Agregar a Google Calendar" para cualquier usuario autenticado (padre, docente, estudiante, personal).
 * El evento se resuelve con el scope de tenant (no se puede pedir el de otro colegio) y además se comprueba que ESE usuario
 * pueda verlo (CalendarioAcademico::visiblePara): no basta con conocer el ID.
 */
class CalendarioIcsController extends Controller
{
    public function ics(Request $request, CalendarioAcademico $evento, IcsBuilder $ics)
    {
        abort_unless($evento->visiblePara($request->user()), 404);

        return response($ics->evento($evento), 200, [
            'Content-Type'        => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="evento-' . $evento->id . '.ics"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    public function google(Request $request, CalendarioAcademico $evento): RedirectResponse
    {
        abort_unless($evento->visiblePara($request->user()), 404);

        return redirect()->away($evento->enlaceGoogleCalendar());
    }
}
