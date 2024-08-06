//./plugins/posthog.js
import posthog from "posthog-js";

export default {
    install(app) {
        app.config.globalProperties.$posthog = posthog.init(
            "phc_Ab0TTrZPUF9LHNRWYgtGO6OkhrJ9My5nlCqmQF09JY5",
            {
                api_host: "https://us.i.posthog.com",
                person_profiles: "identified_only",
                capture_pageview: false,
            }
        );
    },
};
