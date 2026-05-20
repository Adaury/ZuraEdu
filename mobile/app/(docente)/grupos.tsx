import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, TouchableOpacity,
  ActivityIndicator, Modal, FlatList, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useRouter } from 'expo-router'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

export default function GruposDocente() {
  const [selected, setSelected] = useState<any>(null)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['docente-grupos'],
    queryFn:  () => docenteApi.grupos().then(r => r.data),
    staleTime: 60_000,
  })

  const grupos: any[]  = data?.data      ?? []
  const docente        = data?.docente    ?? null
  const schoolYear     = data?.school_year ?? null

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* Header */}
        <View style={styles.header}>
          <View>
            <Text style={styles.title}>Mis Grupos</Text>
            {!!schoolYear && <Text style={styles.schoolYear}>{schoolYear}</Text>}
          </View>
          {grupos.length > 0 && (
            <View style={[styles.badge, { backgroundColor: ACCENT + '18' }]}>
              <Text style={[styles.badgeTxt, { color: ACCENT }]}>{grupos.length} grupos</Text>
            </View>
          )}
        </View>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

        {grupos.map((g: any) => {
          const color = g.color ?? ACCENT
          return (
            <TouchableOpacity
              key={g.id}
              style={styles.card}
              onPress={() => setSelected(g)}
              activeOpacity={0.85}
            >
              <View style={[styles.accentBar, { backgroundColor: color }]} />
              <View style={styles.cardBody}>
                <View style={styles.cardTop}>
                  <View style={[styles.icon, { backgroundColor: color + '20' }]}>
                    <Ionicons name="book" size={18} color={color} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.asignatura}>{g.asignatura}</Text>
                    <Text style={styles.grupo}>{g.grado} · {g.seccion}</Text>
                  </View>
                  <View style={styles.countBadge}>
                    <Ionicons name="people" size={12} color={Colors.muted} />
                    <Text style={styles.countTxt}>{g.total_estudiantes ?? 0}</Text>
                  </View>
                  <Ionicons name="chevron-forward" size={16} color={Colors.muted} />
                </View>

                {/* Info chips */}
                <View style={styles.cardActions}>
                  <View style={[styles.actionChip, { backgroundColor: Colors.green + '15', borderColor: Colors.green + '30' }]}>
                    <Ionicons name="checkmark-circle-outline" size={13} color={Colors.green} />
                    <Text style={[styles.actionChipTxt, { color: Colors.green }]}>Asistencia</Text>
                  </View>
                  <View style={[styles.actionChip, { backgroundColor: Colors.blue + '15', borderColor: Colors.blue + '30' }]}>
                    <Ionicons name="bar-chart-outline" size={13} color={Colors.blue} />
                    <Text style={[styles.actionChipTxt, { color: Colors.blue }]}>Calificaciones</Text>
                  </View>
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

      <Modal
        visible={!!selected}
        animationType="slide"
        presentationStyle="pageSheet"
        onRequestClose={() => setSelected(null)}
      >
        {selected && (
          <GrupoDetalle
            grupo={selected}
            onClose={() => setSelected(null)}
          />
        )}
      </Modal>
    </SafeAreaView>
  )
}


function GrupoDetalle({ grupo: g, onClose }: { grupo: any; onClose: () => void }) {
  const router = useRouter()
  const color  = g.color ?? ACCENT

  const { data, isLoading } = useQuery({
    queryKey: ['docente-asistencia-hoy', g.asignacion_id],
    queryFn:  () => docenteApi.consultarAsistencia(g.asignacion_id).then(r => r.data),
    enabled:  !!g.asignacion_id,
    staleTime: 30_000,
  })

  // La API de consultarAsistencia devuelve {estudiantes:[{matricula_id, nombre, estado_hoy}], stats:{presente, ausente, tardanza}}
  // Fallback: g.alumnos ya viene en la respuesta de grupos
  const estudiantes: any[] = data?.estudiantes ?? g.alumnos ?? []
  const stats = data?.stats ?? null

  const navigateTo = (route: string) => {
    onClose()
    setTimeout(() => router.push(route as any), 300)
  }

  return (
    <SafeAreaView style={styles.safe}>
      {/* Header del modal */}
      <View style={[styles.modalHeader, { borderBottomColor: color + '30' }]}>
        <View style={[styles.modalAccent, { backgroundColor: color }]} />
        <View style={{ flex: 1 }}>
          <Text style={styles.modalTitle}>{g.asignatura}</Text>
          <Text style={styles.modalSub}>
            {g.grado} · {g.seccion} · {g.total_estudiantes ?? estudiantes.length} estudiantes
          </Text>
        </View>
        <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
          <Ionicons name="close-circle" size={26} color={Colors.muted} />
        </TouchableOpacity>
      </View>

      {/* Botones de navegación */}
      <View style={styles.navRow}>
        <TouchableOpacity
          style={[styles.navBtn, { backgroundColor: Colors.green + '15', borderColor: Colors.green + '40' }]}
          activeOpacity={0.8}
          onPress={() => navigateTo('/(docente)/asistencia')}
        >
          <Ionicons name="checkmark-circle" size={20} color={Colors.green} />
          <Text style={[styles.navBtnTxt, { color: Colors.green }]}>Tomar Asistencia</Text>
        </TouchableOpacity>
        <TouchableOpacity
          style={[styles.navBtn, { backgroundColor: Colors.blue + '15', borderColor: Colors.blue + '40' }]}
          activeOpacity={0.8}
          onPress={() => navigateTo('/(docente)/calificaciones')}
        >
          <Ionicons name="bar-chart" size={20} color={Colors.blue} />
          <Text style={[styles.navBtnTxt, { color: Colors.blue }]}>Calificaciones</Text>
        </TouchableOpacity>
      </View>

      {/* Stats de asistencia de hoy */}
      {stats && (
        <View style={styles.statsRow}>
          {[
            { label: 'Presentes', value: stats.presente ?? 0, color: Colors.green },
            { label: 'Ausentes',  value: stats.ausente  ?? 0, color: Colors.red   },
            { label: 'Tardanzas', value: stats.tardanza  ?? 0, color: Colors.amber },
          ].map(s => (
            <View key={s.label} style={[styles.statBox, { borderTopColor: s.color }]}>
              <Text style={[styles.statNum, { color: s.color }]}>{s.value}</Text>
              <Text style={styles.statLbl}>{s.label}</Text>
            </View>
          ))}
        </View>
      )}

      {isLoading && <ActivityIndicator color={color} style={{ margin: 24 }} />}

      {/* Lista de estudiantes */}
      <FlatList
        data={estudiantes}
        keyExtractor={(item, i) => String(item.matricula_id ?? item.id ?? i)}
        contentContainerStyle={{ padding: 16, gap: 8, paddingBottom: 32 }}
        showsVerticalScrollIndicator={false}
        renderItem={({ item, index }) => {
          const nombre = item.nombre ?? item.name ?? '?'
          const estado = item.estado_hoy ?? null
          return (
            <View style={styles.estudRow}>
              <View style={[styles.avatar, { backgroundColor: color + '20' }]}>
                <Text style={[styles.avatarTxt, { color }]}>
                  {nombre[0].toUpperCase()}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.estudNombre}>{nombre}</Text>
                <Text style={styles.estudSub}>#{index + 1}</Text>
              </View>
              {estado ? (
                <View style={[styles.estadoBadge, { backgroundColor: estadoColor(estado) + '20' }]}>
                  <Text style={[styles.estadoTxt, { color: estadoColor(estado) }]}>
                    {estado.charAt(0).toUpperCase() + estado.slice(1)}
                  </Text>
                </View>
              ) : null}
            </View>
          )
        }}
        ListEmptyComponent={
          !isLoading ? (
            <View style={styles.empty}>
              <Text style={styles.emptyTxt}>Sin lista de estudiantes.</Text>
            </View>
          ) : null
        }
      />
    </SafeAreaView>
  )
}

