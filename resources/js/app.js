import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/dist/vue.m";
import Bugsnag from "@bugsnag/js";
import BugsnagPluginVue from "@bugsnag/plugin-vue";

import "font-awesome/css/font-awesome.css";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";
const bugsnagApiKey = import.meta.env.VITE_BUGSNAG_API_KEY;

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        if (bugsnagApiKey) {
            Bugsnag.start({
                apiKey: bugsnagApiKey,
                plugins: [new BugsnagPluginVue.default()],
            });
            const bugsnagVue = Bugsnag.getPlugin("vue");
            const app = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .use(bugsnagVue)
                .mount(el);
        } else {
            const app = createApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue)
                .mount(el);
        }

        return app;
    },
    progress: {
        color: "#4B5563",
    },
});

// For testing bugsnag
// if (bugsnagApiKey) {
//     Bugsnag.notify(new Error("Test error from Vue"));
// }
