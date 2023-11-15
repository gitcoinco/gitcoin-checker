<script setup>
import { ref, toRefs } from "vue";
let props = defineProps({
    application: Object,
});
let { application } = toRefs(props);
</script>
<template>
    <div>
        <h2 class="mb-5">Project: {{ application.project.title }}</h2>
        <div :style="{ 'white-space': 'pre-line' }" class="mb-5">
            <h2 class="bg-gray-300 text-gray-500 pl-3 py-1 rounded mb-3">
                System prompt:
            </h2>
            <div class="text-sm">
                {{ application.generated_prompt.system_prompt }}
            </div>
        </div>
        <div :style="{ 'white-space': 'pre-line' }" class="mb-5">
            <h2 class="bg-gray-300 text-gray-500 pl-3 py-1 rounded mb-3">
                Prompt
            </h2>
            <div class="text-sm">
                {{ application.generated_prompt.prompt }}
            </div>
        </div>
    </div>
</template>
