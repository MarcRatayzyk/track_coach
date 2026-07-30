import type { CapacitorConfig } from '@capacitor/cli';

/** URL prod par défaut — évite un APK bloqué sur « Chargement… » si la var est oubliée. */
const DEFAULT_SERVER_URL = 'https://track-coach.onrender.com';

const serverUrl = (process.env.CAPACITOR_SERVER_URL || DEFAULT_SERVER_URL).replace(/\/$/, '');

if (!process.env.CAPACITOR_SERVER_URL) {
    console.warn(
        `CAPACITOR_SERVER_URL unset — using default ${DEFAULT_SERVER_URL}. Set the env var to override.`,
    );
}

const config: CapacitorConfig = {
    appId: 'com.trackcoach.athlete',
    appName: 'Power Roster',
    webDir: 'capacitor-www',
    server: {
        url: `${serverUrl}/login`,
        cleartext: false,
    },
    android: {
        appendUserAgent: ' TrackCoachMobile/1.0',
        backgroundColor: '#020617',
    },
    plugins: {
        SplashScreen: {
            backgroundColor: '#020617',
            launchAutoHide: true,
        },
        StatusBar: {
            backgroundColor: '#020617',
            style: 'DARK',
        },
    },
};

export default config;
