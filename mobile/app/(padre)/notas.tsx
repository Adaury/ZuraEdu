import React, { useState, useEffect } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { dashboardApi, calificacionesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

function semColor(n: number) {
  return n >= 80 ? Colors.green : n >= 70 ? Colors.amber : Colors.red
}

export default function NotasPadre() {
  const [hijoId, setHijoId]   = useState<number | null>(null)
  const [periodo, setPeriodo] = useState<string | null>(null)

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
    queryKey: ['calificaciones-hijo', hijoActual],
    queryFn:  () => calificacionesApi.hijo(hijoActual!).then(r => r.data),
    enabled:  hijoActual != null,
  })

  const tecnicas: Record<string, any[]> = data?.tecnicas ?? {}
  const academicas: any[]               = data?.academicas ?? []
  const periodos                         = Object.keys(tecnicas)
  const periodoActivo                    = periodo ?? periodos[periodos.length - 1] ?? null
  const notasPeriodo                     = periodoActivo ? (tecnicas[periodoActivo] ?? []) : []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Notas</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hijoRow} contentContainerStyle={{ gap: 8 }}>
            {hijos.map((h: any) => (
              <TouchableOpacity
                key={h.id}
                onPress={() => { setHijoId(h.id); setPeriodo(null) }}
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

        {/* Tarjeta de promedio */}
        {data && (
          <View style={styles.promedioCard}>
            <View style={{ flex: 1 }}>
              <Text style={styles.promedioLbl}>Promedio General</Text>
              <Text style={styles.promedioGrupo}>{data.grupo ?? '—'}</Text>
            </View>
            {data.promedio != null && (
              <View style={[styles.promedioBadge, { backgroundColor: semColor(data.promedio) + '20' }]}>
                <Text style={[styles.promedioNum, { color: semColor(data.promedio) }]}>{data.promedio}</Text>
              </View>
            )}
          </View>
        )}

        {/* Tabs de período */}
        {periodos.length > 0 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.pillRow} contentContainerStyle={{ gap: 8 }}>
            {periodos.map(p => (
              <TouchableOpacity
                key={p}
                onPress={() => setPeriodo(p)}
                style={[styles.pill, periodoActivo === p && { backgroundColor: ACCENT, borderColor: ACCENT }]}
              >
                <Text style={[styles.pillTxt, periodoActivo === p && { color: '#fff' }]}>{p}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

        {/* Calificaciones técnicas del período */}
        {notasPeriodo.map((m: any, i: number) => {
          const nota  = parseFloat(m.nota_final)
          const color = isNaN(nota) ? Colors.muted : semColor(nota)
          return (
            <View key={i} style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={styles.materia}>{m.asignatura}</Text>
                {!!m.indicador && <Text style={styles.indicador}>{m.indicador}</Text>}
              </View>
              <View style={[styles.badge, { backgroundColor: color + '20' }]}>
                <Text style={[styles.nota, { color }]}>
                  {isNaN(nota) ? (m.letra ?? '—') : nota}
                </Text>
              </View>
            </View>
          )
        })}

        {/* Áreas académicas (siempre visibles) */}
        {academicas.length > 0 && (
          <>
            <Text style={styles.sectionLbl}>Áreas Académicas</Text>
            {academicas.map((m: any, i: number) => {
              const nota  = parseFloat(m.nota_final)
              const color = isNaN(nota) ? Colors.muted : semColor(nota)
              return (
                <View key={i} style={styles.row}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.materia}>{m.asignatura}</Text>
                    {!!m.situacion && (
                      <Text style={[styles.indicador, { color: m.situacion === 'Aprobado' ? Colors.green : Colors.red }]}>
                        {m.situacion}
                      </Text>
                    )}
                  </View>
                  <View style={[styles.badge, { backgroundColor: color + '20' }]}>
                    <Text style={[styles.nota, { color }]}>{isNaN(nota) ? '—' : nota}</Text>
                  </View>
                </View>
              )
            })}
          </>
        )}

        {!isLoading && notasPeriodo.length === 0 && academicas.length === 0 && hijoActual && (
          <Text style={styles.empty}>No hay calificaciones publicadas.</Text>
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
  promedioCard:      { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 14, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  promedioLbl:       { fontSize: 12, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .4 },
  promedioGrupo:     { fontSize: 14, fontWeight: '700', color: Colors.text, marginTop: 2 },
  promedioBadge:     { borderRadius: 12, padding: 12, minWidth: 60, alignItems: 'center' },
  promedioNum:       { fontSize: 22, fontWeight: '900' },
  pillRow:           { marginBottom: 4 },
  pill:              { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6 },
  pillTxt:           { fontSize: 13, fontWeight: '600', color: Colors.muted },
  sectionLbl:        { fontSize: 13, fontWeight: '800', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5, marginTop: 4 },
  row:               { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  materia:           { fontSize: 14, fontWeight: '700', color: Colors.text },
  indicador:         { fontSize: 11, color: Colors.muted, marginTop: 2 },
  badge:             { borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8, alignItems: 'center', minWidth: 52 },
  nota:              { fontSize: 17, fontWeight: '900' },
  empty:             { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
