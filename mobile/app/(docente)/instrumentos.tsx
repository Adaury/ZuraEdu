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

const TIPO_ICON: Record<string, any> = {
  lista_cotejo:      'checkbox-outline',
  rubrica:           'grid-outline',
  escala_estimacion: 'stats-chart-outline',
}

const TIPO_COLOR: Record<string, string> = {
  lista_cotejo:      '#3b82f6',
  rubrica:           '#8b5cf6',
  escala_estimacion: '#10b981',
}

export default function InstrumentosDocente() {
  const [asignacionSel, setAsignacion] = useState<any | null>(null)
  const [periodoFiltro, setPeriodoFiltro] = useState<number | null>(null)
  const [expandedId, setExpandedId]    = useState<number | null>(null)

  const { data: gruposData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const { data, isLoading: detLoading } = useQuery({
    queryKey: ['docente-instrumentos', asignacionSel?.asignacion_id],
    queryFn:  () => docenteApi.instrumentos(asignacionSel!.asignacion_id).then(r => r.data),
    enabled:  !!asignacionSel,
  })

  const asignaciones: any[] = gruposData?.asignaciones ?? []

  // ── Vista detalle ──────────────────────────────────────────────────────
  if (asignacionSel) {
    const periodos: any[]    = data?.periodos     ?? []
    const todosInstrs: any[] = data?.instrumentos ?? []

    const filtrados = periodoFiltro
      ? todosInstrs.filter((i: any) => i.periodo_id === periodoFiltro)
      : todosInstrs

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: asignacionSel.color ?? ACCENT }]}>
          <TouchableOpacity onPress={() => { setAsignacion(null); setPeriodoFiltro(null); setExpandedId(null) }} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{asignacionSel.asignatura}</Text>
            <Text style={styles.detSub}>{asignacionSel.grupo}</Text>
          </View>
          <View style={[styles.countBadge, { backgroundColor: 'rgba(255,255,255,.25)' }]}>
            <Text style={styles.countBadgeTxt}>{filtrados.length}</Text>
          </View>
        </View>

        {/* Filtro por período */}
        {periodos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false}
            style={styles.filtroBar} contentContainerStyle={{ paddingHorizontal: 16, gap: 8, paddingVertical: 10 }}
          >
            <TouchableOpacity
              style={[styles.filtroTab, !periodoFiltro && { backgroundColor: asignacionSel.color ?? ACCENT }]}
              onPress={() => setPeriodoFiltro(null)}
            >
              <Text style={[styles.filtroTxt, !periodoFiltro && styles.filtroTxtActive]}>Todos</Text>
            </TouchableOpacity>
            {periodos.map((p: any) => {
              const active = periodoFiltro === p.id
              return (
                <TouchableOpacity
                  key={p.id}
                  style={[styles.filtroTab, active && { backgroundColor: asignacionSel.color ?? ACCENT }]}
                  onPress={() => setPeriodoFiltro(p.id)}
                >
                  <Text style={[styles.filtroTxt, active && styles.filtroTxtActive]}>{p.nombre}</Text>
                </TouchableOpacity>
              )
            })}
          </ScrollView>
        )}

        <ScrollView contentContainerStyle={styles.content}>
          {detLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

          {!detLoading && filtrados.length === 0 && (
            <View style={styles.empty}>
              <Ionicons name="document-outline" size={48} color={Colors.border} />
              <Text style={styles.emptyTxt}>No hay instrumentos{periodoFiltro ? ' en este período' : ''}</Text>
            </View>
          )}

          {filtrados.map((inst: any) => {
            const expanded    = expandedId === inst.id
            const tipoColor   = TIPO_COLOR[inst.tipo]  ?? (asignacionSel.color ?? ACCENT)
            const tipoIcon    = TIPO_ICON[inst.tipo]   ?? 'document-outline'

            return (
              <View key={inst.id} style={styles.instrCard}>
                {/* Cabecera del instrumento */}
                <TouchableOpacity
                  style={styles.instrHead}
                  onPress={() => setExpandedId(expanded ? null : inst.id)}
                  activeOpacity={0.8}
                >
                  <View style={[styles.instrIconWrap, { backgroundColor: tipoColor + '18' }]}>
                    <Ionicons name={tipoIcon} size={20} color={tipoColor} />
                  </View>

                  <View style={{ flex: 1, gap: 3 }}>
                    <View style={styles.instrTitleRow}>
                      <Text style={styles.instrTitulo} numberOfLines={expanded ? 0 : 2}>{inst.titulo}</Text>
                      <View style={[
                        styles.pubBadge,
                        { backgroundColor: inst.publicado ? Colors.green + '20' : Colors.amber + '20' },
                      ]}>
                        <Text style={{ fontSize: 10, fontWeight: '700', color: inst.publicado ? Colors.green : Colors.amber }}>
                          {inst.publicado ? 'Pub' : 'Bor'}
                        </Text>
                      </View>
                    </View>

                    <View style={styles.instrMeta}>
                      <View style={[styles.tipoBadge, { backgroundColor: tipoColor + '18' }]}>
                        <Text style={[styles.tipoTxt, { color: tipoColor }]}>{inst.tipo_label}</Text>
                      </View>
                      <Text style={styles.metaTxt}>{inst.periodo_nombre}</Text>
                      {inst.fecha ? <Text style={styles.metaTxt}>· {inst.fecha}</Text> : null}
                    </View>
                  </View>

                  <Ionicons
                    name={expanded ? 'chevron-up' : 'chevron-down'}
                    size={16}
                    color={Colors.muted}
                  />
                </TouchableOpacity>

                {/* Cuerpo expandible */}
                {expanded && (
                  <View style={styles.instrBody}>
                    {inst.competencia ? (
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Competencia</Text>
                        <Text style={styles.infoVal}>{inst.competencia}</Text>
                      </View>
                    ) : null}

                    {inst.descripcion ? (
                      <View style={styles.infoRow}>
                        <Text style={styles.infoLabel}>Descripción</Text>
                        <Text style={styles.infoVal}>{inst.descripcion}</Text>
                      </View>
                    ) : null}

                    {inst.criterios?.length > 0 && (
                      <View style={styles.criteriosSection}>
                        <Text style={styles.criteriosTitulo}>
                          Criterios ({inst.criterios.length})
                        </Text>
                        {inst.criterios.map((c: any, ci: number) => (
                          <View key={c.id} style={styles.criterioRow}>
                            <View style={[styles.criterioNum, { backgroundColor: tipoColor + '18' }]}>
                              <Text style={[styles.criterioNumTxt, { color: tipoColor }]}>{ci + 1}</Text>
                            </View>
                            <View style={{ flex: 1, gap: 2 }}>
                              <Text style={styles.criterioNombre}>{c.nombre}</Text>
                              {c.descripcion ? (
                                <Text style={styles.criterioDesc}>{c.descripcion}</Text>
                              ) : null}
                            </View>
                            {c.peso_max ? (
                              <View style={[styles.pesoBadge, { backgroundColor: tipoColor + '18' }]}>
                                <Text style={[styles.pesoTxt, { color: tipoColor }]}>{c.peso_max}pts</Text>
                              </View>
                            ) : null}
                          </View>
                        ))}
                      </View>
                    )}

                    {(!inst.competencia && !inst.descripcion && inst.criterios?.length === 0) && (
                      <Text style={styles.sinDetalle}>Sin detalles adicionales</Text>
                    )}
                  </View>
                )}
              </View>
            )
          })}
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
        <Text style={styles.pageTitle}>Instrumentos</Text>

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
  content:      { padding: 16, paddingBottom: 40, gap: 10 },
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
  countBadge:   { borderRadius: 12, paddingHorizontal: 10, paddingVertical: 4 },
  countBadgeTxt:{ fontSize: 14, fontWeight: '900', color: '#fff' },

  filtroBar:    { flexGrow: 0, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border },
  filtroTab:    { paddingHorizontal: 14, paddingVertical: 7, borderRadius: 20, backgroundColor: Colors.border },
  filtroTxt:    { fontSize: 12, fontWeight: '700', color: Colors.muted },
  filtroTxtActive: { color: '#fff' },

  instrCard:    { backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden',
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  instrHead:    { flexDirection: 'row', alignItems: 'flex-start', gap: 10, padding: 14 },
  instrIconWrap:{ width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  instrTitleRow:{ flexDirection: 'row', alignItems: 'flex-start', gap: 6 },
  instrTitulo:  { fontSize: 14, fontWeight: '800', color: Colors.text, flex: 1 },
  pubBadge:     { borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2, alignSelf: 'flex-start' },
  instrMeta:    { flexDirection: 'row', alignItems: 'center', gap: 6, flexWrap: 'wrap' },
  tipoBadge:    { borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2 },
  tipoTxt:      { fontSize: 11, fontWeight: '700' },
  metaTxt:      { fontSize: 11, color: Colors.muted },

  instrBody:    { borderTopWidth: 1, borderTopColor: Colors.border, padding: 14, gap: 10 },
  infoRow:      { gap: 2 },
  infoLabel:    { fontSize: 11, fontWeight: '700', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5 },
  infoVal:      { fontSize: 13, color: Colors.text, lineHeight: 20 },

  criteriosSection:  { gap: 8 },
  criteriosTitulo:   { fontSize: 12, fontWeight: '700', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5 },
  criterioRow:       { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  criterioNum:       { width: 24, height: 24, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  criterioNumTxt:    { fontSize: 12, fontWeight: '900' },
  criterioNombre:    { fontSize: 13, fontWeight: '700', color: Colors.text },
  criterioDesc:      { fontSize: 12, color: Colors.muted, lineHeight: 18 },
  pesoBadge:         { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3, alignSelf: 'flex-start' },
  pesoTxt:           { fontSize: 12, fontWeight: '800' },

  sinDetalle:   { fontSize: 12, color: Colors.muted, fontStyle: 'italic', textAlign: 'center' },
  empty:        { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:     { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
