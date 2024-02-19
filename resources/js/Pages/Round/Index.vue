<script setup>
import { ref, watch } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import Tooltip from "@/Components/Tooltip.vue";
import { showDateInShortFormat, formatDecimals } from "@/utils.js";

const rounds = ref(usePage().props.rounds.valueOf());
const searchTerm = ref("");

let url = new URL(window.location.href);
const showTestRounds = ref(url.searchParams.get("showTestRounds") === "true");

watch(showTestRounds, (newValue) => {
    // Create a new URL object
    let url = new URL(window.location.href);

    // Check if showTestRounds already exists in the URL
    if (url.searchParams.has("showTestRounds")) {
        // If it exists, update the value
        url.searchParams.set("showTestRounds", newValue);
    } else {
        // If it doesn't exist, add it
        url.searchParams.append("showTestRounds", newValue);
    }

    // Refresh the page with the updated URL
    window.location.href = url.toString();
});

const search = async () => {
    try {
        const response = await axios.get("/round/search/" + searchTerm.value);
        rounds.value = response.data;

        // Create a new URL object
        let url = new URL(window.location.href);

        // Check if search already exists in the URL
        if (url.searchParams.has("search")) {
            // If it exists, update the value
            url.searchParams.set("search", searchTerm.value);
        } else {
            // If it doesn't exist, add it
            url.searchParams.append("search", searchTerm.value);
        }

        // Update the URL without reloading the page
        window.history.pushState({}, "", url.toString());
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
                <div class="mb-4 text-right">
                    <input
                        type="checkbox"
                        id="showTestRounds"
                        class="mr-1"
                        v-model="showTestRounds"
                    />
                    <label for="showTestRounds">Show test rounds</label>
                </div>
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
                            <th>
                                <Tooltip>
                                    Round score
                                    <template #content>
                                        How well is this round setup in terms of
                                        eligibility vs. application questions
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
                            <td>
                                <span
                                    v-if="
                                        round.gpt_round_eligibility_scores
                                            .length > 0
                                    "
                                    class="text-xs"
                                >
                                    <tooltip>
                                        {{
                                            round
                                                .gpt_round_eligibility_scores[0]
                                                .score
                                        }}
                                        <template #content>
                                            {{
                                                round
                                                    .gpt_round_eligibility_scores[0]
                                                    .reason
                                            }}
                                        </template>
                                    </tooltip>
                                </span>
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
