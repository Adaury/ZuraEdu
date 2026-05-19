import React from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { notificacionesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPO_META: Record<string, { icon: string; color: string }> = {
  info:         { icon: 'information-circle', color: Colors.blue   },
  success:      { icon: 'checkmark-circle',   color: Colors.green  },
  warning:      { icon: 'warning',            color: Colors.amber  },
  error:        { icon: 'close-circle',       color: '#dc2626'     },
  solicitud:    { icon: 'document-text',      color: Colors.indigo },
  recordatorio: { icon: 'alarm',              color: Colors.purple },
  alerta:       { icon: 'alert-circle',       color: Colors.amber  },
}

function formatTiempo(iso: string): string {
  if (!iso) return ''
  const diff = Date.now() - new Date(iso).getTime()
  const min  = Math.floor(diff / 60_000)
  if (min < 1)   return 'Ahora'
  if (min < 60)  return `Hace ${min} min`
  const hrs = Math.floor(min / 60)
  if (hrs < 24)  return `Hace ${hrs}h`
  const days = Math.floor(hrs / 24)
  return `Hace ${days}d`
}

export default function NotificacionesScreen() {
  const qc = useQueryClient()

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['notificaciones'],
    queryFn:  () => notificacionesApi.index().then(r => r.data),
  })

  const marcarUna = useMutation({
    mutationFn: (id: number) => notificacionesApi.marcar(id),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['notificaciones'] }),
  })

  const marcarTodas = useMutation({
    mutationFn: () => notificacionesApi.marcarTodas(),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['notificaciones'] }),
  })

  const items    = data?.items    ?? []
  const noLeidas = data?.no_leidas ?? 0

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={Colors.blue} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Top bar */}
      <View style={styles.topBar}>
        <View style={styles.topLeft}>
          <Text style={styles.topTitle}>Notificaciones</Text>
          {noLeidas > 0 && (
            <View style={styles.countBadge}>
              <Text style={styles.countBadgeTxt}>{noLeidas}</Text>
            </View>
          )}
        </View>
        {noLeidas > 0 && (
          <TouchableOpacity
            onPress={() => marcarTodas.mutate()}
            disabled={marcarTodas.isPending}
          >
            <Text style={styles.marcarBtn}>Marcar todas leídas</Text>
          </TouchableOpacity>
        )}
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {items.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="notifications-off-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>Sin notificaciones</Text>
          </View>
        ) : items.map((n: any) => {
          const meta  = TIPO_META[n.tipo] ?? TIPO_META.info
          const unread = !n.leida
          return (
            <TouchableOpacity
              key={n.id}
              style={[styles.card, unread && styles.cardUnread]}
              onPress={() => unread && marcarUna.mutate(n.id)}
              activeOpacity={unread ? 0.7 : 1}
            >
              <View style={[styles.iconBox, { backgroundColor: meta.color + '18' }]}>
                <Ionicons name={meta.icon as any} size={22} color={meta.color} />
              </View>
              <View style={styles.cardContent}>
                <View style={styles.cardRow}>
                  <Text style={[styles.titulo, unread && styles.tituloUnread]} numberOfLines={1}>
                    {n.titulo}
                  </Text>
                  {unread && <View style={[styles.dot, { backgroundColor: meta.color }]} />}
                </View>
                {(n.cuerpo ?? n.mensaje) ? (
                  <Text style={styles.cuerpo} numberOfLines={2}>{n.cuerpo ?? n.mensaje}</Text>
                ) : null}
                <Text style={styles.tiempo}>{formatTiempo(n.created_at)}</Text>
              </View>
            </TouchableOpacity>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  topBar:        { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', backgroundColor: '#fff', paddingHorizontal: 16, paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: Colors.border },
  topLeft:       { flexDirection: 'row', alignItems: 'center', gap: 8 },
  topTitle:      { fontSize: 16, fontWeight: '800', color: Colors.text },
  countBadge:    { backgroundColor: Colors.blue, borderRadius: 10, paddingHorizontal: 7, paddingVertical: 2 },
  countBadgeTxt: { color: '#fff', fontSize: 12, fontWeight: '700' },
  marcarBtn:     { fontSize: 13, color: Colors.blue, fontWeight: '600' },
  content:       { padding: 16, gap: 10, paddingBottom: 32 },
  empty:         { alignItems: 'center', paddingVertical: 64, gap: 12 },
  emptyText:     { fontSize: 15, color: Colors.muted, fontWeight: '600' },
  card:          { flexDirection: 'row', alignItems: 'flex-start', gap: 12, backgroundColor: '#fff', borderRadius: 14, padding: 14, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardUnread:    { borderLeftWidth: 3, borderLeftColor: Colors.blue },
  iconBox:       { width: 44, height: 44, borderRadius: 12, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  cardContent:   { flex: 1, gap: 3 },
  cardRow:       { flexDirection: 'row', alignItems: 'center', gap: 6 },
  titulo:        { flex: 1, fontSize: 14, fontWeight: '600', color: Colors.text },
  tituloUnread:  { fontWeight: '800' },
  dot:           { width: 8, height: 8, borderRadius: 4, flexShrink: 0 },
  cuerpo:        { fontSize: 13, color: Colors.muted, lineHeight: 18 },
  tiempo:        { fontSize: 11, color: Colors.muted, marginTop: 2 },
})
