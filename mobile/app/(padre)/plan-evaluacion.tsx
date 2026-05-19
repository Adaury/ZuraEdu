import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, RefreshControl, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { planEvaluacionApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ICON_MAP: Record<string, any> = {
  lista_cotejo:      'checkbox-outline',
  rubrica:           'grid-outline',
  escala_estimacion: 'stats-chart-outline',
}

export default function PlanEvaluacionPadre() {
  const [hijoActual, setHijoActual] = useState<any>(null)
  const [periodoId, setPeriodoId]   = useState<number | null>(null)

  const { data: classData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn: () => classroomApi.index().then(r => r.data),
  })
  const hijos: any[] = classData?.hijos ?? []
  const hijo = hijoActual ?? hijos[0] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['plan-eval-hijo', hijo?.estudiante_id],
    queryFn: () => planEvaluacionApi.hijo(hijo.estudiante_id).then(r => r.data),
    enabled: !!hijo?.estudiante_id,
  })

  const periodos: any[]   = data?.periodos   ?? []
  const categorias: any[] = data?.categorias ?? []
  const planes: Record<string, any[]> = data?.planes ?? {}

  const activePeriodo = periodoId ?? periodos[0]?.id ?? null
  const asignaturas: any[] = activePeriodo ? (planes[String(activePeriodo)] ?? []) : []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        <Text style={styles.pageTitle}>Plan de Evaluación</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hijoTabs}>
            {hijos.map((h: any) => {
              const active = (hijoActual ?? hijos[0])?.estudiante_id === h.estudiante_id
              return (
                <TouchableOpacity
                  key={h.estudiante_id}
                  style={[styles.hijoTab, active && styles.hijoTabActive]}
                  onPress={() => { setHijoActual(h); setPeriodoId(null) }}
                >
                  <Text style={[styles.hijoTabTxt, active && styles.hijoTabTxtActive]}>{h.nombre}</Text>
                </TouchableOpacity>
              )
            })}
          </ScrollView>
        )}

        {/* Tabs de períodos */}
        {periodos.length > 0 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabs}>
            {periodos.map((p: any) => {
              const active = activePeriodo === p.id
              return (
                <TouchableOpacity
                  key={p.id}
                  style={[styles.tab, active && styles.tabActive]}
                  onPress={() => setPeriodoId(p.id)}
                >
                  <Text style={[styles.tabTxt, active && styles.tabTxtActive]}>{p.nombre}</Text>
                </TouchableOpacity>
              )
            })}
          </ScrollView>
        )}

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {!isLoading && hijo && asignaturas.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="document-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>Sin plan de evaluación publicado este período</Text>
          </View>
        )}

        {asignaturas.map((asig: any, i: number) => (
          <View key={i} style={styles.card}>
            <View style={[styles.cardAccent, { backgroundColor: asig.asignatura_color }]} />
            <View style={styles.cardBody}>
              <View style={styles.cardHead}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.asignatura}>{asig.asignatura}</Text>
                  <Text style={styles.docente}>{asig.docente}</Text>
                </View>
                <View style={[styles.totalBadge, { backgroundColor: asig.asignatura_color + '20' }]}>
                  <Text style={[styles.totalVal, { color: asig.asignatura_color }]}>{asig.total}</Text>
                  <Text style={styles.totalLbl}>actividades</Text>
                </View>
              </View>

              {/* Categorías */}
              <View style={styles.catGrid}>
                {categorias.map((cat: any) => {
                  const count: number = asig[cat.clave] ?? 0
                  if (count === 0) return null
                  return (
                    <View key={cat.clave} style={styles.catRow}>
                      <View style={[styles.catDot, { backgroundColor: cat.color }]} />
                      <Text style={styles.catLabel}>{cat.label}</Text>
                      <View style={[styles.catCount, { backgroundColor: cat.color + '20' }]}>
                        <Text style={[styles.catCountTxt, { color: cat.color }]}>{count}</Text>
                      </View>
                    </View>
                  )
                })}
              </View>

              {asig.observaciones ? (
                <Text style={styles.obs}>📝 {asig.observaciones}</Text>
              ) : null}

              {/* Instrumentos */}
              {asig.instrumentos?.length > 0 && (
                <View style={styles.instrSection}>
                  <Text style={styles.instrTitle}>Instrumentos de evaluación</Text>
                  {asig.instrumentos.map((inst: any, j: number) => (
                    <View key={j} style={styles.instrRow}>
                      <Ionicons name={ICON_MAP[inst.tipo] ?? 'document-outline'} size={14} color={asig.asignatura_color} />
                      <View style={{ flex: 1 }}>
                        <Text style={styles.instrNombre}>{inst.titulo}</Text>
                        <Text style={styles.instrMeta}>{inst.tipo_label}{inst.fecha ? ` · ${inst.fecha}` : ''}</Text>
                      </View>
                    </View>
                  ))}
                </View>
              )}
            </View>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, gap: 12, paddingBottom: 32 },
  pageTitle:       { fontSize: 22, fontWeight: '900', color: Colors.text },
  hijoTabs:        { flexGrow: 0 },
  hijoTab:         { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: Colors.border, marginRight: 8 },
  hijoTabActive:   { backgroundColor: Colors.roles.padre },
  hijoTabTxt:      { fontSize: 13, fontWeight: '700', color: Colors.muted },
  hijoTabTxtActive:{ color: '#fff' },
  tabs:            { flexGrow: 0 },
  tab:             { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: Colors.border, marginRight: 8 },
  tabActive:       { backgroundColor: Colors.blue },
  tabTxt:          { fontSize: 13, fontWeight: '700', color: Colors.muted },
  tabTxtActive:    { color: '#fff' },
  empty:           { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:        { fontSize: 14, color: Colors.muted, textAlign: 'center' },
  card:            { backgroundColor: '#fff', borderRadius: 16, flexDirection: 'row', overflow: 'hidden', shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  cardAccent:      { width: 5 },
  cardBody:        { flex: 1, padding: 14, gap: 10 },
  cardHead:        { flexDirection: 'row', alignItems: 'flex-start', gap: 8 },
  asignatura:      { fontSize: 15, fontWeight: '800', color: Colors.text },
  docente:         { fontSize: 12, color: Colors.muted, marginTop: 2 },
  totalBadge:      { borderRadius: 10, paddingHorizontal: 10, paddingVertical: 6, alignItems: 'center' },
  totalVal:        { fontSize: 20, fontWeight: '900', lineHeight: 22 },
  totalLbl:        { fontSize: 9, color: Colors.muted, fontWeight: '600' },
  catGrid:         { gap: 6 },
  catRow:          { flexDirection: 'row', alignItems: 'center', gap: 8 },
  catDot:          { width: 8, height: 8, borderRadius: 99 },
  catLabel:        { fontSize: 13, color: Colors.text, flex: 1 },
  catCount:        { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 },
  catCountTxt:     { fontSize: 13, fontWeight: '800' },
  obs:             { fontSize: 12, color: Colors.muted, fontStyle: 'italic', backgroundColor: '#f8fafc', borderRadius: 8, padding: 8 },
  instrSection:    { gap: 6, borderTopWidth: 1, borderTopColor: Colors.border, paddingTop: 8 },
  instrTitle:      { fontSize: 12, fontWeight: '700', color: Colors.muted },
  instrRow:        { flexDirection: 'row', alignItems: 'flex-start', gap: 8 },
  instrNombre:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  instrMeta:       { fontSize: 11, color: Colors.muted },
})
