<script setup>
import { ref, toRefs } from "vue";
import MarkdownIt from "markdown-it";
const markdown = new MarkdownIt();
import { Link } from "@inertiajs/vue3";

let props = defineProps({
    application: Object,
});
let { application } = toRefs(props);
</script>
<template>
    <div>
        <div class="mb-5">
            <h2>
                <Link
                    :href="
                        route('project.show', {
                            slug: application.project.slug,
                        })
                    "
                    class="text-blue-500"
                    >Project: {{ application.project.title }}</Link
                >
            </h2>
            <h4>
                User Github:
                {{
                    application.project.userGithub
                        ? application.project.userGithub
                        : "Not provided"
                }}
            </h4>

            <h4>
                Project Github:
                {{
                    application.project.projectGithub
                        ? application.project.projectGithub
                        : "Not provided"
                }}
            </h4>
        </div>

        <div :style="{ 'white-space': 'pre-line' }" class="mb-5">
            <h2 class="bg-gray-300 text-gray-500 pl-3 py-1 rounded mb-3">
                System prompt:
            </h2>
            <div
                class="text-sm markdown"
                v-html="
                    markdown.render(application.generated_prompt.system_prompt)
                "
            ></div>
        </div>
        <div :style="{ 'white-space': 'pre-line' }" class="mb-5">
            <h2 class="bg-gray-300 text-gray-500 pl-3 py-1 rounded mb-3">
                Prompt
            </h2>
            <div
                class="text-sm markdown"
                v-html="markdown.render(application.generated_prompt.prompt)"
            ></div>
        </div>
    </div>
</template>
