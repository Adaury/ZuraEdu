import React from 'react'
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, TouchableOpacity } from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { comunicadosApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

export default function ComunicadosEstudiante() {
  const { data, isLoading } = useQuery({
    queryKey: ['comunicados'],
    queryFn:  () => comunicadosApi.index().then(r => r.data),
  })

  const items: any[] = data?.data ?? data ?? []

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>Noticias</Text>

        {isLoading && <ActivityIndicator color={Colors.blue} style={{ marginTop: 40 }} />}

        {items.map((c: any, i: number) => (
          <View key={i} style={styles.card}>
            <View style={styles.cardHeader}>
              <View style={styles.iconBox}>
                <Ionicons name="megaphone" size={18} color={Colors.blue} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.cardTitle}>{c.titulo}</Text>
                <Text style={styles.cardMeta}>{c.autor?.name ?? '—'} · {c.published_at}</Text>
              </View>
            </View>
            <Text style={styles.cardBody} numberOfLines={4}>
              {c.cuerpo?.replace(/<[^>]*>/g, '') ?? ''}
            </Text>
          </View>
        ))}

        {!isLoading && items.length === 0 && (
          <View style={styles.empty}>
            <Ionicons name="megaphone-outline" size={48} color={Colors.muted} />
            <Text style={styles.emptyTxt}>No hay comunicados publicados.</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:       { flex: 1, backgroundColor: Colors.bg },
  content:    { padding: 16, paddingBottom: 32, gap: 12 },
  title:      { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  card:       { backgroundColor: '#fff', borderRadius: 16, padding: 14, gap: 10, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  cardHeader: { flexDirection: 'row', alignItems: 'flex-start', gap: 10 },
  iconBox:    { width: 38, height: 38, borderRadius: 10, backgroundColor: Colors.blue + '15', alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  cardTitle:  { fontSize: 14, fontWeight: '700', color: Colors.text },
  cardMeta:   { fontSize: 11, color: Colors.muted, marginTop: 2 },
  cardBody:   { fontSize: 13, color: '#475569', lineHeight: 20 },
  empty:      { alignItems: 'center', marginTop: 60, gap: 12 },
  emptyTxt:   { color: Colors.muted, fontSize: 14 },
})
