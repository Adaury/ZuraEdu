import React, { useState, useRef } from 'react'
import {
  View, Text, TextInput, TouchableOpacity, StyleSheet,
  ActivityIndicator, KeyboardAvoidingView, Platform,
  ScrollView, TextInput as RNTextInput,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { StatusBar } from 'expo-status-bar'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../context/AuthContext'
import { Colors } from '../constants/Colors'

const ROLES = [
  { label: 'Estudiante',  color: Colors.roles.estudiante },
  { label: 'Padre / Rep', color: Colors.roles.padre      },
  { label: 'Docente',     color: Colors.roles.docente    },
  { label: 'Admin',       color: Colors.roles.admin      },
]

export default function LoginScreen() {
  const { login } = useAuth()

  const [email,    setEmail]    = useState('')
  const [password, setPassword] = useState('')
  const [loading,  setLoading]  = useState(false)
  const [showPass, setShowPass] = useState(false)
  const [error,    setError]    = useState<string | null>(null)
  const [focused,  setFocused]  = useState<'email' | 'pass' | null>(null)

  const passRef = useRef<RNTextInput>(null)

  const handleLogin = async () => {
    setError(null)
    if (!email.trim()) { setError('Ingresa tu correo electrónico.'); return }
    if (!password)     { setError('Ingresa tu contraseña.');         return }

    setLoading(true)
    try {
      await login(email.trim().toLowerCase(), password)
    } catch (err: any) {
      const msg = err?.response?.data?.message ?? 'Credenciales incorrectas. Intenta de nuevo.'
      setError(msg)
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
        <ScrollView
          contentContainerStyle={styles.scroll}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >

          {/* ─── Branding ─── */}
          <View style={styles.hero}>
            <View style={styles.logoWrap}>
              <View style={styles.logoBox}>
                <Ionicons name="school" size={36} color="#fff" />
              </View>
              <View style={styles.logoPulse} />
            </View>
            <Text style={styles.appName}>ZuraEdu</Text>
            <Text style={styles.appSub}>Sistema de Gestión Escolar</Text>
          </View>

          {/* ─── Card ─── */}
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Iniciar Sesión</Text>
            <Text style={styles.cardSub}>Ingresa con tus credenciales institucionales</Text>

            {/* Email */}
            <View style={styles.fieldWrap}>
              <Text style={styles.label}>Correo electrónico</Text>
              <View style={[
                styles.inputWrap,
                focused === 'email' && styles.inputWrapFocused,
                !!error && !email.trim() && styles.inputWrapError,
              ]}>
                <Ionicons
                  name="mail-outline"
                  size={18}
                  color={focused === 'email' ? Colors.primary : Colors.muted}
                  style={styles.inputIcon}
                />
                <TextInput
                  style={styles.input}
                  value={email}
                  onChangeText={t => { setEmail(t); setError(null) }}
                  placeholder="usuario@centro.edu"
                  placeholderTextColor="#94a3b8"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                  autoComplete="email"
                  returnKeyType="next"
                  onFocus={() => setFocused('email')}
                  onBlur={() => setFocused(null)}
                  onSubmitEditing={() => passRef.current?.focus()}
                />
              </View>
            </View>

            {/* Contraseña */}
            <View style={styles.fieldWrap}>
              <Text style={styles.label}>Contraseña</Text>
              <View style={[
                styles.inputWrap,
                focused === 'pass' && styles.inputWrapFocused,
                !!error && !password && styles.inputWrapError,
              ]}>
                <Ionicons
                  name="lock-closed-outline"
                  size={18}
                  color={focused === 'pass' ? Colors.primary : Colors.muted}
                  style={styles.inputIcon}
                />
                <TextInput
                  ref={passRef}
                  style={[styles.input, { flex: 1 }]}
                  value={password}
                  onChangeText={t => { setPassword(t); setError(null) }}
                  placeholder="••••••••"
                  placeholderTextColor="#94a3b8"
                  secureTextEntry={!showPass}
                  autoComplete="password"
                  returnKeyType="done"
                  onFocus={() => setFocused('pass')}
                  onBlur={() => setFocused(null)}
                  onSubmitEditing={handleLogin}
                />
                <TouchableOpacity
                  onPress={() => setShowPass(v => !v)}
                  style={styles.eyeBtn}
                  hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                >
                  <Ionicons
                    name={showPass ? 'eye-off-outline' : 'eye-outline'}
                    size={20}
                    color={Colors.muted}
                  />
                </TouchableOpacity>
              </View>
            </View>

            {/* Error inline */}
            {!!error && (
              <View style={styles.errorBox}>
                <Ionicons name="alert-circle" size={15} color={Colors.red} />
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}

            {/* Botón */}
            <TouchableOpacity
              style={[styles.btn, loading && styles.btnDisabled]}
              onPress={handleLogin}
              disabled={loading}
              activeOpacity={0.85}
            >
              {loading ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <>
                  <Text style={styles.btnText}>Entrar</Text>
                  <Ionicons name="arrow-forward" size={18} color="#fff" />
                </>
              )}
            </TouchableOpacity>

            {/* Divisor */}
            <View style={styles.divider}>
              <View style={styles.dividerLine} />
              <Text style={styles.dividerText}>acceso por rol</Text>
              <View style={styles.dividerLine} />
            </View>

            {/* Chips de roles */}
            <View style={styles.chips}>
              {ROLES.map(({ label, color }) => (
                <View key={label} style={[styles.chip, { borderColor: color + '50' }]}>
                  <View style={[styles.chipDot, { backgroundColor: color }]} />
                  <Text style={[styles.chipText, { color }]}>{label}</Text>
                </View>
              ))}
            </View>
          </View>

          <Text style={styles.footer}>
            © {new Date().getFullYear()} ZuraEdu · v1.0
          </Text>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}

const styles = StyleSheet.create({
  safe:              { flex: 1, backgroundColor: Colors.primary },
  scroll:            { flexGrow: 1, paddingHorizontal: 24, paddingBottom: 40 },

  // Branding
  hero:              { alignItems: 'center', paddingTop: 48, paddingBottom: 36 },
  logoWrap:          { position: 'relative', marginBottom: 16 },
  logoBox:           { width: 80, height: 80, borderRadius: 24,
                       backgroundColor: 'rgba(255,255,255,0.15)',
                       alignItems: 'center', justifyContent: 'center',
                       borderWidth: 1.5, borderColor: 'rgba(255,255,255,0.25)' },
  logoPulse:         { position: 'absolute', inset: -6, borderRadius: 30,
                       borderWidth: 1, borderColor: 'rgba(255,255,255,0.12)' },
  appName:           { fontSize: 32, fontWeight: '900', color: '#fff', letterSpacing: .5 },
  appSub:            { fontSize: 13, color: 'rgba(255,255,255,0.65)', marginTop: 4, letterSpacing: .2 },

  // Card
  card:              { backgroundColor: '#fff', borderRadius: 28, padding: 28,
                       shadowColor: '#000', shadowOpacity: .18, shadowRadius: 24, elevation: 10 },
  cardTitle:         { fontSize: 22, fontWeight: '900', color: Colors.text, marginBottom: 4 },
  cardSub:           { fontSize: 13, color: Colors.muted, marginBottom: 28, lineHeight: 18 },

  // Campos
  fieldWrap:         { marginBottom: 16 },
  label:             { fontSize: 13, fontWeight: '700', color: Colors.text, marginBottom: 8 },
  inputWrap:         { flexDirection: 'row', alignItems: 'center',
                       borderWidth: 1.5, borderColor: Colors.border,
                       borderRadius: 14, backgroundColor: Colors.bg, overflow: 'hidden' },
  inputWrapFocused:  { borderColor: Colors.primary, backgroundColor: '#fff' },
  inputWrapError:    { borderColor: Colors.red },
  inputIcon:         { paddingLeft: 14, paddingRight: 4 },
  input:             { flex: 1, paddingVertical: 14, paddingRight: 14,
                       fontSize: 15, color: Colors.text },
  eyeBtn:            { paddingHorizontal: 14, paddingVertical: 14 },

  // Error
  errorBox:          { flexDirection: 'row', alignItems: 'flex-start', gap: 7,
                       backgroundColor: Colors.red + '10', borderRadius: 10,
                       padding: 10, marginBottom: 4 },
  errorText:         { flex: 1, fontSize: 13, color: Colors.red, fontWeight: '500', lineHeight: 18 },

  // Botón
  btn:               { flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
                       gap: 8, backgroundColor: Colors.primary,
                       borderRadius: 14, paddingVertical: 16, marginTop: 8 },
  btnDisabled:       { opacity: 0.6 },
  btnText:           { color: '#fff', fontSize: 16, fontWeight: '800', letterSpacing: .3 },

  // Divisor
  divider:           { flexDirection: 'row', alignItems: 'center', gap: 10, marginVertical: 20 },
  dividerLine:       { flex: 1, height: 1, backgroundColor: Colors.border },
  dividerText:       { fontSize: 11, fontWeight: '600', color: Colors.muted,
                       textTransform: 'uppercase', letterSpacing: .5 },

  // Chips de roles
  chips:             { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'center', gap: 8 },
  chip:              { flexDirection: 'row', alignItems: 'center', gap: 5,
                       borderWidth: 1.5, borderRadius: 99,
                       paddingHorizontal: 11, paddingVertical: 5 },
  chipDot:           { width: 7, height: 7, borderRadius: 99 },
  chipText:          { fontSize: 12, fontWeight: '700' },

  // Footer
  footer:            { textAlign: 'center', color: 'rgba(255,255,255,0.35)',
                       fontSize: 12, marginTop: 28 },
})
