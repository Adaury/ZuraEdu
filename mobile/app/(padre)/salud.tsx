import React from 'react'
import { View, Text, ScrollView, StyleSheet, RefreshControl, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useLocalSearchParams } from 'expo-router'
import { saludApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const TIPO_INC_COLOR: Record<string, string> = {
  accidente:  Colors.red,
  enfermedad: Colors.amber,
  alergia:    '#ea580c',
  otro:       Colors.muted,
}

export default function SaludHijo() {
  const { id } = useLocalSearchParams<{ id: string }>()
  const hijoId  = Number(id)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['salud-hijo', hijoId],
    queryFn:  () => saludApi.hijo(hijoId).then(r => r.data),
    enabled:  !!hijoId,
  })

  const ficha: any      = data?.ficha ?? null
  const incidentes: any[] = data?.incidentes ?? []

  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={s.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.red} />}
      >
        <View style={s.pageHeader}>
          <Text style={s.pageTitle}>Salud Escolar</Text>
          {data?.estudiante ? <Text style={s.pageSub}>{data.estudiante}</Text> : null}
        </View>

        {isLoading && <ActivityIndicator color={Colors.red} style={{ marginTop: 40 }} />}

        {/* Ficha de salud */}
        {!isLoading && !ficha && (
          <View style={s.emptyCard}>
            <Ionicons name="heart-outline" size={40} color={Colors.border} />
            <Text style={s.emptyTxt}>Sin ficha de salud registrada</Text>
            <Text style={s.emptySub}>Comuníquese con la dirección para registrarla</Text>
          </View>
        )}

        {ficha && (
          <View style={s.card}>
            <View style={s.cardHeader}>
              <View style={[s.cardIcon, { backgroundColor: '#fee2e2' }]}>
                <Ionicons name="heart-half" size={20} color={Colors.red} />
              </View>
              <Text style={s.cardTitle}>Ficha de Salud</Text>
            </View>

            <View style={s.grid}>
              {ficha.tipo_sangre && (
                <View style={[s.gridItem, { backgroundColor: '#fef2f2' }]}>
                  <Text style={[s.gridLabel, { color: Colors.red }]}>TIPO DE SANGRE</Text>
                  <Text style={s.gridValue}>{ficha.tipo_sangre}</Text>
                </View>
              )}
              {ficha.alergias && (
                <View style={[s.gridItem, { backgroundColor: '#fff7ed' }]}>
                  <Text style={[s.gridLabel, { color: '#ea580c' }]}>ALERGIAS</Text>
                  <Text style={s.gridValueSm}>{ficha.alergias}</Text>
                </View>
              )}
              {ficha.condiciones_medicas && (
                <View style={[s.gridItem, { backgroundColor: '#fff7ed' }]}>
                  <Text style={[s.gridLabel, { color: '#ea580c' }]}>CONDICIONES</Text>
                  <Text style={s.gridValueSm}>{ficha.condiciones_medicas}</Text>
                </View>
              )}
              {ficha.medicamentos && (
                <View style={[s.gridItem, { backgroundColor: '#f0fdf4' }]}>
                  <Text style={[s.gridLabel, { color: Colors.green }]}>MEDICAMENTOS</Text>
                  <Text style={s.gridValueSm}>{ficha.medicamentos}</Text>
                </View>
              )}
              {ficha.seguro_medico && (
                <View style={[s.gridItem, { backgroundColor: '#eff6ff' }]}>
                  <Text style={[s.gridLabel, { color: Colors.indigo }]}>SEGURO MÉDICO</Text>
                  <Text style={s.gridValueSm}>{ficha.seguro_medico}</Text>
                  {ficha.num_seguro && <Text style={s.gridValueXs}>No. {ficha.num_seguro}</Text>}
                </View>
              )}
            </View>

            {(ficha.contacto_emergencia || ficha.telefono_emergencia) && (
              <View style={s.emergencia}>
                <Text style={s.emergenciaLabel}>EMERGENCIA</Text>
                {ficha.contacto_emergencia && (
                  <Text style={s.emergenciaNombre}>{ficha.contacto_emergencia}</Text>
                )}
                {ficha.telefono_emergencia && (
                  <View style={s.row}>
                    <Ionicons name="call" size={14} color={Colors.red} />
                    <Text style={s.emergenciaTel}>{ficha.telefono_emergencia}</Text>
                  </View>
                )}
              </View>
            )}
          </View>
        )}

        {/* Incidentes médicos */}
        <View style={s.card}>
          <View style={s.cardHeader}>
            <View style={[s.cardIcon, { backgroundColor: '#fef3c7' }]}>
              <Ionicons name="bandage" size={20} color={Colors.amber} />
            </View>
            <View>
              <Text style={s.cardTitle}>Incidentes Médicos</Text>
              <Text style={s.cardSub}>Atenciones registradas en la escuela</Text>
            </View>
          </View>

          {incidentes.length === 0 ? (
            <View style={s.inlineEmpty}>
              <Ionicons name="shield-checkmark-outline" size={28} color={Colors.green} />
              <Text style={s.inlineEmptyTxt}>Sin incidentes registrados</Text>
            </View>
          ) : (
            <View style={{ gap: 10 }}>
              {incidentes.map((inc: any, i: number) => {
                const color = TIPO_INC_COLOR[inc.tipo] ?? Colors.muted
                return (
                  <View key={i} style={s.incCard}>
                    <View style={[s.incDot, { backgroundColor: color + '25' }]}>
                      <Ionicons name="bandage-outline" size={16} color={color} />
                    </View>
                    <View style={{ flex: 1 }}>
                      <View style={s.row}>
                        <View style={[s.tipoPill, { backgroundColor: color + '20' }]}>
                          <Text style={[s.tipoTxt, { color }]}>{inc.tipo_label}</Text>
                        </View>
                        <Text style={s.incFecha}>{inc.fecha}{inc.hora ? ' · ' + inc.hora : ''}</Text>
                        {inc.notificado_representante && (
                          <View style={s.notifBadge}>
                            <Text style={s.notifTxt}>✓ Notificado</Text>
                          </View>
                        )}
                      </View>
                      <Text style={s.incDesc}>{inc.descripcion}</Text>
                      {inc.accion_tomada && (
                        <Text style={s.incDetail}><Text style={{ fontWeight: '700' }}>Acción:</Text> {inc.accion_tomada}</Text>
                      )}
                      {inc.remitido_a && (
                        <Text style={s.incDetail}><Text style={{ fontWeight: '700' }}>Remitido:</Text> {inc.remitido_a}</Text>
                      )}
                    </View>
                  </View>
                )
              })}
            </View>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  )
}

