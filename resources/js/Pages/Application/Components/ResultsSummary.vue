<script setup>
import { defineProps } from "vue";

const props = defineProps({
    application: {
        type: Object,
        default: () => ({}),
    },
});

const gptNrs = () => {
    if (props.application?.results?.length === 0) {
        return null;
    }

    let ret = {
        nrYes: 0,
        nrNo: 0,
        nrUncertain: 0,
        totalNrAnswers: 0,
    };

    try {
        let results = JSON.parse(props.application?.results[0].results_data);
        let total = 0;
        for (let i = 0; i < results.length; i++) {
            ret.totalNrAnswers += 1;

            if (results[i].score === "Yes") {
                ret.nrYes += 1;
            } else if (results[i].score === "No") {
                ret.nrNo += 1;
            } else if (results[i].score === "Uncertain") {
                ret.nrUncertain += 1;
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
    let nrNoAnswers = 0;
    let nrUncertainAnswers = 0;

    let totalNrAnswers = 0;

    if (props.application?.evaluation_answers?.length > 0) {
        // Get the number of yes answers from users
        for (let i = 0; i < props.application.evaluation_answers.length; i++) {
            let answers = JSON.parse(
                props.application.evaluation_answers[i].answers
            );
            console.log(answers);
            for (let j = 0; j < answers.length; j++) {
                totalNrAnswers += 1;
                if (answers[j] == "Yes") {
                    nrYesAnswers += 1;
                } else if (answers[j] == "No") {
                    nrNoAnswers += 1;
                } else if (answers[j] == "Uncertain") {
                    nrUncertainAnswers += 1;
                }
            }
        }
    }

    const gpt = gptNrs();
    if (gpt) {
        nrYesAnswers += gpt.nrYes;
        nrNoAnswers += gpt.nrNo;
        nrUncertainAnswers += gpt.nrUncertain;
        totalNrAnswers += gpt.totalNrAnswers;
    }

    const score = parseInt((nrYesAnswers / totalNrAnswers) * 100);
    return {
        score: isNaN(score) ? null : score,
        yes: nrYesAnswers,
        no: nrNoAnswers,
        uncertain: nrUncertainAnswers,
        total: totalNrAnswers,
    };
};
</script>
<template>
    <div v-if="totalEvaluationAverage().score !== null">
        <div
            class="h-12 w-12 rounded-full flex items-center justify-center text-white text-sm"
            :class="{
                'bg-red-500': totalEvaluationAverage().score < 40,
                'bg-orange-500':
                    totalEvaluationAverage().score >= 40 &&
                    totalEvaluationAverage().score < 70,
                'bg-green-500': totalEvaluationAverage().score >= 70,
            }"
        >
            {{ totalEvaluationAverage().score }}%
        </div>
        <div
            v-if="
                totalEvaluationAverage().uncertain >=
                totalEvaluationAverage().yes + totalEvaluationAverage().no
            "
            class="text-xs text-gray-500 items-center flex justify-center"
        >
            {{ totalEvaluationAverage().uncertain }} uncertain<span
                v-if="totalEvaluationAverage().uncertain > 1"
                >s</span
            >
        </div>
    </div>
</template>
