import { computed, onMounted, ref } from 'vue';

export const PWA_INSTALL_DISMISSED_KEY = 'tc-pwa-install-dismissed';

/** @type {import('vue').Ref<BeforeInstallPromptEvent | null>} */
const deferredPrompt = ref(null);

const isInstalled = ref(false);
const isDismissed = ref(false);
const isMobile = ref(false);
const isIos = ref(false);
const listenersBound = ref(false);

/**
 * @typedef {Event & {
 *   prompt: () => Promise<void>;
 *   userChoice: Promise<{ outcome: 'accepted' | 'dismissed'; platform: string }>;
 * }} BeforeInstallPromptEvent
 */

function readDismissedState() {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(PWA_INSTALL_DISMISSED_KEY) === 'true';
}

function detectInstalled() {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true
    );
}

function detectMobile() {
    if (typeof window === 'undefined') {
        return false;
    }

    const userAgent = window.navigator.userAgent || '';
    const mobileUserAgent = /Android|iPhone|iPad|iPod|Mobile/i.test(userAgent);
    const narrowViewport = window.matchMedia('(max-width: 1023px)').matches;

    return mobileUserAgent && narrowViewport;
}

function detectIos() {
    if (typeof window === 'undefined') {
        return false;
    }

    const userAgent = window.navigator.userAgent || '';

    return /iPhone|iPad|iPod/i.test(userAgent);
}

function bindInstallListeners() {
    if (typeof window === 'undefined' || listenersBound.value) {
        return;
    }

    listenersBound.value = true;

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt.value = /** @type {BeforeInstallPromptEvent} */ (event);
    });

    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        deferredPrompt.value = null;
    });
}

function refreshEnvironment() {
    isInstalled.value = detectInstalled();
    isDismissed.value = readDismissedState();
    isMobile.value = detectMobile();
    isIos.value = detectIos();
    bindInstallListeners();
}

export function usePwaInstall() {
    onMounted(() => {
        refreshEnvironment();
    });

    const platform = computed(() => {
        if (!isMobile.value || isInstalled.value) {
            return null;
        }

        if (deferredPrompt.value) {
            return 'android';
        }

        if (isIos.value) {
            return 'ios';
        }

        return null;
    });

    const showBanner = computed(() => {
        if (!isMobile.value || isInstalled.value || isDismissed.value) {
            return false;
        }

        return platform.value !== null;
    });

    async function install() {
        if (!deferredPrompt.value) {
            return;
        }

        await deferredPrompt.value.prompt();
        await deferredPrompt.value.userChoice;
        deferredPrompt.value = null;
    }

    function dismiss() {
        isDismissed.value = true;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(PWA_INSTALL_DISMISSED_KEY, 'true');
        }
    }

    return {
        showBanner,
        platform,
        install,
        dismiss,
    };
}
