import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  RefreshControl, Modal, TextInput, Alert, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi, gamificacionApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

const CAT_OPTIONS = [
  { value: 'academico',     label: 'Académico',     color: Colors.blue  },
  { value: 'asistencia',    label: 'Asistencia',    color: Colors.green },
  { value: 'conducta',      label: 'Conducta',      color: '#8b5cf6'    },
  { value: 'participacion', label: 'Participación', color: '#f59e0b'    },
  { value: 'extra',         label: 'Extra',         color: Colors.muted },
]

const MEDALLA = (pos: number) =>
  pos === 1 ? '🥇' : pos === 2 ? '🥈' : pos === 3 ? '🥉' : `${pos}.`

export default function GamificacionDocente() {
  const queryClient = useQueryClient()
  const [asignacionIdx, setAsignacionIdx] = useState(0)
  const [modalVisible, setModalVisible] = useState(false)
  const [selectedMatricula, setSelectedMatricula] = useState<{ id: number; nombre: string } | null>(null)
  const [concepto, setConcepto] = useState('')
  const [categoria, setCategoria] = useState('academico')
  const [puntos, setPuntos] = useState('10')
  const [fecha, setFecha] = useState(new Date().toISOString().slice(0, 10))

  const { data: gruposData, isLoading: loadingGrupos } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const grupos: any[] = gruposData?.data ?? gruposData?.asignaciones ?? []
  const asignacionActual = grupos[asignacionIdx] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-gamif', asignacionActual?.id],
    queryFn:  () => gamificacionApi.grupo(asignacionActual.id).then(r => r.data),
    enabled:  !!asignacionActual?.id,
  })

  const ranking: any[]    = data?.ranking ?? []
  const totalPuntos       = data?.totalPuntos ?? 0
  const totalInsignias    = data?.totalInsignias ?? 0
  const totalGrupo        = data?.totalGrupo ?? 0
  const asignacionInfo    = data?.asignacion ?? null

  const mutation = useMutation({
    mutationFn: (payload: any) =>
      gamificacionApi.asignar(asignacionActual.id, payload).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['docente-gamif', asignacionActual?.id] })
      setModalVisible(false)
      setConcepto('')
      setPuntos('10')
      Alert.alert('✓ Guardado', 'Puntos asignados correctamente.')
    },
    onError: () => Alert.alert('Error', 'No se pudo asignar los puntos.'),
  })

  const abrirModal = (mat?: any) => {
    if (mat) setSelectedMatricula({ id: mat.matricula_id, nombre: mat.nombre })
    setModalVisible(true)
  }

  const guardar = () => {
    if (!selectedMatricula) return Alert.alert('Selecciona un estudiante')
    if (!concepto.trim()) return Alert.alert('Ingresa un concepto')
    const pts = parseInt(puntos, 10)
    if (isNaN(pts) || pts < 1) return Alert.alert('Puntos inválidos')

    mutation.mutate({
      matricula_id: selectedMatricula.id,
      concepto: concepto.trim(),
      categoria,
      puntos: pts,
      fecha,
    })
  }

  if (loadingGrupos) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <ActivityIndicator color={ACCENT} style={{ marginTop: 60 }} />
      </SafeAreaView>
    )
  }

  if (grupos.length === 0) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.empty}>
          <Ionicons name="people-outline" size={48} color={Colors.muted} />
          <Text style={styles.emptyTxt}>Sin grupos asignados.</Text>
        </View>
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* Banner */}
        <View style={[styles.banner, { backgroundColor: ACCENT }]}>
          <Ionicons name="trophy" size={26} color="#fff" style={{ opacity: 0.9 }} />
          <View style={{ flex: 1 }}>
            <Text style={styles.bannerTitle}>Gamificación</Text>
            <Text style={styles.bannerSub}>Ranking y puntos de tus grupos</Text>
          </View>
          <TouchableOpacity
            onPress={() => { setSelectedMatricula(null); setModalVisible(true) }}
            style={styles.addBtn}
          >
            <Ionicons name="add-circle" size={22} color="#fff" />
          </TouchableOpacity>
        </View>

        {/* Selector de grupo */}
        {grupos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 4 }}>
            <View style={{ flexDirection: 'row', gap: 8, paddingVertical: 4 }}>
              {grupos.map((g: any, i: number) => (
                <TouchableOpacity
                  key={g.id ?? i}
                  onPress={() => setAsignacionIdx(i)}
                  style={[styles.pill, i === asignacionIdx && { backgroundColor: ACCENT, borderColor: ACCENT }]}
                >
                  <Text style={[styles.pillTxt, i === asignacionIdx && { color: '#fff' }]} numberOfLines={1}>
                    {g.grado ? `${g.grado} ${g.seccion} — ` : ''}{g.asignatura}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </ScrollView>
        )}

        {/* Stats del grupo */}
        {asignacionInfo && (
          <View style={styles.statsRow}>
            <View style={[styles.statCard, { backgroundColor: '#eef2ff' }]}>
              <Text style={[styles.statVal, { color: '#4338ca' }]}>{totalGrupo}</Text>
              <Text style={styles.statLbl}>Estudiantes</Text>
            </View>
            <View style={[styles.statCard, { backgroundColor: '#dcfce7' }]}>
              <Text style={[styles.statVal, { color: '#15803d' }]}>{totalPuntos}</Text>
              <Text style={styles.statLbl}>Pts Totales</Text>
            </View>
            <View style={[styles.statCard, { backgroundColor: '#fef9c3' }]}>
              <Text style={[styles.statVal, { color: '#92400e' }]}>{totalInsignias}</Text>
              <Text style={styles.statLbl}>Insignias</Text>
            </View>
          </View>
        )}

        {/* Ranking */}
        {isLoading ? (
          <ActivityIndicator color={ACCENT} style={{ marginTop: 24 }} />
        ) : (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>
              Ranking — {asignacionInfo?.grupo ?? (asignacionActual ? `${asignacionActual.grado} ${asignacionActual.seccion}` : '')}
            </Text>
            {ranking.length === 0 ? (
              <Text style={styles.emptyTxt}>Sin datos aún.</Text>
            ) : (
              ranking.map((item: any) => (
                <View key={item.matricula_id} style={styles.rankRow}>
                  <Text style={styles.rankMedalla}>{MEDALLA(item.posicion)}</Text>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.rankNombre} numberOfLines={1}>{item.nombre}</Text>
                    {item.insignias > 0 && (
                      <Text style={styles.rankInsignia}>⭐ {item.insignias} insignia(s)</Text>
                    )}
                  </View>
                  <View style={{ alignItems: 'flex-end', gap: 4 }}>
                    <Text style={styles.rankPts}>{item.puntos} pts</Text>
                    <TouchableOpacity
                      onPress={() => abrirModal(item)}
                      style={styles.asgBtn}
                    >
                      <Ionicons name="add" size={12} color="#4338ca" />
                      <Text style={styles.asgBtnTxt}>Asignar</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ))
            )}
          </View>
        )}
      </ScrollView>

      {/* Modal asignar puntos */}
      <Modal visible={modalVisible} transparent animationType="slide" onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>
                <Ionicons name="trophy" size={16} color="#f59e0b" /> Asignar Puntos
              </Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <Ionicons name="close" size={22} color={Colors.muted} />
              </TouchableOpacity>
            </View>

            {/* Estudiante */}
            <Text style={styles.fieldLabel}>Estudiante</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 10 }}>
              <View style={{ flexDirection: 'row', gap: 6 }}>
                {ranking.map((item: any) => (
                  <TouchableOpacity
                    key={item.matricula_id}
                    onPress={() => setSelectedMatricula({ id: item.matricula_id, nombre: item.nombre })}
                    style={[
                      styles.pill,
                      selectedMatricula?.id === item.matricula_id && { backgroundColor: ACCENT, borderColor: ACCENT },
                    ]}
                  >
                    <Text
                      style={[styles.pillTxt, selectedMatricula?.id === item.matricula_id && { color: '#fff' }]}
                      numberOfLines={1}
                    >
                      {item.nombre.split(',')[0]}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </ScrollView>
            {selectedMatricula && (
              <Text style={{ fontSize: 11, color: Colors.muted, marginBottom: 8 }}>
                Seleccionado: {selectedMatricula.nombre}
              </Text>
            )}

            {/* Concepto */}
            <Text style={styles.fieldLabel}>Concepto</Text>
            <TextInput
              style={styles.input}
              value={concepto}
              onChangeText={setConcepto}
              placeholder="Ej: Participación destacada"
              placeholderTextColor={Colors.muted}
            />

            {/* Categoría */}
            <Text style={styles.fieldLabel}>Categoría</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 10 }}>
              <View style={{ flexDirection: 'row', gap: 6 }}>
                {CAT_OPTIONS.map(c => (
                  <TouchableOpacity
                    key={c.value}
                    onPress={() => setCategoria(c.value)}
                    style={[
                      styles.pill,
                      categoria === c.value && { backgroundColor: c.color, borderColor: c.color },
                    ]}
                  >
                    <Text style={[styles.pillTxt, categoria === c.value && { color: '#fff' }]}>{c.label}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </ScrollView>

            {/* Puntos y fecha */}
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <View style={{ flex: 1 }}>
                <Text style={styles.fieldLabel}>Puntos</Text>
                <TextInput
                  style={styles.input}
                  value={puntos}
                  onChangeText={setPuntos}
                  keyboardType="numeric"
                  placeholder="10"
                  placeholderTextColor={Colors.muted}
                />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.fieldLabel}>Fecha</Text>
                <TextInput
                  style={styles.input}
                  value={fecha}
                  onChangeText={setFecha}
                  placeholder="YYYY-MM-DD"
                  placeholderTextColor={Colors.muted}
                />
              </View>
            </View>

            <TouchableOpacity
              style={[styles.saveBtn, mutation.isPending && { opacity: 0.6 }]}
              onPress={guardar}
              disabled={mutation.isPending}
            >
              {mutation.isPending ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.saveBtnTxt}>Guardar Puntos</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, gap: 14, paddingBottom: 32 },
  banner:       { flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 16, padding: 16 },
  bannerTitle:  { fontSize: 18, fontWeight: '900', color: '#fff' },
  bannerSub:    { fontSize: 12, color: 'rgba(255,255,255,.75)', marginTop: 2 },
  addBtn:       { padding: 4 },
  pill:         { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 99, backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border },
  pillTxt:      { fontSize: 12, fontWeight: '700', color: Colors.text },
  statsRow:     { flexDirection: 'row', gap: 10 },
  statCard:     { flex: 1, borderRadius: 14, padding: 14, alignItems: 'center' },
  statVal:      { fontSize: 20, fontWeight: '900', lineHeight: 24 },
  statLbl:      { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2 },
  section:      { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 8, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  sectionTitle: { fontSize: 14, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  rankRow:      { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: Colors.border },
  rankMedalla:  { width: 26, fontSize: 14, textAlign: 'center' },
  rankNombre:   { fontSize: 13, fontWeight: '700', color: Colors.text },
  rankInsignia: { fontSize: 10, color: Colors.muted, marginTop: 1 },
  rankPts:      { fontSize: 13, fontWeight: '900', color: '#4338ca' },
  asgBtn:       { flexDirection: 'row', alignItems: 'center', gap: 2, backgroundColor: '#eef2ff', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 3 },
  asgBtnTxt:    { fontSize: 10, fontWeight: '700', color: '#4338ca' },
  empty:        { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12 },
  emptyTxt:     { color: Colors.muted, fontSize: 13, textAlign: 'center' },
  // Modal
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,.45)', justifyContent: 'flex-end' },
  modalBox:     { backgroundColor: '#fff', borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: 20, paddingBottom: 32 },
  modalHeader:  { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 },
  modalTitle:   { fontSize: 16, fontWeight: '800', color: Colors.text },
  fieldLabel:   { fontSize: 12, fontWeight: '700', color: Colors.text, marginBottom: 5 },
  input:        { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, padding: 10, fontSize: 14, color: Colors.text, marginBottom: 10 },
  saveBtn:      { backgroundColor: '#6366f1', borderRadius: 12, padding: 14, alignItems: 'center', marginTop: 4 },
  saveBtnTxt:   { color: '#fff', fontWeight: '800', fontSize: 15 },
})
