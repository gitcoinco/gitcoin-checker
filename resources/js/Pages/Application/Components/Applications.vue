<script setup>
import { ref, watch, defineEmits } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import CheckBox from "@/Components/Checkbox.vue";
import PreviousApplicationStatus from "@/Components/Gitcoin/Application/PreviousApplicationStatus.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Applications from "@/Pages/Application/Components/Applications.vue";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import SpecifyUserRounds from "@/Components/Gitcoin/SpecifyUserRounds.vue";
import GptEvaluationButton from "@/Components/Gitcoin/Application/GPTEvaluationButton.vue";
import UserEvaluationButton from "@/Pages/Application/Components/UserEvaluationButton.vue";
import GptEvaluationResults from "@/Components/Gitcoin/Application/GptEvaluationResults.vue";
import UserEvaluationResults from "@/Components/Gitcoin/Application/UserEvaluationResults.vue";
import ApplicationAnswers from "@/Components/Gitcoin/Application/ApplicationAnswers.vue";
import Evaluation from "./Evaluation.vue";
import ResultsSummary from "./ResultsSummary.vue";
import moment from "moment";

import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
    applicationStatusIcon,
    showDateInShortFormat,
} from "@/utils.js";
import Modal from "@/Components/Modal.vue";

import { defineProps } from "vue";

const emit = defineEmits([
    "status-changed",
    "round-type",
    "remove-tests",
    "refresh-applications",
    "user-rounds-changed",
    "search-projects",
]);

// Define the props the component accepts
const props = defineProps({
    applications: {
        type: Object,
        default: () => ({}),
    },
});

const selectedApplicationStatus = ref(
    usePage().props.selectedApplicationStatus.valueOf()
);
const selectedApplicationRoundType = ref(
    usePage().props.selectedApplicationRoundType.valueOf()
);
const selectedApplicationRemoveTests = ref(
    usePage().props.selectedApplicationRemoveTests.valueOf()
);
const selectedSearchProjects = ref(
    usePage().props.selectedSearchProjects.valueOf()
);

const roundPrompt = (round) => {
    router.visit(route("round.prompt.show", { round: round }));
};

const selectedApplicationStatusRef = ref(selectedApplicationStatus.value);
const selectedApplicationRoundTypeRef = ref(selectedApplicationRoundType.value);
const selectedApplicationRemoveTestsRef = ref(
    selectedApplicationRemoveTests.value
);

watch(selectedApplicationRemoveTestsRef, (newStatus) => {
    emit("remove-tests", newStatus);
});

watch(selectedApplicationStatusRef, (newStatus) => {
    emit("status-changed", newStatus);
});

watch(selectedApplicationRoundTypeRef, (newStatus) => {
    emit("round-type", newStatus);
});

const updateSelectedRounds = () => {
    emit("user-rounds-changed");
};

const searchProjects = () => {
    emit("search-projects", selectedSearchProjects.value);
};

// New state for loading indicator for each applications
const loadingStates = ref({});

const handleUserEvaluateApplication = async (application) => {
    emit("refresh-applications");
};

// Methods to handle events emitted by the GptEvaluationButton component
const handleEvaluateApplication = async (application) => {
    // Start loading for this specific project
    loadingStates.value[application.id] = true;

    try {
        const response = await axios.post(
            route("round.application.chatgpt.list", {
                application: application.uuid,
            })
        );

        // Find the application index in the applications array
        const index = props.applications.data.findIndex(
            (app) => app.id === application.id
        );

        // Assuming response.data.project.applications[0].results[0] contains the updated results you want to insert.
        props.applications.data[index].results.unshift(
            response.data.project.applications[0].results[0]
        );
    } catch (error) {
        // Handle error properly, maybe set an error message to display in the UI
        console.error("An error occurred:", error);
    } finally {
        // Stop loading for this specific project
        delete loadingStates.value[application.id];
    }
};

