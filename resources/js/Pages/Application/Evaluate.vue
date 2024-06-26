<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { usePage, useForm, Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import {
    copyToClipboard,
    shortenAddress,
    showDateInShortFormat,
} from "@/utils.js";
import ResultsData from "@/Components/Gitcoin/Application/ResultsData.vue";

const round = ref(usePage().props.round.valueOf());
const application = ref(usePage().props.application.valueOf());
const prompt = ref(usePage().props.prompt.valueOf());
const result = ref(usePage().props.result.valueOf());

const form = useForm({});

let response = "";

const showPromptModal = ref(false);

const checkAgainstChatGPT = async () => {
    result.value = null;

    form.post(
        route("round.application.chatgpt", {
            application: application.value.uuid,
        }),
        {
            onSuccess: (response) => {
                result.value = response.props.result;
            },
            onError: (error) => {},
        }
    );
};
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ round.name }}
                <span class="text-sm" v-if="round.round_addr.length > 10">
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
                    <h2 class="text-lg">System Prompt</h2>
                    <i>{{ prompt.system_prompt }}</i>
                </div>
                <div class="mb-5" style="white-space: pre-line">
                    <h2 class="text-lg">Prompt</h2>
                    <i>{{ prompt.prompt }}</i>
                </div>
            </div>

            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8 text-right">
                <Link :href="route('round.show', { round: round })" class="mr-5"
                    >Round</Link
                >
                <Link
                    :href="route('round.prompt.show', { round: round })"
                    class="mr-5"
                    >Round Criteria</Link
                >
                <PrimaryButton @click="checkAgainstChatGPT"
                    >Check against ChatGPT</PrimaryButton
                >
            </div>

            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" v-if="result">
                <h2 class="text-xl mb-5">
                    Last evaluated on
                    {{ showDateInShortFormat(result.created_at, true) }}
                </h2>
                <div class="mb-5">
                    <Modal
                        :show="showPromptModal"
                        @close="showPromptModal = false"
                    >
                        <div class="modal-content">
                            <h2 class="modal-title flex justify-between">
                                Evaluate
                                <span
                                    @click="toggleModal"
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-times-circle-o"
                                        aria-hidden="true"
                                    ></i>
                                </span>
                            </h2>

                            {{ result.prompt_data }}
                        </div>
                    </Modal>

                    <div
                        @click="showPromptModal = !showPromptModal"
                        class="pointer text-blue-500"
                    >
                        Original Prompt
                    </div>
                </div>
                <div>
                    Result<br />
                    <div>
                        <ResultsData :result="result" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
