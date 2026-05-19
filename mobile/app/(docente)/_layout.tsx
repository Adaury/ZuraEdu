import { Tabs } from 'expo-router'
import { Ionicons } from '@expo/vector-icons'
import { Colors } from '../../constants/Colors'

export default function DocenteLayout() {
  const color = Colors.roles.docente

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
      <Tabs.Screen name="mensajes"       options={{ title: 'Mensajes',   tabBarIcon: ({ color, size }) => <Ionicons name="mail-outline"    size={size} color={color} />, href: null }} />
      <Tabs.Screen name="comunicados"    options={{ title: 'Comint',     tabBarIcon: ({ color, size }) => <Ionicons name="mail"            size={size} color={color} />, href: null }} />
      <Tabs.Screen name="gamificacion"   options={{ title: 'Gamificación',  tabBarIcon: ({ color, size }) => <Ionicons name="trophy"                   size={size} color={color} />, href: null }} />
      <Tabs.Screen name="calendario"    options={{ title: 'Calendario',    tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline"          size={size} color={color} />, href: null }} />
      <Tabs.Screen name="notificaciones"options={{ title: 'Notificaciones',tabBarIcon: ({ color, size }) => <Ionicons name="notifications-outline"     size={size} color={color} />, href: null }} />
      <Tabs.Screen name="perfil"        options={{ title: 'Perfil',        tabBarIcon: ({ color, size }) => <Ionicons name="person-circle-outline"     size={size} color={color} />, href: null }} />
    </Tabs>
  )
}
