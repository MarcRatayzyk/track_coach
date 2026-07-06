import { computed, onMounted, onUnmounted, ref } from 'vue';
import { isNativeApp } from './useNativeApp';

export const PWA_INSTALL_DISMISSED_KEY = 'tc-pwa-install-dismissed';

/** @type {import('vue').Ref<BeforeInstallPromptEvent | null>} */
const deferredPrompt = ref(null);

const isInstalled = ref(false);
const isDismissed = ref(false);
const isMobile = ref(false);
const isIos = ref(false);
const isAndroid = ref(false);
const listenersBound = ref(false);
const showInstallGuide = ref(false);
const installGuideType = ref('desktop');

let mobileMediaQuery = null;

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

    if (/iPhone|iPad|iPod/i.test(userAgent)) {
        return true;
    }

    // iPadOS 13+ se présente parfois comme Mac.
    return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
}

function detectAndroid() {
    if (typeof window === 'undefined') {
        return false;
    }

    return /Android/i.test(window.navigator.userAgent || '');
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
        showInstallGuide.value = false;
    });
}

function refreshEnvironment() {
    isInstalled.value = detectInstalled();
    isDismissed.value = readDismissedState();
    isMobile.value = detectMobile();
    isIos.value = detectIos();
    isAndroid.value = detectAndroid();
    bindInstallListeners();
}

function resolveGuideType() {
    if (isIos.value) {
        return 'ios';
    }

    if (isAndroid.value || isMobile.value) {
        return 'android';
    }

    return 'desktop';
}

export function usePwaInstall() {
    if (isNativeApp) {
        return {
            showBanner: computed(() => false),
            canPromptInstall: computed(() => false),
            isInstalled: computed(() => true),
            platform: computed(() => null),
            showInstallGuide: ref(false),
            installGuideType: ref('desktop'),
            install: async () => {},
            installOrGuide: async () => {},
            closeInstallGuide: () => {},
            dismiss: () => {},
        };
    }

    onMounted(() => {
        refreshEnvironment();

        if (typeof window !== 'undefined') {
            mobileMediaQuery = window.matchMedia('(max-width: 1023px)');
            mobileMediaQuery.addEventListener('change', refreshEnvironment);
        }
    });

    onUnmounted(() => {
        if (mobileMediaQuery) {
            mobileMediaQuery.removeEventListener('change', refreshEnvironment);
        }
    });

    const platform = computed(() => {
        if (isInstalled.value) {
            return null;
        }

        if (deferredPrompt.value) {
            return 'android';
        }

        if (isIos.value) {
            return 'ios';
        }

        if (isAndroid.value) {
            return 'android';
        }

        return null;
    });

    const canPromptInstall = computed(
        () => !isInstalled.value && deferredPrompt.value !== null,
    );

    const showBanner = computed(() => {
        if (isInstalled.value || isDismissed.value) {
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

    async function installOrGuide() {
        if (deferredPrompt.value) {
            await install();
            return;
        }

        installGuideType.value = resolveGuideType();
        showInstallGuide.value = true;
    }

    function closeInstallGuide() {
        showInstallGuide.value = false;
    }

    function dismiss() {
        isDismissed.value = true;

        if (typeof window !== 'undefined') {
            window.localStorage.setItem(PWA_INSTALL_DISMISSED_KEY, 'true');
        }
    }

    return {
        showBanner,
        canPromptInstall,
        isInstalled,
        platform,
        showInstallGuide,
        installGuideType,
        install,
        installOrGuide,
        closeInstallGuide,
        dismiss,
    };
}
