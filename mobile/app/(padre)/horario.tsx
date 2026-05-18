import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { classroomApi, horarioApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const DIAS_KEY   = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes']
const DIAS_LABEL = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie']
const TODAY_IDX  = Math.min(Math.max(new Date().getDay() - 1, 0), 4)

export default function HorarioPadre() {
  const [hijoIdx, setHijoIdx] = useState(0)
  const [diaIdx,  setDia]     = useState(TODAY_IDX)

  const { data: indexData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: { estudiante_id: number; nombre: string }[] = (indexData?.hijos ?? []).map((h: any) => ({
    estudiante_id: h.estudiante_id,
    nombre:        h.estudiante,
  }))

  const hijoActual = hijos[hijoIdx]

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['horario-hijo', hijoActual?.estudiante_id],
    queryFn:  () => horarioApi.hijo(hijoActual!.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
  })

  const raw: any[] = data?.horario ?? []
  const clases = raw
    .filter(c => c.dia === DIAS_KEY[diaIdx])
    .sort((a, b) => (a.franja?.inicio ?? '').localeCompare(b.franja?.inicio ?? ''))

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.purple} />}
      >
        <Text style={styles.title}>Horario</Text>

        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectorRow}>
            {hijos.map((h, i) => (
              <TouchableOpacity
                key={i}
                onPress={() => setHijoIdx(i)}
                style={[styles.pill, hijoIdx === i && styles.pillActive]}
              >
                <Text style={[styles.pillTxt, hijoIdx === i && styles.pillTxtActive]}>
                  {h.nombre.split(' ')[0]}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {hijoActual && (
          <Text style={styles.hijoNombre}>{hijoActual.nombre}</Text>
        )}

        <View style={styles.daysRow}>
          {DIAS_LABEL.map((d, i) => (
            <TouchableOpacity
              key={d}
              onPress={() => setDia(i)}
              style={[styles.dayBtn, diaIdx === i && styles.dayBtnActive]}
            >
              <Text style={[styles.dayTxt, diaIdx === i && styles.dayTxtActive]}>{d}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginTop: 40 }} />}

        {!isLoading && !data?.publicado && hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="calendar-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>El horario aún no ha sido publicado.</Text>
          </View>
        )}

        {!isLoading && !hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="people-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No se encontraron hijos registrados.</Text>
          </View>
        )}

        {clases.map((c: any, i: number) => (
          <View key={i} style={[styles.clase, { borderLeftColor: c.color ?? Colors.purple }]}>
            <View style={[styles.claseLeft, { backgroundColor: (c.color ?? Colors.purple) + '18' }]}>
              <Text style={[styles.hora, { color: c.color ?? Colors.purple }]}>{c.franja?.inicio}</Text>
              <Text style={[styles.horaFin, { color: (c.color ?? Colors.purple) + 'aa' }]}>{c.franja?.fin}</Text>
            </View>
            <View style={{ flex: 1, padding: 12 }}>
              <Text style={styles.materia}>{c.asignatura}</Text>
              {!!c.docente && <Text style={styles.docente}>{c.docente}</Text>}
              {!!c.aula   && <Text style={styles.aula}>Aula {c.aula}</Text>}
            </View>
          </View>
        ))}

        {!isLoading && data?.publicado && clases.length === 0 && (
          <Text style={styles.empty}>No hay clases este día.</Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 32, gap: 10 },
  title:        { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  centered:     { alignItems: 'center', paddingVertical: 48, gap: 10 },
  selectorRow:  { marginBottom: 4 },
  pill:         { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99,
                  paddingHorizontal: 16, paddingVertical: 7, marginRight: 8 },
  pillActive:   { backgroundColor: Colors.purple, borderColor: Colors.purple },
  pillTxt:      { fontSize: 13, fontWeight: '600', color: Colors.muted },
  pillTxtActive:{ color: '#fff' },
  hijoNombre:   { fontSize: 13, fontWeight: '700', color: Colors.muted },
  daysRow:      { flexDirection: 'row', gap: 8, marginBottom: 4 },
  dayBtn:       { flex: 1, paddingVertical: 10, borderRadius: 12, backgroundColor: '#fff',
                  alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border },
  dayBtnActive: { backgroundColor: Colors.purple, borderColor: Colors.purple },
  dayTxt:       { fontSize: 13, fontWeight: '700', color: Colors.muted },
  dayTxtActive: { color: '#fff' },
  clase:        { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 14,
                  overflow: 'hidden', borderLeftWidth: 5,
                  shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  claseLeft:    { width: 64, alignItems: 'center', justifyContent: 'center', padding: 10 },
  hora:         { fontSize: 13, fontWeight: '800' },
  horaFin:      { fontSize: 11, fontWeight: '600', marginTop: 2 },
  materia:      { fontSize: 14, fontWeight: '700', color: Colors.text },
  docente:      { fontSize: 12, color: Colors.muted, marginTop: 2 },
  aula:         { fontSize: 11, color: Colors.muted, marginTop: 2 },
  empty:        { textAlign: 'center', color: Colors.muted, marginTop: 40, fontSize: 13 },
})
