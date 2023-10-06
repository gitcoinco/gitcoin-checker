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
    router.visit(route("round.prompt.show", { round: round.value.id }));
};

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
                    :href="route('round.evaluate.all.show', round.id)"
                    class="text-blue-500 hover:underline"
                >
                    Evaluate Entire Round
                </Link>
            </div>
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
                            <th class="nowrap">Score</th>
                            <th></th>
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
                            <td class="nowrap">
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
                                        <span>
                                            <a
                                                href="#"
                                                class="text-blue-500 hover:underline"
                                                @click="toggleModal(project.id)"
                                            >
                                                <span>
                                                    {{
                                                        scoreTotal(
                                                            project
                                                                .applications[0]
                                                                .results
                                                        )
                                                    }}
                                                    <Tooltip
                                                        v-if="
                                                            project
                                                                .applications[0]
                                                                .results &&
                                                            project
                                                                .applications[0]
                                                                .results
                                                                .length > 0 &&
                                                            latestPrompt &&
                                                            project
                                                                .applications[0]
                                                                .results[0]
                                                                .prompt_id !==
                                                                latestPrompt.id
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
                                                            <td
                                                                class="score-value"
                                                            >
                                                                {{
                                                                    result.score
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    result.criteria
                                                                }}
                                                            </td>
                                                            <td>
                                                                {{
                                                                    result.reason
                                                                }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </Modal>
                                    </span>
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
                            <td></td>
                            <td>
                                <template v-if="latestPrompt">
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
                                                @click="roundPrompt"
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
