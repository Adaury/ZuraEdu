<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoletinConfig;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class BoletinConfigController extends Controller
{
    public function index()
    {
        $schoolYear    = SchoolYear::actual();
        $schoolYears   = SchoolYear::orderByDesc('id')->get();
        $boletinConfig = $schoolYear ? BoletinConfig::getOrCreate($schoolYear->id) : null;

        return view('admin.boletines.config', compact('boletinConfig', 'schoolYear', 'schoolYears'));
    }

    public function update(Request $request)
    {
        $schoolYear = SchoolYear::actual();
        if (! $schoolYear) {
            return back()->with('error', 'No hay un año escolar activo.');
        }

        $data = $request->validate([
            // Institución
            'nombre_institucion'      => 'nullable|string|max:200',
            'codigo'                  => 'nullable|string|max:50',
            'lema'                    => 'nullable|string|max:200',
            'nivel_educativo'         => 'nullable|string|max:100',
            'regional'                => 'nullable|string|max:100',
            'distrito'                => 'nullable|string|max:100',
            'municipio'               => 'nullable|string|max:100',
            'direccion'               => 'nullable|string|max:255',
            'telefono'                => 'nullable|string|max:50',
            // Autoridades
            'titulo_director'         => 'nullable|string|max:30',
            'director'                => 'nullable|string|max:150',
            'titulo_encargado'        => 'nullable|string|max:30',
            'encargado_academico'     => 'nullable|string|max:150',
            // Boletín
            'pie_pagina'              => 'nullable|string|max:500',
            'observaciones_generales' => 'nullable|string|max:1000',
            'mostrar_indicadores'     => 'boolean',
            'mostrar_asistencia'      => 'boolean',
            'logo'                    => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $data['mostrar_indicadores'] = $request->boolean('mostrar_indicadores');
        $data['mostrar_asistencia']  = $request->boolean('mostrar_asistencia');

        $config = BoletinConfig::getOrCreate($schoolYear->id);

        unset($data['logo']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('boletines', 'public');
        }

        $config->update($data);

        return back()->with('success', 'Configuración del boletín guardada correctamente.');
    }
}
