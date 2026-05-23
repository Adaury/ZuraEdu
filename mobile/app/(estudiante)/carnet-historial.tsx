import React from 'react'
import {
  View, Text, ScrollView, StyleSheet,
  ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { carnetApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.estudiante

const TIPO_ICON: Record<string, keyof typeof Ionicons.glyphMap> = {
  entrada:     'enter-outline',
  salida:      'exit-outline',
  biblioteca:  'library-outline',
  comedor:     'restaurant-outline',
  laboratorio: 'flask-outline',
  evento:      'calendar-outline',
  prestamo:    'book-outline',
}

function capitalize(s: string) {
  return s.charAt(0).toUpperCase() + s.slice(1)
}

export default function CarnetHistorialEstudiante() {
  const { data, isLoading, isRefetching, refetch } = useQuery({
    queryKey: ['carnet-historial-estudiante'],
    queryFn:  () => carnetApi.historial().then(r => r.data),
    staleTime: 60_000,
  })

  const accesos: any[] = data?.accesos ?? []

  const grouped = accesos.reduce((acc: Record<string, any[]>, a: any) => {
    const key = a.fecha ?? 'Sin fecha'
    if (!acc[key]) acc[key] = []
    acc[key].push(a)
    return acc
  }, {})

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 32 }} />}

        {!isLoading && accesos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="time-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin accesos registrados</Text>
            <Text style={styles.emptyText}>Tus entradas y salidas aparecerán aquí.</Text>
          </View>
        )}

        {Object.entries(grouped).map(([fecha, items]) => (
          <View key={fecha} style={styles.group}>
            <Text style={styles.groupDate}>{fecha}</Text>
            {(items as any[]).map((a: any) => (
              <View key={a.id} style={styles.row}>
                <View style={[styles.iconBox, { backgroundColor: a.estado_color + '22' }]}>
                  <Ionicons
                    name={TIPO_ICON[a.tipo_evento] ?? 'swap-horizontal-outline'}
                    size={18}
                    color={a.estado_color}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.rowTipo}>{capitalize(a.tipo_evento)}</Text>
                  {a.zona ? <Text style={styles.rowZona}>{a.zona}</Text> : null}
                </View>
                <View style={styles.rowRight}>
                  <Text style={styles.rowHora}>{a.hora}</Text>
                  <View style={[styles.estadoBadge, { backgroundColor: a.estado_color + '22' }]}>
                    <Text style={[styles.estadoText, { color: a.estado_color }]}>{a.estado_label}</Text>
                  </View>
                </View>
              </View>
            ))}
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, gap: 12, paddingBottom: 40 },
  empty:       { alignItems: 'center', gap: 10, paddingTop: 60 },
  emptyTitle:  { fontSize: 16, fontWeight: '800', color: Colors.text },
  emptyText:   { fontSize: 13, color: Colors.muted, textAlign: 'center' },

  group:       { backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden',
                 shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  groupDate:   { fontSize: 12, fontWeight: '800', color: Colors.muted,
                 paddingHorizontal: 14, paddingVertical: 8, backgroundColor: Colors.bg,
                 borderBottomWidth: 1, borderBottomColor: Colors.border },
  row:         { flexDirection: 'row', alignItems: 'center', gap: 12,
                 padding: 12, paddingHorizontal: 14,
                 borderBottomWidth: 1, borderBottomColor: Colors.border },
  iconBox:     { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  rowTipo:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  rowZona:     { fontSize: 11, color: Colors.muted },
  rowRight:    { alignItems: 'flex-end', gap: 4 },
  rowHora:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  estadoBadge: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 99 },
  estadoText:  { fontSize: 10, fontWeight: '800' },
})
