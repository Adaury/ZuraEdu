import React, { createContext, useContext, useEffect, useState } from 'react'
import * as SecureStore from 'expo-secure-store'
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
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextType>({} as AuthContextType)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser]       = useState<AuthUser | null>(null)
  const [token, setToken]     = useState<string | null>(null)
  const [isLoading, setLoading] = useState(true)

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
          setToken(storedToken)
          setUser(JSON.parse(storedUser))
        }
      } catch {}
      finally { setLoading(false) }
    })()
  }, [])

  const login = async (email: string, password: string) => {
    const { data } = await authApi.login(email, password)
    const { token: t, user: u } = data
    await SecureStore.setItemAsync('auth_token', t)
    await SecureStore.setItemAsync('auth_user', JSON.stringify(u))
    setToken(t)
    setUser(u)
  }

  const logout = async () => {
    try { await authApi.logout() } catch {}
    await SecureStore.deleteItemAsync('auth_token')
    await SecureStore.deleteItemAsync('auth_user')
    setToken(null)
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, token, isLoading, primaryRole, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
