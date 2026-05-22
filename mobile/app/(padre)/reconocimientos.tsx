import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useLocalSearchParams } from 'expo-router'
import { reconocimientosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPO_COLORS: Record<string, string> = {
  excelencia: '#d97706',
  deportivo:  '#16a34a',
  arte:       '#db2777',
  liderazgo:  '#7c3aed',
  ciencias:   '#2563eb',
  civico:     '#ea580c',
}

function tipoColor(nombre: string) {
  const key = Object.keys(TIPO_COLORS).find(k => nombre.toLowerCase().includes(k))
  return key ? TIPO_COLORS[key] : Colors.indigo
}

export default function ReconocimientosHijo() {
  const { id } = useLocalSearchParams<{ id: string }>()
  const hijoId  = Number(id)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['reconocimientos-hijo', hijoId],
    queryFn:  () => reconocimientosApi.hijo(hijoId).then(r => r.data),
    enabled:  !!hijoId,
  })

  const reconocimientos: any[] = data?.reconocimientos ?? []

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.amber} />}
      >
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Reconocimientos</Text>
          {data?.estudiante ? <Text style={s.pageSub}>{data.estudiante}</Text> : null}
        </View>

        {/* Resumen */}
        <View style={s.banner}>
          <View style={s.bannerIcon}>
            <Ionicons name="trophy" size={24} color="#fff" />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={s.bannerTitle}>{data?.total ?? 0} reconocimiento{(data?.total ?? 0) !== 1 ? 's' : ''}</Text>
            <Text style={s.bannerSub}>{data?.entregados ?? 0} entregado{(data?.entregados ?? 0) !== 1 ? 's' : ''}</Text>
          </View>
        </View>

        {isLoading && <ActivityIndicator color={Colors.amber} style={{ marginTop: 40 }} />}

        {!isLoading && reconocimientos.length === 0 && (
          <View style={s.empty}>
            <Ionicons name="trophy-outline" size={52} color={Colors.border} />
            <Text style={s.emptyTxt}>Sin reconocimientos registrados</Text>
          </View>
        )}

        {reconocimientos.map((r: any) => {
          const color = r.tipo ? tipoColor(r.tipo.nombre) : Colors.indigo
          return (
            <View key={r.id} style={s.card}>
              <View style={[s.accent, { backgroundColor: color }]} />
              <View style={s.cardBody}>
                <View style={s.row}>
                  <View style={[s.typeIcon, { backgroundColor: color + '20' }]}>
                    <Text style={{ fontSize: 16 }}>{r.tipo?.icono ?? '🏆'}</Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    {r.tipo && <Text style={[s.tipoBadge, { color }]}>{r.tipo.nombre}</Text>}
                    <Text style={s.titulo}>{r.titulo}</Text>
                    {r.descripcion ? <Text style={s.desc}>{r.descripcion}</Text> : null}
                    {r.emitido_por ? <Text style={s.emisor}>Emitido por: {r.emitido_por}</Text> : null}
                  </View>
                  <View style={{ alignItems: 'flex-end' }}>
                    <Text style={s.fecha}>{r.fecha_label}</Text>
                    <View style={[s.estadoBadge, { backgroundColor: r.entregado ? '#dcfce7' : '#fef9c3' }]}>
                      <Text style={[s.estadoTxt, { color: r.entregado ? '#166534' : '#854d0e' }]}>
                        {r.entregado ? '✓ Listo' : '⏳ Pend.'}
                      </Text>
                    </View>
                  </View>
                </View>
              </View>
            </View>
          )
        })}
      </ScrollView>
    </SafeAreaView>
  )
}

const s = StyleSheet.create({
  safe:        { flex: 1, backgroundColor: Colors.bg },
  content:     { padding: 16, gap: 12, paddingBottom: 32 },
  pageHeader:  { marginBottom: 4 },
  pageTitle:   { fontSize: 22, fontWeight: '900', color: Colors.text },
  pageSub:     { fontSize: 13, color: Colors.muted, marginTop: 2 },
  banner:      { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#92400e', borderRadius: 14, padding: 14 },
  bannerIcon:  { width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(255,255,255,.2)', alignItems: 'center', justifyContent: 'center' },
  bannerTitle: { fontSize: 16, fontWeight: '900', color: '#fff' },
  bannerSub:   { fontSize: 12, color: 'rgba(255,255,255,.75)', marginTop: 1 },
  empty:       { alignItems: 'center', gap: 12, paddingVertical: 40 },
  emptyTxt:    { fontSize: 14, color: Colors.muted, textAlign: 'center' },
  card:        { backgroundColor: '#fff', borderRadius: 14, flexDirection: 'row', overflow: 'hidden', shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  accent:      { width: 5 },
  cardBody:    { flex: 1, padding: 13, gap: 4 },
  row:         { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  typeIcon:    { width: 36, height: 36, borderRadius: 8, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  tipoBadge:   { fontSize: 10, fontWeight: '700', marginBottom: 1 },
  titulo:      { fontSize: 14, fontWeight: '800', color: Colors.text },
  desc:        { fontSize: 11, color: Colors.muted, marginTop: 1 },
  emisor:      { fontSize: 11, color: Colors.muted },
  fecha:       { fontSize: 11, fontWeight: '600', color: Colors.muted },
  estadoBadge: { borderRadius: 99, paddingHorizontal: 7, paddingVertical: 2, marginTop: 3 },
  estadoTxt:   { fontSize: 9, fontWeight: '700' },
})
