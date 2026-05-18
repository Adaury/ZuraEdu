import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useRouter } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { dashboardApi, docenteApi } from '../../services/api'
import { KpiCard } from '../../components/ui/Card'
import { Colors } from '../../constants/Colors'

export default function DocenteDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
  })

  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const d      = data?.data ?? {}
  const grupos: any[] = gruposData?.data ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.roles.docente} />}
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
            <Text style={styles.sub}>Portal Docente</Text>
            <Text style={styles.name}>Prof. {user?.name?.split(' ')[0]} 👋</Text>
          </View>
          <View style={styles.headerActions}>
            <TouchableOpacity onPress={() => router.push('/(docente)/qr')} style={styles.qrBtn}>
              <Ionicons name="qr-code" size={20} color="#fff" />
            </TouchableOpacity>
            <TouchableOpacity onPress={logout} style={{ padding: 8 }}>
              <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
            </TouchableOpacity>
          </View>
        </View>

        {/* KPIs */}
        {!isLoading && (
          <View style={styles.kpiRow}>
            <KpiCard label="Mis Grupos"  value={grupos.length || d.total_grupos || '—'}  color={Colors.amber}  style={{ flex: 1 }} />
            <KpiCard label="Estudiantes" value={d.total_estudiantes ?? grupos.reduce((s: number, g: any) => s + (g.total_estudiantes ?? 0), 0) || '—'} color={Colors.blue} style={{ flex: 1 }} />
          </View>
        )}

        {/* Mis grupos */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Mis Grupos</Text>
          {grupos.slice(0, 6).map((g: any, i: number) => (
            <TouchableOpacity
              key={i}
              style={styles.grupoRow}
              onPress={() => router.push({ pathname: '/(docente)/asistencia', params: { grupoId: g.id } })}
            >
              <View style={styles.grupoIcon}>
                <Ionicons name="book" size={16} color={Colors.amber} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.grupoNombre}>{g.asignatura}</Text>
                <Text style={styles.grupoSub}>{g.grado} {g.seccion} · {g.total_estudiantes ?? '—'} est.</Text>
              </View>
              <Ionicons name="chevron-forward" size={16} color={Colors.muted} />
            </TouchableOpacity>
          ))}
          {grupos.length === 0 && <Text style={styles.empty}>No tienes grupos asignados.</Text>}
        </View>

        {/* Acceso rápido */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Acceso Rápido</Text>
          <View style={styles.quickGrid}>
            [
              { label: 'Asistencia',     icon: 'calendar-number', route: '/(docente)/asistencia',     color: Colors.blue   },
              { label: 'QR Scan',        icon: 'qr-code',         route: '/(docente)/qr',             color: Colors.amber  },
              { label: 'Calificaciones', icon: 'bar-chart',       route: '/(docente)/calificaciones', color: Colors.green  },
              { label: 'Horario',        icon: 'time',            route: '/(docente)/horario',        color: Colors.indigo },
              { label: 'Mensajes',       icon: 'mail',            route: '/(docente)/mensajes',       color: Colors.purple },
              { label: 'Comunicados',    icon: 'megaphone',       route: '/(docente)/comunicados',    color: Colors.red    },
              { label: 'Gamificación',   icon: 'trophy',          route: '/(docente)/gamificacion',   color: '#f59e0b'     },
            ].map(({ label, icon, route, color }) => (
              <TouchableOpacity key={label} style={styles.quickItem} onPress={() => router.push(route as any)}>
                <View style={[styles.quickIcon, { backgroundColor: color + '18' }]}>
                  <Ionicons name={icon as any} size={22} color={color} />
                </View>
                <Text style={styles.quickLbl}>{label}</Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, gap: 14, paddingBottom: 32 },
  header:       { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sub:          { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:         { fontSize: 22, fontWeight: '900', color: Colors.text },
  headerActions:{ flexDirection: 'row', alignItems: 'center', gap: 4 },
  qrBtn:        { backgroundColor: Colors.amber, borderRadius: 12, padding: 10, marginRight: 4 },
  kpiRow:       { flexDirection: 'row', gap: 10 },
  section:      { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 8, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle: { fontSize: 14, fontWeight: '700', color: Colors.text, marginBottom: 4 },
  grupoRow:     { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: Colors.border },
  grupoIcon:    { width: 34, height: 34, borderRadius: 10, backgroundColor: Colors.amber + '20', alignItems: 'center', justifyContent: 'center' },
  grupoNombre:  { fontSize: 14, fontWeight: '700', color: Colors.text },
  grupoSub:     { fontSize: 12, color: Colors.muted },
  empty:        { textAlign: 'center', color: Colors.muted, paddingVertical: 12 },
  quickGrid:    { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  quickItem:    { alignItems: 'center', gap: 6, width: '28%', minWidth: 72 },
  quickIcon:    { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  quickLbl:     { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
})
