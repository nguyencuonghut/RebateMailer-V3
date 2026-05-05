import '../css/app.css';
import 'primeicons/primeicons.css';

import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primeuix/themes/aura';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import PrimeVue from 'primevue/config';

const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue');

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent<DefineComponent>(`./pages/${name}.vue`, pages),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.app-dark',
                    },
                },
            })
            .mount(el);
    },
    progress: {
        color: '#1f2937',
    },
});
