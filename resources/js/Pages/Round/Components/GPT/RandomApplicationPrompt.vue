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
        <div :style="{ 'white-space': 'pre-line' }">
            <h2>System prompt:</h2>
            {{ application.generated_prompt.system_prompt }}
        </div>
        <div :style="{ 'white-space': 'pre-line' }">
            <h2>Prompt</h2>
            {{ application.generated_prompt.prompt }}
        </div>
    </div>
</template>
