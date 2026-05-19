import React, { useState } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet, Modal,
  TextInput, KeyboardAvoidingView, Platform, ActivityIndicator,
  RefreshControl, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { solicitudesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const EMPTY_FORM = { tipo: '', asunto: '', descripcion: '', fecha_evento: '' }

export default function SolicitudesEstudiante() {
  const qc = useQueryClient()
  const [createVisible, setCreateVisible] = useState(false)
  const [detailId, setDetailId]           = useState<number | null>(null)
  const [form, setForm]                   = useState(EMPTY_FORM)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['solicitudes-estudiante'],
    queryFn:  () => solicitudesApi.index().then(r => r.data),
  })

  const { data: detailData, isLoading: detailLoading } = useQuery({
    queryKey: ['solicitud-est', detailId],
    queryFn:  () => solicitudesApi.show(detailId!).then(r => r.data),
    enabled:  !!detailId,
  })

  const mutation = useMutation({
    mutationFn: (payload: any) => solicitudesApi.store(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['solicitudes-estudiante'] })
      setCreateVisible(false)
      setForm(EMPTY_FORM)
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message ?? 'Error al enviar la solicitud.'
      Alert.alert('Error', msg)
    },
  })

  const tipos      = data?.tipos ?? {}
  const solicitudes = data?.solicitudes ?? []
  const stats      = data?.stats ?? {}
  const detail     = detailData?.solicitud

  const submit = () => {
    if (!form.tipo)             return Alert.alert('Atención', 'Selecciona un tipo de solicitud.')
    if (!form.asunto.trim())    return Alert.alert('Atención', 'Escribe un asunto.')
    if (!form.descripcion.trim()) return Alert.alert('Atención', 'Escribe una descripción.')
    mutation.mutate({
      tipo:        form.tipo,
      asunto:      form.asunto,
      descripcion: form.descripcion,
      fecha_evento: form.fecha_evento || undefined,
    })
  }

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={Colors.blue} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {/* Stats */}
        <View style={styles.statsRow}>
          <StatCard value={stats.pendientes ?? 0}  label="Pendientes" color={Colors.amber} />
          <StatCard value={stats.en_proceso ?? 0}  label="En proceso"  color={Colors.blue}  />
          <StatCard value={stats.aprobadas ?? 0}   label="Aprobadas"   color={Colors.green} />
          <StatCard value={stats.total ?? 0}        label="Total"       color={Colors.text}  />
        </View>

        <TouchableOpacity style={styles.newBtn} onPress={() => setCreateVisible(true)}>
          <Ionicons name="add-circle" size={18} color="#fff" />
          <Text style={styles.newBtnText}>Nueva Solicitud</Text>
        </TouchableOpacity>

        {solicitudes.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="document-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>Aún no tienes solicitudes</Text>
          </View>
        ) : solicitudes.map((sol: any) => (
          <TouchableOpacity key={sol.id} style={styles.card} onPress={() => setDetailId(sol.id)}>
            <View style={styles.cardTop}>
              <View style={[styles.badge, { backgroundColor: sol.estado_color + '22' }]}>
                <Text style={[styles.badgeText, { color: sol.estado_color }]}>{sol.estado_label}</Text>
              </View>
              <Text style={styles.cardDate}>{sol.creado_hace}</Text>
            </View>
            <Text style={styles.cardAsunto} numberOfLines={1}>{sol.asunto}</Text>
            <Text style={styles.cardTipo}>{sol.tipo_label}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* ── Create modal ── */}
      <Modal visible={createVisible} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Nueva Solicitud</Text>
              <TouchableOpacity onPress={() => { setCreateVisible(false); setForm(EMPTY_FORM) }}>
                <Ionicons name="close" size={24} color={Colors.text} />
              </TouchableOpacity>
            </View>
            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">
              <Text style={styles.fieldLabel}>Tipo de solicitud</Text>
              <View style={styles.tiposGrid}>
                {Object.entries(tipos).map(([key, label]: [string, any]) => (
                  <TouchableOpacity
                    key={key}
                    style={[styles.tipoBtn, form.tipo === key && styles.tipoBtnActive]}
                    onPress={() => setForm(f => ({ ...f, tipo: key }))}
                  >
                    <Text style={[styles.tipoBtnText, form.tipo === key && styles.tipoBtnTextActive]}>
                      {label}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              <Text style={styles.fieldLabel}>Asunto</Text>
              <TextInput
                style={styles.input}
                placeholder="Escribe el asunto..."
                placeholderTextColor={Colors.muted}
                value={form.asunto}
                onChangeText={t => setForm(f => ({ ...f, asunto: t }))}
                maxLength={200}
              />

              <Text style={styles.fieldLabel}>Descripción</Text>
              <TextInput
                style={[styles.input, styles.textarea]}
                placeholder="Describe tu solicitud detalladamente..."
                placeholderTextColor={Colors.muted}
                value={form.descripcion}
                onChangeText={t => setForm(f => ({ ...f, descripcion: t }))}
                multiline
                numberOfLines={5}
                textAlignVertical="top"
                maxLength={2000}
              />

              <Text style={styles.fieldLabel}>Fecha del evento <Text style={styles.optional}>(opcional)</Text></Text>
              <TextInput
                style={styles.input}
                placeholder="AAAA-MM-DD"
                placeholderTextColor={Colors.muted}
                value={form.fecha_evento}
                onChangeText={t => setForm(f => ({ ...f, fecha_evento: t }))}
              />

              <TouchableOpacity style={styles.submitBtn} onPress={submit} disabled={mutation.isPending}>
                {mutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.submitBtnText}>Enviar Solicitud</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </Modal>

      {/* ── Detail modal ── */}
      <Modal visible={!!detailId} animationType="slide" presentationStyle="pageSheet">
        <SafeAreaView style={styles.safe}>
          <View style={styles.modalHeader}>
            <Text style={styles.modalTitle}>Detalle</Text>
            <TouchableOpacity onPress={() => setDetailId(null)}>
              <Ionicons name="close" size={24} color={Colors.text} />
            </TouchableOpacity>
          </View>
          {detailLoading || !detail ? (
            <ActivityIndicator style={{ marginTop: 40 }} color={Colors.blue} />
          ) : (
            <ScrollView contentContainerStyle={styles.modalContent}>
              <View style={[styles.badge, { backgroundColor: detail.estado_color + '22', alignSelf: 'flex-start' }]}>
                <Text style={[styles.badgeText, { color: detail.estado_color }]}>{detail.estado_label}</Text>
              </View>
              <Text style={styles.detailAsunto}>{detail.asunto}</Text>
              <Text style={styles.detailTipo}>{detail.tipo_label}</Text>
              <View style={styles.metaRow}>
                {detail.fecha_evento && (
                  <View style={styles.metaChip}>
                    <Ionicons name="calendar-outline" size={13} color={Colors.muted} />
                    <Text style={styles.metaText}>{detail.fecha_evento}</Text>
                  </View>
                )}
                <View style={styles.metaChip}>
                  <Ionicons name="time-outline" size={13} color={Colors.muted} />
                  <Text style={styles.metaText}>{detail.creado_hace}</Text>
                </View>
              </View>
              <View style={styles.divider} />
              <Text style={styles.fieldLabel}>Descripción</Text>
              <Text style={styles.detailBody}>{detail.descripcion}</Text>
              {detail.respuesta && (
                <>
                  <View style={styles.divider} />
                  <Text style={styles.fieldLabel}>Respuesta de la institución</Text>
                  <View style={styles.respuestaBox}>
                    <Text style={styles.detailBody}>{detail.respuesta}</Text>
                    {detail.respondido_en && (
                      <Text style={[styles.metaText, { marginTop: 6 }]}>{detail.respondido_en}</Text>
                    )}
                  </View>
                </>
              )}
            </ScrollView>
          )}
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  )
}

function StatCard({ value, label, color }: { value: number; label: string; color: string }) {
  return (
    <View style={styles.statCard}>
      <Text style={[styles.statVal, { color }]}>{value}</Text>
      <Text style={styles.statLbl}>{label}</Text>
    </View>
  )
}

const styles = StyleSheet.create({
  safe:             { flex: 1, backgroundColor: Colors.bg },
  content:          { padding: 16, gap: 12, paddingBottom: 32 },
  statsRow:         { flexDirection: 'row', gap: 8 },
  statCard:         { flex: 1, backgroundColor: '#fff', borderRadius: 14, padding: 12, alignItems: 'center', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  statVal:          { fontSize: 22, fontWeight: '900' },
  statLbl:          { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },
  newBtn:           { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, backgroundColor: Colors.blue, borderRadius: 14, paddingVertical: 13 },
  newBtnText:       { color: '#fff', fontWeight: '700', fontSize: 15 },
  empty:            { alignItems: 'center', paddingVertical: 48, gap: 12 },
  emptyText:        { fontSize: 15, color: Colors.muted, fontWeight: '600' },
  card:             { backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 4, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardTop:          { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4 },
  badge:            { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  badgeText:        { fontSize: 11, fontWeight: '700' },
  cardDate:         { fontSize: 11, color: Colors.muted },
  cardAsunto:       { fontSize: 15, fontWeight: '700', color: Colors.text },
  cardTipo:         { fontSize: 12, color: Colors.muted },
  modalHeader:      { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:       { fontSize: 18, fontWeight: '800', color: Colors.text },
  modalContent:     { padding: 16, gap: 14, paddingBottom: 40 },
  fieldLabel:       { fontSize: 13, fontWeight: '700', color: Colors.text },
  optional:         { fontSize: 12, color: Colors.muted, fontWeight: '400' },
  tiposGrid:        { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tipoBtn:          { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 10, paddingHorizontal: 12, paddingVertical: 7 },
  tipoBtnActive:    { borderColor: Colors.blue, backgroundColor: Colors.blue + '12' },
  tipoBtnText:      { fontSize: 13, color: Colors.muted, fontWeight: '600' },
  tipoBtnTextActive:{ color: Colors.blue },
  input:            { backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  textarea:         { minHeight: 110, textAlignVertical: 'top' },
  submitBtn:        { backgroundColor: Colors.blue, borderRadius: 14, paddingVertical: 14, alignItems: 'center', marginTop: 8 },
  submitBtnText:    { color: '#fff', fontWeight: '800', fontSize: 16 },
  detailAsunto:     { fontSize: 20, fontWeight: '900', color: Colors.text },
  detailTipo:       { fontSize: 13, color: Colors.muted },
  metaRow:          { flexDirection: 'row', gap: 10, flexWrap: 'wrap', marginTop: 4 },
  metaChip:         { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaText:         { fontSize: 12, color: Colors.muted },
  divider:          { height: 1, backgroundColor: Colors.border },
  detailBody:       { fontSize: 14, color: Colors.text, lineHeight: 21 },
  respuestaBox:     { backgroundColor: Colors.green + '10', borderRadius: 12, padding: 12 },
})
