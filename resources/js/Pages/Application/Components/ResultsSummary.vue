<script setup>
import { defineProps } from "vue";

const props = defineProps({
    application: {
        type: Object,
        default: () => ({}),
    },
});

const totalEvaluationAverage = () => {
    if (props.application?.evaluation_answers?.length > 0) {
        let total = props.application.evaluation_answers.reduce(
            (acc, curr) => acc + curr.score,
            0
        );

        total += gptEvaluationAverage();

        return total / (props.application.evaluation_answers.length + 1) + "%";
    } else {
        const gptAverage = gptEvaluationAverage();
        return gptAverage ? gptAverage + "%" : null;
    }
};

const gptEvaluationAverage = () => {
    if (props.application?.results?.length > 0) {
        try {
            let results = JSON.parse(
                props.application?.results[0].results_data
            );
            let total = 0;
            for (let i = 0; i < results.length; i++) {
                total += parseInt(results[i].score);
            }

            return parseInt(total / results.length);
        } catch (error) {
            console.error("Error parsing JSON: ", error);
            return null;
        }
    }
};
</script>
<template>
    <div>{{ totalEvaluationAverage() }}</div>
</template>
