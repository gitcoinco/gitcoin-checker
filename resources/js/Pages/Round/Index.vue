<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

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
        const response = await axios.post(`/round/flag/${round.id}`);
        // Assuming the response contains the updated round data
        round.flagged_at = response.data.flagged_at;
    } catch (error) {
        console.error("Error flagging round:", error);
    }
};

function showDateInShortFormat(date) {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
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
                            <th></th>
                            <th>
                                <TextInput
                                    v-model="searchTerm"
                                    placeholder="Search..."
                                    @keyup="onKeyup"
                                />
                            </th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Chain</th>
                            <th># Projects</th>
                            <th></th>
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

                            <td>{{ round.name }}</td>
                            <td>
                                {{
                                    showDateInShortFormat(
                                        round.round_start_time
                                    )
                                }}
                            </td>
                            <td>${{ round.amount_usd }}</td>
                            <td>{{ round.chain.chain_id }}</td>
                            <td>{{ round.project_count }}</td>
                            <td>
                                <Link
                                    :href="route('round.show', round.id)"
                                    class="text-blue-500 hover:underline"
                                >
                                    Projects
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="route('round.prompt.show', round.id)"
                                    class="text-blue-500 hover:underline"
                                >
                                    Criteria
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
