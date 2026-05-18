import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  ActivityIndicator, Alert, FlatList,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

type Estado = 'presente' | 'ausente' | 'tardanza' | 'excusa'

const ESTADOS: { key: Estado; label: string; color: string; icon: any }[] = [
  { key: 'presente', label: 'P', color: Colors.green,  icon: 'checkmark-circle' },
  { key: 'tardanza', label: 'T', color: Colors.amber,  icon: 'time'             },
  { key: 'ausente',  label: 'A', color: Colors.red,    icon: 'close-circle'     },
  { key: 'excusa',   label: 'E', color: Colors.purple, icon: 'shield-checkmark' },
]

export default function AsistenciaDocente() {
  const [grupoId, setGrupoId]   = useState<number | null>(null)
  const [estados, setEstados]   = useState<Record<number, Estado>>({})
  const [fecha, setFecha]       = useState(() => new Date().toISOString().slice(0, 10))
  const queryClient             = useQueryClient()

  const { data: gruposData } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const grupos: any[] = gruposData?.data ?? []

  const { data, isLoading } = useQuery({
    queryKey: ['docente-asistencia', grupoId, fecha],
    queryFn:  () => docenteApi.consultarAsistencia(grupoId!).then(r => r.data),
    enabled:  !!grupoId,
  })

  const estudiantes: any[] = data?.estudiantes ?? []

  // Pre-llenar con asistencia ya registrada del día
  React.useEffect(() => {
    if (!estudiantes.length) return
    const preload: Record<number, Estado> = {}
    estudiantes.forEach((e: any) => {
      if (e.estado_hoy) preload[e.id] = e.estado_hoy
    })
    if (Object.keys(preload).length) setEstados(preload)
  }, [estudiantes.map((e: any) => e.id).join(',')])

  const mutation = useMutation({
    mutationFn: (payload: any) => docenteApi.registrarAsistencia(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['docente-asistencia', grupoId] })
      Alert.alert('Asistencia guardada', 'Los registros han sido guardados correctamente.')
    },
    onError: () => Alert.alert('Error', 'No se pudo guardar la asistencia.'),
  })

  const toggleEstado = (estudId: number) => {
    setEstados(prev => {
      const order: Estado[] = ['presente', 'tardanza', 'ausente', 'excusa']
      const cur = prev[estudId] ?? 'ausente'
      const next = order[(order.indexOf(cur) + 1) % order.length]
      return { ...prev, [estudId]: next }
    })
  }

  const guardar = () => {
    if (!grupoId) return
    const registros = estudiantes.map((e: any) => ({
      estudiante_id: e.id,
      estado:        estados[e.id] ?? 'ausente',
    }))
    mutation.mutate({ grupo_id: grupoId, fecha, registros })
  }

  const marcarTodos = (estado: Estado) => {
    const bulk: Record<number, Estado> = {}
    estudiantes.forEach((e: any) => { bulk[e.id] = estado })
    setEstados(bulk)
  }

  const totalPorEstado = (key: Estado) => Object.values(estados).filter(v => v === key).length

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Selector de grupo */}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.grupoScroll}>
        {grupos.map((g: any) => (
          <TouchableOpacity
            key={g.id}
            style={[styles.grupoChip, grupoId === g.id && styles.grupoChipActive]}
            onPress={() => { setGrupoId(g.id); setEstados({}) }}
          >
            <Text style={[styles.grupoChipTxt, grupoId === g.id && styles.grupoChipTxtActive]}>
              {g.asignatura} · {g.grado}{g.seccion}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {!grupoId ? (
        <View style={styles.emptyWrap}>
          <Ionicons name="calendar-number-outline" size={56} color={Colors.muted} />
          <Text style={styles.emptyTxt}>Selecciona un grupo para registrar asistencia</Text>
        </View>
      ) : (
        <>
          {/* Resumen + acciones rápidas */}
          <View style={styles.toolbar}>
            <View style={styles.statsRow}>
              {ESTADOS.map(s => (
                <View key={s.key} style={[styles.stat, { borderTopColor: s.color }]}>
                  <Text style={[styles.statNum, { color: s.color }]}>{totalPorEstado(s.key)}</Text>
                  <Text style={styles.statLbl}>{s.label}</Text>
                </View>
              ))}
            </View>
            <View style={styles.quickActions}>
              <TouchableOpacity style={[styles.qBtn, { borderColor: Colors.green }]} onPress={() => marcarTodos('presente')}>
                <Text style={[styles.qBtnTxt, { color: Colors.green }]}>Todos presentes</Text>
              </TouchableOpacity>
              <TouchableOpacity style={[styles.qBtn, { borderColor: Colors.red }]} onPress={() => marcarTodos('ausente')}>
                <Text style={[styles.qBtnTxt, { color: Colors.red }]}>Todos ausentes</Text>
              </TouchableOpacity>
            </View>
          </View>

          {isLoading && <ActivityIndicator color={Colors.amber} style={{ margin: 24 }} />}

          <FlatList
            data={estudiantes}
            keyExtractor={(item, i) => String(item.id ?? i)}
            contentContainerStyle={{ padding: 12, paddingBottom: 100, gap: 8 }}
            renderItem={({ item }) => {
              const estado = estados[item.id] ?? 'ausente'
              const cfg = ESTADOS.find(e => e.key === estado)!
              return (
                <TouchableOpacity style={styles.estudRow} onPress={() => toggleEstado(item.id)} activeOpacity={0.85}>
                  <View style={styles.avatar}>
                    <Text style={styles.avatarTxt}>{(item.nombre ?? item.name ?? '?')[0].toUpperCase()}</Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.estudNombre}>{item.nombre ?? item.name}</Text>
                    {item.matricula && <Text style={styles.estudSub}>#{item.matricula}</Text>}
                  </View>
                  <TouchableOpacity
                    style={[styles.estadoBtn, { backgroundColor: cfg.color }]}
                    onPress={() => toggleEstado(item.id)}
                  >
                    <Ionicons name={cfg.icon} size={14} color="#fff" />
                    <Text style={styles.estadoLabel}>{cfg.label}</Text>
                  </TouchableOpacity>
                </TouchableOpacity>
              )
            }}
          />

          {/* Botón guardar fijo */}
          <View style={styles.footer}>
            <TouchableOpacity
              style={[styles.saveBtn, mutation.isPending && { opacity: .6 }]}
              onPress={guardar}
              disabled={mutation.isPending}
            >
              {mutation.isPending
                ? <ActivityIndicator color="#fff" size="small" />
                : <><Ionicons name="save" size={18} color="#fff" /><Text style={styles.saveTxt}>Guardar Asistencia</Text></>
              }
            </TouchableOpacity>
          </View>
        </>
      )}
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  grupoScroll:     { padding: 12, gap: 8 },
  grupoChip:       { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border },
  grupoChipActive: { backgroundColor: Colors.amber, borderColor: Colors.amber },
  grupoChipTxt:    { fontSize: 12, fontWeight: '700', color: Colors.muted },
  grupoChipTxtActive: { color: '#fff' },
  emptyWrap:       { flex: 1, alignItems: 'center', justifyContent: 'center', gap: 12, padding: 40 },
  emptyTxt:        { color: Colors.muted, textAlign: 'center', fontSize: 15 },
  toolbar:         { backgroundColor: '#fff', marginHorizontal: 12, borderRadius: 16, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 3 },
  statsRow:        { flexDirection: 'row', gap: 6 },
  stat:            { flex: 1, alignItems: 'center', borderTopWidth: 3, borderRadius: 8, paddingTop: 6, backgroundColor: Colors.bg },
  statNum:         { fontSize: 18, fontWeight: '900' },
  statLbl:         { fontSize: 10, color: Colors.muted, fontWeight: '700' },
  quickActions:    { flexDirection: 'row', gap: 8 },
  qBtn:            { flex: 1, paddingVertical: 6, borderRadius: 8, borderWidth: 1.5, alignItems: 'center' },
  qBtnTxt:         { fontSize: 11, fontWeight: '700' },
  estudRow:        { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 4, elevation: 2 },
  avatar:          { width: 36, height: 36, borderRadius: 18, backgroundColor: Colors.primary + '20', alignItems: 'center', justifyContent: 'center' },
  avatarTxt:       { fontSize: 15, fontWeight: '800', color: Colors.primary },
  estudNombre:     { fontSize: 14, fontWeight: '700', color: Colors.text },
  estudSub:        { fontSize: 11, color: Colors.muted },
  estadoBtn:       { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6 },
  estadoLabel:     { fontSize: 12, fontWeight: '900', color: '#fff' },
  footer:          { position: 'absolute', bottom: 0, left: 0, right: 0, padding: 16, backgroundColor: '#fff', borderTopWidth: 1, borderTopColor: Colors.border },
  saveBtn:         { backgroundColor: Colors.primary, borderRadius: 14, paddingVertical: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8 },
  saveTxt:         { fontSize: 15, fontWeight: '800', color: '#fff' },
})
