import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => {
        // Prefer runtime-provided app name (from server shared props) if available, else fall back to VITE_APP_NAME
        let runtimeApp = appName;
        try {
            const page = window.__INERTIA__ && window.__INERTIA__.page ? window.__INERTIA__.page : null;
            if (page && page.props && page.props.app && page.props.app.name) {
                runtimeApp = page.props.app.name;
            }
        } catch (e) {}
        return `${runtimeApp} - ${title}`;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Set favicon from server-provided shared props (if company logo uploaded)
        try {
            const initial = props.initialPage || props;
            const appProps = initial.props && initial.props.app ? initial.props.app : null;
            const logo = appProps && appProps.logo ? appProps.logo : null;
            if (logo) {
                let link = document.querySelector("link[rel*='icon']");
                if (!link) {
                    link = document.createElement('link');
                    link.setAttribute('rel', 'icon');
                    document.getElementsByTagName('head')[0].appendChild(link);
                }
                link.setAttribute('href', logo);
                // prefer shortcut icon too
                let sc = document.querySelector("link[rel='shortcut icon']");
                if (!sc) {
                    sc = document.createElement('link');
                    sc.setAttribute('rel', 'shortcut icon');
                    document.getElementsByTagName('head')[0].appendChild(sc);
                }
                sc.setAttribute('href', logo);
            }
        } catch (e) {
            // ignore
        }

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
