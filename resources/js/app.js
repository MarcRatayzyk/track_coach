import '../css/app.css';
import './bootstrap';
import './echo';
import './plugins/charts';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { initTheme } from './composables/useTheme';
import { isNativeApp } from './composables/useNativeApp';
import { initNativeApp } from './pwa/initNativeApp';
import { registerServiceWorker } from './pwa/registerServiceWorker';
import {
    identifyUser,
    initAnalytics,
    resetAnalytics,
} from './utils/analytics';

initTheme();

if (!isNativeApp) {
    registerServiceWorker();
}

initNativeApp();
initAnalytics();

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
        lastIdentifiedUserId = null;
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} — Power Roster` : 'Power Roster'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        syncAnalyticsUser(props.initialPage);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});

router.on('navigate', (event) => {
    syncAnalyticsUser(event.detail.page);
});
