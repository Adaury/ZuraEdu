import React, { useState } from 'react'
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

const EMPTY_TASK = { titulo: '', tipo: 'tarea', fecha_limite: '', descripcion: '', puntos_valor: '' }
const EMPTY_GRADE = { estado: 'revisada', calificacion: '', notas_docente: '' }

const ESTADO_COLORS: Record<string, string> = {
  pendiente: '#f59e0b',
  entregada: '#3b82f6',
  revisada:  '#10b981',
}

export default function TareasDocente() {
  const qc = useQueryClient()
  const [selectedAsig, setSelectedAsig] = useState<any>(null)

  // Modals
  const [createVisible,  setCreateVisible]  = useState(false)
  const [submisVisible,  setSubmisVisible]  = useState(false)
  const [gradeVisible,   setGradeVisible]   = useState(false)
  const [selectedTarea,  setSelectedTarea]  = useState<any>(null)
  const [selectedStudent,setSelectedStudent]= useState<any>(null)

  const [taskForm,  setTaskForm]  = useState(EMPTY_TASK)
  const [gradeForm, setGradeForm] = useState(EMPTY_GRADE)

  const color = Colors.roles.docente

  // Grupos (cached)
  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })
  const grupos: any[] = gruposData?.data ?? gruposData?.asignaciones ?? []

  React.useEffect(() => {
    if (grupos.length > 0 && !selectedAsig) setSelectedAsig(grupos[0])
  }, [grupos])

  // Tareas del grupo seleccionado
  const { data: tareasData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['tareas-docente', selectedAsig?.id],
    queryFn:  () => docenteApi.tareasDocente(selectedAsig!.id).then(r => r.data),
    enabled:  !!selectedAsig,
  })

  // Entregas de la tarea seleccionada
  const { data: entregasData, isLoading: entLoading } = useQuery({
    queryKey: ['entregas-docente', selectedTarea?.id],
    queryFn:  () => docenteApi.entregasTarea(selectedTarea!.id).then(r => r.data),
    enabled:  !!selectedTarea && submisVisible,
  })

  const createMutation = useMutation({
    mutationFn: (payload: any) => docenteApi.storeTarea(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['tareas-docente', selectedAsig?.id] })
      setCreateVisible(false)
      setTaskForm(EMPTY_TASK)
    },
    onError: (err: any) => Alert.alert('Error', err?.response?.data?.message ?? 'Error al crear tarea.'),
  })

  const gradeMutation = useMutation({
    mutationFn: ({ tareaId, data }: { tareaId: number; data: any }) =>
      docenteApi.calificarEntrega(tareaId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['entregas-docente', selectedTarea?.id] })
      qc.invalidateQueries({ queryKey: ['tareas-docente', selectedAsig?.id] })
      setGradeVisible(false)
      setGradeForm(EMPTY_GRADE)
    },
    onError: (err: any) => Alert.alert('Error', err?.response?.data?.message ?? 'Error al calificar.'),
  })

  const tareas: any[] = tareasData?.tareas ?? []
  const tipos: Record<string, string> = tareasData?.tipos ?? {}

  const submitTask = () => {
    if (!taskForm.titulo.trim())       return Alert.alert('Atención', 'Escribe el título.')
    if (!taskForm.fecha_limite.trim()) return Alert.alert('Atención', 'Escribe la fecha límite (AAAA-MM-DD).')
    createMutation.mutate({
      asignacion_id: selectedAsig!.id,
      titulo:        taskForm.titulo,
      tipo:          taskForm.tipo,
      fecha_limite:  taskForm.fecha_limite,
      descripcion:   taskForm.descripcion || undefined,
      puntos_valor:  taskForm.puntos_valor ? parseInt(taskForm.puntos_valor) : undefined,
    })
  }

  const submitGrade = () => {
    if (!selectedStudent) return
    gradeMutation.mutate({
      tareaId: selectedTarea.id,
      data: {
        estudiante_id: selectedStudent.estudiante_id,
        estado:        gradeForm.estado,
        calificacion:  gradeForm.calificacion ? parseFloat(gradeForm.calificacion) : null,
        notas_docente: gradeForm.notas_docente || undefined,
      },
    })
  }

  const openSubmissions = (tarea: any) => {
    setSelectedTarea(tarea)
    setSubmisVisible(true)
  }

  const openGrade = (student: any) => {
    setSelectedStudent(student)
    setGradeForm({
      estado:       student.estado      ?? 'revisada',
      calificacion: student.calificacion != null ? String(student.calificacion) : '',
      notas_docente:student.notas_docente ?? '',
    })
    setGradeVisible(true)
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Selector de asignación */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false}
        style={styles.asigBar} contentContainerStyle={styles.asigBarContent}>
        {grupos.map((g: any) => (
          <TouchableOpacity
            key={g.id}
            style={[styles.asigChip, selectedAsig?.id === g.id && { borderColor: color, backgroundColor: color + '12' }]}
            onPress={() => setSelectedAsig(g)}
          >
            <Text style={[styles.asigChipTxt, selectedAsig?.id === g.id && { color }]}>
              {g.asignatura} · {g.grupo}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={color} />}
      >
        <TouchableOpacity style={[styles.newBtn, { backgroundColor: color }]} onPress={() => setCreateVisible(true)}>
          <Ionicons name="add-circle" size={18} color="#fff" />
          <Text style={styles.newBtnTxt}>Nueva Tarea</Text>
        </TouchableOpacity>

        {isLoading ? (
          <ActivityIndicator style={{ marginTop: 40 }} color={color} />
        ) : tareas.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="checkbox-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>Sin tareas en este grupo</Text>
          </View>
        ) : tareas.map((t: any) => {
          const total = t.total_estudiantes || 1
          const pctEntregado = Math.round(((t.entregadas + t.revisadas) / total) * 100)
          return (
            <TouchableOpacity key={t.id} style={styles.taskCard} onPress={() => openSubmissions(t)}>
              <View style={styles.taskTop}>
                <View style={[styles.tipoBadge, { backgroundColor: t.tipo_color + '20' }]}>
                  <Text style={[styles.tipoBadgeTxt, { color: t.tipo_color }]}>{t.tipo_label}</Text>
                </View>
                <View style={styles.taskTopRight}>
                  {t.esta_vencida && <Ionicons name="time" size={14} color={Colors.red} />}
                  <Text style={[styles.fecha, t.esta_vencida && { color: Colors.red }]}>
                    {t.fecha_limite}
                  </Text>
                </View>
              </View>
              <Text style={styles.taskTitulo}>{t.titulo}</Text>
              {t.puntos_valor ? <Text style={styles.taskPuntos}>{t.puntos_valor} pts</Text> : null}

              {/* Progress */}
              <View style={styles.progressRow}>
                <View style={styles.progressBg}>
                  <View style={[styles.progressFill, { width: `${pctEntregado}%` as any, backgroundColor: color }]} />
                </View>
                <Text style={styles.progressTxt}>{pctEntregado}%</Text>
              </View>
              <View style={styles.countRow}>
                <CountChip label="Pendientes" value={t.pendientes} color={ESTADO_COLORS.pendiente} />
                <CountChip label="Entregadas" value={t.entregadas} color={ESTADO_COLORS.entregada} />
                <CountChip label="Revisadas"  value={t.revisadas}  color={ESTADO_COLORS.revisada}  />
              </View>
            </TouchableOpacity>
          )
        })}
      </ScrollView>

      {/* ── Create task modal ── */}
      <Modal visible={createVisible} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Nueva Tarea</Text>
              <TouchableOpacity onPress={() => { setCreateVisible(false); setTaskForm(EMPTY_TASK) }}>
                <Ionicons name="close" size={24} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
              <Text style={styles.fieldLabel}>Tipo</Text>
              <View style={styles.tiposGrid}>
                {Object.entries(tipos).map(([key, label]) => (
                  <TouchableOpacity
                    key={key}
                    style={[styles.tipoBtn, taskForm.tipo === key && { borderColor: color, backgroundColor: color + '12' }]}
                    onPress={() => setTaskForm(f => ({ ...f, tipo: key }))}
                  >
                    <Text style={[styles.tipoBtnTxt, taskForm.tipo === key && { color }]}>{label as string}</Text>
                  </TouchableOpacity>
                ))}
              </View>

              <Text style={styles.fieldLabel}>Título</Text>
              <TextInput style={styles.input} placeholder="Ej: Ejercicios capítulo 3"
                placeholderTextColor={Colors.muted} value={taskForm.titulo}
                onChangeText={t => setTaskForm(f => ({ ...f, titulo: t }))} maxLength={255}
              />

              <Text style={styles.fieldLabel}>Fecha límite</Text>
              <TextInput style={styles.input} placeholder="AAAA-MM-DD"
                placeholderTextColor={Colors.muted} value={taskForm.fecha_limite}
                onChangeText={t => setTaskForm(f => ({ ...f, fecha_limite: t }))}
              />

              <Text style={styles.fieldLabel}>Descripción <Text style={styles.optional}>(opcional)</Text></Text>
              <TextInput style={[styles.input, styles.textarea]}
                placeholder="Instrucciones adicionales..." placeholderTextColor={Colors.muted}
                value={taskForm.descripcion}
                onChangeText={t => setTaskForm(f => ({ ...f, descripcion: t }))}
                multiline numberOfLines={4} textAlignVertical="top" maxLength={5000}
              />

              <Text style={styles.fieldLabel}>Puntos <Text style={styles.optional}>(opcional)</Text></Text>
              <TextInput style={styles.input} placeholder="Ej: 10"
                placeholderTextColor={Colors.muted} keyboardType="numeric"
                value={taskForm.puntos_valor}
                onChangeText={t => setTaskForm(f => ({ ...f, puntos_valor: t }))}
              />

              <TouchableOpacity style={[styles.submitBtn, { backgroundColor: color }]}
                onPress={submitTask} disabled={createMutation.isPending}>
                {createMutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.submitBtnTxt}>Crear Tarea</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </Modal>

      {/* ── Submissions modal ── */}
      <Modal visible={submisVisible} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <View style={styles.modalHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.modalTitle} numberOfLines={1}>{selectedTarea?.titulo}</Text>
              <Text style={styles.modalSub}>{selectedTarea?.tipo_label} · {selectedTarea?.fecha_limite}</Text>
            </View>
            <TouchableOpacity onPress={() => { setSubmisVisible(false); setSelectedTarea(null) }}>
              <Ionicons name="close" size={24} color={Colors.text} />
            </TouchableOpacity>
          </View>
          {entLoading ? (
            <ActivityIndicator style={{ marginTop: 40 }} color={color} />
          ) : (
            <ScrollView contentContainerStyle={styles.modalContent}>
              {(entregasData?.entregas ?? []).map((e: any) => (
                <TouchableOpacity key={e.estudiante_id} style={styles.entregaRow} onPress={() => openGrade(e)}>
                  <View style={styles.entregaLeft}>
                    <Text style={styles.entregaNombre}>{e.estudiante}</Text>
                    {e.calificacion != null && (
                      <Text style={styles.entregaNota}>{e.calificacion} pts</Text>
                    )}
                  </View>
                  <View style={[styles.estadoBadge, { backgroundColor: ESTADO_COLORS[e.estado] + '20' }]}>
                    <Text style={[styles.estadoBadgeTxt, { color: ESTADO_COLORS[e.estado] }]}>{e.estado_label}</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={Colors.muted} />
                </TouchableOpacity>
              ))}
            </ScrollView>
          )}
        </SafeAreaView>
      </Modal>

      {/* ── Grade modal ── */}
      <Modal visible={gradeVisible} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.modalTitle}>Calificar</Text>
                <Text style={styles.modalSub} numberOfLines={1}>{selectedStudent?.estudiante}</Text>
              </View>
              <TouchableOpacity onPress={() => { setGradeVisible(false); setSelectedStudent(null) }}>
                <Ionicons name="close" size={24} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
              <Text style={styles.fieldLabel}>Estado</Text>
              <View style={styles.tiposGrid}>
                {Object.entries(ESTADO_COLORS).map(([key, clr]) => (
                  <TouchableOpacity
                    key={key}
                    style={[styles.tipoBtn, gradeForm.estado === key && { borderColor: clr, backgroundColor: clr + '12' }]}
                    onPress={() => setGradeForm(f => ({ ...f, estado: key }))}
                  >
                    <Text style={[styles.tipoBtnTxt, gradeForm.estado === key && { color: clr }]}>
                      {key.charAt(0).toUpperCase() + key.slice(1)}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              <Text style={styles.fieldLabel}>Calificación <Text style={styles.optional}>(0–100, opcional)</Text></Text>
              <TextInput style={styles.input} placeholder="Ej: 85"
                placeholderTextColor={Colors.muted} keyboardType="decimal-pad"
                value={gradeForm.calificacion}
                onChangeText={t => setGradeForm(f => ({ ...f, calificacion: t }))}
              />

              <Text style={styles.fieldLabel}>Comentario <Text style={styles.optional}>(opcional)</Text></Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Retroalimentación para el estudiante..."
                placeholderTextColor={Colors.muted}
                value={gradeForm.notas_docente}
                onChangeText={t => setGradeForm(f => ({ ...f, notas_docente: t }))}
                multiline numberOfLines={4} textAlignVertical="top" maxLength={1000}
              />

              <TouchableOpacity style={[styles.submitBtn, { backgroundColor: color }]}
                onPress={submitGrade} disabled={gradeMutation.isPending}>
                {gradeMutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.submitBtnTxt}>Guardar</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  )
}

function CountChip({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <View style={[countStyles.chip, { backgroundColor: color + '15' }]}>
      <Text style={[countStyles.val, { color }]}>{value}</Text>
      <Text style={countStyles.lbl}>{label}</Text>
    </View>
  )
}
const countStyles = StyleSheet.create({
  chip: { flex: 1, alignItems: 'center', borderRadius: 8, paddingVertical: 5 },
  val:  { fontSize: 16, fontWeight: '900' },
  lbl:  { fontSize: 10, color: Colors.muted, fontWeight: '600' },
})

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  asigBar:         { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 54 },
  asigBarContent:  { paddingHorizontal: 12, paddingVertical: 10, gap: 8, flexDirection: 'row' },
  asigChip:        { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, borderWidth: 1.5, borderColor: Colors.border },
  asigChipTxt:     { fontSize: 12, fontWeight: '600', color: Colors.muted },
  content:         { padding: 14, gap: 12, paddingBottom: 32 },
  newBtn:          { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, borderRadius: 14, paddingVertical: 12 },
  newBtnTxt:       { color: '#fff', fontWeight: '700', fontSize: 14 },
  empty:           { alignItems: 'center', paddingVertical: 48, gap: 10 },
  emptyText:       { fontSize: 14, color: Colors.muted, fontWeight: '600' },
  taskCard:        { backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 8, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  taskTop:         { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  tipoBadge:       { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  tipoBadgeTxt:    { fontSize: 11, fontWeight: '700' },
  taskTopRight:    { flexDirection: 'row', alignItems: 'center', gap: 4 },
  fecha:           { fontSize: 12, color: Colors.muted, fontWeight: '600' },
  taskTitulo:      { fontSize: 15, fontWeight: '800', color: Colors.text },
  taskPuntos:      { fontSize: 12, color: Colors.muted },
  progressRow:     { flexDirection: 'row', alignItems: 'center', gap: 8 },
  progressBg:      { flex: 1, height: 6, borderRadius: 3, backgroundColor: Colors.border, overflow: 'hidden' },
  progressFill:    { height: 6, borderRadius: 3 },
  progressTxt:     { fontSize: 11, fontWeight: '700', color: Colors.muted, width: 32, textAlign: 'right' },
  countRow:        { flexDirection: 'row', gap: 6 },
  modalHeader:     { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:      { fontSize: 17, fontWeight: '800', color: Colors.text },
  modalSub:        { fontSize: 12, color: Colors.muted, marginTop: 2 },
  modalContent:    { padding: 16, gap: 14, paddingBottom: 40 },
  fieldLabel:      { fontSize: 13, fontWeight: '700', color: Colors.text },
  optional:        { fontSize: 12, color: Colors.muted, fontWeight: '400' },
  tiposGrid:       { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tipoBtn:         { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 12, paddingVertical: 7 },
  tipoBtnTxt:      { fontSize: 13, color: Colors.muted, fontWeight: '600' },
  input:           { backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  textarea:        { minHeight: 90, textAlignVertical: 'top' },
  submitBtn:       { borderRadius: 14, paddingVertical: 14, alignItems: 'center' },
  submitBtnTxt:    { color: '#fff', fontWeight: '800', fontSize: 15 },
  entregaRow:      { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: '#fff', borderRadius: 12, padding: 12, marginBottom: 8, shadowColor: '#000', shadowOpacity: .03, shadowRadius: 4, elevation: 1 },
  entregaLeft:     { flex: 1 },
  entregaNombre:   { fontSize: 14, fontWeight: '700', color: Colors.text },
  entregaNota:     { fontSize: 12, color: Colors.muted, marginTop: 2 },
  estadoBadge:     { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  estadoBadgeTxt:  { fontSize: 11, fontWeight: '700' },
})
