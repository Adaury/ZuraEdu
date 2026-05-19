import React, { useState, useMemo } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { classroomApi, observacionesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPOS = {
  academica:  { label: 'Académica',  color: '#3b82f6', icon: 'book-outline'          },
  conductual: { label: 'Conductual', color: '#ef4444', icon: 'person-remove-outline'  },
  positiva:   { label: 'Positiva',   color: '#10b981', icon: 'star-outline'           },
  general:    { label: 'General',    color: '#6b7280', icon: 'chatbubble-outline'     },
} as const

type TipoKey = keyof typeof TIPOS | null

export default function ObservacionesPadre() {
  const [hijoIdx, setHijoIdx] = useState(0)
  const [filtro,  setFiltro]  = useState<TipoKey>(null)
  const color = Colors.roles.padre

  // Hijos desde classroomApi (patrón usado en cafetería y transporte)
  const { data: classData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const hijos: any[]  = classData?.hijos ?? []
  const hijoActual    = hijos[hijoIdx] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['obs-hijo', hijoActual?.estudiante_id],
    queryFn:  () => observacionesApi.hijo(hijoActual!.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
  })

  const observaciones: any[] = data?.observaciones ?? []
  const resumen: any          = data?.resumen ?? {}

  const filtradas = useMemo(
    () => filtro ? observaciones.filter(o => o.tipo === filtro) : observaciones,
    [observaciones, filtro],
  )

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Selector de hijo */}
      {hijos.length > 1 && (
        <ScrollView
          horizontal showsHorizontalScrollIndicator={false}
          style={styles.hijoBar} contentContainerStyle={styles.hijoContent}
        >
          {hijos.map((h: any, i: number) => (
            <TouchableOpacity
              key={h.estudiante_id}
              style={[styles.hijoChip, hijoIdx === i && { borderColor: color, backgroundColor: color + '12' }]}
              onPress={() => { setHijoIdx(i); setFiltro(null) }}
            >
              <Text style={[styles.hijoChipTxt, hijoIdx === i && { color }]}>
                {(h.nombre ?? '').split(' ')[0]}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      )}

      {/* Filtros */}
      <ScrollView
        horizontal showsHorizontalScrollIndicator={false}
        style={styles.filterBar} contentContainerStyle={styles.filterContent}
      >
        <TouchableOpacity
          style={[styles.chip, !filtro && { borderColor: color, backgroundColor: color + '12' }]}
          onPress={() => setFiltro(null)}
        >
          <Text style={[styles.chipTxt, !filtro && { color }]}>
            Todas ({resumen.total ?? observaciones.length})
          </Text>
        </TouchableOpacity>
        {(Object.entries(TIPOS) as [TipoKey, typeof TIPOS[keyof typeof TIPOS]][]).map(([key, meta]) => {
          const cnt = resumen[key as string] ?? 0
          if (cnt === 0 && !filtro) return null
          return (
            <TouchableOpacity
              key={key}
              style={[styles.chip, filtro === key && { borderColor: meta.color, backgroundColor: meta.color + '12' }]}
              onPress={() => setFiltro(f => f === key ? null : key)}
            >
              <Ionicons name={meta.icon as any} size={13} color={filtro === key ? meta.color : Colors.muted} />
              <Text style={[styles.chipTxt, filtro === key && { color: meta.color }]}>
                {meta.label}{cnt > 0 ? ` (${cnt})` : ''}
              </Text>
            </TouchableOpacity>
          )
        })}
      </ScrollView>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={color} />}
      >
        <Text style={styles.title}>
          {hijoActual ? `Observaciones de ${(hijoActual.nombre ?? '').split(' ')[0]}` : 'Observaciones'}
        </Text>

        {isLoading && <ActivityIndicator color={color} style={{ marginTop: 40 }} />}

        {!isLoading && !hijoActual && (
          <View style={styles.empty}>
            <Ionicons name="person-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>No hay hijos registrados</Text>
          </View>
        )}

        {!isLoading && hijoActual && filtradas.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="chatbubble-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>Sin observaciones registradas</Text>
          </View>
        )}

        {filtradas.map((obs: any) => {
          const meta = TIPOS[obs.tipo as keyof typeof TIPOS] ?? TIPOS.general
          return (
            <View key={obs.id} style={styles.card}>
              <View style={[styles.accent, { backgroundColor: meta.color }]} />
              <View style={styles.body}>
                <View style={styles.cardTop}>
                  <View style={[styles.badge, { backgroundColor: meta.color + '20' }]}>
                    <Ionicons name={meta.icon as any} size={12} color={meta.color} />
                    <Text style={[styles.badgeTxt, { color: meta.color }]}>{meta.label}</Text>
                  </View>
                  <Text style={styles.fecha}>{obs.fecha_hace}</Text>
                </View>
                {obs.asignatura ? <Text style={styles.asignatura}>{obs.asignatura}</Text> : null}
                <Text style={styles.texto}>{obs.texto}</Text>
                <Text style={styles.docente}>Prof. {obs.docente}</Text>
              </View>
            </View>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  hijoBar:        { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 54 },
  hijoContent:    { paddingHorizontal: 12, paddingVertical: 10, gap: 8, flexDirection: 'row', alignItems: 'center' },
  hijoChip:       { paddingHorizontal: 14, paddingVertical: 6, borderRadius: 16, borderWidth: 1.5, borderColor: Colors.border },
  hijoChipTxt:    { fontSize: 12, fontWeight: '600', color: Colors.muted },
  filterBar:      { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 50 },
  filterContent:  { paddingHorizontal: 12, paddingVertical: 8, gap: 6, flexDirection: 'row', alignItems: 'center' },
  chip:           { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 12, paddingVertical: 5, borderRadius: 14, borderWidth: 1.5, borderColor: Colors.border },
  chipTxt:        { fontSize: 12, fontWeight: '600', color: Colors.muted },
  content:        { padding: 14, gap: 10, paddingBottom: 32 },
  title:          { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 2 },
  empty:          { alignItems: 'center', paddingVertical: 48, gap: 10 },
  emptyTxt:       { fontSize: 14, color: Colors.muted, fontWeight: '600' },
  card:           { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  accent:         { width: 5 },
  body:           { flex: 1, padding: 12, gap: 4 },
  cardTop:        { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  badge:          { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  badgeTxt:       { fontSize: 11, fontWeight: '700' },
  fecha:          { fontSize: 11, color: Colors.muted },
  asignatura:     { fontSize: 12, fontWeight: '700', color: Colors.primary },
  texto:          { fontSize: 13, color: Colors.text, lineHeight: 18 },
  docente:        { fontSize: 11, color: Colors.muted, marginTop: 2 },
})
