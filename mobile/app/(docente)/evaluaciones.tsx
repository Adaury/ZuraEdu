import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { evaluacionesDocenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function MisEvaluaciones() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['mis-evaluaciones-docente'],
    queryFn:  () => evaluacionesDocenteApi.index().then(r => r.data),
  })

  const evaluaciones: any[] = data?.evaluaciones ?? []

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.indigo} />}
      >
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Mis Evaluaciones</Text>
          <Text style={s.pageSub}>Evaluaciones de desempeño docente</Text>
        </View>

        {/* Banner promedio */}
        {data?.promedio_general != null && (
          <View style={s.banner}>
            <View style={s.bannerLeft}>
              <Text style={s.bannerPromedio}>{data.promedio_general}<Text style={s.bannerDen}> /5</Text></Text>
              <Text style={s.bannerLabel}>Promedio general</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={s.bannerTotal}>{data.total} evaluación{data.total !== 1 ? 'es' : ''}</Text>
            </View>
            <Ionicons name="clipboard-outline" size={32} color="rgba(255,255,255,.4)" />
          </View>
        )}

        {isLoading && <ActivityIndicator color={Colors.indigo} style={{ marginTop: 40 }} />}

        {!isLoading && evaluaciones.length === 0 && (
          <View style={s.empty}>
            <Ionicons name="clipboard-outline" size={52} color={Colors.border} />
            <Text style={s.emptyTxt}>Sin evaluaciones registradas</Text>
          </View>
        )}

        {evaluaciones.map((ev: any) => (
          <View key={ev.id} style={s.card}>
            {/* Header */}
            <View style={s.cardHeader}>
              <View style={{ flex: 1 }}>
                <Text style={s.periodo}>{ev.periodo_evaluado}</Text>
                <Text style={s.cardSub}>
                  {ev.evaluador ? `Evaluado por: ${ev.evaluador}` : ''}{ev.evaluador && ev.fecha ? ' · ' : ''}{ev.fecha}
                </Text>
              </View>
              <View style={{ alignItems: 'flex-end', gap: 4 }}>
                <Text style={s.promedio}>{ev.promedio}<Text style={s.denominador}>/5</Text></Text>
                <View style={[s.nivelBadge, { backgroundColor: ev.nivel_bg }]}>
                  <Text style={[s.nivelTxt, { color: ev.nivel_color }]}>{ev.nivel_label}</Text>
                </View>
              </View>
            </View>

            {/* Criterios */}
            <View style={s.criterios}>
              {(ev.criterios ?? []).map((c: any) => {
                const pct = (c.valor / 5) * 100
                const barColor = c.valor >= 4 ? Colors.green : c.valor >= 3 ? Colors.indigo : c.valor >= 2 ? Colors.amber : Colors.red
                return (
                  <View key={c.key} style={s.criterioRow}>
                    <Text style={s.criterioLabel}>{c.label}</Text>
                    <View style={s.barBg}>
                      <View style={[s.barFill, { width: `${pct}%` as any, backgroundColor: barColor }]} />
                    </View>
                    <Text style={s.criterioVal}>{c.valor}/5</Text>
                  </View>
                )
              })}
            </View>

            {ev.observaciones && (
              <View style={s.obs}>
                <Text style={s.obsLabel}>Observaciones</Text>
                <Text style={s.obsTxt}>{ev.observaciones}</Text>
              </View>
            )}
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const s = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, gap: 14, paddingBottom: 32 },
  pageHeader:    { marginBottom: 2 },
  pageTitle:     { fontSize: 22, fontWeight: '900', color: Colors.text },
  pageSub:       { fontSize: 13, color: Colors.muted, marginTop: 2 },
  banner:        { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#1e3a8a', borderRadius: 16, padding: 16 },
  bannerLeft:    { alignItems: 'center', marginRight: 4 },
  bannerPromedio:{ fontSize: 28, fontWeight: '900', color: '#fff' },
  bannerDen:     { fontSize: 14, fontWeight: '500' },
  bannerLabel:   { fontSize: 10, color: 'rgba(255,255,255,.7)', marginTop: 2 },
  bannerTotal:   { fontSize: 15, fontWeight: '700', color: '#fff' },
  empty:         { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:      { fontSize: 14, color: Colors.muted },
  card:          { backgroundColor: '#fff', borderRadius: 14, padding: 16, gap: 12, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  cardHeader:    { flexDirection: 'row', alignItems: 'flex-start', gap: 8 },
  periodo:       { fontSize: 15, fontWeight: '800', color: Colors.text },
  cardSub:       { fontSize: 12, color: Colors.muted, marginTop: 2 },
  promedio:      { fontSize: 24, fontWeight: '900', color: Colors.text },
  denominador:   { fontSize: 12, fontWeight: '500', color: Colors.muted },
  nivelBadge:    { borderRadius: 99, paddingHorizontal: 10, paddingVertical: 3 },
  nivelTxt:      { fontSize: 11, fontWeight: '700' },
  criterios:     { gap: 8 },
  criterioRow:   { flexDirection: 'row', alignItems: 'center', gap: 8 },
  criterioLabel: { fontSize: 11, color: Colors.muted, width: 120 },
  barBg:         { flex: 1, height: 6, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  barFill:       { height: '100%', borderRadius: 99 },
  criterioVal:   { fontSize: 11, fontWeight: '700', color: Colors.text, width: 28, textAlign: 'right' },
  obs:           { backgroundColor: '#eff6ff', borderRadius: 10, padding: 10, borderLeftWidth: 3, borderLeftColor: Colors.indigo },
  obsLabel:      { fontSize: 10, fontWeight: '800', color: Colors.indigo, letterSpacing: 0.5, marginBottom: 3 },
  obsTxt:        { fontSize: 12, color: Colors.text },
})
