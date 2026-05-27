import { useEffect, useRef } from 'react'
import * as Notifications from 'expo-notifications'
import { Platform } from 'react-native'
import { useRouter } from 'expo-router'
import { authApi } from '../services/api'
import { useAuth } from '../context/AuthContext'

// Configuración del comportamiento de notificaciones en primer plano
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert:  true,
    shouldShowBanner: true,
    shouldShowList:   true,
    shouldPlaySound:  true,
    shouldSetBadge:   true,
  }),
})

/** Hook para manejar push notifications. Llamar solo una vez en el layout raíz una vez logueado. */
export function usePushNotifications(primaryRole: string | null) {
  const router          = useRouter()
  const { setPushToken } = useAuth()
  const receivedRef     = useRef<Notifications.Subscription | null>(null)
  const responseRef     = useRef<Notifications.Subscription | null>(null)
  const registeredToken = useRef<string | null>(null)

  useEffect(() => {
    if (!primaryRole) return

    let active = true

    async function setup() {
      try {
        if (Platform.OS === 'android') {
          await Notifications.setNotificationChannelAsync('default', {
            name:             'ZuraEdu',
            importance:       Notifications.AndroidImportance.MAX,
            vibrationPattern: [0, 200, 150, 200],
            lightColor:       '#1e3a6e',
          })
        }

        const { status: existing } = await Notifications.getPermissionsAsync()
        const { status: final } = existing !== 'granted'
          ? await Notifications.requestPermissionsAsync()
          : { status: existing }

        if (final !== 'granted' || !active) return

        const result = await Notifications.getExpoPushTokenAsync()
        const token  = result.data
        if (!active) return

        registeredToken.current = token
        setPushToken(token)
        const platform = Platform.OS === 'ios' ? 'ios' : 'android'
        await authApi.registerPushToken(token, platform)
      } catch {
        // Expo Go / emulador sin soporte de notificaciones remotas — no es crítico
      }
    }

    setup()

    try {
      receivedRef.current = Notifications.addNotificationReceivedListener(() => {})
    } catch {}

    try {
      responseRef.current = Notifications.addNotificationResponseReceivedListener(response => {
        const data  = response.notification.request.content.data as Record<string, any>
        const tipo  = data?.tipo as string | undefined
        const route = data?.route ?? routeFromNotification(tipo, primaryRole)
        try { router.push(route as any) } catch {}
      })
    } catch {}

    return () => {
      active = false
      try { receivedRef.current?.remove() } catch {}
      try { responseRef.current?.remove() } catch {}
    }
  }, [primaryRole])

}


function routeFromNotification(tipo: string | undefined, role: string | null): string {
  const isAdmin = role === 'Administrador' || role === 'Director'

  const prefix = role === 'Docente'       ? '/(docente)'
               : role === 'Representante' ? '/(padre)'
               : isAdmin                  ? '/(admin)'
               : '/(estudiante)'

  if (!tipo) return `${prefix}/notificaciones`

  const map: Record<string, string> = {
    nueva_nota:         isAdmin ? `${prefix}/notificaciones` : `${prefix}/notas`,
    ausencia:           isAdmin ? `${prefix}/notificaciones` : `${prefix}/asistencia`,
    nueva_calificacion: role === 'Docente' ? '/(docente)/calificaciones'
                      : isAdmin           ? `${prefix}/notificaciones`
                      :                     `${prefix}/notas`,
    comunicado:         `${prefix}/comunicados`,
    nuevo_comunicado:   `${prefix}/comunicados`,
    mensaje:            `${prefix}/mensajes`,
    nuevo_mensaje:      `${prefix}/mensajes`,
    tarea:              isAdmin ? `${prefix}/notificaciones` : `${prefix}/tareas`,
    nueva_tarea:        isAdmin ? `${prefix}/notificaciones` : `${prefix}/tareas`,
    encuesta:           isAdmin ? `${prefix}/notificaciones` : `${prefix}/encuestas`,
    nueva_encuesta:     isAdmin ? `${prefix}/notificaciones` : `${prefix}/encuestas`,
    pago:               isAdmin ? `${prefix}/notificaciones` : `${prefix}/pagos`,
    pago_vencido:       isAdmin ? `${prefix}/notificaciones` : `${prefix}/pagos`,
    observacion:        isAdmin ? `${prefix}/notificaciones` : `${prefix}/observaciones`,
    gamificacion:       role === 'Docente' ? '/(docente)/gamificacion'
                      : isAdmin           ? `${prefix}/notificaciones`
                      :                     `${prefix}/mis-puntos`,
    puntos:             role === 'Docente' ? '/(docente)/gamificacion'
                      : isAdmin           ? `${prefix}/notificaciones`
                      :                     `${prefix}/mis-puntos`,
  }

  return map[tipo] ?? `${prefix}/notificaciones`
}
