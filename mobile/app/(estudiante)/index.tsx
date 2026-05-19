import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useRouter } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { dashboardApi } from '../../services/api'
import { KpiCard } from '../../components/ui/Card'
import { Colors } from '../../constants/Colors'

const QUICK_LINKS = [
  { label: 'Observaciones', icon: 'chatbubble',    route: '/(estudiante)/observaciones', color: Colors.indigo },
  { label: 'Horario',    icon: 'time',             route: '/(estudiante)/horario',      color: Colors.blue   },
  { label: 'Mi Estado',  icon: 'shield-checkmark', route: '/(estudiante)/riesgo',       color: Colors.amber  },
  { label: 'Mis Pagos',  icon: 'card',             route: '/(estudiante)/pagos',        color: Colors.green  },
  { label: 'Mensajes',   icon: 'mail',             route: '/(estudiante)/mensajes',     color: Colors.indigo },
  { label: 'Noticias',   icon: 'megaphone',        route: '/(estudiante)/comunicados',  color: Colors.purple },
  { label: 'Mis Puntos', icon: 'trophy',           route: '/(estudiante)/mis-puntos',   color: '#6366f1'     },
  { label: 'Tareas',     icon: 'checkbox',         route: '/(estudiante)/tareas',       color: Colors.blue   },
  { label: 'Encuestas',  icon: 'clipboard',        route: '/(estudiante)/encuestas',    color: '#8b5cf6'     },
  { label: 'Cafetería',  icon: 'cafe',             route: '/(estudiante)/cafeteria',    color: '#7c3aed'     },
  { label: 'Transporte', icon: 'bus',              route: '/(estudiante)/transporte',   color: '#0369a1'     },
  { label: 'Documentos', icon: 'folder-open',      route: '/(estudiante)/documentos',      color: Colors.blue   },
  { label: 'Solicitudes',icon: 'clipboard',        route: '/(estudiante)/solicitudes',     color: Colors.indigo },
  { label: 'Calendario', icon: 'calendar',         route: '/(estudiante)/calendario',      color: Colors.amber  },
  { label: 'Notifs',     icon: 'notifications',    route: '/(estudiante)/notificaciones',  color: Colors.blue   },
  { label: 'Perfil',     icon: 'person-circle',    route: '/(estudiante)/perfil',          color: Colors.muted  },
] as const

