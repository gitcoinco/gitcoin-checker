<script setup>
import { defineProps } from "vue";

const props = defineProps({
    application: {
        type: Object,
        default: () => ({}),
    },
});

const gptNrYes = () => {
    if (props.application?.results?.length === 0) {
        return null;
    }

    let ret = {
        nrYes: 0,
        totalNrAnswers: 0,
    };

    try {
        let results = JSON.parse(props.application?.results[0].results_data);
        let total = 0;
        for (let i = 0; i < results.length; i++) {
            ret.totalNrAnswers += 1;

            if (results[i].score === "Yes") {
                ret.nrYes += 1;
            }
        }

        return ret;
    } catch (error) {
        console.error("Error parsing JSON: ", error);
        return null;
    }
};

const totalEvaluationAverage = () => {
    let nrYesAnswers = 0;
    let totalNrAnswers = 0;

    if (props.application?.evaluation_answers?.length > 0) {
        // Get the number of yes answers from users
        for (let i = 0; i < props.application.evaluation_answers.length; i++) {
            let answers = JSON.parse(
                props.application.evaluation_answers[i].answers
            );
            for (let j = 0; j < answers.length; j++) {
                totalNrAnswers += 1;
                if (answers[j] == "Yes") {
                    nrYesAnswers += 1;
                }
            }
        }
    }

    const gpt = gptNrYes();
    if (gpt) {
        nrYesAnswers += gpt.nrYes;
        totalNrAnswers += gpt.totalNrAnswers;
    }

    return parseInt((nrYesAnswers / totalNrAnswers) * 100);
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
