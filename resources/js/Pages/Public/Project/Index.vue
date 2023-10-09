<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import GitcoinLogo from "@/Components/Gitcoin/Logo.vue";
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

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
    <Head title="Welcome" />

    <div
        class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-dots-darker bg-center bg-gray-100 dark:bg-dots-lighter dark:bg-gray-900 selection:bg-red-500 selection:text-white"
    >
        <div
            v-if="canLogin"
            class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10"
        >
            <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                >Dashboard</Link
            >

            <template v-else>
                <Link
                    :href="route('login')"
                    class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Log in</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="ml-4 font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500"
                    >Register</Link
                >
            </template>
        </div>

        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <div class="flex justify-left">
                <GitcoinLogo class="w-20 h-20" />
                <ApplicationLogo class="w-20 h-20" />
            </div>

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
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(project, index) in projects.data"
                                :key="index"
                            >
                                <td>{{ project.title }}</td>
                                <td>{{ project.website }}</td>
                                <td>{{ project.projectTwitter }}</td>
                                <td>{{ project.userGithub }}</td>
                                <td>
                                    <Link
                                        :href="
                                            route(
                                                'public.project.show',
                                                project.id
                                            )
                                        "
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
        </div>
    </div>
</template>
