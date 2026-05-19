import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, RefreshControl, Linking, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { transporteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = '#0369a1'

export default function TransporteEstudiante() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['transporte-estudiante'],
    queryFn:  () => transporteApi.miRuta().then(r => r.data),
  })

  const asignado: boolean  = data?.asignado ?? false
  const ruta: any          = data?.ruta ?? null
  const miParada: any      = data?.mi_parada ?? null
  const paradas: any[]     = data?.todas_paradas ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* Header */}
        <View style={styles.headerCard}>
          <View style={{ position: 'absolute', top: -20, right: -20, width: 100, height: 100,
            backgroundColor: 'rgba(255,255,255,.08)', borderRadius: 50 }} />
          <Ionicons name="bus" size={28} color="rgba(255,255,255,.85)" style={{ marginBottom: 6 }} />
          <Text style={{ color: '#fff', fontSize: 20, fontWeight: '900' }}>Mi Ruta de Transporte</Text>
          <Text style={{ color: 'rgba(255,255,255,.7)', fontSize: 12, marginTop: 4 }}>
            Información de traslado escolar
          </Text>
        </View>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 20 }} />}

        {!isLoading && !asignado && (
          <View style={styles.centered}>
            <Ionicons name="bus-outline" size={54} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin ruta asignada</Text>
            <Text style={styles.empty}>Si necesitas el servicio de transporte escolar,{'\n'}comunícate con la administración.</Text>
          </View>
        )}

        {asignado && ruta && (
          <>
            {/* Info de la ruta */}
            <View style={styles.card}>
              <Text style={styles.cardTitle}>Información de la Ruta</Text>
              <InfoRow icon="signpost-outline" label="Nombre" value={ruta.nombre} accent={ACCENT} />
              {!!ruta.conductor && (
                <InfoRow icon="person-circle-outline" label="Conductor" value={ruta.conductor} accent={ACCENT} />
              )}
              {!!ruta.placa && (
                <InfoRow icon="card-outline" label="Placa" value={ruta.placa.toUpperCase()} accent={ACCENT} mono />
              )}
              {!!ruta.horario_ida && (
                <InfoRow icon="time-outline" label="Hora de ida" value={ruta.horario_ida} accent={ACCENT} />
              )}
              {!!ruta.horario_vuelta && (
                <InfoRow icon="time-outline" label="Hora de vuelta" value={ruta.horario_vuelta} accent={ACCENT} />
              )}
              {!!ruta.telefono_conductor && (
                <TouchableOpacity
                  style={styles.llamarBtn}
                  onPress={() => Linking.openURL(`tel:${ruta.telefono_conductor}`)}
                >
                  <Ionicons name="call" size={16} color="#fff" />
                  <Text style={{ color: '#fff', fontWeight: '700', fontSize: 13 }}>Llamar al conductor</Text>
                </TouchableOpacity>
              )}
            </View>

            {/* Mi parada */}
            {miParada && (
              <View style={[styles.card, { borderLeftWidth: 4, borderLeftColor: '#7c3aed' }]}>
                <Text style={styles.cardTitle}>Mi Parada</Text>
                <InfoRow icon="location" label="Parada" value={miParada.nombre} accent="#7c3aed" />
                {!!miParada.hora_estimada && (
                  <InfoRow icon="alarm-outline" label="Hora estimada" value={miParada.hora_estimada} accent="#7c3aed" />
                )}
                {!!miParada.referencia && (
                  <InfoRow icon="map-outline" label="Referencia" value={miParada.referencia} accent="#7c3aed" />
                )}
              </View>
            )}

            {/* Todas las paradas */}
            {paradas.length > 0 && (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>Paradas de la Ruta</Text>
                {paradas.map((p: any, idx: number) => (
                  <View key={idx} style={styles.paradaRow}>
                    <View style={[styles.paradaDot, { backgroundColor: p.nombre === miParada?.nombre ? '#7c3aed' : ACCENT }]} />
                    <View style={{ flex: 1 }}>
                      <Text style={{ fontSize: 13, fontWeight: '700', color: Colors.text }}>{p.nombre}</Text>
                      {!!p.hora_estimada && (
                        <Text style={{ fontSize: 11, color: Colors.muted }}>{p.hora_estimada}</Text>
                      )}
                    </View>
                    {p.nombre === miParada?.nombre && (
                      <View style={styles.miParadaBadge}>
                        <Text style={{ fontSize: 9, color: '#7c3aed', fontWeight: '800' }}>MI PARADA</Text>
                      </View>
                    )}
                    {idx < paradas.length - 1 && <View style={styles.paradaLinea} />}
                  </View>
                ))}
              </View>
            )}
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

function InfoRow({ icon, label, value, accent, mono }: any) {
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 8,
      borderBottomWidth: 1, borderBottomColor: '#f1f5f9' }}>
      <View style={{ width: 36, height: 36, borderRadius: 9, alignItems: 'center', justifyContent: 'center',
        backgroundColor: `${accent}15` }}>
        <Ionicons name={icon} size={18} color={accent} />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={{ fontSize: 10, textTransform: 'uppercase', letterSpacing: 0.5, color: Colors.muted, fontWeight: '600' }}>{label}</Text>
        <Text style={{ fontSize: 14, fontWeight: '700', color: '#1e293b', fontFamily: mono ? 'monospace' : undefined }}>{value}</Text>
      </View>
    </View>
  )
}

const styles = StyleSheet.create({
  safe:          { flex: 1, backgroundColor: Colors.bg },
  content:       { padding: 16, paddingBottom: 32, gap: 12 },
  headerCard:    { backgroundColor: ACCENT, borderRadius: 16, padding: 24, alignItems: 'center',
                   overflow: 'hidden', position: 'relative' },
  card:          { backgroundColor: '#fff', borderRadius: 14, padding: 16,
                   shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardTitle:     { fontSize: 14, fontWeight: '800', color: Colors.text, marginBottom: 8 },
  llamarBtn:     { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: Colors.green,
                   borderRadius: 10, padding: 12, marginTop: 10, justifyContent: 'center' },
  paradaRow:     { position: 'relative', flexDirection: 'row', alignItems: 'center', gap: 12,
                   paddingVertical: 8, paddingLeft: 4 },
  paradaDot:     { width: 12, height: 12, borderRadius: 6, flexShrink: 0 },
  paradaLinea:   { position: 'absolute', left: 9, top: 24, bottom: -8,
                   width: 2, backgroundColor: Colors.border },
  miParadaBadge: { backgroundColor: '#ede9fe', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },
  centered:      { alignItems: 'center', paddingVertical: 60, gap: 12 },
  emptyTitle:    { fontSize: 16, fontWeight: '800', color: Colors.text },
  empty:         { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
