import React from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useRouter } from 'expo-router'
import { carnetApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

const TIPO_ICON: Record<string, keyof typeof Ionicons.glyphMap> = {
  entrada:     'enter-outline',
  salida:      'exit-outline',
  biblioteca:  'library-outline',
  comedor:     'restaurant-outline',
  laboratorio: 'flask-outline',
  evento:      'calendar-outline',
  prestamo:    'book-outline',
}

function capitalize(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

function KpiBox({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <View style={[styles.kpi, { borderTopColor: color }]}>
      <Text style={[styles.kpiVal, { color }]}>{value}</Text>
      <Text style={styles.kpiLbl}>{label}</Text>
    </View>
  )
}

export default function CarnetDocente() {
  const router = useRouter()

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ['docente-carnet-hoy'],
    queryFn:  () => carnetApi.grupoHoy().then(r => r.data),
    staleTime: 60_000,
    refetchInterval: 120_000,
  })

  const accesos: any[] = data?.accesos ?? []
  const today = new Date().toLocaleDateString('es-DO', { weekday: 'long', day: 'numeric', month: 'long' })

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ── Header ── */}
        <View style={[styles.header, { backgroundColor: ACCENT }]}>
          <View style={{ flex: 1 }}>
            <Text style={styles.headerTitle}>Control de Acceso</Text>
            <Text style={styles.headerSub}>{capitalize(today)}</Text>
          </View>
          <TouchableOpacity
            style={styles.scanBtn}
            onPress={() => router.push('/(docente)/carnet-scan')}
            activeOpacity={0.8}
          >
            <Ionicons name="qr-code" size={20} color={ACCENT} />
            <Text style={styles.scanBtnTxt}>Escanear</Text>
          </TouchableOpacity>
        </View>

        {/* ── KPIs ── */}
        {isLoading ? (
          <ActivityIndicator color={ACCENT} style={{ marginTop: 24 }} />
        ) : (
          <View style={styles.kpiRow}>
            <KpiBox label="Total"     value={data?.total     ?? 0} color={Colors.muted}  />
            <KpiBox label="Entradas"  value={data?.entradas  ?? 0} color={Colors.blue}   />
            <KpiBox label="Tardanzas" value={data?.tardanzas ?? 0} color={Colors.amber}  />
            <KpiBox label="Ausentes"  value={data?.ausentes  ?? 0} color={Colors.red}    />
          </View>
        )}

        {/* ── Lista de accesos ── */}
        {isError && (
          <View style={styles.empty}>
            <Ionicons name="cloud-offline-outline" size={40} color={Colors.muted} />
            <Text style={styles.emptyTxt}>Error al cargar. Desliza para reintentar.</Text>
          </View>
        )}

        {!isLoading && !isError && accesos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="card-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin accesos registrados hoy</Text>
            <Text style={styles.emptyTxt}>
              Los accesos de tus estudiantes aparecerán aquí en tiempo real.
            </Text>
            <TouchableOpacity
              style={[styles.scanBtnFull, { backgroundColor: ACCENT }]}
              onPress={() => router.push('/(docente)/carnet-scan')}
            >
              <Ionicons name="qr-code" size={18} color="#fff" />
              <Text style={styles.scanBtnFullTxt}>Escanear carnet ahora</Text>
            </TouchableOpacity>
          </View>
        )}

        {accesos.length > 0 && (
          <View style={styles.listCard}>
            <Text style={styles.listTitle}>Accesos del día ({accesos.length})</Text>
            {accesos.map((a: any) => (
              <View key={a.id} style={styles.row}>
                <View style={[styles.iconBox, { backgroundColor: a.estado_color + '22' }]}>
                  <Ionicons
                    name={TIPO_ICON[a.tipo_evento] ?? 'swap-horizontal-outline'}
                    size={17}
                    color={a.estado_color}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.rowNombre} numberOfLines={1}>{a.nombre}</Text>
                  <Text style={styles.rowSub}>
                    {capitalize(a.tipo_evento)}{a.zona ? ` · ${a.zona}` : ''}
                  </Text>
                </View>
                <View style={styles.rowRight}>
                  <Text style={styles.rowHora}>{a.hora}</Text>
                  <View style={[styles.badge, { backgroundColor: a.estado_color + '22' }]}>
                    <Text style={[styles.badgeTxt, { color: a.estado_color }]}>{a.estado_label}</Text>
                  </View>
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
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, gap: 14, paddingBottom: 40 },

  header:        { borderRadius: 16, padding: 16, flexDirection: 'row', alignItems: 'center', gap: 12 },
  headerTitle:   { fontSize: 17, fontWeight: '900', color: '#fff' },
  headerSub:     { fontSize: 12, color: 'rgba(255,255,255,.75)', marginTop: 2 },
  scanBtn:       { backgroundColor: '#fff', borderRadius: 12, paddingHorizontal: 14,
                   paddingVertical: 10, flexDirection: 'row', alignItems: 'center', gap: 6 },
  scanBtnTxt:    { fontSize: 13, fontWeight: '800', color: Colors.roles.docente },

  kpiRow:        { flexDirection: 'row', gap: 8 },
  kpi:           { flex: 1, backgroundColor: '#fff', borderRadius: 14, padding: 12,
                   alignItems: 'center', borderTopWidth: 3,
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  kpiVal:        { fontSize: 24, fontWeight: '900', lineHeight: 28 },
  kpiLbl:        { fontSize: 10, fontWeight: '700', color: Colors.muted, marginTop: 2 },

  empty:         { alignItems: 'center', gap: 10, paddingVertical: 32, paddingHorizontal: 20 },
  emptyTitle:    { fontSize: 15, fontWeight: '800', color: Colors.text },
  emptyTxt:      { fontSize: 13, color: Colors.muted, textAlign: 'center', lineHeight: 19 },
  scanBtnFull:   { flexDirection: 'row', alignItems: 'center', gap: 8, borderRadius: 14,
                   paddingVertical: 12, paddingHorizontal: 20, marginTop: 4 },
  scanBtnFullTxt:{ color: '#fff', fontWeight: '800', fontSize: 14 },

  listCard:      { backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden',
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  listTitle:     { fontSize: 13, fontWeight: '800', color: Colors.text,
                   paddingHorizontal: 14, paddingVertical: 10,
                   borderBottomWidth: 1, borderBottomColor: Colors.border },
  row:           { flexDirection: 'row', alignItems: 'center', gap: 12,
                   padding: 12, paddingHorizontal: 14,
                   borderBottomWidth: 1, borderBottomColor: Colors.border },
  iconBox:       { width: 34, height: 34, borderRadius: 9, alignItems: 'center', justifyContent: 'center' },
  rowNombre:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  rowSub:        { fontSize: 11, color: Colors.muted },
  rowRight:      { alignItems: 'flex-end', gap: 3 },
  rowHora:       { fontSize: 12, fontWeight: '700', color: Colors.text },
  badge:         { paddingHorizontal: 7, paddingVertical: 2, borderRadius: 99 },
  badgeTxt:      { fontSize: 10, fontWeight: '800' },
})
