import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

function notaColor(nota: number | null) {
  if (nota == null) return Colors.muted
  if (nota >= 70) return Colors.green
  if (nota >= 60) return Colors.amber
  return Colors.red
}

export default function CalificacionesDocente() {
  const [asignacionSel, setAsignacion] = useState<any | null>(null)

  const { data: gruposData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const { data: calData, isLoading: calLoading } = useQuery({
    queryKey:  ['docente-calificaciones', asignacionSel?.asignacion_id],
    queryFn:   () => docenteApi.calificaciones(asignacionSel!.asignacion_id).then(r => r.data),
    enabled:   !!asignacionSel,
  })

  const asignaciones: any[] = gruposData?.asignaciones ?? []

  // ── Vista detalle (notas del grupo) ───────────────────────────────────
  if (asignacionSel) {
    const estudiantes: any[] = calData?.estudiantes ?? []
    const periodos: string[] = estudiantes.length > 0
      ? [...new Set(estudiantes.flatMap((e: any) => e.notas.map((n: any) => n.periodo)))] as string[]
      : []

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: asignacionSel.color ?? ACCENT }]}>
          <TouchableOpacity onPress={() => setAsignacion(null)} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{asignacionSel.asignatura}</Text>
            <Text style={styles.detSub}>{asignacionSel.grupo}</Text>
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {calLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

          {!calLoading && estudiantes.length === 0 && (
            <View style={styles.centered}>
              <Ionicons name="document-text-outline" size={44} color={Colors.muted} />
              <Text style={styles.emptyText}>No hay calificaciones registradas aún.</Text>
            </View>
          )}

          {!calLoading && periodos.length > 0 && (
            <View style={styles.tableHeader}>
              <Text style={[styles.thCell, { flex: 2 }]}>Estudiante</Text>
              {periodos.map(p => (
                <Text key={p} style={styles.thCell}>{p}</Text>
              ))}
            </View>
          )}

          {estudiantes.map((est: any) => (
            <View key={est.matricula_id} style={styles.tableRow}>
              <Text style={[styles.tdNombre, { flex: 2 }]} numberOfLines={1}>{est.nombre}</Text>
              {periodos.map(p => {
                const nota = est.notas.find((n: any) => n.periodo === p)?.nota_final ?? null
                return (
                  <View key={p} style={styles.tdCell}>
                    <Text style={[styles.tdNota, { color: notaColor(nota) }]}>
                      {nota != null ? nota.toFixed(1) : '—'}
                    </Text>
                  </View>
                )
              })}
            </View>
          ))}
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
        <Text style={styles.pageTitle}>Calificaciones</Text>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

        {!isLoading && asignaciones.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="people-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>No tienes grupos asignados.</Text>
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
              <Text style={styles.grupoAlumnos}>{a.alumnos?.length ?? 0} estudiantes</Text>
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
  centered:     { alignItems: 'center', paddingVertical: 48, gap: 10 },
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

  tableHeader:  { flexDirection: 'row', backgroundColor: ACCENT + '18', borderRadius: 10, padding: 10, gap: 6 },
  thCell:       { flex: 1, fontSize: 11, fontWeight: '700', color: Colors.text, textAlign: 'center' },
  tableRow:     { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 6,
                  alignItems: 'center', shadowColor: '#000', shadowOpacity: .03, shadowRadius: 4, elevation: 1 },
  tdNombre:     { fontSize: 12, fontWeight: '600', color: Colors.text },
  tdCell:       { flex: 1, alignItems: 'center' },
  tdNota:       { fontSize: 14, fontWeight: '800' },

  emptyText:    { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
