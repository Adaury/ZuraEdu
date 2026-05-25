import React, { useState, useCallback } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  TextInput, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Image,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import { useAuth } from '../../context/AuthContext'
import { authApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

// expo-image-picker se carga solo cuando el usuario lo necesita (evita crash de módulo nativo al cargar la pantalla)
const loadImagePicker = () => import('expo-image-picker')

const ROLE_COLORS: Record<string, string> = {
  Estudiante:    Colors.roles.estudiante,
  Representante: Colors.roles.padre,
  Docente:       Colors.roles.docente,
  Administrador: Colors.roles.admin,
  Director:      Colors.roles.admin,
}

const EMPTY_PWD = { current: '', nueva: '', confirmar: '' }

export default function PerfilScreen() {
  const { user, logout }    = useAuth()
  const qc                  = useQueryClient()
  const [showPwd, setShowPwd]   = useState(false)
  const [showEdit, setShowEdit] = useState(false)
  const [form, setForm]         = useState(EMPTY_PWD)
  const [editForm, setEditForm] = useState({ name: '', apellidos: '', telefono: '' })

  /* ── Datos remotos ─────────────────────────────── */
  const { data, isLoading } = useQuery({
    queryKey: ['me'],
    queryFn:  () => authApi.me().then(r => r.data),
    staleTime: 30_000,
  })

  /* ── Mutaciones ─────────────────────────────────── */
  const pwdMutation = useMutation({
    mutationFn: (payload: { current_password: string; new_password: string; new_password_confirmation: string }) =>
      authApi.changePassword(payload),
    onSuccess: () => {
      setForm(EMPTY_PWD)
      setShowPwd(false)
      Alert.alert('Contraseña actualizada', 'Tu contraseña ha sido cambiada correctamente.')
    },
    onError: (err: any) => {
      const msg =
        err?.response?.data?.message ??
        err?.response?.data?.errors?.new_password?.[0] ??
        'No se pudo cambiar la contraseña.'
      Alert.alert('Error', msg)
    },
  })

  const editMutation = useMutation({
    mutationFn: (payload: { name: string; apellidos?: string; telefono?: string }) =>
      authApi.updateProfile(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['me'] })
      setShowEdit(false)
      Alert.alert('Perfil actualizado', 'Tus datos han sido guardados correctamente.')
    },
    onError: () => Alert.alert('Error', 'No se pudo actualizar el perfil.'),
  })

  const avatarMutation = useMutation({
    mutationFn: (uri: string) => authApi.uploadAvatar(uri),
    onSuccess:  () => qc.invalidateQueries({ queryKey: ['me'] }),
    onError:    () => Alert.alert('Error', 'No se pudo subir la foto.'),
  })

  /* ── Seleccionar foto ───────────────────────────── */
  const pickAvatar = useCallback(async () => {
    try {
      const ImagePicker = await loadImagePicker()
      const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync()
      if (status !== 'granted') {
        Alert.alert('Permiso requerido', 'Activa el acceso a fotos en Ajustes para cambiar tu foto de perfil.')
        return
      }
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: true,
        aspect: [1, 1],
        quality: 0.8,
      })
      if (!result.canceled && result.assets?.[0]?.uri) {
        avatarMutation.mutate(result.assets[0].uri)
      }
    } catch {
      Alert.alert('Error', 'No se pudo abrir la galería.')
    }
  }, [avatarMutation])

  /* ── Cambiar contraseña ─────────────────────────── */
  const submitPwd = useCallback(() => {
    if (!form.current.trim())
      return Alert.alert('Atención', 'Ingresa tu contraseña actual.')
    if (form.nueva.length < 8)
      return Alert.alert('Atención', 'La nueva contraseña debe tener al menos 8 caracteres.')
    if (form.nueva !== form.confirmar)
      return Alert.alert('Atención', 'Las contraseñas nuevas no coinciden.')
    pwdMutation.mutate({
      current_password:          form.current,
      new_password:              form.nueva,
      new_password_confirmation: form.confirmar,
    })
  }, [form, pwdMutation])

  /* ── Derivar datos de pantalla ──────────────────── */
  const rol      = String(data?.role ?? (user as any)?.role ?? user?.roles?.[0] ?? '')
  const nombre   = `${data?.name ?? user?.name ?? ''} ${data?.apellidos ?? ''}`.trim()
  const email    = data?.email    ?? user?.email    ?? ''
  const telefono = data?.telefono ?? ''
  const avatarUrl = (data?.avatar ?? user?.avatar ?? null) as string | null
  const iniciales = nombre
    ? nombre.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase()
    : '?'
  const accentColor = ROLE_COLORS[rol] ?? Colors.primary

  /* ── Pantalla de carga inicial ──────────────────── */
  if (isLoading && !data && !user) {
    return (
      <SafeAreaView style={s.safe} edges={['bottom']}>
        <View style={s.centered}>
          <ActivityIndicator size="large" color={Colors.primary} />
        </View>
      </SafeAreaView>
    )
  }

  /* ── Render principal ───────────────────────────── */
  return (
    <SafeAreaView style={s.safe} edges={['bottom']}>
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={s.content} keyboardShouldPersistTaps="handled">

          {/* ── Avatar + nombre ── */}
          <View style={s.avatarSection}>
            <TouchableOpacity
              style={s.avatarWrap}
              onPress={pickAvatar}
              disabled={avatarMutation.isPending}
              activeOpacity={0.8}
            >
              {avatarUrl ? (
                <Image source={{ uri: avatarUrl }} style={s.avatarImg} />
              ) : (
                <View style={[s.avatarCircle, { backgroundColor: accentColor + '22' }]}>
                  <Text style={[s.avatarInits, { color: accentColor }]}>{iniciales}</Text>
                </View>
              )}
              <View style={[s.avatarCamara, { backgroundColor: accentColor }]}>
                {avatarMutation.isPending
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Ionicons name="camera" size={13} color="#fff" />
                }
              </View>
            </TouchableOpacity>

            <Text style={s.nombre}>{nombre || 'Sin nombre'}</Text>
            <Text style={s.email}>{email}</Text>
            {!!rol && (
              <View style={[s.rolBadge, { backgroundColor: accentColor + '18' }]}>
                <Text style={[s.rolText, { color: accentColor }]}>{rol}</Text>
              </View>
            )}
          </View>

          {/* ── Información de cuenta ── */}
          <View style={s.card}>
            <View style={s.cardHeader}>
              <Text style={s.cardTitle}>Información de cuenta</Text>
              <TouchableOpacity
                style={[s.editBtn, { backgroundColor: accentColor + '15' }]}
                onPress={() => {
                  setEditForm({
                    name:      data?.name      ?? user?.name ?? '',
                    apellidos: data?.apellidos ?? '',
                    telefono:  data?.telefono  ?? '',
                  })
                  setShowEdit(v => !v)
                }}
              >
                <Ionicons name={showEdit ? 'close-outline' : 'pencil-outline'} size={15} color={accentColor} />
                <Text style={[s.editBtnText, { color: accentColor }]}>
                  {showEdit ? 'Cancelar' : 'Editar'}
                </Text>
              </TouchableOpacity>
            </View>

            <InfoRow icon="person-outline"  label="Nombre"   value={nombre}   />
            <InfoRow icon="mail-outline"    label="Email"    value={email}    />
            {!!telefono && <InfoRow icon="call-outline" label="Teléfono" value={telefono} />}
            <InfoRow icon="shield-checkmark-outline" label="Rol" value={rol} />
          </View>

          {/* ── Formulario de edición ── */}
          {showEdit && (
            <View style={s.card}>
              <Text style={s.cardTitle}>Editar datos</Text>
              <CampoTexto
                label="Nombre(s)"
                value={editForm.name}
                onChange={t => setEditForm(f => ({ ...f, name: t }))}
              />
              <CampoTexto
                label="Apellido(s)"
                value={editForm.apellidos}
                onChange={t => setEditForm(f => ({ ...f, apellidos: t }))}
              />
              <CampoTexto
                label="Teléfono"
                value={editForm.telefono}
                onChange={t => setEditForm(f => ({ ...f, telefono: t }))}
                keyboardType="phone-pad"
              />
              <TouchableOpacity
                style={[s.btnPrimary, { backgroundColor: accentColor }, editMutation.isPending && s.btnDisabled]}
                onPress={() => {
                  if (!editForm.name.trim())
                    return Alert.alert('Atención', 'El nombre es obligatorio.')
                  editMutation.mutate({
                    name:      editForm.name.trim(),
                    apellidos: editForm.apellidos.trim() || undefined,
                    telefono:  editForm.telefono.trim()  || undefined,
                  })
                }}
                disabled={editMutation.isPending}
              >
                {editMutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={s.btnText}>Guardar cambios</Text>
                }
              </TouchableOpacity>
            </View>
          )}

          {/* ── Cambiar contraseña ── */}
          <TouchableOpacity
            style={s.card}
            onPress={() => setShowPwd(v => !v)}
            activeOpacity={0.85}
          >
            <View style={s.cardRow}>
              <View style={[s.iconBox, { backgroundColor: Colors.indigo + '18' }]}>
                <Ionicons name="key-outline" size={20} color={Colors.indigo} />
              </View>
              <Text style={[s.cardTitle, { flex: 1 }]}>Cambiar contraseña</Text>
              <Ionicons
                name={showPwd ? 'chevron-up' : 'chevron-down'}
                size={20}
                color={Colors.muted}
              />
            </View>
          </TouchableOpacity>

          {showPwd && (
            <View style={s.card}>
              <CampoClave
                label="Contraseña actual"
                value={form.current}
                onChange={t => setForm(f => ({ ...f, current: t }))}
              />
              <CampoClave
                label="Nueva contraseña"
                value={form.nueva}
                onChange={t => setForm(f => ({ ...f, nueva: t }))}
              />
              <CampoClave
                label="Confirmar nueva contraseña"
                value={form.confirmar}
                onChange={t => setForm(f => ({ ...f, confirmar: t }))}
              />
              <TouchableOpacity
                style={[s.btnPrimary, { backgroundColor: accentColor }, pwdMutation.isPending && s.btnDisabled]}
                onPress={submitPwd}
                disabled={pwdMutation.isPending}
              >
                {pwdMutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={s.btnText}>Actualizar contraseña</Text>
                }
              </TouchableOpacity>
            </View>
          )}

          {/* ── Cerrar sesión ── */}
          <TouchableOpacity
            style={s.logoutBtn}
            onPress={() =>
              Alert.alert(
                'Cerrar sesión',
                '¿Deseas salir de tu cuenta?',
                [
                  { text: 'Cancelar', style: 'cancel' },
                  { text: 'Salir', style: 'destructive', onPress: logout },
                ],
              )
            }
          >
            <Ionicons name="log-out-outline" size={20} color="#dc2626" />
            <Text style={s.logoutText}>Cerrar sesión</Text>
          </TouchableOpacity>

        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}

