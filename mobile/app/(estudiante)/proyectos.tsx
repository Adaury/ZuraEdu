import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { proyectosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ESTADO_COLORS: Record<string, string> = {
  planificacion: Colors.amber,
  desarrollo:    Colors.blue,
  finalizado:    Colors.green,
  presentado:    Colors.indigo,
}

const AREA_COLORS: Record<string, string> = {
  ciencias:    Colors.green,
  matematica:  Colors.blue,
  humanidades: Colors.purple,
  tecnologia:  Colors.indigo,
  arte:        '#ec4899',
  otro:        Colors.muted,
}

export default function ProyectosEstudiante() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['proyectos-estudiante'],
    queryFn:  () => proyectosApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const proyectos: any[] = data?.data ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {/* KPI */}
        <View style={styles.kpiRow}>
          <View style={[styles.kpi, { backgroundColor: '#eff6ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.blue }]}>{proyectos.length}</Text>
            <Text style={styles.kpiLbl}>Proyectos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#f0fdf4' }]}>
            <Text style={[styles.kpiVal, { color: Colors.green }]}>
              {proyectos.filter(p => p.estado === 'finalizado' || p.estado === 'presentado').length}
            </Text>
            <Text style={styles.kpiLbl}>Finalizados</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#fefce8' }]}>
            <Text style={[styles.kpiVal, { color: Colors.amber }]}>
              {proyectos.filter(p => p.estado === 'desarrollo').length}
            </Text>
            <Text style={styles.kpiLbl}>En Desarrollo</Text>
          </View>
        </View>

        {/* Lista */}
        {isLoading && (
          <View style={styles.card}>
            {[0, 1, 2].map(i => (
              <View key={i} style={[styles.skeleton, { marginBottom: 10 }]} />
            ))}
          </View>
        )}

        {proyectos.map((p: any) => {
          const estadoColor = ESTADO_COLORS[p.estado] ?? Colors.muted
          const areaColor   = AREA_COLORS[p.area]    ?? Colors.muted
          const pct         = p.fases_total > 0 ? Math.round((p.fases_ok / p.fases_total) * 100) : 0
          return (
            <View key={p.id} style={styles.card}>
              <View style={styles.cardHeader}>
                <View style={[styles.areaIcon, { backgroundColor: areaColor + '18' }]}>
                  <Ionicons name="flask" size={18} color={areaColor} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.titulo}>{p.titulo}</Text>
                  {!!p.area && <Text style={[styles.area, { color: areaColor }]}>{p.area}</Text>}
                </View>
                <View style={[styles.estadoBadge, { backgroundColor: estadoColor + '18' }]}>
                  <Text style={[styles.estadoTxt, { color: estadoColor }]}>{p.estado_label}</Text>
                </View>
              </View>

              <View style={styles.metaRow}>
                {!!p.tutor && (
                  <View style={styles.metaItem}>
                    <Ionicons name="person" size={12} color={Colors.muted} />
                    <Text style={styles.metaTxt}>{p.tutor}</Text>
                  </View>
                )}
                {!!p.fecha_inicio && (
                  <View style={styles.metaItem}>
                    <Ionicons name="calendar" size={12} color={Colors.muted} />
                    <Text style={styles.metaTxt}>{p.fecha_inicio}</Text>
                  </View>
                )}
                <View style={[styles.rolBadge, { backgroundColor: Colors.indigo + '18' }]}>
                  <Text style={[styles.rolTxt, { color: Colors.indigo }]}>{p.rol}</Text>
                </View>
              </View>

              {p.fases_total > 0 && (
                <View style={styles.progressWrap}>
                  <View style={styles.progressHeader}>
                    <Text style={styles.progressLbl}>Avance de fases</Text>
                    <Text style={[styles.progressPct, { color: estadoColor }]}>{pct}%</Text>
                  </View>
                  <View style={styles.progressBar}>
                    <View style={[styles.progressFill, { width: `${pct}%` as any, backgroundColor: estadoColor }]} />
                  </View>
                  <Text style={styles.progressSub}>{p.fases_ok} de {p.fases_total} fases completadas</Text>
                </View>
              )}
            </View>
          )
        })}

        {!isLoading && proyectos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="flask-outline" size={40} color={Colors.border} />
            <Text style={styles.emptyTxt}>No tienes proyectos asignados.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, gap: 12, paddingBottom: 32 },
  kpiRow:        { flexDirection: 'row', gap: 10 },
  kpi:           { flex: 1, borderRadius: 14, padding: 12, alignItems: 'center' },
  kpiVal:        { fontSize: 22, fontWeight: '900' },
  kpiLbl:        { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },
  card:          { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                   shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader:    { flexDirection: 'row', alignItems: 'center', gap: 10 },
  areaIcon:      { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  titulo:        { fontSize: 14, fontWeight: '800', color: Colors.text },
  area:          { fontSize: 11, fontWeight: '600', marginTop: 2, textTransform: 'capitalize' },
  estadoBadge:   { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  estadoTxt:     { fontSize: 10, fontWeight: '700' },
  metaRow:       { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 8 },
  metaItem:      { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:       { fontSize: 11, color: Colors.muted },
  rolBadge:      { borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },
  rolTxt:        { fontSize: 10, fontWeight: '700', textTransform: 'capitalize' },
  progressWrap:  { gap: 4 },
  progressHeader:{ flexDirection: 'row', justifyContent: 'space-between' },
  progressLbl:   { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  progressPct:   { fontSize: 11, fontWeight: '800' },
  progressBar:   { height: 6, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  progressFill:  { height: 6, borderRadius: 99 },
  progressSub:   { fontSize: 10, color: Colors.muted },
  skeleton:      { height: 80, borderRadius: 12, backgroundColor: Colors.border },
  empty:         { alignItems: 'center', gap: 10, paddingVertical: 40 },
  emptyTxt:      { color: Colors.muted, fontSize: 13 },
})
