<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { useForm, usePage, Link, router } from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";

import { shortenAddress, copyToClipboard } from "@/utils.js";

const round = ref(usePage().props.round.valueOf());

const form = useForm({
    application_result_availability_publicly:
        round.value.application_result_availability_publicly,
});

const save = async () => {
    form.post(
        route("round.settings.update", {
            round: round.value.uuid,
        }),
        {
            onSuccess: (response) => {},
            onError: (error) => {},
        }
    );
};

const openPublicView = (round) => {
    window.open(
        route("public.round.show", {
            round: round.uuid,
        }),
        "_blank"
    );
};
</script>

<template>
    <AuthenticatedLayout title="Profile">
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2
                        class="font-semibold text-xl text-gray-800 leading-tight"
                    >
                        Setup /
                        {{ round.name }} on {{ round.chain.name }}
                        <span
                            class="text-sm"
                            v-if="round.round_addr.length > 10"
                        >
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

        <div class="py-6">
            <div
                class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-3 py-3"
            >
                <div
                    class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex justify-between"
                >
                    <form
                        @submit.prevent="save"
                        class="w-full"
                        :class="{ 'opacity-25': form.processing }"
                    >
                        <div>
                            <div
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                            >
                                <div class="mb-5">
                                    <div class="mb-5">
                                        <a
                                            @click.prevent="
                                                openPublicView(round)
                                            "
                                            href="#"
                                            target="_blank"
                                            class="text-blue-500 hover:underline"
                                        >
                                            <i
                                                class="fa fa-external-link"
                                                aria-hidden="true"
                                            ></i>

                                            Open the public view of this round
                                            in a new window
                                        </a>
                                    </div>

                                    <div class="mb-5">
                                        <Link
                                            :href="
                                                route('round.roles.show', round)
                                            "
                                            class="text-blue-500 hover:underline"
                                        >
                                            Application reviewers
                                        </Link>
                                        <p>
                                            Round managers will automatically be
                                            able to add their reviews to
                                            applications. If you'd like to add
                                            additional reviewers, you can do so
                                            here.
                                        </p>
                                    </div>

                                    <div class="mb-5">
                                        <Link
                                            :href="
                                                route(
                                                    'round.evaluation.show',
                                                    round
                                                )
                                            "
                                            class="text-blue-500 hover:underline"
                                        >
                                            Round Evaluation Criteria
                                        </Link>
                                        <p>
                                            Define the criteria that will be
                                            used to evaluate applications in
                                            this round. Try to limit tweaking
                                            the prompt structure, as Checker
                                            relies on this structure to evaluate
                                            applications.
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="application_result_availability_publicly"
                                    >
                                        When can the public see application
                                        results:
                                    </label>

                                    <div>
                                        <input
                                            type="radio"
                                            id="public"
                                            value="public"
                                            v-model="
                                                form.application_result_availability_publicly
                                            "
                                            name="application_result_availability_publicly"
                                        />&nbsp;
                                        <label for="public"
                                            >Public -
                                            <span class="text-xs"
                                                >As an application is evaluated
                                                by either AI or humans, the
                                                results are available to the
                                                public.</span
                                            ></label
                                        >&nbsp;
                                    </div>
                                    <div>
                                        <input
                                            type="radio"
                                            id="private"
                                            value="private"
                                            v-model="
                                                form.application_result_availability_publicly
                                            "
                                            name="application_result_availability_publicly"
                                        />&nbsp;
                                        <label for="private"
                                            >Private -
                                            <span class="text-xs"
                                                >No application results are ever
                                                publicly available.</span
                                            ></label
                                        >
                                    </div>
                                    <div>
                                        <input
                                            type="radio"
                                            id="processed"
                                            value="processed"
                                            v-model="
                                                form.application_result_availability_publicly
                                            "
                                            name="application_result_availability_publicly"
                                        />&nbsp;
                                        <label for="processed"
                                            >Processed -
                                            <span class="text-xs"
                                                >Application results are
                                                publicly available once the
                                                application has been accepted or
                                                rejected.</span
                                            ></label
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="w-1/2">
                            <SecondaryButton
                                type="submit"
                                class="mt-4"
                                :disabled="form.processing"
                                >Save</SecondaryButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
