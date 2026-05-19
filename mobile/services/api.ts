import axios from 'axios'
import * as SecureStore from 'expo-secure-store'

// Cambia esta URL al IP de tu servidor en desarrollo
// Emulador Android: http://10.0.2.2:8000
// Dispositivo físico: http://TU_IP_LOCAL:8000
export const API_BASE = process.env.EXPO_PUBLIC_API_URL ?? 'http://10.0.2.2:8000'

export const api = axios.create({
  baseURL: `${API_BASE}/api/v1`,
  timeout: 15000,
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
})

// Adjuntar token automáticamente
api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Manejo global de errores 401
api.interceptors.response.use(
  (r) => r,
  async (error) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync('auth_token')
      await SecureStore.deleteItemAsync('auth_user')
    }
    return Promise.reject(error)
  }
)

// ── Auth ──────────────────────────────────────────────────────────────────────
export const authApi = {
  login:  (email: string, password: string) =>
    api.post('/auth/login', { email, password }),
  logout: () => api.post('/auth/logout'),
  me:     () => api.get('/auth/me'),
  changePassword: (data: { current_password: string; new_password: string; new_password_confirmation: string }) =>
    api.patch('/auth/change-password', data),
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
export const dashboardApi = {
  index: () => api.get('/dashboard'),
}

// ── Calificaciones ────────────────────────────────────────────────────────────
export const calificacionesApi = {
  index:         () => api.get('/calificaciones'),
  hijo: (id: number) => api.get(`/calificaciones/hijo/${id}`),
}

// ── Asistencia ────────────────────────────────────────────────────────────────
export const asistenciaApi = {
  index:              () => api.get('/asistencia'),
  hijo: (id: number)  => api.get(`/asistencia/hijo/${id}`),
}

// ── Horario ───────────────────────────────────────────────────────────────────
export const horarioApi = {
  index:              () => api.get('/horario'),
  hijo: (id: number)  => api.get(`/horario/hijo/${id}`),
}

// ── Notificaciones ────────────────────────────────────────────────────────────
export const notificacionesApi = {
  index:              () => api.get('/notificaciones'),
  marcar: (id: number)=> api.patch(`/notificaciones/${id}/leer`),
  marcarTodas:        () => api.post('/notificaciones/leer-todas'),
}

// ── Comunicados ───────────────────────────────────────────────────────────────
export const comunicadosApi = {
  index:                   () => api.get('/comunicados'),
  show:   (id: number)     => api.get(`/comunicados/${id}`),
  // Comunicados internos (comint) del docente
  comint:                  () => api.get('/docente/comint'),
  marcarLeido: (id: number)=> api.post(`/docente/comint/${id}/leer`),
}

// ── Calendario ────────────────────────────────────────────────────────────────
export const calendarioApi = {
  index: (params?: { desde?: string; hasta?: string }) => api.get('/calendario', { params }),
}

// ── Pagos ─────────────────────────────────────────────────────────────────────
export const pagosApi = {
  index:              () => api.get('/pagos'),
  hijo: (id: number)  => api.get(`/pagos/hijo/${id}`),
}

// ── Classroom ─────────────────────────────────────────────────────────────────
export const classroomApi = {
  index:                   () => api.get('/classroom'),
  materiales: (id: number) => api.get(`/classroom/${id}/materiales`),
}

// ── Docente ───────────────────────────────────────────────────────────────────
export const docenteApi = {
  grupos:                          () => api.get('/docente/grupos'),
  consultarAsistencia: (id: number)=> api.get(`/docente/asistencia/${id}`),
  registrarAsistencia: (data: any) => api.post('/docente/asistencia', data),
  calificaciones: (asignacionId: number) => api.get(`/docente/calificaciones/${asignacionId}`),
  // Observaciones
  observaciones: (asignacionId: number) => api.get('/docente/observaciones', { params: { asignacion_id: asignacionId } }),
  storeObservacion: (data: { asignacion_id: number; estudiante_id: number; tipo: string; texto: string; privada?: boolean }) =>
    api.post('/docente/observaciones', data),
  // Tareas docente
  tareasDocente: (asignacionId: number) => api.get('/docente/tareas', { params: { asignacion_id: asignacionId } }),
  storeTarea: (data: { asignacion_id: number; titulo: string; tipo: string; fecha_limite: string; descripcion?: string; puntos_valor?: number }) =>
    api.post('/docente/tareas', data),
  entregasTarea: (tareaId: number)  => api.get(`/docente/tareas/${tareaId}/entregas`),
  calificarEntrega: (tareaId: number, data: { estudiante_id: number; estado: string; calificacion?: number | null; notas_docente?: string }) =>
    api.patch(`/docente/tareas/${tareaId}/calificar`, data),
  // Conducta
  conducta: (asignacionId: number, periodoId?: number) =>
    api.get('/docente/conducta', { params: { asignacion_id: asignacionId, ...(periodoId ? { periodo_id: periodoId } : {}) } }),
  guardarConducta: (data: { asignacion_id: number; matricula_id: number; periodo_id: number; puntualidad?: number | null; participacion?: number | null; respeto?: number | null; trabajo_equipo?: number | null; responsabilidad?: number | null; orden?: number | null; observaciones?: string }) =>
    api.post('/docente/conducta', data),
}

// ── Mensajes ─────────────────────────────────────────────────────────────────
export const mensajesApi = {
  index:          () => api.get('/mensajes'),
  destinatarios:  () => api.get('/mensajes/destinatarios'),
  show:     (id: number)  => api.get(`/mensajes/${id}`),
  store:    (data: { asunto: string; cuerpo: string; destinatario_ids: number[] }) =>
    api.post('/mensajes', data),
}

// ── Risk Score ────────────────────────────────────────────────────────────────
export const riesgoApi = {
  miScore:              () => api.get('/riesgo/mi-score'),
  hijo: (id: number)   => api.get(`/riesgo/hijo/${id}`),
}

// ── Gamificación ─────────────────────────────────────────────────────────────
export const gamificacionApi = {
  misPuntos:                       () => api.get('/gamificacion/mis-puntos'),
  hijo: (id: number)               => api.get(`/gamificacion/hijo/${id}`),
  grupo: (asignacionId: number)    => api.get(`/gamificacion/grupo/${asignacionId}`),
  asignar: (asignacionId: number, data: {
    matricula_id: number
    concepto: string
    categoria: string
    puntos: number
    fecha: string
  })                               => api.post(`/gamificacion/grupo/${asignacionId}/asignar`, data),
}

// ── Tutor IA ──────────────────────────────────────────────────────────────────
export const tutorApi = {
  chat: (message: string, history: { role: 'user' | 'assistant'; content: string }[] = []) =>
    api.post('/ai/chat', { message, history }),
}

// ── Encuestas ─────────────────────────────────────────────────────────────────
export const encuestasApi = {
  index:    ()                                  => api.get('/encuestas'),
  show:     (id: number)                        => api.get(`/encuestas/${id}`),
  responder: (id: number, respuestas: Record<number, {
    opcion_id?: number; escala_valor?: number; respuesta_texto?: string
  }>) => api.post(`/encuestas/${id}/responder`, { respuestas }),
}

// ── Tareas ────────────────────────────────────────────────────────────────────
export const tareasApi = {
  index:         ()              => api.get('/tareas'),
  hijo: (id: number) => api.get(`/tareas/hijo/${id}`),
}

// ── Cafetería ─────────────────────────────────────────────────────────────────
export const cafeteriaApi = {
  saldo:         ()              => api.get('/cafeteria/saldo'),
  saldoHijo: (id: number) => api.get(`/cafeteria/saldo-hijo/${id}`),
}

// ── Transporte ────────────────────────────────────────────────────────────────
export const transporteApi = {
  miRuta:        ()              => api.get('/transporte/mi-ruta'),
  rutaHijo: (id: number) => api.get(`/transporte/ruta-hijo/${id}`),
}

// ── Documentos ────────────────────────────────────────────────────────────────
export const documentosApi = {
  info:              ()              => api.get('/documentos/info'),
  infoHijo: (id: number) => api.get(`/documentos/info-hijo/${id}`),
}

// ── Observaciones (Estudiante y Representante) ────────────────────────────────
export const observacionesApi = {
  index:              () => api.get('/observaciones'),
  hijo: (id: number) => api.get(`/observaciones/hijo/${id}`),
}

// ── Solicitudes ───────────────────────────────────────────────────────────────
export const solicitudesApi = {
  index: () => api.get('/solicitudes'),
  store: (data: { tipo: string; asunto: string; descripcion: string; fecha_evento?: string; estudiante_id?: number }) =>
    api.post('/solicitudes', data),
  show: (id: number) => api.get(`/solicitudes/${id}`),
}
