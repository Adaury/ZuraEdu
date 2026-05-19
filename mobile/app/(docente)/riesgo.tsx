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

const DIMS = [
  { key: 'dim_academico',  label: 'Académico',  color: '#3b82f6' },
  { key: 'dim_asistencia', label: 'Asistencia', color: '#10b981' },
  { key: 'dim_disciplina', label: 'Disciplina', color: '#f59e0b' },
]

export default function RiesgoDocente() {
  const [asignacionSel, setAsignacion] = useState<any | null>(null)
  const [expandedId, setExpandedId]   = useState<number | null>(null)
  const [filtroNivel, setFiltroNivel] = useState<string | null>(null)

  const { data: gruposData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const { data, isLoading: detLoading, refetch: refetchDet, isRefetching: isRefetchingDet } = useQuery({
    queryKey: ['docente-riesgo', asignacionSel?.asignacion_id],
    queryFn:  () => docenteApi.riesgoGrupo(asignacionSel!.asignacion_id).then(r => r.data),
    enabled:  !!asignacionSel,
  })

  const asignaciones: any[] = gruposData?.asignaciones ?? []

  // ── Vista detalle ──────────────────────────────────────────────────────
  if (asignacionSel) {
    const alumnos: any[]  = data?.alumnos  ?? []
    const niveles: any[]  = data?.niveles  ?? []
    const total: number   = data?.total    ?? 0
    const conDatos: number= data?.con_datos ?? 0

    const filtrados = filtroNivel
      ? alumnos.filter(a => a.nivel === filtroNivel)
      : alumnos

    // Distribución por nivel
    const distribucion = niveles.map((n: any) => ({
      ...n,
      count: alumnos.filter(a => a.nivel === n.nivel).length,
    })).filter(n => n.count > 0)

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: asignacionSel.color ?? ACCENT }]}>
          <TouchableOpacity onPress={() => { setAsignacion(null); setFiltroNivel(null); setExpandedId(null) }} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{asignacionSel.asignatura}</Text>
            <Text style={styles.detSub}>{asignacionSel.grupo} · {total} estudiantes</Text>
          </View>
        </View>

        <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={<RefreshControl refreshing={isRefetchingDet} onRefresh={refetchDet} tintColor={ACCENT} />}
        >
          {detLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

          {/* Resumen si hay datos */}
          {!detLoading && conDatos === 0 && (
            <View style={styles.empty}>
              <Ionicons name="analytics-outline" size={52} color={Colors.border} />
              <Text style={styles.emptyTxt}>Aún no se han calculado scores de riesgo para este grupo.</Text>
            </View>
          )}

          {!detLoading && conDatos > 0 && (
            <>
              {/* Distribución por nivel */}
              <View style={styles.distCard}>
                <Text style={styles.distTitle}>Distribución del grupo</Text>
                {distribucion.map((d: any) => (
                  <TouchableOpacity
                    key={d.nivel}
                    style={styles.distRow}
                    onPress={() => setFiltroNivel(filtroNivel === d.nivel ? null : d.nivel)}
                  >
                    <View style={[styles.distDot, { backgroundColor: d.color }]} />
                    <Text style={[styles.distLabel, filtroNivel === d.nivel && { fontWeight: '900', color: d.color }]}>
                      {d.label}
                    </Text>
                    <View style={styles.distBarWrap}>
                      <View style={[styles.distBar, {
                        width: `${Math.round((d.count / total) * 100)}%` as any,
                        backgroundColor: d.color + '60',
                      }]} />
                    </View>
                    <Text style={[styles.distCount, { color: d.color }]}>{d.count}</Text>
                  </TouchableOpacity>
                ))}
                {filtroNivel && (
                  <TouchableOpacity onPress={() => setFiltroNivel(null)} style={styles.clearFiltro}>
                    <Ionicons name="close-circle" size={14} color={Colors.muted} />
                    <Text style={styles.clearFiltroTxt}>Quitar filtro</Text>
                  </TouchableOpacity>
                )}
              </View>

              {/* Lista de alumnos */}
              <Text style={styles.listHeader}>
                {filtroNivel ? `${filtrados.length} en nivel seleccionado` : `${conDatos} con score calculado`}
              </Text>

              {filtrados.map((alumno: any) => {
                const expanded = expandedId === alumno.matricula_id
                return (
                  <TouchableOpacity
                    key={alumno.matricula_id}
                    style={styles.alumnoCard}
                    onPress={() => setExpandedId(expanded ? null : alumno.matricula_id)}
                    activeOpacity={0.85}
                  >
                    {/* Fila principal */}
                    <View style={styles.alumnoMain}>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.alumnoNombre} numberOfLines={1}>{alumno.nombre}</Text>
                        {alumno.calculado ? (
                          <Text style={styles.alumnoSub}>
                            Prom. {alumno.promedio ?? '—'} · Asist. {alumno.pct_asistencia != null ? `${alumno.pct_asistencia}%` : '—'}
                          </Text>
                        ) : (
                          <Text style={styles.alumnoSub}>Sin datos calculados</Text>
                        )}
                      </View>

                      {alumno.calculado ? (
                        <View style={styles.scoreWrap}>
                          <View style={[styles.scoreBadge, { backgroundColor: alumno.nivel_color + '20' }]}>
                            <Text style={[styles.scoreNum, { color: alumno.nivel_color }]}>{alumno.score}</Text>
                          </View>
                          <Text style={[styles.scoreLabel, { color: alumno.nivel_color }]}>{alumno.nivel_label}</Text>
                        </View>
                      ) : (
                        <Text style={[styles.scoreLabel, { color: Colors.muted }]}>—</Text>
                      )}

                      <Ionicons name={expanded ? 'chevron-up' : 'chevron-down'} size={14} color={Colors.muted} style={{ marginLeft: 6 }} />
                    </View>

                    {/* Dimensiones expandidas */}
                    {expanded && alumno.calculado && (
                      <View style={styles.dimsGrid}>
                        {DIMS.map(dim => {
                          const val: number = alumno[dim.key] ?? 0
                          return (
                            <View key={dim.key} style={styles.dimRow}>
                              <Text style={styles.dimLabel}>{dim.label}</Text>
                              <View style={styles.dimBarTrack}>
                                <View style={[styles.dimBarFill, { width: `${val}%` as any, backgroundColor: dim.color }]} />
                              </View>
                              <Text style={[styles.dimVal, { color: dim.color }]}>{val}</Text>
                            </View>
                          )
                        })}
                        {alumno.materias_riesgo > 0 && (
                          <View style={[styles.materiasChip, { backgroundColor: Colors.red + '15' }]}>
                            <Ionicons name="warning-outline" size={13} color={Colors.red} />
                            <Text style={[styles.materiasChipTxt, { color: Colors.red }]}>
                              {alumno.materias_riesgo} de {alumno.total_materias} materias en riesgo
                            </Text>
                          </View>
                        )}
                      </View>
                    )}
                  </TouchableOpacity>
                )
              })}
            </>
          )}
        </ScrollView>
      </SafeAreaView>
    )
  }

  // ── Vista lista de grupos ──────────────────────────────────────────────
  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.pageTitle}>Riesgo Académico</Text>
        <Text style={styles.pageSub}>Selecciona un grupo para ver el estado de sus estudiantes</Text>

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
  pageTitle:    { fontSize: 22, fontWeight: '900', color: Colors.text },
  pageSub:      { fontSize: 13, color: Colors.muted, marginTop: 2, marginBottom: 4 },

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

  distCard:     { backgroundColor: '#fff', borderRadius: 16, padding: 16, gap: 10,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  distTitle:    { fontSize: 14, fontWeight: '800', color: Colors.text, marginBottom: 2 },
  distRow:      { flexDirection: 'row', alignItems: 'center', gap: 8 },
  distDot:      { width: 8, height: 8, borderRadius: 99 },
  distLabel:    { fontSize: 13, color: Colors.text, width: 72 },
  distBarWrap:  { flex: 1, height: 8, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  distBar:      { height: '100%', borderRadius: 99 },
  distCount:    { fontSize: 14, fontWeight: '800', width: 24, textAlign: 'right' },
  clearFiltro:  { flexDirection: 'row', alignItems: 'center', gap: 4, alignSelf: 'flex-end' },
  clearFiltroTxt: { fontSize: 11, color: Colors.muted },

  listHeader:   { fontSize: 12, fontWeight: '700', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5 },

  alumnoCard:   { backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden',
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 4, elevation: 1 },
  alumnoMain:   { flexDirection: 'row', alignItems: 'center', padding: 12, gap: 10 },
  alumnoNombre: { fontSize: 14, fontWeight: '700', color: Colors.text },
  alumnoSub:    { fontSize: 11, color: Colors.muted, marginTop: 2 },
  scoreWrap:    { alignItems: 'center', gap: 2 },
  scoreBadge:   { borderRadius: 10, paddingHorizontal: 10, paddingVertical: 4 },
  scoreNum:     { fontSize: 16, fontWeight: '900' },
  scoreLabel:   { fontSize: 10, fontWeight: '700' },

  dimsGrid:     { borderTopWidth: 1, borderTopColor: Colors.border, padding: 12, gap: 8 },
  dimRow:       { flexDirection: 'row', alignItems: 'center', gap: 8 },
  dimLabel:     { fontSize: 11, color: Colors.muted, width: 68 },
  dimBarTrack:  { flex: 1, height: 6, backgroundColor: Colors.border, borderRadius: 99, overflow: 'hidden' },
  dimBarFill:   { height: '100%', borderRadius: 99 },
  dimVal:       { fontSize: 12, fontWeight: '800', width: 28, textAlign: 'right' },
  materiasChip: { flexDirection: 'row', alignItems: 'center', gap: 6, borderRadius: 8, padding: 8, marginTop: 4 },
  materiasChipTxt: { fontSize: 12, fontWeight: '700' },

  empty:        { alignItems: 'center', gap: 12, paddingVertical: 48 },
  emptyTxt:     { fontSize: 13, color: Colors.muted, textAlign: 'center', maxWidth: 260 },
})
