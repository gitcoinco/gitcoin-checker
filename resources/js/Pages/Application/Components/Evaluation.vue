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
});

const showModal = ref(false);

const showGPTResultsModal = ref(false);

const busyDoingGPTEvaluation = ref(false);

const doGPTEvaluation = async () => {
    busyDoingGPTEvaluation.value = true;
    emit("perform-gpt-evaluation", props.application);
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
                        <td>
                            <SecondaryButton
                                @click="doGPTEvaluation"
                                :disabled="busyDoingGPTEvaluation"
                            >
                                <span v-if="busyDoingGPTEvaluation"
                                    >Busy...</span
                                >
                                <span v-else>GPT evaluation</span>
                            </SecondaryButton>
                        </td>
                        <td>Evaluation not done yet</td>
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
