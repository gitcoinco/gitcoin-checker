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

const form = useForm([]);

function evaluateApplication(application) {
    form.post(
        route("round.application.chatgpt.list", {
            application: application.id,
        }),
        {
            onSuccess: (response) => {
                round.value = response.props.round;
                projects.value = response.props.projects;
            },
            onError: (error) => {},
        }
    );
}

function scoreTotal(results) {
    if (results && results.length > 0) {
        let resultsData = results[0].results_data;

        let total = 0;

        // Try to parse resultsData into a json object
        try {
            resultsData = JSON.parse(resultsData);

            // Check if resultsData is an array and has items
            if (!Array.isArray(resultsData) || resultsData.length === 0) {
                return "n/a";
            }
        } catch (error) {
            return resultsData; // Return "n/a" or any other appropriate value in case of a parsing error
        }

        // iterate over each result
        let counter = 0;
        for (let result of resultsData) {
            // Check if result has a score property and it's a number
            if (result && typeof result.score === "number") {
                // add the score to the total
                total += result.score;
                counter++;
            }
        }

        // Check if counter is not zero to avoid division by zero
        if (counter === 0) {
            return "n/a";
        }

        total = total / counter;
        // set total to a max of 1 decimal
        total = total.toFixed(1);
        return total + "%";
    } else {
        return "n/a";
    }
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
                            <td>
                                <Link
                                    @click="
                                        evaluateApplication(
                                            project.applications[0]
                                        )
                                    "
                                    href="#"
                                    class="text-blue-500 hover:underline"
                                >
                                    Evaluate
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
