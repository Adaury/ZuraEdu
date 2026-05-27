import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { gamificacionApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const CAT_INFO: Record<string, { label: string; color: string; icon: keyof typeof Ionicons.glyphMap }> = {
  academico:     { label: 'Académico',     color: Colors.blue,   icon: 'school' },
  asistencia:    { label: 'Asistencia',    color: Colors.green,  icon: 'calendar' },
  conducta:      { label: 'Conducta',      color: '#8b5cf6',     icon: 'shield-checkmark' },
  participacion: { label: 'Participación', color: '#f59e0b',     icon: 'hand-left' },
  extra:         { label: 'Extra',         color: Colors.muted,  icon: 'star' },
}

const MEDALLA = (pos: number) =>
  pos === 1 ? '🥇' : pos === 2 ? '🥈' : pos === 3 ? '🥉' : `${pos}.`

export default function MisPuntosEstudiante() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['estudiante-puntos'],
    queryFn:  () => gamificacionApi.misPuntos().then(r => r.data),
  })

  const totalPuntos     = data?.totalPuntos ?? 0
  const insignias       = data?.insignias   ?? []
  const historial       = data?.historial   ?? []
  const puntosCategoria = data?.puntosCategoria ?? []
  const ranking         = data?.ranking     ?? []
  const miPosicion      = data?.miPosicion  ?? null
  const totalEnGrupo    = data?.totalEnGrupo ?? 0
  const insigniasCount  = insignias.filter((i: any) => i.obtenida).length

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.roles.estudiante} />}
      >
        {/* Header */}
        <View style={styles.headerBanner}>
          <Ionicons name="game-controller" size={28} color="#fff" style={{ opacity: 0.9 }} />
          <View style={{ flex: 1 }}>
            <Text style={styles.headerTitle}>Mis Puntos</Text>
            <Text style={styles.headerSub}>Tu progreso y ranking del grupo</Text>
          </View>
        </View>

        {/* Stats */}
        <View style={styles.statsRow}>
          <View style={[styles.statCard, { backgroundColor: '#eef2ff' }]}>
            <Text style={[styles.statVal, { color: '#4338ca' }]}>{totalPuntos}</Text>
            <Text style={styles.statLbl}>Puntos</Text>
          </View>
          <View style={[styles.statCard, { backgroundColor: '#fef9c3' }]}>
            <Text style={[styles.statVal, { color: '#b45309' }]}>
              {miPosicion ? `#${miPosicion}` : 'N/A'}
            </Text>
            <Text style={styles.statLbl}>Posición</Text>
          </View>
          <View style={[styles.statCard, { backgroundColor: '#fef3c7' }]}>
            <Text style={[styles.statVal, { color: '#d97706' }]}>{insigniasCount}</Text>
            <Text style={styles.statLbl}>Insignias</Text>
          </View>
        </View>

        {/* Insignias */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Mis Insignias</Text>
          <View style={styles.insigniasGrid}>
            {insignias.map((ins: any) => (
              <View
                key={ins.tipo}
                style={[styles.insigniaItem, { opacity: ins.obtenida ? 1 : 0.4 }]}
              >
                <View style={[styles.insigniaIcon, { backgroundColor: ins.obtenida ? '#fef9c3' : '#f3f4f6', borderColor: ins.obtenida ? '#fde68a' : '#e5e7eb' }]}>
                  <Text style={{ fontSize: 22 }}>
                    {ins.obtenida ? '⭐' : '🔒'}
                  </Text>
                </View>
                <Text style={styles.insigniaLbl} numberOfLines={2}>{ins.label}</Text>
                {ins.obtenida && ins.fecha_obtencion && (
                  <Text style={styles.insigniaFecha}>{ins.fecha_obtencion}</Text>
                )}
              </View>
            ))}
          </View>
        </View>

        {/* Puntos por categoría */}
        {puntosCategoria.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Por Categoría</Text>
            {puntosCategoria.map((cat: any) => {
              const info = CAT_INFO[cat.categoria] ?? { label: cat.categoria, color: Colors.muted, icon: 'star' }
              return (
                <View key={cat.categoria} style={styles.catRow}>
                  <View style={[styles.catIcon, { backgroundColor: info.color + '20' }]}>
                    <Ionicons name={info.icon} size={14} color={info.color} />
                  </View>
                  <Text style={styles.catLabel}>{info.label}</Text>
                  <Text style={[styles.catPts, { color: info.color }]}>{cat.total} pts</Text>
                </View>
              )
            })}
          </View>
        )}

        {/* Ranking */}
        {ranking.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Top 10 del Grupo</Text>
            {ranking.map((item: any) => (
              <View
                key={item.posicion}
                style={[styles.rankRow, item.es_yo && styles.rankRowMio]}
              >
                <Text style={styles.rankMedalla}>{MEDALLA(item.posicion)}</Text>
                <Text
                  style={[styles.rankNombre, item.es_yo && { color: '#4338ca', fontWeight: '800' }]}
                  numberOfLines={1}
                >
                  {item.nombre}
                  {item.es_yo ? ' (tú)' : ''}
                </Text>
                <Text style={[styles.rankPts, item.es_yo && { color: '#4338ca' }]}>
                  {item.total} pts
                </Text>
              </View>
            ))}
            {miPosicion && miPosicion > 10 && (
              <View style={[styles.rankRow, styles.rankRowMio]}>
                <Text style={styles.rankMedalla}>#{miPosicion}</Text>
                <Text style={[styles.rankNombre, { color: '#4338ca', fontWeight: '800' }]}>Tú</Text>
                <Text style={[styles.rankPts, { color: '#4338ca' }]}>{totalPuntos} pts</Text>
              </View>
            )}
          </View>
        )}

        {/* Historial */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Historial</Text>
          {historial.length === 0 ? (
            <Text style={styles.empty}>Sin puntos registrados aún.</Text>
          ) : (
            historial.map((p: any, i: number) => {
              const info = CAT_INFO[p.categoria] ?? { label: p.categoria, color: Colors.muted, icon: 'star' }
              return (
                <View key={i} style={styles.histRow}>
                  <View style={[styles.catIcon, { backgroundColor: info.color + '20' }]}>
                    <Ionicons name={info.icon} size={13} color={info.color} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.histConcepto} numberOfLines={1}>{p.concepto}</Text>
                    <Text style={styles.histFecha}>{p.fecha}</Text>
                  </View>
                  <Text style={[styles.histPts, { color: info.color }]}>+{p.puntos}</Text>
                </View>
              )
            })
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, gap: 14, paddingBottom: 32 },
  headerBanner:  { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#6366f1', borderRadius: 16, padding: 16 },
  headerTitle:   { fontSize: 18, fontWeight: '900', color: '#fff' },
  headerSub:     { fontSize: 12, color: 'rgba(255,255,255,.75)', marginTop: 2 },
  statsRow:      { flexDirection: 'row', gap: 10 },
  statCard:      { flex: 1, borderRadius: 14, padding: 14, alignItems: 'center' },
  statVal:       { fontSize: 22, fontWeight: '900', lineHeight: 26 },
  statLbl:       { fontSize: 11, fontWeight: '600', color: Colors.muted, marginTop: 2 },
  section:       { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle:  { fontSize: 14, fontWeight: '700', color: Colors.text },
  insigniasGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  insigniaItem:  { alignItems: 'center', width: '30%', gap: 4 },
  insigniaIcon:  { width: 50, height: 50, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: 1.5 },
  insigniaLbl:   { fontSize: 10, fontWeight: '700', color: Colors.text, textAlign: 'center', lineHeight: 13 },
  insigniaFecha: { fontSize: 9, color: Colors.muted, textAlign: 'center' },
  catRow:        { flexDirection: 'row', alignItems: 'center', gap: 10 },
  catIcon:       { width: 28, height: 28, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  catLabel:      { flex: 1, fontSize: 13, fontWeight: '600', color: Colors.text },
  catPts:        { fontSize: 13, fontWeight: '800' },
  rankRow:       { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: Colors.border },
  rankRowMio:    { backgroundColor: '#eef2ff', borderRadius: 10, paddingHorizontal: 8, borderBottomWidth: 0 },
  rankMedalla:   { width: 28, fontSize: 14, textAlign: 'center' },
  rankNombre:    { flex: 1, fontSize: 13, color: Colors.text, fontWeight: '600' },
  rankPts:       { fontSize: 13, fontWeight: '800', color: Colors.muted },
  histRow:       { flexDirection: 'row', alignItems: 'center', gap: 10, paddingVertical: 4, borderBottomWidth: 1, borderBottomColor: Colors.border },
  histConcepto:  { fontSize: 13, fontWeight: '600', color: Colors.text },
  histFecha:     { fontSize: 11, color: Colors.muted },
  histPts:       { fontSize: 14, fontWeight: '900' },
  empty:         { textAlign: 'center', color: Colors.muted, paddingVertical: 12, fontSize: 13 },
})
