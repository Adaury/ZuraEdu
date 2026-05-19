<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class DocumentosApiController extends Controller
{
    /**
     * GET /api/v1/documentos/info
     * Devuelve metadata del estudiante para saber qué documentos están disponibles.
     * También devuelve un token temporal para abrir documentos en el browser.
     */
    public function info(Request $request)
    {
        $user = $request->user();
        $sy   = SchoolYear::actual();

        if ($user->hasRole('Estudiante')) {
            return $this->infoEstudiante($user, $sy);
        }
        if ($user->hasRole('Representante')) {
            return $this->infoRepresentante($user, $sy);
        }

        return response()->json(['message' => 'Rol no soportado.'], 403);
    }

    /**
     * GET /api/v1/documentos/info-hijo/{estudiante}
     * Para el representante: metadata del hijo.
     */
    public function infoHijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        $sy       = SchoolYear::actual();
        $matricula = $estudiante->matriculas()
            ->with(['grupo.grado', 'grupo.seccion', 'schoolYear'])
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        $token = $this->generateDownloadToken($request->user()->id);

        return response()->json([
            'estudiante'    => $estudiante->nombre_completo,
            'tiene_matricula' => (bool) $matricula,
            'grupo'         => $matricula?->grupo?->nombre_completo,
            'school_year'   => $sy?->nombre,
            'download_token'=> $token,
            'documentos'    => $this->buildDocumentosHijo($estudiante, $matricula),
        ]);
    }

    // ── Privados ──────────────────────────────────────────────────────────────

    private function infoEstudiante($user, $sy)
    {
        $est = Estudiante::where('user_id', $user->id)->first();
        if (! $est) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $matricula = $est->matriculas()
            ->with(['grupo.grado', 'grupo.seccion'])
            ->where('estado', 'activa')
            ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->latest()->first();

        $token = $this->generateDownloadToken($user->id);

        return response()->json([
            'estudiante'      => $est->nombre_completo,
            'tiene_matricula' => (bool) $matricula,
            'grupo'           => $matricula?->grupo?->nombre_completo,
            'school_year'     => $sy?->nombre,
            'download_token'  => $token,
            'documentos'      => $this->buildDocumentosEstudiante($matricula),
        ]);
    }

    private function infoRepresentante($user, $sy)
    {
        $rep = Representante::where('user_id', $user->id)->first();
        if (! $rep) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        $token = $this->generateDownloadToken($user->id);

        $hijos = $rep->estudiantes()->get()->map(function ($est) use ($sy, $token) {
            $matricula = $est->matriculas()
                ->where('estado', 'activa')
                ->when($sy, fn($q) => $q->where('school_year_id', $sy->id))
                ->latest()->first();

            return [
                'estudiante_id'   => $est->id,
                'nombre'          => $est->nombre_completo,
                'tiene_matricula' => (bool) $matricula,
                'grupo'           => $matricula?->grupo?->nombre_completo ?? null,
                'documentos'      => $this->buildDocumentosHijo($est, $matricula),
            ];
        });

        return response()->json([
            'download_token' => $token,
            'school_year'    => $sy?->nombre,
            'hijos'          => $hijos,
        ]);
    }

    private function buildDocumentosEstudiante($matricula): array
    {
        $tiene = (bool) $matricula;

        return [
            [
                'categoria' => 'Boletines',
                'items' => [
                    ['id' => 'boletin_pdf',       'label' => 'Boletín completo (PDF)',    'icono' => 'document-text',   'color' => '#3b82f6', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/boletin-pdf'],
                    ['id' => 'notas_pdf',          'label' => 'Calificaciones (PDF)',       'icono' => 'bar-chart',       'color' => '#10b981', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/notas-pdf'],
                ],
            ],
            [
                'categoria' => 'Certificados y Constancias',
                'items' => [
                    ['id' => 'constancia',         'label' => 'Constancia de Matrícula',   'icono' => 'ribbon',          'color' => '#f59e0b', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/constancia'],
                    ['id' => 'cert_calificaciones','label' => 'Certificado de Notas',       'icono' => 'award',           'color' => '#6366f1', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/certificado-calificaciones'],
                    ['id' => 'carta_conducta',     'label' => 'Carta de Buena Conducta',   'icono' => 'shield-checkmark','color' => '#16a34a', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/carta-conducta'],
                ],
            ],
            [
                'categoria' => 'Asistencia y Horario',
                'items' => [
                    ['id' => 'asistencia_pdf',     'label' => 'Asistencia (PDF)',           'icono' => 'calendar-check',  'color' => '#0ea5e9', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/asistencia-pdf'],
                    ['id' => 'horario_pdf',        'label' => 'Horario (PDF)',              'icono' => 'time',            'color' => '#8b5cf6', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/horario-pdf'],
                ],
            ],
            [
                'categoria' => 'Observaciones',
                'items' => [
                    ['id' => 'observaciones_pdf',  'label' => 'Observaciones Docentes (PDF)','icono' => 'chatbox',        'color' => '#ec4899', 'disponible' => $tiene, 'ruta_web' => '/portal/estudiante/observaciones-pdf'],
                ],
            ],
        ];
    }

    private function buildDocumentosHijo(Estudiante $est, $matricula): array
    {
        $tiene = (bool) $matricula;
        $id    = $est->id;

        return [
            [
                'categoria' => 'Boletines',
                'items' => [
                    ['id' => 'boletin_pdf',    'label' => 'Boletín (PDF)',         'icono' => 'document-text', 'color' => '#3b82f6', 'disponible' => $tiene, 'ruta_web' => "/portal/padre/hijo/{$id}/boletin"],
                    ['id' => 'notas_pdf',      'label' => 'Calificaciones (PDF)',  'icono' => 'bar-chart',     'color' => '#10b981', 'disponible' => $tiene, 'ruta_web' => "/portal/padre/hijo/{$id}/notas-pdf"],
                ],
            ],
            [
                'categoria' => 'Asistencia y Horario',
                'items' => [
                    ['id' => 'asistencia_pdf', 'label' => 'Asistencia (PDF)',      'icono' => 'calendar-check','color' => '#0ea5e9', 'disponible' => $tiene, 'ruta_web' => "/portal/padre/hijo/{$id}/asistencia-pdf"],
                    ['id' => 'horario_pdf',    'label' => 'Horario (PDF)',         'icono' => 'time',          'color' => '#8b5cf6', 'disponible' => $tiene, 'ruta_web' => "/portal/padre/hijo/{$id}/horario"],
                ],
            ],
            [
                'categoria' => 'Observaciones',
                'items' => [
                    ['id' => 'obs_pdf',        'label' => 'Observaciones (PDF)',   'icono' => 'chatbox',       'color' => '#ec4899', 'disponible' => $tiene, 'ruta_web' => "/portal/padre/hijo/{$id}/observaciones-pdf"],
                ],
            ],
        ];
    }

    /** Genera un token de descarga temporal (60 min) para abrir PDFs en el browser. */
    private function generateDownloadToken(int $userId): string
    {
        $token = Str::random(40);
        Cache::put("download_token_{$token}", $userId, now()->addHour());
        return $token;
    }
}
