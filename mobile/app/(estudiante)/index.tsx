import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { dashboardApi } from '../../services/api'
import { KpiCard } from '../../components/ui/Card'
import { Colors } from '../../constants/Colors'

export default function EstudianteDashboard() {
  const { user, logout } = useAuth()
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
  notifItem:    { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  notifTitulo:  { fontSize: 13, fontWeight: '700', color: Colors.text },
  notifCuerpo:  { fontSize: 12, color: Colors.muted },
  unreadDot:    { width: 8, height: 8, borderRadius: 99, backgroundColor: Colors.blue, marginTop: 4 },
})
