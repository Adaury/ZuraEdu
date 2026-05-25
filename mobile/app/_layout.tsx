import { useEffect, Component, ReactNode } from 'react'
import { View, Text, TouchableOpacity } from 'react-native'
import { Stack, useRouter, useSegments, usePathname } from 'expo-router'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { GestureHandlerRootView } from 'react-native-gesture-handler'
import { StatusBar } from 'expo-status-bar'
import { AuthProvider, useAuth } from '../context/AuthContext'
import { usePushNotifications } from '../hooks/usePushNotifications'

/* ── Error Boundary global — evita que errores JS cierren la app ── */
interface EBState { hasError: boolean; error: string }
class ErrorBoundary extends Component<{ children: ReactNode }, EBState> {
  constructor(props: { children: ReactNode }) {
    super(props)
    this.state = { hasError: false, error: '' }
  }
  static getDerivedStateFromError(error: Error): EBState {
    return { hasError: true, error: error?.message ?? 'Error desconocido' }
  }
  render() {
    if (this.state.hasError) {
      return (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 24, gap: 16 }}>
          <Text style={{ fontSize: 18, fontWeight: '800', color: '#1e293b' }}>Algo salió mal</Text>
          <Text style={{ fontSize: 14, color: '#64748b', textAlign: 'center' }}>{this.state.error}</Text>
          <TouchableOpacity
            onPress={() => this.setState({ hasError: false, error: '' })}
            style={{ backgroundColor: '#1e3a6e', borderRadius: 12, paddingHorizontal: 24, paddingVertical: 12 }}
          >
            <Text style={{ color: '#fff', fontWeight: '700' }}>Reintentar</Text>
          </TouchableOpacity>
        </View>
      )
    }
    return this.props.children
  }
}

const queryClient = new QueryClient({
  defaultOptions: { queries: { retry: 1, staleTime: 60_000 } },
})

function NavigationGuard() {
  const { user, isLoading, primaryRole } = useAuth()
  const router   = useRouter()
  const segments = useSegments()
  const pathname = usePathname()

  usePushNotifications(user ? primaryRole : null)

  useEffect(() => {
    if (isLoading) return

    const inAuth = segments[0] === 'login' || pathname === '/login' || pathname === '/login/'

    const correctSegment =
      primaryRole === 'Docente'                                        ? '(docente)'
      : primaryRole === 'Representante'                                ? '(padre)'
      : (primaryRole === 'Administrador' || primaryRole === 'Director')? '(admin)'
      : '(estudiante)'

    const inCorrectPortal = segments[0] === correctSegment

    try {
      if (!user && !inAuth) {
        router.replace('/login')
      } else if (user && (inAuth || !inCorrectPortal)) {
        // Redirige al portal correcto tanto desde login como tras crash en portal equivocado
        if (primaryRole === 'Docente')                                          router.replace('/(docente)')
        else if (primaryRole === 'Representante')                               router.replace('/(padre)')
        else if (primaryRole === 'Administrador' || primaryRole === 'Director') router.replace('/(admin)')
        else                                                                    router.replace('/(estudiante)')
      }
    } catch {}

  }, [user, isLoading, primaryRole, pathname, segments])

  return null
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <NavigationGuard />
          <StatusBar style="auto" />
          <ErrorBoundary>
            <Stack screenOptions={{ headerShown: false }}>
              <Stack.Screen name="login" />
              <Stack.Screen name="(estudiante)" />
              <Stack.Screen name="(padre)" />
              <Stack.Screen name="(docente)" />
              <Stack.Screen name="(admin)" />
            </Stack>
          </ErrorBoundary>
        </AuthProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  )
}
