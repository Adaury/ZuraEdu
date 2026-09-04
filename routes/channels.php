<?php

use App\Models\Asignacion;
use App\Models\ClaseVirtual;
use App\Models\Matricula;
use Illuminate\Support\Facades\Broadcast;

// Roles con capacidad administrativa/de monitoreo — los nombres reales del
// proyecto (ver RolesSeeder), no los genéricos 'SuperAdmin'/'Admin'/
// 'Coordinator' que se usaban antes en este archivo y que no coinciden con
// ningún rol real (Spatie compara el string exacto, así que esas ramas nunca
// se cumplían para ningún usuario — fallaba cerrado, pero dejaba a los
// admins/coordinadores reales sin acceso a estos canales).
// define() con guardia (no const): este archivo se vuelve a cargar en cada
// arranque de la app durante los tests, y un const de nivel de archivo choca
// la segunda vez con "already defined".
if (! defined('ROLES_ADMIN_CAPACES')) {
    define('ROLES_ADMIN_CAPACES', [
        'super_admin', 'Administrador', 'Director',
        'Coordinador Académico', 'Coordinador Primer Ciclo', 'Coordinador Segundo Ciclo',
    ]);
}

// Convención de nombres: los patrones de este archivo NO llevan el prefijo
// private-/presence-. Laravel se lo quita al nombre de canal entrante antes
// de compararlo contra los patrones registrados (ver
// Broadcaster::normalizeChannelName + verifyUserCanAccessChannel), así que
// un patrón registrado CON el prefijo nunca puede matchear — hallazgo de
// auditoría 2026-09-03: los 9 canales de este archivo estaban registrados
// con el prefijo incluido (p.ej. 'private-user.{id}'), lo que en teoría los
// dejaba inutilizables. En la práctica "funcionaban" porque resources/js/
// echo.js cometía el error espejo del lado cliente (Echo.private() ya
// antepone 'private-' por su cuenta, y el código llamaba
// .private('private-user.'+id), así que el canal real en el cable quedaba
// 'private-private-user.5' — Laravel le quita un solo prefijo y el patrón
// con el prefijo baked-in volvía a matchear). Ambos lados se corrigieron
// juntos: aquí los patrones ya no llevan el prefijo, y echo.js ya no lo
// antepone manualmente. Al escribir un canal nuevo, seguir esta convención:
// Broadcast::channel('recurso.{id}', ...) + cliente Echo.private('recurso.'+id).
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ── 2. Canal del tenant — admins y coordinadores ──────────────────────────────
// Usado para DashboardActualizado y eventos de gestión global.
Broadcast::channel('tenant.{tenantId}', function ($user, $tenantId) {
    if ((int) (tenant_id() ?? 0) !== (int) $tenantId) {
        return false;
    }
    return $user->hasAnyRole(ROLES_ADMIN_CAPACES);
});

// ── 3. Canal de classroom — docente o estudiante matriculado ─────────────────
// Usado para NewClassroomMessage, NuevoMaterialPublicado, ClassroomMeetingUpdated.
Broadcast::channel('classroom.{claseId}', function ($user, $claseId) {
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

    return $user->hasAnyRole(ROLES_ADMIN_CAPACES);
});

// ── 4. Canal de grupo — calificaciones y eventos del grupo ───────────────────
// Usado para CalificacionesPublicadas.
Broadcast::channel('grupo.{grupoId}', function ($user, $grupoId) {
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

    return $user->hasAnyRole(ROLES_ADMIN_CAPACES);
});

// ── 5. Canal personal del docente — confirmaciones inmediatas ────────────────
// Usado para AsistenciaRegistrada y otras confirmaciones operativas del docente.
// Admins también pueden escuchar para monitoreo.
Broadcast::channel('docente.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId
        || $user->hasAnyRole(ROLES_ADMIN_CAPACES);
});

// ── 6. Presence channel — live classroom ─────────────────────────────────────
// Devuelve datos del usuario para mostrar quién está en línea en la clase.
// Nombre distinto a 'classroom.{claseId}' (canal 3) a propósito: aunque
// private-classroom.5 y presence-classroom.5 son nombres de canal distintos
// en el cable (protocolo Pusher), Laravel los guarda en el mismo array
// $channels indexado solo por el patrón — si ambos usaran el mismo patrón
// 'classroom.{claseId}' tras quitarle el prefijo, el segundo registro
// pisaría al primero silenciosamente.
Broadcast::channel('classroom-presence.{claseId}', function ($user, $claseId) {
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

    if (! $esDocente && ! $esEstudiante && ! $user->hasAnyRole(ROLES_ADMIN_CAPACES)) {
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
Broadcast::channel('tenant.{tenantId}.chat', function ($user, $tenantId) {
    return (int) (tenant_id() ?? 0) === (int) $tenantId;
});

// ── 8. Notificaciones tenant-wide ─────────────────────────────────────────────
// Canal de difusión masiva para anuncios del admin a todos los usuarios del tenant.
Broadcast::channel('tenant.{tenantId}.notifications', function ($user, $tenantId) {
    return (int) (tenant_id() ?? 0) === (int) $tenantId;
});

// Canal privado de soporte — solo admins/coordinadores del tenant pueden escuchar mensajes entrantes.
Broadcast::channel('tenant.{tenantId}.support', function ($user, $tenantId) {
    if ((int) (tenant_id() ?? 0) !== (int) $tenantId) return false;
    return $user->hasAnyRole(ROLES_ADMIN_CAPACES);
});

// ── 9. Monitoreo de Carnet+ — escaneos en vivo ────────────────────────────────
// CarnetEscaneado (nombre, foto, hora de entrada/salida) usaba un Channel
// público sin autenticación — cualquiera con la app key pública de Reverb
// podía suscribirse sin sesión y ver en vivo quién entra/sale, con foto.
Broadcast::channel('carnet.{tenantId}', function ($user, $tenantId) {
    if ((int) (tenant_id() ?? 0) !== (int) $tenantId) return false;
    return $user->can('ver-servicios');
});

// ── Legacy: modelo estándar de Laravel ───────────────────────────────────────
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
