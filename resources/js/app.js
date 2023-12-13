import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/dist/vue.m";
import moment from "moment";
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
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Provide moment globally
        app.config.globalProperties.$moment = moment;

        if (bugsnagApiKey) {
            Bugsnag.start({
                apiKey: bugsnagApiKey,
                plugins: [new BugsnagPluginVue.default()],
            });
            const bugsnagVue = Bugsnag.getPlugin("vue");
            app.use(bugsnagVue);
        }

        app.mount(el);

        return app;
    },
    progress: {
        color: "#4B5563",
    },
});

// if production
if (process.env.NODE_ENV === "production") {
    router.on("navigate", (event) => {
        gtag("event", "page_view", {
            page_location: event.detail.page.url,
        });
    });
}
