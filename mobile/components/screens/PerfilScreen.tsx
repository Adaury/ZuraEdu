import React, { useState } from 'react'
import {
  View, Text, ScrollView, TouchableOpacity, StyleSheet,
  TextInput, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, Image,
} from 'react-native'
import { SafeAreaView } from 'react-native-safe-area-context'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ionicons } from '@expo/vector-icons'
import * as ImagePicker from 'expo-image-picker'
import { useAuth } from '../../context/AuthContext'
import { authApi } from '../../services/api'
import { Colors } from '../../constants/Colors'

const ROLE_COLORS: Record<string, string> = {
  Estudiante:    Colors.roles.estudiante,
  Representante: Colors.roles.padre,
  Docente:       Colors.roles.docente,
}

const EMPTY_PWD = { current: '', nueva: '', confirmar: '' }

export default function PerfilScreen() {
  const { user, logout } = useAuth()
  const qc = useQueryClient()
  const [showPwd,   setShowPwd]   = useState(false)
  const [showEdit,  setShowEdit]  = useState(false)
  const [form,      setForm]      = useState(EMPTY_PWD)
  const [editForm,  setEditForm]  = useState({ name: '', apellidos: '', telefono: '' })

  const { data } = useQuery({
    queryKey: ['me'],
    queryFn:  () => authApi.me().then(r => r.data),
  })

  const mutation = useMutation({
    mutationFn: (payload: any) => authApi.changePassword(payload),
    onSuccess: () => {
      setForm(EMPTY_PWD)
      setShowPwd(false)
      Alert.alert('Éxito', 'Contraseña actualizada correctamente.')
    },
    onError: (err: any) => {
      const msg =
        err?.response?.data?.message ??
        err?.response?.data?.errors?.new_password?.[0] ??
        'Error al cambiar la contraseña.'
      Alert.alert('Error', msg)
    },
  })

  const updateProfileMutation = useMutation({
    mutationFn: (payload: { name: string; apellidos?: string; telefono?: string }) =>
      authApi.updateProfile(payload),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['me'] })
      setShowEdit(false)
      Alert.alert('Éxito', 'Perfil actualizado correctamente.')
    },
    onError: () => Alert.alert('Error', 'No se pudo actualizar el perfil.'),
  })

  const avatarMutation = useMutation({
    mutationFn: (uri: string) => authApi.uploadAvatar(uri),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['me'] }) },
    onError:   () => Alert.alert('Error', 'No se pudo subir la foto.'),
  })

  const pickAvatar = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync()
    if (status !== 'granted') {
      Alert.alert('Permiso necesario', 'Se necesita acceso a la galería para cambiar la foto.')
      return
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      aspect: [1, 1],
      quality: 0.8,
    })
    if (!result.canceled && result.assets[0]) {
      avatarMutation.mutate(result.assets[0].uri)
    }
  }

  const submit = () => {
    if (!form.current.trim())        return Alert.alert('Atención', 'Escribe tu contraseña actual.')
    if (form.nueva.length < 8)       return Alert.alert('Atención', 'La nueva contraseña debe tener al menos 8 caracteres.')
    if (form.nueva !== form.confirmar) return Alert.alert('Atención', 'Las contraseñas no coinciden.')
    mutation.mutate({
      current_password:           form.current,
      new_password:               form.nueva,
      new_password_confirmation:  form.confirmar,
    })
  }

  const rol      = (data?.role ?? (user as any)?.role ?? (user?.roles?.[0]) ?? '') as string
  const nombre   = `${data?.name ?? user?.name ?? ''} ${data?.apellidos ?? ''}`.trim()
  const email    = data?.email   ?? user?.email ?? ''
  const telefono = (data?.telefono ?? '') as string
  const avatarUrl= (data?.avatar  ?? user?.avatar ?? null) as string | null
  const words    = nombre ? nombre.split(' ').filter(Boolean) : []
  const inits    = words.slice(0, 2).map(w => w[0] ?? '').join('').toUpperCase() || '?'
  const rc       = ROLE_COLORS[rol] ?? Colors.blue

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">

          {/* Avatar */}
          <View style={styles.avatarSection}>
            <TouchableOpacity onPress={pickAvatar} style={styles.avatarWrap} disabled={avatarMutation.isPending}>
              {avatarUrl
                ? <Image source={{ uri: avatarUrl }} style={styles.avatarImg} />
                : <View style={[styles.avatarCircle, { backgroundColor: rc + '20' }]}>
                    <Text style={[styles.avatarInits, { color: rc }]}>{inits}</Text>
                  </View>
              }
              <View style={[styles.avatarEdit, { backgroundColor: rc }]}>
                {avatarMutation.isPending
                  ? <ActivityIndicator size="small" color="#fff" />
                  : <Ionicons name="camera" size={13} color="#fff" />
                }
              </View>
            </TouchableOpacity>
            <Text style={styles.nombre}>{nombre}</Text>
            <Text style={styles.email}>{email}</Text>
            <View style={[styles.roleBadge, { backgroundColor: rc + '18' }]}>
              <Text style={[styles.roleText, { color: rc }]}>{rol}</Text>
            </View>
          </View>

          {/* Info */}
          <View style={styles.section}>
            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
              <Text style={[styles.sectionTitle, { flex: 1 }]}>Información de cuenta</Text>
              <TouchableOpacity
                style={[styles.editInfoBtn, { backgroundColor: rc + '18' }]}
                onPress={() => {
                  setEditForm({
                    name:      data?.name      ?? user?.name ?? '',
                    apellidos: data?.apellidos ?? '',
                    telefono:  data?.telefono  ?? '',
                  })
                  setShowEdit(v => !v)
                }}
              >
                <Ionicons name={showEdit ? 'close' : 'pencil'} size={14} color={rc} />
                <Text style={[styles.editInfoTxt, { color: rc }]}>{showEdit ? 'Cancelar' : 'Editar'}</Text>
              </TouchableOpacity>
            </View>
            <InfoRow icon="person-outline"  label="Nombre"   value={nombre}   />
            <InfoRow icon="mail-outline"    label="Email"    value={email}    />
            {!!telefono && <InfoRow icon="call-outline" label="Teléfono" value={telefono} />}
            <InfoRow icon="shield-outline"  label="Rol"      value={rol}      />
          </View>

          {/* Editar info */}
          {showEdit && (
            <View style={styles.section}>
              <EditField label="Nombre(s)"    value={editForm.name}      onChange={t => setEditForm(f => ({ ...f, name: t }))}      />
              <EditField label="Apellido(s)"  value={editForm.apellidos} onChange={t => setEditForm(f => ({ ...f, apellidos: t }))} />
              <EditField label="Teléfono"     value={editForm.telefono}  onChange={t => setEditForm(f => ({ ...f, telefono: t }))}  keyboardType="phone-pad" />
              <TouchableOpacity
                style={[styles.btn, { backgroundColor: rc }]}
                onPress={() => {
                  if (!editForm.name.trim()) return Alert.alert('Atención', 'El nombre es obligatorio.')
                  updateProfileMutation.mutate({
                    name:      editForm.name.trim(),
                    apellidos: editForm.apellidos.trim() || undefined,
                    telefono:  editForm.telefono.trim()  || undefined,
                  })
                }}
                disabled={updateProfileMutation.isPending}
              >
                {updateProfileMutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.btnText}>Guardar cambios</Text>
                }
              </TouchableOpacity>
            </View>
          )}

          {/* Cambiar contraseña */}
          <TouchableOpacity style={styles.section} onPress={() => setShowPwd(v => !v)} activeOpacity={0.85}>
            <View style={styles.sectionRow}>
              <View style={[styles.sectionIconBox, { backgroundColor: Colors.indigo + '18' }]}>
                <Ionicons name="key-outline" size={18} color={Colors.indigo} />
              </View>
              <Text style={styles.sectionTitle}>Cambiar contraseña</Text>
              <Ionicons name={showPwd ? 'chevron-up' : 'chevron-down'} size={18} color={Colors.muted} />
            </View>
          </TouchableOpacity>

          {showPwd && (
            <View style={styles.section}>
              <PwdField label="Contraseña actual"    value={form.current}   onChange={t => setForm(f => ({ ...f, current: t }))}   />
              <PwdField label="Nueva contraseña"     value={form.nueva}     onChange={t => setForm(f => ({ ...f, nueva: t }))}     />
              <PwdField label="Confirmar contraseña" value={form.confirmar} onChange={t => setForm(f => ({ ...f, confirmar: t }))} />
              <TouchableOpacity
                style={[styles.btn, { backgroundColor: rc }]}
                onPress={submit}
                disabled={mutation.isPending}
              >
                {mutation.isPending
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.btnText}>Actualizar contraseña</Text>
                }
              </TouchableOpacity>
            </View>
          )}

          {/* Cerrar sesión */}
          <TouchableOpacity
            style={styles.logoutBtn}
            onPress={() =>
              Alert.alert('Cerrar sesión', '¿Seguro que deseas salir?', [
                { text: 'Cancelar', style: 'cancel' },
                { text: 'Salir', style: 'destructive', onPress: logout },
              ])
            }
          >
            <Ionicons name="log-out-outline" size={20} color="#dc2626" />
            <Text style={styles.logoutText}>Cerrar sesión</Text>
          </TouchableOpacity>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  )
}

