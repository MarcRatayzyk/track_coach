# APK Android athlète — Track Coach

Application Android via **Capacitor** : une WebView charge l'app web Inertia/Vue depuis le serveur Laravel de production. Même style, même backend, même base de données.

Scope v1 : parcours **athlète uniquement** (connexion coach bloquée).

## Prérequis

- Node.js 20+
- [Android Studio](https://developer.android.com/studio) + SDK Android 34+
- JDK 17
- Serveur Laravel en **HTTPS** (obligatoire en production)

## Configuration

1. Définir l'URL de production dans `.env` ou en variable d'environnement shell :

```bash
# Windows PowerShell
$env:CAPACITOR_SERVER_URL="https://votre-domaine.com"

# Linux / macOS
export CAPACITOR_SERVER_URL=https://votre-domaine.com
```

2. S'assurer que le frontend prod est buildé avec les bonnes variables Reverb (`VITE_REVERB_*`).

3. Vérifier les limites upload côté serveur pour les retours vidéo (100 Mo/fichier) : `upload_max_filesize`, `post_max_size`, `client_max_body_size` (nginx).

## Workflow build APK

```bash
# 1. Build frontend (icônes PWA + assets Vite)
npm run build

# 2. Sync Capacitor (icônes Android + config)
npm run cap:sync

# 3a. Générer l'APK en ligne de commande (recommandé)
npm run android:apk

# 3b. Ou ouvrir Android Studio
npm run cap:open
```

Dans Android Studio :

- **Build > Build Bundle(s) / APK(s) > Build APK(s)** pour un APK debug (tests internes)
- Ou en ligne de commande : `cd android && ./gradlew assembleDebug`

APK debug généré dans : `android/app/build/outputs/apk/debug/`

## Scripts npm

| Script | Description |
|--------|-------------|
| `npm run cap:sync` | Copie les icônes Android + synchronise Capacitor |
| `npm run cap:open` | Ouvre le projet dans Android Studio |
| `npm run android:apk` | Sync + build APK debug (ligne de commande) |
| `npm run android:build` | Sync + ouvre Android Studio |

## Architecture

```
APK Android (Capacitor)
  └── WebView → CAPACITOR_SERVER_URL (HTTPS)
        └── Laravel + Inertia + Vue (identique au web)
```

- **Détection app athlète** : User-Agent `TrackCoachAthlete/1.0` (configuré dans `capacitor.config.ts`)
- **Blocage coach** : `LoginController` + middleware `athlete.app`
- **UI native** : splash `#020617`, barre de statut `#2563eb`, bouton PWA « Installer » masqué

## Pages athlète couvertes

- Dashboard (`/athlete/dashboard`)
- Programme (`/athlete/program`)
- Profil (`/athletes/{id}`)
- Retours vidéo (`/feedbacks`)
- Messagerie (`/messaging`)
- Meet live (`/athletes/{id}/competitions/{id}/live`)
- Activation compte (`/account/setup/{user}` via lien signé)

## Fichiers clés

| Fichier | Rôle |
|---------|------|
| `capacitor.config.ts` | Config Capacitor (appId, server.url, plugins) |
| `capacitor-www/index.html` | Placeholder requis par Capacitor (non utilisé en mode distant) |
| `android/` | Projet Android natif |
| `scripts/generate-android-icons.mjs` | Copie `public/icons/icon-512.png` vers les mipmaps Android |
| `app/Support/AthleteMobileApp.php` | Détection User-Agent app athlète |
| `resources/js/composables/useNativeApp.js` | Détection plateforme native côté Vue |

## Checklist de validation

- [ ] APK démarre sur splash sombre, charge la page login
- [ ] Connexion athlète → dashboard + navigation 5 onglets
- [ ] Connexion coach → message d'erreur
- [ ] Upload vidéo retours séance
- [ ] Messagerie temps réel (Reverb WSS) ou polling 60s
- [ ] Thème clair/sombre identique au web

## Hors scope v1

- App coach Android
- Mode offline
- Notifications push (FCM)
- Publication Play Store (signing release)
