import React, { useState, useRef } from 'react'
import {
  View, Text, StyleSheet, TouchableOpacity,
  Alert, Vibration, ActivityIndicator,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { CameraView, useCameraPermissions } from 'expo-camera'
import { Ionicons } from '@expo/vector-icons'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { carnetApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.docente

type TipoEvento = 'entrada' | 'salida' | 'biblioteca' | 'comedor' | 'laboratorio'

const TIPOS: { key: TipoEvento; label: string; icon: keyof typeof Ionicons.glyphMap; color: string }[] = [
  { key: 'entrada',     label: 'Entrada',     icon: 'enter-outline',      color: Colors.green  },
  { key: 'salida',      label: 'Salida',      icon: 'exit-outline',       color: Colors.red    },
  { key: 'biblioteca',  label: 'Biblioteca',  icon: 'library-outline',    color: Colors.blue   },
  { key: 'comedor',     label: 'Comedor',     icon: 'restaurant-outline', color: Colors.amber  },
  { key: 'laboratorio', label: 'Laboratorio', icon: 'flask-outline',      color: Colors.purple },
]

type FeedbackResult = {
  nombre: string
  carnet: string
  estado: string
  hora: string
  color: string
}

const CORNER = 22
const BORDER = 3
const SIZE   = 220

export default function CarnetScanDocente() {
  const [permission, requestPermission] = useCameraPermissions()
  const [scanned, setScanned]           = useState(false)
  const [tipoSel, setTipoSel]           = useState<TipoEvento>('entrada')
  const [feedback, setFeedback]         = useState<FeedbackResult | null>(null)
  const cooldownRef                     = useRef(false)
  const queryClient                     = useQueryClient()

  const mutation = useMutation({
    mutationFn: (qrToken: string) =>
      carnetApi.scan({ qr_token: qrToken, tipo_evento: tipoSel }).then(r => r.data),
    onSuccess: (res) => {
      const tipoInfo = TIPOS.find(t => t.key === tipoSel)!
      setFeedback({
        nombre: res.nombre  ?? '—',
        carnet: res.carnet  ?? '—',
        estado: res.estado  ?? tipoSel,
        hora:   res.hora    ?? new Date().toLocaleTimeString('es-DO', { hour: '2-digit', minute: '2-digit' }),
        color:  tipoInfo.color,
      })
      Vibration.vibrate(200)
      queryClient.invalidateQueries({ queryKey: ['docente-carnet-hoy'] })
      setTimeout(() => {
        setFeedback(null)
        setScanned(false)
        cooldownRef.current = false
      }, 3000)
    },
    onError: (err: any) => {
      const msg = err.response?.data?.message ?? 'QR inválido o carnet suspendido'
      Alert.alert('No reconocido', msg, [{
        text: 'OK', onPress: () => { setScanned(false); cooldownRef.current = false },
      }])
    },
  })

  const handleBarCodeScanned = ({ data }: { data: string }) => {
    if (scanned || cooldownRef.current) return
    cooldownRef.current = true
    setScanned(true)
    setFeedback(null)

    // El QR del carnet contiene la URL: http://.../checkin/scan/{sha256-token}
    const match = data.match(/\/checkin\/scan\/([a-f0-9]{64})/)
    const qrToken = match?.[1]

    if (!qrToken) {
      Alert.alert('QR no reconocido', 'Este código no es un Carnet+ válido.', [{
        text: 'OK', onPress: () => { setScanned(false); cooldownRef.current = false },
      }])
      return
    }

    mutation.mutate(qrToken)
  }

  // ── Permisos ──────────────────────────────────────────────────────────────

  if (!permission) return <View style={styles.safe} />

  if (!permission.granted) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.permBox}>
          <Ionicons name="camera-outline" size={64} color={Colors.muted} />
          <Text style={styles.permTitle}>Permiso de cámara requerido</Text>
          <Text style={styles.permSub}>Necesitamos acceso a tu cámara para escanear carnets.</Text>
          <TouchableOpacity style={[styles.permBtn, { backgroundColor: ACCENT }]} onPress={requestPermission}>
            <Text style={styles.permBtnTxt}>Conceder permiso</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    )
  }

  // ── Selector de tipo de evento ────────────────────────────────────────────

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Tipo de evento */}
      <View style={styles.tipoBar}>
        <Text style={styles.tipoLabel}>Tipo de acceso:</Text>
        <View style={styles.tipoChips}>
          {TIPOS.map(t => (
            <TouchableOpacity
              key={t.key}
              style={[styles.chip, { borderColor: t.color, backgroundColor: tipoSel === t.key ? t.color : '#fff' }]}
              onPress={() => setTipoSel(t.key)}
            >
              <Ionicons name={t.icon} size={13} color={tipoSel === t.key ? '#fff' : t.color} />
              <Text style={[styles.chipTxt, { color: tipoSel === t.key ? '#fff' : t.color }]}>{t.label}</Text>
            </TouchableOpacity>
          ))}
        </View>
      </View>

      {/* Cámara */}
      <View style={styles.cameraWrap}>
        <CameraView
          style={StyleSheet.absoluteFillObject}
          facing="back"
          barcodeScannerSettings={{ barcodeTypes: ['qr'] }}
          onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
        />

        {/* Viewfinder */}
        <View style={styles.overlay}>
          <View style={styles.viewfinder}>
            <View style={[styles.corner, styles.cornerTL]} />
            <View style={[styles.corner, styles.cornerTR]} />
            <View style={[styles.corner, styles.cornerBL]} />
            <View style={[styles.corner, styles.cornerBR]} />
          </View>
          <Text style={styles.scanHint}>Apunta al QR del carnet estudiantil</Text>
        </View>

        {/* Feedback overlay */}
        {(mutation.isPending || feedback) && (
          <View style={styles.feedbackOverlay}>
            {mutation.isPending ? (
              <View style={styles.feedbackBox}>
                <ActivityIndicator color="#fff" size="large" />
                <Text style={styles.feedbackTxt}>Registrando acceso...</Text>
              </View>
            ) : feedback ? (
              <View style={[styles.feedbackBox, { backgroundColor: feedback.color }]}>
                <Ionicons name="checkmark-circle" size={52} color="#fff" />
                <Text style={styles.feedbackNombre}>{feedback.nombre}</Text>
                <Text style={styles.feedbackCarnet}>{feedback.carnet}</Text>
                <View style={styles.feedbackHoraRow}>
                  <Ionicons name="time-outline" size={14} color="rgba(255,255,255,.85)" />
                  <Text style={styles.feedbackHora}>{feedback.hora}</Text>
                </View>
              </View>
            ) : null}
          </View>
        )}
      </View>

      {/* Footer */}
      <View style={styles.footer}>
        <View style={styles.instrRow}>
          <Ionicons name="information-circle-outline" size={15} color={Colors.muted} />
          <Text style={styles.instrTxt}>Escanea el QR del carnet físico o digital del estudiante</Text>
        </View>
        <View style={styles.instrRow}>
          <Ionicons name="refresh-outline" size={15} color={Colors.muted} />
          <Text style={styles.instrTxt}>El registro se actualiza en "Control de Acceso" automáticamente</Text>
        </View>
      </View>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:            { flex: 1, backgroundColor: '#000' },

  tipoBar:         { backgroundColor: '#fff', padding: 12, gap: 8 },
  tipoLabel:       { fontSize: 11, fontWeight: '700', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5 },
  tipoChips:       { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  chip:            { flexDirection: 'row', alignItems: 'center', gap: 4,
                     paddingHorizontal: 10, paddingVertical: 6, borderRadius: 99, borderWidth: 1.5 },
  chipTxt:         { fontSize: 11, fontWeight: '800' },

  cameraWrap:      { flex: 1, position: 'relative' },
  overlay:         { ...StyleSheet.absoluteFillObject, alignItems: 'center', justifyContent: 'center', gap: 20 },
  viewfinder:      { width: SIZE, height: SIZE, position: 'relative' },
  corner:          { position: 'absolute', width: CORNER, height: CORNER, borderColor: '#fff' },
  cornerTL:        { top: 0, left: 0, borderTopWidth: BORDER, borderLeftWidth: BORDER },
  cornerTR:        { top: 0, right: 0, borderTopWidth: BORDER, borderRightWidth: BORDER },
  cornerBL:        { bottom: 0, left: 0, borderBottomWidth: BORDER, borderLeftWidth: BORDER },
  cornerBR:        { bottom: 0, right: 0, borderBottomWidth: BORDER, borderRightWidth: BORDER },
  scanHint:        { color: 'rgba(255,255,255,.85)', fontSize: 13, fontWeight: '600', textAlign: 'center' },

  feedbackOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,.65)',
                     alignItems: 'center', justifyContent: 'center' },
  feedbackBox:     { borderRadius: 24, padding: 32, alignItems: 'center', gap: 10,
                     minWidth: 220, backgroundColor: 'rgba(30,30,30,.95)' },
  feedbackNombre:  { fontSize: 20, fontWeight: '900', color: '#fff', textAlign: 'center' },
  feedbackCarnet:  { fontSize: 13, color: 'rgba(255,255,255,.7)', fontWeight: '600' },
  feedbackHoraRow: { flexDirection: 'row', alignItems: 'center', gap: 4, marginTop: 4 },
  feedbackHora:    { fontSize: 14, color: 'rgba(255,255,255,.85)', fontWeight: '700' },
  feedbackTxt:     { fontSize: 14, color: 'rgba(255,255,255,.8)', textAlign: 'center' },

  footer:          { backgroundColor: '#fff', padding: 12, gap: 5 },
  instrRow:        { flexDirection: 'row', alignItems: 'center', gap: 8 },
  instrTxt:        { fontSize: 11, color: Colors.muted, flex: 1, lineHeight: 16 },

  permBox:         { flex: 1, alignItems: 'center', justifyContent: 'center',
                     padding: 40, gap: 16, backgroundColor: Colors.bg },
  permTitle:       { fontSize: 20, fontWeight: '900', color: Colors.text, textAlign: 'center' },
  permSub:         { fontSize: 14, color: Colors.muted, textAlign: 'center', lineHeight: 22 },
  permBtn:         { paddingHorizontal: 28, paddingVertical: 14, borderRadius: 14, marginTop: 8 },
  permBtnTxt:      { fontSize: 15, fontWeight: '800', color: '#fff' },
})
