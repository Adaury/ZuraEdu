import React, { useState } from 'react'
import {
  View, Text, ScrollView, StyleSheet, ActivityIndicator,
  TouchableOpacity,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useRouter } from 'expo-router'
import { dashboardApi, riesgoApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

// ── Helpers ───────────────────────────────────────────────────────────────────

function dimColor(v: number) {
  return v > 60 ? Colors.red : v > 30 ? Colors.amber : Colors.green
}

function nivelMsg(nivel: string) {
  const map: Record<string, { titulo: string; texto: string }> = {
    sin_riesgo: { titulo: 'Excelente desempeño',     texto: 'Su representado/a no presenta señales de riesgo académico.' },
    bajo:       { titulo: 'Desempeño satisfactorio', texto: 'El desempeño es bueno. Mantenga el seguimiento habitual.' },
    moderado:   { titulo: 'Atención moderada',       texto: 'Hay áreas que requieren atención. Sugiera usar el Tutor IA.' },
    alto:       { titulo: 'Requiere intervención',   texto: 'Comuníquese con la coordinación para un plan de apoyo.' },
    critico:    { titulo: 'Situación crítica',       texto: 'Es urgente contactar al coordinador académico.' },
  }
  return map[nivel] ?? map.sin_riesgo
}

// ── Tarjeta de dimensión ──────────────────────────────────────────────────────

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

export default function RiesgoPadre() {
  const router = useRouter()

  // Hijos desde el dashboard
  const { data: dash, isLoading: dashLoading } = useQuery({
    queryKey:  ['dashboard'],
    queryFn:   () => dashboardApi.index().then(r => r.data),
    staleTime: 60_000,
  })

  const hijos: { id: number; nombre: string; grupo?: string }[] =
    dash?.hijos ?? dash?.data?.hijos ?? []

  const [hijoId, setHijoId] = useState<number | null>(null)
  const selectedId = hijoId ?? hijos[0]?.id ?? null

  // Risk score del hijo seleccionado
  const { data, isLoading, isError, refetch } = useQuery({
    queryKey:  ['riesgo-hijo', selectedId],
    queryFn:   () => riesgoApi.hijo(selectedId!).then(r => r.data),
    enabled:   selectedId !== null,
  })

  if (dashLoading) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <ActivityIndicator color={Colors.purple} style={{ marginTop: 60 }} />
      </SafeAreaView>
    )
  }

  if (hijos.length === 0) {
    return (
      <SafeAreaView style={styles.safe} edges={['bottom']}>
        <View style={styles.centered}>
          <Ionicons name="person-outline" size={48} color={Colors.muted} />
          <Text style={styles.emptyText}>No hay estudiantes asociados a su cuenta.</Text>
        </View>
      </SafeAreaView>
    )
  }

  const hijoActual = hijos.find(h => h.id === selectedId) ?? hijos[0]
  const color  = data?.nivel_color ?? Colors.purple
  const score  = data?.score as number | undefined
  const nivel  = data?.nivel ?? 'sin_riesgo'
  const msg    = nivelMsg(nivel)

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <ScrollView contentContainerStyle={styles.content}>

        {/* ── Selector de hijo ── */}
        {hijos.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.selectorRow}>
            {hijos.map(h => (
              <TouchableOpacity
                key={h.id}
                onPress={() => setHijoId(h.id)}
                style={[styles.selectorPill, selectedId === h.id && styles.selectorPillActive]}
              >
                <Text style={[styles.selectorText, selectedId === h.id && styles.selectorTextActive]}>
                  {h.nombre.split(' ')[0]}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        )}

        {/* nombre del hijo */}
        <View style={styles.hijoRow}>
          <View style={styles.hijoAvatar}>
            <Text style={styles.hijoAvatarTxt}>{hijoActual?.nombre?.[0] ?? '?'}</Text>
          </View>
          <View>
            <Text style={styles.hijoNombre}>{hijoActual?.nombre}</Text>
            <Text style={styles.hijoGrupo}>{hijoActual?.grupo ?? ''}</Text>
          </View>
        </View>

        {/* ── Loading / Error del score ── */}
        {isLoading && <ActivityIndicator color={Colors.purple} style={{ marginVertical: 40 }} />}

        {isError && (
          <View style={styles.centered}>
            <Ionicons name="cloud-offline-outline" size={40} color={Colors.muted} />
            <Text style={styles.emptyText}>Error al cargar. Toca para reintentar.</Text>
            <TouchableOpacity style={styles.retryBtn} onPress={() => refetch()}>
              <Text style={styles.retryText}>Reintentar</Text>
            </TouchableOpacity>
          </View>
        )}

        {!isLoading && !isError && data && !data.calculado && (
          <View style={[styles.centered, { marginVertical: 20 }]}>
            <Ionicons name="hourglass-outline" size={40} color={Colors.muted} />
            <Text style={styles.emptyTitle}>Sin datos aún</Text>
            <Text style={styles.emptyText}>La evaluación de riesgo académico aún no ha sido calculada.</Text>
          </View>
        )}

        {!isLoading && !isError && data?.calculado && (
          <>
            {/* ── Hero ── */}
            <View style={[styles.hero, { borderColor: color + '55', backgroundColor: color + '11' }]}>
              <View style={styles.heroLeft}>
                <Text style={[styles.heroScore, { color }]}>{score}</Text>
                <Text style={[styles.heroNivel, { color }]}>{data.nivel_label}</Text>
              </View>
              <View style={styles.heroRight}>
                <Text style={styles.heroTitle}>{msg.titulo}</Text>
                <Text style={styles.heroText}>{msg.texto}</Text>
                <View style={styles.gaugeBg}>
                  <View style={[styles.gaugeMarker, {
                    left: `${Math.min(score ?? 0, 99)}%` as any,
                    borderColor: color,
                  }]} />
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
                { k: 'Materias con dificultad', v: `${data.materias_en_riesgo} de ${data.total_materias}` },
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
                { k: 'Tardanzas',        v: data.tardanzas },
                { k: 'Obs. leves',       v: data.faltas_leves },
                { k: 'Obs. graves',      v: data.faltas_graves },
                { k: 'Suspensiones',     v: data.suspensiones },
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

            {/* ── CTA ── */}
            {(score ?? 0) >= 60 && (
              <View style={[styles.ctaCard, { backgroundColor: Colors.red }]}>
                <Ionicons name="call" size={26} color="#fff" style={{ marginRight: 12 }} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.ctaTitle}>Contacte a la institución</Text>
                  <Text style={styles.ctaText}>El nivel de riesgo es alto. Coordine con la dirección académica un plan de apoyo.</Text>
                </View>
              </View>
            )}

            {(score ?? 0) >= 40 && (score ?? 0) < 60 && (
              <TouchableOpacity
                style={[styles.ctaCard, { backgroundColor: Colors.purple }]}
                onPress={() => router.push('/(padre)/tutor')}
                activeOpacity={0.85}
              >
                <Ionicons name="sparkles" size={26} color="#fff" style={{ marginRight: 12 }} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.ctaTitle}>El Tutor IA puede ayudar</Text>
                  <Text style={styles.ctaText}>Hay áreas de mejora. El Tutor IA puede orientar a su representado/a.</Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color="#fff" />
              </TouchableOpacity>
            )}

            {data.calculado_en && (
              <Text style={styles.updatedAt}>
                Actualizado: {new Date(data.calculado_en).toLocaleDateString('es-DO')}
              </Text>
            )}
          </>
        )}

      </ScrollView>
    </SafeAreaView>
  )
}

