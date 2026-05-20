import { Tabs } from 'expo-router'
import { Ionicons } from '@expo/vector-icons'
import { useQuery } from '@tanstack/react-query'
import { Colors } from '../../constants/Colors'
import { mensajesApi, notificacionesApi } from '../../services/api'

const ACCENT = Colors.roles.admin

export default function AdminLayout() {
  const { data: msgs }   = useQuery({ queryKey: ['mensajes-admin'],   queryFn: () => mensajesApi.index().then(r => r.data),       staleTime: 30_000, refetchInterval: 60_000 })
  const { data: notifs } = useQuery({ queryKey: ['notificaciones'],    queryFn: () => notificacionesApi.index().then(r => r.data), staleTime: 30_000, refetchInterval: 60_000 })

  const msgBadge   = (msgs?.no_leidos   ?? 0) > 0 ? msgs!.no_leidos   : undefined
  const notifBadge = (notifs?.no_leidas ?? 0) > 0 ? notifs!.no_leidas : undefined

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor:   ACCENT,
        tabBarInactiveTintColor: Colors.muted,
        tabBarStyle:             { backgroundColor: '#fff', borderTopColor: Colors.border, paddingBottom: 6, height: 60 },
        tabBarLabelStyle:        { fontSize: 11, fontWeight: '600' },
        headerStyle:             { backgroundColor: ACCENT },
        headerTintColor:         '#fff',
        headerTitleStyle:        { fontWeight: '800', fontSize: 17 },
      }}
    >
      <Tabs.Screen name="index"          options={{ title: 'Inicio',         tabBarIcon: ({ color, size }) => <Ionicons name="home"                    size={size} color={color} /> }} />
      <Tabs.Screen name="mensajes"       options={{ title: 'Mensajes',        tabBarIcon: ({ color, size }) => <Ionicons name="mail-outline"            size={size} color={color} />, tabBarBadge: msgBadge }} />
      <Tabs.Screen name="notificaciones" options={{ title: 'Notificaciones',  tabBarIcon: ({ color, size }) => <Ionicons name="notifications-outline"   size={size} color={color} />, tabBarBadge: notifBadge }} />
      <Tabs.Screen name="perfil"         options={{ title: 'Perfil',          tabBarIcon: ({ color, size }) => <Ionicons name="person-circle-outline"   size={size} color={color} /> }} />
      <Tabs.Screen name="comunicados"    options={{ title: 'Comunicados',     tabBarIcon: ({ color, size }) => <Ionicons name="megaphone-outline"       size={size} color={color} />, href: null }} />
      <Tabs.Screen name="calendario"     options={{ title: 'Calendario',      tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline"        size={size} color={color} />, href: null }} />
    </Tabs>
  )
}
