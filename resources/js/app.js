import '../css/app.css';
import './bootstrap';
import './echo';
import './plugins/charts';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { initTheme } from './composables/useTheme';

initTheme();

createInertiaApp({
    title: (title) => (title ? `${title} — Track Coach` : 'Track Coach'),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});