/* ── Sub-componentes ──────────────────────────────────────────────────── */

function InfoRow({ icon, label, value }: { icon: string; label: string; value: string }) {
  return (
    <View style={s.infoRow}>
      <Ionicons name={icon as any} size={17} color={Colors.muted} style={{ width: 24 }} />
      <View style={{ flex: 1 }}>
        <Text style={s.infoLabel}>{label}</Text>
        <Text style={s.infoValue}>{value || '—'}</Text>
      </View>
    </View>
  )
}

function CampoTexto({
  label, value, onChange, keyboardType,
}: {
  label: string
  value: string
  onChange: (t: string) => void
  keyboardType?: 'default' | 'phone-pad' | 'email-address'
}) {
  return (
    <View style={s.fieldWrap}>
      <Text style={s.fieldLabel}>{label}</Text>
      <TextInput
        style={s.fieldInput}
        value={value}
        onChangeText={onChange}
        keyboardType={keyboardType ?? 'default'}
        autoCapitalize="words"
        placeholderTextColor={Colors.muted}
      />
    </View>
  )
}

function CampoClave({
  label, value, onChange,
}: {
  label: string
  value: string
  onChange: (t: string) => void
}) {
  const [visible, setVisible] = useState(false)
  return (
    <View style={s.fieldWrap}>
      <Text style={s.fieldLabel}>{label}</Text>
      <View style={s.claveWrap}>
        <TextInput
          style={s.claveInput}
          value={value}
          onChangeText={onChange}
          secureTextEntry={!visible}
          autoCapitalize="none"
          placeholder="••••••••"
          placeholderTextColor={Colors.muted}
        />
        <TouchableOpacity onPress={() => setVisible(v => !v)} style={s.ojito}>
          <Ionicons
            name={visible ? 'eye-off-outline' : 'eye-outline'}
            size={19}
            color={Colors.muted}
          />
        </TouchableOpacity>
      </View>
    </View>
  )
}

