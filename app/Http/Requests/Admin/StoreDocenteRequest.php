<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Administrador', 'Director', 'Coordinador Académico']);
    }

    public function rules(): array
    {
        return [
            'cedula'           => 'nullable|string|max:20|unique:docentes,cedula',
            'nombres'          => 'required|string|min:2|max:100',
            'apellidos'        => 'required|string|min:2|max:100',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'sexo'             => 'nullable|in:M,F',
            'telefono'         => 'nullable|string|max:20',
            'email'            => 'nullable|email|max:150|unique:docentes,email',
            'direccion'        => 'nullable|string|max:500',
            'especialidad'     => 'nullable|string|max:100',
            'titulo_academico' => 'nullable|string|max:150',
            'foto'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'estado'           => 'required|in:activo,inactivo',
            'area'             => 'required|in:tecnica,administrativa,otro',
            'cargo'            => 'nullable|string|max:150',
        ];
    }

    public function messages(): array
    {
        return [
            'nombres.required'  => 'El nombre es obligatorio.',
            'apellidos.required'=> 'El apellido es obligatorio.',
            'cedula.unique'     => 'Esta cédula ya está registrada.',
            'email.unique'      => 'Este correo ya está registrado para otro docente.',
            'area.required'     => 'El área es obligatoria.',
            'area.in'           => 'El área debe ser técnica, administrativa u otro.',
        ];
    }
}
