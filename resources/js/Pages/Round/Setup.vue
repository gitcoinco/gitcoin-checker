<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm, usePage, Link, router } from "@inertiajs/vue3";
import { copyToClipboard, shortenAddress } from "@/utils.js";

import axios from "axios";

const page = usePage();

const round = ref(usePage().props.round.valueOf());
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
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-3 p-5"
            >
                <div
                    class="max-w-7xl mx-auto p-6 bg-white rounded-lg shadow-md"
                >
                    <div class="mb-5">
                        Prompt testing - still under development
                    </div>

                    <div class="mb-5">
                        Gitcoin runs rounds to fund public goods. Each round has
                        a set of eligibility criteria that applicants must meet
                        in order to be included in the round. The round also has
                        a set of application questions that applicants must
                        answer to apply for funding. Evaluate the eligibility
                        criteria and the application questions and highlight
                        where there is a mismatch between the two, for the
                        {{ round.name }} round.
                    </div>

                    <h2 class="text-2xl font-bold text-gray-700">
                        Eligibility criteria
                    </h2>

                    <p class="mt-2 text-gray-600">
                        {{
                            JSON.parse(round.round_metadata).eligibility
                                .description
                        }}
                    </p>
                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="(requirement, index) in JSON.parse(
                                round.round_metadata
                            ).eligibility.requirements"
                            :key="index"
                        >
                            {{ requirement.requirement }}
                        </li>
                    </ul>

                    <h2 class="mt-6 text-2xl font-bold text-gray-700">
                        Application questions
                    </h2>

                    <ul class="mt-4 space-y-2">
                        <li
                            v-for="(questions, index) in JSON.parse(
                                round.application_metadata
                            ).applicationSchema.questions"
                            :key="index"
                        >
                            {{ questions.title }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