export default function EstudianteDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
  })

  const d = data?.data ?? {}

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.roles.estudiante} />}
      >
        {/* Greeting */}
        <View style={styles.greeting}>
          <View>
            <Text style={styles.greetSub}>Portal Estudiante</Text>
            <Text style={styles.greetName}>Hola, {user?.name?.split(' ')[0]} 👋</Text>
          </View>
          <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
            <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
          </TouchableOpacity>
        </View>

        {/* KPIs */}
        {isLoading
          ? <View style={styles.skeletonRow}>{[0,1,2].map(i => <View key={i} style={styles.skeleton} />)}</View>
          : (
            <View style={styles.kpiRow}>
              <KpiCard label="Promedio"    value={d.promedio     ?? '—'}  color={Colors.green}  style={styles.kpi} />
              <KpiCard label="Asistencia"  value={d.pct_asistencia != null ? `${d.pct_asistencia}%` : '—'} color={Colors.blue} style={styles.kpi} />
              <KpiCard label="Materias"    value={d.total_materias ?? '—'} color={Colors.purple} style={styles.kpi} />
            </View>
          )
        }

        {/* Próximas tareas */}
        {d.proximas_tareas?.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Próximas Tareas</Text>
            {d.proximas_tareas.map((t: any, i: number) => (
              <View key={i} style={styles.tareaItem}>
                <View style={[styles.tareaDot, { backgroundColor: Colors.amber }]} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.tareaNombre}>{t.titulo ?? t.nombre}</Text>
                  <Text style={styles.tareaFecha}>{t.asignatura} · {t.fecha_entrega}</Text>
                </View>
              </View>
            ))}
          </View>
        )}

        {/* Gamificación */}
        {d.gamificacion && (
          <TouchableOpacity style={styles.section} onPress={() => router.push('/(estudiante)/mis-puntos' as any)}>
            <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
              <Text style={styles.sectionTitle}>🎮 Mis Puntos</Text>
              <Text style={{ fontSize: 11, color: Colors.blue, fontWeight: '700' }}>Ver todo →</Text>
            </View>
            <View style={{ flexDirection: 'row', gap: 8 }}>
              <View style={[styles.gamifStat, { backgroundColor: '#eef2ff' }]}>
                <Text style={[styles.gamifVal, { color: '#4338ca' }]}>{d.gamificacion.puntos}</Text>
                <Text style={styles.gamifLbl}>Puntos</Text>
              </View>
              <View style={[styles.gamifStat, { backgroundColor: '#fef9c3' }]}>
                <Text style={[styles.gamifVal, { color: '#b45309' }]}>
                  {d.gamificacion.posicion ? `#${d.gamificacion.posicion}` : 'N/A'}
                </Text>
                <Text style={styles.gamifLbl}>Posición</Text>
              </View>
              <View style={[styles.gamifStat, { backgroundColor: '#fef3c7' }]}>
                <Text style={[styles.gamifVal, { color: '#d97706' }]}>{d.gamificacion.insignias}</Text>
                <Text style={styles.gamifLbl}>Insignias</Text>
              </View>
            </View>
          </TouchableOpacity>
        )}

        {/* Acceso rápido */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Acceso Rápido</Text>
          <View style={styles.quickGrid}>
            {QUICK_LINKS.map(({ label, icon, route, color }) => (
              <TouchableOpacity key={label} style={styles.quickItem} onPress={() => router.push(route as any)}>
                <View style={[styles.quickIcon, { backgroundColor: color + '18' }]}>
                  <Ionicons name={icon as any} size={22} color={color} />
                </View>
                <Text style={styles.quickLbl}>{label}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* Notificaciones recientes */}
        {d.notificaciones?.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Notificaciones</Text>
            {d.notificaciones.slice(0,4).map((n: any, i: number) => (
              <View key={i} style={styles.notifItem}>
                <Ionicons name="notifications-outline" size={16} color={Colors.blue} style={{ marginTop: 2 }} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.notifTitulo}>{n.titulo}</Text>
                  <Text style={styles.notifCuerpo}>{n.cuerpo}</Text>
                </View>
                {!n.leida && <View style={styles.unreadDot} />}
              </View>
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  scroll:       { flex: 1 },
  content:      { padding: 16, gap: 16, paddingBottom: 32 },
  greeting:     { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4 },
  greetSub:     { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  greetName:    { fontSize: 22, fontWeight: '900', color: Colors.text },
  logoutBtn:    { padding: 8 },
  kpiRow:       { flexDirection: 'row', gap: 10 },
  kpi:          { flex: 1 },
  skeletonRow:  { flexDirection: 'row', gap: 10 },
  skeleton:     { flex: 1, height: 80, borderRadius: 14, backgroundColor: Colors.border },
  section:      { backgroundColor: '#fff', borderRadius: 16, padding: 16, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle: { fontSize: 14, fontWeight: '700', color: Colors.text, marginBottom: 4 },
  tareaItem:    { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  tareaDot:     { width: 9, height: 9, borderRadius: 99, marginTop: 5 },
  tareaNombre:  { fontSize: 14, fontWeight: '600', color: Colors.text },
  tareaFecha:   { fontSize: 12, color: Colors.muted },
  quickGrid:    { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  quickItem:    { alignItems: 'center', gap: 6, width: '18%', minWidth: 58 },
  quickIcon:    { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  quickLbl:     { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
  notifItem:    { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  notifTitulo:  { fontSize: 13, fontWeight: '700', color: Colors.text },
  notifCuerpo:  { fontSize: 12, color: Colors.muted },
  unreadDot:    { width: 8, height: 8, borderRadius: 99, backgroundColor: Colors.blue, marginTop: 4 },
  gamifStat:    { flex: 1, borderRadius: 12, padding: 10, alignItems: 'center' },
  gamifVal:     { fontSize: 18, fontWeight: '900', lineHeight: 22 },
  gamifLbl:     { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2 },
})
