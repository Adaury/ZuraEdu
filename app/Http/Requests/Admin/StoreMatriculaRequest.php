<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            'Administrador', 'Director', 'Coordinador Académico',
            'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo',
            'Secretaría', 'Secretaria Docente',
        ]);
    }

    public function rules(): array
    {
        return [
            'school_year_id' => 'required|exists:school_years,id',
            'estudiante_id'  => [
                'required',
                'exists:estudiantes,id',
                Rule::unique('matriculas')->where(fn ($q) =>
                    $q->where('school_year_id', $this->school_year_id)
                      ->where('estado', 'activa')
                ),
            ],
            'grupo_id'       => 'required|exists:grupos,id',
            'fecha_matricula'=> 'required|date',
            'numero_orden'   => 'nullable|integer|min:1|max:999',
            'estado'         => 'required|in:activa,retirada,trasladada',
            'observaciones'  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'estudiante_id.unique'   => 'Este estudiante ya tiene una matrícula activa para este año escolar.',
            'school_year_id.exists'  => 'El año escolar seleccionado no existe.',
            'grupo_id.exists'        => 'El grupo seleccionado no existe.',
            'fecha_matricula.required'=> 'La fecha de matrícula es obligatoria.',
        ];
    }
}
