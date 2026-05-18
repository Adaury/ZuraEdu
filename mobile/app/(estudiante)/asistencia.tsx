import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { asistenciaApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ESTADOS: Record<string, { label: string; color: string; bg: string }> = {
  presente: { label: 'P', color: Colors.green,  bg: Colors.green  + '22' },
  tardanza: { label: 'T', color: Colors.amber,  bg: Colors.amber  + '22' },
  ausente:  { label: 'A', color: Colors.red,    bg: Colors.red    + '22' },
  excusa:   { label: 'E', color: Colors.purple, bg: Colors.purple + '22' },
}

export default function AsistenciaEstudiante() {
  const { data, isLoading } = useQuery({
    queryKey: ['asistencia'],
    queryFn:  () => asistenciaApi.index().then(r => r.data),
  })

  const resumen = data?.resumen ?? {}
  const registros: any[] = data?.registros ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Mi Asistencia</Text>

        {/* Resumen */}
        <View style={styles.resumenRow}>
          {Object.entries(ESTADOS).map(([key, est]) => (
            <View key={key} style={[styles.resumenBox, { backgroundColor: est.bg }]}>
              <Text style={[styles.resumenNum, { color: est.color }]}>{resumen[key] ?? 0}</Text>
              <Text style={[styles.resumenLbl, { color: est.color }]}>
                {key.charAt(0).toUpperCase() + key.slice(1)}
              </Text>
            </View>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 30 }} />}

        {registros.map((r: any, i: number) => {
          const est = ESTADOS[r.estado] ?? { label: '?', color: Colors.muted, bg: '#f1f5f9' }
          return (
            <View key={i} style={styles.row}>
              <View style={[styles.badge, { backgroundColor: est.bg }]}>
                <Text style={[styles.badgeTxt, { color: est.color }]}>{est.label}</Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.fecha}>{r.fecha}</Text>
                <Text style={styles.asignatura}>{r.asignatura}</Text>
              </View>
              <Text style={[styles.estado, { color: est.color }]}>
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
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, paddingBottom: 32, gap: 8 },
  title:       { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 8 },
  resumenRow:  { flexDirection: 'row', gap: 10, marginBottom: 8 },
  resumenBox:  { flex: 1, borderRadius: 14, padding: 12, alignItems: 'center' },
  resumenNum:  { fontSize: 22, fontWeight: '900' },
  resumenLbl:  { fontSize: 11, fontWeight: '700', marginTop: 2 },
  row:         { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 12, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  badge:       { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  badgeTxt:    { fontSize: 16, fontWeight: '900' },
  fecha:       { fontSize: 13, fontWeight: '700', color: Colors.text },
  asignatura:  { fontSize: 12, color: Colors.muted },
  estado:      { fontSize: 12, fontWeight: '700' },
  empty:       { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
