<script setup>
import { getShortenedName } from "@/utils.js";

const props = defineProps({
    application: Object,
});

const reviewedBy = () => {
    let list = [];
    if (props.application?.results.length > 0) {
        list.push("GPT");
    }

    if (props.application?.evaluation_answers?.length > 0) {
        for (let i = 0; i < props.application.evaluation_answers.length; i++) {
            let name = getShortenedName(
                props.application.evaluation_answers[i].user.name
            );

            list.push(name);
        }
    }
    return list.join(", ");
};
</script>

<template>
    <div class="text-xs text-gray-400">
        {{ reviewedBy() }}
    </div>
</template>
