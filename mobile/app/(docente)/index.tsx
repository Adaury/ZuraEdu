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

const ACCENT = Colors.roles.docente

const SECCIONES = [
  {
    titulo: 'Clases',
    items: [
      { label: 'Asistencia',     icon: 'calendar-number', route: '/(docente)/asistencia',     color: Colors.blue   },
      { label: 'QR Scan',        icon: 'qr-code',         route: '/(docente)/qr',             color: Colors.amber  },
      { label: 'Calificaciones', icon: 'bar-chart',       route: '/(docente)/calificaciones', color: Colors.green  },
      { label: 'Classroom',      icon: 'easel',           route: '/(docente)/classroom',      color: '#0ea5e9'     },
      { label: 'Horario',        icon: 'time',            route: '/(docente)/horario',        color: Colors.indigo },
    ],
  },
  {
    titulo: 'Seguimiento',
    items: [
      { label: 'Tareas',        icon: 'checkbox',        route: '/(docente)/tareas',           color: Colors.green  },
      { label: 'Conducta',      icon: 'shield-half',     route: '/(docente)/conducta',         color: Colors.purple },
      { label: 'Observaciones', icon: 'chatbubble',      route: '/(docente)/observaciones',    color: Colors.indigo },
      { label: 'Riesgo',        icon: 'analytics',       route: '/(docente)/riesgo',           color: Colors.red    },
      { label: 'Plan Eval.',    icon: 'document-text',   route: '/(docente)/plan-evaluacion',  color: Colors.blue   },
      { label: 'Instrumentos',  icon: 'grid',            route: '/(docente)/instrumentos',     color: Colors.indigo },
      { label: 'Gamificación',  icon: 'trophy',          route: '/(docente)/gamificacion',     color: '#f59e0b'     },
    ],
  },
  {
    titulo: 'Comunicación',
    items: [
      { label: 'Mensajes',       icon: 'mail',           route: '/(docente)/mensajes',       color: Colors.purple },
      { label: 'Comunicados',    icon: 'megaphone',      route: '/(docente)/comunicados',    color: Colors.red    },
      { label: 'Solicitudes',    icon: 'document-text',  route: '/(docente)/solicitudes',    color: Colors.amber  },
      { label: 'Notificaciones', icon: 'notifications',  route: '/(docente)/notificaciones', color: Colors.blue   },
      { label: 'Calendario',     icon: 'calendar',       route: '/(docente)/calendario',     color: Colors.amber  },
      { label: 'Perfil',         icon: 'person-circle',  route: '/(docente)/perfil',         color: Colors.muted  },
    ],
  },
] as const

