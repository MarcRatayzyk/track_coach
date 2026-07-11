import type { CapacitorConfig } from '@capacitor/cli';

const serverUrl = process.env.CAPACITOR_SERVER_URL?.replace(/\/$/, '');

if (!serverUrl) {
    console.warn(
        'CAPACITOR_SERVER_URL is not set. Set it to your production HTTPS URL before running cap sync.',
    );
}

const config: CapacitorConfig = {
    appId: 'com.trackcoach.athlete',
    appName: 'Track Coach',
    webDir: 'capacitor-www',
    server: serverUrl
        ? { url: `${serverUrl}/login`, cleartext: false }
        : undefined,
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