// ── Estilos ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.bg },
  content:           { padding: 16, paddingBottom: 40, gap: 10 },
  centered:          { alignItems: 'center', justifyContent: 'center', padding: 24, gap: 10 },

  selectorRow:       { marginBottom: 2 },
  selectorPill:      { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 99,
                       paddingHorizontal: 16, paddingVertical: 7, marginRight: 8 },
  selectorPillActive:{ backgroundColor: Colors.purple, borderColor: Colors.purple },
  selectorText:      { fontSize: 13, fontWeight: '600', color: Colors.muted },
  selectorTextActive:{ color: '#fff' },

  hijoRow:           { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: 4 },
  hijoAvatar:        { width: 40, height: 40, borderRadius: 20,
                       backgroundColor: Colors.purple + '22', alignItems: 'center', justifyContent: 'center' },
  hijoAvatarTxt:     { fontSize: 18, fontWeight: '900', color: Colors.purple },
  hijoNombre:        { fontSize: 15, fontWeight: '800', color: Colors.text },
  hijoGrupo:         { fontSize: 12, color: Colors.muted },

  hero:              { borderRadius: 16, borderWidth: 2, padding: 16, flexDirection: 'row', gap: 14 },
  heroLeft:          { alignItems: 'center', justifyContent: 'center', minWidth: 80 },
  heroScore:         { fontSize: 48, fontWeight: '900', lineHeight: 52 },
  heroNivel:         { fontSize: 12, fontWeight: '800', marginTop: 2 },
  heroRight:         { flex: 1 },
  heroTitle:         { fontSize: 15, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  heroText:          { fontSize: 12, color: Colors.muted, marginBottom: 10, lineHeight: 17 },

  gaugeBg:           { height: 8, borderRadius: 99, backgroundColor: '#e2e8f0', position: 'relative', overflow: 'visible' },
  gaugeMarker:       { position: 'absolute', top: -5, width: 18, height: 18, borderRadius: 99,
                       backgroundColor: '#fff', borderWidth: 3, marginLeft: -9,
                       shadowColor: '#000', shadowOpacity: .15, shadowRadius: 4, elevation: 3 },
  gaugeLabels:       { flexDirection: 'row', justifyContent: 'space-between', marginTop: 4 },
  gaugeLabel:        { fontSize: 10, color: Colors.muted },

  sectionTitle:      { fontSize: 14, fontWeight: '800', color: Colors.text, marginTop: 4 },

  dimCard:           { backgroundColor: '#fff', borderRadius: 14, padding: 14,
                       borderLeftWidth: 4, gap: 6,
                       shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  dimHeader:         { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  dimLabel:          { fontSize: 13, fontWeight: '700', color: Colors.text, flex: 1 },
  dimScore:          { fontSize: 22, fontWeight: '900', lineHeight: 26 },
  barBg:             { height: 6, borderRadius: 99, backgroundColor: Colors.border, overflow: 'hidden' },
  barFill:           { height: '100%', borderRadius: 99 },
  detailRow:         { flexDirection: 'row', justifyContent: 'space-between' },
  detailKey:         { fontSize: 12, color: Colors.muted },
  detailVal:         { fontSize: 12, fontWeight: '700', color: Colors.text },

  ctaCard:           { borderRadius: 14, padding: 16, flexDirection: 'row', alignItems: 'center', marginTop: 4 },
  ctaTitle:          { fontSize: 14, fontWeight: '800', color: '#fff', marginBottom: 3 },
  ctaText:           { fontSize: 12, color: 'rgba(255,255,255,.85)', lineHeight: 17 },

  updatedAt:         { fontSize: 11, color: Colors.muted, textAlign: 'center', marginTop: 4 },
  emptyTitle:        { fontSize: 16, fontWeight: '800', color: Colors.text },
  emptyText:         { fontSize: 13, color: Colors.muted, textAlign: 'center' },
  retryBtn:          { backgroundColor: Colors.purple, borderRadius: 99, paddingHorizontal: 20, paddingVertical: 10, marginTop: 4 },
  retryText:         { color: '#fff', fontWeight: '700' },
})
