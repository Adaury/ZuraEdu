import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, RefreshControl, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { resultadosApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

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

const NIVEL_CONFIG: Record<string, { label: string; color: string }> = {
  excelente:  { label: 'Excelente',  color: '#22c55e' },
  bueno:      { label: 'Bueno',      color: '#3b82f6' },
  regular:    { label: 'Regular',    color: '#f59e0b' },
  en_proceso: { label: 'En Proceso', color: '#ef4444' },
}

export default function ResultadosEvaluacionPadre() {
  const [hijoActual, setHijoActual] = useState<any>(null)
  const [expandedId, setExpandedId] = useState<number | null>(null)
  const [filtroAsig, setFiltroAsig] = useState<string | null>(null)

  const { data: classData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })
  const hijos: any[] = classData?.hijos ?? []
  const hijo = hijoActual ?? hijos[0] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['resultados-hijo', hijo?.estudiante_id],
    queryFn:  () => resultadosApi.hijo(hijo.estudiante_id).then(r => r.data),
    enabled:  !!hijo?.estudiante_id,
  })

  const resultados: any[] = data?.resultados ?? []
  const asignaturas = [...new Set(resultados.map((r: any) => r.asignatura))].sort()
  const filtrados = filtroAsig ? resultados.filter((r: any) => r.asignatura === filtroAsig) : resultados

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        <Text style={styles.pageTitle}>Evaluaciones</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hijoTabs}>
            {hijos.map((h: any) => {
              const active = (hijoActual ?? hijos[0])?.estudiante_id === h.estudiante_id
              return (
                <TouchableOpacity
                  key={h.estudiante_id}
                  style={[styles.hijoTab, active && styles.hijoTabActive]}
                  onPress={() => { setHijoActual(h); setExpandedId(null); setFiltroAsig(null) }}
                >
                  <Text style={[styles.hijoTabTxt, active && styles.hijoTabTxtActive]}>{h.nombre}</Text>
                </TouchableOpacity>
              )
            })}
          </ScrollView>
        )}

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {/* Filtro por asignatura */}
        {!isLoading && asignaturas.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.filtroBar}>
            <TouchableOpacity
              style={[styles.filtroTab, !filtroAsig && styles.filtroTabActive]}
              onPress={() => setFiltroAsig(null)}
            >
              <Text style={[styles.filtroTxt, !filtroAsig && styles.filtroTxtActive]}>Todas</Text>
            </TouchableOpacity>
            {asignaturas.map(asig => {
              const active = filtroAsig === asig
              const color  = resultados.find((r: any) => r.asignatura === asig)?.asignatura_color ?? Colors.blue
              return (
                <TouchableOpacity
                  key={asig as string}
                  style={[styles.filtroTab, active && { backgroundColor: color }]}
                  onPress={() => setFiltroAsig(active ? null : asig as string)}
                >
                  <Text style={[styles.filtroTxt, active && styles.filtroTxtActive]}>{asig as string}</Text>
                </TouchableOpacity>
              )
            })}
          </ScrollView>
        )}

        {!isLoading && hijo && filtrados.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="document-text-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>No hay resultados de evaluación registrados</Text>
          </View>
        )}

        {filtrados.map((res: any, i: number) => {
          const expanded  = expandedId === res.instrumento_id
          const tipoColor = TIPO_COLOR[res.tipo] ?? res.asignatura_color
          const tipoIcon  = TIPO_ICON[res.tipo]  ?? 'document-outline'
          const nivelCfg  = res.nivel_desempeno ? (NIVEL_CONFIG[res.nivel_desempeno] ?? null) : null

          return (
            <View key={i} style={styles.card}>
              <View style={[styles.cardAccent, { backgroundColor: res.asignatura_color }]} />
              <View style={{ flex: 1 }}>
                <TouchableOpacity
                  style={styles.cardHead}
                  onPress={() => setExpandedId(expanded ? null : res.instrumento_id)}
                  activeOpacity={0.8}
                >
                  <View style={[styles.tipoIcon, { backgroundColor: tipoColor + '18' }]}>
                    <Ionicons name={tipoIcon} size={18} color={tipoColor} />
                  </View>
                  <View style={{ flex: 1, gap: 3 }}>
                    <Text style={styles.titulo} numberOfLines={expanded ? 0 : 2}>{res.titulo}</Text>
                    <View style={styles.metaRow}>
                      <Text style={[styles.metaTxt, { color: res.asignatura_color, fontWeight: '700' }]}>
                        {res.asignatura}
                      </Text>
                      <Text style={styles.metaDot}>·</Text>
                      <Text style={styles.metaTxt}>{res.periodo_nombre}</Text>
                    </View>
                  </View>
                  <View style={styles.scoreCol}>
                    {res.ponderacion != null && (
                      <Text style={[styles.scoreNum, { color: res.asignatura_color }]}>{res.ponderacion}</Text>
                    )}
                    {nivelCfg && (
                      <View style={[styles.nivelBadge, { backgroundColor: nivelCfg.color + '20' }]}>
                        <Text style={[styles.nivelTxt, { color: nivelCfg.color }]}>{nivelCfg.label}</Text>
                      </View>
                    )}
                    <Ionicons name={expanded ? 'chevron-up' : 'chevron-down'} size={14} color={Colors.muted} />
                  </View>
                </TouchableOpacity>

                {expanded && (
                  <View style={styles.cardBody}>
                    {res.observacion ? (
                      <View style={styles.obsBox}>
                        <Ionicons name="chatbubble-outline" size={13} color={Colors.muted} />
                        <Text style={styles.obsText}>{res.observacion}</Text>
                      </View>
                    ) : null}

                    {res.criterios?.length > 0 && (
                      <View style={styles.criteriosSection}>
                        <Text style={styles.criteriosTitulo}>Criterios evaluados</Text>
                        {res.criterios.map((c: any, ci: number) => {
                          const pctPuntaje = c.peso_max > 0 && c.puntaje != null
                            ? Math.round((c.puntaje / c.peso_max) * 100)
                            : null
                          return (
                            <View key={c.id} style={styles.criterioRow}>
                              <View style={[styles.criterioNum, { backgroundColor: tipoColor + '18' }]}>
                                <Text style={[styles.criterioNumTxt, { color: tipoColor }]}>{ci + 1}</Text>
                              </View>
                              <View style={{ flex: 1, gap: 4 }}>
                                <Text style={styles.criterioNombre}>{c.nombre}</Text>
                                {c.puntaje != null ? (
                                  <View style={styles.puntajeRow}>
                                    <View style={styles.puntajeBarTrack}>
                                      <View style={[styles.puntajeBarFill, {
                                        width: `${pctPuntaje ?? 0}%` as any,
                                        backgroundColor: tipoColor,
                                      }]} />
                                    </View>
                                    <Text style={[styles.puntajeTxt, { color: tipoColor }]}>
                                      {c.puntaje}/{c.peso_max}
                                    </Text>
                                  </View>
                                ) : (
                                  <Text style={styles.sinPuntaje}>Sin puntaje asignado</Text>
                                )}
                              </View>
                            </View>
                          )
                        })}
                      </View>
                    )}
                  </View>
                )}
              </View>
            </View>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, gap: 10, paddingBottom: 32 },
  pageTitle:       { fontSize: 22, fontWeight: '900', color: Colors.text },

  hijoTabs:        { flexGrow: 0 },
  hijoTab:         { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20,
                     backgroundColor: Colors.border, marginRight: 8 },
  hijoTabActive:   { backgroundColor: Colors.roles.padre },
  hijoTabTxt:      { fontSize: 13, fontWeight: '700', color: Colors.muted },
  hijoTabTxtActive:{ color: '#fff' },

  filtroBar:       { flexGrow: 0, marginBottom: 2 },
  filtroTab:       { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20,
                     backgroundColor: Colors.border, marginRight: 8 },
  filtroTabActive: { backgroundColor: Colors.blue },
  filtroTxt:       { fontSize: 12, fontWeight: '700', color: Colors.muted },
  filtroTxtActive: { color: '#fff' },

  empty:           { alignItems: 'center', gap: 12, paddingVertical: 48 },
  emptyTxt:        { fontSize: 14, color: Colors.muted, textAlign: 'center' },

  card:            { backgroundColor: '#fff', borderRadius: 16, flexDirection: 'row', overflow: 'hidden',
                     shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  cardAccent:      { width: 5 },
  cardHead:        { flexDirection: 'row', alignItems: 'flex-start', gap: 10, padding: 14 },
  tipoIcon:        { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  titulo:          { fontSize: 14, fontWeight: '800', color: Colors.text },
  metaRow:         { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaTxt:         { fontSize: 11, color: Colors.muted },
  metaDot:         { fontSize: 11, color: Colors.muted },

  scoreCol:        { alignItems: 'flex-end', gap: 4 },
  scoreNum:        { fontSize: 20, fontWeight: '900' },
  nivelBadge:      { borderRadius: 6, paddingHorizontal: 7, paddingVertical: 2 },
  nivelTxt:        { fontSize: 10, fontWeight: '700' },

  cardBody:        { borderTopWidth: 1, borderTopColor: Colors.border, padding: 14, gap: 10 },
  obsBox:          { flexDirection: 'row', gap: 6, backgroundColor: '#f8fafc', borderRadius: 8, padding: 8 },
  obsText:         { fontSize: 12, color: Colors.muted, flex: 1, lineHeight: 18 },

  criteriosSection:{ gap: 8 },
  criteriosTitulo: { fontSize: 11, fontWeight: '700', color: Colors.muted,
                     textTransform: 'uppercase', letterSpacing: .5 },
  criterioRow:     { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  criterioNum:     { width: 24, height: 24, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  criterioNumTxt:  { fontSize: 12, fontWeight: '900' },
  criterioNombre:  { fontSize: 13, fontWeight: '700', color: Colors.text },
  puntajeRow:      { flexDirection: 'row', alignItems: 'center', gap: 8 },
  puntajeBarTrack: { flex: 1, height: 5, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  puntajeBarFill:  { height: '100%', borderRadius: 99 },
  puntajeTxt:      { fontSize: 12, fontWeight: '800', minWidth: 40, textAlign: 'right' },
  sinPuntaje:      { fontSize: 11, color: Colors.muted, fontStyle: 'italic' },
})
