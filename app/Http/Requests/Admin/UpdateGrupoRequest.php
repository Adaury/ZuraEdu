<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            'Administrador', 'Director', 'Secretaría', 'Secretaria Docente',
        ]);
    }

    public function rules(): array
    {
        return [
            'school_year_id' => 'required|exists:school_years,id',
            'grado_id'       => 'required|exists:grados,id',
            'seccion_id'     => 'required|exists:secciones,id',
            'tutor_id'       => 'nullable|exists:docentes,id',
            'aula'           => 'nullable|string|max:20',
            'capacidad'      => 'nullable|integer|min:1|max:60',
            'activo'         => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'school_year_id.required' => 'El año escolar es obligatorio.',
            'grado_id.required'       => 'El grado es obligatorio.',
            'seccion_id.required'     => 'La sección es obligatoria.',
            'capacidad.min'           => 'La capacidad mínima es 1.',
            'capacidad.max'           => 'La capacidad máxima es 60.',
        ];
    }
}
