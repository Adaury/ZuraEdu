import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  RefreshControl, TouchableOpacity, TextInput, Alert, Modal,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { encuestasApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = '#8b5cf6'

export default function EncuestasEstudiante() {
  const qc = useQueryClient()
  const [modalEncuesta, setModalEncuesta] = useState<any>(null)
  const [respuestas, setRespuestas] = useState<Record<number, any>>({})

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['encuestas-estudiante'],
    queryFn:  () => encuestasApi.index().then(r => r.data),
  })

  const { data: detalle, isFetching: cargandoDetalle } = useQuery({
    queryKey: ['encuesta-detalle', modalEncuesta?.id],
    queryFn:  () => encuestasApi.show(modalEncuesta!.id).then(r => r.data.encuesta),
    enabled:  !!modalEncuesta && !modalEncuesta.ya_respondio,
  })

  const enviar = useMutation({
    mutationFn: ({ id, r }: { id: number; r: any }) => encuestasApi.responder(id, r),
    onSuccess: () => {
      Alert.alert('¡Gracias!', 'Tus respuestas han sido registradas.')
      setModalEncuesta(null)
      setRespuestas({})
      qc.invalidateQueries({ queryKey: ['encuestas-estudiante'] })
    },
    onError: () => Alert.alert('Error', 'No se pudo enviar la respuesta.'),
  })

  const encuestas: any[] = data?.encuestas ?? []

  const abrirEncuesta = (e: any) => {
    setRespuestas({})
    setModalEncuesta(e)
  }

  const cerrarModal = () => {
    setModalEncuesta(null)
    setRespuestas({})
  }

  const handleEnviar = () => {
    if (!detalle) return
    const faltantes = detalle.preguntas.filter((p: any) => {
      const r = respuestas[p.id]
      if (!r) return true
      if (p.tipo === 'opcion_multiple' && !r.opcion_id) return true
      if (p.tipo === 'escala_1_5' && !r.escala_valor) return true
      if (p.tipo === 'texto_libre' && !r.respuesta_texto?.trim()) return true
      return false
    })
    if (faltantes.length > 0) {
      Alert.alert('Respuestas incompletas', `Debes responder todas las preguntas (${faltantes.length} pendiente${faltantes.length > 1 ? 's' : ''}).`)
      return
    }
    enviar.mutate({ id: detalle.id, r: respuestas })
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Encuestas de Satisfacción</Text>
        <Text style={styles.sub}>{encuestas.length} disponible{encuestas.length !== 1 ? 's' : ''}</Text>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 30 }} />}

        {encuestas.map((e: any) => (
          <View key={e.id} style={[styles.card, { borderLeftColor: e.ya_respondio ? Colors.green : ACCENT }]}>
            <View style={{ flex: 1 }}>
              <Text style={styles.cardTitle}>{e.titulo}</Text>
              {!!e.descripcion && <Text style={styles.cardDesc}>{e.descripcion}</Text>}
              <View style={styles.chipRow}>
                <View style={[styles.chip, { backgroundColor: '#f3e8ff' }]}>
                  <Text style={[styles.chipText, { color: '#7c3aed' }]}>{e.preguntas_count} pregunta{e.preguntas_count !== 1 ? 's' : ''}</Text>
                </View>
                {!!e.fecha_cierre && (
                  <View style={[styles.chip, { backgroundColor: '#fef3c7' }]}>
                    <Text style={[styles.chipText, { color: '#92400e' }]}>Cierra: {e.fecha_cierre}</Text>
                  </View>
                )}
                {e.ya_respondio && (
                  <View style={[styles.chip, { backgroundColor: '#dcfce7' }]}>
                    <Text style={[styles.chipText, { color: '#166534' }]}>✓ Respondida</Text>
                  </View>
                )}
              </View>
            </View>
            {!e.ya_respondio && (
              <TouchableOpacity style={styles.btnResponder} onPress={() => abrirEncuesta(e)}>
                <Ionicons name="pencil" size={14} color="#fff" />
                <Text style={styles.btnText}>Responder</Text>
              </TouchableOpacity>
            )}
          </View>
        ))}

        {!isLoading && encuestas.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="clipboard-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay encuestas disponibles en este momento.</Text>
          </View>
        )}
      </ScrollView>

      {/* Modal para responder encuesta */}
      <Modal visible={!!modalEncuesta} animationType="slide" presentationStyle="pageSheet" onRequestClose={cerrarModal}>
        <SafeAreaView style={{ flex: 1, backgroundColor: '#fff' }}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={cerrarModal}>
              <Ionicons name="close" size={24} color={Colors.text} />
            </TouchableOpacity>
            <Text style={styles.modalTitle} numberOfLines={1}>{modalEncuesta?.titulo}</Text>
            <View style={{ width: 24 }} />
          </View>

          {cargandoDetalle ? (
            <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />
          ) : detalle ? (
            <ScrollView contentContainerStyle={{ padding: 16, paddingBottom: 40 }}>
              {!!detalle.descripcion && (
                <Text style={{ color: Colors.muted, fontSize: 13, marginBottom: 16 }}>{detalle.descripcion}</Text>
              )}
              {detalle.preguntas.map((p: any, idx: number) => (
                <View key={p.id} style={styles.preguntaCard}>
                  <Text style={styles.preguntaTexto}>{idx + 1}. {p.texto}</Text>

                  {p.tipo === 'opcion_multiple' && p.opciones.map((o: any) => (
                    <TouchableOpacity
                      key={o.id}
                      style={[styles.opcion, respuestas[p.id]?.opcion_id === o.id && styles.opcionSeleccionada]}
                      onPress={() => setRespuestas(r => ({ ...r, [p.id]: { opcion_id: o.id } }))}
                    >
                      <View style={[styles.radio, respuestas[p.id]?.opcion_id === o.id && { borderColor: ACCENT, backgroundColor: ACCENT }]} />
                      <Text style={{ fontSize: 13, color: Colors.text, flex: 1 }}>{o.texto}</Text>
                    </TouchableOpacity>
                  ))}

                  {p.tipo === 'escala_1_5' && (
                    <View style={{ flexDirection: 'row', gap: 8, marginTop: 8 }}>
                      {[1,2,3,4,5].map(n => (
                        <TouchableOpacity
                          key={n}
                          style={[styles.escalaBtn, respuestas[p.id]?.escala_valor === n && { backgroundColor: ACCENT }]}
                          onPress={() => setRespuestas(r => ({ ...r, [p.id]: { escala_valor: n } }))}
                        >
                          <Text style={{ fontWeight: '800', color: respuestas[p.id]?.escala_valor === n ? '#fff' : Colors.text }}>{n}</Text>
                        </TouchableOpacity>
                      ))}
                    </View>
                  )}

                  {p.tipo === 'texto_libre' && (
                    <TextInput
                      style={styles.textInput}
                      placeholder="Escribe tu respuesta..."
                      placeholderTextColor={Colors.muted}
                      multiline
                      numberOfLines={3}
                      value={respuestas[p.id]?.respuesta_texto ?? ''}
                      onChangeText={t => setRespuestas(r => ({ ...r, [p.id]: { respuesta_texto: t } }))}
                    />
                  )}
                </View>
              ))}

              <TouchableOpacity
                style={[styles.btnEnviar, enviar.isPending && { opacity: 0.6 }]}
                onPress={handleEnviar}
                disabled={enviar.isPending}
              >
                {enviar.isPending
                  ? <ActivityIndicator color="#fff" size="small" />
                  : <Text style={{ color: '#fff', fontWeight: '800', fontSize: 15 }}>Enviar respuestas</Text>
                }
              </TouchableOpacity>
            </ScrollView>
          ) : null}
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, paddingBottom: 32, gap: 10 },
  title:       { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 2 },
  sub:         { fontSize: 13, color: Colors.muted, marginBottom: 4 },
  centered:    { alignItems: 'center', paddingVertical: 48, gap: 10 },
  empty:       { textAlign: 'center', color: Colors.muted, fontSize: 13 },
  card:        { backgroundColor: '#fff', borderRadius: 14, padding: 14, borderLeftWidth: 4, flexDirection: 'row', alignItems: 'center', gap: 12,
                 shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardTitle:   { fontSize: 14, fontWeight: '700', color: Colors.text, marginBottom: 2 },
  cardDesc:    { fontSize: 12, color: Colors.muted, marginBottom: 6 },
  chipRow:     { flexDirection: 'row', flexWrap: 'wrap', gap: 4 },
  chip:        { borderRadius: 99, paddingHorizontal: 8, paddingVertical: 2 },
  chipText:    { fontSize: 10, fontWeight: '700' },
  btnResponder:{ backgroundColor: ACCENT, borderRadius: 8, paddingHorizontal: 12, paddingVertical: 7,
                 flexDirection: 'row', alignItems: 'center', gap: 5 },
  btnText:     { color: '#fff', fontWeight: '700', fontSize: 12 },
  modalHeader: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
                 padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:  { fontSize: 15, fontWeight: '800', color: Colors.text, flex: 1, textAlign: 'center', marginHorizontal: 8 },
  preguntaCard:{ backgroundColor: '#f8fafc', borderRadius: 12, padding: 14, marginBottom: 12 },
  preguntaTexto:{ fontSize: 14, fontWeight: '700', color: Colors.text, marginBottom: 10 },
  opcion:      { flexDirection: 'row', alignItems: 'center', gap: 10, padding: 10,
                 borderRadius: 8, marginBottom: 4, borderWidth: 1, borderColor: Colors.border },
  opcionSeleccionada: { borderColor: ACCENT, backgroundColor: '#f3e8ff' },
  radio:       { width: 18, height: 18, borderRadius: 9, borderWidth: 2, borderColor: Colors.border },
  escalaBtn:   { width: 44, height: 44, borderRadius: 10, backgroundColor: '#f1f5f9',
                 alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: Colors.border },
  textInput:   { borderWidth: 1, borderColor: Colors.border, borderRadius: 8, padding: 10,
                 fontSize: 13, color: Colors.text, minHeight: 80, textAlignVertical: 'top', marginTop: 8 },
  btnEnviar:   { backgroundColor: ACCENT, borderRadius: 12, padding: 16,
                 alignItems: 'center', justifyContent: 'center', marginTop: 8 },
})
