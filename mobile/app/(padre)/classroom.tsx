import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity, Linking, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

// ── Helpers ───────────────────────────────────────────────────────────────────

const TIPO_COLOR: Record<string, string> = {
  anuncio:    Colors.blue,
  material:   Colors.green,
  tarea:      Colors.amber,
  evaluacion: Colors.red,
}

function tipoColor(tipo: string) { return TIPO_COLOR[tipo] ?? Colors.muted }

function fechaCorta(iso: string | null) {
  if (!iso) return null
  return new Date(iso).toLocaleDateString('es-DO', { day: '2-digit', month: 'short' })
}

function diasRestantes(iso: string | null): number | null {
  if (!iso) return null
  return Math.ceil((new Date(iso).getTime() - Date.now()) / 86_400_000)
}

// ── Pantalla ──────────────────────────────────────────────────────────────────

export default function ClassroomPadre() {
  const [hijoIdx, setHijoIdx]               = useState(0)
  const [claseSeleccionada, setClase]       = useState<any | null>(null)

  // ── Lista (por rol padre, devuelve hijos con clases) ──
  const {
    data, isLoading, isError, refetch, isRefetching,
  } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: { estudiante: string; clases: any[] }[] = data?.hijos ?? []
  const hijoActual = hijos[hijoIdx]
  const clases: any[] = hijoActual?.clases ?? []

  // ── Materiales del aula seleccionada ──
  const { data: detalle, isLoading: detLoading } = useQuery({
    queryKey:  ['classroom-materiales-padre', claseSeleccionada?.id],
    queryFn:   () => classroomApi.materiales(claseSeleccionada!.id).then(r => r.data),
    enabled:   !!claseSeleccionada,
  })

  // ── Vista detalle ──────────────────────────────────────────────────────────
  if (claseSeleccionada) {
    const color      = claseSeleccionada.portada_color ?? Colors.purple
    const materiales: any[] = detalle?.materiales ?? []

    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={[styles.detHeader, { backgroundColor: color }]}>
          <TouchableOpacity onPress={() => setClase(null)} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </TouchableOpacity>
          <View style={{ flex: 1 }}>
            <Text style={styles.detTitle} numberOfLines={1}>{claseSeleccionada.nombre}</Text>
            <Text style={styles.detSub}>{claseSeleccionada.asignatura} · {claseSeleccionada.docente}</Text>
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.content}>
          {detLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 40 }} />}

          {!detLoading && materiales.length === 0 && (
            <View style={styles.centered}>
              <Ionicons name="documents-outline" size={44} color={Colors.muted} />
              <Text style={styles.emptyText}>No hay materiales publicados aún.</Text>
            </View>
          )}

          {materiales.map((m: any) => {
            const tc     = tipoColor(m.tipo)
            const dias   = diasRestantes(m.fecha_limite)
            const vencido = m.vencido
            const entregado = m.entrega?.estado === 'entregado' || m.entrega?.estado === 'calificado'

            return (
              <View key={m.id} style={[styles.materialCard, { borderLeftColor: tc }]}>
                <View style={styles.materialHeader}>
                  <View style={[styles.tipoBadge, { backgroundColor: tc + '20' }]}>
                    <Text style={[styles.tipoText, { color: tc }]}>
                      {m.tipo.charAt(0).toUpperCase() + m.tipo.slice(1)}
                    </Text>
                  </View>
                  {entregado && (
                    <View style={[styles.tipoBadge, { backgroundColor: Colors.green + '20', marginLeft: 6 }]}>
                      <Text style={[styles.tipoText, { color: Colors.green }]}>
                        {m.entrega.calificacion != null ? `${m.entrega.calificacion} pts` : 'Entregado'}
                      </Text>
                    </View>
                  )}
                  {m.puntos != null && (
                    <Text style={styles.puntos}>{m.puntos} pts</Text>
                  )}
                </View>

                <Text style={styles.materialTitulo}>{m.titulo}</Text>

                {!!m.contenido && (
                  <Text style={styles.materialContenido} numberOfLines={3}>{m.contenido}</Text>
                )}

                {m.fecha_limite && (
                  <View style={styles.fechaRow}>
                    <Ionicons
                      name="time-outline" size={13}
                      color={vencido ? Colors.red : dias != null && dias <= 2 ? Colors.amber : Colors.muted}
                    />
                    <Text style={[styles.fechaText, {
                      color: vencido ? Colors.red : dias != null && dias <= 2 ? Colors.amber : Colors.muted,
                    }]}>
                      {vencido ? 'Vencido'
                        : dias === 0 ? 'Hoy'
                        : dias === 1 ? 'Mañana'
                        : `Vence ${fechaCorta(m.fecha_limite)}`}
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

                {!!m.entrega?.comentario && (
                  <View style={styles.comentarioBox}>
                    <Text style={styles.comentarioLabel}>Comentario del docente:</Text>
                    <Text style={styles.comentarioText}>{m.entrega.comentario}</Text>
                  </View>
                )}
              </View>
            )
          })}
        </ScrollView>
      </SafeAreaView>
    )
  }

  // ── Vista lista ────────────────────────────────────────────────────────────
  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.purple} />}
      >
        <Text style={styles.pageTitle}>Classroom</Text>

        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 40 }} />}

        {isError && (
          <View style={styles.centered}>
            <Ionicons name="cloud-offline-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>Error al cargar. Desliza para reintentar.</Text>
          </View>
        )}

        {/* Selector de hijo */}
        {!isLoading && hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectorRow}>
            {hijos.map((h, i) => (
              <TouchableOpacity
                key={i}
                onPress={() => { setHijoIdx(i); setClase(null) }}
                style={[styles.selectorPill, hijoIdx === i && styles.selectorPillActive]}
              >
                <Text style={[styles.selectorText, hijoIdx === i && styles.selectorTextActive]}>
                  {h.estudiante.split(' ')[0]}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Nombre del hijo */}
        {hijoActual && (
          <View style={styles.hijoRow}>
            <View style={styles.hijoAvatar}>
              <Text style={styles.hijoAvatarTxt}>{hijoActual.estudiante[0] ?? '?'}</Text>
            </View>
            <Text style={styles.hijoNombre}>{hijoActual.estudiante}</Text>
          </View>
        )}

        {!isLoading && !isError && clases.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="easel-outline" size={44} color={Colors.muted} />
            <Text style={styles.emptyText}>No hay aulas virtuales activas.</Text>
          </View>
        )}

        {clases.map((c: any) => (
          <TouchableOpacity
            key={c.id}
            style={styles.claseCard}
            onPress={() => setClase(c)}
            activeOpacity={0.85}
          >
            <View style={[styles.claseAccent, { backgroundColor: c.portada_color ?? Colors.purple }]} />
            <View style={styles.claseBody}>
              <Text style={styles.claseNombre} numberOfLines={2}>{c.nombre}</Text>
              <Text style={styles.claseAsig}>{c.asignatura}</Text>
              {!!c.docente && <Text style={styles.claseDocente}>{c.docente}</Text>}
            </View>
            <Ionicons name="chevron-forward" size={18} color={Colors.muted} />
          </TouchableOpacity>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

// ── Estilos ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, paddingBottom: 40, gap: 10 },
  centered:          { alignItems: 'center', justifyContent: 'center', paddingVertical: 48, gap: 10 },
  pageTitle:         { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },

  selectorRow:       { marginBottom: 4 },
  selectorPill:      { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99,
                       paddingHorizontal: 16, paddingVertical: 7, marginRight: 8 },
  selectorPillActive:{ backgroundColor: Colors.purple, borderColor: Colors.purple },
  selectorText:      { fontSize: 13, fontWeight: '600', color: Colors.muted },
  selectorTextActive:{ color: '#fff' },

  hijoRow:           { flexDirection: 'row', alignItems: 'center', gap: 10, marginBottom: 4 },
  hijoAvatar:        { width: 36, height: 36, borderRadius: 18,
                       backgroundColor: Colors.purple + '22', alignItems: 'center', justifyContent: 'center' },
  hijoAvatarTxt:     { fontSize: 16, fontWeight: '900', color: Colors.purple },
  hijoNombre:        { fontSize: 14, fontWeight: '800', color: Colors.text },

  claseCard:         { backgroundColor: '#fff', borderRadius: 14, flexDirection: 'row', alignItems: 'center',
                       overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  claseAccent:       { width: 8, alignSelf: 'stretch' },
  claseBody:         { flex: 1, padding: 14, gap: 3 },
  claseNombre:       { fontSize: 15, fontWeight: '800', color: Colors.text },
  claseAsig:         { fontSize: 12, fontWeight: '600', color: Colors.purple },
  claseDocente:      { fontSize: 11, color: Colors.muted },

  detHeader:         { flexDirection: 'row', alignItems: 'center', gap: 12,
                       paddingHorizontal: 16, paddingTop: 12, paddingBottom: 14 },
  backBtn:           { padding: 4 },
  detTitle:          { fontSize: 16, fontWeight: '900', color: '#fff' },
  detSub:            { fontSize: 11, color: 'rgba(255,255,255,.8)', marginTop: 2 },

  materialCard:      { backgroundColor: '#fff', borderRadius: 14, padding: 14, borderLeftWidth: 4,
                       gap: 8, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  materialHeader:    { flexDirection: 'row', alignItems: 'center', flexWrap: 'wrap', gap: 6 },
  tipoBadge:         { borderRadius: 99, paddingHorizontal: 10, paddingVertical: 3 },
  tipoText:          { fontSize: 11, fontWeight: '700' },
  puntos:            { fontSize: 11, fontWeight: '700', color: Colors.muted, marginLeft: 'auto' },
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

  comentarioBox:     { backgroundColor: Colors.green + '12', borderRadius: 8, padding: 10, gap: 3 },
  comentarioLabel:   { fontSize: 11, fontWeight: '700', color: Colors.green },
  comentarioText:    { fontSize: 12, color: Colors.text },

  emptyText:         { fontSize: 13, color: Colors.muted, textAlign: 'center' },
})
