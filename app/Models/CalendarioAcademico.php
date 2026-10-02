<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarioAcademico extends Model
{
    use BelongsToTenant;

    protected $table = 'calendario_academico';

    protected $fillable = [
        'school_year_id', 'titulo', 'descripcion', 'tipo',
        'fecha_inicio', 'fecha_fin', 'hora_inicio', 'color',
        'aplica_a', 'periodo_id', 'creado_por', 'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function destinatarios(): HasMany
    {
        return $this->hasMany(CalendarioDestinatario::class, 'calendario_id');
    }

    /**
     * ¿Puede este usuario ver (y descargar el .ics de) este evento? Mismo criterio que el calendario de los portales:
     * activo, y o bien `aplica_a` incluye su rol, o bien el administrador lo eligió como destinatario.
     * La pertenencia al tenant la garantiza el scope global al resolver el evento.
     */
    public function visiblePara(User $user): bool
    {
        if (! $this->activo) {
            return $user->hasAnyRole(['Administrador', 'Director']);
        }
        if ($user->hasAnyRole(['Administrador', 'Director'])) {
            return true;
        }
        if ($this->destinatarios()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return match ($this->aplica_a) {
            'todos'           => true,
            'docentes'        => $user->hasRole('Docente'),
            'estudiantes'     => $user->hasRole('Estudiante'),
            'coordinadores',
            'administrativos' => ! $user->hasAnyRole(['Docente', 'Estudiante', 'Representante']),
            default           => false,
        };
    }

    /** Eventos que ve un usuario en su portal: por rol (`aplica_a`) o porque el administrador lo eligió. */
    public function scopeVisiblesParaUsuario($query, int $userId, array $aplica)
    {
        return $query->where('activo', true)->where(function ($q) use ($userId, $aplica) {
            $q->whereIn('aplica_a', $aplica)
              ->orWhereHas('destinatarios', fn ($d) => $d->where('user_id', $userId));
        });
    }

    /** Zona horaria del colegio para las horas del calendario (no la de la aplicación, que es UTC). */
    public static function zonaHoraria(): string
    {
        return config('calendario.zona_horaria', 'America/Santo_Domingo');
    }

    /** Inicio y fin reales del evento. Sin hora = todo el día (fin exclusivo, como pide iCalendar/Google). */
    public function intervalo(): array
    {
        $tz  = self::zonaHoraria();
        // `fecha_*` son columnas de solo fecha: se toma el día calendario y se construye en la zona del colegio.
        $dia = fn ($f) => \Carbon\Carbon::createFromFormat('Y-m-d', $f->format('Y-m-d'), $tz)->startOfDay();

        $ini0 = $dia($this->fecha_inicio);
        $fin0 = $dia($this->fecha_fin ?? $this->fecha_inicio);

        if (! $this->hora_inicio) {
            return ['todoElDia' => true, 'inicio' => $ini0, 'fin' => $fin0->copy()->addDay()];
        }

        [$h, $m] = array_map('intval', explode(':', substr((string) $this->hora_inicio, 0, 5)));

        // No hay hora de fin en la tabla: una hora de duración (en eventos de varios días, hasta el último día a la misma hora + 1 h).
        return [
            'todoElDia' => false,
            'inicio'    => $ini0->copy()->setTime($h, $m),
            'fin'       => $fin0->copy()->setTime($h, $m)->addHour(),
        ];
    }

    /** Enlace "Agregar a Google Calendar": abre Google con el evento precargado; la persona decide si lo guarda. */
    public function enlaceGoogleCalendar(): string
    {
        $i = $this->intervalo();

        if ($i['todoElDia']) {
            $fechas = $i['inicio']->format('Ymd') . '/' . $i['fin']->format('Ymd');
        } else {
            $fechas = $i['inicio']->format('Ymd\THis') . '/' . $i['fin']->format('Ymd\THis');
        }

        return 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action'  => 'TEMPLATE',
            'text'    => $this->titulo,
            'dates'   => $fechas,
            'ctz'     => self::zonaHoraria(),
            'details' => trim(($this->descripcion ? $this->descripcion . "\n\n" : '') . (self::tiposLabels()[$this->tipo] ?? '')),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function scopeVigentes($query)
    {
        return $query->where('fecha_inicio', '>=', now()->toDateString())
                     ->where('activo', true);
    }

    public function scopeDelAnio($query, int $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeParaRol($query, string $rol)
    {
        return $query->where(function ($q) use ($rol) {
            $q->where('aplica_a', 'todos')
              ->orWhere('aplica_a', $rol);
        });
    }

    public function getEsPuntualAttribute(): bool
    {
        return is_null($this->fecha_fin);
    }

    public function getDiasRestantesAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->fecha_inicio, false));
    }

    public static function tiposLabels(): array
    {
        return [
            'entrega_notas'  => 'Entrega de Notas',
            'examen'         => 'Examen',
            'suspension'     => 'Suspensión',
            'inicio_periodo' => 'Inicio de Período',
            'fin_periodo'    => 'Fin de Período',
            'actividad'      => 'Actividad',
            'feriado'        => 'Feriado',
            'reunion'        => 'Reunión',
            'otro'           => 'Otro',
        ];
    }
}