function InfoRow({ icon, label, value }: { icon: string; label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Ionicons name={icon as any} size={17} color={Colors.muted} style={{ width: 22 }} />
      <View>
        <Text style={styles.infoLabel}>{label}</Text>
        <Text style={styles.infoValue}>{value || '—'}</Text>
      </View>
    </View>
  )
}

function EditField({
  label, value, onChange, keyboardType,
}: { label: string; value: string; onChange: (t: string) => void; keyboardType?: any }) {
  return (
    <View style={{ gap: 5 }}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={styles.editInput}
        value={value}
        onChangeText={onChange}
        keyboardType={keyboardType ?? 'default'}
        autoCapitalize="words"
        placeholderTextColor={Colors.muted}
      />
    </View>
  )
}

function PwdField({ label, value, onChange }: { label: string; value: string; onChange: (t: string) => void }) {
  const [show, setShow] = useState(false)
  return (
    <View style={{ gap: 5 }}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.pwdWrap}>
        <TextInput
          style={styles.pwdInput}
          value={value}
          onChangeText={onChange}
          secureTextEntry={!show}
          autoCapitalize="none"
          placeholder="••••••••"
          placeholderTextColor={Colors.muted}
        />
        <TouchableOpacity onPress={() => setShow(v => !v)} style={styles.eyeBtn}>
          <Ionicons name={show ? 'eye-off-outline' : 'eye-outline'} size={18} color={Colors.muted} />
        </TouchableOpacity>
      </View>
    </View>
  )
}

