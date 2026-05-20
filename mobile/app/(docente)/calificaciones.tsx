import React, { useState, useRef } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity, RefreshControl, TextInput, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
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
  const qc = useQueryClient()
  const [asignacionSel, setAsignacion] = useState<any | null>(null)
  const [periodoSel,    setPeriodo]    = useState<any | null>(null)
  const [editingId,     setEditing]    = useState<number | null>(null)
  const [editValue,     setEditValue]  = useState<string>('')
  const inputRef = useRef<TextInput>(null)

  const { data: gruposData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const { data: calData, isLoading: calLoading, refetch: calRefetch } = useQuery({
    queryKey:  ['docente-calificaciones', asignacionSel?.asignacion_id],
    queryFn:   () => docenteApi.calificaciones(asignacionSel!.asignacion_id).then(r => r.data),
    enabled:   !!asignacionSel,
  })

  const guardar = useMutation({
    mutationFn: (vars: { matricula_id: number; periodo_id: number; nota_final: number }) =>
      docenteApi.guardarCalificacion(asignacionSel!.asignacion_id, vars),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['docente-calificaciones', asignacionSel?.asignacion_id] })
      setEditing(null)
    },
    onError: () => Alert.alert('Error', 'No se pudo guardar la nota.'),
  })

  const commitEdit = (matriculaId: number) => {
    const val = parseFloat(editValue.replace(',', '.'))
    if (isNaN(val) || val < 0 || val > 100) {
      Alert.alert('Valor inválido', 'La nota debe ser un número entre 0 y 100.')
      return
    }
    if (!periodoSel) return
    guardar.mutate({ matricula_id: matriculaId, periodo_id: periodoSel.id, nota_final: val })
  }

  const asignaciones: any[] = gruposData?.asignaciones ?? []

  // ── Vista detalle ─────────────────────────────────────────────────────
  if (asignacionSel) {
    const estudiantes: any[] = calData?.estudiantes ?? []
    const periodos: any[]    = calData?.periodos    ?? []

    const periodo = periodoSel ?? periodos[0] ?? null
    if (!periodoSel && periodo) setPeriodo(periodo)

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: asignacionSel.color ?? ACCENT }]}>
          <TouchableOpacity onPress={() => { setAsignacion(null); setPeriodo(null); setEditing(null) }} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{asignacionSel.asignatura}</Text>
            <Text style={styles.detSub}>{asignacionSel.grupo}</Text>
          </View>
          <TouchableOpacity onPress={() => calRefetch()} style={{ padding: 8 }}>
            <Ionicons name="refresh" size={18} color="rgba(255,255,255,.8)" />
          </TouchableOpacity>
        </View>

        {/* Tabs de períodos */}
        {!calLoading && periodos.length > 0 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.tabsScroll} contentContainerStyle={styles.tabsContent}>
            {periodos.map((p: any) => (
              <TouchableOpacity
                key={p.id}
                style={[styles.periodoTab, periodo?.id === p.id && { backgroundColor: ACCENT }]}
                onPress={() => { setPeriodo(p); setEditing(null) }}
              >
                <Text style={[styles.periodoTabTxt, periodo?.id === p.id && { color: '#fff' }]}>{p.nombre}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        <ScrollView contentContainerStyle={styles.content}>
          {calLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

          {!calLoading && estudiantes.length === 0 && (
            <View style={styles.centered}>
              <Ionicons name="document-text-outline" size={44} color={Colors.muted} />
              <Text style={styles.emptyText}>No hay estudiantes en este grupo.</Text>
            </View>
          )}

          {!calLoading && periodo && estudiantes.map((est: any) => {
            const notaObj = est.notas.find((n: any) => n.periodo_id === periodo.id)
            const nota    = notaObj?.nota_final ?? null
            const isEdit  = editingId === est.matricula_id

            return (
              <View key={est.matricula_id} style={styles.estudRow}>
                <Text style={styles.estudNombre} numberOfLines={1}>{est.nombre}</Text>

                {isEdit ? (
                  <View style={styles.editBox}>
                    <TextInput
                      ref={inputRef}
                      style={styles.notaInput}
                      value={editValue}
                      onChangeText={setEditValue}
                      keyboardType="decimal-pad"
                      placeholder="0–100"
                      placeholderTextColor={Colors.muted}
                      autoFocus
                      selectTextOnFocus
                    />
                    <TouchableOpacity
                      style={[styles.saveBtn, { backgroundColor: ACCENT }]}
                      onPress={() => commitEdit(est.matricula_id)}
                      disabled={guardar.isPending}
                    >
                      {guardar.isPending && guardar.variables?.matricula_id === est.matricula_id
                        ? <ActivityIndicator size="small" color="#fff" />
                        : <Ionicons name="checkmark" size={16} color="#fff" />
                      }
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.cancelBtn} onPress={() => setEditing(null)}>
                      <Ionicons name="close" size={16} color={Colors.muted} />
                    </TouchableOpacity>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[styles.notaBadge, { backgroundColor: notaColor(nota) + '18' }]}
                    onPress={() => {
                      setEditing(est.matricula_id)
                      setEditValue(nota != null ? nota.toFixed(1) : '')
                      setTimeout(() => inputRef.current?.focus(), 80)
                    }}
                  >
                    <Text style={[styles.notaVal, { color: notaColor(nota) }]}>
                      {nota != null ? nota.toFixed(1) : '—'}
                    </Text>
                    <Ionicons name="pencil" size={11} color={notaColor(nota)} style={{ marginLeft: 4 }} />
                  </TouchableOpacity>
                )}
              </View>
            )
          })}

          {!calLoading && !periodo && periodos.length === 0 && estudiantes.length > 0 && (
            <View style={styles.centered}>
              <Text style={styles.emptyText}>No hay períodos configurados.</Text>
            </View>
          )}
        </ScrollView>
      </SafeAreaView>
    )
  }

  // ── Vista lista de grupos ─────────────────────────────────────────────
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
            onPress={() => { setAsignacion(a); setPeriodo(null); setEditing(null) }}
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
  content:      { padding: 16, paddingBottom: 40, gap: 8 },
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

  tabsScroll:   { flexGrow: 0, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border },
  tabsContent:  { paddingHorizontal: 12, paddingVertical: 10, gap: 8 },
  periodoTab:   { paddingHorizontal: 16, paddingVertical: 7, borderRadius: 99,
                  borderWidth: 1.5, borderColor: Colors.border, backgroundColor: '#fff' },
  periodoTabTxt:{ fontSize: 12, fontWeight: '700', color: Colors.muted },

  estudRow:     { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12,
                  padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .03, shadowRadius: 4, elevation: 1 },
  estudNombre:  { flex: 1, fontSize: 13, fontWeight: '600', color: Colors.text },

  notaBadge:    { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
  notaVal:      { fontSize: 16, fontWeight: '900' },

  editBox:      { flexDirection: 'row', alignItems: 'center', gap: 6 },
  notaInput:    { width: 72, borderWidth: 1.5, borderColor: ACCENT, borderRadius: 10,
                  paddingHorizontal: 10, paddingVertical: 6, fontSize: 15, fontWeight: '700',
                  color: Colors.text, textAlign: 'center', backgroundColor: '#fff' },
  saveBtn:      { width: 34, height: 34, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  cancelBtn:    { width: 30, height: 30, borderRadius: 8, alignItems: 'center', justifyContent: 'center',
                  backgroundColor: Colors.border },
  emptyText:    { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
