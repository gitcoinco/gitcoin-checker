<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { usePage, useForm, Link } from "@inertiajs/vue3";
import { copyToClipboard, shortenAddress } from "@/utils.js";

const round = ref(usePage().props.round.valueOf());
const application = ref(usePage().props.application.valueOf());
const prompt = ref(usePage().props.prompt.valueOf());
let result = ref(usePage().props.result.valueOf());

const form = useForm({});

let response = "";

const checkAgainstChatGPT = async () => {
    form.post(
        route("round.application.chatgpt", {
            application: application.value.id,
        }),
        {
            onSuccess: (response) => {
                result = response.props.result;
            },
            onError: (error) => {},
        }
    );
};
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
                <div class="mb-5">
                    <h2 class="text-lg">{{ application.project.title }}</h2>
                </div>
                <div class="mb-5" style="white-space: pre-line">
                    system_prompt: {{ prompt.system_prompt }}
                </div>
                <div class="mb-5" style="white-space: pre-line">
                    prompt: {{ prompt.prompt }}
                </div>
            </div>

            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 text-right">
                <Link
                    :href="route('round.show', { round: round.id })"
                    class="mr-5"
                    >Round</Link
                >
                <Link
                    :href="route('round.prompt.show', { round: round.id })"
                    class="mr-5"
                    >Round Criteria</Link
                >
                <PrimaryButton @click="checkAgainstChatGPT"
                    >Check against ChatGPT</PrimaryButton
                >
            </div>

            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" v-if="result">
                <div class="mb-5">
                    Prompt<br />
                    {{ result.prompt_data }}
                </div>
                <div>
                    Result<br />
                    {{ result.results_data }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
