import React, { useState } from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity, Linking } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { transporteApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = '#0369a1'

export default function TransportePadre() {
  const [hijoIdx, setHijoIdx] = useState(0)

  const { data: classroomData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: any[] = classroomData?.hijos ?? []
  const hijoActual = hijos[hijoIdx] ?? null

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['transporte-hijo', hijoActual?.estudiante_id],
    queryFn:  () => transporteApi.rutaHijo(hijoActual!.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
  })

  const asignado: boolean = data?.asignado ?? false
  const ruta: any         = data?.ruta ?? null
  const miParada: any     = data?.mi_parada ?? null
  const paradas: any[]    = data?.todas_paradas ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        <Text style={styles.title}>Ruta de Transporte</Text>

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

        {/* Header */}
        <View style={styles.headerCard}>
          <Ionicons name="bus" size={28} color="rgba(255,255,255,.85)" style={{ marginBottom: 6 }} />
          <Text style={{ color: '#fff', fontSize: 18, fontWeight: '900' }}>Información de Traslado</Text>
          {hijoActual && (
            <Text style={{ color: 'rgba(255,255,255,.7)', fontSize: 12, marginTop: 4 }}>{hijoActual.nombre}</Text>
          )}
        </View>

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 20 }} />}

        {!isLoading && !hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="person-outline" size={44} color={Colors.muted} />
            <Text style={styles.empty}>No hay hijos registrados.</Text>
          </View>
        )}

        {!isLoading && hijoActual && !asignado && (
          <View style={styles.centered}>
            <Ionicons name="bus-outline" size={54} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin ruta asignada</Text>
            <Text style={styles.empty}>Tu hijo/a no tiene una ruta de transporte asignada.{'\n'}Comunícate con la administración.</Text>
          </View>
        )}

        {asignado && ruta && (
          <>
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

            {miParada && (
              <View style={[styles.card, { borderLeftWidth: 4, borderLeftColor: '#7c3aed' }]}>
                <Text style={styles.cardTitle}>Parada del Hijo/a</Text>
                <InfoRow icon="location" label="Parada" value={miParada.nombre} accent="#7c3aed" />
                {!!miParada.hora_estimada && (
                  <InfoRow icon="alarm-outline" label="Hora estimada" value={miParada.hora_estimada} accent="#7c3aed" />
                )}
                {!!miParada.referencia && (
                  <InfoRow icon="map-outline" label="Referencia" value={miParada.referencia} accent="#7c3aed" />
                )}
              </View>
            )}

            {paradas.length > 0 && (
              <View style={styles.card}>
                <Text style={styles.cardTitle}>Paradas de la Ruta</Text>
                {paradas.map((p: any, idx: number) => (
                  <View key={idx} style={styles.paradaRow}>
                    <View style={[styles.paradaDot, { backgroundColor: p.nombre === miParada?.nombre ? '#7c3aed' : ACCENT }]} />
                    <View style={{ flex: 1 }}>
                      <Text style={{ fontSize: 13, fontWeight: '700', color: Colors.text }}>{p.nombre}</Text>
                      {!!p.hora_estimada && <Text style={{ fontSize: 11, color: Colors.muted }}>{p.hora_estimada}</Text>}
                    </View>
                    {p.nombre === miParada?.nombre && (
                      <View style={styles.miParadaBadge}>
                        <Text style={{ fontSize: 9, color: '#7c3aed', fontWeight: '800' }}>PARADA</Text>
                      </View>
                    )}
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
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 32, gap: 12 },
  title:        { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  hijoBtn:      { borderRadius: 99, paddingHorizontal: 14, paddingVertical: 6,
                  backgroundColor: '#f1f5f9', borderWidth: 1, borderColor: Colors.border },
  hijoText:     { fontSize: 12, fontWeight: '700', color: Colors.text },
  headerCard:   { backgroundColor: ACCENT, borderRadius: 16, padding: 24, alignItems: 'center' },
  card:         { backgroundColor: '#fff', borderRadius: 14, padding: 16,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  cardTitle:    { fontSize: 14, fontWeight: '800', color: Colors.text, marginBottom: 8 },
  llamarBtn:    { flexDirection: 'row', alignItems: 'center', gap: 8, backgroundColor: Colors.green,
                  borderRadius: 10, padding: 12, marginTop: 10, justifyContent: 'center' },
  paradaRow:    { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 8,
                  borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  paradaDot:    { width: 12, height: 12, borderRadius: 6, flexShrink: 0 },
  miParadaBadge:{ backgroundColor: '#ede9fe', borderRadius: 6, paddingHorizontal: 6, paddingVertical: 2 },
  centered:     { alignItems: 'center', paddingVertical: 60, gap: 12 },
  emptyTitle:   { fontSize: 16, fontWeight: '800', color: Colors.text },
  empty:        { textAlign: 'center', color: Colors.muted, fontSize: 13 },
})
