import React, { createContext, useContext, useEffect, useRef, useState } from 'react'
import * as SecureStore from 'expo-secure-store'
import * as Notifications from 'expo-notifications'
import { authApi } from '../services/api'

type Role = 'Estudiante' | 'Docente' | 'Representante' | 'Administrador' | 'Director' | string

interface AuthUser {
  id: number
  name: string
  email: string
  roles: Role[]
  avatar?: string
}

interface AuthContextType {
  user: AuthUser | null
  token: string | null
  isLoading: boolean
  primaryRole: Role | null
  login: (email: string, password: string) => Promise<AuthUser>
  logout: () => Promise<void>
  /** Registra el push token desde el hook de notificaciones para limpiarlo en logout. */
  setPushToken: (token: string | null) => void
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser]         = useState<AuthUser | null>(null)
  const [token, setToken]       = useState<string | null>(null)
  const [isLoading, setLoading] = useState(true)
  const pushTokenRef            = useRef<string | null>(null)

  const setPushToken = (t: string | null) => { pushTokenRef.current = t }

  // Determina el portal principal según roles
  const primaryRole: Role | null = user?.roles
    ? user.roles.includes('Docente')        ? 'Docente'
    : user.roles.includes('Representante')  ? 'Representante'
    : user.roles.includes('Estudiante')     ? 'Estudiante'
    : user.roles.includes('Administrador')  ? 'Administrador'
    : user.roles.includes('Director')       ? 'Director'
    : user.roles[0] ?? null
    : null

  useEffect(() => {
    (async () => {
      try {
        const [storedToken, storedUser] = await Promise.all([
          SecureStore.getItemAsync('auth_token'),
          SecureStore.getItemAsync('auth_user'),
        ])
        if (storedToken && storedUser) {
          const parsed = JSON.parse(storedUser)
          if (parsed.role && !parsed.roles) parsed.roles = [parsed.role]
          setToken(storedToken)
          setUser(parsed)
        }
      } catch {}
      finally { setLoading(false) }
    })()
  }, [])

  const login = async (email: string, password: string): Promise<AuthUser> => {
    const { data } = await authApi.login(email, password)
    const { token: t, user: u } = data
    if (!t || !u) throw new Error('Respuesta inválida del servidor.')
    if (u.role && !u.roles) u.roles = [u.role]
    try {
      await SecureStore.setItemAsync('auth_token', t)
      await SecureStore.setItemAsync('auth_user', JSON.stringify(u))
    } catch {}
    setToken(t)
    setUser(u)
    return u
  }

  const logout = async () => {
    if (pushTokenRef.current) {
      try { await authApi.removePushToken(pushTokenRef.current) } catch {}
      pushTokenRef.current = null
    }
    try { await Notifications.setBadgeCountAsync(0) } catch {}
    try { await authApi.logout() } catch {}
    try { await SecureStore.deleteItemAsync('auth_token') } catch {}
    try { await SecureStore.deleteItemAsync('auth_user') } catch {}
    setToken(null)
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, token, isLoading, primaryRole, login, logout, setPushToken }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
