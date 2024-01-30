<script setup>
import { ref, watch, defineEmits } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import CheckBox from "@/Components/Checkbox.vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import SpecifyUserRounds from "@/Components/Gitcoin/SpecifyUserRounds.vue";
import ReviewedBy from "./ReviewedBy.vue";
import EvaluationResults from "./EvaluationResults.vue";
import Application from "./Application.vue";

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
    "order-by-changed",
    "order-by-direction-changed",
]);

// Define the props the component accepts
const props = defineProps({
    applications: {
        type: Object,
        default: () => ({}),
    },
    displayFilter: {
        type: Boolean,
        default: true,
    },
    busyLoadingApplications: {
        type: Boolean,
        default: false,
    },
    orderBy: {
        type: String,
        default: "created_at",
    },
    orderByDirection: {
        type: String,
        default: "desc",
    },
});

const orderBy = ref(usePage().props.orderBy.valueOf());
const orderByDirection = ref(usePage().props.orderByDirection.valueOf());

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

const averageGPTEvaluationTime = usePage().props.averageGPTEvaluationTime;

const roundPrompt = (round) => {
    router.visit(route("round.prompt.show", { round: round }));
};

const selectedApplicationStatusRef = ref(selectedApplicationStatus.value);
const selectedApplicationRoundTypeRef = ref(selectedApplicationRoundType.value);
const selectedApplicationRemoveTestsRef = ref(
    selectedApplicationRemoveTests.value
);

watch(orderBy, (newStatus) => {
    emit("order-by-changed", newStatus);
});

watch(orderByDirection, (newStatus) => {
    emit("order-by-direction-changed", newStatus);
});

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
</script>

<template>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div v-if="displayFilter">
            <div class="flex items-center justify-end mb-3">
                <CheckBox
                    v-model="selectedApplicationRemoveTestsRef"
                    :checked="selectedApplicationRemoveTestsRef == 1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                />
                <label
                    for="remove-test-projects"
                    class="ml-2 block text-xs text-gray-900"
                >
                    Remove "test" rounds
                </label>
            </div>
            <div
                class="border border-black flex justify-between border-t-0 border-l-0 border-r-0"
            >
                <div class="border-black mr-5 rounded">
                    <div class="flex">
                        <button
                            class="p-1 mr-1 pl-3 pr-3 text-center"
                            :class="{
                                'bg-blue-200':
                                    selectedApplicationStatusRef === 'pending',
                            }"
                            @click="selectedApplicationStatusRef = 'pending'"
                        >
                            Pending
                        </button>
                        <button
                            class="p-1 mr-1 pl-3 pr-3 text-center"
                            :class="{
                                'bg-blue-200':
                                    selectedApplicationStatusRef === 'approved',
                            }"
                            @click="selectedApplicationStatusRef = 'approved'"
                        >
                            Accepted
                        </button>
                        <button
                            class="p-1 mr-1 pl-3 pr-3 text-center"
                            :class="{
                                'bg-blue-200':
                                    selectedApplicationStatusRef === 'rejected',
                            }"
                            @click="selectedApplicationStatusRef = 'rejected'"
                        >
                            Rejected
                        </button>
                        <button
                            class="p-1 mr-1 pl-3 pr-3 text-center"
                            :class="{
                                'bg-blue-200':
                                    selectedApplicationStatusRef === 'all',
                            }"
                            @click="selectedApplicationStatusRef = 'all'"
                        >
                            All
                        </button>
                    </div>
                </div>
                <div>
                    <div
                        class="flex items-center p-1 pl-2 pr-2 bg-gray-200"
                        style="min-height: 32px"
                    >
                        <i class="fa fa-search mr-1" aria-hidden="true"></i>
                        <TextInput
                            v-model="selectedSearchProjects"
                            @keyup.enter.prevent="searchProjects"
                            placeholder="Search projects"
                            class="p-0 px-1 mr-1 text-sm"
                        />
                        <select
                            v-model="selectedApplicationRoundTypeRef"
                            class="border-gray-300 flex-grow p-0 mr-1 pr-7 text-sm rounded-md px-1"
                        >
                            <option value="all">All</option>
                            <option value="mine">My Rounds</option>
                        </select>
                        <div
                            class="flex items-center"
                            v-if="selectedApplicationRoundTypeRef == 'mine'"
                        >
                            <SpecifyUserRounds
                                @selected-rounds-changed="updateSelectedRounds"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-200 p-1">
                Order by:

                <select
                    v-model="orderBy"
                    class="border-gray-300 flex-grow p-0 mr-1 pr-7 text-sm rounded-md px-1"
                >
                    <option value="created_at">Date</option>
                    <option value="score">Score</option>
                </select>

                <select
                    v-model="orderByDirection"
                    class="border-gray-300 flex-grow p-0 mr-1 pr-7 text-sm rounded-md px-1"
                >
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col">
            <div v-if="!busyLoadingApplications">
                <div v-if="props?.applications?.data?.length > 0" class="pt-10">
                    <div
                        v-for="(application, index) in applications.data"
                        :key="index"
                        class="border-b mb-10"
                    >
                        <Application
                            :application="application"
                            :averageGPTEvaluationTime="averageGPTEvaluationTime"
                            @perform-gpt-evaluation="handleEvaluateApplication"
                            @user-evaluation-updated="refreshApplication"
                        />

                        <div>
                            <EvaluationResults :application="application" />
                        </div>
                    </div>
                    <Pagination :links="applications.links" />
                </div>
                <div v-else class="pt-5">
                    No results for your selected filter.
                </div>
            </div>
            <div v-else>
                <div class="flex justify-center items-center">
                    <span class="mt-10 py-10 text-4xl">
                        <i class="fa fa-spinner fa-spin" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
