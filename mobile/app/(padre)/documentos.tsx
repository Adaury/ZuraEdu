import React, { useState } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  Linking, ActivityIndicator, RefreshControl,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { classroomApi, documentosApi, API_BASE } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function DocumentosPadre() {
  const [selectedHijo, setSelectedHijo] = useState<{ id: number; nombre: string } | null>(null)

  // Obtener lista de hijos
  const { data: clData, isLoading: clLoading } = useQuery({
    queryKey: ['classroom-padre'],
    queryFn:  () => classroomApi.index().then(r => r.data),
  })

  const hijos: { id: number; nombre: string }[] = React.useMemo(() => {
    const clases = clData?.aulas ?? clData?.clases ?? clData ?? []
    const seen   = new Set<number>()
    const list: { id: number; nombre: string }[] = []
    for (const c of clases) {
      if (c.estudiante_id && !seen.has(c.estudiante_id)) {
        seen.add(c.estudiante_id)
        list.push({ id: c.estudiante_id, nombre: c.estudiante ?? c.nombre_estudiante ?? `Hijo #${c.estudiante_id}` })
      }
    }
    return list
  }, [clData])

  // Auto-seleccionar primer hijo
  React.useEffect(() => {
    if (hijos.length > 0 && !selectedHijo) setSelectedHijo(hijos[0])
  }, [hijos])

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['documentos-hijo', selectedHijo?.id],
    queryFn:  () => documentosApi.infoHijo(selectedHijo!.id).then(r => r.data),
    enabled:  !!selectedHijo,
  })

  const token      = data?.download_token
  const categorias = data?.documentos ?? []

  const openDoc = (rutaWeb: string, disponible: boolean) => {
    if (!disponible || !token) return
    Linking.openURL(`${API_BASE}${rutaWeb}?download_token=${token}`)
  }

  if (clLoading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator style={{ marginTop: 60 }} color={Colors.roles.padre} />
      </SafeAreaView>
    )
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={Colors.roles.padre} />}
      >
        {/* Selector de hijo */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectorWrap}>
            {hijos.map(h => (
              <TouchableOpacity
                key={h.id}
                style={[styles.hijoChip, selectedHijo?.id === h.id && styles.hijoChipActive]}
                onPress={() => setSelectedHijo(h)}
              >
                <Text style={[styles.hijoChipText, selectedHijo?.id === h.id && styles.hijoChipTextActive]}>
                  {h.nombre.split(' ')[0]}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* Header */}
        {data && (
          <View style={styles.headerCard}>
            <View style={[styles.headerIcon, { backgroundColor: Colors.roles.padre + '18' }]}>
              <Ionicons name="folder-open" size={28} color={Colors.roles.padre} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.headerName}>{data.estudiante}</Text>
              <Text style={styles.headerSub}>{data.school_year ?? 'Período actual'}</Text>
            </View>
            {data.grupo && (
              <View style={styles.grupoBadge}>
                <Text style={styles.grupoText}>{data.grupo}</Text>
              </View>
            )}
          </View>
        )}

        {data && !data.tiene_matricula && (
          <View style={styles.alertBox}>
            <Ionicons name="information-circle" size={18} color={Colors.amber} />
            <Text style={styles.alertText}>Este estudiante no tiene matrícula activa. Algunos documentos no están disponibles.</Text>
          </View>
        )}

        {isLoading && !data ? (
          <ActivityIndicator style={{ marginTop: 40 }} color={Colors.roles.padre} />
        ) : categorias.map((cat: any) => (
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

        {categorias.length > 0 && (
          <Text style={styles.footNote}>
            Los documentos se abren en el navegador. El enlace es válido por 60 minutos.
          </Text>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:             { flex: 1, backgroundColor: Colors.bg },
  content:          { padding: 16, gap: 14, paddingBottom: 32 },
  selectorWrap:     { marginBottom: 2 },
  hijoChip:         { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 20, paddingHorizontal: 14, paddingVertical: 7, marginRight: 8, backgroundColor: '#fff' },
  hijoChipActive:   { borderColor: Colors.roles.padre, backgroundColor: Colors.roles.padre + '12' },
  hijoChipText:     { fontSize: 13, fontWeight: '600', color: Colors.muted },
  hijoChipTextActive:{ color: Colors.roles.padre },
  headerCard:       { flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: '#fff', borderRadius: 16, padding: 16, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  headerIcon:       { width: 52, height: 52, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  headerName:       { fontSize: 16, fontWeight: '800', color: Colors.text },
  headerSub:        { fontSize: 12, color: Colors.muted, marginTop: 2 },
  grupoBadge:       { backgroundColor: Colors.roles.padre + '18', borderRadius: 8, paddingHorizontal: 8, paddingVertical: 4 },
  grupoText:        { fontSize: 11, fontWeight: '700', color: Colors.roles.padre },
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
