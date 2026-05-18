import React from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity, Linking,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useRouter } from 'expo-router'
import { riesgoApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

// ── Helpers ───────────────────────────────────────────────────────────────────

function dimColor(v: number) {
  return v > 60 ? Colors.red : v > 30 ? Colors.amber : Colors.green
}

function nivelMsg(nivel: string) {
  const map: Record<string, { titulo: string; texto: string }> = {
    sin_riesgo: { titulo: '¡Excelente trabajo!',    texto: 'Tu desempeño es sobresaliente. ¡Sigue así!' },
    bajo:       { titulo: '¡Vas muy bien!',          texto: 'Tu situación es buena. Mantén el ritmo.' },
    moderado:   { titulo: 'Atención moderada',       texto: 'Hay áreas donde puedes mejorar. Usa el Tutor IA para reforzar.' },
    alto:       { titulo: 'Requiere atención',       texto: 'Toma acción ahora. Habla con tu docente o usa el Tutor IA.' },
    critico:    { titulo: 'Situación crítica',       texto: 'Necesitas apoyo inmediato. Comunícate con tu coordinador.' },
  }
  return map[nivel] ?? map.sin_riesgo
}

// ── Componente de dimensión ───────────────────────────────────────────────────

function DimCard({ label, value, details }: {
  label: string
  value: number
  details: { k: string; v: string | number }[]
}) {
  const color = dimColor(value)
  return (
    <View style={[styles.dimCard, { borderLeftColor: color }]}>
      <View style={styles.dimHeader}>
        <Text style={styles.dimLabel}>{label}</Text>
        <Text style={[styles.dimScore, { color }]}>{Math.round(value)}</Text>
      </View>
      {/* barra */}
      <View style={styles.barBg}>
        <View style={[styles.barFill, { width: `${Math.min(value, 100)}%`, backgroundColor: color }]} />
      </View>
      {details.map((d, i) => (
        <View key={i} style={styles.detailRow}>
          <Text style={styles.detailKey}>{d.k}</Text>
          <Text style={styles.detailVal}>{d.v ?? '—'}</Text>
        </View>
      ))}
    </View>
  )
}

// ── Pantalla principal ────────────────────────────────────────────────────────

export default function RiesgoEstudiante() {
  const router = useRouter()
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['riesgo-estudiante'],
    queryFn:  () => riesgoApi.miScore().then(r => r.data),
  })

  if (isLoading) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <ActivityIndicator color={Colors.blue} style={{ marginTop: 60 }} />
      </SafeAreaView>
    )
  }

  if (isError) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.centered}>
          <Ionicons name="cloud-offline-outline" size={48} color={Colors.muted} />
          <Text style={styles.emptyText}>Error al cargar. Toca para reintentar.</Text>
          <TouchableOpacity style={styles.retryBtn} onPress={() => refetch()}>
            <Text style={styles.retryText}>Reintentar</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    )
  }

  if (!data?.calculado) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.centered}>
          <Ionicons name="hourglass-outline" size={48} color={Colors.muted} />
          <Text style={styles.emptyTitle}>Sin datos aún</Text>
          <Text style={styles.emptyText}>Tu evaluación de riesgo académico aún no ha sido calculada.</Text>
        </View>
      </SafeAreaView>
    )
  }

  const color   = data.nivel_color ?? Colors.amber
  const msg     = nivelMsg(data.nivel)
  const score   = data.score as number

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>

        {/* ── Hero ── */}
        <View style={[styles.hero, { borderColor: color + '55', backgroundColor: color + '11' }]}>
          <View style={styles.heroLeft}>
            <Text style={[styles.heroScore, { color }]}>{score}</Text>
            <Text style={[styles.heroNivel, { color }]}>{data.nivel_label}</Text>
          </View>
          <View style={styles.heroRight}>
            <Text style={styles.heroTitle}>{msg.titulo}</Text>
            <Text style={styles.heroText}>{msg.texto}</Text>
            {/* gauge */}
            <View style={styles.gaugeBg}>
              <View style={[styles.gaugeMarker, { left: `${Math.min(score, 99)}%` as any, borderColor: color }]} />
            </View>
            <View style={styles.gaugeLabels}>
              <Text style={styles.gaugeLabel}>Sin Riesgo</Text>
              <Text style={styles.gaugeLabel}>Crítico</Text>
            </View>
          </View>
        </View>

        {/* ── Dimensiones ── */}
        <Text style={styles.sectionTitle}>Dimensiones</Text>

        <DimCard
          label="Desempeño Académico (40%)"
          value={data.dim_academico}
          details={[
            { k: 'Materias a reforzar', v: `${data.materias_en_riesgo} de ${data.total_materias}` },
            { k: 'Promedio general', v: data.promedio_general != null ? Number(data.promedio_general).toFixed(1) : '—' },
          ]}
        />
        <DimCard
          label="Asistencia (30%)"
          value={data.dim_asistencia}
          details={[
            { k: '% Asistencia', v: data.pct_asistencia != null ? `${Number(data.pct_asistencia).toFixed(1)}%` : '—' },
          ]}
        />
        <DimCard
          label="Conducta (20%)"
          value={data.dim_disciplina}
          details={[
            { k: 'Tardanzas',           v: data.tardanzas },
            { k: 'Obs. leves',          v: data.faltas_leves },
            { k: 'Obs. graves',         v: data.faltas_graves },
            { k: 'Suspensiones',        v: data.suspensiones },
          ]}
        />
        <DimCard
          label="Tendencia (10%)"
          value={data.dim_tendencia}
          details={[
            {
              k: 'Dirección',
              v: data.dim_tendencia <= 10 ? 'Mejorando ↑'
               : data.dim_tendencia <= 30 ? 'Estable →'
               : data.dim_tendencia <= 60 ? 'Declive leve ↓'
               : 'Declive severo ↘',
            },
          ]}
        />

        {/* ── CTA Tutor IA ── */}
        {score >= 40 && (
          <TouchableOpacity
            style={styles.ctaCard}
            onPress={() => router.push('/(estudiante)/tutor')}
            activeOpacity={0.85}
          >
            <Ionicons name="sparkles" size={28} color="#fff" style={{ marginRight: 12 }} />
            <View style={{ flex: 1 }}>
              <Text style={styles.ctaTitle}>¿Necesitas apoyo?</Text>
              <Text style={styles.ctaText}>El Tutor IA puede ayudarte a reforzar las materias donde tienes dificultades.</Text>
            </View>
            <Ionicons name="chevron-forward" size={20} color="#fff" />
          </TouchableOpacity>
        )}

        {/* nota de actualización */}
        {data.calculado_en && (
          <Text style={styles.updatedAt}>
            Actualizado: {new Date(data.calculado_en).toLocaleDateString('es-DO')}
          </Text>
        )}

      </ScrollView>
    </SafeAreaView>
  )
}

