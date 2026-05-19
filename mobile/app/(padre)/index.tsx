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
  { label: 'Horario',    icon: 'time',             route: '/(padre)/horario',    color: Colors.blue   },
  { label: 'Situación',  icon: 'shield-checkmark', route: '/(padre)/riesgo',     color: Colors.amber  },
  { label: 'Pagos',      icon: 'card',             route: '/(padre)/pagos',      color: Colors.green  },
  { label: 'Mensajes',   icon: 'mail',             route: '/(padre)/mensajes',   color: Colors.indigo },
  { label: 'Noticias',   icon: 'megaphone',        route: '/(padre)/comunicados',color: Colors.purple },
  { label: 'Logros',     icon: 'trophy',           route: '/(padre)/mis-puntos', color: '#6366f1'     },
  { label: 'Tareas',     icon: 'checkbox',         route: '/(padre)/tareas',     color: Colors.blue   },
  { label: 'Encuestas',  icon: 'clipboard',        route: '/(padre)/encuestas',  color: '#8b5cf6'     },
  { label: 'Cafetería',  icon: 'cafe',             route: '/(padre)/cafeteria',  color: '#7c3aed'     },
  { label: 'Transporte', icon: 'bus',              route: '/(padre)/transporte', color: '#0369a1'     },
] as const

export default function PadreDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
  })

  const d     = data?.data ?? {}
  const hijos = d.hijos ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.roles.padre} />}
      >
        <View style={styles.header}>
          <View>
            <Text style={styles.sub}>Portal Representante</Text>
            <Text style={styles.name}>Hola, {user?.name?.split(' ')[0]} 👋</Text>
          </View>
          <TouchableOpacity onPress={logout} style={{ padding: 8 }}>
            <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
          </TouchableOpacity>
        </View>

        {/* Tarjetas de hijos */}
        {hijos.map((hijo: any, i: number) => (
          <View key={i} style={styles.hijoCard}>
            <View style={styles.hijoAvatar}>
              <Text style={styles.hijoAvatarText}>{hijo.nombres?.[0] ?? '?'}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.hijoNombre}>{hijo.nombres} {hijo.apellidos}</Text>
              <Text style={styles.hijoGrado}>{hijo.grado ?? hijo.grupo}</Text>
            </View>
            <View style={styles.hijoStats}>
              <Text style={[styles.hijoNota, { color: parseFloat(hijo.promedio) >= 70 ? Colors.green : Colors.red }]}>
                {hijo.promedio ?? '—'}
              </Text>
              <Text style={styles.hijoStatLbl}>Promedio</Text>
            </View>
          </View>
        ))}

        {/* KPIs del primer hijo */}
        {hijos.length > 0 && (
          <>
            <Text style={styles.sectionTitle}>{hijos[0]?.nombres?.split(' ')[0]}</Text>
            <View style={styles.kpiRow}>
              <KpiCard label="Promedio"   value={hijos[0]?.promedio ?? '—'}   color={Colors.purple} style={{ flex: 1 }} />
              <KpiCard label="Asistencia" value={hijos[0]?.asistencia != null ? `${hijos[0].asistencia}%` : '—'} color={Colors.blue} style={{ flex: 1 }} />
            </View>
          </>
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

        {/* Notificaciones */}
        {d.notificaciones?.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Notificaciones</Text>
            {d.notificaciones.slice(0, 4).map((n: any, i: number) => (
              <View key={i} style={styles.notifItem}>
                <Ionicons name="notifications-outline" size={15} color={Colors.purple} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.notifTitle}>{n.titulo}</Text>
                  <Text style={styles.notifBody}>{n.cuerpo}</Text>
                </View>
              </View>
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 14, paddingBottom: 32 },
  header:         { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sub:            { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:           { fontSize: 22, fontWeight: '900', color: Colors.text },
  hijoCard:       { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 16, padding: 14, alignItems: 'center', gap: 12, shadowColor: '#000', shadowOpacity: .06, shadowRadius: 10, elevation: 3 },
  hijoAvatar:     { width: 48, height: 48, borderRadius: 14, backgroundColor: Colors.roles.padre + '20', alignItems: 'center', justifyContent: 'center' },
  hijoAvatarText: { fontSize: 20, fontWeight: '900', color: Colors.roles.padre },
  hijoNombre:     { fontSize: 15, fontWeight: '800', color: Colors.text },
  hijoGrado:      { fontSize: 12, color: Colors.muted, marginTop: 2 },
  hijoStats:      { alignItems: 'center' },
  hijoNota:       { fontSize: 22, fontWeight: '900' },
  hijoStatLbl:    { fontSize: 10, color: Colors.muted, fontWeight: '600' },
  kpiRow:         { flexDirection: 'row', gap: 10 },
  sectionTitle:   { fontSize: 15, fontWeight: '800', color: Colors.text },
  section:        { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  quickGrid:      { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  quickItem:      { alignItems: 'center', gap: 6, width: '18%', minWidth: 58 },
  quickIcon:      { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  quickLbl:       { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
  notifItem:      { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  notifTitle:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  notifBody:      { fontSize: 12, color: Colors.muted },
})
