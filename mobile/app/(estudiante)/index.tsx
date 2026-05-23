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

const ACCENT = Colors.roles.estudiante

const SECCIONES = [
  {
    titulo: 'Académico',
    items: [
      { label: 'Notas',        icon: 'bar-chart',        route: '/(estudiante)/notas',                   color: Colors.blue   },
      { label: 'Asistencia',   icon: 'calendar-number',  route: '/(estudiante)/asistencia',              color: Colors.green  },
      { label: 'Horario',      icon: 'time',             route: '/(estudiante)/horario',                 color: Colors.indigo },
      { label: 'Tareas',       icon: 'checkbox',         route: '/(estudiante)/tareas',                  color: Colors.amber  },
      { label: 'Evaluaciones', icon: 'ribbon',           route: '/(estudiante)/resultados-evaluacion',   color: Colors.purple },
      { label: 'Plan Eval.',   icon: 'document-text',    route: '/(estudiante)/plan-evaluacion',         color: Colors.blue   },
      { label: 'Conducta',     icon: 'shield-half',      route: '/(estudiante)/conducta',                color: Colors.red    },
      { label: 'Mi Estado',    icon: 'shield-checkmark', route: '/(estudiante)/riesgo',                  color: Colors.amber  },
    ],
  },
  {
    titulo: 'Comunicación',
    items: [
      { label: 'Mensajes',       icon: 'mail',              route: '/(estudiante)/mensajes',       color: Colors.blue   },
      { label: 'Comunicados',    icon: 'megaphone',         route: '/(estudiante)/comunicados',    color: Colors.purple },
      { label: 'Observaciones',  icon: 'chatbubble',        route: '/(estudiante)/observaciones',  color: Colors.indigo },
      { label: 'Encuestas',      icon: 'clipboard',         route: '/(estudiante)/encuestas',      color: '#8b5cf6'     },
      { label: 'Notificaciones', icon: 'notifications',     route: '/(estudiante)/notificaciones', color: Colors.red    },
    ],
  },
  {
    titulo: 'Recursos',
    items: [
      { label: 'Classroom',   icon: 'easel',         route: '/(estudiante)/classroom',   color: '#0ea5e9'     },
      { label: 'Tutor IA',    icon: 'sparkles',      route: '/(estudiante)/tutor',       color: '#6366f1'     },
      { label: 'Mis Puntos',   icon: 'trophy',        route: '/(estudiante)/mis-puntos',        color: '#f59e0b'     },
      { label: 'Reconocim.',  icon: 'ribbon',        route: '/(estudiante)/reconocimientos',   color: '#d97706'     },
      { label: 'Pagos',       icon: 'card',          route: '/(estudiante)/pagos',             color: Colors.green  },
      { label: 'Solicitudes', icon: 'document-text', route: '/(estudiante)/solicitudes', color: Colors.indigo },
      { label: 'Cafetería',   icon: 'cafe',          route: '/(estudiante)/cafeteria',   color: '#7c3aed'     },
      { label: 'Transporte',  icon: 'bus',           route: '/(estudiante)/transporte',  color: '#0369a1'     },
      { label: 'Calendario',  icon: 'calendar',      route: '/(estudiante)/calendario',  color: Colors.amber  },
      { label: 'Proyectos',   icon: 'flask',         route: '/(estudiante)/proyectos',   color: Colors.green  },
      { label: 'Eventos',     icon: 'ribbon',        route: '/(estudiante)/eventos',     color: Colors.purple },
      { label: 'Biblioteca',  icon: 'library',       route: '/(estudiante)/biblioteca',  color: Colors.indigo },
      { label: 'Perfil',      icon: 'person-circle', route: '/(estudiante)/perfil',      color: Colors.muted  },
    ],
  },
] as const

function semColor(n: number | null) {
  if (n == null) return Colors.muted
  return n >= 80 ? Colors.green : n >= 70 ? Colors.amber : Colors.red
}

