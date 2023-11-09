<script setup>
import { defineProps } from "vue";

const props = defineProps({
    application: {
        type: Object,
        default: () => ({}),
    },
});

const gptEvaluationAverage = () => {
    if (props.application?.results?.length === 0) {
        return null;
    }

    try {
        let results = JSON.parse(props.application?.results[0].results_data);
        let total = 0;
        for (let i = 0; i < results.length; i++) {
            total += parseInt(results[i].score);
        }

        return parseInt(total / results.length);
    } catch (error) {
        console.error("Error parsing JSON: ", error);
        return null;
    }
};

const totalEvaluationAverage = () => {
    if (props.application?.evaluation_answers?.length > 0) {
        let total = props.application.evaluation_answers.reduce(
            (acc, curr) => acc + curr.score,
            0
        );

        total += gptEvaluationAverage();

        return total / (props.application.evaluation_answers.length + 1);
    } else {
        const gptAverage = gptEvaluationAverage();
        return gptAverage ? gptAverage : null;
    }
};
</script>
<template>
    <div
        v-if="totalEvaluationAverage()"
        class="h-12 w-12 rounded-full flex items-center justify-center text-white text-sm"
        :class="{
            'bg-red-500': totalEvaluationAverage() < 40,
            'bg-orange-500':
                totalEvaluationAverage() >= 40 && totalEvaluationAverage() < 70,
            'bg-green-500': totalEvaluationAverage() >= 70,
        }"
    >
        {{ totalEvaluationAverage() }}%
    </div>
</template>