const styles = StyleSheet.create({
  safe:           { flex: 1, backgroundColor: Colors.bg },
  content:        { padding: 16, gap: 14, paddingBottom: 40 },
  avatarSection:  { alignItems: 'center', gap: 8, paddingVertical: 16 },
  avatarWrap:     { position: 'relative', marginBottom: 4 },
  avatarImg:      { width: 80, height: 80, borderRadius: 24 },
  avatarCircle:   { width: 80, height: 80, borderRadius: 24, alignItems: 'center', justifyContent: 'center' },
  avatarInits:    { fontSize: 30, fontWeight: '900' },
  avatarEdit:     { position: 'absolute', bottom: -6, right: -6, width: 26, height: 26,
                    borderRadius: 8, alignItems: 'center', justifyContent: 'center',
                    borderWidth: 2, borderColor: Colors.bg },
  nombre:         { fontSize: 20, fontWeight: '900', color: Colors.text },
  email:          { fontSize: 14, color: Colors.muted },
  roleBadge:      { borderRadius: 10, paddingHorizontal: 14, paddingVertical: 5, marginTop: 2 },
  roleText:       { fontSize: 13, fontWeight: '700' },
  section:        { backgroundColor: '#fff', borderRadius: 16, padding: 16, gap: 12, shadowColor: '#000', shadowOpacity: .04, shadowRadius: 6, elevation: 2 },
  sectionRow:     { flexDirection: 'row', alignItems: 'center', gap: 10 },
  sectionIconBox: { width: 34, height: 34, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  sectionTitle:   { flex: 1, fontSize: 15, fontWeight: '700', color: Colors.text },
  infoRow:        { flexDirection: 'row', alignItems: 'flex-start', gap: 10, paddingVertical: 4 },
  infoLabel:      { fontSize: 11, color: Colors.muted, fontWeight: '600', textTransform: 'uppercase', letterSpacing: .3 },
  infoValue:      { fontSize: 14, fontWeight: '600', color: Colors.text, marginTop: 1 },
  fieldLabel:     { fontSize: 13, fontWeight: '600', color: Colors.text },
  pwdWrap:        { flexDirection: 'row', alignItems: 'center', borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12, backgroundColor: '#fff' },
  pwdInput:       { flex: 1, paddingHorizontal: 14, paddingVertical: 11, fontSize: 14, color: Colors.text },
  eyeBtn:         { padding: 10 },
  btn:            { borderRadius: 14, paddingVertical: 14, alignItems: 'center', marginTop: 4 },
  btnText:        { color: '#fff', fontWeight: '800', fontSize: 15 },
  logoutBtn:      { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, backgroundColor: '#fef2f2', borderRadius: 14, paddingVertical: 14 },
  logoutText:     { color: '#dc2626', fontWeight: '700', fontSize: 15 },
  editInfoBtn:    { flexDirection: 'row', alignItems: 'center', gap: 5, paddingHorizontal: 12, paddingVertical: 5, borderRadius: 99 },
  editInfoTxt:    { fontSize: 12, fontWeight: '700' },
  editInput:      { borderWidth: 1.5, borderColor: Colors.border, borderRadius: 12,
                    paddingHorizontal: 14, paddingVertical: 10, fontSize: 14, color: Colors.text, backgroundColor: '#fff' },
})