function estadoColor(e: string) {
  if (e === 'presente')  return Colors.green
  if (e === 'ausente')   return Colors.red
  if (e === 'tardanza')  return Colors.amber
  return Colors.purple
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, paddingBottom: 32, gap: 12 },

  // Header
  header:        { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 4 },
  title:         { fontSize: 22, fontWeight: '900', color: Colors.text },
  schoolYear:    { fontSize: 12, color: Colors.muted, marginTop: 2 },
  badge:         { borderRadius: 20, paddingHorizontal: 10, paddingVertical: 4, alignSelf: 'flex-start' },
  badgeTxt:      { fontSize: 12, fontWeight: '700' },

  // Card
  card:          { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden',
                   shadowColor: '#000', shadowOpacity: .06, shadowRadius: 8, elevation: 3 },
  accentBar:     { width: 5 },
  cardBody:      { flex: 1, padding: 14, gap: 10 },
  cardTop:       { flexDirection: 'row', alignItems: 'center', gap: 10 },
  icon:          { width: 38, height: 38, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  asignatura:    { fontSize: 15, fontWeight: '800', color: Colors.text },
  grupo:         { fontSize: 12, color: Colors.muted, marginTop: 1 },
  countBadge:    { flexDirection: 'row', alignItems: 'center', gap: 3 },
  countTxt:      { fontSize: 12, color: Colors.muted, fontWeight: '600' },

  // Action chips en card
  cardActions:   { flexDirection: 'row', gap: 8 },
  actionChip:    { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1,
                   borderRadius: 20, paddingHorizontal: 10, paddingVertical: 4 },
  actionChipTxt: { fontSize: 11, fontWeight: '700' },

  // Empty
  empty:         { alignItems: 'center', gap: 12, paddingTop: 40 },
  emptyTxt:      { color: Colors.muted, textAlign: 'center' },

  // Modal
  modalHeader:   { flexDirection: 'row', alignItems: 'center', gap: 12, padding: 16,
                   borderBottomWidth: 1 },
  modalAccent:   { width: 4, height: 40, borderRadius: 4 },
  modalTitle:    { fontSize: 18, fontWeight: '900', color: Colors.text },
  modalSub:      { fontSize: 12, color: Colors.muted, marginTop: 2 },
  closeBtn:      { padding: 4 },

  // Nav buttons
  navRow:        { flexDirection: 'row', gap: 10, padding: 14 },
  navBtn:        { flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                   gap: 6, borderWidth: 1, borderRadius: 12, paddingVertical: 10 },
  navBtnTxt:     { fontSize: 13, fontWeight: '700' },

  // Stats
  statsRow:      { flexDirection: 'row', gap: 8, paddingHorizontal: 14, paddingBottom: 8 },
  statBox:       { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10,
                   alignItems: 'center', borderTopWidth: 3,
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  statNum:       { fontSize: 20, fontWeight: '900' },
  statLbl:       { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },

  // Estudiantes
  estudRow:      { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff',
                   borderRadius: 12, padding: 12, gap: 10,
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  avatar:        { width: 38, height: 38, borderRadius: 19, alignItems: 'center', justifyContent: 'center' },
  avatarTxt:     { fontSize: 16, fontWeight: '800' },
  estudNombre:   { fontSize: 14, fontWeight: '700', color: Colors.text },
  estudSub:      { fontSize: 11, color: Colors.muted },
  estadoBadge:   { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  estadoTxt:     { fontSize: 11, fontWeight: '700' },
})
