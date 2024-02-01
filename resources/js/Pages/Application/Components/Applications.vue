<script setup>
import { ref, watch, defineEmits } from "vue";
import { usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
    applicationStatusIcon,
    showDateInShortFormat,
} from "@/utils.js";
import Modal from "@/Components/Modal.vue";

import { defineProps } from "vue";

// Define the props the component accepts
const props = defineProps({
    applications: {
        type: Object,
        default: () => ({}),
    },
});

const averageGPTEvaluationTime = usePage().props.averageGPTEvaluationTime;
</script>

<template>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col">
            <div v-if="props?.applications?.data?.length > 0">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Chain</th>
                            <th>Round</th>
                            <th>Application</th>
                            <th>Project</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(application, index) in applications.data"
                            :key="index"
                            class="border-b mb-10"
                        >
                            <td>
                                {{
                                    showDateInShortFormat(
                                        application.created_at,
                                        true
                                    )
                                }}
                            </td>
                            <td>
                                {{ application.round.chain.name }}
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route('round.show', {
                                            round: application.round.uuid,
                                        })
                                    "
                                    class="text-blue-500 hover:underline mr-1"
                                >
                                    {{ application.round.name }}
                                </Link>
                            </td>
                            <td>{{ application.application_id }}</td>
                            <td>
                                <Link
                                    v-if="application.project"
                                    :href="
                                        route('project.show', {
                                            project: application.project.slug,
                                        })
                                    "
                                    class="text-blue-500 hover:underline mr-1"
                                >
                                    {{ application.project.title }}
                                </Link>
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
                                    {{ application.status.toLowerCase() }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination :links="applications.links" />
            </div>
            <div v-else class="pt-5">No results for your selected filter.</div>
        </div>
    </div>
</template>