const refreshApplication = async (application) => {
    try {
        const response = await axios.get(
            route("round.application.show", {
                application: application.uuid,
            })
        );

        // Find the application index in the applications array
        const index = props.applications.data.findIndex(
            (app) => app.id === application.id
        );

        props.applications.data[index] = response.data.application;
    } catch (error) {
        // Handle error properly, maybe set an error message to display in the UI
        console.error("An error occurred:", error);
    } finally {
        // Stop loading for this specific project
        delete loadingStates.value[application.id];
    }
};

const handleRoundPrompt = (round) => {
    // This function was already defined in your setup as 'roundPrompt'
    // Just point it to the correct function here to maintain consistency in naming.
    roundPrompt(round);
};

const formatDate = (dateString) => {
    return moment(dateString).fromNow();
};
</script>

<template>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <div class="flex items-center justify-end mb-3">
                <CheckBox
                    v-model="selectedApplicationRemoveTestsRef"
                    :checked="selectedApplicationRemoveTestsRef == 1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label
                    for="remove-test-projects"
                    class="ml-2 block text-sm text-gray-900"
                >
                    Remove "test" rounds
                </label>
            </div>
            <table class="">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="whitespace-nowrap">
                            <select
                                v-model="selectedApplicationStatusRef"
                                class="p-1 mr-1 pr-6"
                            >
                                <option value="all">All</option>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>

                            <Tooltip>
                                <i
                                    class="fa fa-question-circle-o text-gray-400"
                                    aria-hidden="true"
                                    title="This is the last application date for the round"
                                ></i>
                                <template #content>
                                    The status of the application.
                                </template>
                            </Tooltip>
                        </th>
                        <th>
                            <input
                                type="text"
                                v-model="selectedSearchProjects"
                                @keyup.enter.prevent="searchProjects()"
                                class="p-1 mr-1 pr-6"
                                placeholder="Projects"
                            />
                            in
                            <select
                                v-model="selectedApplicationRoundTypeRef"
                                class="p-1 mr-1 pr-6"
                            >
                                <option value="all">All</option>
                                <option value="mine">My Rounds</option>
                            </select>
                        </th>
                        <th class="text-center">
                            <i
                                class="fa fa-clock-o fa-2x text-gray-400"
                                aria-hidden="true"
                            ></i>
                        </th>
                        <th class="text-center">
                            <div
                                v-if="selectedApplicationRoundTypeRef == 'mine'"
                            >
                                <SpecifyUserRounds
                                    @selected-rounds-changed="
                                        updateSelectedRounds
                                    "
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody
                    v-if="
                        props.applications && props.applications.data.length > 0
                    "
                >
                    <tr
                        v-for="(application, index) in applications.data"
                        :key="index"
                    >
                        <td class="border-l-0">
                            <div class="flex items-center">
                                <span
                                    v-html="
                                        applicationStatusIcon(
                                            application.status
                                        )
                                    "
                                    class="mr-1"
                                ></span>
                                <span class="text-xs">
                                    {{ formatDate(application.created_at) }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div>
                                <Link
                                    v-if="application.project"
                                    :href="
                                        route(
                                            'project.show',
                                            application.project
                                        )
                                    "
                                    class="text-blue-500 hover:underline mr-2"
                                >
                                    {{ application.project.title }}
                                </Link>

                                <ApplicationAnswers
                                    :application="application"
                                />
                            </div>
                            <div>
                                in
                                <Link
                                    :href="
                                        route('round.show', application.round)
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ application.round.name }}
                                </Link>
                            </div>
                        </td>
                        <td>
                            <PreviousApplicationStatus
                                :application="application"
                            />
                        </td>
                        <td class="border-r-0">
                            <div v-if="application.project" class="text-center">
                                <Evaluation
                                    :application="application"
                                    @perform-gpt-evaluation="
                                        handleEvaluateApplication
                                    "
                                    @user-evaluation-updated="
                                        refreshApplication
                                    "
                                />
                                <ResultsSummary :application="application" />
                            </div>
                            <div v-else>No project data available yet</div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="selectedApplicationRoundTypeRef == 'mine'">
                    <tr>
                        <td colspan="5" class="text-center">
                            <SpecifyUserRounds
                                @selected-rounds-changed="updateSelectedRounds"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

            <Pagination :links="applications.links" />
        </div>
    </div>
</template>
