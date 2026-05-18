import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { horarioApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie']
const TODAY_IDX = Math.min(Math.max(new Date().getDay() - 1, 0), 4)

export default function HorarioEstudiante() {
  const { data, isLoading } = useQuery({
    queryKey: ['horario'],
    queryFn:  () => horarioApi.index().then(r => r.data),
  })

  const [diaIdx, setDia] = useState(TODAY_IDX)
  const horario = data?.horario ?? {}
  const clases: any[] = horario[DIAS[diaIdx]] ?? horario[Object.keys(horario)[diaIdx]] ?? []

  const subjectColors = [Colors.blue, Colors.purple, Colors.green, Colors.amber, Colors.red, '#06b6d4', '#ec4899']

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Mi Horario</Text>

        {/* Selector de día */}
        <View style={styles.daysRow}>
          {DIAS.map((d, i) => (
            <TouchableOpacity
              key={d}
              onPress={() => setDia(i)}
              style={[styles.dayBtn, diaIdx === i && styles.dayBtnActive]}
            >
              <Text style={[styles.dayTxt, diaIdx === i && styles.dayTxtActive]}>{d}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {clases.map((c: any, i: number) => (
          <View key={i} style={[styles.clase, { borderLeftColor: subjectColors[i % subjectColors.length] }]}>
            <View style={[styles.claseLeft, { backgroundColor: subjectColors[i % subjectColors.length] + '15' }]}>
              <Text style={[styles.hora, { color: subjectColors[i % subjectColors.length] }]}>
                {c.hora_inicio ?? c.inicio}
              </Text>
              <Text style={[styles.horaFin, { color: subjectColors[i % subjectColors.length] + 'aa' }]}>
                {c.hora_fin ?? c.fin}
              </Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.materia}>{c.asignatura ?? c.materia}</Text>
              <Text style={styles.docente}>{c.docente}</Text>
              {c.aula && <Text style={styles.aula}>Aula {c.aula}</Text>}
            </View>
          </View>
        ))}

        {!isLoading && clases.length === 0 && (
          <Text style={styles.empty}>No hay clases este día.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, paddingBottom: 32, gap: 10 },
  title:       { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  daysRow:     { flexDirection: 'row', gap: 8, marginBottom: 4 },
  dayBtn:      { flex: 1, paddingVertical: 10, borderRadius: 12, backgroundColor: '#fff', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border },
  dayBtnActive:{ backgroundColor: Colors.blue, borderColor: Colors.blue },
  dayTxt:      { fontSize: 13, fontWeight: '700', color: Colors.muted },
  dayTxtActive:{ color: '#fff' },
  clase:       { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden', borderLeftWidth: 5, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  claseLeft:   { width: 66, padding: 12, alignItems: 'center', justifyContent: 'center' },
  hora:        { fontSize: 14, fontWeight: '800' },
  horaFin:     { fontSize: 11, fontWeight: '600', marginTop: 2 },
  materia:     { fontSize: 14, fontWeight: '700', color: Colors.text, padding: 12, paddingBottom: 2 },
  docente:     { fontSize: 12, color: Colors.muted, paddingHorizontal: 12 },
  aula:        { fontSize: 11, color: Colors.muted, paddingHorizontal: 12, paddingBottom: 10 },
  empty:       { textAlign: 'center', color: Colors.muted, marginTop: 40 },
})
