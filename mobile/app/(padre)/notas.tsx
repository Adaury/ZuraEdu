import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { dashboardApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

function semColor(n: number) {
  return n >= 80 ? Colors.green : n >= 70 ? Colors.amber : Colors.red
}

export default function NotasPadre() {
  const { data, isLoading } = useQuery({
    queryKey:  ['dashboard'],
    queryFn:   () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const hijos = data?.data?.hijos ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Notas</Text>

        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 40 }} />}

        {hijos.map((hijo: any, hi: number) => (
          <View key={hi}>
            <View style={styles.hijoHeader}>
              <View style={styles.dot} />
              <Text style={styles.hijoNombre}>{hijo.nombres} {hijo.apellidos}</Text>
            </View>
            {(hijo.materias ?? []).map((m: any, mi: number) => {
              const nota = parseFloat(m.nota_final ?? m.promedio ?? 0)
              return (
                <View key={mi} style={styles.row}>
                  <Text style={styles.materia}>{m.asignatura}</Text>
                  <View style={[styles.badge, { backgroundColor: semColor(nota) + '20' }]}>
                    <Text style={[styles.nota, { color: semColor(nota) }]}>{isNaN(nota) ? '—' : nota}</Text>
                  </View>
                </View>
              )
            })}
          </View>
        ))}

        {!isLoading && hijos.length === 0 && (
          <Text style={styles.empty}>No hay datos disponibles.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, paddingBottom: 32, gap: 8 },
  title:       { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoHeader:  { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 12, marginBottom: 6 },
  dot:         { width: 10, height: 10, borderRadius: 99, backgroundColor: Colors.roles.padre },
  hijoNombre:  { fontSize: 15, fontWeight: '800', color: Colors.text },
  row:         { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  materia:     { flex: 1, fontSize: 14, fontWeight: '600', color: Colors.text },
  badge:       { borderRadius: 10, paddingHorizontal: 14, paddingVertical: 8, alignItems: 'center', minWidth: 52 },
  nota:        { fontSize: 17, fontWeight: '900' },
  empty:       { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
