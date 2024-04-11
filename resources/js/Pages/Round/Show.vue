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

const round = ref(usePage().props.round.valueOf());
const applications = ref(usePage().props.applications.valueOf());
const pinataUrl = usePage().props.pinataUrl;
const isRoundManager = usePage().props.isRoundManager;

const queryParams = new URLSearchParams(window.location.search);
const status = ref(queryParams.get("status") || "all");

watch(status, (newStatus) => {
    router.visit(
        route("round.show", {
            round: round.value.uuid,
            status: newStatus,
        })
    );
});
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

const searchProjects = (newStatus) => {
    router.visit(
        route("round.show", {
            round: round.uuid,
            selectedSearchProjects: newStatus,
        })
    );
};

const removeTests = (newStatus) => {
    router.visit(
        route("round.show", {
            round: round.uuid,
            selectedApplicationRemoveTests: newStatus,
        })
    );
};

const statusChanged = (newStatus) => {
    router.visit(
        route("round.show", {
            round: round.uuid,
            status: newStatus,
        })
    );
};

const roundType = (newStatus) => {
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
            refreshApplications();
        });
};

function refreshApplications() {
    axios
        .get(route("round.show", {}), {
            responseType: "json",
        })
        .then((response) => {
            applications.value = response.data.applications;
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

const refreshApplication = async (application) => {
    try {
        const response = await axios.get(
            route("api.round.application.show", {
                application: application.uuid,
            })
        );

        // Find the application index in the applications array
        const index = applications.value.data.findIndex(
            (app) => app.id === application.id
        );
        applications.value.data[index] = response.data.application;
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
                        <span
                            class="text-sm"
                            v-if="round.round_addr.length > 10"
                        >
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
                <div v-if="isRoundManager">
                    <Link
                        :href="route('round.roles.show', round)"
                        class="text-blue-500 hover:underline"
                    >
                        Users
                    </Link>
                    |

                    <Link
                        :href="route('round.evaluation.show', round)"
                        class="text-blue-500 hover:underline"
                    >
                        Round Evaluation Criteria
                    </Link>
                </div>
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

                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-3">
                    <div
                        class="mb-3"
                        v-if="
                            JSON.parse(round.round_metadata)?.eligibility
                                ?.description
                        "
                    >
                        <div class="text-xl mr-6">Eligibility description:</div>
                        <div class="text-xs">
                            {{
                                JSON.parse(round.round_metadata).eligibility
                                    .description
                            }}
                        </div>
                    </div>
                    <div
                        v-if="
                            JSON.parse(round.round_metadata)?.eligibility
                                ?.requirements.length > 0
                        "
                    >
                        <div class="text-xl mr-6">
                            Eligibility requirements:
                        </div>
                        <div class="text-xs">
                            {{
                                JSON.parse(round.round_metadata)
                                    .eligibility.requirements.map(
                                        (requirement) =>
                                            "- " + requirement.requirement
                                    )
                                    .join(", ")
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
                                    <th>Project</th>
                                    <th>
                                        <select v-model="status">
                                            <option value="all">All</option>
                                            <option value="pending">
                                                Pending
                                            </option>
                                            <option value="approved">
                                                Approved
                                            </option>
                                            <option value="rejected">
                                                Rejected
                                            </option>
                                        </select>
                                    </th>
                                    <th>Prior approvals</th>
                                    <th>Results</th>
                                    <th>Manager</th>
                                </tr>
                            </thead>
                            <tbody v-if="applications.data.length > 0">
                                <tr
                                    v-for="(
                                        application, index
                                    ) in applications.data"
                                    :key="index"
                                >
                                    <td>
                                        <Link
                                            :href="
                                                route('application.show', {
                                                    application:
                                                        application.uuid,
                                                })
                                            "
                                            class="text-blue-500 hover:underline"
                                        >
                                            {{
                                                showDateInShortFormat(
                                                    application.created_at
                                                )
                                            }}
                                        </Link>
                                    </td>
                                    <td>
                                        <div v-if="application.project"></div>
                                        <div class="flex">
                                            <Link
                                                :href="
                                                    route('project.show', {
                                                        project:
                                                            application.project
                                                                .slug,
                                                    })
                                                "
                                                class="text-blue-500 hover:underline mr-1"
                                            >
                                                <img
                                                    :src="
                                                        application.project
                                                            .logoImg
                                                            ? pinataUrl +
                                                              '/' +
                                                              application
                                                                  .project
                                                                  .logoImg +
                                                              '?img-width=42'
                                                            : '/img/placeholder.png'
                                                    "
                                                    onerror="this.onerror=null; this.src='/img/placeholder.png';"
                                                    style="
                                                        width: 42px;
                                                        height: 42px;
                                                    "
                                                    class="rounded-full mr-1"
                                                />
                                            </Link>
                                            <div>
                                                <div>
                                                    <Link
                                                        :href="
                                                            route(
                                                                'project.show',
                                                                {
                                                                    project:
                                                                        application
                                                                            .project
                                                                            .slug,
                                                                }
                                                            )
                                                        "
                                                        class="text-blue-500 hover:underline mr-1"
                                                    >
                                                        {{
                                                            application.project
                                                                .title
                                                        }}
                                                    </Link>
                                                </div>
                                                <ApplicationAnswers
                                                    :applicationUuid="
                                                        application.uuid
                                                    "
                                                >
                                                    <span class="text-xs">
                                                        Application answers
                                                    </span>
                                                </ApplicationAnswers>
                                            </div>
                                        </div>
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
                                            v-if="
                                                application.status === 'PENDING'
                                            "
                                        />
                                        <div class="mt-2 flex justify-center">
                                            <ResultsSummary
                                                :application="application"
                                            />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-xl">
                                            <a
                                                :href="
                                                    'https://manager.gitcoin.co/#/round/' +
                                                    round.round_addr.toLowerCase() +
                                                    '/application/' +
                                                    application.application_id.toLowerCase()
                                                "
                                                target="_blank"
                                                title="View application in Gitcoin Manager"
                                                class="text-blue-500 underline mr-2"
                                            >
                                                <i
                                                    class="fa fa-external-link"
                                                    aria-hidden="true"
                                                ></i>
                                            </a>

                                            <a
                                                :href="
                                                    route('application.show', {
                                                        application:
                                                            application.uuid,
                                                    })
                                                "
                                                target="_blank"
                                                title="View application"
                                                class="text-blue-500 underline"
                                            >
                                                <i
                                                    class="fa fa-share-square-o"
                                                    aria-hidden="true"
                                                ></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6">No results</td>
                                </tr>
                            </tbody>
                        </table>

                        <Pagination
                            :links="applications.links"
                            @pagination-changed="applications = $event"
                        />
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
