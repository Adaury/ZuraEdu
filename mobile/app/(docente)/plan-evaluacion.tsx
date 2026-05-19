import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, RefreshControl, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

const ICON_MAP: Record<string, any> = {
  lista_cotejo:      'checkbox-outline',
  rubrica:           'grid-outline',
  escala_estimacion: 'stats-chart-outline',
}

export default function PlanEvaluacionDocente() {
  const [asignacionSel, setAsignacion] = useState<any | null>(null)
  const [periodoId, setPeriodoId]      = useState<number | null>(null)

  const { data: gruposData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const { data, isLoading: detLoading } = useQuery({
    queryKey: ['docente-plan-eval', asignacionSel?.asignacion_id],
    queryFn:  () => docenteApi.planEvaluacion(asignacionSel!.asignacion_id).then(r => r.data),
    enabled:  !!asignacionSel,
  })

  const asignaciones: any[] = gruposData?.asignaciones ?? []

  // ── Vista detalle ──────────────────────────────────────────────────────
  if (asignacionSel) {
    const periodos: any[]    = data?.periodos    ?? []
    const categorias: any[]  = data?.categorias  ?? []
    const planes: Record<string, any>       = data?.planes       ?? {}
    const instrumentos: Record<string, any[]> = data?.instrumentos ?? {}

    const activePeriodo = periodoId ?? periodos[0]?.id ?? null
    const plan          = activePeriodo ? (planes[String(activePeriodo)] ?? null) : null
    const instrs: any[] = activePeriodo ? (instrumentos[String(activePeriodo)] ?? []) : []

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: asignacionSel.color ?? ACCENT }]}>
          <TouchableOpacity onPress={() => { setAsignacion(null); setPeriodoId(null) }} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{asignacionSel.asignatura}</Text>
            <Text style={styles.detSub}>{asignacionSel.grupo}</Text>
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {/* Tabs de períodos */}
          {periodos.length > 0 && (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabs}>
              {periodos.map((p: any) => {
                const active   = activePeriodo === p.id
                const tienePlan = !!planes[String(p.id)]
                return (
                  <TouchableOpacity
                    key={p.id}
                    style={[styles.tab, active && { backgroundColor: asignacionSel.color ?? ACCENT }]}
                    onPress={() => setPeriodoId(p.id)}
                  >
                    <Text style={[styles.tabTxt, active && styles.tabTxtActive]}>{p.nombre}</Text>
                    {tienePlan && <View style={[styles.tabDot, { backgroundColor: active ? '#fff' : (asignacionSel.color ?? ACCENT) }]} />}
                  </TouchableOpacity>
                )
              })}
            </ScrollView>
          )}

          {detLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

          {/* Plan del período */}
          {!detLoading && plan ? (
            <View style={styles.planCard}>
              <View style={styles.planCardHead}>
                <Text style={styles.planCardTitle}>Plan del período</Text>
                <View style={[
                  styles.pubBadge,
                  { backgroundColor: plan.publicado ? Colors.green + '20' : Colors.amber + '20' },
                ]}>
                  <Ionicons
                    name={plan.publicado ? 'checkmark-circle' : 'time-outline'}
                    size={13}
                    color={plan.publicado ? Colors.green : Colors.amber}
                  />
                  <Text style={[styles.pubTxt, { color: plan.publicado ? Colors.green : Colors.amber }]}>
                    {plan.publicado ? 'Publicado' : 'Borrador'}
                  </Text>
                </View>
              </View>

              {/* Categorías */}
              <View style={styles.catGrid}>
                {categorias.map((cat: any) => {
                  const count: number = plan[cat.clave] ?? 0
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

              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Total de actividades</Text>
                <Text style={[styles.totalVal, { color: asignacionSel.color ?? ACCENT }]}>{plan.total}</Text>
              </View>

              {plan.observaciones ? (
                <Text style={styles.obs}>📝 {plan.observaciones}</Text>
              ) : null}
            </View>
          ) : !detLoading && activePeriodo ? (
            <View style={styles.empty}>
              <Ionicons name="document-outline" size={48} color={Colors.border} />
              <Text style={styles.emptyTxt}>No hay plan definido para este período</Text>
            </View>
          ) : null}

          {/* Instrumentos del período */}
          {!detLoading && instrs.length > 0 && (
            <View style={styles.instrSection}>
              <Text style={styles.instrSectionTitle}>Instrumentos de evaluación</Text>
              {instrs.map((inst: any, i: number) => (
                <View key={i} style={styles.instrCard}>
                  <View style={styles.instrHead}>
                    <Ionicons
                      name={ICON_MAP[inst.tipo] ?? 'document-outline'}
                      size={16}
                      color={asignacionSel.color ?? ACCENT}
                    />
                    <Text style={styles.instrTitulo} numberOfLines={2}>{inst.titulo}</Text>
                    <View style={[
                      styles.instrPubBadge,
                      { backgroundColor: inst.publicado ? Colors.green + '20' : Colors.amber + '20' },
                    ]}>
                      <Text style={{ fontSize: 10, fontWeight: '700', color: inst.publicado ? Colors.green : Colors.amber }}>
                        {inst.publicado ? 'Pub.' : 'Bor.'}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.instrMeta}>
                    <Text style={styles.instrMetaTxt}>{inst.tipo_label}</Text>
                    {inst.fecha ? <Text style={styles.instrMetaTxt}>· {inst.fecha}</Text> : null}
                    {inst.criterios > 0 ? (
                      <Text style={styles.instrMetaTxt}>· {inst.criterios} criterios</Text>
                    ) : null}
                  </View>
                </View>
              ))}
            </View>
          )}
        </ScrollView>
      </SafeAreaView>
    )
  }

  // ── Vista lista ────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.pageTitle}>Plan de Evaluación</Text>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

        {!isLoading && asignaciones.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="people-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyTxt}>No tienes grupos asignados.</Text>
          </View>
        )}

        {asignaciones.map((a: any) => (
          <TouchableOpacity
            key={a.asignacion_id}
            style={styles.grupoCard}
            onPress={() => setAsignacion(a)}
            activeOpacity={0.85}
          >
            <View style={[styles.grupoAccent, { backgroundColor: a.color ?? ACCENT }]} />
            <View style={styles.grupoBody}>
              <Text style={styles.grupoAsig}>{a.asignatura}</Text>
              <Text style={styles.grupoNombre}>{a.grupo}</Text>
              <Text style={styles.grupoAlumnos}>{a.total_estudiantes ?? 0} estudiantes</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={Colors.muted} />
          </TouchableOpacity>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 40, gap: 12 },
  pageTitle:    { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },

  grupoCard:    { backgroundColor: '#fff', borderRadius: 14, flexDirection: 'row', alignItems: 'center',
                  overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  grupoAccent:  { width: 8, alignSelf: 'stretch' },
  grupoBody:    { flex: 1, padding: 14, gap: 3 },
  grupoAsig:    { fontSize: 15, fontWeight: '800', color: Colors.text },
  grupoNombre:  { fontSize: 12, fontWeight: '600', color: ACCENT },
  grupoAlumnos: { fontSize: 11, color: Colors.muted },

  detHeader:    { flexDirection: 'row', alignItems: 'center', gap: 12,
                  paddingHorizontal: 16, paddingTop: 12, paddingBottom: 14 },
  backBtn:      { padding: 4 },
  detTitle:     { fontSize: 16, fontWeight: '900', color: '#fff' },
  detSub:       { fontSize: 11, color: 'rgba(255,255,255,.8)', marginTop: 2 },

  tabs:         { flexGrow: 0, marginBottom: 4 },
  tab:          { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: Colors.border,
                  marginRight: 8, flexDirection: 'row', alignItems: 'center', gap: 5 },
  tabTxt:       { fontSize: 13, fontWeight: '700', color: Colors.muted },
  tabTxtActive: { color: '#fff' },
  tabDot:       { width: 6, height: 6, borderRadius: 99 },

  planCard:     { backgroundColor: '#fff', borderRadius: 16, padding: 16, gap: 12,
                  shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  planCardHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  planCardTitle:{ fontSize: 15, fontWeight: '800', color: Colors.text },
  pubBadge:     { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  pubTxt:       { fontSize: 11, fontWeight: '700' },

  catGrid:      { gap: 8 },
  catRow:       { flexDirection: 'row', alignItems: 'center', gap: 8 },
  catDot:       { width: 8, height: 8, borderRadius: 99 },
  catLabel:     { fontSize: 13, color: Colors.text, flex: 1 },
  catCount:     { borderRadius: 6, paddingHorizontal: 10, paddingVertical: 3 },
  catCountTxt:  { fontSize: 14, fontWeight: '800' },

  totalRow:     { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
                  borderTopWidth: 1, borderTopColor: Colors.border, paddingTop: 10 },
  totalLabel:   { fontSize: 13, fontWeight: '600', color: Colors.muted },
  totalVal:     { fontSize: 24, fontWeight: '900' },

  obs:          { fontSize: 12, color: Colors.muted, fontStyle: 'italic',
                  backgroundColor: '#f8fafc', borderRadius: 8, padding: 8 },

  instrSection: { gap: 8 },
  instrSectionTitle: { fontSize: 14, fontWeight: '800', color: Colors.text },
  instrCard:    { backgroundColor: '#fff', borderRadius: 14, padding: 12, gap: 6,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 4, elevation: 1 },
  instrHead:    { flexDirection: 'row', alignItems: 'flex-start', gap: 8 },
  instrTitulo:  { fontSize: 13, fontWeight: '700', color: Colors.text, flex: 1 },
  instrPubBadge:{ borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },
  instrMeta:    { flexDirection: 'row', gap: 4, flexWrap: 'wrap' },
  instrMetaTxt: { fontSize: 11, color: Colors.muted },

  empty:        { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:     { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
