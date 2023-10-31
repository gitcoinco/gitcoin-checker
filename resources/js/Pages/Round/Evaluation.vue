<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
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
            </div>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    <Link
                        :href="route('round.prompt.show', round)"
                        class="text-blue-500 hover:underline"
                    >
                        ChatGPT Evaluation Criteria
                    </Link>
                </div>
                <div>
                    <Link
                        :href="route('round.evaluation.show.qa', round)"
                        class="text-blue-500 hover:underline"
                    >
                        Human Evaluation Criteria
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped></style>
