import React from 'react'
import { View, Text, StyleSheet, ViewStyle } from 'react-native'
import { Colors } from '../../constants/Colors'

interface Props {
  title?: string
  children: React.ReactNode
  style?: ViewStyle
  accent?: string
}

export function Card({ title, children, style, accent }: Props) {
  return (
    <View style={[styles.card, style]}>
      {accent && <View style={[styles.accent, { backgroundColor: accent }]} />}
      {title && <Text style={styles.title}>{title}</Text>}
      {children}
    </View>
  )
}

interface KpiProps {
  label: string
  value: string | number
  sub?: string
  color?: string
  style?: ViewStyle
}

export function KpiCard({ label, value, sub, color = Colors.primary, style }: KpiProps) {
  return (
    <View style={[styles.kpi, { borderLeftColor: color }, style]}>
      <Text style={[styles.kpiValue, { color }]}>{value}</Text>
      <Text style={styles.kpiLabel}>{label}</Text>
      {sub && <Text style={styles.kpiSub}>{sub}</Text>}
    </View>
  )
}

const styles = StyleSheet.create({
  card:      { backgroundColor: '#fff', borderRadius: 16, padding: 16, shadowColor: '#000', shadowOpacity: .06, shadowRadius: 12, elevation: 3, overflow: 'hidden' },
  accent:    { position: 'absolute', top: 0, left: 0, right: 0, height: 4 },
  title:     { fontSize: 14, fontWeight: '700', color: Colors.text, marginBottom: 12 },
  kpi:       { backgroundColor: '#fff', borderRadius: 14, padding: 14, borderLeftWidth: 4, shadowColor: '#000', shadowOpacity: .05, shadowRadius: 8, elevation: 2 },
  kpiValue:  { fontSize: 26, fontWeight: '900', lineHeight: 30 },
  kpiLabel:  { fontSize: 12, color: Colors.muted, fontWeight: '600', marginTop: 2 },
  kpiSub:    { fontSize: 11, color: Colors.muted, marginTop: 2 },
})
