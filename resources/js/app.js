import '../css/app.css';
import './bootstrap';
import './echo';
import './plugins/charts';
import { createApp, h, Fragment } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { initTheme } from './composables/useTheme';
import { isNativeApp } from './composables/useNativeApp';
import { initNativeApp } from './pwa/initNativeApp';
import { registerServiceWorker } from './pwa/registerServiceWorker';
import {
    identifyUser,
    resetAnalytics,
} from './utils/analytics';
import CookieConsentBanner from './Components/CookieConsentBanner.vue';
import i18n, { setAppLocale } from './i18n';

initTheme();

if (!isNativeApp) {
    registerServiceWorker();
}

initNativeApp();

let lastIdentifiedUserId = null;

function syncAnalyticsUser(page) {
    const user = page?.props?.auth?.user ?? null;

    if (user?.id) {
        identifyUser(user);
        lastIdentifiedUserId = user.id;
        return;
    }

    if (lastIdentifiedUserId !== null) {
        resetAnalytics();
    }
    lastIdentifiedUserId = null;
}

function syncLocale(page) {
    setAppLocale(page?.props?.locale ?? 'en');
}

createInertiaApp({
    title: (title) => (title ? `${title} — Power Roster` : 'Power Roster'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        syncAnalyticsUser(props.initialPage);
        syncLocale(props.initialPage);

        createApp({
            render: () =>
                h(Fragment, null, [h(App, props), h(CookieConsentBanner)]),
        })
            .use(plugin)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});

router.on('navigate', (event) => {
    syncAnalyticsUser(event.detail.page);
    syncLocale(event.detail.page);
});
