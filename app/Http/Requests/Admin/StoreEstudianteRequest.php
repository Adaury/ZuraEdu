<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudianteRequest extends FormRequest
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
            'numero_matricula'  => 'required|string|max:20|unique:estudiantes,numero_matricula',
            'cedula'            => 'nullable|string|max:20|unique:estudiantes,cedula',
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
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before'   => 'La fecha de nacimiento debe ser anterior a hoy.',
            'sexo.required'             => 'El sexo es obligatorio.',
            'sexo.in'                   => 'El sexo debe ser M o F.',
            'cedula.unique'             => 'Esta cédula ya está registrada.',
            'numero_matricula.unique'   => 'Este número de matrícula ya existe.',
            'email.email'               => 'Ingresa un correo electrónico válido.',
            'foto.image'                => 'El archivo debe ser una imagen.',
            'foto.max'                  => 'La imagen no puede superar 2MB.',
        ];
    }
}