const s = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, gap: 14, paddingBottom: 32 },
  pageHeader:      { marginBottom: 2 },
  pageTitle:       { fontSize: 22, fontWeight: '900', color: Colors.text },
  pageSub:         { fontSize: 13, color: Colors.muted, marginTop: 2 },
  emptyCard:       { backgroundColor: '#fff', borderRadius: 14, padding: 32, alignItems: 'center', gap: 8 },
  emptyTxt:        { fontSize: 14, fontWeight: '700', color: Colors.muted },
  emptySub:        { fontSize: 12, color: Colors.muted, textAlign: 'center' },
  card:            { backgroundColor: '#fff', borderRadius: 14, padding: 16, gap: 14, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 6, elevation: 2 },
  cardHeader:      { flexDirection: 'row', alignItems: 'center', gap: 10 },
  cardIcon:        { width: 36, height: 36, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  cardTitle:       { fontSize: 15, fontWeight: '800', color: Colors.text },
  cardSub:         { fontSize: 11, color: Colors.muted },
  grid:            { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  gridItem:        { borderRadius: 10, padding: 10, minWidth: 130, flex: 1 },
  gridLabel:       { fontSize: 9, fontWeight: '800', letterSpacing: 0.5, marginBottom: 3 },
  gridValue:       { fontSize: 22, fontWeight: '900', color: Colors.text },
  gridValueSm:     { fontSize: 13, fontWeight: '600', color: Colors.text },
  gridValueXs:     { fontSize: 11, color: Colors.muted, marginTop: 1 },
  emergencia:      { backgroundColor: '#fef2f2', borderRadius: 10, padding: 12, borderLeftWidth: 3, borderLeftColor: Colors.red },
  emergenciaLabel: { fontSize: 9, fontWeight: '800', color: Colors.red, letterSpacing: 0.5, marginBottom: 4 },
  emergenciaNombre:{ fontSize: 14, fontWeight: '700', color: Colors.text },
  emergenciaTel:   { fontSize: 13, color: Colors.muted, marginLeft: 4 },
  row:             { flexDirection: 'row', alignItems: 'center', gap: 6, flexWrap: 'wrap' },
  inlineEmpty:     { flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 8 },
  inlineEmptyTxt:  { fontSize: 13, color: Colors.muted },
  incCard:         { flexDirection: 'row', gap: 10, alignItems: 'flex-start' },
  incDot:          { width: 32, height: 32, borderRadius: 8, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  tipoPill:        { borderRadius: 99, paddingHorizontal: 8, paddingVertical: 2 },
  tipoTxt:         { fontSize: 10, fontWeight: '700' },
  incFecha:        { fontSize: 11, color: Colors.muted },
  notifBadge:      { backgroundColor: '#dcfce7', borderRadius: 99, paddingHorizontal: 7, paddingVertical: 2 },
  notifTxt:        { fontSize: 9, fontWeight: '700', color: '#166534' },
  incDesc:         { fontSize: 13, color: Colors.text, marginTop: 4 },
  incDetail:       { fontSize: 11, color: Colors.muted, marginTop: 2 },
})
