<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { copyToClipboard, shortenAddress } from "@/utils.js";
import TextareaInput from "@/Components/TextareaInput.vue";
import RandomApplicationPrompt from "./Components/GPT/RandomApplicationPrompt.vue";

const round = ref(usePage().props.round.valueOf());
const prompt = ref(usePage().props.prompt.valueOf());
const randomGeneratedPrompt = ref(null);

const form = useForm({
    system_prompt: "",
    prompt: "",
});

if (prompt.value) {
    form.system_prompt = prompt.value.system_prompt;
    form.prompt = prompt.value.prompt;
}

const resetToDefaultPrompt = () => {
    if (confirm("Are you sure you want to reset to the default prompt?")) {
        form.get(
            route("round.prompt.reset", {
                round: round.value.uuid,
            }),
            {
                onSuccess: (response) => {
                    window.location.reload();
                },
                onError: (error) => {},
            }
        );
    }
};

const savePrompts = async () => {
    form.post(
        route("round.prompt.upsert", {
            round: round.value.uuid,
        }),
        {
            onSuccess: (response) => {
                randomApplication.value = response.props.randomApplication;
                prompt.value = response.props.prompt;
            },
            onError: (error) => {},
        }
    );
};

const addAccessControl = () => {
    form.post(route("access-control.upsert", {}), {
        onSuccess: (response) => {
            accessControls.value = response.props.accessControls;
        },
        onError: (error) => {},
    });

    form.eth_addr = "";
    form.role = "admin";
};

onMounted(async () => {
    try {
        const response = await axios.get(
            route("round.prompt.randomapplicationresults", {
                round: round.value.uuid,
            })
        );
        randomGeneratedPrompt.value = response.data;
    } catch (error) {
        console.error(error);
    }
});
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <Link :href="route('round.show', { round })">
                    {{ round.name }} </Link
                >&nbsp;
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
        </template>

        <div
            class="mt-10 bg-white overflow-hidden shadow-xl sm:rounded-lg mb-5 py-6"
        >
            <div>
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
                    <div class="flex justify-between items-center mb-5">
                        <h2>Evaluation Criteria</h2>
                        <PrimaryButton @click="resetToDefaultPrompt"
                            >Reset to Default Prompt</PrimaryButton
                        >
                    </div>

                    <div class="flex">
                        <div class="w-5/6">
                            <div class="mb-4">
                                <label
                                    for="system-prompt"
                                    class="block text-gray-700 text-sm mb-2"
                                    >System Prompt:</label
                                >
                                <TextareaInput
                                    id="system-prompt"
                                    v-model="form.system_prompt"
                                />
                            </div>
                            <div class="mb-4">
                                <label
                                    for="evaluation-prompt"
                                    class="block text-gray-700 text-sm mb-2"
                                    >Evaluation Prompt:</label
                                >
                                <TextareaInput
                                    id="evaluation-prompt"
                                    v-model="form.prompt"
                                />
                            </div>
                        </div>
                        <div class="w-1/2 p-5 text-sm">
                            <p class="mb-5">
                                You can use the variables below to pull in
                                dynamic data from the user:
                            </p>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{ round.name }}</span>
                                <span
                                    @click="copyToClipboard('{{ round.name }}')"
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                The name of the round.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    round.eligibility.description
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ round.eligibility.description }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                The eligibility criteria for the round.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    round.eligibility.requirements
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ round.eligibility.requirements }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                The eligibility requirements for the round.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    application.answers
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ application.answers }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                The answers the user gave in their application.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    project.name
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard('{{ project.name }}')
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                Name of the project.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    project.details
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard('{{ project.details }}')
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                Details of the project, e.g. name, website,
                                description, twitter, github (project), github
                                (user).
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    project.historic_applications
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ project.historic_applications }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                The number of historic applications the project
                                has, together with their status
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    github.recent_activity.summary
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ github.recent_activity.summary }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                Recent (past 3 months) Github activity.
                            </div>
                            <div class="mb-2">
                                <span v-pre class="mr-3">{{
                                    github.oss.project.summary
                                }}</span>
                                <span
                                    @click="
                                        copyToClipboard(
                                            '{{ github.oss.project.summary }}'
                                        )
                                    "
                                    class="cursor-pointer"
                                >
                                    <i
                                        class="fa fa-clone"
                                        aria-hidden="true"
                                    ></i> </span
                                ><br />
                                An Opensource Observer summary of the project if
                                available.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-right">
                        <Link
                            :href="route('round.show', { round: round })"
                            class="mr-3"
                            >Round Projects</Link
                        >
                        <PrimaryButton @click="savePrompts">Save</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-5 p-5">
            <div v-if="randomGeneratedPrompt">
                <div>
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <h2 class="text-2xl mb-5">
                            A random application and the prompt that will be
                            generated for them
                        </h2>
                        <RandomApplicationPrompt
                            :application="randomGeneratedPrompt"
                            class="text-lg"
                        />
                    </div>
                    <div>
                        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                            <div class="mb-10"></div>
                            <div>
                                <div>
                                    <Link
                                        :href="
                                            route(
                                                'round.evaluation.show.qa',
                                                round
                                            )
                                        "
                                        class="text-blue-500 hover:underline"
                                    >
                                        Human Evaluation Criteria
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else>No random application available.</div>
        </div>
    </AuthenticatedLayout>
</template>
