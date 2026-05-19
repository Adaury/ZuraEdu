import React, { useState, useMemo } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet, Modal,
  TextInput, KeyboardAvoidingView, Platform, ActivityIndicator,
  RefreshControl, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ESCALA = [
  { valor: 1, label: 'D',  nombre: 'Deficiente',  color: '#ef4444' },
  { valor: 2, label: 'R',  nombre: 'Regular',     color: '#f97316' },
  { valor: 3, label: 'B',  nombre: 'Bueno',       color: '#f59e0b' },
  { valor: 4, label: 'MB', nombre: 'Muy Bueno',   color: '#3b82f6' },
  { valor: 5, label: 'E',  nombre: 'Excelente',   color: '#10b981' },
]

const INDICADORES = [
  { key: 'puntualidad',     label: 'Puntualidad',     icon: 'time-outline'          },
  { key: 'participacion',   label: 'Participación',   icon: 'hand-left-outline'     },
  { key: 'respeto',         label: 'Respeto',         icon: 'heart-outline'         },
  { key: 'trabajo_equipo',  label: 'Trab. Equipo',    icon: 'people-outline'        },
  { key: 'responsabilidad', label: 'Responsabilidad', icon: 'checkbox-outline'      },
  { key: 'orden',           label: 'Orden',           icon: 'reorder-four-outline'  },
] as const

type IndKey = typeof INDICADORES[number]['key']
type FormType = Record<IndKey, number | null> & { observaciones: string }

const EMPTY_FORM: FormType = {
  puntualidad: null, participacion: null, respeto: null,
  trabajo_equipo: null, responsabilidad: null, orden: null,
  observaciones: '',
}

