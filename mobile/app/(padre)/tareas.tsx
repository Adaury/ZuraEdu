import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { tareasApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

type Filtro = 'todas' | 'pendiente' | 'entregada' | 'vencida'

const estadoConfig: Record<string, { color: string; icon: any; label: string }> = {
  pendiente: { color: Colors.amber, icon: 'hourglass-outline', label: 'Pendiente' },
  entregada: { color: Colors.green, icon: 'checkmark-circle',  label: 'Entregada' },
  revisada:  { color: '#6366f1',    icon: 'ribbon',            label: 'Revisada' },
  vencida:   { color: Colors.red,   icon: 'alert-circle',      label: 'Vencida' },
}

export default function TareasPadre() {
  const [hijoIdx, setHijoIdx] = useState(0)
  const [filtro, setFiltro]   = useState<Filtro>('todas')

  const { data: classroomData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: any[] = classroomData?.hijos ?? []
  const hijoActual = hijos[hijoIdx] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['tareas-hijo', hijoActual?.estudiante_id],
    queryFn:  () => tareasApi.hijo(hijoActual!.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
  })

  const tareas: any[] = data?.tareas ?? []
  const resumen: any  = data?.resumen ?? {}

  const filtradas = filtro === 'todas'
    ? tareas
    : filtro === 'entregada'
      ? tareas.filter(t => ['entregada', 'revisada'].includes(t.estado))
      : tareas.filter(t => t.estado === filtro)

  const FILTROS: { key: Filtro; label: string; count: number }[] = [
    { key: 'todas',     label: 'Todas',      count: tareas.length },
    { key: 'pendiente', label: 'Pendientes', count: resumen.pendientes ?? 0 },
    { key: 'entregada', label: 'Entregadas', count: resumen.entregadas ?? 0 },
    { key: 'vencida',   label: 'Vencidas',   count: resumen.vencidas ?? 0 },
  ]

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Tareas del Hijo</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 4 }}
            contentContainerStyle={{ gap: 6, paddingHorizontal: 2 }}>
            {hijos.map((h: any, i: number) => (
              <TouchableOpacity
                key={h.estudiante_id}
                style={[styles.hijoBtn, hijoIdx === i && { backgroundColor: ACCENT, borderColor: ACCENT }]}
                onPress={() => { setHijoIdx(i); setFiltro('todas') }}
              >
                <Text style={[styles.hijoText, hijoIdx === i && { color: '#fff' }]}>{h.nombre}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Resumen */}
        {((resumen.pendientes ?? 0) > 0 || (resumen.vencidas ?? 0) > 0) && (
          <View style={styles.badgeRow}>
            {(resumen.pendientes ?? 0) > 0 && (
              <View style={[styles.badge, { backgroundColor: Colors.amber }]}>
                <Text style={styles.badgeText}>{resumen.pendientes} pendiente{resumen.pendientes !== 1 ? 's' : ''}</Text>
              </View>
            )}
            {(resumen.vencidas ?? 0) > 0 && (
              <View style={[styles.badge, { backgroundColor: Colors.red }]}>
                <Text style={styles.badgeText}>{resumen.vencidas} vencida{resumen.vencidas !== 1 ? 's' : ''}</Text>
              </View>
            )}
          </View>
        )}

        {/* Filtros */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 4 }}
          contentContainerStyle={{ gap: 6, paddingHorizontal: 2 }}>
          {FILTROS.map(f => (
            <TouchableOpacity
              key={f.key}
              style={[styles.filtroBtn, filtro === f.key && { backgroundColor: ACCENT, borderColor: ACCENT }]}
              onPress={() => setFiltro(f.key)}
            >
              <Text style={[styles.filtroText, filtro === f.key && { color: '#fff' }]}>
                {f.label}{f.count > 0 ? ` (${f.count})` : ''}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 30 }} />}

        {filtradas.map((t: any) => {
          const cfg = estadoConfig[t.estado] ?? estadoConfig.pendiente
          return (
            <View key={t.id} style={[styles.card, { borderLeftColor: t.tipo_color ?? Colors.blue }]}>
              <View style={{ flex: 1 }}>
                <View style={styles.cardHeader}>
                  <Text style={[styles.tipoBadge, { backgroundColor: t.tipo_color ?? Colors.blue }]}>{t.tipo_label}</Text>
                  <Text style={styles.asignatura}>{t.asignatura}</Text>
                </View>
                <Text style={styles.cardTitle}>{t.titulo}</Text>
                {!!t.descripcion && <Text style={styles.cardDesc} numberOfLines={2}>{t.descripcion}</Text>}
                <View style={styles.metaRow}>
                  <Ionicons name="calendar-outline" size={12} color={Colors.muted} />
                  <Text style={styles.metaText}>Límite: {t.fecha_limite}</Text>
                  {t.puntos_valor && <Text style={styles.metaText}>· {t.puntos_valor} pts</Text>}
                </View>
                {t.nota_entrega != null && (
                  <Text style={{ fontSize: 12, color: '#6366f1', fontWeight: '700', marginTop: 2 }}>
                    Nota: {t.nota_entrega}
                  </Text>
                )}
              </View>
              <View style={{ alignItems: 'center', gap: 4 }}>
                <Ionicons name={cfg.icon} size={22} color={cfg.color} />
                <Text style={[styles.estadoText, { color: cfg.color }]}>{cfg.label}</Text>
                {t.estado === 'pendiente' && t.dias_restantes != null && (
                  <View style={[styles.diasBadge,
                    { backgroundColor: t.dias_restantes <= 1 ? Colors.red : t.dias_restantes <= 3 ? Colors.amber : '#e0f2fe' }]}>
                    <Text style={{ fontSize: 9, fontWeight: '800',
                      color: t.dias_restantes <= 3 ? '#fff' : '#0369a1' }}>
                      {t.dias_restantes === 0 ? 'Hoy' : `${t.dias_restantes}d`}
                    </Text>
                  </View>
                )}
              </View>
            </View>
          )
        })}

        {!isLoading && !hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="person-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay hijos registrados.</Text>
          </View>
        )}
        {!isLoading && hijoActual && filtradas.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="checkmark-done-circle-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay tareas en esta categoría.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 32, gap: 8 },
  title:        { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoBtn:      { borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6,
                  backgroundColor: '#f1f5f9', borderWidth: 1, borderColor: Colors.border },
  hijoText:     { fontSize: 12, fontWeight: '700', color: Colors.text },
  badgeRow:     { flexDirection: 'row', gap: 6 },
  badge:        { flexDirection: 'row', alignItems: 'center', gap: 4, borderRadius: 99,
                  paddingHorizontal: 10, paddingVertical: 4 },
  badgeText:    { color: '#fff', fontSize: 11, fontWeight: '700' },
  filtroBtn:    { borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6,
                  backgroundColor: '#f1f5f9', borderWidth: 1, borderColor: Colors.border },
  filtroText:   { fontSize: 12, fontWeight: '700', color: Colors.text },
  card:         { backgroundColor: '#fff', borderRadius: 14, padding: 14, borderLeftWidth: 4,
                  flexDirection: 'row', alignItems: 'center', gap: 12,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardHeader:   { flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 4 },
  tipoBadge:    { borderRadius: 99, paddingHorizontal: 7, paddingVertical: 2,
                  fontSize: 9, fontWeight: '800', color: '#fff', overflow: 'hidden' },
  asignatura:   { fontSize: 11, color: Colors.muted, fontWeight: '600' },
  cardTitle:    { fontSize: 14, fontWeight: '800', color: Colors.text, marginBottom: 2 },
  cardDesc:     { fontSize: 12, color: Colors.muted, marginBottom: 4 },
  metaRow:      { flexDirection: 'row', alignItems: 'center', gap: 4 },
  metaText:     { fontSize: 11, color: Colors.muted },
  estadoText:   { fontSize: 9, fontWeight: '800' },
  diasBadge:    { borderRadius: 6, paddingHorizontal: 5, paddingVertical: 2 },
  centered:     { alignItems: 'center', paddingVertical: 48, gap: 10 },
  empty:        { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
