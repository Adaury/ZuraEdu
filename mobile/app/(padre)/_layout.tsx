import { Tabs } from 'expo-router'
import { Ionicons } from '@expo/vector-icons'
import { Colors } from '../../constants/Colors'

export default function PadreLayout() {
  const color = Colors.roles.padre

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
      <Tabs.Screen name="index"       options={{ title: 'Inicio',     tabBarIcon: ({ color, size }) => <Ionicons name="home"             size={size} color={color} /> }} />
      <Tabs.Screen name="classroom"   options={{ title: 'Classroom',  tabBarIcon: ({ color, size }) => <Ionicons name="easel"            size={size} color={color} /> }} />
      <Tabs.Screen name="notas"       options={{ title: 'Notas',      tabBarIcon: ({ color, size }) => <Ionicons name="bar-chart"        size={size} color={color} /> }} />
      <Tabs.Screen name="asistencia"  options={{ title: 'Asistencia', tabBarIcon: ({ color, size }) => <Ionicons name="calendar"         size={size} color={color} /> }} />
      <Tabs.Screen name="tutor"       options={{ title: 'Asistente',  tabBarIcon: ({ color, size }) => <Ionicons name="sparkles"         size={size} color={color} /> }} />
      <Tabs.Screen name="riesgo"      options={{ title: 'Situación',  tabBarIcon: ({ color, size }) => <Ionicons name="shield-checkmark" size={size} color={color} />, href: null }} />
      <Tabs.Screen name="pagos"       options={{ title: 'Pagos',      tabBarIcon: ({ color, size }) => <Ionicons name="card"             size={size} color={color} />, href: null }} />
      <Tabs.Screen name="horario"     options={{ title: 'Horario',    tabBarIcon: ({ color, size }) => <Ionicons name="time"             size={size} color={color} />, href: null }} />
      <Tabs.Screen name="mensajes"    options={{ title: 'Mensajes',   tabBarIcon: ({ color, size }) => <Ionicons name="mail"             size={size} color={color} />, href: null }} />
      <Tabs.Screen name="comunicados" options={{ title: 'Noticias',   tabBarIcon: ({ color, size }) => <Ionicons name="megaphone"        size={size} color={color} />, href: null }} />
      <Tabs.Screen name="mis-puntos"  options={{ title: 'Logros',     tabBarIcon: ({ color, size }) => <Ionicons name="trophy"           size={size} color={color} />, href: null }} />
      <Tabs.Screen name="encuestas"   options={{ title: 'Encuestas',  tabBarIcon: ({ color, size }) => <Ionicons name="clipboard-outline"  size={size} color={color} />, href: null }} />
      <Tabs.Screen name="tareas"      options={{ title: 'Tareas',     tabBarIcon: ({ color, size }) => <Ionicons name="checkbox-outline"   size={size} color={color} />, href: null }} />
      <Tabs.Screen name="cafeteria"   options={{ title: 'Cafetería',  tabBarIcon: ({ color, size }) => <Ionicons name="cafe-outline"       size={size} color={color} />, href: null }} />
      <Tabs.Screen name="transporte"  options={{ title: 'Transporte', tabBarIcon: ({ color, size }) => <Ionicons name="bus-outline"           size={size} color={color} />, href: null }} />
      <Tabs.Screen name="documentos"    options={{ title: 'Documentos',    tabBarIcon: ({ color, size }) => <Ionicons name="folder-open-outline"      size={size} color={color} />, href: null }} />
      <Tabs.Screen name="solicitudes"   options={{ title: 'Solicitudes',   tabBarIcon: ({ color, size }) => <Ionicons name="document-text-outline"    size={size} color={color} />, href: null }} />
      <Tabs.Screen name="calendario"    options={{ title: 'Calendario',    tabBarIcon: ({ color, size }) => <Ionicons name="calendar-outline"          size={size} color={color} />, href: null }} />
      <Tabs.Screen name="notificaciones"options={{ title: 'Notificaciones',tabBarIcon: ({ color, size }) => <Ionicons name="notifications-outline"     size={size} color={color} />, href: null }} />
      <Tabs.Screen name="perfil"        options={{ title: 'Perfil',        tabBarIcon: ({ color, size }) => <Ionicons name="person-circle-outline"     size={size} color={color} />, href: null }} />
      <Tabs.Screen name="observaciones"   options={{ title: 'Observaciones',   tabBarIcon: ({ color, size }) => <Ionicons name="chatbubble-outline"     size={size} color={color} />, href: null }} />
      <Tabs.Screen name="conducta"        options={{ title: 'Conducta',        tabBarIcon: ({ color, size }) => <Ionicons name="shield-half-outline"    size={size} color={color} />, href: null }} />
      <Tabs.Screen name="plan-evaluacion"      options={{ title: 'Plan Evaluación', tabBarIcon: ({ color, size }) => <Ionicons name="document-text-outline"  size={size} color={color} />, href: null }} />
      <Tabs.Screen name="resultados-evaluacion" options={{ title: 'Evaluaciones',    tabBarIcon: ({ color, size }) => <Ionicons name="ribbon-outline"          size={size} color={color} />, href: null }} />
    </Tabs>
  )
}
