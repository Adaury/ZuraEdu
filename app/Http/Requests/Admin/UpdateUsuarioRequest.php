<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['Administrador', 'Director']);
    }

    public function rules(): array
    {
        $userId = $this->route('usuario')?->id;

        return [
            'name'      => 'required|string|max:100',
            'apellidos' => 'nullable|string|max:100',
            'email'     => "required|email|max:180|unique:users,email,{$userId}",
            'telefono'  => 'nullable|string|max:20',
            'password'  => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'role'      => 'required|exists:roles,name',
            'activo'    => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'El nombre es obligatorio.',
            'email.required'  => 'El correo electrónico es obligatorio.',
            'email.email'     => 'El correo no tiene un formato válido.',
            'email.unique'    => 'Este correo ya está registrado en el sistema.',
            'role.required'   => 'Debe asignar un rol al usuario.',
            'role.exists'     => 'El rol seleccionado no existe.',
        ];
    }
}
