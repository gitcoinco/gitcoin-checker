<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const projects = ref(usePage().props.projects.valueOf());

const searchTerm = ref("");

const search = async () => {
    try {
        const response = await axios.get("/project/search/" + searchTerm.value);
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
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projects
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table v-if="projects && projects.data.length > 0">
                    <thead>
                        <tr>
                            <th>
                                <TextInput
                                    v-model="searchTerm"
                                    placeholder="Search..."
                                    @keyup="onKeyup"
                                />
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(project, index) in projects.data"
                            :key="index"
                        >
                            <td>{{ project.title }}</td>
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
