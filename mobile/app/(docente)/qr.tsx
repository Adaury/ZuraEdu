import React, { useState, useEffect, useRef } from 'react'
import { View, Text, StyleSheet, TouchableOpacity, Alert, Vibration, ActivityIndicator } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { CameraView, useCameraPermissions } from 'expo-camera'
import { Ionicons } from '@expo/vector-icons'
import { useMutation } from '@tanstack/react-query'
import { docenteApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

type Estado = 'presente' | 'tardanza' | 'excusa'

const ESTADOS: { key: Estado; label: string; color: string }[] = [
  { key: 'presente', label: 'Presente', color: Colors.green  },
  { key: 'tardanza', label: 'Tardanza', color: Colors.amber  },
  { key: 'excusa',   label: 'Excusa',   color: Colors.purple },
]

export default function QRScanDocente() {
  const [permission, requestPermission] = useCameraPermissions()
  const [scanned, setScanned]           = useState(false)
  const [estadoSel, setEstadoSel]       = useState<Estado>('presente')
  const [lastResult, setLastResult]     = useState<{ nombre: string; estado: string } | null>(null)
  const cooldownRef                     = useRef(false)

  const mutation = useMutation({
    mutationFn: (data: any) => docenteApi.registrarAsistencia(data),
    onSuccess: (res) => {
      const nombre = res.data?.estudiante ?? 'Estudiante'
      setLastResult({ nombre, estado: estadoSel })
      Vibration.vibrate(200)
      setTimeout(() => {
        setScanned(false)
        cooldownRef.current = false
      }, 2500)
    },
    onError: (err: any) => {
      const msg = err.response?.data?.message ?? 'Error al registrar asistencia'
      Alert.alert('Error', msg, [{ text: 'OK', onPress: () => { setScanned(false); cooldownRef.current = false } }])
    },
  })

  const handleBarCodeScanned = ({ data }: { data: string }) => {
    if (scanned || cooldownRef.current) return
    cooldownRef.current = true
    setScanned(true)
    setLastResult(null)

    // El QR del estudiante contiene su ID o un token firmado
    let estudianteId: number | null = null
    try {
      const parsed = JSON.parse(data)
      estudianteId = parsed.id ?? parsed.estudiante_id
    } catch {
      // QR simple: solo el ID numérico
      const num = parseInt(data, 10)
      if (!isNaN(num)) estudianteId = num
    }

    if (!estudianteId) {
      Alert.alert('QR no reconocido', 'Este código QR no corresponde a un estudiante.', [
        { text: 'OK', onPress: () => { setScanned(false); cooldownRef.current = false } }
      ])
      return
    }

    mutation.mutate({
      registros: [{ estudiante_id: estudianteId, estado: estadoSel }],
    })
  }

  if (!permission) return <View style={styles.safe} />

  if (!permission.granted) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.permBox}>
          <Ionicons name="camera-outline" size={64} color={Colors.muted} />
          <Text style={styles.permTitle}>Permiso de cámara requerido</Text>
          <Text style={styles.permSub}>Para escanear códigos QR necesitamos acceso a tu cámara.</Text>
          <TouchableOpacity style={styles.permBtn} onPress={requestPermission}>
            <Text style={styles.permBtnTxt}>Conceder permiso</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      {/* Estado selector */}
      <View style={styles.estadoRow}>
        <Text style={styles.estadoLabel}>Registrar como:</Text>
        <View style={styles.estadoChips}>
          {ESTADOS.map(e => (
            <TouchableOpacity
              key={e.key}
              style={[styles.chip, { borderColor: e.color, backgroundColor: estadoSel === e.key ? e.color : '#fff' }]}
              onPress={() => setEstadoSel(e.key)}
            >
              <Text style={[styles.chipTxt, { color: estadoSel === e.key ? '#fff' : e.color }]}>{e.label}</Text>
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

        {/* Overlay de viewfinder */}
        <View style={styles.overlay}>
          <View style={styles.viewfinder}>
            <View style={[styles.corner, styles.cornerTL]} />
            <View style={[styles.corner, styles.cornerTR]} />
            <View style={[styles.corner, styles.cornerBL]} />
            <View style={[styles.corner, styles.cornerBR]} />
          </View>
          <Text style={styles.scanHint}>Apunta al código QR del estudiante</Text>
        </View>

        {/* Feedback de escaneo */}
        {(scanned || mutation.isPending) && (
          <View style={styles.feedbackOverlay}>
            {mutation.isPending ? (
              <View style={styles.feedbackBox}>
                <ActivityIndicator color="#fff" size="large" />
                <Text style={styles.feedbackTxt}>Registrando...</Text>
              </View>
            ) : lastResult ? (
              <View style={[styles.feedbackBox, { backgroundColor: ESTADOS.find(e => e.key === estadoSel)!.color }]}>
                <Ionicons name="checkmark-circle" size={48} color="#fff" />
                <Text style={styles.feedbackName}>{lastResult.nombre}</Text>
                <Text style={styles.feedbackTxt}>{ESTADOS.find(e => e.key === estadoSel)!.label}</Text>
              </View>
            ) : null}
          </View>
        )}
      </View>

      {/* Instrucciones */}
      <View style={styles.footer}>
        <View style={styles.instrRow}>
          <Ionicons name="information-circle-outline" size={16} color={Colors.muted} />
          <Text style={styles.instrTxt}>El QR se escanea automáticamente al enfocar</Text>
        </View>
        <View style={styles.instrRow}>
          <Ionicons name="refresh" size={16} color={Colors.muted} />
          <Text style={styles.instrTxt}>Cada escaneo registra el estado seleccionado arriba</Text>
        </View>
      </View>
    </SafeAreaView>
  )
}

const CORNER = 22
const BORDER = 3
const SIZE   = 220

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: '#000' },
  estadoRow:      { backgroundColor: '#fff', padding: 12, gap: 8 },
  estadoLabel:    { fontSize: 12, fontWeight: '700', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5 },
  estadoChips:    { flexDirection: 'row', gap: 8 },
  chip:           { flex: 1, paddingVertical: 8, borderRadius: 10, borderWidth: 2, alignItems: 'center' },
  chipTxt:        { fontSize: 13, fontWeight: '800' },
  cameraWrap:     { flex: 1, position: 'relative' },
  overlay:        { ...StyleSheet.absoluteFillObject, alignItems: 'center', justifyContent: 'center', gap: 24 },
  viewfinder:     { width: SIZE, height: SIZE, position: 'relative' },
  corner:         { position: 'absolute', width: CORNER, height: CORNER, borderColor: '#fff' },
  cornerTL:       { top: 0, left: 0, borderTopWidth: BORDER, borderLeftWidth: BORDER },
  cornerTR:       { top: 0, right: 0, borderTopWidth: BORDER, borderRightWidth: BORDER },
  cornerBL:       { bottom: 0, left: 0, borderBottomWidth: BORDER, borderLeftWidth: BORDER },
  cornerBR:       { bottom: 0, right: 0, borderBottomWidth: BORDER, borderRightWidth: BORDER },
  scanHint:       { color: 'rgba(255,255,255,.8)', fontSize: 14, fontWeight: '600', textAlign: 'center' },
  feedbackOverlay:{ ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,.6)', alignItems: 'center', justifyContent: 'center' },
  feedbackBox:    { backgroundColor: 'rgba(30,30,30,.95)', borderRadius: 24, padding: 32, alignItems: 'center', gap: 12, minWidth: 200 },
  feedbackName:   { fontSize: 18, fontWeight: '900', color: '#fff', textAlign: 'center' },
  feedbackTxt:    { fontSize: 14, color: 'rgba(255,255,255,.8)', textAlign: 'center' },
  footer:         { backgroundColor: '#fff', padding: 16, gap: 6 },
  instrRow:       { flexDirection: 'row', alignItems: 'center', gap: 8 },
  instrTxt:       { fontSize: 12, color: Colors.muted, flex: 1 },
  // permission
  permBox:        { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 40, gap: 16, backgroundColor: Colors.bg },
  permTitle:      { fontSize: 20, fontWeight: '900', color: Colors.text, textAlign: 'center' },
  permSub:        { fontSize: 14, color: Colors.muted, textAlign: 'center', lineHeight: 22 },
  permBtn:        { backgroundColor: Colors.primary, paddingHorizontal: 28, paddingVertical: 14, borderRadius: 14, marginTop: 8 },
  permBtnTxt:     { fontSize: 15, fontWeight: '800', color: '#fff' },
})
