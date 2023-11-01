<script setup>
import { ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import PreviousApplicationStatus from "@/Components/Gitcoin/Application/PreviousApplicationStatus.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import SpecifyUserRounds from "@/Components/Gitcoin/SpecifyUserRounds.vue";
import GptEvaluationButton from "@/Components/Gitcoin/Application/GPTEvaluationButton.vue";
import UserEvaluationButton from "@/Components/Gitcoin/Application/UserEvaluationButton.vue";
import GptEvaluationResults from "@/Components/Gitcoin/Application/GptEvaluationResults.vue";
import UserEvaluationResults from "@/Components/Gitcoin/Application/UserEvaluationResults.vue";
import ApplicationAnswers from "@/Components/Gitcoin/Application/ApplicationAnswers.vue";

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

watch(selectedApplicationRoundTypeRef, (newStatus) => {
    // Refresh applications using ajax
    axios
        .get(
            route("user-preferences.rounds.selectedApplicationRoundType", {
                selectedApplicationRoundType: newStatus,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            updateSelectedRounds();
        });
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

// New state for loading indicator for each applications
const loadingStates = ref({});

const handleUserEvaluateApplication = async (application) => {
    updateSelectedRounds();
};

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
                            <th class="whitespace-nowrap">History</th>
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
                                    class="text-blue-500 hover:underline mr-2"
                                >
                                    {{ application.project.title }}
                                </Link>

                                <ApplicationAnswers
                                    :application="application"
                                />
                            </td>
                            <td class="whitespace-nowrap">
                                <PreviousApplicationStatus
                                    :application="application"
                                />
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
                                <GptEvaluationResults
                                    :application="application"
                                    :loading-states="loadingStates"
                                    class="mb-2"
                                >
                                </GptEvaluationResults>
                                <UserEvaluationResults
                                    :application="application"
                                >
                                </UserEvaluationResults>
                            </td>
                            <td>
                                <GptEvaluationButton
                                    :application="application"
                                    :loadingStates="loadingStates"
                                    @evaluate-application="
                                        handleEvaluateApplication
                                    "
                                    @round-prompt="handleRoundPrompt"
                                    class="mb-2"
                                ></GptEvaluationButton>
                                <UserEvaluationButton
                                    :application="application"
                                    @evaluated-application="
                                        handleUserEvaluateApplication
                                    "
                                ></UserEvaluationButton>
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
