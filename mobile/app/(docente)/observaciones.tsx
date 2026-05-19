import React, { useState, useMemo } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet, Modal,
  TextInput, KeyboardAvoidingView, Platform, ActivityIndicator,
  RefreshControl, Alert, Switch,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPOS_OBS = {
  academica:  { label: 'Académica',  color: '#3b82f6', icon: 'book' },
  conductual: { label: 'Conductual', color: '#ef4444', icon: 'person-remove' },
  positiva:   { label: 'Positiva',   color: '#10b981', icon: 'star' },
  general:    { label: 'General',    color: '#6b7280', icon: 'chatbubble' },
}

const EMPTY_FORM = { estudiante_id: 0, tipo: 'academica', texto: '', privada: false }

export default function ObservacionesDocente() {
  const qc = useQueryClient()
  const [selectedAsig, setSelectedAsig] = useState<any>(null)
  const [filterTipo, setFilterTipo]     = useState<string | null>(null)
  const [modalVisible, setModalVisible] = useState(false)
  const [form, setForm]                 = useState(EMPTY_FORM)

  // Grupos (cached)
  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })
  const grupos: any[] = gruposData?.data ?? gruposData?.asignaciones ?? []

  // Auto-seleccionar primera asignación
  React.useEffect(() => {
    if (grupos.length > 0 && !selectedAsig) setSelectedAsig(grupos[0])
  }, [grupos])

  // Observaciones del grupo seleccionado
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['obs-docente', selectedAsig?.id],
    queryFn:  () => docenteApi.observaciones(selectedAsig!.id).then(r => r.data),
    enabled:  !!selectedAsig,
  })

  const mutation = useMutation({
    mutationFn: (payload: any) => docenteApi.storeObservacion(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['obs-docente', selectedAsig?.id] })
      setModalVisible(false)
      setForm(EMPTY_FORM)
    },
    onError: (err: any) => {
      Alert.alert('Error', err?.response?.data?.message ?? 'Error al guardar.')
    },
  })

  const alumnos: any[] = selectedAsig?.alumnos ?? []
  const observaciones: any[] = useMemo(() => {
    const list = data?.observaciones ?? []
    return filterTipo ? list.filter((o: any) => o.tipo === filterTipo) : list
  }, [data, filterTipo])

  const submit = () => {
    if (!form.estudiante_id) return Alert.alert('Atención', 'Selecciona un estudiante.')
    if (!form.texto.trim())   return Alert.alert('Atención', 'Escribe el texto de la observación.')
    mutation.mutate({
      asignacion_id: selectedAsig!.id,
      estudiante_id: form.estudiante_id,
      tipo:          form.tipo,
      texto:         form.texto,
      privada:       form.privada,
    })
  }

  const color = Colors.roles.docente

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Selector de asignación */}
      <ScrollView
        horizontal showsHorizontalScrollIndicator={false}
        style={styles.asigBar} contentContainerStyle={styles.asigBarContent}
      >
        {grupos.map((g: any) => (
          <TouchableOpacity
            key={g.id}
            style={[styles.asigChip, selectedAsig?.id === g.id && { borderColor: color, backgroundColor: color + '12' }]}
            onPress={() => { setSelectedAsig(g); setFilterTipo(null) }}
          >
            <Text style={[styles.asigChipTxt, selectedAsig?.id === g.id && { color }]}>
              {g.asignatura} · {g.grupo}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* Filtros de tipo */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false}
        style={styles.filterBar} contentContainerStyle={styles.filterBarContent}>
        <TouchableOpacity
          style={[styles.filterChip, !filterTipo && styles.filterChipActive]}
          onPress={() => setFilterTipo(null)}
        >
          <Text style={[styles.filterTxt, !filterTipo && { color }]}>Todas</Text>
        </TouchableOpacity>
        {Object.entries(TIPOS_OBS).map(([key, meta]) => (
          <TouchableOpacity
            key={key}
            style={[styles.filterChip, filterTipo === key && { borderColor: meta.color, backgroundColor: meta.color + '12' }]}
            onPress={() => setFilterTipo(t => t === key ? null : key)}
          >
            <Text style={[styles.filterTxt, filterTipo === key && { color: meta.color }]}>{meta.label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={color} />}
      >
        {/* Nueva observación */}
        <TouchableOpacity style={[styles.newBtn, { backgroundColor: color }]} onPress={() => setModalVisible(true)}>
          <Ionicons name="add-circle" size={18} color="#fff" />
          <Text style={styles.newBtnText}>Nueva Observación</Text>
        </TouchableOpacity>

        {isLoading ? (
          <ActivityIndicator style={{ marginTop: 40 }} color={color} />
        ) : observaciones.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="chatbubble-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>Sin observaciones registradas</Text>
          </View>
        ) : observaciones.map((obs: any) => {
          const meta = TIPOS_OBS[obs.tipo as keyof typeof TIPOS_OBS] ?? TIPOS_OBS.general
          return (
            <View key={obs.id} style={styles.card}>
              <View style={[styles.cardAccent, { backgroundColor: meta.color }]} />
              <View style={styles.cardBody}>
                <View style={styles.cardTop}>
                  <View style={[styles.tipoBadge, { backgroundColor: meta.color + '20' }]}>
                    <Ionicons name={meta.icon as any} size={12} color={meta.color} />
                    <Text style={[styles.tipoBadgeTxt, { color: meta.color }]}>{meta.label}</Text>
                  </View>
                  <View style={styles.cardTopRight}>
                    {obs.privada && (
                      <Ionicons name="lock-closed" size={13} color={Colors.muted} />
                    )}
                    <Text style={styles.cardTime}>{obs.creado_hace}</Text>
                  </View>
                </View>
                <Text style={styles.cardEstudiante}>{obs.estudiante}</Text>
                <Text style={styles.cardTexto}>{obs.texto}</Text>
              </View>
            </View>
          )
        })}
      </ScrollView>

      {/* ── Create modal ── */}
      <Modal visible={modalVisible} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Nueva Observación</Text>
              <TouchableOpacity onPress={() => { setModalVisible(false); setForm(EMPTY_FORM) }}>
                <Ionicons name="close" size={24} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
              {/* Estudiante */}
              <Text style={styles.fieldLabel}>Estudiante</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 4 }}>
                <View style={{ flexDirection: 'row', gap: 8 }}>
                  {alumnos.map((a: any) => (
                    <TouchableOpacity
                      key={a.estudiante_id}
                      style={[styles.alumnoChip, form.estudiante_id === a.estudiante_id && { borderColor: color, backgroundColor: color + '12' }]}
                      onPress={() => setForm(f => ({ ...f, estudiante_id: a.estudiante_id }))}
                    >
                      <Text style={[styles.alumnoChipTxt, form.estudiante_id === a.estudiante_id && { color }]} numberOfLines={1}>
                        {a.nombre.split(',')[0]}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </ScrollView>

              {/* Tipo */}
              <Text style={styles.fieldLabel}>Tipo</Text>
              <View style={styles.tiposGrid}>
                {Object.entries(TIPOS_OBS).map(([key, meta]) => (
                  <TouchableOpacity
                    key={key}
                    style={[styles.tipoBtn, form.tipo === key && { borderColor: meta.color, backgroundColor: meta.color + '12' }]}
                    onPress={() => setForm(f => ({ ...f, tipo: key }))}
                  >
                    <Ionicons name={meta.icon as any} size={14} color={form.tipo === key ? meta.color : Colors.muted} />
                    <Text style={[styles.tipoBtnTxt, form.tipo === key && { color: meta.color }]}>{meta.label}</Text>
                  </TouchableOpacity>
                ))}
              </View>

              {/* Texto */}
              <Text style={styles.fieldLabel}>Observación</Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Escribe la observación..."
                placeholderTextColor={Colors.muted}
                value={form.texto}
                onChangeText={t => setForm(f => ({ ...f, texto: t }))}
                multiline numberOfLines={5}
                textAlignVertical="top"
                maxLength={1000}
              />

              {/* Privada */}
              <View style={styles.switchRow}>
                <View>
                  <Text style={styles.fieldLabel}>Observación privada</Text>
                  <Text style={styles.switchSub}>Solo visible para docentes y directivos</Text>
                </View>
                <Switch
                  value={form.privada}
                  onValueChange={v => setForm(f => ({ ...f, privada: v }))}
                  trackColor={{ false: Colors.border, true: color + '60' }}
                  thumbColor={form.privada ? color : '#fff'}
                />
              </View>

              <TouchableOpacity
                style={[styles.submitBtn, { backgroundColor: color }]}
                onPress={submit}
                disabled={mutation.isPending}
              >
                {mutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.submitBtnTxt}>Guardar Observación</Text>
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
  safe:              { flex: 1, backgroundColor: Colors.bg },
  asigBar:           { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 54 },
  asigBarContent:    { paddingHorizontal: 12, paddingVertical: 10, gap: 8, flexDirection: 'row' },
  asigChip:          { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, borderWidth: 1.5, borderColor: Colors.border },
  asigChipTxt:       { fontSize: 12, fontWeight: '600', color: Colors.muted },
  filterBar:         { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 50 },
  filterBarContent:  { paddingHorizontal: 12, paddingVertical: 8, gap: 6, flexDirection: 'row' },
  filterChip:        { paddingHorizontal: 12, paddingVertical: 5, borderRadius: 14, borderWidth: 1.5, borderColor: Colors.border },
  filterChipActive:  { borderColor: Colors.roles.docente, backgroundColor: Colors.roles.docente + '12' },
  filterTxt:         { fontSize: 12, fontWeight: '600', color: Colors.muted },
  content:           { padding: 14, gap: 10, paddingBottom: 32 },
  newBtn:            { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, borderRadius: 14, paddingVertical: 12 },
  newBtnText:        { color: '#fff', fontWeight: '700', fontSize: 14 },
  empty:             { alignItems: 'center', paddingVertical: 48, gap: 10 },
  emptyText:         { fontSize: 14, color: Colors.muted, fontWeight: '600' },
  card:              { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardAccent:        { width: 5 },
  cardBody:          { flex: 1, padding: 12, gap: 4 },
  cardTop:           { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  tipoBadge:         { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  tipoBadgeTxt:      { fontSize: 11, fontWeight: '700' },
  cardTopRight:      { flexDirection: 'row', alignItems: 'center', gap: 4 },
  cardTime:          { fontSize: 11, color: Colors.muted },
  cardEstudiante:    { fontSize: 13, fontWeight: '800', color: Colors.text },
  cardTexto:         { fontSize: 13, color: Colors.muted, lineHeight: 18 },
  modalHeader:       { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:        { fontSize: 18, fontWeight: '800', color: Colors.text },
  modalContent:      { padding: 16, gap: 14, paddingBottom: 40 },
  fieldLabel:        { fontSize: 13, fontWeight: '700', color: Colors.text },
  alumnoChip:        { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 10, paddingVertical: 6, maxWidth: 120 },
  alumnoChipTxt:     { fontSize: 12, fontWeight: '600', color: Colors.muted },
  tiposGrid:         { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tipoBtn:           { flexDirection: 'row', alignItems: 'center', gap: 6, borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 12, paddingVertical: 8 },
  tipoBtnTxt:        { fontSize: 13, fontWeight: '600', color: Colors.muted },
  input:             { backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  textarea:          { minHeight: 100, textAlignVertical: 'top' },
  switchRow:         { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#fff', borderRadius: 12, padding: 14 },
  switchSub:         { fontSize: 12, color: Colors.muted, marginTop: 2 },
  submitBtn:         { borderRadius: 14, paddingVertical: 14, alignItems: 'center' },
  submitBtnTxt:      { color: '#fff', fontWeight: '800', fontSize: 15 },
})
