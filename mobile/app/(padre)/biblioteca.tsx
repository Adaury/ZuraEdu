import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useLocalSearchParams } from 'expo-router'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { bibliotecaApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

function estadoColor(estado: string, vencido: boolean) {
  if (vencido || estado === 'vencido') return Colors.red
  if (estado === 'devuelto') return Colors.green
  return Colors.blue
}

export default function BibliotecaPadre() {
  const { id } = useLocalSearchParams<{ id: string }>()
  const hijoId = id ? parseInt(id) : 0
  const [tab, setTab] = useState<'activos' | 'historial'>('activos')

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['biblioteca-hijo', hijoId],
    queryFn:  () => bibliotecaApi.hijo(hijoId).then(r => r.data),
    staleTime: 60_000,
    enabled:  !!hijoId,
  })

  const activos:   any[] = data?.activos   ?? []
  const historial: any[] = data?.historial ?? []
  const hijo = data?.estudiante
  const lista = tab === 'activos' ? activos : historial

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.indigo} />}
      >
        {!!hijo && (
          <View style={styles.hijoCard}>
            <View style={styles.hijoAvatar}>
              <Text style={styles.hijoAvatarTxt}>{hijo.nombre.charAt(0).toUpperCase()}</Text>
            </View>
            <Text style={styles.hijoNombre}>{hijo.nombre}</Text>
          </View>
        )}

        <View style={styles.kpiRow}>
          <View style={[styles.kpi, { backgroundColor: '#eff6ff' }]}>
            <Text style={[styles.kpiVal, { color: Colors.blue }]}>{activos.length}</Text>
            <Text style={styles.kpiLbl}>Activos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#fef2f2' }]}>
            <Text style={[styles.kpiVal, { color: Colors.red }]}>
              {activos.filter(p => p.esta_vencido).length}
            </Text>
            <Text style={styles.kpiLbl}>Vencidos</Text>
          </View>
          <View style={[styles.kpi, { backgroundColor: '#f0fdf4' }]}>
            <Text style={[styles.kpiVal, { color: Colors.green }]}>{historial.length}</Text>
            <Text style={styles.kpiLbl}>Devueltos</Text>
          </View>
        </View>

        <View style={styles.tabs}>
          <TouchableOpacity
            style={[styles.tab, tab === 'activos' && styles.tabActive]}
            onPress={() => setTab('activos')}
          >
            <Text style={[styles.tabTxt, tab === 'activos' && styles.tabTxtActive]}>
              Activos ({activos.length})
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.tab, tab === 'historial' && styles.tabActive]}
            onPress={() => setTab('historial')}
          >
            <Text style={[styles.tabTxt, tab === 'historial' && styles.tabTxtActive]}>
              Historial ({historial.length})
            </Text>
          </TouchableOpacity>
        </View>

        {isLoading && [0, 1].map(i => <View key={i} style={styles.skeleton} />)}

        {lista.map((p: any) => {
          const color = estadoColor(p.estado, p.esta_vencido)
          return (
            <View key={p.id} style={styles.card}>
              <View style={styles.cardHeader}>
                <View style={[styles.bookIcon, { backgroundColor: color + '18' }]}>
                  <Ionicons name="book" size={18} color={color} />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.titulo}>{p.libro_titulo ?? '—'}</Text>
                  {!!p.libro_autor && <Text style={styles.autor}>{p.libro_autor}</Text>}
                </View>
                <View style={[styles.badge, { backgroundColor: color + '18' }]}>
                  <Text style={[styles.badgeTxt, { color }]}>
                    {p.esta_vencido ? 'Vencido' : p.estado === 'devuelto' ? 'Devuelto' : 'Activo'}
                  </Text>
                </View>
              </View>

              {!!p.libro_categoria && (
                <View style={styles.catRow}>
                  <Ionicons name="pricetag" size={12} color={Colors.muted} />
                  <Text style={styles.catTxt}>{p.libro_categoria}</Text>
                </View>
              )}

              <View style={styles.fechas}>
                <View style={styles.fechaItem}>
                  <Text style={styles.fechaLbl}>Préstamo</Text>
                  <Text style={styles.fechaVal}>{p.fecha_prestamo ?? '—'}</Text>
                </View>
                {p.estado !== 'devuelto' && (
                  <View style={styles.fechaItem}>
                    <Text style={styles.fechaLbl}>Vencimiento</Text>
                    <Text style={[styles.fechaVal, p.esta_vencido && { color: Colors.red }]}>
                      {p.fecha_vencimiento ?? '—'}
                    </Text>
                  </View>
                )}
                {p.estado === 'devuelto' && (
                  <View style={styles.fechaItem}>
                    <Text style={styles.fechaLbl}>Devuelto</Text>
                    <Text style={[styles.fechaVal, { color: Colors.green }]}>{p.fecha_devolucion ?? '—'}</Text>
                  </View>
                )}
                {p.estado !== 'devuelto' && p.dias_restantes != null && (
                  <View style={styles.fechaItem}>
                    <Text style={styles.fechaLbl}>Días restantes</Text>
                    <Text style={[styles.fechaVal, { color }]}>
                      {p.dias_restantes >= 0 ? p.dias_restantes : `+${Math.abs(p.dias_restantes)} vencido`}
                    </Text>
                  </View>
                )}
              </View>
            </View>
          )
        })}

        {!isLoading && lista.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="library-outline" size={40} color={Colors.border} />
            <Text style={styles.emptyTxt}>
              {tab === 'activos' ? 'No hay préstamos activos.' : 'No hay historial de préstamos.'}
            </Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 12, paddingBottom: 32 },
  hijoCard:       { flexDirection: 'row', alignItems: 'center', gap: 12,
                    backgroundColor: '#fff', borderRadius: 16, padding: 14,
                    shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  hijoAvatar:     { width: 42, height: 42, borderRadius: 13, backgroundColor: Colors.roles.padre + '18',
                    alignItems: 'center', justifyContent: 'center' },
  hijoAvatarTxt:  { fontSize: 18, fontWeight: '900', color: Colors.roles.padre },
  hijoNombre:     { fontSize: 15, fontWeight: '800', color: Colors.text },
  kpiRow:         { flexDirection: 'row', gap: 10 },
  kpi:            { flex: 1, borderRadius: 14, padding: 12, alignItems: 'center' },
  kpiVal:         { fontSize: 22, fontWeight: '900' },
  kpiLbl:         { fontSize: 10, fontWeight: '600', color: Colors.muted, marginTop: 2, textAlign: 'center' },
  tabs:           { flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, padding: 4,
                    shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 1 },
  tab:            { flex: 1, paddingVertical: 8, borderRadius: 10, alignItems: 'center' },
  tabActive:      { backgroundColor: Colors.indigo },
  tabTxt:         { fontSize: 12, fontWeight: '700', color: Colors.muted },
  tabTxtActive:   { color: '#fff' },
  card:           { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10,
                    shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader:     { flexDirection: 'row', alignItems: 'center', gap: 10 },
  bookIcon:       { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  titulo:         { fontSize: 14, fontWeight: '800', color: Colors.text },
  autor:          { fontSize: 11, color: Colors.muted, marginTop: 2 },
  badge:          { borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  badgeTxt:       { fontSize: 10, fontWeight: '700' },
  catRow:         { flexDirection: 'row', alignItems: 'center', gap: 4 },
  catTxt:         { fontSize: 11, color: Colors.muted, textTransform: 'capitalize' },
  fechas:         { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  fechaItem:      { gap: 2 },
  fechaLbl:       { fontSize: 10, color: Colors.muted, fontWeight: '600' },
  fechaVal:       { fontSize: 12, fontWeight: '700', color: Colors.text },
  skeleton:       { height: 100, borderRadius: 16, backgroundColor: Colors.border },
  empty:          { alignItems: 'center', gap: 10, paddingVertical: 40 },
  emptyTxt:       { color: Colors.muted, fontSize: 13 },
})
