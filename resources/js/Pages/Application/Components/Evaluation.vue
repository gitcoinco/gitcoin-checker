<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { defineProps, defineEmits, ref, watch, onMounted } from "vue";
import ResultsData from "@/Components/Gitcoin/Application/ResultsData.vue";
import UserEvaluationButton from "./UserEvaluationButton.vue";
import { showDateInShortFormat } from "@/utils";
import Checkbox from "@/Components/Checkbox.vue";

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
const showResults = ref(false);

// Initialize showResults from local storage
onMounted(() => {
    const savedShowResults = localStorage.getItem("showResults");
    showResults.value = savedShowResults === "true"; // Convert string back to boolean
});

// Watch for changes in showResults and save to local storage
watch(showResults, (newValue) => {
    localStorage.setItem("showResults", newValue.toString()); // Convert boolean to string for storage
});

watch(
    () => props.application,
    (newVal, oldVal) => {
        busyDoingGPTEvaluation.value = false;
        evaluationProgress.value = 0;
    }
);

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

const gptNrPercentage = (result) => {
    const gpt = gptNrYes();
    if (gpt) {
        return parseInt((gpt.nrYes / gpt.totalNrAnswers) * 100);
    } else {
        return null;
    }
};

const clearGPTResult = async () => {
    try {
        await axios.delete(
            route("round.application.evaluation.results.destroy", {
                application: props.application.uuid,
            })
        );
        emit("user-evaluation-updated", props.application);
    } catch (error) {
        console.error("Error deleting result: ", error);
    }
};

const clearHumanResult = async (answer) => {
    try {
        await axios.delete(
            route("round.application.evaluation-answer.destroy", {
                answer: answer.uuid,
            })
        );
        emit("user-evaluation-updated", props.application);
    } catch (error) {
        console.error("Error deleting result: ", error);
    }
};

const nrResults = (application) => {
    return application.results.length + application.evaluation_answers.length;
};
</script>
<template>
    <div>
        <SecondaryButton @click="showModal = true">
            <div class="" style="min-width: 100px">
                {{ nrResults(application) }} Evaluation<span
                    v-if="nrResults(application) > 1"
                    >s</span
                >
            </div></SecondaryButton
        >
        <Modal :show="showModal">
            <div class="bg-white p-5 w-full">
                <h2 class="modal-title flex justify-between">
                    Evaluate {{ application.project.title }}
                    <span @click="showModal = false" class="cursor-pointer">
                        <i class="fa fa-times-circle-o" aria-hidden="true"></i>
                    </span>
                </h2>

                <div class="mb-4">
                    <Checkbox
                        id="toggleResults"
                        v-model="showResults"
                        :checked="showResults"
                        class="mr-2"
                    />
                    <label for="toggleResults">Show result details</label>
                </div>

                <table>
                    <tr>
                        <th>Date</th>
                        <th>Who</th>
                        <th>Result</th>
                        <th>Notes</th>
                        <th></th>
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
                        <td>
                            <div v-if="showResults">
                                <Modal :show="showGPTResultsModal">
                                    <div class="bg-white p-5 w-full">
                                        <h2
                                            class="modal-title flex justify-between"
                                        >
                                            GPT Evaluation for
                                            {{ application.project.title }}
                                            <span
                                                @click="
                                                    showGPTResultsModal = false
                                                "
                                                class="cursor-pointer"
                                            >
                                                <i
                                                    class="fa fa-times-circle-o"
                                                    aria-hidden="true"
                                                ></i>
                                            </span>
                                        </h2>
                                        <ResultsData
                                            :result="
                                                props.application.results[0]
                                            "
                                        ></ResultsData>
                                    </div>
                                </Modal>
                                <a
                                    href="#"
                                    class="text-blue-500 hover:underline"
                                    @click="showGPTResultsModal = true"
                                >
                                    {{
                                        gptNrPercentage(
                                            props.application.results[0]
                                        )
                                    }}%
                                </a>
                            </div>
                        </td>
                        <td></td>
                        <td>
                            <SecondaryButton
                                @click="clearGPTResult"
                                v-if="$page.props.auth.user.is_admin"
                            >
                                Clear</SecondaryButton
                            >
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
                                <span v-if="evaluationProgress < 100">
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
                        <td></td>
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
                        <td>
                            <div v-if="showResults">{{ answer.score }}%</div>
                        </td>
                        <td>
                            <div v-if="showResults">
                                {{ answer.notes }}
                            </div>
                        </td>
                        <td>
                            <SecondaryButton
                                v-if="
                                    $page.props.auth_user_id == answer.user_id
                                "
                                @click="clearHumanResult(answer)"
                            >
                                Clear</SecondaryButton
                            >
                        </td>
                    </tr>

                    <tr>
                        <td colspan="5" class="text-center">
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