export default function DocenteDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()

  const { data, isLoading: dashLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const { data: gruposData, isLoading: gruposLoading } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
    staleTime: 60_000,
  })

  const isLoading       = dashLoading || gruposLoading
  const grupos: any[]   = gruposData?.data ?? []
  const schoolYear      = gruposData?.school_year ?? data?.school_year ?? null
  const totalEstudiantes = grupos.reduce((s: number, g: any) => s + (g.total_estudiantes ?? 0), 0)

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ─── Header ─── */}
        <View style={styles.header}>
          <View>
            <Text style={styles.sub}>Portal Docente</Text>
            <Text style={styles.name}>Prof. {user?.name?.split(' ')[0]} 👋</Text>
            {!!schoolYear && <Text style={styles.schoolYear}>{schoolYear}</Text>}
          </View>
          <View style={styles.headerActions}>
            <TouchableOpacity
              onPress={() => router.push('/(docente)/qr')}
              style={styles.qrBtn}
            >
              <Ionicons name="qr-code" size={20} color="#fff" />
            </TouchableOpacity>
            <TouchableOpacity onPress={logout} style={{ padding: 8 }}>
              <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
            </TouchableOpacity>
          </View>
        </View>

        {/* ─── KPIs ─── */}
        {isLoading ? (
          <View style={styles.skeletonRow}>
            <View style={styles.skeleton} />
            <View style={styles.skeleton} />
          </View>
        ) : (
          <View style={styles.kpiRow}>
            <KpiCard
              label="Mis Grupos"
              value={grupos.length || '—'}
              color={Colors.amber}
              style={{ flex: 1 }}
            />
            <KpiCard
              label="Estudiantes"
              value={totalEstudiantes || '—'}
              color={Colors.blue}
              style={{ flex: 1 }}
            />
          </View>
        )}

        {/* ─── Mis Grupos ─── */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Mis Grupos</Text>
            {grupos.length > 5 && (
              <TouchableOpacity onPress={() => router.push('/(docente)/grupos' as any)}>
                <Text style={styles.verTodo}>Ver todos →</Text>
              </TouchableOpacity>
            )}
          </View>

          {gruposLoading && (
            <>
              <View style={styles.grupoSkeleton} />
              <View style={styles.grupoSkeleton} />
            </>
          )}

          {grupos.slice(0, 5).map((g: any, i: number) => (
            <TouchableOpacity
              key={g.id}
              style={[styles.grupoRow, i < Math.min(grupos.length, 5) - 1 && styles.grupoRowBorder]}
              activeOpacity={0.75}
              onPress={() => router.push({ pathname: '/(docente)/asistencia', params: { grupoId: g.id } })}
            >
              <View style={[styles.grupoColor, { backgroundColor: g.color ?? ACCENT }]} />
              <View style={[styles.grupoIcon, { backgroundColor: (g.color ?? ACCENT) + '18' }]}>
                <Ionicons name="book" size={16} color={g.color ?? ACCENT} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.grupoAsignatura}>{g.asignatura}</Text>
                <Text style={styles.grupoSub}>
                  {g.grado} {g.seccion}
                  {g.total_estudiantes != null ? ` · ${g.total_estudiantes} est.` : ''}
                </Text>
              </View>
              <View style={styles.grupoActions}>
                <TouchableOpacity
                  style={styles.grupoActionBtn}
                  onPress={() => router.push({ pathname: '/(docente)/calificaciones', params: { asignacionId: g.id } })}
                >
                  <Ionicons name="bar-chart" size={14} color={Colors.green} />
                </TouchableOpacity>
                <Ionicons name="chevron-forward" size={14} color={Colors.border} />
              </View>
            </TouchableOpacity>
          ))}

          {!gruposLoading && grupos.length === 0 && (
            <View style={styles.emptyWrap}>
              <Ionicons name="people-outline" size={36} color={Colors.border} />
              <Text style={styles.empty}>No tienes grupos asignados.</Text>
            </View>
          )}
        </View>

        {/* ─── Secciones de acceso rápido ─── */}
        {SECCIONES.map(sec => (
          <View key={sec.titulo} style={styles.section}>
            <Text style={styles.sectionTitle}>{sec.titulo}</Text>
            <View style={styles.grid}>
              {sec.items.map(({ label, icon, route, color }) => (
                <TouchableOpacity
                  key={label}
                  style={styles.gridItem}
                  activeOpacity={0.8}
                  onPress={() => router.push(route as any)}
                >
                  <View style={[styles.gridIcon, { backgroundColor: color + '18' }]}>
                    <Ionicons name={icon as any} size={22} color={color} />
                  </View>
                  <Text style={styles.gridLbl}>{label}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, gap: 14, paddingBottom: 32 },

  // Header
  header:          { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  sub:             { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:            { fontSize: 22, fontWeight: '900', color: Colors.text },
  schoolYear:      { fontSize: 12, color: Colors.muted, marginTop: 2 },
  headerActions:   { flexDirection: 'row', alignItems: 'center', gap: 4 },
  qrBtn:           { backgroundColor: ACCENT, borderRadius: 12, padding: 10, marginRight: 4 },

  // KPIs
  skeletonRow:     { flexDirection: 'row', gap: 10 },
  skeleton:        { flex: 1, height: 80, borderRadius: 14, backgroundColor: Colors.border },
  kpiRow:          { flexDirection: 'row', gap: 10 },

  // Sección
  section:         { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                     shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionHeader:   { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  sectionTitle:    { fontSize: 13, fontWeight: '800', color: Colors.text,
                     textTransform: 'uppercase', letterSpacing: .4 },
  verTodo:         { fontSize: 11, color: ACCENT, fontWeight: '700' },

  // Grupos
  grupoSkeleton:   { height: 52, borderRadius: 10, backgroundColor: Colors.border },
  grupoRow:        { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 9 },
  grupoRowBorder:  { borderBottomWidth: 1, borderBottomColor: Colors.border },
  grupoColor:      { width: 3, height: 36, borderRadius: 99 },
  grupoIcon:       { width: 36, height: 36, borderRadius: 10,
                     alignItems: 'center', justifyContent: 'center' },
  grupoAsignatura: { fontSize: 14, fontWeight: '700', color: Colors.text },
  grupoSub:        { fontSize: 12, color: Colors.muted, marginTop: 1 },
  grupoActions:    { flexDirection: 'row', alignItems: 'center', gap: 6 },
  grupoActionBtn:  { width: 28, height: 28, borderRadius: 8, backgroundColor: Colors.bg,
                     alignItems: 'center', justifyContent: 'center' },
  emptyWrap:       { alignItems: 'center', gap: 8, paddingVertical: 16 },
  empty:           { color: Colors.muted, fontSize: 13 },

  // Grid de acceso rápido
  grid:            { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  gridItem:        { alignItems: 'center', gap: 6, width: '28%', minWidth: 72 },
  gridIcon:        { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  gridLbl:         { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
})
