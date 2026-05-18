import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { asistenciaApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function AsistenciaPadre() {
  const { data, isLoading } = useQuery({
    queryKey: ['asistencia-padre'],
    queryFn:  () => asistenciaApi.index().then(r => r.data),
  })

  const resumen   = data?.resumen ?? {}
  const registros: any[] = data?.registros ?? []

  const stats = [
    { key: 'presente', label: 'Presente', color: Colors.green  },
    { key: 'tardanza', label: 'Tardanza', color: Colors.amber  },
    { key: 'ausente',  label: 'Ausente',  color: Colors.red    },
    { key: 'excusa',   label: 'Excusa',   color: Colors.purple },
  ]

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Asistencia</Text>

        <View style={styles.statsRow}>
          {stats.map(s => (
            <View key={s.key} style={[styles.statBox, { borderTopColor: s.color }]}>
              <Text style={[styles.statNum, { color: s.color }]}>{resumen[s.key] ?? 0}</Text>
              <Text style={styles.statLbl}>{s.label}</Text>
            </View>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 30 }} />}

        {registros.map((r: any, i: number) => {
          const color = r.estado === 'presente' ? Colors.green
            : r.estado === 'tardanza' ? Colors.amber
            : r.estado === 'ausente'  ? Colors.red : Colors.purple
          return (
            <View key={i} style={styles.row}>
              <View style={[styles.dot, { backgroundColor: color }]} />
              <View style={{ flex: 1 }}>
                <Text style={styles.fecha}>{r.fecha}</Text>
                <Text style={styles.asig}>{r.asignatura}</Text>
              </View>
              <Text style={[styles.estado, { color }]}>
                {r.estado.charAt(0).toUpperCase() + r.estado.slice(1)}
              </Text>
            </View>
          )
        })}

        {!isLoading && registros.length === 0 && (
          <Text style={styles.empty}>No hay registros de asistencia.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:      { flex: 1, backgroundColor: Colors.bg },
  content:   { padding: 16, paddingBottom: 32, gap: 8 },
  title:     { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  statsRow:  { flexDirection: 'row', gap: 8, marginBottom: 4 },
  statBox:   { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10, alignItems: 'center', borderTopWidth: 3, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  statNum:   { fontSize: 22, fontWeight: '900' },
  statLbl:   { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  row:       { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  dot:       { width: 10, height: 10, borderRadius: 99 },
  fecha:     { fontSize: 13, fontWeight: '700', color: Colors.text },
  asig:      { fontSize: 12, color: Colors.muted },
  estado:    { fontSize: 12, fontWeight: '700' },
  empty:     { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
