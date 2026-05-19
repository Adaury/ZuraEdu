import React, { useMemo, useState } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { calendarioApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPO: Record<string, { label: string; fallbackColor: string }> = {
  evento:    { label: 'Evento',      fallbackColor: '#3b82f6' },
  academico: { label: 'Académico',   fallbackColor: '#f59e0b' },
}

function buildMonths(count = 3) {
  const months = []
  const now = new Date()
  for (let i = 0; i < count; i++) {
    const d    = new Date(now.getFullYear(), now.getMonth() + i, 1)
    const last = new Date(now.getFullYear(), now.getMonth() + i + 1, 0)
    months.push({
      label: d.toLocaleString('es', { month: 'long', year: 'numeric' }),
      desde: d.toISOString().slice(0, 10),
      hasta: last.toISOString().slice(0, 10),
    })
  }
  return months
}

function formatRango(inicio: string, fin: string): string {
  if (!inicio) return ''
  const toDate = (s: string) => new Date(s + 'T12:00:00')
  const opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short' }
  const i = toDate(inicio).toLocaleDateString('es', opts)
  if (!fin || fin === inicio) return i
  const f = toDate(fin).toLocaleDateString('es', opts)
  return `${i} – ${f}`
}

export default function CalendarioScreen() {
  const months     = useMemo(() => buildMonths(3), [])
  const [sel, setSel] = useState(0)
  const month      = months[sel]

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['calendario'],
    queryFn:  () => calendarioApi.index().then(r => r.data),
    staleTime: 5 * 60 * 1000,
  })

  const items: any[] = useMemo(() => {
    if (!data?.items) return []
    return data.items.filter((e: any) => e.fecha_inicio >= month.desde && e.fecha_inicio <= month.hasta)
  }, [data, month])

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Month pills */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.monthBar}
        contentContainerStyle={styles.monthBarContent}
      >
        {months.map((m, i) => (
          <TouchableOpacity
            key={i}
            style={[styles.monthChip, sel === i && styles.monthChipActive]}
            onPress={() => setSel(i)}
          >
            <Text style={[styles.monthChipTxt, sel === i && styles.monthChipTxtActive]}>
              {m.label.charAt(0).toUpperCase() + m.label.slice(1)}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {isLoading ? (
          <ActivityIndicator style={{ marginTop: 48 }} color={Colors.blue} />
        ) : items.length === 0 ? (
          <View style={styles.empty}>
            <Ionicons name="calendar-outline" size={52} color={Colors.border} />
            <Text style={styles.emptyText}>No hay eventos este mes</Text>
          </View>
        ) : items.map((item: any) => {
          const meta  = TIPO[item.tipo] ?? TIPO.evento
          const color = item.color ?? meta.fallbackColor
          return (
            <View key={`${item.tipo}-${item.id}`} style={styles.card}>
              <View style={[styles.colorBar, { backgroundColor: color }]} />
              <View style={styles.cardBody}>
                <View style={styles.cardTop}>
                  <View style={[styles.tipoBadge, { backgroundColor: color + '20' }]}>
                    <Text style={[styles.tipoBadgeTxt, { color }]}>{meta.label}</Text>
                  </View>
                  <Text style={styles.fecha}>{formatRango(item.fecha_inicio, item.fecha_fin)}</Text>
                </View>
                <Text style={styles.titulo}>{item.titulo}</Text>
                {item.descripcion ? (
                  <Text style={styles.desc} numberOfLines={2}>{item.descripcion}</Text>
                ) : null}
                {item.lugar ? (
                  <View style={styles.lugarRow}>
                    <Ionicons name="location-outline" size={13} color={Colors.muted} />
                    <Text style={styles.lugarTxt}>{item.lugar}</Text>
                  </View>
                ) : null}
              </View>
            </View>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  monthBar:          { backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: Colors.border, maxHeight: 58 },
  monthBarContent:   { paddingHorizontal: 16, paddingVertical: 10, gap: 8, flexDirection: 'row' },
  monthChip:         { paddingHorizontal: 16, paddingVertical: 8, borderRadius: 20, borderWidth: 1.5, borderColor: Colors.border },
  monthChipActive:   { borderColor: Colors.blue, backgroundColor: Colors.blue + '12' },
  monthChipTxt:      { fontSize: 13, fontWeight: '600', color: Colors.muted },
  monthChipTxtActive:{ color: Colors.blue },
  content:           { padding: 16, gap: 12, paddingBottom: 32 },
  empty:             { alignItems: 'center', paddingVertical: 64, gap: 12 },
  emptyText:         { fontSize: 15, color: Colors.muted, fontWeight: '600' },
  card:              { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden', shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  colorBar:          { width: 5 },
  cardBody:          { flex: 1, padding: 14, gap: 6 },
  cardTop:           { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  tipoBadge:         { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  tipoBadgeTxt:      { fontSize: 11, fontWeight: '700' },
  fecha:             { fontSize: 12, color: Colors.muted, fontWeight: '600' },
  titulo:            { fontSize: 15, fontWeight: '800', color: Colors.text },
  desc:              { fontSize: 13, color: Colors.muted, lineHeight: 18 },
  lugarRow:          { flexDirection: 'row', alignItems: 'center', gap: 4 },
  lugarTxt:          { fontSize: 12, color: Colors.muted },
})
