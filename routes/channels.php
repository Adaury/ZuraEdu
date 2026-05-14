<?php

use App\Models\Asignacion;
use App\Models\ClaseVirtual;
use App\Models\Matricula;
use Illuminate\Support\Facades\Broadcast;

// ── 1. Canal personal de notificaciones ──────────────────────────────────────
// Cada usuario escucha su propio canal; solo él puede suscribirse.
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ── 2. Canal del tenant — admins y coordinadores ──────────────────────────────
// Usado para DashboardActualizado y eventos de gestión global.
Broadcast::channel('private-tenant.{tenantId}', function ($user, $tenantId) {
    if ((int) (tenant_id() ?? 0) !== (int) $tenantId) {
        return false;
    }
    return $user->hasAnyRole(['SuperAdmin', 'Admin', 'Coordinator']);
});

// ── 3. Canal de classroom — docente o estudiante matriculado ─────────────────
// Usado para NewClassroomMessage, NuevoMaterialPublicado, ClassroomMeetingUpdated.
Broadcast::channel('private-classroom.{claseId}', function ($user, $claseId) {
    $clase = ClaseVirtual::with('asignacion.docente')->find($claseId);
    if (! $clase) {
        return false;
    }

    // Docente titular de la clase
    if ($clase->asignacion?->docente?->user_id === $user->id) {
        return true;
    }

    // Estudiante matriculado en el grupo de esta clase
    $grupoId = $clase->asignacion?->grupo_id;
    if ($grupoId && $user->estudiante) {
        return Matricula::where('grupo_id', $grupoId)
            ->where('estudiante_id', $user->estudiante->id)
            ->exists();
    }

    return $user->hasAnyRole(['SuperAdmin', 'Admin']);
});

// ── 4. Canal de grupo — calificaciones y eventos del grupo ───────────────────
// Usado para CalificacionesPublicadas.
Broadcast::channel('private-grupo.{grupoId}', function ($user, $grupoId) {
    // Docente con al menos una asignación en este grupo
    if ($user->docente) {
        if (Asignacion::where('grupo_id', $grupoId)
            ->where('docente_id', $user->docente->id)
            ->exists()) {
            return true;
        }
    }

    // Estudiante matriculado en el grupo
    if ($user->estudiante) {
        return Matricula::where('grupo_id', $grupoId)
            ->where('estudiante_id', $user->estudiante->id)
            ->exists();
    }

    return $user->hasAnyRole(['SuperAdmin', 'Admin']);
});

// ── 5. Canal personal del docente — confirmaciones inmediatas ────────────────
// Usado para AsistenciaRegistrada y otras confirmaciones operativas del docente.
// Admins también pueden escuchar para monitoreo.
Broadcast::channel('private-docente.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId
        || $user->hasAnyRole(['SuperAdmin', 'Admin']);
});

// ── 6. Presence channel — live classroom ─────────────────────────────────────
// Devuelve datos del usuario para mostrar quién está en línea en la clase.
Broadcast::channel('presence-classroom.{claseId}', function ($user, $claseId) {
    $clase = ClaseVirtual::with('asignacion.docente')->find($claseId);
    if (! $clase) {
        return false;
    }

    $esDocente    = $clase->asignacion?->docente?->user_id === $user->id;
    $grupoId      = $clase->asignacion?->grupo_id;
    $esEstudiante = $grupoId && $user->estudiante
        && Matricula::where('grupo_id', $grupoId)
            ->where('estudiante_id', $user->estudiante->id)
            ->exists();

    if (! $esDocente && ! $esEstudiante && ! $user->hasAnyRole(['SuperAdmin', 'Admin'])) {
        return false;
    }

    return [
        'id'     => $user->id,
        'nombre' => $user->name,
        'rol'    => $esDocente ? 'docente' : 'estudiante',
    ];
});

// ── 7. Chat interno del tenant ────────────────────────────────────────────────
// Cualquier usuario activo del tenant puede enviar y recibir mensajes.
Broadcast::channel('private-tenant.{tenantId}.chat', function ($user, $tenantId) {
    return (int) (tenant_id() ?? 0) === (int) $tenantId;
});

// ── 8. Notificaciones tenant-wide ─────────────────────────────────────────────
// Canal de difusión masiva para anuncios del admin a todos los usuarios del tenant.
Broadcast::channel('private-tenant.{tenantId}.notifications', function ($user, $tenantId) {
    return (int) (tenant_id() ?? 0) === (int) $tenantId;
});

// Canal privado de soporte — solo admins/coordinadores del tenant pueden escuchar mensajes entrantes.
Broadcast::channel('private-tenant.{tenantId}.support', function ($user, $tenantId) {
    if ((int) (tenant_id() ?? 0) !== (int) $tenantId) return false;
    return $user->hasAnyRole(['Admin', 'SuperAdmin', 'Coordinator', 'Director']);
});

// ── Legacy: modelo estándar de Laravel ───────────────────────────────────────
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
