import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { pagosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const estadoConfig: Record<string, { color: string; icon: any }> = {
  pagado:    { color: Colors.green, icon: 'checkmark-circle' },
  pendiente: { color: Colors.amber, icon: 'time' },
  vencido:   { color: Colors.red,   icon: 'alert-circle' },
}

export default function PagosEstudiante() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['pagos-estudiante'],
    queryFn:  () => pagosApi.index().then(r => r.data),
  })

  const pagos: any[] = Array.isArray(data?.pagos) ? data.pagos : Array.isArray(data) ? data : []
  const resumen      = data?.resumen ?? {}

  const fmtMoney = (v: any) => {
    if (v == null) return '—'
    const n = Number(v)
    return `$${(isNaN(n) ? 0 : n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        <Text style={styles.title}>Mis Pagos</Text>

        <View style={styles.resumenRow}>
          {[
            { label: 'Cobrado',   value: fmtMoney(resumen.cobrado),   color: Colors.green },
            { label: 'Pendiente', value: fmtMoney(resumen.pendiente), color: Colors.amber },
            { label: 'Vencido',   value: fmtMoney(resumen.vencido),   color: Colors.red   },
          ].map(r => (
            <View key={r.label} style={[styles.resBox, { borderTopColor: r.color }]}>
              <Text style={[styles.resNum, { color: r.color }]}>{r.value}</Text>
              <Text style={styles.resLbl}>{r.label}</Text>
            </View>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 30 }} />}

        {pagos.map((p: any, i: number) => {
          const cfg = estadoConfig[p.estado] ?? { color: Colors.muted, icon: 'help-circle' }
          return (
            <View key={i} style={styles.row}>
              <Ionicons name={cfg.icon} size={24} color={cfg.color} />
              <View style={{ flex: 1 }}>
                <Text style={styles.concepto}>{p.concepto}</Text>
                <Text style={styles.fecha}>{p.fecha ?? p.fecha_vencimiento}</Text>
              </View>
              <View style={{ alignItems: 'flex-end' }}>
                <Text style={[styles.monto, { color: cfg.color }]}>{fmtMoney(p.monto)}</Text>
                <Text style={[styles.estado, { color: cfg.color }]}>
                  {p.estado ? p.estado.charAt(0).toUpperCase() + p.estado.slice(1) : ''}
                </Text>
              </View>
            </View>
          )
        })}

        {!isLoading && pagos.length === 0 && (
          <View style={styles.centered}>
            <Ionicons name="card-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay registros de pagos.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:       { flex: 1, backgroundColor: Colors.bg },
  content:    { padding: 16, paddingBottom: 32, gap: 8 },
  title:      { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  centered:   { alignItems: 'center', paddingVertical: 48, gap: 10 },
  resumenRow: { flexDirection: 'row', gap: 8, marginBottom: 4 },
  resBox:     { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10, alignItems: 'center',
                borderTopWidth: 3, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  resNum:     { fontSize: 14, fontWeight: '900' },
  resLbl:     { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  row:        { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff',
                borderRadius: 14, padding: 14, gap: 12,
                shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  concepto:   { fontSize: 14, fontWeight: '700', color: Colors.text },
  fecha:      { fontSize: 12, color: Colors.muted },
  monto:      { fontSize: 15, fontWeight: '900' },
  estado:     { fontSize: 11, fontWeight: '700' },
  empty:      { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
