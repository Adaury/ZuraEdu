<?php

namespace App\Http\Requests\Admin;

use App\Models\HorarioDetalle;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la creación/edición manual de un detalle de horario.
 * Garantiza las 3 restricciones de conflicto antes de persistir:
 *   R1 — El docente no puede estar en dos clases al mismo tiempo
 *   R2 — El aula no puede tener dos clases al mismo tiempo
 *   R3 — El grupo/curso no puede tener dos clases al mismo tiempo
 */
class HorarioDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grupo_id'      => 'required|exists:grupos,id',
            'asignatura_id' => 'required|exists:asignaturas,id',
            'docente_id'    => 'required|exists:docentes,id',
            'aula_id'       => 'nullable|exists:aulas,id',
            'dia'           => 'required|in:lunes,martes,miercoles,jueves,viernes',
            'franja_id'     => 'required|exists:franjas_horarias,id',
        ];
    }

    public function messages(): array
    {
        return [
            'grupo_id.required'      => 'Debes seleccionar un curso.',
            'asignatura_id.required' => 'Debes seleccionar una materia.',
            'docente_id.required'    => 'Debes seleccionar un profesor.',
            'dia.required'           => 'Debes seleccionar el día.',
            'franja_id.required'     => 'Debes seleccionar la franja horaria.',
        ];
    }

    /**
     * Validación de conflictos post-rules.
     * Se ejecuta solo si las reglas básicas pasan.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $horarioId  = $this->route('horario')?->id;
            $detalleId  = $this->route('detalle')?->id; // null en store, set en update
            $dia        = $this->input('dia');
            $franjaId   = $this->input('franja_id');
            $docenteId  = $this->input('docente_id');
            $aulaId     = $this->input('aula_id');
            $grupoId    = $this->input('grupo_id');

            // Base query: mismo horario, mismo día, misma franja, excluyendo el propio registro en update
            $base = HorarioDetalle::where('horario_id', $horarioId)
                ->where('dia', $dia)
                ->where('franja_id', $franjaId)
                ->when($detalleId, fn($q) => $q->where('id', '!=', $detalleId));

            // R1 — Conflicto de docente
            if ($this->filled('docente_id')) {
                $conflictoDocente = (clone $base)
                    ->whereHas('asignacion', fn($q) => $q->where('docente_id', $docenteId))
                    ->exists();

                if ($conflictoDocente) {
                    $validator->errors()->add(
                        'docente_id',
                        'El docente ya tiene una clase asignada en ese día y franja horaria.'
                    );
                }
            }

            // R2 — Conflicto de aula
            if ($aulaId) {
                $conflictoAula = (clone $base)
                    ->where('aula_id', $aulaId)
                    ->exists();

                if ($conflictoAula) {
                    $validator->errors()->add(
                        'aula_id',
                        'El aula ya está ocupada en ese día y franja horaria.'
                    );
                }
            }

            // R3 — Conflicto de grupo/curso
            if ($this->filled('grupo_id')) {
                $conflictoGrupo = (clone $base)
                    ->whereHas('asignacion', fn($q) => $q->where('grupo_id', $grupoId))
                    ->exists();

                if ($conflictoGrupo) {
                    $validator->errors()->add(
                        'grupo_id',
                        'El curso ya tiene una clase en ese día y franja horaria.'
                    );
                }
            }
        });
    }
}
