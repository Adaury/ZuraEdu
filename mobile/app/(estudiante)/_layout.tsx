import { Tabs } from 'expo-router'
import { Ionicons } from '@expo/vector-icons'
import { Colors } from '../../constants/Colors'

export default function EstudianteLayout() {
  const color = Colors.roles.estudiante

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
      <Tabs.Screen name="index"        options={{ title: 'Inicio',     tabBarIcon: ({ color, size }) => <Ionicons name="home"           size={size} color={color} /> }} />
      <Tabs.Screen name="notas"        options={{ title: 'Notas',      tabBarIcon: ({ color, size }) => <Ionicons name="bar-chart"      size={size} color={color} /> }} />
      <Tabs.Screen name="asistencia"   options={{ title: 'Asistencia', tabBarIcon: ({ color, size }) => <Ionicons name="calendar"       size={size} color={color} /> }} />
      <Tabs.Screen name="horario"      options={{ title: 'Horario',    tabBarIcon: ({ color, size }) => <Ionicons name="time"           size={size} color={color} /> }} />
      <Tabs.Screen name="tutor"        options={{ title: 'Tutor IA',   tabBarIcon: ({ color, size }) => <Ionicons name="sparkles"       size={size} color={color} /> }} />
      <Tabs.Screen name="comunicados"  options={{ title: 'Noticias',   tabBarIcon: ({ color, size }) => <Ionicons name="megaphone"      size={size} color={color} />, href: null }} />
    </Tabs>
  )
}
