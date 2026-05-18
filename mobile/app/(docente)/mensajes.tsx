import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity,
  TextInput, RefreshControl, KeyboardAvoidingView, Platform, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { mensajesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

type ViewState = 'list' | 'detail' | 'compose'

function fechaCorta(iso: string | null) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('es-DO', { day: '2-digit', month: 'short', year: 'numeric' })
}

export default function MensajesDocente() {
  const qc = useQueryClient()
  const [view,     setView]     = useState<ViewState>('list')
  const [tab,      setTab]      = useState<'recibidos' | 'enviados'>('recibidos')
  const [selected, setSelected] = useState<any | null>(null)
  const [asunto,   setAsunto]   = useState('')
  const [cuerpo,   setCuerpo]   = useState('')
  const [destIds,  setDestIds]  = useState<number[]>([])

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['mensajes-docente'],
    queryFn:  () => mensajesApi.index().then(r => r.data),
  })

  const { data: destData } = useQuery({
    queryKey: ['mensajes-destinatarios-docente'],
    queryFn:  () => mensajesApi.destinatarios().then(r => r.data),
    enabled:  view === 'compose',
  })

  const enviar = useMutation({
    mutationFn: () => mensajesApi.store({ asunto, cuerpo, destinatario_ids: destIds }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['mensajes-docente'] })
      setAsunto(''); setCuerpo(''); setDestIds([])
      setView('list')
      Alert.alert('Enviado', 'Tu mensaje fue enviado correctamente.')
    },
    onError: () => Alert.alert('Error', 'No se pudo enviar el mensaje.'),
  })

  const recibidos: any[] = data?.recibidos ?? []
  const enviados:  any[] = data?.enviados  ?? []
  const noLeidos         = data?.no_leidos ?? 0
  const lista            = tab === 'recibidos' ? recibidos : enviados
  const destinatarios: any[] = destData?.destinatarios ?? []

  if (view === 'detail' && selected) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.detHeader}>
          <TouchableOpacity onPress={() => setView('list')} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color={Colors.text} />
          </TouchableOpacity>
          <Text style={styles.detAsunto} numberOfLines={2}>{selected.asunto}</Text>
        </View>
        <ScrollView contentContainerStyle={styles.content}>
          <View style={styles.metaRow}>
            <Ionicons name={selected.tipo === 'recibido' ? 'person-circle' : 'send'} size={16} color={ACCENT} />
            <Text style={styles.metaTxt}>
              {selected.tipo === 'recibido' ? `De: ${selected.remitente}` : `Para: ${selected.destinatarios}`}
            </Text>
          </View>
          <Text style={styles.metaFecha}>{fechaCorta(selected.fecha)}</Text>
          <View style={styles.cuerpoBx}>
            <Text style={styles.cuerpoTxt}>{selected.cuerpo}</Text>
          </View>
        </ScrollView>
      </SafeAreaView>
    )
  }

  if (view === 'compose') {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <View style={styles.detHeader}>
            <TouchableOpacity onPress={() => setView('list')} style={styles.backBtn}>
              <Ionicons name="close" size={20} color={Colors.text} />
            </TouchableOpacity>
            <Text style={[styles.detAsunto, { fontSize: 16 }]}>Nuevo mensaje</Text>
            <TouchableOpacity
              onPress={() => {
                if (!asunto.trim() || !cuerpo.trim() || destIds.length === 0) {
                  Alert.alert('Faltan datos', 'Completa destinatario, asunto y mensaje.')
                  return
                }
                enviar.mutate()
              }}
              style={[styles.sendBtn, { backgroundColor: ACCENT }]}
            >
              {enviar.isPending
                ? <ActivityIndicator size="small" color="#fff" />
                : <Ionicons name="send" size={16} color="#fff" />}
            </TouchableOpacity>
          </View>
          <ScrollView contentContainerStyle={styles.content}>
            <Text style={styles.label}>Destinatario</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 8 }}>
              {destinatarios.map((d: any) => {
                const sel = destIds.includes(d.id)
                return (
                  <TouchableOpacity
                    key={d.id}
                    onPress={() => setDestIds(sel ? destIds.filter(x => x !== d.id) : [...destIds, d.id])}
                    style={[styles.destPill, sel && { backgroundColor: ACCENT, borderColor: ACCENT }]}
                  >
                    <Text style={[styles.destTxt, sel && { color: '#fff' }]} numberOfLines={1}>{d.nombre}</Text>
                  </TouchableOpacity>
                )
              })}
            </ScrollView>
            <Text style={styles.label}>Asunto</Text>
            <TextInput style={styles.input} value={asunto} onChangeText={setAsunto}
              placeholder="Asunto del mensaje" placeholderTextColor={Colors.muted} />
            <Text style={styles.label}>Mensaje</Text>
            <TextInput style={[styles.input, styles.inputMulti]} value={cuerpo} onChangeText={setCuerpo}
              placeholder="Escribe tu mensaje aquí..." placeholderTextColor={Colors.muted}
              multiline textAlignVertical="top" />
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <View style={styles.headerRow}>
        <Text style={styles.pageTitle}>Mensajes</Text>
        <TouchableOpacity style={[styles.fab, { backgroundColor: ACCENT }]} onPress={() => setView('compose')}>
          <Ionicons name="create-outline" size={18} color="#fff" />
          <Text style={styles.fabTxt}>Redactar</Text>
        </TouchableOpacity>
      </View>
      <View style={styles.tabs}>
        {(['recibidos', 'enviados'] as const).map(t => (
          <TouchableOpacity key={t} style={[styles.tabBtn, tab === t && styles.tabBtnActive]} onPress={() => setTab(t)}>
            <Text style={[styles.tabTxt, tab === t && { color: ACCENT }]}>
              {t === 'recibidos' ? `Recibidos${noLeidos > 0 ? ` (${noLeidos})` : ''}` : 'Enviados'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}
        {!isLoading && lista.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="mail-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>No hay mensajes aquí.</Text>
          </View>
        )}
        {lista.map((m: any) => (
          <TouchableOpacity
            key={m.id}
            style={[styles.msgRow, !m.leido && tab === 'recibidos' && styles.msgRowUnread]}
            onPress={async () => {
              const detail = await mensajesApi.show(m.id).then(r => r.data)
              setSelected(detail); setView('detail')
            }}
            activeOpacity={0.85}
          >
            <View style={[styles.avatarDot, { backgroundColor: ACCENT }]}>
              <Ionicons name={tab === 'recibidos' ? 'person' : 'send'} size={14} color="#fff" />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={[styles.msgAsunto, !m.leido && tab === 'recibidos' && { fontWeight: '900' }]} numberOfLines={1}>
                {m.asunto}
              </Text>
              <Text style={styles.msgMeta} numberOfLines={1}>
                {tab === 'recibidos' ? m.remitente : m.destinatarios}
              </Text>
              <Text style={styles.msgPreview} numberOfLines={1}>{m.preview}</Text>
            </View>
            <Text style={styles.msgFecha}>{fechaCorta(m.fecha)}</Text>
          </TouchableOpacity>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 32, gap: 8 },
  centered:     { alignItems: 'center', paddingVertical: 48, gap: 10 },
  headerRow:    { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingTop: 16, paddingBottom: 8 },
  pageTitle:    { fontSize: 22, fontWeight: '900', color: Colors.text, flex: 1 },
  fab:          { flexDirection: 'row', alignItems: 'center', gap: 6, paddingHorizontal: 14, paddingVertical: 8, borderRadius: 99 },
  fabTxt:       { fontSize: 13, fontWeight: '700', color: '#fff' },
  tabs:         { flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: Colors.border, marginHorizontal: 16 },
  tabBtn:       { flex: 1, paddingVertical: 10, alignItems: 'center' },
  tabBtnActive: { borderBottomWidth: 2, borderBottomColor: ACCENT },
  tabTxt:       { fontSize: 13, fontWeight: '600', color: Colors.muted },
  msgRow:       { flexDirection: 'row', alignItems: 'flex-start', backgroundColor: '#fff', borderRadius: 14,
                  padding: 14, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  msgRowUnread: { borderLeftWidth: 3, borderLeftColor: ACCENT },
  avatarDot:    { width: 32, height: 32, borderRadius: 16, alignItems: 'center', justifyContent: 'center' },
  msgAsunto:    { fontSize: 14, fontWeight: '700', color: Colors.text },
  msgMeta:      { fontSize: 11, color: Colors.muted, marginTop: 1 },
  msgPreview:   { fontSize: 12, color: Colors.muted, marginTop: 2 },
  msgFecha:     { fontSize: 10, color: Colors.muted, marginTop: 2 },
  detHeader:    { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 16,
                  borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: '#fff' },
  backBtn:      { padding: 4 },
  detAsunto:    { flex: 1, fontSize: 15, fontWeight: '800', color: Colors.text },
  sendBtn:      { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  metaRow:      { flexDirection: 'row', alignItems: 'center', gap: 6 },
  metaTxt:      { fontSize: 13, fontWeight: '600', color: Colors.text },
  metaFecha:    { fontSize: 11, color: Colors.muted, marginBottom: 12 },
  cuerpoBx:     { backgroundColor: '#fff', borderRadius: 14, padding: 16,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cuerpoTxt:    { fontSize: 14, color: Colors.text, lineHeight: 22 },
  label:        { fontSize: 12, fontWeight: '700', color: Colors.muted, marginBottom: 6 },
  input:        { backgroundColor: '#fff', borderRadius: 12, padding: 12, fontSize: 14,
                  color: Colors.text, borderWidth: 1, borderColor: Colors.border },
  inputMulti:   { minHeight: 140 },
  destPill:     { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99,
                  paddingHorizontal: 14, paddingVertical: 6, marginRight: 8 },
  destTxt:      { fontSize: 12, fontWeight: '600', color: Colors.muted },
  emptyText:    { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
