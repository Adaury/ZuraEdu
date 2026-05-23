import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, Image,
  TouchableOpacity, ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useRouter } from 'expo-router'
import { carnetApi, classroomApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.padre

const ESTADO_BADGE: Record<string, { label: string; color: string }> = {
  activo:     { label: 'Activo',     color: Colors.green },
  suspendido: { label: 'Suspendido', color: Colors.red },
  vencido:    { label: 'Vencido',    color: Colors.amber },
}

export default function CarnetPadre() {
  const router = useRouter()

  const { data: classroomData } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: any[]   = classroomData?.hijos ?? []
  const [hijoIdx, setHijoIdx] = useState(0)
  const hijoActual     = hijos[hijoIdx] ?? null

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ['carnet-hijo', hijoActual?.estudiante_id],
    queryFn:  () => carnetApi.hijo(hijoActual.estudiante_id).then(r => r.data),
    enabled:  !!hijoActual?.estudiante_id,
    staleTime: 300_000,
  })

  const carnet     = data?.carnet
  const risk       = data?.risk
  const estado     = ESTADO_BADGE[carnet?.estado ?? ''] ?? { label: carnet?.estado ?? '', color: Colors.muted }
  const qrImageUrl = carnet?.qr_url
    ? `https://quickchart.io/qr?text=${encodeURIComponent(carnet.qr_url)}&size=260&ecLevel=M&margin=2`
    : null
  const scoreColor = (risk?.score ?? 0) > 60 ? Colors.red : (risk?.score ?? 0) > 30 ? Colors.amber : Colors.green

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* ── Selector hijo ── */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false}>
            <View style={{ flexDirection: 'row', gap: 8 }}>
              {hijos.map((h: any, i: number) => (
                <TouchableOpacity
                  key={i}
                  onPress={() => setHijoIdx(i)}
                  style={[styles.hijoPill, i === hijoIdx && { backgroundColor: ACCENT, borderColor: ACCENT }]}
                >
                  <Text style={[styles.hijoPillTxt, i === hijoIdx && { color: '#fff' }]}>
                    {h.estudiante?.split(' ')[0] ?? `Hijo ${i + 1}`}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </ScrollView>
        )}

        {isLoading && <ActivityIndicator color={ACCENT} style={{ marginTop: 40 }} />}

        {isError && (
          <View style={styles.centered}>
            <Ionicons name="cloud-offline-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Error al cargar</Text>
            <TouchableOpacity style={[styles.retryBtn, { backgroundColor: ACCENT }]} onPress={() => refetch()}>
              <Text style={styles.retryText}>Reintentar</Text>
            </TouchableOpacity>
          </View>
        )}

        {!isLoading && !isError && !carnet && hijoActual && (
          <View style={styles.centered}>
            <Ionicons name="card-outline" size={56} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin carnet generado</Text>
            <Text style={styles.emptyText}>
              El carnet de tu hijo/a aún no ha sido generado. Contacta a administración.
            </Text>
          </View>
        )}

        {carnet && qrImageUrl && (
          <>
            {/* ── Carnet visual ── */}
            <View style={styles.carnetCard}>
              <View style={[styles.carnetHeader, { backgroundColor: ACCENT }]}>
                <View>
                  <Text style={styles.carnetTipo}>CARNET ESTUDIANTIL</Text>
                  <Text style={styles.carnetNumero}>{carnet.numero_carnet}</Text>
                </View>
                <View style={[styles.estadoBadge, { backgroundColor: estado.color + '33', borderColor: estado.color }]}>
                  <Text style={[styles.estadoText, { color: estado.color }]}>{estado.label}</Text>
                </View>
              </View>

              <View style={styles.carnetBody}>
                <View style={styles.carnetFotoCol}>
                  {carnet.foto ? (
                    <Image source={{ uri: carnet.foto }} style={styles.foto} />
                  ) : (
                    <View style={[styles.foto, styles.fotoPlaceholder]}>
                      <Ionicons name="person" size={40} color={Colors.muted} />
                    </View>
                  )}
                  <Text style={styles.carnetGrupo} numberOfLines={2}>{carnet.grupo ?? '—'}</Text>
                </View>

                <View style={styles.qrCol}>
                  <Image source={{ uri: qrImageUrl }} style={styles.qr} resizeMode="contain" />
                  <Text style={styles.qrHint}>QR del estudiante</Text>
                </View>
              </View>

              {carnet.vigencia_hasta && (
                <View style={styles.carnetFooter}>
                  <Ionicons name="time-outline" size={13} color={Colors.muted} />
                  <Text style={styles.vigencia}>Válido hasta: {carnet.vigencia_hasta}</Text>
                </View>
              )}
            </View>

            {/* ── Risk Score de asistencia ── */}
            {risk && (
              <View style={[styles.riskCard, { borderLeftColor: scoreColor }]}>
                <View style={styles.riskRow}>
                  <View style={styles.riskLeft}>
                    <Text style={styles.riskTitle}>Asistencia — Risk Score</Text>
                    <View style={styles.riskStats}>
                      <View style={styles.riskStat}>
                        <Text style={[styles.riskVal, { color: Colors.green }]}>{risk.presencias}</Text>
                        <Text style={styles.riskLbl}>Presentes</Text>
                      </View>
                      <View style={styles.riskStat}>
                        <Text style={[styles.riskVal, { color: Colors.amber }]}>{risk.tardanzas}</Text>
                        <Text style={styles.riskLbl}>Tardanzas</Text>
                      </View>
                      <View style={styles.riskStat}>
                        <Text style={[styles.riskVal, { color: Colors.red }]}>{risk.ausencias}</Text>
                        <Text style={styles.riskLbl}>Ausencias</Text>
                      </View>
                    </View>
                    <View style={styles.barBg}>
                      <View
                        style={[styles.barFill, {
                          width: `${Math.max(0, 100 - risk.score)}%` as any,
                          backgroundColor: Colors.green,
                        }]}
                      />
                    </View>
                    <Text style={styles.riskPct}>
                      {Number(risk.porcentaje_asistencia ?? 0).toFixed(1)}% asistencia (últimos 30 días)
                    </Text>
                  </View>
                  <View style={styles.riskRight}>
                    <Text style={[styles.riskScore, { color: scoreColor }]}>{risk.score}</Text>
                    <Text style={[styles.riskNivel, { color: scoreColor }]}>{risk.nivel?.label ?? '—'}</Text>
                  </View>
                </View>
              </View>
            )}

            {/* ── Ver historial ── */}
            <TouchableOpacity
              style={[styles.histBtn, { backgroundColor: ACCENT }]}
              onPress={() => router.push({
                pathname: '/(padre)/carnet-historial',
                params:   { estudianteId: String(hijoActual?.estudiante_id ?? 0) },
              })}
              activeOpacity={0.85}
            >
              <Ionicons name="time" size={18} color="#fff" />
              <Text style={styles.histBtnText}>Ver historial de accesos</Text>
              <Ionicons name="chevron-forward" size={18} color="#fff" />
            </TouchableOpacity>
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: Colors.bg },
  content:         { padding: 16, gap: 14, paddingBottom: 40 },
  centered:        { alignItems: 'center', justifyContent: 'center', padding: 32, gap: 12 },
  emptyTitle:      { fontSize: 16, fontWeight: '800', color: Colors.text },
  emptyText:       { fontSize: 13, color: Colors.muted, textAlign: 'center', lineHeight: 19 },
  retryBtn:        { borderRadius: 99, paddingHorizontal: 20, paddingVertical: 10 },
  retryText:       { color: '#fff', fontWeight: '700' },
  hijoPill:        { paddingHorizontal: 14, paddingVertical: 7, borderRadius: 99,
                     backgroundColor: '#fff', borderWidth: 1.5, borderColor: Colors.border },
  hijoPillTxt:     { fontSize: 13, fontWeight: '700', color: Colors.text },

  carnetCard:      { backgroundColor: '#fff', borderRadius: 20, overflow: 'hidden',
                     shadowColor: '#000', shadowOpacity: .08, shadowRadius: 12, elevation: 4 },
  carnetHeader:    { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between',
                     paddingHorizontal: 16, paddingVertical: 14 },
  carnetTipo:      { fontSize: 10, fontWeight: '800', color: 'rgba(255,255,255,.8)', letterSpacing: 1 },
  carnetNumero:    { fontSize: 16, fontWeight: '900', color: '#fff', marginTop: 2 },
  estadoBadge:     { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 99, borderWidth: 1 },
  estadoText:      { fontSize: 11, fontWeight: '800' },

  carnetBody:      { flexDirection: 'row', padding: 16, gap: 12, alignItems: 'center' },
  carnetFotoCol:   { alignItems: 'center', gap: 8, flex: 1 },
  foto:            { width: 80, height: 80, borderRadius: 40, borderWidth: 3, borderColor: Colors.border },
  fotoPlaceholder: { backgroundColor: Colors.bg, alignItems: 'center', justifyContent: 'center' },
  carnetGrupo:     { fontSize: 11, fontWeight: '700', color: Colors.muted, textAlign: 'center' },
  qrCol:           { alignItems: 'center', gap: 4, flex: 1 },
  qr:              { width: 130, height: 130 },
  qrHint:          { fontSize: 10, color: Colors.muted, fontWeight: '600' },

  carnetFooter:    { flexDirection: 'row', alignItems: 'center', gap: 4,
                     borderTopWidth: 1, borderTopColor: Colors.border, padding: 10, paddingHorizontal: 16 },
  vigencia:        { fontSize: 11, color: Colors.muted },

  riskCard:        { backgroundColor: '#fff', borderRadius: 16, padding: 14, borderLeftWidth: 4,
                     shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  riskRow:         { flexDirection: 'row', gap: 12 },
  riskLeft:        { flex: 1, gap: 8 },
  riskTitle:       { fontSize: 13, fontWeight: '700', color: Colors.text },
  riskStats:       { flexDirection: 'row', gap: 16 },
  riskStat:        { alignItems: 'center' },
  riskVal:         { fontSize: 20, fontWeight: '900', lineHeight: 24 },
  riskLbl:         { fontSize: 10, color: Colors.muted, fontWeight: '600' },
  barBg:           { height: 6, borderRadius: 99, backgroundColor: Colors.border, overflow: 'hidden' },
  barFill:         { height: '100%', borderRadius: 99 },
  riskPct:         { fontSize: 10, color: Colors.muted },
  riskRight:       { alignItems: 'center', justifyContent: 'center', minWidth: 64 },
  riskScore:       { fontSize: 36, fontWeight: '900', lineHeight: 40 },
  riskNivel:       { fontSize: 11, fontWeight: '800' },

  histBtn:         { flexDirection: 'row', alignItems: 'center', gap: 8,
                     borderRadius: 14, paddingVertical: 14, paddingHorizontal: 20 },
  histBtnText:     { color: '#fff', fontWeight: '800', fontSize: 14, flex: 1, textAlign: 'center' },
})
