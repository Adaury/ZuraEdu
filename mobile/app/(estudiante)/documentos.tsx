import React, { useState } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  ActivityIndicator, RefreshControl, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import * as FileSystem from 'expo-file-system/legacy'
import * as Sharing from 'expo-sharing'
import { documentosApi, API_BASE } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ACCENT = Colors.roles.estudiante

export default function DocumentosEstudiante() {
  const [downloading, setDownloading] = useState<string | null>(null)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['documentos-estudiante'],
    queryFn:  () => documentosApi.info().then(r => r.data),
    staleTime: 60_000,
  })

  const token      = data?.download_token ?? ''
  const categorias = data?.documentos ?? []

  const handleDownload = async (item: any) => {
    if (!item.disponible || !token) return

    const docKey = item.id ?? item.label
    setDownloading(docKey)

    try {
      const url      = `${API_BASE}${item.ruta_web}?download_token=${token}`
      const filename = `${item.label.replace(/\s+/g, '_')}_${Date.now()}.pdf`
      const destPath = `${FileSystem.cacheDirectory}${filename}`

      const { status, uri } = await FileSystem.downloadAsync(url, destPath)

      if (status !== 200) {
        Alert.alert('Error', 'No se pudo descargar el documento. Intenta de nuevo.')
        return
      }

      const canShare = await Sharing.isAvailableAsync()
      if (!canShare) {
        Alert.alert('No disponible', 'La función de compartir no está disponible en este dispositivo.')
        return
      }

      await Sharing.shareAsync(uri, {
        mimeType: 'application/pdf',
        dialogTitle: item.label,
        UTI: 'com.adobe.pdf',
      })
    } catch (err: any) {
      const msg = err?.message?.includes('Network')
        ? 'Error de conexión. Verifica tu internet.'
        : 'No se pudo abrir el documento.'
      Alert.alert('Error', msg)
    } finally {
      setDownloading(null)
    }
  }

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={ACCENT} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={ACCENT} />}
      >
        {/* Header */}
        <View style={styles.headerCard}>
          <View style={[styles.headerIcon, { backgroundColor: ACCENT + '18' }]}>
            <Ionicons name="folder-open" size={28} color={ACCENT} />
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.headerName}>{data?.estudiante}</Text>
            <Text style={styles.headerSub}>{data?.school_year ?? 'Período actual'}</Text>
          </View>
          {data?.grupo && (
            <View style={styles.grupoBadge}>
              <Text style={styles.grupoText}>{data.grupo}</Text>
            </View>
          )}
        </View>

        {data && !data.tiene_matricula && (
          <View style={styles.alertBox}>
            <Ionicons name="information-circle" size={18} color={Colors.amber} />
            <Text style={styles.alertText}>
              No tienes matrícula activa este período. Algunos documentos no están disponibles.
            </Text>
          </View>
        )}

        {categorias.map((cat: any) => (
          <View key={cat.categoria} style={styles.section}>
            <Text style={styles.catTitle}>{cat.categoria}</Text>
            {cat.items.map((item: any, idx: number) => {
              const docKey    = item.id ?? item.label
              const isActive  = downloading === docKey
              const disponible = item.disponible && !!token

              return (
                <TouchableOpacity
                  key={docKey}
                  style={[
                    styles.docRow,
                    idx === 0 && styles.docRowFirst,
                    !disponible && styles.docRowDisabled,
                  ]}
                  onPress={() => handleDownload(item)}
                  activeOpacity={disponible ? 0.7 : 1}
                  disabled={isActive || !disponible}
                >
                  <View style={[styles.docIconBox, { backgroundColor: item.color + '18' }]}>
                    {isActive ? (
                      <ActivityIndicator size="small" color={item.color} />
                    ) : (
                      <Ionicons
                        name={item.icono as any}
                        size={20}
                        color={disponible ? item.color : Colors.muted}
                      />
                    )}
                  </View>

                  <View style={{ flex: 1 }}>
                    <Text style={[styles.docLabel, !disponible && styles.docLabelDisabled]}>
                      {item.label}
                    </Text>
                    {isActive && (
                      <Text style={[styles.docSub, { color: item.color }]}>Descargando…</Text>
                    )}
                  </View>

                  {disponible && !isActive && (
                    <View style={[styles.dlBtn, { backgroundColor: item.color + '15' }]}>
                      <Ionicons name="download-outline" size={16} color={item.color} />
                    </View>
                  )}
                  {!disponible && (
                    <Ionicons name="lock-closed-outline" size={16} color={Colors.border} />
                  )}
                </TouchableOpacity>
              )
            })}
          </View>
        ))}

        {categorias.length > 0 && (
          <Text style={styles.footNote}>
            Los documentos se abren directamente en el visor de PDF del dispositivo.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, gap: 14, paddingBottom: 32 },

  headerCard:        { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#fff',
                       borderRadius: 16, padding: 16,
                       shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  headerIcon:        { width: 52, height: 52, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  headerName:        { fontSize: 16, fontWeight: '800', color: Colors.text },
  headerSub:         { fontSize: 12, color: Colors.muted, marginTop: 2 },
  grupoBadge:        { backgroundColor: ACCENT + '18', borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  grupoText:         { fontSize: 11, fontWeight: '700', color: ACCENT },

  alertBox:          { flexDirection: 'row', alignItems: 'flex-start', gap: 8,
                       backgroundColor: Colors.amber + '18', borderRadius: 12, padding: 12 },
  alertText:         { fontSize: 13, color: Colors.text, flex: 1, lineHeight: 18 },

  section:           { backgroundColor: '#fff', borderRadius: 16, padding: 14,
                       shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  catTitle:          { fontSize: 12, fontWeight: '800', color: Colors.muted,
                       textTransform: 'uppercase', letterSpacing: .5, marginBottom: 8 },

  docRow:            { flexDirection: 'row', alignItems: 'center', gap: 12,
                       paddingVertical: 11, borderTopWidth: 1, borderTopColor: Colors.border },
  docRowFirst:       { borderTopWidth: 0 },
  docRowDisabled:    { opacity: 0.45 },
  docIconBox:        { width: 40, height: 40, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  docLabel:          { fontSize: 14, fontWeight: '600', color: Colors.text },
  docLabelDisabled:  { color: Colors.muted },
  docSub:            { fontSize: 11, fontWeight: '600', marginTop: 2 },
  dlBtn:             { width: 32, height: 32, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },

  footNote:          { fontSize: 11, color: Colors.muted, textAlign: 'center', lineHeight: 16 },
})
