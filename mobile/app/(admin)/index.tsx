import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useRouter } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { dashboardApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.admin

const STAT_CONFIG = [
  { key: 'estudiantes', label: 'Estudiantes', icon: 'school',       color: Colors.blue   },
  { key: 'docentes',    label: 'Docentes',    icon: 'person',       color: Colors.green  },
  { key: 'grupos',      label: 'Grupos',      icon: 'people',       color: Colors.amber  },
] as const

const SECCIONES = [
  {
    titulo: 'Comunicación',
    items: [
      { label: 'Mensajes',       icon: 'mail',           route: '/(admin)/mensajes',       color: Colors.blue   },
      { label: 'Notificaciones', icon: 'notifications',  route: '/(admin)/notificaciones', color: Colors.red    },
      { label: 'Comunicados',    icon: 'megaphone',      route: '/(admin)/comunicados',    color: Colors.purple },
      { label: 'Calendario',     icon: 'calendar',       route: '/(admin)/calendario',     color: Colors.amber  },
    ],
  },
  {
    titulo: 'Mi Cuenta',
    items: [
      { label: 'Perfil', icon: 'person-circle', route: '/(admin)/perfil', color: Colors.muted },
    ],
  },
] as const

export default function AdminDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const stats      = data?.stats      ?? {}
  const schoolYear = data?.school_year ?? null

  const isAdmin    = user?.roles?.includes('Administrador')
  const isDirector = user?.roles?.includes('Director')
  const rolLabel   = isAdmin ? 'Administrador' : isDirector ? 'Director' : 'Administración'

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ─── Header ─── */}
        <View style={styles.header}>
          <View style={{ flex: 1 }}>
            <Text style={styles.sub}>{rolLabel}</Text>
            <Text style={styles.name}>{user?.name?.split(' ')[0]} 👋</Text>
            {!!schoolYear && <Text style={styles.schoolYear}>{schoolYear}</Text>}
          </View>
          <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
            <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
          </TouchableOpacity>
        </View>

        {/* ─── Banner institucional ─── */}
        <View style={styles.banner}>
          <View style={styles.bannerIcon}>
            <Ionicons name="business" size={28} color="#fff" />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.bannerTitle}>Panel de Gestión</Text>
            <Text style={styles.bannerSub}>
              Para administración completa usa el portal web.
            </Text>
          </View>
        </View>

        {/* ─── Estadísticas ─── */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Resumen Institucional</Text>

          {isLoading ? (
            <View style={styles.statsRow}>
              {[0, 1, 2].map(i => <View key={i} style={styles.statSkeleton} />)}
            </View>
          ) : (
            <View style={styles.statsRow}>
              {STAT_CONFIG.map(({ key, label, icon, color }) => (
                <View key={key} style={[styles.statBox, { borderTopColor: color }]}>
                  <View style={[styles.statIconWrap, { backgroundColor: color + '18' }]}>
                    <Ionicons name={icon as any} size={20} color={color} />
                  </View>
                  <Text style={[styles.statNum, { color }]}>
                    {stats[key] ?? '—'}
                  </Text>
                  <Text style={styles.statLbl}>{label}</Text>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* ─── Estado del año escolar ─── */}
        {schoolYear && (
          <View style={styles.syCard}>
            <View style={[styles.syDot, { backgroundColor: Colors.green }]} />
            <View style={{ flex: 1 }}>
              <Text style={styles.syLabel}>Año Escolar Activo</Text>
              <Text style={styles.syName}>{schoolYear}</Text>
            </View>
            <View style={[styles.syBadge, { backgroundColor: Colors.green + '18' }]}>
              <Text style={[styles.syBadgeTxt, { color: Colors.green }]}>Activo</Text>
            </View>
          </View>
        )}

        {/* ─── Acceso rápido por sección ─── */}
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

        {/* ─── Aviso portal web ─── */}
        <View style={styles.webCard}>
          <Ionicons name="globe-outline" size={20} color={ACCENT} />
          <View style={{ flex: 1 }}>
            <Text style={styles.webTitle}>Portal Web Completo</Text>
            <Text style={styles.webSub}>
              Matrículas, nómina, horarios, reportes, configuración y más están disponibles en el portal web del sistema.
            </Text>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, gap: 14, paddingBottom: 100 },

  // Header
  header:        { flexDirection: 'row', alignItems: 'flex-start', gap: 8 },
  sub:           { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:          { fontSize: 22, fontWeight: '900', color: Colors.text },
  schoolYear:    { fontSize: 12, color: Colors.muted, marginTop: 2 },
  logoutBtn:     { padding: 8 },

  // Banner
  banner:        { flexDirection: 'row', alignItems: 'center', gap: 14, backgroundColor: ACCENT,
                   borderRadius: 16, padding: 16 },
  bannerIcon:    { width: 50, height: 50, borderRadius: 14, backgroundColor: 'rgba(255,255,255,0.15)',
                   alignItems: 'center', justifyContent: 'center' },
  bannerTitle:   { fontSize: 16, fontWeight: '800', color: '#fff' },
  bannerSub:     { fontSize: 12, color: 'rgba(255,255,255,0.75)', marginTop: 3, lineHeight: 17 },

  // Sección
  section:       { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 12,
                   shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle:  { fontSize: 13, fontWeight: '800', color: Colors.text,
                   textTransform: 'uppercase', letterSpacing: .4 },

  // Stats
  statsRow:      { flexDirection: 'row', gap: 8 },
  statSkeleton:  { flex: 1, height: 100, borderRadius: 12, backgroundColor: Colors.border },
  statBox:       { flex: 1, backgroundColor: Colors.bg, borderRadius: 12, padding: 12,
                   alignItems: 'center', gap: 6, borderTopWidth: 3 },
  statIconWrap:  { width: 38, height: 38, borderRadius: 10,
                   alignItems: 'center', justifyContent: 'center' },
  statNum:       { fontSize: 24, fontWeight: '900', lineHeight: 28 },
  statLbl:       { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },

  // School year card
  syCard:        { flexDirection: 'row', alignItems: 'center', gap: 12,
                   backgroundColor: '#fff', borderRadius: 14, padding: 14,
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  syDot:         { width: 10, height: 10, borderRadius: 99 },
  syLabel:       { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  syName:        { fontSize: 15, fontWeight: '800', color: Colors.text, marginTop: 1 },
  syBadge:       { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 4 },
  syBadgeTxt:    { fontSize: 11, fontWeight: '800' },

  // Grid de acceso rápido
  grid:          { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  gridItem:      { alignItems: 'center', gap: 6, width: '28%', minWidth: 72 },
  gridIcon:      { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  gridLbl:       { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },

  // Aviso web
  webCard:       { flexDirection: 'row', alignItems: 'flex-start', gap: 12,
                   backgroundColor: ACCENT + '0d', borderRadius: 14, padding: 14,
                   borderWidth: 1, borderColor: ACCENT + '25' },
  webTitle:      { fontSize: 13, fontWeight: '800', color: ACCENT, marginBottom: 4 },
  webSub:        { fontSize: 12, color: Colors.muted, lineHeight: 18 },
})
