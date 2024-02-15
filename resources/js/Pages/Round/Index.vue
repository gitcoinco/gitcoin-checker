<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import { showDateInShortFormat, formatDecimals } from "@/utils.js";

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
                                <TextInput
                                    v-model="searchTerm"
                                    placeholder="Search..."
                                    @keyup="onKeyup"
                                />
                            </th>
                            <th>
                                <Tooltip>
                                    Applications
                                    <template #content>
                                        When are applications open
                                    </template>
                                </Tooltip>
                            </th>
                            <th>
                                <Tooltip>
                                    Round
                                    <template #content>
                                        When does the round run
                                    </template>
                                </Tooltip>
                            </th>
                            <th>Amount</th>
                            <th>Chain</th>
                            <th class="nowrap"># Projects</th>
                            <th>
                                <Tooltip>
                                    Pending
                                    <template #content>
                                        Pending applications
                                    </template>
                                </Tooltip>
                            </th>
                            <th>
                                <Tooltip>
                                    Approved
                                    <template #content>
                                        Approved applications
                                    </template>
                                </Tooltip>
                            </th>
                            <th>
                                <Tooltip>
                                    Rejected
                                    <template #content>
                                        Rejected applications
                                    </template>
                                </Tooltip>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(round, index) in rounds.data" :key="index">
                            <td>
                                <Link
                                    :href="route('round.show', round)"
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.name }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap">
                                <span
                                    v-if="
                                        round.applications_start_time &&
                                        round.applications_end_time
                                    "
                                    class="text-xs"
                                >
                                    {{
                                        showDateInShortFormat(
                                            round.applications_start_time
                                        )
                                    }}
                                    <br />
                                    {{
                                        showDateInShortFormat(
                                            round.applications_end_time
                                        )
                                    }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span
                                    v-if="
                                        round.donations_start_time &&
                                        round.donations_end_time
                                    "
                                    class="text-xs"
                                >
                                    {{
                                        showDateInShortFormat(
                                            round.donations_start_time
                                        )
                                    }}
                                    <br />
                                    {{
                                        showDateInShortFormat(
                                            round.donations_end_time
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
                                        route('round.show', {
                                            round: round,
                                            status: 'pending',
                                        })
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.pending_applications_count }}
                                    <span
                                        class="text-xs"
                                        v-if="round.applications_pending"
                                    >
                                        <br />
                                        GPT Avg:
                                        {{
                                            formatDecimals(
                                                round.applications_pending
                                            )
                                        }}%
                                    </span>
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route('round.show', {
                                            round: round,
                                            status: 'approved',
                                        })
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.approved_applications_count }}
                                    <span
                                        class="text-xs"
                                        v-if="round.applications_approved"
                                    >
                                        <br />
                                        GPT Avg:
                                        {{
                                            formatDecimals(
                                                round.applications_approved
                                            )
                                        }}%
                                    </span>
                                </Link>
                            </td>
                            <td>
                                <Link
                                    :href="
                                        route('round.show', {
                                            round: round,
                                            status: 'rejected',
                                        })
                                    "
                                    class="text-blue-500 hover:underline"
                                >
                                    {{ round.rejected_applications_count }}
                                    <span
                                        class="text-xs"
                                        v-if="round.applications_rejected"
                                    >
                                        <br />
                                        GPT Avg:
                                        {{
                                            formatDecimals(
                                                round.applications_rejected
                                            )
                                        }}%
                                    </span>
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
