import React, { useState } from 'react'
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, Alert,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { StatusBar } from 'expo-status-bar'
import { useAuth } from '../context/AuthContext'
import { Colors } from '../constants/Colors'

export default function LoginScreen() {
  const { login } = useAuth()
  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [loading,  setLoading]  = useState(false)
  const [showPass, setShowPass] = useState(false)

  const handleLogin = async () => {
    if (!email.trim() || !password) {
      Alert.alert('Campos requeridos', 'Ingresa tu correo y contraseña.')
      return
    }
    setLoading(true)
    try {
      await login(email.trim().toLowerCase(), password)
    } catch (err: any) {
      const msg = err?.response?.data?.message ?? 'Credenciales incorrectas. Intenta de nuevo.'
      Alert.alert('Error al iniciar sesión', msg)
    } finally {
      setLoading(false)
    }
  }

  return (
    <SafeAreaView style={styles.safe}>
      <StatusBar style="light" />
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">

          {/* Hero */}
          <View style={styles.hero}>
            <View style={styles.logoBox}>
              <Text style={styles.logoText}>Z</Text>
            </View>
            <Text style={styles.appName}>ZuraEdu</Text>
            <Text style={styles.appSub}>Sistema de Gestión Escolar</Text>
          </View>

          {/* Card */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Iniciar Sesión</Text>
            <Text style={styles.cardSub}>Ingresa con tus credenciales institucionales</Text>

            <Text style={styles.label}>Correo electrónico</Text>
            <TextInput
              style={styles.input}
              value={email}
              onChangeText={setEmail}
              placeholder="usuario@centro.edu"
              placeholderTextColor="#94a3b8"
              keyboardType="email-address"
              autoCapitalize="none"
              autoComplete="email"
              returnKeyType="next"
            />

            <Text style={styles.label}>Contraseña</Text>
            <View style={styles.passRow}>
              <TextInput
                style={[styles.input, { flex: 1, marginBottom: 0 }]}
                value={password}
                onChangeText={setPassword}
                placeholder="••••••••"
                placeholderTextColor="#94a3b8"
                secureTextEntry={!showPass}
                autoComplete="password"
                returnKeyType="done"
                onSubmitEditing={handleLogin}
              />
              <TouchableOpacity onPress={() => setShowPass(p => !p)} style={styles.eyeBtn}>
                <Text style={{ color: Colors.muted, fontSize: 16 }}>{showPass ? '🙈' : '👁'}</Text>
              </TouchableOpacity>
            </View>

            <TouchableOpacity
              style={[styles.btn, loading && styles.btnDisabled]}
              onPress={handleLogin}
              disabled={loading}
              activeOpacity={0.85}
            >
              {loading
                ? <ActivityIndicator color="#fff" />
                : <Text style={styles.btnText}>Entrar</Text>
              }
            </TouchableOpacity>

            <View style={styles.roleHints}>
              {[
                { role: 'Estudiante', color: Colors.roles.estudiante },
                { role: 'Padre/Rep.', color: Colors.roles.padre },
                { role: 'Docente',    color: Colors.roles.docente },
              ].map(({ role, color }) => (
                <View key={role} style={[styles.chip, { borderColor: color }]}>
                  <View style={[styles.dot, { backgroundColor: color }]} />
                  <Text style={[styles.chipText, { color }]}>{role}</Text>
                </View>
              ))}
            </View>
          </View>

          <Text style={styles.footer}>© {new Date().getFullYear()} ZuraEdu · v1.0</Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:       { flex: 1, backgroundColor: Colors.primary },
  scroll:     { flexGrow: 1, padding: 24, paddingBottom: 40 },
  hero:       { alignItems: 'center', paddingVertical: 40 },
  logoBox:    { width: 80, height: 80, borderRadius: 22, backgroundColor: 'rgba(255,255,255,.18)', alignItems: 'center', justifyContent: 'center', marginBottom: 14 },
  logoText:   { fontSize: 38, fontWeight: '900', color: '#fff' },
  appName:    { fontSize: 30, fontWeight: '900', color: '#fff', letterSpacing: .5 },
  appSub:     { fontSize: 14, color: 'rgba(255,255,255,.7)', marginTop: 4 },
  card:       { backgroundColor: '#fff', borderRadius: 24, padding: 24, shadowColor: '#000', shadowOpacity: .12, shadowRadius: 20, elevation: 8 },
  cardTitle:  { fontSize: 22, fontWeight: '800', color: Colors.text, marginBottom: 4 },
  cardSub:    { fontSize: 13, color: Colors.muted, marginBottom: 24 },
  label:      { fontSize: 13, fontWeight: '600', color: Colors.text, marginBottom: 6, marginTop: 12 },
  input:      { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, padding: 14, fontSize: 15, color: Colors.text, backgroundColor: '#f8fafc', marginBottom: 4 },
  passRow:    { flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 4 },
  eyeBtn:     { padding: 12 },
  btn:        { backgroundColor: Colors.primary, borderRadius: 14, padding: 16, alignItems: 'center', marginTop: 24 },
  btnDisabled:{ opacity: .65 },
  btnText:    { color: '#fff', fontSize: 16, fontWeight: '800', letterSpacing: .3 },
  roleHints:  { flexDirection: 'row', justifyContent: 'center', gap: 8, marginTop: 20, flexWrap: 'wrap' },
  chip:       { flexDirection: 'row', alignItems: 'center', gap: 5, borderWidth: 1.5, borderRadius: 99, paddingHorizontal: 10, paddingVertical: 4 },
  dot:        { width: 7, height: 7, borderRadius: 99 },
  chipText:   { fontSize: 12, fontWeight: '700' },
  footer:     { textAlign: 'center', color: 'rgba(255,255,255,.4)', fontSize: 12, marginTop: 24 },
})
