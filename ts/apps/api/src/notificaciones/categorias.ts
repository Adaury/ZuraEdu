/**
 * Catálogo de notificaciones, copiado de `App\Models\Notificacion` (constantes ICONOS, TIPO_CATEGORIA y CATEGORIAS).
 * La prueba `categorias.spec.ts` lee el archivo PHP y compara, así que si alguien cambia el catálogo en Laravel sin
 * actualizar este archivo, la prueba falla en lugar de dejar que las dos implementaciones se desalineen en silencio.
 */

/** tipo → icono Bootstrap que la campanita del frontend muestra. */
export const ICONOS: Readonly<Record<string, string>> = {
  nueva_nota: 'bi-journal-check',
  ausencia: 'bi-calendar-x',
  comunicado: 'bi-megaphone',
  observacion: 'bi-chat-square-text',
  alerta: 'bi-exclamation-triangle',
  horario: 'bi-calendar-week',
  recursos: 'bi-folder-fill',
  planificacion: 'bi-journal-text',
  general: 'bi-bell',
  zura_tarea: 'bi-pencil-fill',
  zura_calificado: 'bi-check-circle-fill',
  zura_devuelto: 'bi-arrow-return-left',
  zura_quiz: 'bi-clipboard-check-fill',
  zura_anuncio: 'bi-megaphone-fill',
  zura_material: 'bi-book-fill',
  zura_boletin: 'bi-file-earmark-text',
  boletin: 'bi-file-earmark-text',
  asistencia: 'bi-calendar-check',
  carnet_acceso: 'bi-person-badge-fill',
  pago: 'bi-cash-coin',
  academica: 'bi-mortarboard-fill',
  cumpleanos: 'bi-balloon-fill',
  solicitud: 'bi-inbox-fill',
  info: 'bi-info-circle-fill',
};

/** tipo → categoría. Un tipo desconocido cae en 'sistema' (nunca se pierde una notificación). */
export const TIPO_CATEGORIA: Readonly<Record<string, string>> = {
  academica: 'academico',
  nueva_nota: 'academico',
  boletin: 'academico',
  zura_boletin: 'academico',
  planificacion: 'academico',
  recursos: 'academico',
  horario: 'academico',
  zura_tarea: 'zuraclass',
  zura_quiz: 'zuraclass',
  zura_anuncio: 'zuraclass',
  zura_material: 'zuraclass',
  zura_calificado: 'zuraclass',
  zura_devuelto: 'zuraclass',
  comunicado: 'comunicacion',
  cumpleanos: 'comunicacion',
  alerta: 'alertas',
  observacion: 'alertas',
  ausencia: 'alertas',
  asistencia: 'alertas',
  carnet_acceso: 'alertas',
  pago: 'pagos',
  solicitud: 'operacion',
  info: 'operacion',
  general: 'sistema',
};

/** Nombres de todas las categorías. */
export const CATEGORIAS: readonly string[] = ['academico', 'zuraclass', 'comunicacion', 'alertas', 'pagos', 'operacion', 'sistema'];

/** Categorías cuyo in-app la institución NO puede apagar (hoy solo 'sistema': avisos críticos de cuenta). */
export const CATEGORIAS_INAPP_BLOQUEADAS: ReadonlySet<string> = new Set(['sistema']);

export const categoriaDe = (tipo: string): string => TIPO_CATEGORIA[tipo] ?? 'sistema';
export const iconoDe = (tipo: string): string => ICONOS[tipo] ?? 'bi-bell';
