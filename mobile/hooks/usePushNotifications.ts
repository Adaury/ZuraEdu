import { useEffect, useRef } from 'react'
import * as Notifications from 'expo-notifications'
import { Platform } from 'react-native'
import { useRouter } from 'expo-router'
import { authApi } from '../services/api'
import { useAuth } from '../context/AuthContext'

// Configuración del comportamiento de notificaciones en primer plano
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge:  true,
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
      // Canal Android (necesario antes de pedir permisos)
      if (Platform.OS === 'android') {
        await Notifications.setNotificationChannelAsync('default', {
          name:              'ZuraEdu',
          importance:        Notifications.AndroidImportance.MAX,
          vibrationPattern:  [0, 200, 150, 200],
          lightColor:        '#1e3a6e',
        })
      }

      // Solicitar permisos
      const { status: existing } = await Notifications.getPermissionsAsync()
      const { status: final } = existing !== 'granted'
        ? await Notifications.requestPermissionsAsync()
        : { status: existing }

      if (final !== 'granted' || !active) return

      // Obtener token Expo Push
      try {
        const result = await Notifications.getExpoPushTokenAsync()
        const token  = result.data
        if (!active) return

        registeredToken.current = token
        setPushToken(token)
        const platform = Platform.OS === 'ios' ? 'ios' : Platform.OS === 'android' ? 'android' : 'unknown'
        await authApi.registerPushToken(token, platform)
      } catch {
        // Expo Go sin EAS projectId, o simulator — no es un error crítico
      }
    }

    setup()

    // Listener: notificación recibida con la app abierta
    receivedRef.current = Notifications.addNotificationReceivedListener(() => {
      // La badge se actualiza automáticamente con shouldSetBadge: true
    })

    // Listener: usuario toca la notificación → navegar a la pantalla correcta
    responseRef.current = Notifications.addNotificationResponseReceivedListener(response => {
      const data  = response.notification.request.content.data as Record<string, any>
      const route = data?.route ?? routeFromRole(primaryRole)
      try { router.push(route as any) } catch {}
    })

    return () => {
      active = false
      receivedRef.current?.remove()
      responseRef.current?.remove()
    }
  }, [primaryRole])

}


function routeFromRole(role: string | null): string {
  if (role === 'Docente')         return '/(docente)/notificaciones'
  if (role === 'Representante')   return '/(padre)/notificaciones'
  return '/(estudiante)/notificaciones'
}
