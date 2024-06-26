<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import GptEvaluationResults from "@/Components/Gitcoin/Application/GptEvaluationResults.vue";
import HumanEvaluationResults from "@/Components/Gitcoin/Application/UserEvaluationResults.vue";

const pinataUrl = import.meta.env.VITE_PINATA_CLOUDFRONT_URL;

import {
    formatDate,
    shortenAddress,
    copyToClipboard,
    applicationStatusIcon,
} from "@/utils.js";
import Tooltip from "@/Components/Tooltip.vue";
import MarkdownIt from "markdown-it";
const markdown = new MarkdownIt();

const project = ref(usePage().props.project.valueOf());
const applications = ref(usePage().props.applications.valueOf());
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ project.title }}
                <span class="text-sm">
                    {{ shortenAddress(project.id_addr) }}

                    <span
                        @click="copyToClipboard(project.id_addr)"
                        class="cursor-pointer"
                    >
                        <i class="fa fa-clone" aria-hidden="true"></i>
                    </span>
                </span>
            </h2>
        </template>

        <div class="py-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div>
                    <div class="max-w-7xl mx-auto py-5 sm:px-6 lg:px-8">
                        <div
                            v-if="project.logoImg"
                            class="mb-5 p-5 bg-gray-300"
                            :style="{
                                backgroundPosition: 'center',
                                backgroundRepeat: 'no-repeat',
                                backgroundImage:
                                    'url(' +
                                    pinataUrl +
                                    '/' +
                                    project.bannerImg +
                                    '?img-width=1000' +
                                    ')',
                            }"
                        >
                            <img
                                :src="
                                    pinataUrl +
                                    '/' +
                                    project.logoImg +
                                    '?img-height=150'
                                "
                                class="mx-auto rounded-full"
                                style="
                                    height: 150px;
                                    height: 150px;
                                    object-fit: cover;
                                "
                            />
                        </div>

                        <div>
                            <div v-if="project.website">
                                <i
                                    class="fa fa-globe mr-2"
                                    aria-hidden="true"
                                ></i>
                                <a
                                    :href="project.website"
                                    target="_blank"
                                    class="text-blue-500"
                                >
                                    {{ project.website }}
                                </a>
                            </div>
                            <div v-if="project.metadata.projectTwitter">
                                <i
                                    class="fa fa-twitter text-blue-500 mr-2"
                                    aria-hidden="true"
                                ></i>
                                <a
                                    :href="
                                        'https://twitter.com/' +
                                        project.metadata.projectTwitter
                                    "
                                    target="_blank"
                                    class="text-blue-500"
                                >
                                    {{ project.metadata.projectTwitter }}
                                </a>
                            </div>
                            <div v-if="project.metadata.projectGithub">
                                <i
                                    class="fa fa-github mr-2"
                                    aria-hidden="true"
                                ></i>
                                <a
                                    :href="
                                        'https://github.com/' +
                                        project.metadata.projectGithub
                                    "
                                    target="_blank"
                                    class="text-blue-500"
                                >
                                    {{ project.metadata.projectGithub }}
                                    (Project)
                                </a>
                            </div>
                            <div v-if="project.metadata.userGithub">
                                <i
                                    class="fa fa-github mr-2"
                                    aria-hidden="true"
                                ></i>
                                <a
                                    :href="
                                        'https://github.com/' +
                                        project.metadata.userGithub
                                    "
                                    target="_blank"
                                    class="text-blue-500"
                                >
                                    {{ project.metadata.userGithub }} (User)
                                </a>
                            </div>
                            <div
                                v-if="project.description"
                                class="text-xs mt-5 markdown"
                                v-html="markdown.render(project.description)"
                            ></div>
                        </div>
                    </div>
                </div>

                <div>
                    <div
                        class="max-w-7xl mx-auto py-5 sm:px-6 lg:px-8"
                        v-if="applications && applications.data.length > 0"
                    >
                        <div class="mb-3">
                            Total project funding: ${{
                                project.project_donations_sum_amount_usd
                            }}<br />
                            <div class="italic text-gray-500 text-xs">
                                Under development: Estimate
                            </div>
                        </div>

                        <h2 class="text-xl">All Applications</h2>

                        <table class="text-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>
                                        Date

                                        <Tooltip>
                                            <i
                                                class="fa fa-question-circle-o"
                                                aria-hidden="true"
                                                title="This is the last application date for the round"
                                            ></i>
                                            <template #content>
                                                Date the application was
                                                received.
                                            </template>
                                        </Tooltip>
                                    </th>

                                    <th>
                                        Application<span
                                            v-if="applications.data.length > 1"
                                            >s</span
                                        >
                                    </th>
                                    <th>Eligibility</th>
                                    <th>Prompt</th>
                                    <th>User Funding</th>
                                    <!-- <th>Evaluation</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        application, index
                                    ) in applications.data"
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
                                    </td>
                                    <td>
                                        {{
                                            new Date(
                                                application.created_at
                                            ).toLocaleDateString()
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
                                            :href="
                                                route(
                                                    'application.show',
                                                    application
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
                                                application.round.round_metadata
                                                    .eligibility
                                            "
                                        >
                                            <strong>Description:</strong><br />

                                            <div class="text-xs">
                                                {{
                                                    application.round
                                                        .round_metadata
                                                        .eligibility.description
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                application.round.round_metadata
                                                    .requirements
                                            "
                                        >
                                            <strong>Requirements:</strong><br />
                                            <div
                                                v-for="requirement in application
                                                    .round.round_metadata
                                                    .eligibility.requirements"
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
                                                    application.uuid
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
                                                The criteria that will be passed
                                                on to ChatGPT for an evaluation.
                                            </template>
                                        </Tooltip>
                                    </td>

                                    <td>
                                        <span
                                            v-if="
                                                application.application_donations_sum_amount_usd
                                            "
                                        >
                                            ${{
                                                application.application_donations_sum_amount_usd
                                            }}
                                        </span>
                                        <div
                                            class="italic text-gray-500 text-xs"
                                        >
                                            Under development: Estimate
                                        </div>
                                    </td>

                                    <!-- <td>
                                <GptEvaluationResults
                                    :application="application"
                                    class="mb-2"
                                >
                                </GptEvaluationResults>
                                <HumanEvaluationResults
                                    :application="application"
                                >
                                </HumanEvaluationResults>
                            </td> -->
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
