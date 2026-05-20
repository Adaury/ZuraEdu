import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, Modal, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { comunicadosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function ComunicadosEstudiante() {
  const [sel, setSel] = useState<any>(null)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['comunicados'],
    queryFn:  () => comunicadosApi.index().then(r => r.data),
  })

  const items: any[] = data?.items ?? []

  function fmtFecha(iso: string) {
    try { return new Date(iso).toLocaleDateString('es-DO', { day: '2-digit', month: 'long', year: 'numeric' }) }
    catch { return iso }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        <Text style={styles.title}>Comunicados</Text>

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {items.map((c: any, i: number) => (
          <TouchableOpacity key={i} style={styles.card} activeOpacity={0.85} onPress={() => setSel(c)}>
            <View style={styles.cardHeader}>
              <View style={[styles.iconBox, c.importante && { backgroundColor: Colors.red + '15' }]}>
                <Ionicons name={c.importante ? 'alert-circle' : 'megaphone'} size={18} color={c.importante ? Colors.red : Colors.blue} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.cardTitle}>{c.titulo}</Text>
                <Text style={styles.cardMeta}>{c.fecha ? fmtFecha(c.fecha) : '—'}</Text>
              </View>
              {c.importante && (
                <View style={styles.importanteBadge}>
                  <Text style={styles.importanteTxt}>Importante</Text>
                </View>
              )}
            </View>
            <Text style={styles.cardBody} numberOfLines={3}>
              {c.contenido?.replace(/<[^>]*>/g, '') ?? ''}
            </Text>
          </TouchableOpacity>
        ))}

        {!isLoading && items.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="megaphone-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTxt}>No hay comunicados publicados.</Text>
          </View>
        )}
      </ScrollView>

      {/* Modal de detalle */}
      <Modal visible={!!sel} animationType="slide" onRequestClose={() => setSel(null)}>
        <SafeAreaView style={{ flex: 1, backgroundColor: Colors.bg }}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setSel(null)} style={styles.backBtn}>
              <Ionicons name="arrow-back" size={22} color={Colors.text} />
            </TouchableOpacity>
            <Text style={styles.modalHeaderTitle} numberOfLines={1}>{sel?.titulo}</Text>
          </View>
          <ScrollView contentContainerStyle={styles.modalBody}>
            <View style={styles.detailMeta}>
              <View style={[styles.iconBox, sel?.importante && { backgroundColor: Colors.red + '15' }]}>
                <Ionicons name={sel?.importante ? 'alert-circle' : 'megaphone'} size={22} color={sel?.importante ? Colors.red : Colors.blue} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.detailTitle}>{sel?.titulo}</Text>
                <Text style={styles.detailFecha}>{sel?.fecha ? fmtFecha(sel.fecha) : ''}</Text>
              </View>
            </View>
            {sel?.tipo && sel.tipo !== 'general' && (
              <View style={styles.tipoBadge}>
                <Text style={styles.tipoTxt}>{sel.tipo.charAt(0).toUpperCase() + sel.tipo.slice(1)}</Text>
              </View>
            )}
            <Text style={styles.detailCuerpo}>
              {sel?.contenido?.replace(/<[^>]*>/g, '') ?? ''}
            </Text>
            {!!sel?.adjunto_url && (
              <View style={styles.adjuntoRow}>
                <Ionicons name="attach" size={18} color={Colors.blue} />
                <Text style={styles.adjuntoTxt}>Adjunto disponible</Text>
              </View>
            )}
          </ScrollView>
        </SafeAreaView>
      </Modal>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:             { flex: 1, backgroundColor: Colors.bg },
  content:          { padding: 16, paddingBottom: 32, gap: 12 },
  title:            { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  card:             { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader:       { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  iconBox:          { width: 40, height: 40, borderRadius: 12, backgroundColor: Colors.blue + '15', alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  cardTitle:        { fontSize: 14, fontWeight: '700', color: Colors.text },
  cardMeta:         { fontSize: 11, color: Colors.muted, marginTop: 2 },
  cardBody:         { fontSize: 13, color: '#475569', lineHeight: 20 },
  importanteBadge:  { backgroundColor: Colors.red + '15', borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  importanteTxt:    { fontSize: 10, fontWeight: '800', color: Colors.red },
  empty:            { alignItems: 'center', marginTop: 60, gap: 12 },
  emptyTxt:         { color: Colors.muted, fontSize: 14 },
  modalHeader:      { flexDirection: 'row', alignItems: 'center', gap: 10, paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: Colors.border, backgroundColor: '#fff' },
  backBtn:          { padding: 4 },
  modalHeaderTitle: { flex: 1, fontSize: 16, fontWeight: '700', color: Colors.text },
  modalBody:        { padding: 20, gap: 14 },
  detailMeta:       { flexDirection: 'row', alignItems: 'flex-start', gap: 12 },
  detailTitle:      { fontSize: 18, fontWeight: '800', color: Colors.text, lineHeight: 24 },
  detailFecha:      { fontSize: 12, color: Colors.muted, marginTop: 4 },
  tipoBadge:        { alignSelf: 'flex-start', backgroundColor: Colors.blue + '15', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 4 },
  tipoTxt:          { fontSize: 12, fontWeight: '700', color: Colors.blue },
  detailCuerpo:     { fontSize: 15, color: '#374151', lineHeight: 24 },
  adjuntoRow:       { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: Colors.blue + '10', borderRadius: 10, padding: 12 },
  adjuntoTxt:       { fontSize: 13, fontWeight: '600', color: Colors.blue },
})
