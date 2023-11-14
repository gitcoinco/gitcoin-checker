<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
} from "@/utils.js";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Applications from "@/Pages/Application/Components/Applications.vue";

const round = ref(usePage().props.round.valueOf());
const applications = ref(usePage().props.applications.valueOf());
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
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        {{ round.name }}
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

        <div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Applications
                    :displayFilter="false"
                    :applications="applications"
                    @status-changed="statusChanged"
                    @remove-tests="removeTests"
                    @round-type="roundType"
                    @refresh-applications="refreshApplications"
                    @user-rounds-changed="refreshApplications"
                    @search-projects="searchProjects"
                />
            </div>
        </div>
    </AppLayout>
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
