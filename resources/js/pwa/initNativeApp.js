import { Capacitor } from '@capacitor/core';

export async function initNativeApp() {
    if (!Capacitor.isNativePlatform()) {
        return;
    }

    document.documentElement.classList.add('tc-native-app');

    const [{ StatusBar, Style }, { SplashScreen }] = await Promise.all([
        import('@capacitor/status-bar'),
        import('@capacitor/splash-screen'),
    ]);

    try {
        if (Capacitor.getPlatform() === 'android') {
            await StatusBar.setOverlaysWebView({ overlay: false });
        }

        await StatusBar.setBackgroundColor({ color: '#020617' });
        await StatusBar.setStyle({ style: Style.Dark });
    } catch {
        // Status bar API may be unavailable on some devices.
    }

    try {
        await SplashScreen.hide();
    } catch {
        // Splash screen may already be hidden.
    }
}
