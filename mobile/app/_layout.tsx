import { useEffect } from 'react'
import { Stack, useRouter, useSegments, usePathname } from 'expo-router'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { GestureHandlerRootView } from 'react-native-gesture-handler'
import { StatusBar } from 'expo-status-bar'
import { AuthProvider, useAuth } from '../context/AuthContext'
import { usePushNotifications } from '../hooks/usePushNotifications'

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

  }, [user, isLoading, primaryRole, pathname])

  return null
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <NavigationGuard />
          <StatusBar style="auto" />
          <Stack screenOptions={{ headerShown: false }}>
            <Stack.Screen name="login" />
            <Stack.Screen name="(estudiante)" />
            <Stack.Screen name="(padre)" />
            <Stack.Screen name="(docente)" />
            <Stack.Screen name="(admin)" />
          </Stack>
        </AuthProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  )
}
