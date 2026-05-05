<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole([
            'Administrador', 'Director', 'Coordinador Académico',
            'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo',
            'Secretaría', 'Secretaria Docente', 'Docente',
        ]);
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:csv,xlsx,xls',
                'max:10240', // 10MB máximo
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debes seleccionar un archivo para importar.',
            'archivo.file'     => 'El campo debe ser un archivo.',
            'archivo.mimes'    => 'Solo se aceptan archivos CSV o Excel (.xlsx, .xls).',
            'archivo.max'      => 'El archivo no puede superar 10MB.',
        ];
    }
}
