<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import { showDateInShortFormat } from "@/utils.js";

const rounds = ref(usePage().props.rounds.valueOf());

const searchTerm = ref("");

const search = async () => {
    try {
        const response = await axios.get("/round/search/" + searchTerm.value);
        rounds.value = response.data;

        // Check if the URL already contains a ?
        var urlContainsQuestionMark = window.location.href.indexOf("?") !== -1;

        // Append the search term to the URL if it doesn't already exist.
        if (window.location.href.indexOf("?search=") === -1) {
            var separator = urlContainsQuestionMark ? "&" : "?";
            window.history.pushState(
                {},
                "",
                window.location.href + separator + "search=" + searchTerm.value
            );
        }
    } catch (error) {
        console.error("Error fetching search results:", error);
    }
};

const resetSearch = () => {
    // remove all url parameters
    window.history.pushState({}, "", window.location.href.split("?")[0]);
    searchTerm.value = "";
    search();
};

const onKeyup = (event) => {
    if (event.key === "Enter") {
        search();
    }
};

// check if the search parameter is passed in the url and if so, set searchTerm and kick off a search
const urlParams = new URLSearchParams(window.location.search);

if (urlParams.has("search")) {
    searchTerm.value = urlParams.get("search");
    search();
}

const flagRound = async (round) => {
    try {
        const response = await axios.post(`/round/flag/${round.uuid}`);
        // Assuming the response contains the updated round data
        round.flagged_at = response.data.flagged_at;
    } catch (error) {
        console.error("Error flagging round:", error);
    }
};
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Rounds
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table v-if="rounds && rounds.data.length > 0">
                    <thead>
                        <tr>
                            <th>
                                <Tooltip>
                                    <i
                                        class="fa fa-question-circle-o"
                                        aria-hidden="true"
                                        title="Flagged rounds are rounds that have been flagged by the community as potentially fraudulent. Flagged rounds are not included in the calculation of the total amount of funding available."
                                    ></i>
                                    <template #content>
                                        Pinned rounds will always stay at the
                                        top.
                                    </template>
                                </Tooltip>
                            </th>
                            <th>
                                <TextInput
                                    v-model="searchTerm"
                                    placeholder="Search..."
                                    @keyup="onKeyup"
                                />
                            </th>
                            <th>
                                Last application date
                                <Tooltip>
                                    <i
                                        class="fa fa-question-circle-o"
                                        aria-hidden="true"
                                        title="This is the last application date for the round"
                                    ></i>
                                    <template #content>
                                        The date the last application for the
                                        round as received.
                                    </template>
                                </Tooltip>
                            </th>
                            <th>Amount</th>
                            <th>Chain</th>
                            <th class="nowrap"># Projects</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(round, index) in rounds.data" :key="index">
                            <td @click="flagRound(round)" class="pointer">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-green-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    v-if="round.flagged_at"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-6 w-6 text-gray-500"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    v-else
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </td>

                            <td>
                                <Link
                                    :href="route('round.show', round)"
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.name }}
                                </Link>
                            </td>
                            <td>
                                <span v-if="round.last_application_at">
                                    {{
                                        showDateInShortFormat(
                                            round.last_application_at
                                        )
                                    }}
                                </span>
                            </td>
                            <td>${{ round.total_amount_donated_in_usd }}</td>
                            <td>
                                {{ round.chain.name }}
                            </td>
                            <td class="nowrap">
                                <Link
                                    :href="route('round.show', round)"
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.projects_count }}
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route('round.evaluation.show', round)
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    Round Evaluation
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-else>
                    <p>No rounds found for {{ searchTerm }}.</p>

                    <PrimaryButton @click="resetSearch()" class="mt-4">
                        Reset search
                    </PrimaryButton>
                </div>

                <Pagination :links="rounds.links" />
            </div>
        </div>
    </AppLayout>
</template>
