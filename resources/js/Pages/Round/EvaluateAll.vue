<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { copyToClipboard, shortenAddress, scoreTotal } from "@/utils.js";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";

const round = ref(usePage().props.round.valueOf());
const projects = ref(usePage().props.projects.valueOf());

let projectsTotal = projects.value.total;
const latestPrompt = ref(
    usePage().props.latestPrompt ? usePage().props.latestPrompt.valueOf() : null
);

const form = useForm([]);

const isLoading = ref(false);

// New state for loading indicator for each project
const loadingStates = ref({});

// Evaluation specific states
const currentProjectIndex = ref(0);
const evaluationResults = ref([]);
const isEvaluating = ref(false);

const evaluateAll = async () => {
    isEvaluating.value = true;

    // Loop through each project for evaluation
    for (let index = 0; index < projects.value.data.length; index++) {
        currentProjectIndex.value = index;

        try {
            let result = await evaluateApplication(
                projects.value.data[index].applications[0]
            );
            evaluationResults.value.push(result);
            projectsTotal--;
        } catch (error) {}

        // Optional: Delay to allow users to view result before next evaluation
        await new Promise((resolve) => setTimeout(resolve, 2000));
    }

    isLoading.value = false;
    isEvaluating.value = false;
};

async function evaluateApplication(application) {
    console.log("Evaluating application: ", application.id);
    try {
        let response = await axios.post(
            route("round.application.chatgpt.list", {
                application: application.id,
            })
        );
        return response.data;
    } catch (error) {
        console.error("Error evaluating application:", error);
        return null; // Or handle this as you see fit.
    }
}
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <Link :href="route('round.show', { round })">
                    {{ round.name }}
                </Link>
                <span class="text-sm">
                    {{ shortenAddress(round.round_addr) }}

                    <span
                        @click="copyToClipboard(round.round_addr)"
                        class="cursor-pointer"
                    >
                        <i class="fa fa-clone" aria-hidden="true"></i>
                    </span>
                </span>
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <h2 class="text-xl">
                    Evaluate all the projects that have not been evaluate for
                    "{{ round.name }}"
                </h2>

                <div class="mt-5 mb-5 text-xl">
                    <span class="font-semibold"
                        >Number of projects that need to be evaluated:</span
                    >
                    {{ projectsTotal }}
                </div>

                <div v-if="isEvaluating" title="Evaluation in Progress">
                    <div class="mb-5">
                        <h2 class="text-xl">Busy evaluating...</h2>
                        <p>This might take a while. Please be patient.</p>
                    </div>
                    <div>
                        <h2 class="text-xl">
                            {{ projects.data[currentProjectIndex].title }}
                            <span
                                v-if="
                                    evaluationResults[currentProjectIndex] &&
                                    evaluationResults[currentProjectIndex]
                                        .project
                                "
                            >
                                -
                                {{
                                    scoreTotal(
                                        evaluationResults[currentProjectIndex]
                                            .project.applications[0].results
                                    )
                                }}
                            </span>
                        </h2>
                        <div v-if="evaluationResults[currentProjectIndex]">
                            <table class="score-table">
                                <thead>
                                    <tr>
                                        <th>Score</th>
                                        <th>Criteria</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(result, index) in JSON.parse(
                                            evaluationResults[
                                                currentProjectIndex
                                            ].project.applications[0].results[0]
                                                .results_data
                                        )"
                                        :key="'eval-application-' + index"
                                    >
                                        <td class="score-value">
                                            {{ result.score }}
                                        </td>
                                        <td>
                                            {{ result.criteria }}
                                        </td>
                                        <td>
                                            {{ result.reason }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <PrimaryButton
                    class="mt-4"
                    @click="evaluateAll"
                    :disabled="isLoading"
                    v-if="!isEvaluating"
                >
                    Evaluate all
                </PrimaryButton>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Add styles for disabled link */
a[disabled] {
    pointer-events: none;
    opacity: 0.6;
}

.modal-content {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.modal-title {
    font-size: 1.5em;
    margin-bottom: 20px;
    font-weight: bold;
}

.score-table {
    width: 100%;
    border-collapse: collapse;
}

.score-table th,
.score-table td {
    border: 1px solid #ddd;
    padding: 8px 12px;
}

.score-table th {
    background-color: #f2f2f2;
    text-align: left;
}

.score-value {
    font-weight: bold;
}
</style>