export default function EstudianteDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  // El dashboard estudiante retorna los campos en el nivel raíz
  const nombre        = data?.nombre ?? user?.name ?? ''
  const grupo         = data?.grupo  ?? null
  const schoolYear    = data?.school_year ?? null
  const promedio      = data?.promedio    ?? null
  const pctAsistencia = data?.pct_asistencia ?? null
  const totalMaterias = data?.total_materias ?? null
  const gamif         = data?.gamificacion  ?? null

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ─── Header ─── */}
        <View style={styles.header}>
          <View>
            <Text style={styles.sub}>Portal Estudiante</Text>
            <Text style={styles.name}>Hola, {nombre.split(' ')[0]} 👋</Text>
          </View>
          <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
            <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
          </TouchableOpacity>
        </View>

        {/* ─── Tarjeta de perfil ─── */}
        {(grupo || schoolYear) && (
          <View style={styles.profileCard}>
            <View style={styles.profileAvatar}>
              <Text style={styles.profileAvatarTxt}>
                {nombre.charAt(0).toUpperCase()}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.profileNombre}>{nombre}</Text>
              {!!grupo      && <Text style={styles.profileGrupo}>{grupo}</Text>}
              {!!schoolYear && <Text style={styles.profileYear}>{schoolYear}</Text>}
            </View>
            {promedio != null && (
              <View style={[styles.promedioBox, { backgroundColor: semColor(promedio) + '18' }]}>
                <Text style={[styles.promedioNum, { color: semColor(promedio) }]}>{promedio}</Text>
                <Text style={styles.promedioLbl}>Promedio</Text>
              </View>
            )}
          </View>
        )}

        {/* ─── KPIs ─── */}
        {isLoading ? (
          <View style={styles.skeletonRow}>
            {[0, 1, 2].map(i => <View key={i} style={styles.skeleton} />)}
          </View>
        ) : (
          <View style={styles.kpiRow}>
            <KpiCard
              label="Promedio"
              value={promedio ?? '—'}
              color={semColor(promedio)}
              style={styles.kpi}
            />
            <KpiCard
              label="Asistencia"
              value={pctAsistencia != null ? `${pctAsistencia}%` : '—'}
              color={pctAsistencia != null && pctAsistencia >= 85 ? Colors.green : pctAsistencia != null && pctAsistencia >= 70 ? Colors.amber : Colors.red}
              style={styles.kpi}
            />
            <KpiCard
              label="Materias"
              value={totalMaterias ?? '—'}
              color={Colors.purple}
              style={styles.kpi}
            />
          </View>
        )}

        {/* ─── Gamificación ─── */}
        {gamif && (
          <TouchableOpacity
            style={styles.gamifCard}
            activeOpacity={0.85}
            onPress={() => router.push('/(estudiante)/mis-puntos' as any)}
          >
            <View style={styles.gamifHeader}>
              <View style={styles.gamifTitleRow}>
                <Ionicons name="trophy" size={16} color="#f59e0b" />
                <Text style={styles.gamifTitle}>Gamificación</Text>
              </View>
              <Text style={styles.gamifLink}>Ver todo →</Text>
            </View>
            <View style={styles.gamifStats}>
              <View style={[styles.gamifBox, { backgroundColor: '#eef2ff' }]}>
                <Text style={[styles.gamifVal, { color: '#4338ca' }]}>{gamif.puntos}</Text>
                <Text style={styles.gamifLbl}>Puntos</Text>
              </View>
              <View style={[styles.gamifBox, { backgroundColor: '#fef9c3' }]}>
                <Text style={[styles.gamifVal, { color: '#b45309' }]}>
                  {gamif.posicion != null ? `#${gamif.posicion}` : 'N/A'}
                </Text>
                <Text style={styles.gamifLbl}>Posición</Text>
              </View>
              <View style={[styles.gamifBox, { backgroundColor: '#f0fdf4' }]}>
                <Text style={[styles.gamifVal, { color: '#15803d' }]}>{gamif.insignias}</Text>
                <Text style={styles.gamifLbl}>Insignias</Text>
              </View>
              {gamif.total_grupo != null && (
                <View style={[styles.gamifBox, { backgroundColor: '#f8fafc' }]}>
                  <Text style={[styles.gamifVal, { color: Colors.muted }]}>{gamif.total_grupo}</Text>
                  <Text style={styles.gamifLbl}>En grupo</Text>
                </View>
              )}
            </View>
          </TouchableOpacity>
        )}

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
  header:          { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sub:             { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:            { fontSize: 22, fontWeight: '900', color: Colors.text },
  logoutBtn:       { padding: 8 },

  // Perfil
  profileCard:     { flexDirection: 'row', alignItems: 'center', gap: 12,
                     backgroundColor: '#fff', borderRadius: 16, padding: 14,
                     shadowColor: '#000', shadowOpacity: .06, shadowRadius: 10, elevation: 3 },
  profileAvatar:   { width: 48, height: 48, borderRadius: 14, backgroundColor: ACCENT + '18',
                     alignItems: 'center', justifyContent: 'center' },
  profileAvatarTxt:{ fontSize: 20, fontWeight: '900', color: ACCENT },
  profileNombre:   { fontSize: 14, fontWeight: '800', color: Colors.text },
  profileGrupo:    { fontSize: 12, fontWeight: '600', color: ACCENT, marginTop: 2 },
  profileYear:     { fontSize: 11, color: Colors.muted, marginTop: 1 },
  promedioBox:     { borderRadius: 12, padding: 10, alignItems: 'center', minWidth: 60 },
  promedioNum:     { fontSize: 20, fontWeight: '900' },
  promedioLbl:     { fontSize: 9, color: Colors.muted, fontWeight: '600', marginTop: 1 },

  // KPIs
  skeletonRow:     { flexDirection: 'row', gap: 10 },
  skeleton:        { flex: 1, height: 80, borderRadius: 14, backgroundColor: Colors.border },
  kpiRow:          { flexDirection: 'row', gap: 10 },
  kpi:             { flex: 1 },

  // Gamificación
  gamifCard:       { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 12,
                     shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  gamifHeader:     { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  gamifTitleRow:   { flexDirection: 'row', alignItems: 'center', gap: 6 },
  gamifTitle:      { fontSize: 13, fontWeight: '800', color: Colors.text,
                     textTransform: 'uppercase', letterSpacing: .4 },
  gamifLink:       { fontSize: 11, color: Colors.blue, fontWeight: '700' },
  gamifStats:      { flexDirection: 'row', gap: 8 },
  gamifBox:        { flex: 1, borderRadius: 12, padding: 10, alignItems: 'center' },
  gamifVal:        { fontSize: 18, fontWeight: '900', lineHeight: 22 },
  gamifLbl:        { fontSize: 9, fontWeight: '600', color: Colors.muted, marginTop: 2 },

  // Secciones de acceso rápido
  section:         { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                     shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle:    { fontSize: 13, fontWeight: '800', color: Colors.text,
                     textTransform: 'uppercase', letterSpacing: .4, marginBottom: 2 },
  grid:            { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  gridItem:        { alignItems: 'center', gap: 6, width: '28%', minWidth: 72 },
  gridIcon:        { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  gridLbl:         { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
})
