import React from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, ActivityIndicator, Alert, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { comunicadosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function ComintDocente() {
  const queryClient = useQueryClient()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-comint'],
    queryFn:  () => comunicadosApi.comint().then(r => r.data),
  })

  const comunicados: any[] = data?.data ?? data ?? []

  const mutation = useMutation({
    mutationFn: (id: number) => comunicadosApi.marcarLeido(id),
    onSuccess: (_, id) => {
      queryClient.setQueryData(['docente-comint'], (old: any) => {
        const list = old?.data ?? old ?? []
        const updated = list.map((c: any) => c.id === id ? { ...c, leido: true } : c)
        return old?.data ? { ...old, data: updated } : updated
      })
    },
    onError: () => Alert.alert('Error', 'No se pudo registrar la lectura.'),
  })

  const marcarLeido = (c: any) => {
    if (c.leido) return
    mutation.mutate(c.id)
  }

  const noLeidos = comunicados.filter((c: any) => !c.leido).length

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.amber} />
        }
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
            <Text style={styles.title}>Comunicados Internos</Text>
            {noLeidos > 0 && (
              <Text style={styles.subtitle}>{noLeidos} sin leer</Text>
            )}
          </View>
          <View style={[styles.badgeWrap, { backgroundColor: noLeidos > 0 ? Colors.red : Colors.green }]}>
            <Ionicons name={noLeidos > 0 ? 'mail-unread' : 'mail'} size={16} color="#fff" />
            <Text style={styles.badgeNum}>{noLeidos > 0 ? noLeidos : '✓'}</Text>
          </View>
        </View>

        {isLoading && <ActivityIndicator color={Colors.amber} style={{ marginTop: 40 }} />}

        {comunicados.map((c: any, i: number) => {
          const leido = c.leido ?? false
          const fecha = c.fecha_publicacion ?? c.created_at?.slice(0, 10) ?? ''
          const prioridad = c.prioridad ?? 'normal'
          const badgeColor = prioridad === 'urgente' ? Colors.red : prioridad === 'alta' ? Colors.amber : Colors.blue
          return (
            <TouchableOpacity
              key={c.id ?? i}
              style={[styles.card, leido && styles.cardLeido]}
              onPress={() => marcarLeido(c)}
              activeOpacity={0.85}
            >
              {!leido && <View style={styles.unreadDot} />}

              <View style={styles.cardTop}>
                <View style={[styles.iconWrap, { backgroundColor: badgeColor + '20' }]}>
                  <Ionicons
                    name={c.es_interno ? 'shield-checkmark' : 'megaphone'}
                    size={18}
                    color={badgeColor}
                  />
                </View>
                <View style={{ flex: 1, gap: 2 }}>
                  <View style={styles.titleRow}>
                    <Text style={[styles.cardTitle, !leido && styles.cardTitleUnread]} numberOfLines={2}>
                      {c.titulo ?? c.title}
                    </Text>
                    {c.es_interno && (
                      <View style={styles.internoBadge}>
                        <Text style={styles.internoTxt}>Interno</Text>
                      </View>
                    )}
                  </View>
                  <Text style={styles.cardMeta}>
                    {fecha}{c.autor ? ` · ${c.autor}` : ''}
                  </Text>
                </View>
              </View>

              {c.contenido || c.cuerpo ? (
                <Text style={styles.cardBody} numberOfLines={3}>
                  {c.contenido ?? c.cuerpo}
                </Text>
              ) : null}

              <View style={styles.cardFooter}>
                {leido ? (
                  <View style={styles.leidoChip}>
                    <Ionicons name="checkmark-done" size={12} color={Colors.green} />
                    <Text style={styles.leidoTxt}>Acuse registrado</Text>
                  </View>
                ) : (
                  <TouchableOpacity
                    style={[styles.acuseBtn, mutation.isPending && { opacity: .5 }]}
                    onPress={() => marcarLeido(c)}
                    disabled={mutation.isPending}
                  >
                    <Ionicons name="checkmark-circle-outline" size={14} color={Colors.primary} />
                    <Text style={styles.acuseTxt}>Acuse de recibo</Text>
                  </TouchableOpacity>
                )}

                {prioridad !== 'normal' && (
                  <View style={[styles.prioChip, { backgroundColor: badgeColor + '20' }]}>
                    <Text style={[styles.prioTxt, { color: badgeColor }]}>
                      {prioridad.charAt(0).toUpperCase() + prioridad.slice(1)}
                    </Text>
                  </View>
                )}
              </View>
            </TouchableOpacity>
          )
        })}

        {!isLoading && comunicados.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="mail-outline" size={56} color={Colors.muted} />
            <Text style={styles.emptyTxt}>No hay comunicados internos.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, paddingBottom: 32, gap: 10 },
  header:         { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 4 },
  title:          { fontSize: 22, fontWeight: '900', color: Colors.text },
  subtitle:       { fontSize: 12, color: Colors.red, fontWeight: '700', marginTop: 2 },
  badgeWrap:      { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 20, paddingHorizontal: 10, paddingVertical: 5 },
  badgeNum:       { fontSize: 13, fontWeight: '900', color: '#fff' },
  card:           { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .06, shadowRadius: 8, elevation: 3, position: 'relative' },
  cardLeido:      { opacity: .75 },
  unreadDot:      { position: 'absolute', top: 12, right: 12, width: 8, height: 8, borderRadius: 4, backgroundColor: Colors.blue },
  cardTop:        { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  iconWrap:       { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  titleRow:       { flexDirection: 'row', flexWrap: 'wrap', gap: 6, alignItems: 'center' },
  cardTitle:      { fontSize: 14, fontWeight: '600', color: Colors.text, flex: 1 },
  cardTitleUnread:{ fontWeight: '800' },
  cardMeta:       { fontSize: 11, color: Colors.muted },
  cardBody:       { fontSize: 13, color: Colors.muted, lineHeight: 20 },
  cardFooter:     { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  leidoChip:      { flexDirection: 'row', alignItems: 'center', gap: 4 },
  leidoTxt:       { fontSize: 11, color: Colors.green, fontWeight: '700' },
  acuseBtn:       { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: Colors.primary + '10', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5, borderWidth: 1, borderColor: Colors.primary + '30' },
  acuseTxt:       { fontSize: 12, fontWeight: '700', color: Colors.primary },
  internoBadge:   { backgroundColor: Colors.blue + '20', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },
  internoTxt:     { fontSize: 10, fontWeight: '700', color: Colors.blue },
  prioChip:       { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  prioTxt:        { fontSize: 11, fontWeight: '700' },
  empty:          { alignItems: 'center', gap: 12, paddingTop: 60 },
  emptyTxt:       { color: Colors.muted, textAlign: 'center', fontSize: 15 },
})
