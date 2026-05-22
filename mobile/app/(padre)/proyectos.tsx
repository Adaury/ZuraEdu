import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useLocalSearchParams } from 'expo-router'
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

export default function ProyectosPadre() {
  const { id } = useLocalSearchParams<{ id: string }>()
  const hijoId = id ? parseInt(id) : 0

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['proyectos-hijo', hijoId],
    queryFn:  () => proyectosApi.hijo(hijoId).then(r => r.data),
    staleTime: 60_000,
    enabled:  !!hijoId,
  })

  const proyectos: any[] = data?.data ?? []
  const hijo = data?.estudiante

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {!!hijo && (
          <View style={styles.hijoCard}>
            <View style={styles.hijoAvatar}>
              <Text style={styles.hijoAvatarTxt}>{hijo.nombre.charAt(0).toUpperCase()}</Text>
            </View>
            <Text style={styles.hijoNombre}>{hijo.nombre}</Text>
          </View>
        )}

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

        {isLoading && [0, 1].map(i => <View key={i} style={styles.skeleton} />)}

        {proyectos.map((p: any) => {
          const color = ESTADO_COLORS[p.estado] ?? Colors.muted
          const pct   = p.fases_total > 0 ? Math.round((p.fases_ok / p.fases_total) * 100) : 0
          return (
            <View key={p.id} style={styles.card}>
              <View style={styles.cardHeader}>
                <View style={[styles.icon, { backgroundColor: color + '18' }]}>
                  <Ionicons name="flask" size={18} color={color} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.titulo}>{p.titulo}</Text>
                  {!!p.area && <Text style={[styles.area, { color }]}>{p.area}</Text>}
                </View>
                <View style={[styles.badge, { backgroundColor: color + '18' }]}>
                  <Text style={[styles.badgeTxt, { color }]}>{p.estado_label}</Text>
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
                    <Text style={styles.metaTxt}>{p.fecha_inicio} → {p.fecha_fin ?? '?'}</Text>
                  </View>
                )}
              </View>

              {p.fases_total > 0 && (
                <View style={styles.progressWrap}>
                  <View style={styles.progressHeader}>
                    <Text style={styles.progressLbl}>Avance</Text>
                    <Text style={[styles.progressPct, { color }]}>{pct}%</Text>
                  </View>
                  <View style={styles.progressBar}>
                    <View style={[styles.progressFill, { width: `${pct}%` as any, backgroundColor: color }]} />
                  </View>
                  <Text style={styles.progressSub}>{p.fases_ok}/{p.fases_total} fases</Text>
                </View>
              )}
            </View>
          )
        })}

        {!isLoading && proyectos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="flask-outline" size={40} color={Colors.border} />
            <Text style={styles.emptyTxt}>Sin proyectos asignados.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 12, paddingBottom: 32 },
  hijoCard:       { flexDirection: 'row', alignItems: 'center', gap: 12,
                    backgroundColor: '#fff', borderRadius: 16, padding: 14,
                    shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  hijoAvatar:     { width: 42, height: 42, borderRadius: 13, backgroundColor: Colors.roles.padre + '18',
                    alignItems: 'center', justifyContent: 'center' },
  hijoAvatarTxt:  { fontSize: 18, fontWeight: '900', color: Colors.roles.padre },
  hijoNombre:     { fontSize: 15, fontWeight: '800', color: Colors.text },
  kpiRow:         { flexDirection: 'row', gap: 10 },
  kpi:            { flex: 1, borderRadius: 14, padding: 12, alignItems: 'center' },
  kpiVal:         { fontSize: 22, fontWeight: '900' },
  kpiLbl:         { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },
  card:           { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                    shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader:     { flexDirection: 'row', alignItems: 'center', gap: 10 },
  icon:           { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  titulo:         { fontSize: 14, fontWeight: '800', color: Colors.text },
  area:           { fontSize: 11, fontWeight: '600', marginTop: 2, textTransform: 'capitalize' },
  badge:          { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  badgeTxt:       { fontSize: 10, fontWeight: '700' },
  metaRow:        { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  metaItem:       { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:        { fontSize: 11, color: Colors.muted },
  progressWrap:   { gap: 4 },
  progressHeader: { flexDirection: 'row', justifyContent: 'space-between' },
  progressLbl:    { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  progressPct:    { fontSize: 11, fontWeight: '800' },
  progressBar:    { height: 6, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  progressFill:   { height: 6, borderRadius: 99 },
  progressSub:    { fontSize: 10, color: Colors.muted },
  skeleton:       { height: 100, borderRadius: 16, backgroundColor: Colors.border },
  empty:          { alignItems: 'center', gap: 10, paddingVertical: 40 },
  emptyTxt:       { color: Colors.muted, fontSize: 13 },
})
