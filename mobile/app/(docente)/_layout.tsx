import { Tabs } from 'expo-router'
import { Ionicons } from '@expo/vector-icons'
import { useQuery } from '@tanstack/react-query'
import { Colors } from '../../constants/Colors'
import { mensajesApi, notificacionesApi } from '../../services/api'

export default function DocenteLayout() {
  const color = Colors.roles.docente

  const { data: msgs }   = useQuery({ queryKey: ['mensajes-docente'],   queryFn: () => mensajesApi.index().then(r => r.data),          staleTime: 30_000, refetchInterval: 60_000 })
  const { data: notifs } = useQuery({ queryKey: ['notificaciones'],      queryFn: () => notificacionesApi.index().then(r => r.data),    staleTime: 30_000, refetchInterval: 60_000 })

  const msgBadge   = (msgs?.no_leidos   ?? 0) > 0 ? msgs!.no_leidos   : undefined
  const notifBadge = (notifs?.no_leidas ?? 0) > 0 ? notifs!.no_leidas : undefined

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor:   color,
        tabBarInactiveTintColor: Colors.muted,
        tabBarStyle:             { backgroundColor: '#fff', borderTopColor: Colors.border, paddingBottom: 6, height: 60 },
        tabBarLabelStyle:        { fontSize: 11, fontWeight: '600' },
        headerStyle:             { backgroundColor: Colors.primary },
        headerTintColor:         '#fff',
        headerTitleStyle:        { fontWeight: '800', fontSize: 17 },
      }}
    >
      <Tabs.Screen name="index"          options={{ title: 'Inicio',     tabBarIcon: ({ color, size }) => <Ionicons name="home"            size={size} color={color} /> }} />
      <Tabs.Screen name="classroom"      options={{ title: 'Mis Aulas',  tabBarIcon: ({ color, size }) => <Ionicons name="easel"           size={size} color={color} /> }} />
      <Tabs.Screen name="grupos"         options={{ title: 'Mis Grupos', tabBarIcon: ({ color, size }) => <Ionicons name="people"          size={size} color={color} /> }} />
      <Tabs.Screen name="asistencia"     options={{ title: 'Asistencia', tabBarIcon: ({ color, size }) => <Ionicons name="calendar-number" size={size} color={color} /> }} />
      <Tabs.Screen name="qr"             options={{ title: 'QR Scan',    tabBarIcon: ({ color, size }) => <Ionicons name="qr-code"         size={size} color={color} /> }} />
      <Tabs.Screen name="calificaciones" options={{ title: 'Notas',      tabBarIcon: ({ color, size }) => <Ionicons name="bar-chart"       size={size} color={color} />, href: null }} />
      <Tabs.Screen name="horario"        options={{ title: 'Horario',    tabBarIcon: ({ color, size }) => <Ionicons name="time"            size={size} color={color} />, href: null }} />
      <Tabs.Screen name="mensajes"       options={{ title: 'Mensajes',   tabBarIcon: ({ color, size }) => <Ionicons name="mail-outline"    size={size} color={color} />, href: null, tabBarBadge: msgBadge }} />
      <Tabs.Screen name="comunicados"    options={{ title: 'Comint',     tabBarIcon: ({ color, size }) => <Ionicons name="mail"            size={size} color={color} />, href: null }} />
      <Tabs.Screen name="gamificacion"   options={{ title: 'Gamificación',    tabBarIcon: ({ color, size }) => <Ionicons name="trophy"                   size={size} color={color} />, href: null }} />
      <Tabs.Screen name="calendario"     options={{ title: 'Calendario',      tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline"          size={size} color={color} />, href: null }} />
      <Tabs.Screen name="notificaciones" options={{ title: 'Notificaciones',  tabBarIcon: ({ color, size }) => <Ionicons name="notifications-outline"     size={size} color={color} />, href: null, tabBarBadge: notifBadge }} />
      <Tabs.Screen name="perfil"         options={{ title: 'Perfil',          tabBarIcon: ({ color, size }) => <Ionicons name="person-circle-outline"     size={size} color={color} />, href: null }} />
      <Tabs.Screen name="observaciones"  options={{ title: 'Observaciones',   tabBarIcon: ({ color, size }) => <Ionicons name="chatbubble-outline"          size={size} color={color} />, href: null }} />
      <Tabs.Screen name="tareas"         options={{ title: 'Tareas',          tabBarIcon: ({ color, size }) => <Ionicons name="checkbox-outline"            size={size} color={color} />, href: null }} />
      <Tabs.Screen name="conducta"         options={{ title: 'Conducta',        tabBarIcon: ({ color, size }) => <Ionicons name="shield-half-outline"    size={size} color={color} />, href: null }} />
      <Tabs.Screen name="plan-evaluacion"  options={{ title: 'Plan Eval.',     tabBarIcon: ({ color, size }) => <Ionicons name="document-text-outline" size={size} color={color} />, href: null }} />
      <Tabs.Screen name="instrumentos"     options={{ title: 'Instrumentos',   tabBarIcon: ({ color, size }) => <Ionicons name="grid-outline"          size={size} color={color} />, href: null }} />
      <Tabs.Screen name="riesgo"           options={{ title: 'Riesgo',         tabBarIcon: ({ color, size }) => <Ionicons name="analytics-outline"     size={size} color={color} />, href: null }} />
    </Tabs>
  )
}
