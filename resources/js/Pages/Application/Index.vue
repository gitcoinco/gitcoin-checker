<script setup>
import { ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import ResultsData from "@/Components/Gitcoin/ResultsData.vue";
import SpecifyUserRounds from "@/Components/Gitcoin/SpecifyUserRounds.vue";
import GptEvaluationButton from "@/Components/Gitcoin/Application/GPTEvaluationButton.vue";

import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
    applicationStatusIcon,
    showDateInShortFormat,
} from "@/utils.js";
import Modal from "@/Components/Modal.vue";

const applications = ref(usePage().props.applications.valueOf());
const selectedApplicationStatus = ref(
    usePage().props.selectedApplicationStatus.valueOf()
);
const selectedApplicationRoundType = ref(
    usePage().props.selectedApplicationRoundType.valueOf()
);

const roundPrompt = (round) => {
    router.visit(route("round.prompt.show", { round: round }));
};

const selectedApplicationStatusRef = ref(selectedApplicationStatus.value);
const selectedApplicationRoundTypeRef = ref(selectedApplicationRoundType.value);

watch(selectedApplicationStatusRef, (newStatus) => {
    // reload the page with the new status added to the query string
    router.visit(
        route("round.application.index", {
            status: newStatus,
        })
    );
});

function updateSelectedRounds() {
    // Refresh applications using ajax
    axios
        .get(route("round.application.index", {}), {
            responseType: "json",
        })
        .then((response) => {
            applications.value = response.data.applications;
        });
}

const openModalId = ref(null);
function toggleModal(applicationId) {
    if (openModalId.value === applicationId) {
        openModalId.value = null; // Close the modal if it's already open
    } else {
        openModalId.value = applicationId; // Open the modal for the clicked project
    }
}

// New state for loading indicator for each applications
const loadingStates = ref({});

// Methods to handle events emitted by the GptEvaluationButton component
const handleEvaluateApplication = async (application) => {
    // Start loading for this specific project
    loadingStates.value[application.id] = true;

    // Here, you might want to adjust depending on what the response looks like and what data you need to update
    try {
        const response = await axios.post(
            route("round.application.chatgpt.list", {
                application: application.uuid,
            })
        );

        // Find the application index in the applications array
        const index = applications.value.data.findIndex(
            (app) => app.id === application.id
        );

        // Assuming response.data.project.applications[0].results[0] contains the updated results you want to insert.
        applications.value.data[index].results.unshift(
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

const handleRoundPrompt = (round) => {
    // This function was already defined in your setup as 'roundPrompt'
    // Just point it to the correct function here to maintain consistency in naming.
    roundPrompt(round);
};

// async function evaluateApplication(event, application) {
//     event.preventDefault();

//     // Start loading for this specific project
//     loadingStates.value[application.id] = true;

//     axios
//         .post(
//             route("round.application.chatgpt.list", {
//                 application: application.uuid,
//             })
//         )
//         .then((response) => {
//             // find the application index in the applications array
//             const index = applications.value.data.findIndex(
//                 (app) => app.id === application.id
//             );

//             applications.value.data[index].results.unshift(
//                 response.data.project.applications[0].results[0]
//             );
//         })
//         .finally(() => {
//             // Stop loading for this specific project
//             delete loadingStates.value[application.id];
//         });
// }
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Applications
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table>
                    <thead>
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
                                        class="fa fa-question-circle-o"
                                        aria-hidden="true"
                                        title="This is the last application date for the round"
                                    ></i>
                                    <template #content>
                                        The status of the application.
                                    </template>
                                </Tooltip>
                            </th>
                            <th>Project</th>
                            <th>
                                <select
                                    v-model="selectedApplicationRoundTypeRef"
                                    class="p-1 mr-1 pr-6"
                                >
                                    <option value="all">All</option>
                                    <option value="mine">My Rounds</option>
                                </select>
                            </th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody v-if="applications && applications.data.length > 0">
                        <tr v-if="selectedApplicationRoundTypeRef == 'mine'">
                            <td colspan="5" class="text-center">
                                <SpecifyUserRounds
                                    @selectedRoundsChanged="
                                        updateSelectedRounds
                                    "
                                />
                            </td>
                        </tr>

                        <tr
                            v-for="(application, index) in applications.data"
                            :key="index"
                        >
                            <td>
                                <span
                                    v-html="
                                        applicationStatusIcon(
                                            application.status
                                        )
                                    "
                                ></span>
                                {{
                                    showDateInShortFormat(
                                        application.created_at
                                    )
                                }}<br />
                                {{
                                    new Date(
                                        application.created_at
                                    ).toLocaleTimeString([], {
                                        hour: "2-digit",
                                        minute: "2-digit",
                                    })
                                }}
                            </td>
                            <td>
                                <Link
                                    v-if="application.project"
                                    :href="
                                        route(
                                            'project.show',
                                            application.project
                                        )
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ application.project.title }}
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route('round.show', application.round)
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ application.round.name }}
                                </Link>
                            </td>
                            <td>
                                <span
                                    v-if="loadingStates[application.id]"
                                    class="ml-2"
                                >
                                    <i class="fa fa-spinner fa-spin"></i>
                                </span>
                                <span v-else>
                                    <span
                                        v-if="
                                            application.results.length > 0 &&
                                            application.latestPrompt
                                        "
                                    >
                                    </span>
                                    <span>
                                        <a
                                            href="#"
                                            class="text-blue-500 hover:underline"
                                            @click="toggleModal(application.id)"
                                        >
                                            <span>
                                                {{
                                                    scoreTotal(
                                                        application.results
                                                    )
                                                }}
                                                <Tooltip
                                                    v-if="
                                                        application.results &&
                                                        application.results
                                                            .length > 0 &&
                                                        application.latestPrompt &&
                                                        application.results[0]
                                                            .prompt_id !==
                                                            application
                                                                .latestPrompt.id
                                                    "
                                                >
                                                    <i
                                                        class="fa fa-exclamation-circle text-red-500"
                                                        aria-hidden="true"
                                                    ></i>
                                                    <template #content>
                                                        This score was
                                                        calculated using an
                                                        older version of the
                                                        scoring criteria.
                                                    </template>
                                                </Tooltip>
                                            </span>
                                        </a>
                                    </span>
                                    <Modal
                                        v-if="
                                            application.results &&
                                            application.results.length > 0
                                        "
                                        :show="openModalId === application.id"
                                        @close="toggleModal(application.id)"
                                    >
                                        <div class="modal-content">
                                            <h2 class="modal-title">
                                                Score Details for
                                                {{ application.project.title }}
                                            </h2>

                                            <ResultsData
                                                :result="
                                                    application.project
                                                        .results[0]
                                                "
                                            />
                                        </div>
                                    </Modal>
                                </span>
                            </td>
                            <td>
                                <GptEvaluationButton
                                    :application="application"
                                    :loadingStates="loadingStates"
                                    @evaluate-application="
                                        handleEvaluateApplication
                                    "
                                    @round-prompt="handleRoundPrompt"
                                ></GptEvaluationButton>
                            </td>
                        </tr>
                    </tbody>
                    <tbody
                        v-else-if="selectedApplicationRoundTypeRef == 'mine'"
                    >
                        <tr>
                            <td colspan="5" class="text-center">
                                <SpecifyUserRounds />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination :links="applications.links" />
            </div>
        </div>
    </AppLayout>
</template>
