import React, { useState, useEffect } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { dashboardApi, asistenciaApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

function estadoColor(e: string) {
  return e === 'presente' ? Colors.green : e === 'tardanza' ? Colors.amber : e === 'ausente' ? Colors.red : Colors.purple
}

export default function AsistenciaPadre() {
  const [hijoId, setHijoId] = useState<number | null>(null)

  const { data: dashData } = useQuery({
    queryKey: ['dashboard'],
    queryFn:  () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const hijos: any[] = dashData?.hijos ?? []
  const hijoActual   = hijoId ?? hijos[0]?.id ?? null

  useEffect(() => {
    if (!hijoId && hijos.length > 0) setHijoId(hijos[0].id)
  }, [hijos.length])

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['asistencia-hijo', hijoActual],
    queryFn:  () => asistenciaApi.hijo(hijoActual!).then(r => r.data),
    enabled:  hijoActual != null,
  })

  const porMateria: any[] = data?.por_materia ?? []
  const ultimas: any[]    = data?.ultimas     ?? []

  const stats = [
    { key: 'presentes', label: 'Presentes', value: data?.presentes ?? 0, color: Colors.green  },
    { key: 'tardanzas', label: 'Tardanzas', value: data?.tardanzas ?? 0, color: Colors.amber  },
    { key: 'ausentes',  label: 'Ausentes',  value: data?.ausentes  ?? 0, color: Colors.red    },
  ]

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Asistencia</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hijoRow} contentContainerStyle={{ gap: 8 }}>
            {hijos.map((h: any) => (
              <TouchableOpacity
                key={h.id}
                onPress={() => setHijoId(h.id)}
                style={[styles.hijoPill, hijoActual === h.id && styles.hijoPillActive]}
              >
                <Ionicons name="person" size={12} color={hijoActual === h.id ? '#fff' : Colors.muted} />
                <Text style={[styles.hijoPillTxt, hijoActual === h.id && styles.hijoPillTxtActive]}>
                  {h.nombre.split(' ')[0]}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Estadísticas globales */}
        <View style={styles.statsRow}>
          {stats.map(s => (
            <View key={s.key} style={[styles.statBox, { borderTopColor: s.color }]}>
              <Text style={[styles.statNum, { color: s.color }]}>{s.value}</Text>
              <Text style={styles.statLbl}>{s.label}</Text>
            </View>
          ))}
        </View>

        {data?.porcentaje != null && (
          <View style={styles.pctCard}>
            <Ionicons name="checkmark-circle" size={22} color={data.porcentaje >= 85 ? Colors.green : data.porcentaje >= 70 ? Colors.amber : Colors.red} />
            <Text style={styles.pctLbl}>Asistencia general</Text>
            <Text style={[styles.pctNum, { color: data.porcentaje >= 85 ? Colors.green : data.porcentaje >= 70 ? Colors.amber : Colors.red }]}>
              {data.porcentaje}%
            </Text>
          </View>
        )}

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 30 }} />}

        {/* Por materia */}
        {porMateria.length > 0 && (
          <>
            <Text style={styles.sectionLbl}>Por Asignatura</Text>
            {porMateria.map((m: any, i: number) => {
              const pct   = m.porcentaje ?? 0
              const color = pct >= 85 ? Colors.green : pct >= 70 ? Colors.amber : Colors.red
              return (
                <View key={i} style={styles.materiaRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.materiaName}>{m.asignatura}</Text>
                    <View style={styles.barBg}>
                      <View style={[styles.barFill, { width: `${pct}%`, backgroundColor: color }]} />
                    </View>
                  </View>
                  <Text style={[styles.pctBadge, { color }]}>{pct}%</Text>
                </View>
              )
            })}
          </>
        )}

        {/* Últimos registros */}
        {ultimas.length > 0 && (
          <>
            <Text style={styles.sectionLbl}>Últimos Registros</Text>
            {ultimas.map((r: any, i: number) => {
              const color = estadoColor(r.estado)
              return (
                <View key={i} style={styles.registroRow}>
                  <View style={[styles.dot, { backgroundColor: color }]} />
                  <View style={{ flex: 1 }}>
                    <Text style={styles.registroFecha}>{r.fecha}</Text>
                    <Text style={styles.registroAsig}>{r.asignatura}</Text>
                  </View>
                  <Text style={[styles.registroEstado, { color }]}>
                    {r.estado.charAt(0).toUpperCase() + r.estado.slice(1)}
                  </Text>
                </View>
              )
            })}
          </>
        )}

        {!isLoading && ultimas.length === 0 && hijoActual && (
          <Text style={styles.empty}>No hay registros de asistencia.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, paddingBottom: 32, gap: 8 },
  title:             { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoRow:           { marginBottom: 4 },
  hijoPill:          { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99, paddingHorizontal: 12, paddingVertical: 6 },
  hijoPillActive:    { backgroundColor: ACCENT, borderColor: ACCENT },
  hijoPillTxt:       { fontSize: 13, fontWeight: '700', color: Colors.muted },
  hijoPillTxtActive: { color: '#fff' },
  statsRow:          { flexDirection: 'row', gap: 8 },
  statBox:           { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10, alignItems: 'center', borderTopWidth: 3, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  statNum:           { fontSize: 22, fontWeight: '900' },
  statLbl:           { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  pctCard:           { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: '#fff', borderRadius: 12, padding: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  pctLbl:            { flex: 1, fontSize: 14, fontWeight: '600', color: Colors.text },
  pctNum:            { fontSize: 18, fontWeight: '900' },
  sectionLbl:        { fontSize: 13, fontWeight: '800', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5, marginTop: 4 },
  materiaRow:        { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  materiaName:       { fontSize: 13, fontWeight: '700', color: Colors.text, marginBottom: 6 },
  barBg:             { height: 6, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  barFill:           { height: 6, borderRadius: 99 },
  pctBadge:          { fontSize: 14, fontWeight: '800', minWidth: 44, textAlign: 'right' },
  registroRow:       { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  dot:               { width: 10, height: 10, borderRadius: 99 },
  registroFecha:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  registroAsig:      { fontSize: 12, color: Colors.muted },
  registroEstado:    { fontSize: 12, fontWeight: '700' },
  empty:             { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
