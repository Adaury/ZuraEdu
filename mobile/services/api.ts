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
  index: () => api.get('/calendario'),
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
  misPuntos: () => api.get('/gamificacion/mis-puntos'),
}

// ── Tutor IA ──────────────────────────────────────────────────────────────────
export const tutorApi = {
  chat: (message: string, history: { role: 'user' | 'assistant'; content: string }[] = []) =>
    api.post('/ai/chat', { message, history }),
}