// ── Estilos ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  safe:         { flex: 1, backgroundColor: Colors.bg },
  content:      { padding: 16, paddingBottom: 40, gap: 10 },
  centered:     { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 32, gap: 12 },

  hero:         { borderRadius: 16, borderWidth: 2, padding: 16, flexDirection: 'row', gap: 14 },
  heroLeft:     { alignItems: 'center', justifyContent: 'center', minWidth: 80 },
  heroScore:    { fontSize: 48, fontWeight: '900', lineHeight: 52 },
  heroNivel:    { fontSize: 12, fontWeight: '800', marginTop: 2 },
  heroRight:    { flex: 1 },
  heroTitle:    { fontSize: 15, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  heroText:     { fontSize: 12, color: Colors.muted, marginBottom: 10, lineHeight: 17 },

  gaugeBg:      { height: 8, borderRadius: 99, overflow: 'visible', position: 'relative',
                  background: 'linear-gradient(to right,#22c55e,#84cc16,#f59e0b,#f97316,#ef4444)' as any,
                  backgroundColor: '#e2e8f0' },
  gaugeMarker:  { position: 'absolute', top: -5, width: 18, height: 18, borderRadius: 99,
                  backgroundColor: '#fff', borderWidth: 3, marginLeft: -9,
                  shadowColor: '#000', shadowOpacity: .15, shadowRadius: 4, elevation: 3 },
  gaugeLabels:  { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  gaugeLabel:   { fontSize: 10, color: Colors.muted },

  sectionTitle: { fontSize: 14, fontWeight: '800', color: Colors.text, marginTop: 4 },

  dimCard:      { backgroundColor: '#fff', borderRadius: 14, padding: 14,
                  borderLeftWidth: 4, gap: 6,
                  shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  dimHeader:    { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  dimLabel:     { fontSize: 13, fontWeight: '700', color: Colors.text, flex: 1 },
  dimScore:     { fontSize: 22, fontWeight: '900', lineHeight: 26 },
  barBg:        { height: 6, borderRadius: 99, backgroundColor: Colors.border, overflow: 'hidden' },
  barFill:      { height: '100%', borderRadius: 99 },
  detailRow:    { flexDirection: 'row', justifyContent: 'space-between' },
  detailKey:    { fontSize: 12, color: Colors.muted },
  detailVal:    { fontSize: 12, fontWeight: '700', color: Colors.text },

  ctaCard:      { backgroundColor: Colors.indigo, borderRadius: 14, padding: 16,
                  flexDirection: 'row', alignItems: 'center', marginTop: 6 },
  ctaTitle:     { fontSize: 14, fontWeight: '800', color: '#fff', marginBottom: 3 },
  ctaText:      { fontSize: 12, color: 'rgba(255,255,255,.85)', lineHeight: 17 },

  updatedAt:    { fontSize: 11, color: Colors.muted, textAlign: 'center', marginTop: 4 },
  emptyTitle:   { fontSize: 16, fontWeight: '800', color: Colors.text },
  emptyText:    { fontSize: 13, color: Colors.muted, textAlign: 'center' },
  retryBtn:     { backgroundColor: Colors.blue, borderRadius: 99, paddingHorizontal: 20, paddingVertical: 10, marginTop: 8 },
  retryText:    { color: '#fff', fontWeight: '700' },
})
