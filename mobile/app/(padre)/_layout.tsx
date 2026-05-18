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
      <Tabs.Screen name="index"       options={{ title: 'Inicio',     tabBarIcon: ({ color, size }) => <Ionicons name="home"         size={size} color={color} /> }} />
      <Tabs.Screen name="notas"       options={{ title: 'Notas',      tabBarIcon: ({ color, size }) => <Ionicons name="bar-chart"    size={size} color={color} /> }} />
      <Tabs.Screen name="asistencia"  options={{ title: 'Asistencia', tabBarIcon: ({ color, size }) => <Ionicons name="calendar"     size={size} color={color} /> }} />
      <Tabs.Screen name="pagos"       options={{ title: 'Pagos',      tabBarIcon: ({ color, size }) => <Ionicons name="card"         size={size} color={color} /> }} />
      <Tabs.Screen name="comunicados" options={{ title: 'Noticias',   tabBarIcon: ({ color, size }) => <Ionicons name="megaphone"    size={size} color={color} /> }} />
    </Tabs>
  )
}
