<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { formatDate } from "@/utils.js";
import Tooltip from "@/Components/Tooltip.vue";

const project = ref(usePage().props.project.valueOf());
const applications = ref(usePage().props.applications.valueOf());
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ project.title }}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-5 sm:px-6 lg:px-8">
                <h2 class="text-xl">Project Details</h2>
                <div>
                    <div>
                        {{ project.title }}
                    </div>
                    <div v-if="project.website">
                        Website: {{ project.website }}
                    </div>
                    <div v-if="project.metadata.projectTwitter">
                        Twitter: {{ project.metadata.projectTwitter }}
                    </div>
                    <div v-if="project.metadata.userGithub">
                        Github: {{ project.metadata.userGithub }}
                    </div>
                    <div v-if="project.metadata.description" class="text-xs">
                        {{ project.metadata.description }}
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="max-w-7xl mx-auto py-5 sm:px-6 lg:px-8">
                <table v-if="applications && applications.data.length > 0">
                    <thead>
                        <tr>
                            <th>Start</th>
                            <th>
                                Round<span v-if="applications.data.length > 1"
                                    >s</span
                                >
                            </th>
                            <th>Eligibility</th>
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
                                    formatDate(
                                        application.round.round_start_time
                                    )
                                }}
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
                                <div
                                    class="mb-2"
                                    v-if="
                                        application.round.metadata.eligibility
                                    "
                                >
                                    <strong>Description:</strong><br />

                                    <div class="text-xs">
                                        {{
                                            application.round.metadata
                                                .eligibility.description
                                        }}
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        application.round.metadata.requirements
                                    "
                                >
                                    <strong>Requirements:</strong><br />
                                    <div
                                        v-for="requirement in application.round
                                            .metadata.eligibility.requirements"
                                        :key="requirement"
                                        class="text-xs"
                                    >
                                        {{ requirement.requirement }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route(
                                            'round.application.evaluate',
                                            application.id
                                        )
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    Generated Prompt
                                </Link>
                                <Tooltip>
                                    <i
                                        class="fa fa-question-circle-o"
                                        aria-hidden="true"
                                        title="This is the last application date for the round"
                                    ></i>
                                    <template #content>
                                        The criteria that will be passed on to
                                        ChatGPT for an evaluation.
                                    </template>
                                </Tooltip>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
