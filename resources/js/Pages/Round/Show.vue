<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { copyToClipboard, shortenAddress } from "@/utils.js";

const round = ref(usePage().props.round.valueOf());
const projects = ref(usePage().props.projects.valueOf());

function scoreTotal(results) {
    if (results && results.length > 0) {
        let resultsData = results[0].results_data;

        let total = 0;

        // parse resultsData into a json object
        resultsData = JSON.parse(resultsData);

        // iterate over each result
        let counter = 0;
        for (let result of resultsData) {
            // add the score to the total
            total += result.score;
            counter++;
        }

        total = total / counter;
        return total + "%";
    } else {
        return "n/a";
    }

    // // Split the results_data by newline to get individual score objects
    // let scoreObjects = resultsData.split("\n\n");

    // // Initialize total score to 0
    // let totalScore = 0;

    // // Iterate over each score object
    // for (let scoreObj of scoreObjects) {
    //     // Parse the score object to get the score value
    //     let parsedObj = JSON.parse(scoreObj);
    //     totalScore += parsedObj.score;
    // }

    // // Return the aggregated score object
    // return {
    //     aggregated_score: totalScore,
    // };
}
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
                <h2 class="text-xl">Projects</h2>

                <table v-if="projects && projects.data.length > 0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Website</th>
                            <th>Twitter</th>
                            <th>Github</th>
                            <th>Score</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(project, index) in projects.data"
                            :key="index"
                        >
                            <td>
                                <Link :href="route('project.show', project.id)">
                                    {{ project.title }}
                                    >

                                    {{ project.title }}
                                </Link>
                            </td>
                            <td>
                                <a
                                    href="{{ project.website }}"
                                    _target="_blank"
                                >
                                    {{
                                        project.website.replace("https://", "")
                                    }}
                                </a>
                            </td>
                            <td>
                                {{ project.projectTwitter }}
                            </td>
                            <td>
                                {{ project.userGithub }}
                            </td>
                            <td>
                                {{
                                    scoreTotal(project.applications[0].results)
                                }}
                            </td>
                            <td>
                                <Link
                                    :href="route('project.show', project.id)"
                                    class="text-blue-500 hover:underline"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <Pagination :links="projects.links" />
            </div>
        </div>
    </AppLayout>
</template>
