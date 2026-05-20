import React, { useState, useEffect } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity, RefreshControl } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { pagosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

const estadoConfig: Record<string, { color: string; icon: any }> = {
  pagado:    { color: Colors.green, icon: 'checkmark-circle' },
  pendiente: { color: Colors.amber, icon: 'time' },
  vencido:   { color: Colors.red,   icon: 'alert-circle' },
}

export default function PagosPadre() {
  const [hijoIdx, setHijoIdx] = useState(0)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['pagos-padre'],
    queryFn:  () => pagosApi.index().then(r => r.data),
  })

  const hijos: any[] = data?.hijos ?? []

  useEffect(() => {
    if (hijoIdx >= hijos.length && hijos.length > 0) setHijoIdx(0)
  }, [hijos.length])

  const hijoData        = hijos[hijoIdx] ?? null
  const pagos: any[]    = hijoData?.pagos ?? []

  const cobrado   = pagos.filter(p => p.estado === 'pagado').reduce((s, p) => s + Number(p.monto), 0)
  const pendiente = pagos.filter(p => p.estado === 'pendiente').reduce((s, p) => s + Number(p.monto), 0)
  const vencido   = pagos.filter(p => p.vencido).reduce((s, p) => s + (Number(p.monto) - Number(p.monto_pagado ?? 0)), 0)

  const fmtMoney = (v: number) => `$${v.toLocaleString('es-DO')}`

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Estado de Cuenta</Text>

        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.hijoRow} contentContainerStyle={{ gap: 8 }}>
            {hijos.map((h: any, i: number) => (
              <TouchableOpacity
                key={h.estudiante_id}
                onPress={() => setHijoIdx(i)}
                style={[styles.hijoPill, hijoIdx === i && styles.hijoPillActive]}
              >
                <Ionicons name="person" size={12} color={hijoIdx === i ? '#fff' : Colors.muted} />
                <Text style={[styles.hijoPillTxt, hijoIdx === i && styles.hijoPillTxtActive]}>
                  {h.estudiante?.split(' ')[0] ?? `Hijo ${i + 1}`}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Resumen */}
        <View style={styles.resumenRow}>
          {[
            { label: 'Cobrado',   value: fmtMoney(cobrado),   color: Colors.green },
            { label: 'Pendiente', value: fmtMoney(pendiente), color: Colors.amber },
            { label: 'Vencido',   value: fmtMoney(vencido),   color: Colors.red   },
          ].map(r => (
            <View key={r.label} style={[styles.resBox, { borderTopColor: r.color }]}>
              <Text style={[styles.resNum, { color: r.color }]}>{r.value}</Text>
              <Text style={styles.resLbl}>{r.label}</Text>
            </View>
          ))}
        </View>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 30 }} />}

        {pagos.map((p: any, i: number) => {
          const cfg  = estadoConfig[p.estado] ?? { color: Colors.muted, icon: 'help-circle' }
          const real = p.vencido ? { ...cfg, color: Colors.red, icon: 'alert-circle' } : cfg
          return (
            <View key={i} style={styles.row}>
              <Ionicons name={real.icon} size={24} color={real.color} />
              <View style={{ flex: 1 }}>
                <Text style={styles.concepto}>{p.concepto}</Text>
                <Text style={styles.fecha}>{p.fecha_vencimiento ?? p.fecha_pago ?? '—'}</Text>
              </View>
              <View style={{ alignItems: 'flex-end' }}>
                <Text style={[styles.monto, { color: real.color }]}>{fmtMoney(Number(p.monto))}</Text>
                <Text style={[styles.estado, { color: real.color }]}>{p.estado_label ?? p.estado}</Text>
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
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, paddingBottom: 32, gap: 8 },
  title:             { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoRow:           { marginBottom: 4 },
  hijoPill:          { flexDirection: 'row', alignItems: 'center', gap: 4, borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99, paddingHorizontal: 12, paddingVertical: 6 },
  hijoPillActive:    { backgroundColor: ACCENT, borderColor: ACCENT },
  hijoPillTxt:       { fontSize: 13, fontWeight: '700', color: Colors.muted },
  hijoPillTxtActive: { color: '#fff' },
  resumenRow:        { flexDirection: 'row', gap: 8 },
  resBox:            { flex: 1, backgroundColor: '#fff', borderRadius: 12, padding: 10, alignItems: 'center', borderTopWidth: 3, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 5, elevation: 2 },
  resNum:            { fontSize: 13, fontWeight: '900' },
  resLbl:            { fontSize: 10, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  centered:          { alignItems: 'center', paddingVertical: 48, gap: 10 },
  row:               { flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', borderRadius: 14, padding: 14, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  concepto:          { fontSize: 14, fontWeight: '700', color: Colors.text },
  fecha:             { fontSize: 12, color: Colors.muted },
  monto:             { fontSize: 15, fontWeight: '900' },
  estado:            { fontSize: 11, fontWeight: '700' },
  empty:             { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
