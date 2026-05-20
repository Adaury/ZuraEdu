# ZuraEdu Mobile — Guía de Despliegue

## Requisitos previos

| Herramienta | Versión mínima | Instalación |
|---|---|---|
| Node.js | 18+ | https://nodejs.org |
| Expo CLI | latest | `npm install -g expo-cli` |
| EAS CLI | latest | `npm install -g eas-cli` |
| Cuenta Expo | — | https://expo.dev/signup |
| Cuenta Firebase | — | https://console.firebase.google.com |
| Apple Developer | $99/año | https://developer.apple.com (solo iOS) |

---

## 1. Configuración inicial (una sola vez)

### 1.1 Instalar dependencias

```bash
cd mobile
npm install
```

### 1.2 Variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con la URL del servidor:

```env
# Emulador Android
EXPO_PUBLIC_API_URL=http://10.0.2.2:8000

# Dispositivo físico en red local
EXPO_PUBLIC_API_URL=http://192.168.1.X:8000

# Producción
EXPO_PUBLIC_API_URL=https://app.zuraedu.com
```

### 1.3 Firebase (Android — push notifications)

1. Ir a [Firebase Console](https://console.firebase.google.com)
2. Crear proyecto → nombre `ZuraEdu`
3. Agregar app Android → package: `com.zuraedu.mobile`
4. Descargar `google-services.json`
5. Copiar a `mobile/google-services.json`

> ⚠️ `google-services.json` está en `.gitignore` — nunca subir al repositorio.  
> Usar `google-services.example.json` como referencia de estructura.

### 1.4 Vincular proyecto con EAS

```bash
eas login
eas init
```

Esto genera un `extra.eas.projectId` en `app.json` automáticamente.

---

## 2. Desarrollo local

### Expo Go (más rápido, sin build nativo)

```bash
npm start
# Escanear QR con la app Expo Go en el dispositivo
```

### Development build (con módulos nativos completos)

```bash
# Android — emulador
eas build --profile development --platform android
npx expo start --dev-client

# iOS — simulador
eas build --profile development --platform ios --local
```

---

## 3. Build de prueba interna (Preview)

Genera un APK (Android) o IPA (iOS) para distribución interna sin pasar por los stores.

```bash
# Android
eas build --profile preview --platform android

# iOS
eas build --profile preview --platform ios
```

El enlace de descarga aparece en https://expo.dev tras el build.

---

## 4. Build de producción

### 4.1 Preparar credenciales iOS (solo primera vez)

Editar `eas.json` → sección `submit.production.ios`:

```json
"appleId": "tu@email.com",
"ascAppId": "ID numérico de la app en App Store Connect",
"appleTeamId": "ID de tu equipo Apple Developer"
```

### 4.2 Preparar Google Play (solo primera vez)

1. Crear app en [Google Play Console](https://play.google.com/console)
2. Generar Service Account con permiso de Release Manager
3. Descargar clave JSON → guardar como `mobile/google-play-key.json`

> ⚠️ `google-play-key.json` está en `.gitignore`.

### 4.3 Ejecutar build

```bash
# Ambas plataformas
eas build --profile production --platform all

# Solo Android (AAB para Play Store)
eas build --profile production --platform android

# Solo iOS
eas build --profile production --platform ios
```

### 4.4 Subir a los stores

```bash
# Subir automáticamente tras el build
eas submit --platform android
eas submit --platform ios
```

---

## 5. Actualizaciones OTA (Over The Air)

Para publicar cambios de JS sin pasar por revisión de los stores:

```bash
eas update --branch production --message "Descripción del cambio"
```

> Solo funciona para cambios en JavaScript/TypeScript.  
> Cambios en dependencias nativas requieren un nuevo build completo.

---

## 6. Perfiles de build

| Perfil | Plataforma | Formato | URL API | Uso |
|---|---|---|---|---|
| `development` | Android / iOS | APK / Simulator | `10.0.2.2:8000` | Dev con módulos nativos |
| `preview` | Android / iOS | APK / IPA | `staging.zuraedu.com` | QA interno |
| `production` | Android / iOS | AAB / IPA | `app.zuraedu.com` | Stores |

---

## 7. Variables de entorno por entorno

Las URLs de API están definidas en `eas.json` por perfil. Para cambiarlas:

```json
// eas.json
"production": {
  "env": {
    "EXPO_PUBLIC_API_URL": "https://app.zuraedu.com"
  }
}
```

---

## 8. Checklist antes de publicar

- [ ] `EXPO_PUBLIC_API_URL` apunta al servidor de producción
- [ ] `google-services.json` real (de Firebase Console) en `mobile/`
- [ ] `google-play-key.json` configurado para subida automática
- [ ] `appleId`, `ascAppId`, `appleTeamId` correctos en `eas.json`
- [ ] `eas init` ejecutado (projectId en `app.json`)
- [ ] Versión actualizada en `app.json` → `version` y `android.versionCode`
- [ ] Build de preview probado en dispositivo físico
- [ ] Push notifications verificadas con token real

---

## 9. Estructura de archivos clave

```
mobile/
├── app/                      # Pantallas (Expo Router)
│   ├── (admin)/              # Portal administrador
│   ├── (docente)/            # Portal docente
│   ├── (estudiante)/         # Portal estudiante
│   ├── (padre)/              # Portal padre/representante
│   └── login.tsx             # Pantalla de login
├── assets/                   # Íconos y splash screen
│   ├── icon.png              # 1024×1024 — app icon
│   ├── adaptive-icon.png     # 1024×1024 — Android adaptive
│   ├── splash-icon.png       # 512×512 — splash screen
│   ├── favicon.png           # 48×48 — web
│   └── notification-icon.png # 96×96 — Android push
├── components/               # Componentes compartidos
├── constants/Colors.ts       # Paleta de colores
├── context/AuthContext.tsx   # Autenticación y token
├── hooks/usePushNotifications.ts  # Push notifications + deeplink
├── services/api.ts           # Todos los endpoints del backend
├── app.json                  # Configuración Expo
├── eas.json                  # Perfiles de build EAS
├── .env                      # Variables locales (ignorado por git)
├── .env.example              # Plantilla de variables
├── google-services.json      # Firebase Android (ignorado por git)
└── google-services.example.json  # Plantilla Firebase
```

---

## 10. Soporte y recursos

| Recurso | URL |
|---|---|
| Expo docs | https://docs.expo.dev/versions/v54.0.0/ |
| EAS Build | https://docs.expo.dev/build/introduction/ |
| EAS Submit | https://docs.expo.dev/submit/introduction/ |
| Firebase Console | https://console.firebase.google.com |
| Expo Dashboard | https://expo.dev |
