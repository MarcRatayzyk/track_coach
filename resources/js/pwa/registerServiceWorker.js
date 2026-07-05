export function registerServiceWorker() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .catch(() => {
                // Ignore registration errors (unsupported browser, invalid scope, etc.).
            });
    });
}
