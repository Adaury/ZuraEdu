import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, ActivityIndicator, Modal, FlatList, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const PALETTE = [Colors.blue, Colors.amber, Colors.purple, Colors.green, Colors.red, '#06b6d4', '#ec4899']

export default function GruposDocente() {
  const [selected, setSelected] = useState<any>(null)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
  })

  const grupos: any[] = data?.data ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.amber} />}
      >
        <Text style={styles.title}>Mis Grupos</Text>

        {isLoading && <ActivityIndicator color={Colors.amber} style={{ marginTop: 40 }} />}

        {grupos.map((g: any, i: number) => {
          const color = PALETTE[i % PALETTE.length]
          return (
            <TouchableOpacity key={g.id ?? i} style={styles.card} onPress={() => setSelected(g)} activeOpacity={0.85}>
              <View style={[styles.accentBar, { backgroundColor: color }]} />
              <View style={styles.cardBody}>
                <View style={styles.cardTop}>
                  <View style={[styles.icon, { backgroundColor: color + '20' }]}>
                    <Ionicons name="book" size={18} color={color} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.asignatura}>{g.asignatura}</Text>
                    <Text style={styles.grupo}>{g.grado} {g.seccion}</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={Colors.muted} />
                </View>

                <View style={styles.chips}>
                  <View style={styles.chip}>
                    <Ionicons name="people" size={12} color={Colors.muted} />
                    <Text style={styles.chipTxt}>{g.total_estudiantes ?? '—'} est.</Text>
                  </View>
                  {g.aula && (
                    <View style={styles.chip}>
                      <Ionicons name="location" size={12} color={Colors.muted} />
                      <Text style={styles.chipTxt}>{g.aula}</Text>
                    </View>
                  )}
                  {g.turno && (
                    <View style={styles.chip}>
                      <Ionicons name="time" size={12} color={Colors.muted} />
                      <Text style={styles.chipTxt}>{g.turno}</Text>
                    </View>
                  )}
                </View>
              </View>
            </TouchableOpacity>
          )
        })}

        {!isLoading && grupos.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="book-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTxt}>No tienes grupos asignados.</Text>
          </View>
        )}
      </ScrollView>

      {/* Modal detalle del grupo */}
      <Modal visible={!!selected} animationType="slide" presentationStyle="pageSheet" onRequestClose={() => setSelected(null)}>
        {selected && <GrupoDetalle grupo={selected} onClose={() => setSelected(null)} />}
      </Modal>
    </SafeAreaView>
  )
}

function GrupoDetalle({ grupo: g, onClose }: { grupo: any; onClose: () => void }) {
  const { data, isLoading } = useQuery({
    queryKey: ['docente-asistencia', g.id],
    queryFn:  () => docenteApi.consultarAsistencia(g.id).then(r => r.data),
    enabled:  !!g.id,
  })

  const estudiantes: any[] = data?.estudiantes ?? g.estudiantes ?? []
  const stats = data?.stats ?? {}

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.modalHeader}>
        <View>
          <Text style={styles.modalTitle}>{g.asignatura}</Text>
          <Text style={styles.modalSub}>{g.grado} {g.seccion} · {g.total_estudiantes ?? estudiantes.length} estudiantes</Text>
        </View>
        <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
          <Ionicons name="close" size={22} color={Colors.text} />
        </TouchableOpacity>
      </View>

      {/* Stats rápidas */}
      {(stats.presente != null || stats.ausente != null) && (
        <View style={styles.statsRow}>
          {[
            { label: 'Presentes', value: stats.presente ?? 0, color: Colors.green },
            { label: 'Ausentes',  value: stats.ausente  ?? 0, color: Colors.red   },
            { label: 'Tardanzas', value: stats.tardanza ?? 0, color: Colors.amber  },
          ].map(s => (
            <View key={s.label} style={[styles.statBox, { borderTopColor: s.color }]}>
              <Text style={[styles.statNum, { color: s.color }]}>{s.value}</Text>
              <Text style={styles.statLbl}>{s.label}</Text>
            </View>
          ))}
        </View>
      )}

      {isLoading && <ActivityIndicator color={Colors.amber} style={{ margin: 24 }} />}

      <FlatList
        data={estudiantes}
        keyExtractor={(item, i) => String(item.id ?? i)}
        contentContainerStyle={{ padding: 16, gap: 8 }}
        renderItem={({ item }) => (
          <View style={styles.estudRow}>
            <View style={styles.avatar}>
              <Text style={styles.avatarTxt}>{(item.nombre ?? item.name ?? '?')[0].toUpperCase()}</Text>
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.estudNombre}>{item.nombre ?? item.name}</Text>
              {item.matricula && <Text style={styles.estudSub}>Matrícula: {item.matricula}</Text>}
            </View>
            {item.estado_hoy && (
              <View style={[styles.estadoBadge, { backgroundColor: estadoColor(item.estado_hoy) + '20' }]}>
                <Text style={[styles.estadoTxt, { color: estadoColor(item.estado_hoy) }]}>
                  {item.estado_hoy.charAt(0).toUpperCase() + item.estado_hoy.slice(1)}
                </Text>
              </View>
            )}
          </View>
        )}
        ListEmptyComponent={
          !isLoading ? <Text style={styles.emptyTxt}>Sin lista de estudiantes.</Text> : null
        }
      />
    </SafeAreaView>
  )
}

function estadoColor(e: string) {
  return e === 'presente' ? Colors.green : e === 'ausente' ? Colors.red : e === 'tardanza' ? Colors.amber : Colors.purple
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 32, gap: 12 },
  title:        { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  card:         { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden', shadowColor: '#000', shadowOpacity: .06, shadowRadius: 8, elevation: 3 },
  accentBar:    { width: 5 },
  cardBody:     { flex: 1, padding: 14, gap: 8 },
  cardTop:      { flexDirection: 'row', alignItems: 'center', gap: 10 },
  icon:         { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  asignatura:   { fontSize: 15, fontWeight: '800', color: Colors.text },
  grupo:        { fontSize: 12, color: Colors.muted },
  chips:        { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  chip:         { flexDirection: 'row', alignItems: 'center', gap: 4, backgroundColor: Colors.bg, borderRadius: 20, paddingHorizontal: 8, paddingVertical: 3 },
  chipTxt:      { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  empty:        { alignItems: 'center', gap: 12, paddingTop: 60 },
  emptyTxt:     { color: Colors.muted, textAlign: 'center' },
  // modal
  modalHeader:  { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 16, borderBottomWidth: 1, borderBottomColor: Colors.border },
  modalTitle:   { fontSize: 18, fontWeight: '900', color: Colors.text },
  modalSub:     { fontSize: 13, color: Colors.muted, marginTop: 2 },
  closeBtn:     { padding: 8 },
  statsRow:     { flexDirection: 'row', gap: 8, padding: 16, paddingBottom: 0 },
  statBox:      { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10, alignItems: 'center', borderTopWidth: 3, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  statNum:      { fontSize: 20, fontWeight: '900' },
  statLbl:      { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  estudRow:     { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 12, padding: 12, gap: 10, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  avatar:       { width: 38, height: 38, borderRadius: 19, backgroundColor: Colors.primary + '20', alignItems: 'center', justifyContent: 'center' },
  avatarTxt:    { fontSize: 16, fontWeight: '800', color: Colors.primary },
  estudNombre:  { fontSize: 14, fontWeight: '700', color: Colors.text },
  estudSub:     { fontSize: 11, color: Colors.muted },
  estadoBadge:  { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  estadoTxt:    { fontSize: 11, fontWeight: '700' },
})
