import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl, ActivityIndicator, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { reunionesDocenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ESTADO_COLOR: Record<string, [string, string]> = {
  programada: ['#dbeafe', '#1e40af'],
  realizada:  ['#dcfce7', '#166534'],
  cancelada:  ['#fee2e2', '#991b1b'],
}

const FILTROS = ['todas', 'programada', 'realizada', 'cancelada'] as const
type Filtro = typeof FILTROS[number]

export default function MisReuniones() {
  const [filtro, setFiltro] = useState<Filtro>('todas')

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['mis-reuniones-docente'],
    queryFn:  () => reunionesDocenteApi.index().then(r => r.data),
  })

  const todas: any[] = data?.reuniones ?? []
  const reuniones = filtro === 'todas' ? todas : todas.filter((r: any) => r.estado === filtro)

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.indigo} />}
      >
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Mis Reuniones</Text>
          <Text style={s.pageSub}>Actas y convocatorias</Text>
        </View>

        {/* KPIs */}
        <View style={s.kpiRow}>
          <View style={[s.kpi, { backgroundColor: '#eff6ff' }]}>
            <Text style={[s.kpiNum, { color: Colors.indigo }]}>{data?.programadas ?? 0}</Text>
            <Text style={s.kpiLabel}>Programadas</Text>
          </View>
          <View style={[s.kpi, { backgroundColor: '#f0fdf4' }]}>
            <Text style={[s.kpiNum, { color: Colors.green }]}>{data?.realizadas ?? 0}</Text>
            <Text style={s.kpiLabel}>Realizadas</Text>
          </View>
          <View style={[s.kpi, { backgroundColor: '#f8fafc' }]}>
            <Text style={[s.kpiNum, { color: Colors.muted }]}>{data?.total ?? 0}</Text>
            <Text style={s.kpiLabel}>Total</Text>
          </View>
        </View>

        {/* Filtros */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={s.filtros}>
          {FILTROS.map(f => (
            <TouchableOpacity
              key={f}
              style={[s.filtroBtn, filtro === f && s.filtroBtnActive]}
              onPress={() => setFiltro(f)}
            >
              <Text style={[s.filtroTxt, filtro === f && s.filtroTxtActive]}>
                {f === 'todas' ? 'Todas' : f.charAt(0).toUpperCase() + f.slice(1) + 's'}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {isLoading && <ActivityIndicator color={Colors.indigo} style={{ marginTop: 40 }} />}

        {!isLoading && reuniones.length === 0 && (
          <View style={s.empty}>
            <Ionicons name="journal-outline" size={52} color={Colors.border} />
            <Text style={s.emptyTxt}>Sin reuniones{filtro !== 'todas' ? ` ${filtro}s` : ''}</Text>
          </View>
        )}

        {reuniones.map((r: any) => {
          const [bgColor, txtColor] = ESTADO_COLOR[r.estado] ?? ['#f1f5f9', Colors.muted]
          return (
            <View key={r.id} style={s.card}>
              <View style={s.cardTop}>
                <View style={{ flex: 1, gap: 4 }}>
                  <View style={s.row}>
                    <View style={[s.estadoBadge, { backgroundColor: bgColor }]}>
                      <Text style={[s.estadoTxt, { color: txtColor }]}>{r.estado_label}</Text>
                    </View>
                    <View style={s.tipoBadge}>
                      <Text style={s.tipoTxt}>{r.tipo_label}</Text>
                    </View>
                    {r.es_convocante && (
                      <View style={s.convBadge}>
                        <Text style={s.convTxt}>Convocante</Text>
                      </View>
                    )}
                  </View>
                  <Text style={s.titulo}>{r.titulo}</Text>
                </View>
              </View>

              <View style={s.metaRow}>
                <View style={s.metaItem}>
                  <Ionicons name="calendar-outline" size={13} color={Colors.muted} />
                  <Text style={s.metaTxt}>{r.fecha_label} · {r.hora_label}</Text>
                </View>
                {r.lugar && (
                  <View style={s.metaItem}>
                    <Ionicons name="location-outline" size={13} color={Colors.muted} />
                    <Text style={s.metaTxt}>{r.lugar}</Text>
                  </View>
                )}
              </View>

              {r.agenda && (
                <Text style={s.agenda} numberOfLines={2}>{r.agenda}</Text>
              )}

              {r.acuerdos_total > 0 && (
                <View style={s.acuerdosRow}>
                  <Ionicons name="checkmark-circle-outline" size={14} color={Colors.green} />
                  <Text style={s.acuerdosTxt}>
                    {r.acuerdos_cumplidos}/{r.acuerdos_total} acuerdos cumplidos
                  </Text>
                </View>
              )}
            </View>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const s = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 12, paddingBottom: 32 },
  pageHeader:     { marginBottom: 2 },
  pageTitle:      { fontSize: 22, fontWeight: '900', color: Colors.text },
  pageSub:        { fontSize: 13, color: Colors.muted, marginTop: 2 },
  kpiRow:         { flexDirection: 'row', gap: 8 },
  kpi:            { flex: 1, borderRadius: 12, padding: 12, alignItems: 'center' },
  kpiNum:         { fontSize: 22, fontWeight: '900' },
  kpiLabel:       { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  filtros:        { flexGrow: 0 },
  filtroBtn:      { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: Colors.border, marginRight: 8 },
  filtroBtnActive:{ backgroundColor: Colors.indigo },
  filtroTxt:      { fontSize: 13, fontWeight: '700', color: Colors.muted },
  filtroTxtActive:{ color: '#fff' },
  empty:          { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:       { fontSize: 14, color: Colors.muted },
  card:           { backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  cardTop:        { flexDirection: 'row', gap: 8 },
  row:            { flexDirection: 'row', flexWrap: 'wrap', gap: 6, alignItems: 'center' },
  estadoBadge:    { borderRadius: 99, paddingHorizontal: 8, paddingVertical: 3 },
  estadoTxt:      { fontSize: 10, fontWeight: '700' },
  tipoBadge:      { backgroundColor: '#f1f5f9', borderRadius: 99, paddingHorizontal: 8, paddingVertical: 3 },
  tipoTxt:        { fontSize: 10, fontWeight: '600', color: Colors.muted },
  convBadge:      { backgroundColor: '#ede9fe', borderRadius: 99, paddingHorizontal: 8, paddingVertical: 3 },
  convTxt:        { fontSize: 10, fontWeight: '700', color: '#5b21b6' },
  titulo:         { fontSize: 15, fontWeight: '800', color: Colors.text },
  metaRow:        { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  metaItem:       { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:        { fontSize: 12, color: Colors.muted },
  agenda:         { fontSize: 12, color: Colors.muted, fontStyle: 'italic', backgroundColor: '#f8fafc', borderRadius: 8, padding: 8 },
  acuerdosRow:    { flexDirection: 'row', alignItems: 'center', gap: 6 },
  acuerdosTxt:    { fontSize: 12, color: Colors.green, fontWeight: '600' },
})
