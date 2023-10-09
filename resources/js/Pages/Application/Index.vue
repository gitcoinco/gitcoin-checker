<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
    applicationStatusIcon,
} from "@/utils.js";
import Modal from "@/Components/Modal.vue";

const applications = ref(usePage().props.applications.valueOf());

const roundPrompt = (round) => {
    router.visit(route("round.prompt.show", { round: round.value.id }));
};

const openModalId = ref(null);
function toggleModal(applicationId) {
    if (openModalId.value === applicationId) {
        openModalId.value = null; // Close the modal if it's already open
    } else {
        openModalId.value = applicationId; // Open the modal for the clicked project
    }
}

function showDateInShortFormat(date) {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

// New state for loading indicator for each applications
const loadingStates = ref({});

async function evaluateApplication(event, application) {
    event.preventDefault();

    // Start loading for this specific project
    loadingStates.value[application.id] = true;

    axios
        .post(
            route("round.application.chatgpt.list", {
                application: application.id,
            })
        )
        .then((response) => {
            // find the application index in the applications array
            const index = applications.value.data.findIndex(
                (app) => app.id === application.id
            );

            applications.value.data[index].results.unshift(
                response.data.project.applications[0].results[0]
            );

            console.log(applications.value.data[index].results);
        })
        .finally(() => {
            // Stop loading for this specific project
            delete loadingStates.value[application.id];
        });
}
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
                <table v-if="applications && applications.data.length > 0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Project</th>
                            <th>Round</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(application, index) in applications.data"
                            :key="index"
                        >
                            <td>
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
                                <span
                                    v-html="
                                        applicationStatusIcon(
                                            application.status
                                        )
                                    "
                                ></span>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route(
                                            'project.show',
                                            application.project.id
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
                                        route(
                                            'round.show',
                                            application.round.id
                                        )
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
                                                        v-for="(
                                                            result, index
                                                        ) in JSON.parse(
                                                            application
                                                                .results[0]
                                                                .results_data
                                                        )"
                                                        :key="
                                                            'modal' +
                                                            application.project
                                                                .id +
                                                            '-' +
                                                            index
                                                        "
                                                    >
                                                        <td class="score-value">
                                                            {{ result.score }}
                                                        </td>
                                                        <td>
                                                            {{
                                                                result.criteria
                                                            }}
                                                        </td>
                                                        <td>
                                                            {{ result.reason }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </Modal>
                                </span>
                            </td>
                            <td>
                                <template v-if="application.latestPrompt">
                                    <span
                                        v-if="
                                            (application.results &&
                                                application.results.length ===
                                                    0) ||
                                            (application.results.length > 0 &&
                                                application.results[0]
                                                    .prompt_id !==
                                                    application.latestPrompt.id)
                                        "
                                    >
                                        <a
                                            @click="
                                                evaluateApplication(
                                                    $event,
                                                    application
                                                )
                                            "
                                            href="#"
                                            class="text-blue-500 hover:underline"
                                            :disabled="
                                                loadingStates[application.id]
                                            "
                                        >
                                            Evaluate
                                        </a>
                                    </span>
                                </template>
                                <template v-else>
                                    <Tooltip>
                                        <i
                                            class="fa fa-exclamation-circle text-red-500"
                                            aria-hidden="true"
                                        ></i>
                                        <template #content>
                                            Cannot evaluate if a prompt is not
                                            set for this round.<br /><br />
                                            <SecondaryButton
                                                @click="
                                                    roundPrompt(
                                                        application.round
                                                    )
                                                "
                                                class="text-blue-500 hover:underline"
                                            >
                                                Set Evaluation Criteria
                                            </SecondaryButton>
                                        </template>
                                    </Tooltip>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination :links="applications.links" />
            </div>
        </div>
    </AppLayout>
</template>
