<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { defineProps, defineEmits, ref } from "vue";
import ResultsData from "@/Components/Gitcoin/Application/ResultsData.vue";
import UserEvaluationButton from "./UserEvaluationButton.vue";
import { showDateInShortFormat } from "@/utils";

const emit = defineEmits(["perform-gpt-evaluation", "user-evaluation-updated"]);

const props = defineProps({
    application: {
        type: Object,
        default: () => ({}),
    },
    loadingBarInSeconds: {
        type: Number,
        default: 30,
    },
});

const showModal = ref(false);

const showGPTResultsModal = ref(false);

const busyDoingGPTEvaluation = ref(false);
const evaluationProgress = ref(0);

const doGPTEvaluation = async () => {
    busyDoingGPTEvaluation.value = true;
    evaluationProgress.value = 0; // Reset progress
    const interval = props.loadingBarInSeconds; // Total time for the progress bar in seconds
    const step = 1000; // Update every second

    // Update the progress every second
    const intervalId = setInterval(() => {
        if (evaluationProgress.value < 100) {
            evaluationProgress.value++;
        } else {
            clearInterval(intervalId);
            //            busyDoingGPTEvaluation.value = false;
        }
    }, interval * 10); // interval * 10 because 100% / 30 seconds = 3.33% per second

    emit("perform-gpt-evaluation", props.application);
    // Simulate a 30 second task
    setTimeout(() => {
        clearInterval(intervalId);
        //        busyDoingGPTEvaluation.value = false;
        evaluationProgress.value = 100;
    }, interval * 1000);
};

const handleUserEvaluateApplication = () => {
    emit("user-evaluation-updated", props.application);
};

// GPT score calculation as percentage
const getGPTScore = (results) => {
    let score = 0;
    const data = JSON.parse(results.results_data);
    for (let i = 0; i < data.length; i++) {
        score += parseInt(data[i].score);
    }
    score = parseInt(score / data.length);
    return score;
};
</script>
<template>
    <div>
        <SecondaryButton @click="showModal = true">
            <i class="fa fa-list mr-1" aria-hidden="true"></i>
            Evaluation</SecondaryButton
        >
        <Modal :show="showModal">
            <div class="bg-white p-5 w-full">
                <h2 class="modal-title flex justify-between">
                    Evaluate {{ application.project.title }}
                    <span @click="showModal = false" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>

                <table>
                    <tr>
                        <th>Date</th>
                        <th>Who</th>
                        <th>Result</th>
                        <th>Notes</th>
                    </tr>
                    <tr v-if="props.application?.results?.length > 0">
                        <td>
                            {{
                                showDateInShortFormat(
                                    props.application.results[0].created_at,
                                    true
                                )
                            }}
                        </td>
                        <td>
                            <i class="fa fa-server mr-1" aria-hidden="true"></i>
                            GPT
                        </td>
                        <td colspan="2">
                            <Modal :show="showGPTResultsModal">
                                <div class="bg-white p-5 w-full">
                                    <h2
                                        class="modal-title flex justify-between"
                                    >
                                        GPT Evaluation for
                                        {{ application.project.title }}
                                        <span
                                            @click="showGPTResultsModal = false"
                                            class="cursor-pointer"
                                        >
                                            <i
                                                class="fa fa-times-circle-o"
                                                aria-hidden="true"
                                            ></i>
                                        </span>
                                    </h2>
                                    <ResultsData
                                        :result="props.application.results[0]"
                                    ></ResultsData>
                                </div>
                            </Modal>
                            <a
                                href="#"
                                class="text-blue-500 hover:underline"
                                @click="showGPTResultsModal = true"
                            >
                                {{ getGPTScore(props.application.results[0]) }}%
                            </a>
                        </td>
                    </tr>
                    <tr v-else>
                        <td>-</td>
                        <td>
                            <i class="fa fa-server mr-1" aria-hidden="true"></i>

                            GPT
                        </td>
                        <td class="align-middle">
                            <SecondaryButton
                                @click="doGPTEvaluation"
                                :disabled="busyDoingGPTEvaluation"
                                v-if="!busyDoingGPTEvaluation"
                            >
                                Run GPT evaluation
                            </SecondaryButton>
                            <span v-else>
                                <span v-if="evaluationProgress < 90">
                                    <div
                                        class="overflow-hidden h-2 text-xs flex rounded bg-pink-200 items-center"
                                    >
                                        <div
                                            style="height: 20px"
                                            :style="{
                                                width: evaluationProgress + '%',
                                            }"
                                            class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-pink-500"
                                        ></div>
                                    </div>
                                </span>
                                <span v-else>...a bit longer</span>
                            </span>
                        </td>
                        <td>
                            <span v-if="busyDoingGPTEvaluation">
                                Evaluation in progress.
                            </span>
                            <span v-else> Evaluation not done yet </span>
                        </td>
                    </tr>
                    <tr
                        v-for="(
                            answer, index
                        ) in application.evaluation_answers"
                        :key="index"
                    >
                        <td>
                            {{ showDateInShortFormat(answer.updated_at, true) }}
                        </td>
                        <td>
                            <i class="fa fa-user mr-1" aria-hidden="true"></i
                            >{{ answer.user.name }}
                        </td>
                        <td>{{ answer.score }}%</td>
                        <td>
                            {{ answer.notes }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            <UserEvaluationButton
                                :application="application"
                                @evaluated-application="
                                    handleUserEvaluateApplication
                                "
                            ></UserEvaluationButton>
                        </td>
                    </tr>
                </table>
            </div>
        </Modal>
    </div>
</template>
