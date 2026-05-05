<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;

class MaterialClase extends Model
{
    use BelongsToTenant;

    protected $table = 'materiales_clase';

    protected $fillable = [
        'clase_virtual_id',
        'periodo_id',
        'competencia_id',
        'titulo',
        'tipo',
        'subtipo',
        'contenido',
        'url_externo',
        'fecha_limite',
        'puntos',
        'orden',
        'publicado',
        'permite_reentrega',
        'limite_tiempo',
        'publicar_en',
    ];

    protected $casts = [
        'fecha_limite'     => 'datetime',
        'publicar_en'      => 'datetime',
        'publicado'        => 'boolean',
        'permite_reentrega'=> 'boolean',
    ];

    const TIPOS = [
        'anuncio'    => ['label' => 'Anuncio',    'color' => 'blue',   'icon' => 'megaphone'],
        'material'   => ['label' => 'Material',   'color' => 'green',  'icon' => 'book-open'],
        'tarea'      => ['label' => 'Tarea',      'color' => 'yellow', 'icon' => 'clipboard-list'],
        'evaluacion' => ['label' => 'Evaluación', 'color' => 'red',    'icon' => 'academic-cap'],
    ];

    public function claseVirtual()
    {
        return $this->belongsTo(ClaseVirtual::class);
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function competencia()
    {
        return $this->belongsTo(CompetenciaEspecifica::class, 'competencia_id');
    }

    public function archivos()
    {
        return $this->hasMany(ArchivoMaterial::class, 'material_id');
    }

    public function entregas()
    {
        return $this->hasMany(EntregaClassroom::class, 'material_id');
    }

    public function comentarios()
    {
        return $this->hasMany(ComentarioClassroom::class, 'material_id')->with('user')->latest();
    }

    public function rubric()
    {
        return $this->hasOne(ZcRubric::class, 'material_id');
    }

    public function quiz()
    {
        return $this->hasOne(ZcQuiz::class, 'material_id');
    }

    public function getTipoInfoAttribute(): array
    {
        return self::TIPOS[$this->tipo] ?? ['label' => $this->tipo, 'color' => 'gray', 'icon' => 'document'];
    }

    public function esTareaOEvaluacion(): bool
    {
        return in_array($this->tipo, ['tarea', 'evaluacion']);
    }

    public function estaVencido(): bool
    {
        return $this->fecha_limite && $this->fecha_limite->isPast();
    }

    public function getEntregasCount(): int
    {
        return $this->entregas()->whereIn('estado', ['entregado', 'calificado'])->count();
    }

    public function getCalificadosCount(): int
    {
        return $this->entregas()->where('estado', 'calificado')->count();
    }
}
