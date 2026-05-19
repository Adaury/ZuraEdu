import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, RefreshControl, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { conductaApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const INDICADORES = [
  { key: 'puntualidad',     label: 'Puntualidad' },
  { key: 'participacion',   label: 'Participación' },
  { key: 'respeto',         label: 'Respeto' },
  { key: 'trabajo_equipo',  label: 'Trabajo en equipo' },
  { key: 'responsabilidad', label: 'Responsabilidad' },
  { key: 'orden',           label: 'Orden' },
]

export default function ConductaPadre() {
  const [hijoActual, setHijoActual] = useState<any>(null)
  const [periodoId, setPeriodoId]   = useState<number | null>(null)

  const { data: classData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn: () => classroomApi.index().then(r => r.data),
  })
  const hijos: any[] = classData?.hijos ?? []
  const hijo = hijoActual ?? hijos[0] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['conducta-hijo', hijo?.estudiante_id],
    queryFn: () => conductaApi.hijo(hijo.estudiante_id).then(r => r.data),
    enabled: !!hijo?.estudiante_id,
  })

  const periodos: any[] = data?.periodos ?? []
  const escala: any[]   = data?.escala   ?? []
  const registros: Record<string, any[]> = data?.registros ?? {}

  const activePeriodo = periodoId ?? periodos[0]?.id ?? null
  const regs: any[]   = activePeriodo ? (registros[String(activePeriodo)] ?? []) : []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.purple} />}
      >
        <Text style={styles.pageTitle}>Conducta</Text>

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

        {/* Escala */}
        {escala.length > 0 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={styles.escalaRow}>
              {[...escala].reverse().map((e: any) => (
                <View key={e.valor} style={[styles.escalaPill, { borderColor: e.color }]}>
                  <Text style={[styles.escalaLbl, { color: e.color }]}>{e.label}</Text>
                  <Text style={styles.escalaNom}>{e.nombre}</Text>
                </View>
              ))}
            </View>
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

        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 40 }} />}

        {!isLoading && hijo && regs.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="shield-half-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>Sin registros de conducta este período</Text>
          </View>
        )}

        {regs.map((r: any, i: number) => (
          <View key={i} style={styles.card}>
            <View style={[styles.cardAccent, { backgroundColor: r.asignatura_color }]} />
            <View style={styles.cardBody}>
              <View style={styles.cardHead}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.asignatura}>{r.asignatura}</Text>
                  <Text style={styles.docente}>{r.docente}</Text>
                </View>
                {r.concepto ? (
                  <View style={[styles.conceptoBadge, { backgroundColor: r.concepto_color + '20' }]}>
                    <Text style={[styles.conceptoLbl, { color: r.concepto_color }]}>{r.concepto_label}</Text>
                  </View>
                ) : null}
              </View>

              <View style={styles.indGrid}>
                {INDICADORES.map(ind => {
                  const val = r[ind.key] as number | null
                  return (
                    <View key={ind.key} style={styles.indRow}>
                      <Text style={styles.indLabel}>{ind.label}</Text>
                      <View style={styles.dots}>
                        {[1, 2, 3, 4, 5].map(n => (
                          <View
                            key={n}
                            style={[
                              styles.dot,
                              { backgroundColor: val != null && val >= n ? r.asignatura_color : Colors.border },
                            ]}
                          />
                        ))}
                      </View>
                    </View>
                  )
                })}
              </View>

              {r.observaciones ? (
                <Text style={styles.obs}>💬 {r.observaciones}</Text>
              ) : null}
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
  escalaRow:       { flexDirection: 'row', gap: 8, paddingVertical: 4 },
  escalaPill:      { borderWidth: 1.5, borderRadius: 10, paddingHorizontal: 10, paddingVertical: 4, alignItems: 'center', minWidth: 52 },
  escalaLbl:       { fontSize: 15, fontWeight: '900' },
  escalaNom:       { fontSize: 9, color: Colors.muted, fontWeight: '600' },
  tabs:            { flexGrow: 0 },
  tab:             { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: Colors.border, marginRight: 8 },
  tabActive:       { backgroundColor: Colors.purple },
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
  conceptoBadge:   { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 4 },
  conceptoLbl:     { fontSize: 14, fontWeight: '900' },
  indGrid:         { gap: 6 },
  indRow:          { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  indLabel:        { fontSize: 12, color: Colors.muted, flex: 1 },
  dots:            { flexDirection: 'row', gap: 4 },
  dot:             { width: 10, height: 10, borderRadius: 99 },
  obs:             { fontSize: 12, color: Colors.muted, fontStyle: 'italic', backgroundColor: '#f8fafc', borderRadius: 8, padding: 8 },
})
