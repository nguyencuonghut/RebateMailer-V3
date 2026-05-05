import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent<DefineComponent>(`./pages/${name}.vue`, pages),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#1f2937',
    },
});
