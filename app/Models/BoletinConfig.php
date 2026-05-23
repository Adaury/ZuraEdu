<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class BoletinConfig extends Model
{
    use BelongsToTenant;

    protected $table = 'boletines_config';

    protected $fillable = [
        'school_year_id',
        // Institución
        'nombre_institucion',
        'codigo',
        'lema',
        'logo',
        // Diseño
        'color_primario',
        'color_secundario',
        'logo_ancho',
        'logo_alto',
        'tamano_fuente',
        'mostrar_foto_estudiante',
        // Info institucional
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
        'mostrar_indicadores'     => 'boolean',
        'mostrar_asistencia'      => 'boolean',
        'mostrar_foto_estudiante' => 'boolean',
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
                'nombre_institucion'      => '',
                'nivel_educativo'         => 'Nivel Secundario',
                'titulo_director'         => 'Lic.',
                'titulo_encargado'        => 'Lic.',
                'mostrar_indicadores'     => true,
                'mostrar_asistencia'      => true,
                'color_primario'          => '#1e3a6e',
                'color_secundario'        => '#c0392b',
                'logo_ancho'              => 68,
                'logo_alto'               => 58,
                'tamano_fuente'           => '9pt',
                'mostrar_foto_estudiante' => false,
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
