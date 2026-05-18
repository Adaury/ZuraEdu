import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { calificacionesApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

function semColor(n: number) {
  return n >= 80 ? Colors.green : n >= 70 ? Colors.amber : Colors.red
}

export default function NotasEstudiante() {
  const { data, isLoading } = useQuery({
    queryKey: ['calificaciones'],
    queryFn:  () => calificacionesApi.index().then(r => r.data),
  })

  const periodos: string[] = data?.periodos ?? []
  const [periodoActivo, setPeriodo] = useState<string | null>(null)
  const materias = data?.materias ?? []

  const periodo = periodoActivo ?? periodos[periodos.length - 1] ?? null

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Mis Calificaciones</Text>

        {/* Tabs de períodos */}
        {periodos.length > 0 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.pillRow}>
            {periodos.map(p => (
              <TouchableOpacity
                key={p}
                onPress={() => setPeriodo(p)}
                style={[styles.pill, periodo === p && styles.pillActive]}
              >
                <Text style={[styles.pillText, periodo === p && styles.pillTextActive]}>{p}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {materias.map((m: any, i: number) => {
          const nota = m.notas?.[periodo ?? ''] ?? m.nota_final ?? null
          const prom = parseFloat(nota)
          return (
            <View key={i} style={styles.row}>
              <View style={{ flex: 1 }}>
                <Text style={styles.materia}>{m.asignatura}</Text>
                <Text style={styles.docente}>{m.docente}</Text>
              </View>
              <View style={[styles.notaBadge, { backgroundColor: isNaN(prom) ? Colors.border : semColor(prom) + '22' }]}>
                <Text style={[styles.notaNum, { color: isNaN(prom) ? Colors.muted : semColor(prom) }]}>
                  {nota ?? '—'}
                </Text>
              </View>
            </View>
          )
        })}

        {!isLoading && materias.length === 0 && (
          <Text style={styles.empty}>No hay calificaciones registradas.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, paddingBottom: 32, gap: 8 },
  title:           { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 8 },
  pillRow:         { marginBottom: 12 },
  pill:            { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6, marginRight: 8 },
  pillActive:      { backgroundColor: Colors.blue, borderColor: Colors.blue },
  pillText:        { fontSize: 13, fontWeight: '600', color: Colors.muted },
  pillTextActive:  { color: '#fff' },
  row:             { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  materia:         { fontSize: 14, fontWeight: '700', color: Colors.text },
  docente:         { fontSize: 12, color: Colors.muted },
  notaBadge:       { borderRadius: 10, padding: 10, minWidth: 52, alignItems: 'center' },
  notaNum:         { fontSize: 18, fontWeight: '900' },
  empty:           { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
