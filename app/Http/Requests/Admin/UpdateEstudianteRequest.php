<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstudianteRequest extends FormRequest
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
        $id = $this->route('estudiante')?->id ?? $this->route('estudiante');

        return [
            'numero_matricula'  => "required|string|max:20|unique:estudiantes,numero_matricula,{$id}",
            'cedula'            => "nullable|string|max:20|unique:estudiantes,cedula,{$id}",
            'nombres'           => 'required|string|min:2|max:100',
            'apellidos'         => 'required|string|min:2|max:100',
            'fecha_nacimiento'  => 'required|date|before:today',
            'sexo'              => 'required|in:M,F',
            'nacionalidad'      => 'nullable|string|max:50',
            'lugar_nacimiento'  => 'nullable|string|max:100',
            'telefono'          => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:150',
            'direccion'         => 'nullable|string|max:500',
            'sector'            => 'nullable|string|max:100',
            'municipio'         => 'nullable|string|max:100',
            'provincia'         => 'nullable|string|max:100',
            'foto'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'estado'            => 'required|in:activo,inactivo,egresado,transferido',
            'tutor_nombre'      => 'nullable|string|max:150',
            'tutor_parentesco'  => 'nullable|string|max:150',
            'tutor_telefono'    => 'nullable|string|max:20',
            'tutor_email'       => 'nullable|email|max:150',
            'tutor_trabajo'     => 'nullable|string|max:150',
            'notas_medicas'     => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required'          => 'El nombre es obligatorio.',
            'apellidos.required'        => 'El apellido es obligatorio.',
            'fecha_nacimiento.before'   => 'La fecha de nacimiento debe ser anterior a hoy.',
            'cedula.unique'             => 'Esta cédula ya está registrada en otro estudiante.',
            'numero_matricula.unique'   => 'Este número de matrícula ya existe en otro estudiante.',
        ];
    }
}