export default function ConductaDocente() {
  const qc = useQueryClient()
  const [selectedAsig,     setSelectedAsig]     = useState<any>(null)
  const [selectedPeriodoId, setSelectedPeriodoId] = useState<number | null>(null)
  const [modalAlumno,      setModalAlumno]      = useState<any | null>(null)
  const [form,             setForm]             = useState<FormType>(EMPTY_FORM)

  const color = Colors.roles.docente

  // Grupos (cacheados)
  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })
  const grupos: any[] = gruposData?.data ?? gruposData?.asignaciones ?? []

  React.useEffect(() => {
    if (grupos.length > 0 && !selectedAsig) setSelectedAsig(grupos[0])
  }, [grupos])

  // Conducta del grupo + período
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['conducta-docente', selectedAsig?.id, selectedPeriodoId],
    queryFn:  () => docenteApi.conducta(selectedAsig!.id, selectedPeriodoId ?? undefined).then(r => r.data),
    enabled:  !!selectedAsig,
  })

  const periodos: any[] = data?.periodos ?? []
  const alumnos:  any[] = data?.alumnos  ?? []
  const activePeriodo   = selectedPeriodoId ?? data?.periodo_id

  const evaluados = useMemo(() => alumnos.filter(a => a.concepto !== null).length, [alumnos])

  const mutation = useMutation({
    mutationFn: (payload: any) => docenteApi.guardarConducta(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['conducta-docente', selectedAsig?.id, activePeriodo] })
      setModalAlumno(null)
    },
    onError: (err: any) => {
      Alert.alert('Error', err?.response?.data?.message ?? 'No se pudo guardar.')
    },
  })

  const openModal = (alumno: any) => {
    const ind = alumno.indicadores ?? {}
    setForm({
      puntualidad:     ind.puntualidad     ?? null,
      participacion:   ind.participacion   ?? null,
      respeto:         ind.respeto         ?? null,
      trabajo_equipo:  ind.trabajo_equipo  ?? null,
      responsabilidad: ind.responsabilidad ?? null,
      orden:           ind.orden           ?? null,
      observaciones:   alumno.observaciones ?? '',
    })
    setModalAlumno(alumno)
  }

  const submit = () => {
    if (!activePeriodo) return Alert.alert('Atención', 'Selecciona un período.')
    mutation.mutate({
      asignacion_id:   selectedAsig!.id,
      matricula_id:    modalAlumno!.matricula_id,
      periodo_id:      activePeriodo,
      ...form,
    })
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Selector de asignación */}
      <ScrollView
        horizontal showsHorizontalScrollIndicator={false}
        style={styles.barWrap} contentContainerStyle={styles.barContent}
      >
        {grupos.map((g: any) => (
          <TouchableOpacity
            key={g.id}
            style={[styles.chip, selectedAsig?.id === g.id && { borderColor: color, backgroundColor: color + '12' }]}
            onPress={() => { setSelectedAsig(g); setSelectedPeriodoId(null) }}
          >
            <Text style={[styles.chipTxt, selectedAsig?.id === g.id && { color }]}>
              {g.asignatura} · {g.grupo}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* Selector de período */}
      {periodos.length > 0 && (
        <ScrollView
          horizontal showsHorizontalScrollIndicator={false}
          style={styles.barWrap2} contentContainerStyle={styles.barContent}
        >
          {periodos.map((p: any) => (
            <TouchableOpacity
              key={p.id}
              style={[styles.chip, activePeriodo === p.id && { borderColor: color, backgroundColor: color + '12' }]}
              onPress={() => setSelectedPeriodoId(p.id)}
            >
              <Text style={[styles.chipTxt, activePeriodo === p.id && { color }]}>{p.nombre}</Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      )}

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={color} />}
      >
        {/* Progreso */}
        {alumnos.length > 0 && (
          <View style={styles.progRow}>
            <Ionicons name="checkmark-circle" size={16} color={Colors.green} />
            <Text style={styles.progTxt}>
              {evaluados} de {alumnos.length} evaluados
            </Text>
            <View style={styles.progBarWrap}>
              <View style={[styles.progBar, { width: `${alumnos.length ? Math.round(evaluados / alumnos.length * 100) : 0}%` as any }]} />
            </View>
          </View>
        )}

        {/* Leyenda de escala */}
        {!isLoading && alumnos.length > 0 && (
          <View style={styles.leyenda}>
            {ESCALA.map(e => (
              <View key={e.valor} style={[styles.leyendaItem, { backgroundColor: e.color + '18' }]}>
                <Text style={[styles.leyendaLbl, { color: e.color }]}>{e.label}</Text>
                <Text style={styles.leyendaNom}>{e.nombre}</Text>
              </View>
            ))}
          </View>
        )}

        {isLoading && <ActivityIndicator style={{ marginTop: 40 }} color={color} />}

        {!isLoading && alumnos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="people-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyTxt}>Sin estudiantes en este grupo</Text>
          </View>
        )}

        {/* Tarjetas de alumnos */}
        {alumnos.map((alumno: any) => (
          <TouchableOpacity
            key={alumno.matricula_id}
            style={styles.card}
            onPress={() => openModal(alumno)}
            activeOpacity={0.85}
          >
            <View style={{ flex: 1 }}>
              <Text style={styles.cardNombre} numberOfLines={1}>{alumno.nombre}</Text>
              {alumno.concepto ? (
                <View style={styles.indicMini}>
                  {INDICADORES.map(({ key }) => {
                    const val = alumno.indicadores?.[key]
                    const meta = val ? ESCALA.find(e => e.valor === val) : null
                    return (
                      <View
                        key={key}
                        style={[styles.dotMini, { backgroundColor: meta ? meta.color : Colors.border }]}
                      />
                    )
                  })}
                </View>
              ) : (
                <Text style={styles.sinReg}>Sin registro</Text>
              )}
            </View>
            <View style={styles.cardRight}>
              {alumno.concepto_label ? (
                <View style={[styles.conceptoBadge, { backgroundColor: alumno.concepto_color + '20' }]}>
                  <Text style={[styles.conceptoLbl, { color: alumno.concepto_color }]}>
                    {alumno.concepto_label}
                  </Text>
                </View>
              ) : (
                <View style={[styles.conceptoBadge, { backgroundColor: Colors.border + '40' }]}>
                  <Text style={[styles.conceptoLbl, { color: Colors.muted }]}>—</Text>
                </View>
              )}
              <Ionicons name="chevron-forward" size={16} color={Colors.muted} />
            </View>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* ── Modal edición de conducta ── */}
      <Modal visible={!!modalAlumno} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <TouchableOpacity onPress={() => setModalAlumno(null)}>
                <Ionicons name="close" size={24} color={Colors.text} />
              </TouchableOpacity>
              <View style={{ flex: 1 }}>
                <Text style={styles.modalTitle} numberOfLines={1}>{modalAlumno?.nombre}</Text>
                <Text style={styles.modalSub}>Registro de Conducta</Text>
              </View>
            </View>

            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
              {/* Indicadores */}
              {INDICADORES.map(({ key, label, icon }) => (
                <View key={key} style={styles.indicRow}>
                  <View style={styles.indicLabel}>
                    <Ionicons name={icon as any} size={14} color={Colors.muted} />
                    <Text style={styles.indicLabelTxt}>{label}</Text>
                  </View>
                  <View style={styles.escalaRow}>
                    {ESCALA.map(({ valor, label: eLbl, color: eColor }) => {
                      const sel = form[key] === valor
                      return (
                        <TouchableOpacity
                          key={valor}
                          style={[styles.escalaBtn, sel && { backgroundColor: eColor, borderColor: eColor }]}
                          onPress={() => setForm(f => ({ ...f, [key]: f[key] === valor ? null : valor }))}
                        >
                          <Text style={[styles.escalaBtnTxt, sel && { color: '#fff', fontWeight: '800' }]}>
                            {eLbl}
                          </Text>
                        </TouchableOpacity>
                      )
                    })}
                  </View>
                </View>
              ))}

              {/* Observaciones opcionales */}
              <Text style={styles.fieldLabel}>Observaciones (opcional)</Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Notas adicionales sobre la conducta..."
                placeholderTextColor={Colors.muted}
                value={form.observaciones}
                onChangeText={t => setForm(f => ({ ...f, observaciones: t }))}
                multiline numberOfLines={3}
                textAlignVertical="top"
                maxLength={500}
              />

              <TouchableOpacity
                style={[styles.submitBtn, { backgroundColor: color }]}
                onPress={submit}
                disabled={mutation.isPending}
              >
                {mutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.submitBtnTxt}>Guardar Conducta</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  barWrap:        { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 54 },
  barWrap2:       { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 46 },
  barContent:     { paddingHorizontal: 12, paddingVertical: 8, gap: 8, flexDirection: 'row', alignItems: 'center' },
  chip:           { paddingHorizontal: 12, paddingVertical: 5, borderRadius: 14, borderWidth: 1.5, borderColor: Colors.border },
  chipTxt:        { fontSize: 12, fontWeight: '600', color: Colors.muted },
  content:        { padding: 14, gap: 10, paddingBottom: 32 },
  progRow:        { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: '#fff', borderRadius: 12, padding: 12 },
  progTxt:        { fontSize: 13, fontWeight: '700', color: Colors.text, flex: 1 },
  progBarWrap:    { width: 60, height: 6, backgroundColor: Colors.border, borderRadius: 3, overflow: 'hidden' },
  progBar:        { height: 6, backgroundColor: Colors.green, borderRadius: 3 },
  leyenda:        { flexDirection: 'row', gap: 6, flexWrap: 'wrap' },
  leyendaItem:    { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  leyendaLbl:     { fontSize: 12, fontWeight: '800' },
  leyendaNom:     { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  empty:          { alignItems: 'center', paddingVertical: 48, gap: 10 },
  emptyTxt:       { fontSize: 14, color: Colors.muted, fontWeight: '600' },
  card:           { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardNombre:     { fontSize: 14, fontWeight: '700', color: Colors.text },
  indicMini:      { flexDirection: 'row', gap: 4, marginTop: 5 },
  dotMini:        { width: 10, height: 10, borderRadius: 5 },
  sinReg:         { fontSize: 11, color: Colors.muted, marginTop: 3 },
  cardRight:      { flexDirection: 'row', alignItems: 'center', gap: 6 },
  conceptoBadge:  { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5, minWidth: 36, alignItems: 'center' },
  conceptoLbl:    { fontSize: 14, fontWeight: '900' },
  modalHeader:    { flexDirection: 'row', alignItems: 'center', gap: 14, padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:     { fontSize: 15, fontWeight: '800', color: Colors.text },
  modalSub:       { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  modalContent:   { padding: 16, gap: 12, paddingBottom: 40 },
  indicRow:       { backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 8 },
  indicLabel:     { flexDirection: 'row', alignItems: 'center', gap: 6 },
  indicLabelTxt:  { fontSize: 13, fontWeight: '700', color: Colors.text },
  escalaRow:      { flexDirection: 'row', gap: 6 },
  escalaBtn:      { flex: 1, paddingVertical: 8, borderRadius: 8, borderWidth: 1.5, borderColor: Colors.border, alignItems: 'center' },
  escalaBtnTxt:   { fontSize: 12, fontWeight: '700', color: Colors.muted },
  fieldLabel:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  input:          { backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  textarea:       { minHeight: 80, textAlignVertical: 'top' },
  submitBtn:      { borderRadius: 14, paddingVertical: 14, alignItems: 'center' },
  submitBtnTxt:   { color: '#fff', fontWeight: '800', fontSize: 15 },
})
