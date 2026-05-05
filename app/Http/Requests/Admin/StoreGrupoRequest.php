<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrupoRequest extends FormRequest
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
            'school_year_id.exists'   => 'El año escolar seleccionado no existe.',
            'grado_id.required'       => 'El grado es obligatorio.',
            'grado_id.exists'         => 'El grado seleccionado no existe.',
            'seccion_id.required'     => 'La sección es obligatoria.',
            'seccion_id.exists'       => 'La sección seleccionada no existe.',
            'capacidad.integer'       => 'La capacidad debe ser un número entero.',
            'capacidad.min'           => 'La capacidad mínima es 1.',
            'capacidad.max'           => 'La capacidad máxima es 60.',
        ];
    }
}
