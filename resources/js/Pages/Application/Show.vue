<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Evaluation from "@/Pages/Application/Components/Evaluation.vue";
import ResultsSummary from "@/Pages/Application/Components/ResultsSummary.vue";
import { useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import ApplicationAnswers from "@/Components/Gitcoin/Application/ApplicationAnswers.vue";

import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
    showDateInShortFormat,
    applicationStatusIcon,
    formatDate,
} from "@/utils.js";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Applications from "@/Pages/Application/Components/Applications.vue";

const page = usePage();

const application = ref(usePage().props.application.valueOf());
const round = ref(usePage().props.round.valueOf());

const queryParams = new URLSearchParams(window.location.search);

const latestPrompt = ref(
    usePage().props.latestPrompt ? usePage().props.latestPrompt.valueOf() : null
);

const openModalId = ref(null);
function toggleModal(projectId) {
    if (openModalId.value === projectId) {
        openModalId.value = null; // Close the modal if it's already open
    } else {
        openModalId.value = projectId; // Open the modal for the clicked project
    }
}

const averageGPTEvaluationTime = usePage().props.averageGPTEvaluationTime;

const form = useForm([]);

const isLoading = ref(false);

// New state for loading indicator for each project
const loadingStates = ref({});

const roundPrompt = () => {
    router.visit(route("round.prompt.show", { round: round.value.uuid }));
};

async function evaluateApplication(event, application) {
    event.preventDefault();

    // Start loading for this specific project
    loadingStates.value[application.id] = true;

    axios
        .post(
            route("round.application.chatgpt.list", {
                application: application,
            })
        )
        .then((response) => {
            const page = response.data;
            // Find the project in the projects array using its ID
            const projectIndex = projects.value.data.findIndex((p) => {
                return p.id_addr === page.project.id_addr;
            });

            // Update the specific project with the new data from the response
            if (projectIndex !== -1) {
                projects.value.data[projectIndex] = page.project;
            }
        })
        .finally(() => {
            // Stop loading for this specific project
            delete loadingStates.value[application.id];
        });
}

const previousApplicationSummary = (application) => {
    let summary = {
        approved: [],
        rejected: [],
    };

    if (application.project.applications) {
        const previousApplications = application.project.applications.filter(
            (a) => {
                return a.id !== application.id;
            }
        );

        if (previousApplications) {
            Array.isArray(previousApplications) &&
                previousApplications.forEach((a) => {
                    if (a.status === "APPROVED") {
                        summary.approved.push(a);
                    } else if (a.status === "REJECTED") {
                        summary.rejected.push(a);
                    }
                });
        }
    }

    return summary;
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

        refreshApplication(application);
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
            route("api.round.application.show", {
                application: application.uuid,
            })
        );
        Object.assign(application, response.data.application);
    } catch (error) {
        // Handle error properly, maybe set an error message to display in the UI
        console.error("An error occurred:", error);
    } finally {
        // Stop loading for this specific project
        delete loadingStates.value[application.id];
    }
};
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        {{ round.name }} on {{ round.chain.name }}
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
                </div>
                <Link
                    :href="route('round.evaluation.show', round)"
                    class="text-blue-500 hover:underline"
                >
                    Round Evaluation Criteria
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-3 py-3"
            >
                <div
                    class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex justify-between"
                >
                    <div>
                        <div class="text-xl mr-6">
                            Match amount: ${{ round.match_amount_in_usd }}
                        </div>
                        <div class="text-sm">
                            Applications start:
                            {{
                                showDateInShortFormat(
                                    round.applications_start_time,
                                    true
                                )
                            }}
                            <br />
                            Applications end:
                            {{
                                showDateInShortFormat(
                                    round.applications_end_time,
                                    true
                                )
                            }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xl mr-6">
                            Unique donors: {{ round.unique_donors_count }}
                        </div>
                        <div class="text-sm">
                            Donations start:
                            {{
                                showDateInShortFormat(
                                    round.donations_start_time,
                                    true
                                )
                            }}
                            <br />
                            Donations end:
                            {{
                                showDateInShortFormat(
                                    round.donations_end_time,
                                    true
                                )
                            }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
                    <div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Project</th>
                                    <th>Prior approvals</th>
                                    <th>Results</th>
                                    <th>Manager</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        {{
                                            showDateInShortFormat(
                                                application.created_at
                                            )
                                        }}
                                    </td>
                                    <td>
                                        <span>
                                            <span
                                                v-html="
                                                    applicationStatusIcon(
                                                        application.status
                                                    )
                                                "
                                                class="mr-1"
                                            ></span>
                                            {{
                                                application.status.toLowerCase()
                                            }}
                                        </span>
                                    </td>
                                    <td>
                                        <Link
                                            v-if="application.project"
                                            :href="
                                                route('project.show', {
                                                    project:
                                                        application.project
                                                            .slug,
                                                })
                                            "
                                            class="text-blue-500 hover:underline mr-1"
                                        >
                                            {{ application.project.title }}
                                        </Link>

                                        <ApplicationAnswers
                                            :applicationUuid="application.uuid"
                                        />
                                    </td>
                                    <td>
                                        <div
                                            v-if="
                                                previousApplicationSummary(
                                                    application
                                                ).approved.length > 0
                                            "
                                        >
                                            Approved in:
                                            <div
                                                v-for="(
                                                    approvedApplication, index
                                                ) in previousApplicationSummary(
                                                    application
                                                ).approved"
                                                :key="index"
                                            >
                                                <Link
                                                    :href="
                                                        route('round.show', {
                                                            round: approvedApplication
                                                                .round.uuid,
                                                        })
                                                    "
                                                    class="text-blue-500 hover:underline"
                                                >
                                                    {{
                                                        approvedApplication
                                                            .round.name
                                                    }}
                                                </Link>
                                            </div>
                                        </div>
                                        <div v-else>No</div>
                                    </td>
                                    <td>
                                        <Evaluation
                                            :application="application"
                                            @perform-gpt-evaluation="
                                                handleEvaluateApplication
                                            "
                                            @user-evaluation-updated="
                                                refreshApplication
                                            "
                                            :loadingBarInSeconds="
                                                averageGPTEvaluationTime
                                            "
                                        />
                                        <div class="mt-2 flex justify-center">
                                            <ResultsSummary
                                                :application="application"
                                            />
                                        </div>
                                    </td>
                                    <td>
                                        <a
                                            :href="
                                                'https://manager.gitcoin.co/#/round/' +
                                                round.round_addr.toLowerCase() +
                                                '/application/' +
                                                round.round_addr.toLowerCase() +
                                                '-' +
                                                application.application_id.toLowerCase()
                                            "
                                            target="_blank"
                                            class="text-blue-500 underline"
                                        >
                                            <i
                                                class="fa fa-external-link"
                                                aria-hidden="true"
                                            ></i>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
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
