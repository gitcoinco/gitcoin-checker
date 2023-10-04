<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { copyToClipboard, shortenAddress } from "@/utils.js";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

const round = ref(usePage().props.round.valueOf());
const projects = ref(usePage().props.projects.valueOf());

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

function scoreTotal(results) {
    if (results && results.length > 0) {
        let resultsData = results[0].results_data;

        let total = 0;

        // Try to parse resultsData into a json object
        try {
            resultsData = JSON.parse(resultsData);

            // Check if resultsData is an array and has items
            if (!Array.isArray(resultsData) || resultsData.length === 0) {
                return "n/a";
            }
        } catch (error) {
            return resultsData; // Return "n/a" or any other appropriate value in case of a parsing error
        }

        // iterate over each result
        let counter = 0;
        for (let result of resultsData) {
            // Check if result has a score property and it's a number
            if (result && typeof result.score === "number") {
                // add the score to the total
                total += result.score;
                counter++;
            }
        }

        // Check if counter is not zero to avoid division by zero
        if (counter === 0) {
            return "n/a";
        }

        total = total / counter;
        // set total to a max of 1 decimal
        total = total.toFixed(1);
        return total + "%";
    } else {
        return "n/a";
    }
}
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <h2 class="text-xl">Projects</h2>

                <table v-if="projects && projects.data.length > 0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Website</th>
                            <th>Twitter</th>
                            <th>Github</th>
                            <th>Score</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(project, index) in projects.data"
                            :key="index"
                        >
                            <td>
                                <Link :href="route('project.show', project.id)">
                                    {{ project.title }}
                                    >

                                    {{ project.title }}
                                </Link>
                            </td>
                            <td>
                                <a
                                    href="{{ project.website }}"
                                    _target="_blank"
                                >
                                    {{
                                        project.website.replace("https://", "")
                                    }}
                                </a>
                            </td>
                            <td>
                                {{ project.projectTwitter }}
                            </td>
                            <td>
                                {{ project.userGithub }}
                            </td>
                            <td>
                                <span
                                    v-if="
                                        loadingStates[
                                            project.applications[0].id
                                        ]
                                    "
                                    class="ml-2"
                                >
                                    <i class="fa fa-spinner fa-spin"></i>
                                </span>
                                <span v-else>
                                    <span>
                                        <a
                                            href="
                                        #"
                                            class="text-blue-500 hover:underline"
                                            @click="toggleModal(project.id)"
                                        >
                                            {{
                                                scoreTotal(
                                                    project.applications[0]
                                                        .results
                                                )
                                            }}
                                        </a>
                                    </span>
                                    <Modal
                                        :show="openModalId === project.id"
                                        @close="toggleModal(project.id)"
                                    >
                                        <div class="modal-content">
                                            <h2 class="modal-title">
                                                Score Details for
                                                {{ project.title }}
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
                                                            project
                                                                .applications[0]
                                                                .results[0]
                                                                .results_data
                                                        )"
                                                        :key="
                                                            'modal' +
                                                            project.id +
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
                                <Link
                                    :href="route('project.show', project.id)"
                                    class="text-blue-500 hover:underline"
                                >
                                    View
                                </Link>
                            </td>
                            <td>
                                <a
                                    @click="
                                        evaluateApplication(
                                            $event,
                                            project.applications[0]
                                        )
                                    "
                                    href="#"
                                    class="text-blue-500 hover:underline"
                                    :disabled="
                                        loadingStates[
                                            project.applications[0].id
                                        ]
                                    "
                                >
                                    Evaluate
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination :links="projects.links" />
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
