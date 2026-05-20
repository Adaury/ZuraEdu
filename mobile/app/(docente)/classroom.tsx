import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity, Linking, RefreshControl, TextInput,
  Modal, KeyboardAvoidingView, Platform, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { classroomApi, docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const color = Colors.roles.docente

const TIPO_COLOR: Record<string, string> = {
  anuncio:    Colors.blue,
  material:   Colors.green,
  tarea:      Colors.amber,
  evaluacion: Colors.red,
}

const TIPOS = ['anuncio', 'material', 'tarea', 'evaluacion'] as const
type Tipo = typeof TIPOS[number]

function tipoColor(tipo: string) { return TIPO_COLOR[tipo] ?? Colors.muted }

function fechaCorta(iso: string | null) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('es-DO', { day: '2-digit', month: 'short' })
}

function diasRestantes(iso: string | null): number | null {
  if (!iso) return null
  return Math.ceil((new Date(iso).getTime() - Date.now()) / 86_400_000)
}

const FORM_EMPTY = { titulo: '', tipo: 'material' as Tipo, contenido: '', url_externo: '', publicado: false }

const AULA_COLORS = ['#1e3a6e', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#64748b']
const AULA_EMPTY  = { asignacion_id: 0, nombre: '', portada_color: '#1e3a6e' }

export default function ClassroomDocente() {
  const qc = useQueryClient()
  const [claseSeleccionada, setClase]   = useState<any | null>(null)
  const [showForm, setShowForm]         = useState(false)
  const [form, setForm]                 = useState(FORM_EMPTY)
  const [showAulaForm, setShowAulaForm] = useState(false)
  const [aulaForm, setAulaForm]         = useState(AULA_EMPTY)

  const { data: listaData, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ['classroom-docente'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const clases: any[]       = listaData?.clases    ?? []
  const asignaciones: any[] = gruposData?.asignaciones ?? []

  const { data: detalle, isLoading: detLoading, refetch: detRefetch } = useQuery({
    queryKey:  ['classroom-mat-docente', claseSeleccionada?.id],
    queryFn:   () => classroomApi.materiales(claseSeleccionada!.id).then(r => r.data),
    enabled:   !!claseSeleccionada,
  })

  const crearMaterial = useMutation({
    mutationFn: () => classroomApi.storeMaterial(claseSeleccionada!.id, {
      titulo:      form.titulo.trim(),
      tipo:        form.tipo,
      contenido:   form.contenido.trim() || undefined,
      url_externo: form.url_externo.trim() || undefined,
      publicado:   form.publicado,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['classroom-mat-docente', claseSeleccionada?.id] })
      setShowForm(false)
      setForm(FORM_EMPTY)
    },
    onError: () => Alert.alert('Error', 'No se pudo crear el material.'),
  })

  const togglePublicar = useMutation({
    mutationFn: (materialId: number) => classroomApi.togglePublicar(materialId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['classroom-mat-docente', claseSeleccionada?.id] })
    },
    onError: () => Alert.alert('Error', 'No se pudo cambiar el estado.'),
  })

  const crearAula = useMutation({
    mutationFn: () => classroomApi.storeClase({
      asignacion_id: aulaForm.asignacion_id,
      nombre:        aulaForm.nombre.trim(),
      portada_color: aulaForm.portada_color,
    }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['classroom-docente'] })
      setShowAulaForm(false)
      setAulaForm(AULA_EMPTY)
    },
    onError: () => Alert.alert('Error', 'No se pudo crear el aula.'),
  })

  const submitAula = () => {
    if (!aulaForm.nombre.trim())   return Alert.alert('Atención', 'El nombre del aula es obligatorio.')
    if (!aulaForm.asignacion_id)   return Alert.alert('Atención', 'Selecciona una asignatura/grupo.')
    crearAula.mutate()
  }

  const submitForm = () => {
    if (!form.titulo.trim()) return Alert.alert('Atención', 'El título es obligatorio.')
    crearMaterial.mutate()
  }

  // ── Modal crear aula ────────────────────────────────────────────────────
  const AulaModal = (
    <Modal visible={showAulaForm} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setShowAulaForm(false)}>
      <SafeAreaView style={{ flex: 1, backgroundColor: Colors.bg }} edges={['top', 'bottom']}>
        <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setShowAulaForm(false)} style={{ padding: 4 }}>
              <Ionicons name="close" size={22} color={Colors.text} />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Nueva Aula Virtual</Text>
            <TouchableOpacity
              onPress={submitAula}
              disabled={crearAula.isPending}
              style={[styles.modalSaveBtn, { backgroundColor: color }]}
            >
              {crearAula.isPending
                ? <ActivityIndicator size="small" color="#fff" />
                : <Text style={styles.modalSaveTxt}>Crear</Text>
              }
            </TouchableOpacity>
          </View>

          <ScrollView contentContainerStyle={{ padding: 16, gap: 16 }} keyboardShouldPersistTaps="handled">
            {/* Nombre */}
            <View>
              <Text style={styles.fieldLabel}>Nombre del aula *</Text>
              <TextInput
                style={styles.input}
                value={aulaForm.nombre}
                onChangeText={t => setAulaForm(f => ({ ...f, nombre: t }))}
                placeholder="Ej: Matemáticas 1er Cuatrimestre"
                placeholderTextColor={Colors.muted}
              />
            </View>

            {/* Asignatura/grupo */}
            <View>
              <Text style={styles.fieldLabel}>Asignatura / Grupo *</Text>
              {asignaciones.length === 0
                ? <Text style={{ color: Colors.muted, fontSize: 13 }}>Cargando asignaciones...</Text>
                : asignaciones.map((a: any) => (
                  <TouchableOpacity
                    key={a.asignacion_id}
                    style={[styles.asigRow, aulaForm.asignacion_id === a.asignacion_id && { borderColor: color, backgroundColor: color + '10' }]}
                    onPress={() => setAulaForm(f => ({ ...f, asignacion_id: a.asignacion_id }))}
                  >
                    <View style={{ flex: 1 }}>
                      <Text style={{ fontSize: 14, fontWeight: '700', color: Colors.text }}>{a.asignatura}</Text>
                      <Text style={{ fontSize: 11, color: Colors.muted }}>{a.grupo}</Text>
                    </View>
                    {aulaForm.asignacion_id === a.asignacion_id && (
                      <Ionicons name="checkmark-circle" size={20} color={color} />
                    )}
                  </TouchableOpacity>
                ))
              }
            </View>

            {/* Color */}
            <View>
              <Text style={styles.fieldLabel}>Color de portada</Text>
              <View style={styles.colorRow}>
                {AULA_COLORS.map(c => (
                  <TouchableOpacity
                    key={c}
                    style={[styles.colorSwatch, { backgroundColor: c },
                      aulaForm.portada_color === c && styles.colorSwatchSel]}
                    onPress={() => setAulaForm(f => ({ ...f, portada_color: c }))}
                  >
                    {aulaForm.portada_color === c && <Ionicons name="checkmark" size={14} color="#fff" />}
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </Modal>
  )

  // ── Modal crear material ────────────────────────────────────────────────
  const FormModal = (
    <Modal visible={showForm} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setShowForm(false)}>
      <SafeAreaView style={{ flex: 1, backgroundColor: Colors.bg }} edges={['top', 'bottom']}>
        <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setShowForm(false)} style={{ padding: 4 }}>
              <Ionicons name="close" size={22} color={Colors.text} />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Nuevo material</Text>
            <TouchableOpacity
              onPress={submitForm}
              disabled={crearMaterial.isPending}
              style={[styles.modalSaveBtn, { backgroundColor: color }]}
            >
              {crearMaterial.isPending
                ? <ActivityIndicator size="small" color="#fff" />
                : <Text style={styles.modalSaveTxt}>Crear</Text>
              }
            </TouchableOpacity>
          </View>

          <ScrollView contentContainerStyle={{ padding: 16, gap: 14 }} keyboardShouldPersistTaps="handled">
            {/* Tipo */}
            <View>
              <Text style={styles.fieldLabel}>Tipo</Text>
              <View style={styles.tipoRow}>
                {TIPOS.map(t => (
                  <TouchableOpacity
                    key={t}
                    style={[styles.tipoPill, form.tipo === t && { backgroundColor: tipoColor(t), borderColor: tipoColor(t) }]}
                    onPress={() => setForm(f => ({ ...f, tipo: t }))}
                  >
                    <Text style={[styles.tipoPillTxt, form.tipo === t && { color: '#fff' }]}>
                      {t.charAt(0).toUpperCase() + t.slice(1)}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>

            {/* Título */}
            <View>
              <Text style={styles.fieldLabel}>Título *</Text>
              <TextInput
                style={styles.input}
                value={form.titulo}
                onChangeText={t => setForm(f => ({ ...f, titulo: t }))}
                placeholder="Título del material"
                placeholderTextColor={Colors.muted}
              />
            </View>

            {/* Contenido */}
            <View>
              <Text style={styles.fieldLabel}>Descripción / Instrucciones</Text>
              <TextInput
                style={[styles.input, { minHeight: 100 }]}
                value={form.contenido}
                onChangeText={t => setForm(f => ({ ...f, contenido: t }))}
                placeholder="Instrucciones o descripción..."
                placeholderTextColor={Colors.muted}
                multiline
                textAlignVertical="top"
              />
            </View>

            {/* URL */}
            <View>
              <Text style={styles.fieldLabel}>Enlace externo</Text>
              <TextInput
                style={styles.input}
                value={form.url_externo}
                onChangeText={t => setForm(f => ({ ...f, url_externo: t }))}
                placeholder="https://..."
                placeholderTextColor={Colors.muted}
                keyboardType="url"
                autoCapitalize="none"
              />
            </View>

            {/* Publicar */}
            <TouchableOpacity
              style={styles.checkRow}
              onPress={() => setForm(f => ({ ...f, publicado: !f.publicado }))}
            >
              <View style={[styles.checkbox, form.publicado && { backgroundColor: color, borderColor: color }]}>
                {form.publicado && <Ionicons name="checkmark" size={14} color="#fff" />}
              </View>
              <Text style={styles.checkLabel}>Publicar inmediatamente</Text>
            </TouchableOpacity>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </Modal>
  )

  // ── Vista detalle ──────────────────────────────────────────────────────
  if (claseSeleccionada) {
    const acento     = claseSeleccionada.portada_color ?? color
    const materiales: any[] = detalle?.materiales ?? []
    const publicados  = materiales.filter(m => m.publicado)
    const borradores  = materiales.filter(m => !m.publicado)

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        {FormModal}
        <View style={[styles.detHeader, { backgroundColor: acento }]}>
          <TouchableOpacity onPress={() => setClase(null)} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{claseSeleccionada.nombre}</Text>
            <Text style={styles.detSub}>{claseSeleccionada.asignatura}</Text>
          </View>
          <TouchableOpacity
            style={styles.addBtn}
            onPress={() => { setForm(FORM_EMPTY); setShowForm(true) }}
          >
            <Ionicons name="add" size={20} color="#fff" />
          </TouchableOpacity>
        </View>

        <ScrollView
          contentContainerStyle={styles.content}
          refreshControl={<RefreshControl refreshing={false} onRefresh={detRefetch} tintColor={color} />}
        >
          {detLoading && <ActivityIndicator color={color} style={{ marginTop: 40 }} />}

          {!detLoading && materiales.length === 0 && (
            <View style={styles.centered}>
              <Ionicons name="documents-outline" size={44} color={Colors.muted} />
              <Text style={styles.emptyText}>No hay materiales. Toca + para crear uno.</Text>
            </View>
          )}

          {[...borradores, ...publicados].map((m: any) => {
            const tc   = tipoColor(m.tipo)
            const dias = diasRestantes(m.fecha_limite)

            return (
              <View key={m.id} style={[styles.materialCard, { borderLeftColor: tc, opacity: m.publicado ? 1 : 0.75 }]}>
                <View style={styles.materialHeader}>
                  <View style={[styles.tipoBadge, { backgroundColor: tc + '20' }]}>
                    <Text style={[styles.tipoText, { color: tc }]}>
                      {m.tipo.charAt(0).toUpperCase() + m.tipo.slice(1)}
                    </Text>
                  </View>
                  {!m.publicado && (
                    <View style={[styles.tipoBadge, { backgroundColor: Colors.muted + '20', marginLeft: 6 }]}>
                      <Text style={[styles.tipoText, { color: Colors.muted }]}>Borrador</Text>
                    </View>
                  )}
                  {m.puntos != null && (
                    <Text style={styles.puntos}>{m.puntos} pts</Text>
                  )}
                  {/* Toggle publicar */}
                  <TouchableOpacity
                    style={[styles.pubBtn, { backgroundColor: m.publicado ? Colors.green + '18' : Colors.amber + '18' }]}
                    onPress={() => togglePublicar.mutate(m.id)}
                    disabled={togglePublicar.isPending && togglePublicar.variables === m.id}
                  >
                    {togglePublicar.isPending && togglePublicar.variables === m.id
                      ? <ActivityIndicator size="small" color={Colors.muted} />
                      : <Ionicons
                          name={m.publicado ? 'eye' : 'eye-off'}
                          size={14}
                          color={m.publicado ? Colors.green : Colors.amber}
                        />
                    }
                  </TouchableOpacity>
                </View>

                <Text style={styles.materialTitulo}>{m.titulo}</Text>

                {!!m.contenido && (
                  <Text style={styles.materialContenido} numberOfLines={2}>{m.contenido}</Text>
                )}

                {m.fecha_limite && (
                  <View style={styles.fechaRow}>
                    <Ionicons name="time-outline" size={13} color={m.vencido ? Colors.red : dias != null && dias <= 2 ? Colors.amber : Colors.muted} />
                    <Text style={[styles.fechaText, { color: m.vencido ? Colors.red : dias != null && dias <= 2 ? Colors.amber : Colors.muted }]}>
                      {m.vencido ? 'Vencido' : dias === 0 ? 'Hoy' : dias === 1 ? 'Mañana' : `Vence ${fechaCorta(m.fecha_limite)}`}
                    </Text>
                  </View>
                )}

                {m.archivos?.length > 0 && (
                  <View style={styles.archivosRow}>
                    {m.archivos.map((a: any, i: number) => (
                      <TouchableOpacity
                        key={i} style={styles.archivoBtn}
                        onPress={() => a.url && Linking.openURL(a.url)}
                      >
                        <Ionicons name="attach" size={13} color={Colors.blue} />
                        <Text style={styles.archivoNombre} numberOfLines={1}>{a.nombre}</Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                )}

                {!!m.url_externo && (
                  <TouchableOpacity style={styles.urlBtn} onPress={() => Linking.openURL(m.url_externo)}>
                    <Ionicons name="link" size={13} color={Colors.indigo} />
                    <Text style={styles.urlText}>Abrir enlace</Text>
                  </TouchableOpacity>
                )}
              </View>
            )
          })}
        </ScrollView>
      </SafeAreaView>
    )
  }

  // ── Vista lista ────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {AulaModal}
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={color} />}
      >
        <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
          <Text style={[styles.pageTitle, { flex: 1, marginBottom: 0 }]}>Mis Aulas</Text>
          <TouchableOpacity
            style={[styles.fabList, { backgroundColor: color }]}
            onPress={() => { setAulaForm(AULA_EMPTY); setShowAulaForm(true) }}
          >
            <Ionicons name="add" size={20} color="#fff" />
            <Text style={styles.fabListTxt}>Nueva</Text>
          </TouchableOpacity>
        </View>

        {isLoading && <ActivityIndicator color={color} style={{ marginTop: 40 }} />}

        {isError && (
          <View style={styles.centered}>
            <Ionicons name="cloud-offline-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>Error al cargar. Desliza para reintentar.</Text>
          </View>
        )}

        {!isLoading && !isError && clases.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="easel-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>No tienes aulas virtuales activas.</Text>
          </View>
        )}

        {clases.map((c: any) => (
          <TouchableOpacity
            key={c.id}
            style={styles.claseCard}
            onPress={() => setClase(c)}
            activeOpacity={0.85}
          >
            <View style={[styles.claseAccent, { backgroundColor: c.portada_color ?? color }]} />
            <View style={styles.claseBody}>
              <Text style={styles.claseNombre} numberOfLines={2}>{c.nombre}</Text>
              <Text style={styles.claseAsig}>{c.asignatura}</Text>
            </View>
            <Ionicons name="chevron-forward" size={18} color={Colors.muted} />
          </TouchableOpacity>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, paddingBottom: 40, gap: 10 },
  centered:          { alignItems: 'center', justifyContent: 'center', paddingVertical: 48, gap: 10 },
  pageTitle:         { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },

  claseCard:         { backgroundColor: '#fff', borderRadius: 14, flexDirection: 'row', alignItems: 'center',
                       overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  claseAccent:       { width: 8, alignSelf: 'stretch' },
  claseBody:         { flex: 1, padding: 14, gap: 3 },
  claseNombre:       { fontSize: 15, fontWeight: '800', color: Colors.text },
  claseAsig:         { fontSize: 12, fontWeight: '600', color },

  detHeader:         { flexDirection: 'row', alignItems: 'center', gap: 12,
                       paddingHorizontal: 16, paddingTop: 12, paddingBottom: 14 },
  backBtn:           { padding: 4 },
  addBtn:            { width: 34, height: 34, borderRadius: 10, backgroundColor: 'rgba(255,255,255,.2)',
                       alignItems: 'center', justifyContent: 'center' },
  detTitle:          { fontSize: 16, fontWeight: '900', color: '#fff' },
  detSub:            { fontSize: 11, color: 'rgba(255,255,255,.8)', marginTop: 2 },

  materialCard:      { backgroundColor: '#fff', borderRadius: 14, padding: 14, borderLeftWidth: 4,
                       gap: 8, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  materialHeader:    { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 6 },
  tipoBadge:         { borderRadius: 99, paddingHorizontal: 10, paddingVertical: 3 },
  tipoText:          { fontSize: 11, fontWeight: '700' },
  puntos:            { fontSize: 11, fontWeight: '700', color: Colors.muted },
  pubBtn:            { marginLeft: 'auto', width: 28, height: 28, borderRadius: 8,
                       alignItems: 'center', justifyContent: 'center' },
  materialTitulo:    { fontSize: 14, fontWeight: '800', color: Colors.text },
  materialContenido: { fontSize: 12, color: Colors.muted, lineHeight: 18 },

  fechaRow:          { flexDirection: 'row', alignItems: 'center', gap: 4 },
  fechaText:         { fontSize: 12, fontWeight: '600' },

  archivosRow:       { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  archivoBtn:        { flexDirection: 'row', alignItems: 'center', gap: 4,
                       backgroundColor: Colors.blue + '12', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5 },
  archivoNombre:     { fontSize: 11, color: Colors.blue, maxWidth: 160 },

  urlBtn:            { flexDirection: 'row', alignItems: 'center', gap: 5 },
  urlText:           { fontSize: 12, color: Colors.indigo, fontWeight: '600' },

  emptyText:         { fontSize: 13, color: Colors.muted, textAlign: 'center' },

  fabList:           { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 14, paddingVertical: 8, borderRadius: 99 },
  fabListTxt:        { color: '#fff', fontWeight: '700', fontSize: 13 },

  asigRow:           { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border,
                       borderRadius: 12, padding: 12, marginBottom: 8, backgroundColor: '#fff' },

  colorRow:          { flexDirection: 'row', flexWrap: 'wrap', gap: 10, marginTop: 4 },
  colorSwatch:       { width: 38, height: 38, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  colorSwatchSel:    { borderWidth: 3, borderColor: '#fff', shadowColor: '#000', shadowOpacity: .25, shadowRadius: 4, elevation: 4 },

  // Modal
  modalHeader:       { flexDirection: 'row', alignItems: 'center', padding: 16,
                       borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: '#fff', gap: 12 },
  modalTitle:        { flex: 1, fontSize: 16, fontWeight: '800', color: Colors.text },
  modalSaveBtn:      { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 10 },
  modalSaveTxt:      { color: '#fff', fontWeight: '700', fontSize: 14 },
  fieldLabel:        { fontSize: 12, fontWeight: '700', color: Colors.muted, marginBottom: 6 },
  input:             { backgroundColor: '#fff', borderRadius: 12, padding: 12, fontSize: 14,
                       color: Colors.text, borderWidth: 1, borderColor: Colors.border },
  tipoRow:           { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  tipoPill:          { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99,
                       paddingHorizontal: 14, paddingVertical: 6 },
  tipoPillTxt:       { fontSize: 12, fontWeight: '600', color: Colors.muted },
  checkRow:          { flexDirection: 'row', alignItems: 'center', gap: 10 },
  checkbox:          { width: 22, height: 22, borderRadius: 6, borderWidth: 2, borderColor: Colors.border,
                       alignItems: 'center', justifyContent: 'center' },
  checkLabel:        { fontSize: 14, fontWeight: '600', color: Colors.text },
})
