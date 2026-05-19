import React from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Linking, ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { documentosApi, API_BASE } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function DocumentosEstudiante() {
  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['documentos-estudiante'],
    queryFn: () => documentosApi.info().then(r => r.data),
  })

  const token     = data?.download_token
  const categorias = data?.documentos ?? []

  const openDoc = (rutaWeb: string, disponible: boolean) => {
    if (!disponible || !token) return
    Linking.openURL(`${API_BASE}${rutaWeb}?download_token=${token}`)
  }

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={Colors.blue} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.blue} />}
      >
        {/* Header */}
        <View style={styles.headerCard}>
          <View style={[styles.headerIcon, { backgroundColor: Colors.blue + '18' }]}>
            <Ionicons name="folder-open" size={28} color={Colors.blue} />
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

        {!data?.tiene_matricula && (
          <View style={styles.alertBox}>
            <Ionicons name="information-circle" size={18} color={Colors.amber} />
            <Text style={styles.alertText}>No tienes matrícula activa este período. Algunos documentos no están disponibles.</Text>
          </View>
        )}

        {categorias.map((cat: any) => (
          <View key={cat.categoria} style={styles.section}>
            <Text style={styles.catTitle}>{cat.categoria}</Text>
            {cat.items.map((item: any, idx: number) => (
              <TouchableOpacity
                key={item.id}
                style={[styles.docRow, idx === 0 && styles.docRowFirst, !item.disponible && styles.docRowDisabled]}
                onPress={() => openDoc(item.ruta_web, item.disponible)}
                activeOpacity={item.disponible ? 0.7 : 1}
              >
                <View style={[styles.docIconBox, { backgroundColor: item.color + '18' }]}>
                  <Ionicons
                    name={item.icono as any}
                    size={20}
                    color={item.disponible ? item.color : Colors.muted}
                  />
                </View>
                <Text style={[styles.docLabel, !item.disponible && styles.docLabelDisabled]}>
                  {item.label}
                </Text>
                <Ionicons
                  name={item.disponible ? 'open-outline' : 'lock-closed-outline'}
                  size={16}
                  color={item.disponible ? Colors.muted : Colors.border}
                />
              </TouchableOpacity>
            ))}
          </View>
        ))}

        <Text style={styles.footNote}>
          Los documentos se abren en el navegador. El enlace es válido por 60 minutos.
        </Text>
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:             { flex: 1, backgroundColor: Colors.bg },
  content:          { padding: 16, gap: 14, paddingBottom: 32 },
  headerCard:       { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#fff', borderRadius: 16, padding: 16, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  headerIcon:       { width: 52, height: 52, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  headerName:       { fontSize: 16, fontWeight: '800', color: Colors.text },
  headerSub:        { fontSize: 12, color: Colors.muted, marginTop: 2 },
  grupoBadge:       { backgroundColor: Colors.blue + '18', borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  grupoText:        { fontSize: 11, fontWeight: '700', color: Colors.blue },
  alertBox:         { flexDirection: 'row', alignItems: 'flex-start', gap: 8, backgroundColor: Colors.amber + '18', borderRadius: 12, padding: 12 },
  alertText:        { fontSize: 13, color: Colors.text, flex: 1, lineHeight: 18 },
  section:          { backgroundColor: '#fff', borderRadius: 16, padding: 14, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  catTitle:         { fontSize: 12, fontWeight: '800', color: Colors.muted, textTransform: 'uppercase', letterSpacing: .5, marginBottom: 8 },
  docRow:           { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 10, borderTopWidth: 1, borderTopColor: Colors.border },
  docRowFirst:      { borderTopWidth: 0 },
  docRowDisabled:   { opacity: 0.45 },
  docIconBox:       { width: 40, height: 40, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  docLabel:         { flex: 1, fontSize: 14, fontWeight: '600', color: Colors.text },
  docLabelDisabled: { color: Colors.muted },
  footNote:         { fontSize: 11, color: Colors.muted, textAlign: 'center', lineHeight: 16 },
})