/* ── Estilos ──────────────────────────────────────────────────────────── */

const s = StyleSheet.create({
  safe:     { flex: 1, backgroundColor: Colors.bg },
  centered: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  content:  { padding: 16, gap: 14, paddingBottom: 48 },

  // Avatar
  avatarSection: { alignItems: 'center', gap: 8, paddingVertical: 20 },
  avatarWrap:    { position: 'relative', marginBottom: 6 },
  avatarImg:     { width: 88, height: 88, borderRadius: 26 },
  avatarCircle:  { width: 88, height: 88, borderRadius: 26, alignItems: 'center', justifyContent: 'center' },
  avatarInits:   { fontSize: 32, fontWeight: '900' },
  avatarCamara:  {
    position: 'absolute', bottom: -6, right: -6,
    width: 28, height: 28, borderRadius: 9,
    alignItems: 'center', justifyContent: 'center',
    borderWidth: 2.5, borderColor: Colors.bg,
  },
  nombre:   { fontSize: 21, fontWeight: '900', color: Colors.text, textAlign: 'center' },
  email:    { fontSize: 14, color: Colors.muted, textAlign: 'center' },
  rolBadge: { borderRadius: 10, paddingHorizontal: 16, paddingVertical: 5, marginTop: 2 },
  rolText:  { fontSize: 13, fontWeight: '700' },

  // Tarjetas
  card: {
    backgroundColor: '#fff',
    borderRadius: 18,
    padding: 16,
    gap: 12,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  cardHeader: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  cardTitle:  { fontSize: 15, fontWeight: '800', color: Colors.text, flex: 1 },
  cardRow:    { flexDirection: 'row', alignItems: 'center', gap: 12 },

  // Botón editar
  editBtn:     { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 12, paddingVertical: 6, borderRadius: 99 },
  editBtnText: { fontSize: 13, fontWeight: '700' },

  // Filas de info
  infoRow:   { flexDirection: 'row', alignItems: 'flex-start', gap: 10, paddingVertical: 2 },
  infoLabel: { fontSize: 11, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 0.3 },
  infoValue: { fontSize: 14, fontWeight: '600', color: Colors.text, marginTop: 2 },

  // Icono de sección
  iconBox: { width: 36, height: 36, borderRadius: 11, alignItems: 'center', justifyContent: 'center' },

  // Campos de formulario
  fieldWrap:  { gap: 6 },
  fieldLabel: { fontSize: 13, fontWeight: '700', color: Colors.text },
  fieldInput: {
    borderWidth: 1.5,
    borderColor: Colors.border,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 11,
    fontSize: 14,
    color: Colors.text,
    backgroundColor: Colors.bg,
  },
  claveWrap:  { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, backgroundColor: Colors.bg },
  claveInput: { flex: 1, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  ojito:      { padding: 11 },

  // Botón principal
  btnPrimary:  { borderRadius: 14, paddingVertical: 15, alignItems: 'center', marginTop: 4 },
  btnDisabled: { opacity: 0.65 },
  btnText:     { color: '#fff', fontWeight: '800', fontSize: 15, letterSpacing: 0.2 },

  // Cerrar sesión
  logoutBtn:  {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 8, backgroundColor: '#fef2f2', borderRadius: 14, paddingVertical: 15,
  },
  logoutText: { color: '#dc2626', fontWeight: '700', fontSize: 15 },
})
