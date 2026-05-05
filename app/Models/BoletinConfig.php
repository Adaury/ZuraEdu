<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoletinConfig extends Model
{
    protected $table = 'boletines_config';

    protected $fillable = [
        'school_year_id',
        // Institución
        'nombre_institucion',
        'codigo',
        'lema',
        'logo',
        'nivel_educativo',
        'regional',
        'distrito',
        'municipio',
        'direccion',
        'telefono',
        // Autoridades
        'titulo_director',
        'director',
        'titulo_encargado',
        'encargado_academico',
        // Boletín
        'mostrar_indicadores',
        'mostrar_asistencia',
        'pie_pagina',
        'observaciones_generales',
    ];

    protected $casts = [
        'mostrar_indicadores' => 'boolean',
        'mostrar_asistencia'  => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public static function getOrCreate(int $schoolYearId): self
    {
        return static::firstOrCreate(
            ['school_year_id' => $schoolYearId],
            [
                'nombre_institucion'  => '',
                'nivel_educativo'     => 'Nivel Secundario',
                'titulo_director'     => 'Lic.',
                'titulo_encargado'    => 'Lic.',
                'mostrar_indicadores' => true,
                'mostrar_asistencia'  => true,
            ]
        );
    }

    /** Nombre del director con título */
    public function getNombreDirectorCompletoAttribute(): string
    {
        $titulo = trim($this->titulo_director ?? '');
        $nombre = trim($this->director ?? '');
        return $titulo && $nombre ? "$titulo $nombre" : ($nombre ?: 'Director(a)');
    }

    /** Nombre del encargado académico con título */
    public function getNombreEncargadoCompletoAttribute(): string
    {
        $titulo = trim($this->titulo_encargado ?? '');
        $nombre = trim($this->encargado_academico ?? '');
        return $titulo && $nombre ? "$titulo $nombre" : ($nombre ?: 'Encargado(a) Académico');
    }
}
