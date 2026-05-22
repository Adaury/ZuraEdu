import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl, Alert } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { eventosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPO_COLOR: Record<string, string> = {
  academico: Colors.blue,
  deportivo: Colors.green,
  cultural:  Colors.purple,
  social:    Colors.amber,
}

const TIPO_ICON: Record<string, string> = {
  academico: 'school',
  deportivo: 'football',
  cultural:  'musical-notes',
  social:    'people',
}

const FILTROS = ['Todos', 'Académico', 'Deportivo', 'Cultural', 'Social']

export default function EventosEstudiante() {
  const [filtro, setFiltro] = useState('Todos')
  const qc = useQueryClient()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['eventos-estudiante'],
    queryFn:  () => eventosApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const inscribir = useMutation({
    mutationFn: (id: number) => eventosApi.inscribirse(id),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: ['eventos-estudiante'] }) },
    onError:    (e: any) => Alert.alert('Error', e?.response?.data?.message ?? 'No se pudo inscribir.'),
  })

  const todos: any[]    = data?.data ?? []
  const filtroMap: Record<string, string> = {
    'Académico': 'academico', 'Deportivo': 'deportivo', 'Cultural': 'cultural', 'Social': 'social',
  }
  const eventos = filtro === 'Todos' ? todos : todos.filter(e => e.tipo === filtroMap[filtro])

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.purple} />}
      >
        {/* KPIs */}
        <View style={styles.kpiRow}>
          <View style={[styles.kpi, { backgroundColor: '#eff6ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.blue }]}>{todos.length}</Text>
            <Text style={styles.kpiLbl}>Eventos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#f0fdf4' }]}>
            <Text style={[styles.kpiVal, { color: Colors.green }]}>
              {todos.filter(e => e.inscrito).length}
            </Text>
            <Text style={styles.kpiLbl}>Inscritos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#faf5ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.purple }]}>
              {todos.filter(e => !e.inscrito && !e.lleno).length}
            </Text>
            <Text style={styles.kpiLbl}>Disponibles</Text>
          </View>
        </View>

        {/* Filtros */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filtroWrap}>
          {FILTROS.map(f => (
            <TouchableOpacity
              key={f}
              style={[styles.filtroBtn, filtro === f && styles.filtroBtnActive]}
              onPress={() => setFiltro(f)}
            >
              <Text style={[styles.filtroTxt, filtro === f && styles.filtroTxtActive]}>{f}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {/* Lista */}
        {isLoading && (
          <View style={styles.card}>
            {[0, 1].map(i => <View key={i} style={[styles.skeleton, { marginBottom: 10 }]} />)}
          </View>
        )}

        {eventos.map((e: any) => {
          const color  = TIPO_COLOR[e.tipo] ?? Colors.muted
          const icon   = TIPO_ICON[e.tipo]  ?? 'calendar'
          const lleno  = e.lleno && !e.inscrito
          return (
            <View key={e.id} style={styles.card}>
              <View style={styles.cardHeader}>
                <View style={[styles.tipoIcon, { backgroundColor: color + '18' }]}>
                  <Ionicons name={icon as any} size={18} color={color} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.nombre}>{e.nombre}</Text>
                  <Text style={[styles.tipoLbl, { color }]}>{e.tipo_label}</Text>
                </View>
                {e.inscrito ? (
                  <View style={[styles.inscritoBadge, { backgroundColor: Colors.green + '18' }]}>
                    <Ionicons name="checkmark-circle" size={14} color={Colors.green} />
                    <Text style={[styles.inscritoTxt, { color: Colors.green }]}>Inscrito</Text>
                  </View>
                ) : lleno ? (
                  <View style={[styles.inscritoBadge, { backgroundColor: Colors.red + '18' }]}>
                    <Text style={[styles.inscritoTxt, { color: Colors.red }]}>Lleno</Text>
                  </View>
                ) : null}
              </View>

              {!!e.descripcion && <Text style={styles.desc} numberOfLines={2}>{e.descripcion}</Text>}

              <View style={styles.metaRow}>
                {!!e.lugar && (
                  <View style={styles.metaItem}>
                    <Ionicons name="location" size={12} color={Colors.muted} />
                    <Text style={styles.metaTxt}>{e.lugar}</Text>
                  </View>
                )}
                {!!e.fecha_inicio && (
                  <View style={styles.metaItem}>
                    <Ionicons name="calendar" size={12} color={Colors.muted} />
                    <Text style={styles.metaTxt}>{e.fecha_inicio}</Text>
                  </View>
                )}
                {e.cupo_maximo != null && (
                  <View style={styles.metaItem}>
                    <Ionicons name="people" size={12} color={Colors.muted} />
                    <Text style={styles.metaTxt}>{e.inscritos}/{e.cupo_maximo} cupos</Text>
                  </View>
                )}
              </View>

              {!e.inscrito && !lleno && (
                <TouchableOpacity
                  style={[styles.inscribirBtn, { backgroundColor: color }]}
                  activeOpacity={0.8}
                  onPress={() => inscribir.mutate(e.id)}
                  disabled={inscribir.isPending}
                >
                  <Ionicons name="add-circle" size={16} color="#fff" />
                  <Text style={styles.inscribirTxt}>Inscribirse</Text>
                </TouchableOpacity>
              )}
            </View>
          )
        })}

        {!isLoading && eventos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="calendar-outline" size={40} color={Colors.border} />
            <Text style={styles.emptyTxt}>No hay eventos disponibles.</Text>
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
  filtroWrap:    { flexGrow: 0 },
  filtroBtn:     { paddingHorizontal: 14, paddingVertical: 7, borderRadius: 99,
                   backgroundColor: '#fff', marginRight: 8, borderWidth: 1, borderColor: Colors.border },
  filtroBtnActive:{ backgroundColor: Colors.purple, borderColor: Colors.purple },
  filtroTxt:     { fontSize: 12, fontWeight: '700', color: Colors.muted },
  filtroTxtActive:{ color: '#fff' },
  card:          { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                   shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader:    { flexDirection: 'row', alignItems: 'center', gap: 10 },
  tipoIcon:      { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  nombre:        { fontSize: 14, fontWeight: '800', color: Colors.text },
  tipoLbl:       { fontSize: 11, fontWeight: '600', marginTop: 2 },
  inscritoBadge: { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  inscritoTxt:   { fontSize: 10, fontWeight: '700' },
  desc:          { fontSize: 12, color: Colors.muted, lineHeight: 18 },
  metaRow:       { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  metaItem:      { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:       { fontSize: 11, color: Colors.muted },
  inscribirBtn:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                   gap: 6, borderRadius: 10, padding: 10 },
  inscribirTxt:  { color: '#fff', fontWeight: '800', fontSize: 13 },
  skeleton:      { height: 80, borderRadius: 12, backgroundColor: Colors.border },
  empty:         { alignItems: 'center', gap: 10, paddingVertical: 40 },
  emptyTxt:      { color: Colors.muted, fontSize: 13 },
})
