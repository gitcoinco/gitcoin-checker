<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, useForm, usePage, Link, router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import Tooltip from "@/Components/Tooltip.vue";
import {
    copyToClipboard,
    shortenAddress,
    scoreTotal,
    shortenURL,
} from "@/utils.js";

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
                        {{ round.name }}
                        <span class="text-sm" v-if="round?.chain?.name"
                            >on {{ round.chain.name }}</span
                        >

                        <span class="ml-2 text-sm">
                            (
                            {{ shortenAddress(round.round_addr) }}
                            )
                            <span
                                @click="copyToClipboard(round.round_addr)"
                                class="cursor-pointer"
                            >
                                <i class="fa fa-clone" aria-hidden="true"></i>
                            </span>
                        </span>
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight mb-10"
                    >
                        Set evaluation criteria for the {{ round.name }} round
                    </h2>
                    <div class="mb-5">
                        <Link
                            :href="route('round.prompt.show', round)"
                            class="text-blue-500 hover:underline"
                        >
                            <i class="fa fa-server mr-1" aria-hidden="true"></i>
                            ChatGPT Evaluation Criteria
                        </Link>
                        <p>
                            By default this has a standard set of evaluation
                            criteria inherited by the evaluation criteria of the
                            round. Override it with your own criteria and note
                            the potential use of variables in the criteria that
                            you specify.
                        </p>
                    </div>
                    <div class="mb-5">
                        <Link
                            :href="route('round.evaluation.show.qa', round)"
                            class="text-blue-500 hover:underline"
                        >
                            <i class="fa fa-user mr-1" aria-hidden="true"></i>
                            Human Evaluation Criteria
                        </Link>
                        <p>
                            By default this has a standard set of evaluation
                            criteria inherited from the eligibility criteria of
                            the round. You can also set your own criteria by
                            overriding the default questions.
                        </p>
                    </div>
                    <div class="mb-5">
                        <Link
                            :href="route('round.evaluation.setup', round)"
                            class="text-blue-500 hover:underline"
                        >
                            <i class="fa fa-user mr-1" aria-hidden="true"></i>
                            How well is the setup of this round?
                        </Link>
                        <p>
                            Rounds have two major pieces of information that
                            contributes to how easy or difficult it is for
                            Checker to evaluate applications. One is the round
                            eligibility criteria, and the other is the
                            application questions. These two need to have a
                            reasonable balance.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped></style>
