import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from 'vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, DefineComponent, h } from 'vue';
import { createHead } from '@vueuse/head';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: (name) =>
                resolvePageComponent(
                    `./pages/${name}.vue`,
                    import.meta.glob<DefineComponent>('./pages/**/*.vue'),
                ),
            setup: ({ App, props, plugin }) => {
                const ziggy = (page.props as any)?.ziggy ?? {};
                const head = createHead();
                return createSSRApp({ render: () => h(App, props) })
                    .use(plugin)
                    .use(head)
                    .use(ZiggyVue, {
                        ...ziggy,
                        location: ziggy.location
                            ? new URL(ziggy.location)
                            : undefined,
                    });
            },
        }),
    { cluster: true },
);
