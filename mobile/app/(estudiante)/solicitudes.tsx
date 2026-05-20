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

const ACCENT     = Colors.roles.estudiante
const EMPTY_FORM = { tipo: '', asunto: '', descripcion: '', fecha_evento: '' }

function tipoIcon(tipo: string): string {
  const map: Record<string, string> = {
    justificacion_ausencia: 'calendar-outline',
    constancia_estudios:    'school-outline',
    certificado_notas:      'ribbon-outline',
    solicitar_beca:         'cash-outline',
    cambio_datos:           'create-outline',
    otro:                   'ellipsis-horizontal-circle-outline',
  }
  return map[tipo] ?? 'document-outline'
}

export default function SolicitudesEstudiante() {
  const qc = useQueryClient()
  const [createVisible, setCreateVisible] = useState(false)
  const [detailId,      setDetailId]      = useState<number | null>(null)
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

  const tipos:      Record<string, string> = data?.tipos       ?? {}
  const solicitudes: any[]                 = data?.solicitudes ?? []
  const stats:       any                   = data?.stats        ?? {}
  const detail                             = detailData?.solicitud

  const submit = () => {
    if (!form.tipo)               return Alert.alert('Atención', 'Selecciona un tipo de solicitud.')
    if (!form.asunto.trim())      return Alert.alert('Atención', 'Escribe un asunto.')
    if (!form.descripcion.trim()) return Alert.alert('Atención', 'Escribe una descripción.')
    mutation.mutate({
      tipo:         form.tipo,
      asunto:       form.asunto.trim(),
      descripcion:  form.descripcion.trim(),
      fecha_evento: form.fecha_evento || undefined,
    })
  }

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={ACCENT} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.pageTitle}>Mis Solicitudes</Text>

        {/* Stats */}
        <View style={styles.statsRow}>
          <StatCard value={stats.pendientes ?? 0} label="Pendientes" color={Colors.amber} />
          <StatCard value={stats.en_proceso  ?? 0} label="En proceso"  color={Colors.blue}  />
          <StatCard value={stats.aprobadas   ?? 0} label="Aprobadas"   color={Colors.green} />
          <StatCard value={stats.total       ?? 0} label="Total"       color={Colors.text}  />
        </View>

        {/* Botón nueva */}
        <TouchableOpacity style={[styles.newBtn, { backgroundColor: ACCENT }]} onPress={() => setCreateVisible(true)}>
          <Ionicons name="add-circle" size={18} color="#fff" />
          <Text style={styles.newBtnText}>Nueva Solicitud</Text>
        </TouchableOpacity>

        {/* Lista */}
        {solicitudes.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="document-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>Aún no tienes solicitudes</Text>
            <Text style={styles.emptySub}>Usa el botón de arriba para crear una</Text>
          </View>
        ) : solicitudes.map((sol: any) => (
          <TouchableOpacity key={sol.id} style={styles.card} onPress={() => setDetailId(sol.id)} activeOpacity={0.85}>
            <View style={styles.cardTop}>
              <View style={[styles.iconBox, { backgroundColor: ACCENT + '14' }]}>
                <Ionicons name={tipoIcon(sol.tipo) as any} size={18} color={ACCENT} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.cardAsunto} numberOfLines={1}>{sol.asunto}</Text>
                <Text style={styles.cardTipo}>{sol.tipo_label}</Text>
              </View>
              <View style={[styles.estadoBadge, { backgroundColor: sol.estado_color + '20' }]}>
                <Text style={[styles.estadoText, { color: sol.estado_color }]}>{sol.estado_label}</Text>
              </View>
            </View>

            <View style={styles.cardMeta}>
              {sol.fecha_evento && (
                <View style={styles.metaChip}>
                  <Ionicons name="calendar-outline" size={12} color={Colors.muted} />
                  <Text style={styles.metaText}>{sol.fecha_evento}</Text>
                </View>
              )}
              <View style={styles.metaChip}>
                <Ionicons name="time-outline" size={12} color={Colors.muted} />
                <Text style={styles.metaText}>{sol.creado_hace}</Text>
              </View>
              {sol.respuesta && (
                <View style={[styles.metaChip, styles.respondidaChip]}>
                  <Ionicons name="chatbubble-outline" size={11} color={Colors.green} />
                  <Text style={[styles.metaText, { color: Colors.green }]}>Respondida</Text>
                </View>
              )}
            </View>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {/* ── Modal crear solicitud ─────────────────────────────────────────── */}
      <Modal
        visible={createVisible}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => { setCreateVisible(false); setForm(EMPTY_FORM) }}
      >
        <SafeAreaView style={styles.safe}>
          <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
            <View style={styles.modalHeader}>
              <TouchableOpacity onPress={() => { setCreateVisible(false); setForm(EMPTY_FORM) }} style={{ padding: 4 }}>
                <Ionicons name="close" size={22} color={Colors.text} />
              </TouchableOpacity>
              <Text style={styles.modalTitle}>Nueva Solicitud</Text>
              <TouchableOpacity
                onPress={submit}
                disabled={mutation.isPending}
                style={[styles.modalSaveBtn, { backgroundColor: ACCENT }]}
              >
                {mutation.isPending
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Text style={styles.modalSaveTxt}>Enviar</Text>
                }
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={styles.modalContent} keyboardShouldPersistTaps="handled">

              {/* Tipo */}
              <View>
                <Text style={styles.fieldLabel}>Tipo de solicitud</Text>
                <View style={styles.tiposGrid}>
                  {Object.entries(tipos).map(([key, label]: [string, any]) => (
                    <TouchableOpacity
                      key={key}
                      style={[styles.tipoBtn, form.tipo === key && { borderColor: ACCENT, backgroundColor: ACCENT + '10' }]}
                      onPress={() => setForm(f => ({ ...f, tipo: key }))}
                    >
                      <Ionicons
                        name={tipoIcon(key) as any}
                        size={13}
                        color={form.tipo === key ? ACCENT : Colors.muted}
                        style={{ marginRight: 4 }}
                      />
                      <Text style={[styles.tipoBtnText, form.tipo === key && { color: ACCENT }]}>{label}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              {/* Asunto */}
              <View>
                <Text style={styles.fieldLabel}>Asunto</Text>
                <TextInput
                  style={styles.input}
                  placeholder="Escribe el asunto..."
                  placeholderTextColor={Colors.muted}
                  value={form.asunto}
                  onChangeText={t => setForm(f => ({ ...f, asunto: t }))}
                  maxLength={200}
                />
              </View>

              {/* Descripción */}
              <View>
                <Text style={styles.fieldLabel}>Descripción</Text>
                <TextInput
                  style={[styles.input, styles.textarea]}
                  placeholder="Describe tu solicitud detalladamente..."
                  placeholderTextColor={Colors.muted}
                  value={form.descripcion}
                  onChangeText={t => setForm(f => ({ ...f, descripcion: t }))}
                  multiline
                  textAlignVertical="top"
                  maxLength={2000}
                />
              </View>

              {/* Fecha del evento */}
              <View>
                <Text style={styles.fieldLabel}>
                  Fecha del evento <Text style={styles.optional}>(opcional)</Text>
                </Text>
                <TextInput
                  style={styles.input}
                  placeholder="AAAA-MM-DD"
                  placeholderTextColor={Colors.muted}
                  value={form.fecha_evento}
                  onChangeText={t => setForm(f => ({ ...f, fecha_evento: t }))}
                />
              </View>

            </ScrollView>
          </KeyboardAvoidingView>
        </SafeAreaView>
      </Modal>

      {/* ── Modal detalle ─────────────────────────────────────────────────── */}
      <Modal
        visible={!!detailId}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setDetailId(null)}
      >
        <SafeAreaView style={styles.safe}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setDetailId(null)} style={{ padding: 4 }}>
              <Ionicons name="close" size={22} color={Colors.text} />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Detalle</Text>
            <View style={{ width: 60 }} />
          </View>

          {detailLoading || !detail ? (
            <ActivityIndicator style={{ marginTop: 40 }} color={ACCENT} />
          ) : (
            <ScrollView contentContainerStyle={styles.modalContent}>

              {/* Encabezado */}
              <View style={styles.detailHeader}>
                <View style={[styles.detailIconBox, { backgroundColor: ACCENT + '14' }]}>
                  <Ionicons name={tipoIcon(detail.tipo) as any} size={28} color={ACCENT} />
                </View>
                <View style={{ flex: 1, gap: 4 }}>
                  <View style={[styles.estadoBadge, { alignSelf: 'flex-start', backgroundColor: detail.estado_color + '20' }]}>
                    <Text style={[styles.estadoText, { color: detail.estado_color }]}>{detail.estado_label}</Text>
                  </View>
                  <Text style={styles.detailAsunto}>{detail.asunto}</Text>
                  <Text style={styles.detailTipo}>{detail.tipo_label}</Text>
                </View>
              </View>

              {/* Meta */}
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

              {/* Descripción */}
              <Text style={styles.fieldLabel}>Descripción</Text>
              <Text style={styles.detailBody}>{detail.descripcion}</Text>

              {/* Respuesta */}
              {detail.respuesta ? (
                <>
                  <View style={styles.divider} />
                  <Text style={styles.fieldLabel}>Respuesta de la institución</Text>
                  <View style={[styles.respuestaBox, { borderLeftColor: detail.estado_color }]}>
                    <Text style={styles.detailBody}>{detail.respuesta}</Text>
                    {detail.respondido_en && (
                      <Text style={[styles.metaText, { marginTop: 8 }]}>
                        Respondido: {new Date(detail.respondido_en).toLocaleDateString('es-DO', {
                          day: '2-digit', month: 'long', year: 'numeric',
                        })}
                      </Text>
                    )}
                  </View>
                </>
              ) : (
                <View style={styles.pendienteBox}>
                  <Ionicons name="hourglass-outline" size={20} color={Colors.amber} />
                  <Text style={[styles.metaText, { color: Colors.amber }]}>
                    En espera de respuesta de la institución
                  </Text>
                </View>
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
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 12, paddingBottom: 40 },
  pageTitle:      { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },

  statsRow:       { flexDirection: 'row', gap: 8 },
  statCard:       { flex: 1, backgroundColor: '#fff', borderRadius: 14, padding: 12, alignItems: 'center',
                    shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  statVal:        { fontSize: 22, fontWeight: '900' },
  statLbl:        { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },

  newBtn:         { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8,
                    borderRadius: 14, paddingVertical: 13 },
  newBtnText:     { color: '#fff', fontWeight: '700', fontSize: 15 },

  empty:          { alignItems: 'center', paddingVertical: 48, gap: 8 },
  emptyText:      { fontSize: 16, color: Colors.muted, fontWeight: '700' },
  emptySub:       { fontSize: 13, color: Colors.muted },

  card:           { backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 10,
                    shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardTop:        { flexDirection: 'row', alignItems: 'center', gap: 10 },
  iconBox:        { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  cardAsunto:     { fontSize: 14, fontWeight: '700', color: Colors.text },
  cardTipo:       { fontSize: 11, color: Colors.muted },
  estadoBadge:    { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  estadoText:     { fontSize: 11, fontWeight: '700' },
  cardMeta:       { flexDirection: 'row', alignItems: 'center', gap: 10, flexWrap: 'wrap' },
  metaChip:       { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaText:       { fontSize: 12, color: Colors.muted },
  respondidaChip: { backgroundColor: Colors.green + '14', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },

  modalHeader:    { flexDirection: 'row', alignItems: 'center', gap: 12,
                    padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: '#fff' },
  modalTitle:     { flex: 1, fontSize: 17, fontWeight: '800', color: Colors.text },
  modalSaveBtn:   { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 10 },
  modalSaveTxt:   { color: '#fff', fontWeight: '700', fontSize: 14 },
  modalContent:   { padding: 16, gap: 16, paddingBottom: 40 },

  fieldLabel:     { fontSize: 13, fontWeight: '700', color: Colors.text, marginBottom: 6 },
  optional:       { fontSize: 12, color: Colors.muted, fontWeight: '400' },
  input:          { backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12,
                    paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  textarea:       { minHeight: 110, textAlignVertical: 'top' },

  tiposGrid:      { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tipoBtn:        { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border,
                    borderRadius: 10, paddingHorizontal: 10, paddingVertical: 7 },
  tipoBtnText:    { fontSize: 12, color: Colors.muted, fontWeight: '600' },

  detailHeader:   { flexDirection: 'row', gap: 14, alignItems: 'flex-start' },
  detailIconBox:  { width: 56, height: 56, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  detailAsunto:   { fontSize: 18, fontWeight: '900', color: Colors.text },
  detailTipo:     { fontSize: 13, color: Colors.muted },
  metaRow:        { flexDirection: 'row', gap: 10, flexWrap: 'wrap' },
  divider:        { height: 1, backgroundColor: Colors.border },
  detailBody:     { fontSize: 14, color: Colors.text, lineHeight: 22 },
  respuestaBox:   { borderLeftWidth: 3, borderRadius: 10, backgroundColor: Colors.green + '08',
                    padding: 14, gap: 4 },
  pendienteBox:   { flexDirection: 'row', alignItems: 'center', gap: 8,
                    backgroundColor: Colors.amber + '12', borderRadius: 10, padding: 12 },
})
