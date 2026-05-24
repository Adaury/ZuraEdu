import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useRouter } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { dashboardApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

// Secciones de acceso rápido
const SECCIONES = [
  {
    titulo: 'Académico',
    items: [
      { label: 'Notas',        icon: 'bar-chart',       route: '/(padre)/notas',                   color: Colors.blue   },
      { label: 'Asistencia',   icon: 'calendar-number', route: '/(padre)/asistencia',              color: Colors.green  },
      { label: 'Horario',      icon: 'time',            route: '/(padre)/horario',                 color: Colors.indigo },
      { label: 'Tareas',       icon: 'checkbox',        route: '/(padre)/tareas',                  color: Colors.amber  },
      { label: 'Evaluaciones', icon: 'ribbon',          route: '/(padre)/resultados-evaluacion',   color: Colors.purple },
      { label: 'Plan Eval.',   icon: 'document-text',   route: '/(padre)/plan-evaluacion',         color: Colors.blue   },
      { label: 'Conducta',     icon: 'shield-half',     route: '/(padre)/conducta',                color: Colors.red    },
      { label: 'Situación',    icon: 'shield-checkmark',route: '/(padre)/riesgo',                  color: Colors.amber  },
    ],
  },
  {
    titulo: 'Comunicación',
    items: [
      { label: 'Mensajes',      icon: 'mail',             route: '/(padre)/mensajes',       color: Colors.blue   },
      { label: 'Comunicados',   icon: 'megaphone',        route: '/(padre)/comunicados',    color: Colors.purple },
      { label: 'Observaciones', icon: 'chatbubble',       route: '/(padre)/observaciones',  color: Colors.indigo },
      { label: 'Encuestas',     icon: 'clipboard',        route: '/(padre)/encuestas',      color: '#8b5cf6'     },
      { label: 'Notificaciones',icon: 'notifications',    route: '/(padre)/notificaciones', color: Colors.red    },
    ],
  },
  {
    titulo: 'Gestión',
    items: [
      { label: 'Pagos',       icon: 'card',             route: '/(padre)/pagos',       color: Colors.green  },
      { label: 'Solicitudes', icon: 'document-text',   route: '/(padre)/solicitudes', color: Colors.indigo },
      { label: 'Documentos',  icon: 'folder-open',     route: '/(padre)/documentos',  color: Colors.blue   },
      { label: 'Classroom',   icon: 'easel',           route: '/(padre)/classroom',   color: '#0ea5e9'     },
      { label: 'Cafetería',   icon: 'cafe',            route: '/(padre)/cafeteria',   color: '#7c3aed'     },
      { label: 'Transporte',  icon: 'bus',             route: '/(padre)/transporte',  color: '#0369a1'     },
      { label: 'Logros',         icon: 'trophy',          route: '/(padre)/mis-puntos',      color: '#f59e0b'     },
      { label: 'Reconocim.',    icon: 'ribbon',          route: '/(padre)/reconocimientos', color: '#d97706'     },
      { label: 'Salud',         icon: 'heart-half',      route: '/(padre)/salud',           color: Colors.red    },
      { label: 'Proyectos',     icon: 'flask',           route: '/(padre)/proyectos',       color: Colors.green  },
      { label: 'Eventos',       icon: 'ribbon',          route: '/(padre)/eventos',         color: Colors.purple },
      { label: 'Biblioteca',    icon: 'library',         route: '/(padre)/biblioteca',      color: Colors.indigo },
      { label: 'Calendario',    icon: 'calendar',        route: '/(padre)/calendario',      color: Colors.amber  },
      { label: 'Perfil',        icon: 'person-circle',   route: '/(padre)/perfil',          color: Colors.muted  },
    ],
  },
] as const

export default function PadreDashboard() {
  const { user, logout } = useAuth()
  const router = useRouter()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const hijos: any[] = data?.hijos ?? []
  const nombre = data?.nombre ?? user?.name ?? ''

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ─── Header ─── */}
        <View style={styles.header}>
          <View>
            <Text style={styles.sub}>Portal Representante</Text>
            <Text style={styles.name}>Hola, {nombre.split(' ')[0]} 👋</Text>
          </View>
          <TouchableOpacity onPress={logout} style={styles.logoutBtn}>
            <Ionicons name="log-out-outline" size={22} color={Colors.muted} />
          </TouchableOpacity>
        </View>

        {/* ─── Mis Hijos ─── */}
        {(hijos.length > 0 || isLoading) && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Mis Hijos</Text>

            {isLoading && (
              <View style={styles.hijoSkeleton} />
            )}

            {hijos.map((h: any, i: number) => (
              <TouchableOpacity
                key={h.id}
                style={[styles.hijoRow, i < hijos.length - 1 && styles.hijoRowBorder]}
                activeOpacity={0.75}
                onPress={() => router.push('/(padre)/notas')}
              >
                {/* Avatar */}
                <View style={styles.avatar}>
                  <Text style={styles.avatarTxt}>
                    {(h.nombre ?? '?').charAt(0).toUpperCase()}
                  </Text>
                </View>

                {/* Nombre y grupo */}
                <View style={{ flex: 1 }}>
                  <Text style={styles.hijoNombre}>{h.nombre}</Text>
                  <Text style={styles.hijoGrupo}>{h.grupo ?? 'Sin grupo asignado'}</Text>
                </View>

                {/* Acciones rápidas del hijo */}
                <View style={styles.hijoActions}>
                  <TouchableOpacity
                    style={styles.hijoActionBtn}
                    onPress={() => router.push('/(padre)/asistencia')}
                  >
                    <Ionicons name="calendar" size={15} color={Colors.green} />
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={styles.hijoActionBtn}
                    onPress={() => router.push('/(padre)/notas')}
                  >
                    <Ionicons name="bar-chart" size={15} color={Colors.blue} />
                  </TouchableOpacity>
                  <Ionicons name="chevron-forward" size={14} color={Colors.border} />
                </View>
              </TouchableOpacity>
            ))}

            {!isLoading && hijos.length === 0 && (
              <Text style={styles.empty}>No hay hijos registrados.</Text>
            )}
          </View>
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
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 14, paddingBottom: 100 },

  // Header
  header:         { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  sub:            { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .5 },
  name:           { fontSize: 22, fontWeight: '900', color: Colors.text },
  logoutBtn:      { padding: 8 },

  // Section wrapper
  section:        { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                    shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle:   { fontSize: 13, fontWeight: '800', color: Colors.text,
                    textTransform: 'uppercase', letterSpacing: .4, marginBottom: 2 },

  // Hijos
  hijoSkeleton:   { height: 60, borderRadius: 12, backgroundColor: Colors.border },
  hijoRow:        { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10 },
  hijoRowBorder:  { borderBottomWidth: 1, borderBottomColor: Colors.border },
  avatar:         { width: 44, height: 44, borderRadius: 13, backgroundColor: ACCENT + '18',
                    alignItems: 'center', justifyContent: 'center' },
  avatarTxt:      { fontSize: 18, fontWeight: '900', color: ACCENT },
  hijoNombre:     { fontSize: 14, fontWeight: '800', color: Colors.text },
  hijoGrupo:      { fontSize: 12, color: Colors.muted, marginTop: 2 },
  hijoActions:    { flexDirection: 'row', alignItems: 'center', gap: 6 },
  hijoActionBtn:  { width: 30, height: 30, borderRadius: 9, backgroundColor: Colors.bg,
                    alignItems: 'center', justifyContent: 'center' },
  empty:          { textAlign: 'center', color: Colors.muted, paddingVertical: 12 },

  // Grid de acceso rápido
  grid:           { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  gridItem:       { alignItems: 'center', gap: 6, width: '28%', minWidth: 72 },
  gridIcon:       { width: 52, height: 52, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  gridLbl:        { fontSize: 10, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
})
