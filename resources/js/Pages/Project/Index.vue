7
<script setup>
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { shortenURL } from "@/utils.js";
import Tooltip from "@/Components/Tooltip.vue";

const projects = ref(usePage().props.projects.valueOf());

const searchTerm = ref("");

const search = async () => {
    try {
        const response = await axios.get(
            route("project.search") + "/" + searchTerm.value
        );
        projects.value = response.data;

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
</script>

<template>
    <AuthenticatedLayout title="Projects">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projects
            </h2>
        </template>

        <div class="py-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                    <div v-if="projects && projects.data.length > 0">
                        <table>
                            <thead>
                                <tr>
                                    <th>
                                        <TextInput
                                            v-model="searchTerm"
                                            placeholder="Search..."
                                            @keyup="onKeyup"
                                        />
                                    </th>
                                    <th>Rounds</th>
                                    <th>Website</th>
                                    <th>Twitter</th>
                                    <th>Github</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(project, index) in projects.data"
                                    :key="index"
                                >
                                    <td>
                                        <Link
                                            :href="
                                                route('project.show', project)
                                            "
                                            class="text-blue-500 hover:underline"
                                        >
                                            {{ project.title }}
                                        </Link>
                                    </td>
                                    <td>
                                        <div>
                                            <Link
                                                v-for="(
                                                    application, index
                                                ) in project.applications"
                                                :key="index"
                                                :href="
                                                    route(
                                                        'round.show',
                                                        application.round
                                                    )
                                                "
                                                class="text-blue-500 hover:underline"
                                            >
                                                {{ application.round.name }}
                                            </Link>
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            :href="project.website"
                                            _target="_blank"
                                            class="text-blue-500 hover:underline"
                                            v-if="project.website"
                                        >
                                            {{
                                                shortenURL(
                                                    project.website.replace(
                                                        "https://",
                                                        ""
                                                    ),
                                                    20
                                                )
                                            }}
                                        </a>
                                    </td>
                                    <td class="nowrap">
                                        <span v-if="project.projectTwitter">
                                            <a
                                                :href="
                                                    'https://twitter.com/' +
                                                    project.projectTwitter
                                                "
                                                target="_blank"
                                            >
                                                <i
                                                    class="fa fa-twitter text-blue-500"
                                                    aria-hidden="true"
                                                ></i>
                                                {{ project.projectTwitter }}
                                            </a>
                                        </span>
                                    </td>
                                    <td class="nowrap">
                                        <a
                                            :href="
                                                'https://github.com/' +
                                                project.projectGithub
                                            "
                                            target="_blank"
                                            v-if="project.projectGithub"
                                        >
                                            <i
                                                class="fa fa-github"
                                                aria-hidden="true"
                                            ></i>
                                            {{ project.projectGithub }}

                                            <Tooltip>
                                                <i
                                                    class="fa fa-question-circle-o"
                                                    aria-hidden="true"
                                                    title="This is the last application date for the round"
                                                ></i>
                                                <template #content>
                                                    Project Github repository.
                                                </template>
                                            </Tooltip>
                                            <br />
                                        </a>
                                        <a
                                            :href="
                                                'https://github.com/' +
                                                project.userGithub
                                            "
                                            target="_blank"
                                            v-if="project.userGithub"
                                        >
                                            <i
                                                class="fa fa-github"
                                                aria-hidden="true"
                                            ></i>
                                            {{ project.userGithub }}

                                            <Tooltip>
                                                <i
                                                    class="fa fa-question-circle-o"
                                                    aria-hidden="true"
                                                    title="This is the last application date for the round"
                                                ></i>
                                                <template #content>
                                                    User Github repository.
                                                </template>
                                            </Tooltip>
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <Pagination :links="projects.links" />
                    </div>
                    <div v-else-if="searchTerm">
                        <p>No results found for "{{ searchTerm }}"</p>

                        <Link
                            :href="route('project.index')"
                            class="text-blue-500 hover:underline"
                        >
                            Clear Search
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
