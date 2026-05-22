import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useLocalSearchParams } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
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

export default function EventosPadre() {
  const { id } = useLocalSearchParams<{ id: string }>()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['eventos-padre'],
    queryFn:  () => eventosApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const eventos: any[] = data?.data ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.purple} />}
      >
        <View style={styles.kpiRow}>
          <View style={[styles.kpi, { backgroundColor: '#eff6ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.blue }]}>{eventos.length}</Text>
            <Text style={styles.kpiLbl}>Eventos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#f0fdf4' }]}>
            <Text style={[styles.kpiVal, { color: Colors.green }]}>
              {eventos.filter(e => e.inscrito).length}
            </Text>
            <Text style={styles.kpiLbl}>Inscritos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#faf5ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.purple }]}>
              {eventos.filter(e => !e.inscrito && !e.lleno).length}
            </Text>
            <Text style={styles.kpiLbl}>Disponibles</Text>
          </View>
        </View>

        {isLoading && [0, 1].map(i => <View key={i} style={styles.skeleton} />)}

        {eventos.map((e: any) => {
          const color = TIPO_COLOR[e.tipo] ?? Colors.muted
          const icon  = TIPO_ICON[e.tipo]  ?? 'calendar'
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
                {e.inscrito && (
                  <View style={[styles.badge, { backgroundColor: Colors.green + '18' }]}>
                    <Ionicons name="checkmark-circle" size={13} color={Colors.green} />
                    <Text style={[styles.badgeTxt, { color: Colors.green }]}>Inscrito</Text>
                  </View>
                )}
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
  safe:       { flex: 1, backgroundColor: Colors.bg },
  content:    { padding: 16, gap: 12, paddingBottom: 32 },
  kpiRow:     { flexDirection: 'row', gap: 10 },
  kpi:        { flex: 1, borderRadius: 14, padding: 12, alignItems: 'center' },
  kpiVal:     { fontSize: 22, fontWeight: '900' },
  kpiLbl:     { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },
  card:       { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 10 },
  tipoIcon:   { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  nombre:     { fontSize: 14, fontWeight: '800', color: Colors.text },
  tipoLbl:    { fontSize: 11, fontWeight: '600', marginTop: 2 },
  badge:      { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  badgeTxt:   { fontSize: 10, fontWeight: '700' },
  desc:       { fontSize: 12, color: Colors.muted, lineHeight: 18 },
  metaRow:    { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  metaItem:   { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:    { fontSize: 11, color: Colors.muted },
  skeleton:   { height: 80, borderRadius: 16, backgroundColor: Colors.border },
  empty:      { alignItems: 'center', gap: 10, paddingVertical: 40 },
  emptyTxt:   { color: Colors.muted, fontSize: 13 },
})
