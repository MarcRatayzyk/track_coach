import { Capacitor } from '@capacitor/core';

export const isNativeApp = Capacitor.isNativePlatform();
export const isMobileApp = isNativeApp && Capacitor.getPlatform() === 'android';

export function useNativeApp() {
    return {
        isNative: isNativeApp,
        isMobileApp,
    };
}
