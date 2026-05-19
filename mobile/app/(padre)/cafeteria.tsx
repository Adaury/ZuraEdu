import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { cafeteriaApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = '#7c3aed'

export default function CafeteriaPadre() {
  const [hijoIdx, setHijoIdx] = useState(0)

  const { data: classroomData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: any[] = classroomData?.hijos ?? []
  const hijoActual = hijos[hijoIdx] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['cafeteria-hijo', hijoActual?.estudiante_id],
    queryFn:  () => cafeteriaApi.saldoHijo(hijoActual!.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
  })

  const saldo          = data?.saldo ?? 0
  const totalRecargado = data?.total_recargado ?? 0
  const totalGastado   = data?.total_gastado ?? 0
  const historial: any[]= data?.historial ?? []

  const fmt = (v: number) => `RD$ ${Number(v).toLocaleString('es-DO', { minimumFractionDigits: 2 })}`

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Cafetería Escolar</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false}
            contentContainerStyle={{ gap: 6, paddingHorizontal: 2 }}>
            {hijos.map((h: any, i: number) => (
              <TouchableOpacity
                key={h.estudiante_id}
                style={[styles.hijoBtn, hijoIdx === i && { backgroundColor: ACCENT, borderColor: ACCENT }]}
                onPress={() => setHijoIdx(i)}
              >
                <Text style={[styles.hijoText, hijoIdx === i && { color: '#fff' }]}>{h.nombre}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Header saldo */}
        <View style={styles.headerCard}>
          <Ionicons name="cafe" size={28} color="rgba(255,255,255,.8)" style={{ marginBottom: 6 }} />
          {hijoActual && (
            <Text style={{ color: 'rgba(255,255,255,.75)', fontSize: 12, fontWeight: '600', marginBottom: 4 }}>
              {hijoActual.nombre}
            </Text>
          )}
          <Text style={{ color: 'rgba(255,255,255,.8)', fontSize: 12, fontWeight: '600', marginBottom: 4 }}>SALDO ACTUAL</Text>
          <Text style={[styles.saldoNum, { color: saldo < 0 ? '#fca5a5' : '#fff' }]}>{fmt(saldo)}</Text>
        </View>

        {saldo < 0 && (
          <View style={styles.alertaCard}>
            <Ionicons name="alert-circle" size={18} color={Colors.red} />
            <View style={{ flex: 1 }}>
              <Text style={{ fontWeight: '700', color: '#991b1b', fontSize: 13 }}>Saldo insuficiente</Text>
              <Text style={{ color: '#7f1d1d', fontSize: 11 }}>Solicita una recarga para tu hijo/a.</Text>
            </View>
          </View>
        )}

        {/* Stats */}
        <View style={styles.statsRow}>
          {[
            { label: 'Total Recargado', value: fmt(totalRecargado), color: Colors.green, icon: 'arrow-up-circle' as const },
            { label: 'Total Gastado',   value: fmt(totalGastado),   color: Colors.red,   icon: 'bag'           as const },
          ].map(s => (
            <View key={s.label} style={styles.statCard}>
              <Ionicons name={s.icon} size={22} color={s.color} />
              <Text style={[styles.statNum, { color: s.color }]}>{s.value}</Text>
              <Text style={styles.statLabel}>{s.label}</Text>
            </View>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 20 }} />}

        {historial.length > 0 && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Historial de Movimientos</Text>
            {historial.map((h: any) => (
              <View key={h.id} style={styles.movRow}>
                <View style={[styles.movIcon, { backgroundColor: h.tipo === 'recarga' ? '#d1fae5' : '#fee2e2' }]}>
                  <Ionicons
                    name={h.tipo === 'recarga' ? 'arrow-up-circle' : 'bag'}
                    size={18}
                    color={h.tipo === 'recarga' ? Colors.green : Colors.red}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.movDesc}>{h.descripcion}</Text>
                  <Text style={styles.movFecha}>{h.fecha} · {h.hora}</Text>
                </View>
                <Text style={[styles.movMonto, { color: h.tipo === 'recarga' ? Colors.green : Colors.red }]}>
                  {h.tipo === 'recarga' ? '+' : '-'}{fmt(h.monto)}
                </Text>
              </View>
            ))}
          </View>
        )}

        {!isLoading && !hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="person-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay hijos registrados.</Text>
          </View>
        )}
        {!isLoading && hijoActual && historial.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="receipt-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay movimientos registrados.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, paddingBottom: 32, gap: 12 },
  title:       { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoBtn:     { borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6,
                 backgroundColor: '#f1f5f9', borderWidth: 1, borderColor: Colors.border },
  hijoText:    { fontSize: 12, fontWeight: '700', color: Colors.text },
  headerCard:  { backgroundColor: ACCENT, borderRadius: 16, padding: 24, alignItems: 'center' },
  saldoNum:    { fontSize: 32, fontWeight: '900' },
  alertaCard:  { flexDirection: 'row', alignItems: 'center', gap: 10, backgroundColor: '#fef2f2',
                 borderRadius: 12, padding: 12, borderWidth: 1, borderColor: '#fca5a5' },
  statsRow:    { flexDirection: 'row', gap: 10 },
  statCard:    { flex: 1, backgroundColor: '#fff', borderRadius: 14, padding: 14,
                 alignItems: 'center', gap: 4,
                 shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  statNum:     { fontSize: 14, fontWeight: '900' },
  statLabel:   { fontSize: 10, color: Colors.muted, fontWeight: '600', textAlign: 'center' },
  section:     { backgroundColor: '#fff', borderRadius: 14, overflow: 'hidden',
                 shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  sectionTitle:{ fontSize: 13, fontWeight: '800', color: Colors.text,
                 padding: 14, borderBottomWidth: 1, borderBottomColor: Colors.border },
  movRow:      { flexDirection: 'row', alignItems: 'center', gap: 12,
                 padding: 12, borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  movIcon:     { width: 38, height: 38, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  movDesc:     { fontSize: 13, fontWeight: '600', color: Colors.text },
  movFecha:    { fontSize: 11, color: Colors.muted },
  movMonto:    { fontSize: 14, fontWeight: '900' },
  centered:    { alignItems: 'center', paddingVertical: 48, gap: 10 },
  empty:       { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
