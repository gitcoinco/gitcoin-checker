<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import GitcoinLogo from "@/Components/Gitcoin/Logo.vue";
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const project = ref(usePage().props.project.valueOf());

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
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
                    <div class="max-w-7xl mx-auto py-5 sm:px-6 lg:px-8">
                        <h2 class="text-xl">Project Details</h2>
                        <div>
                            <div>
                                {{ project.title }}
                            </div>
                            <div v-if="project.website">
                                Website: {{ project.website }}
                            </div>
                            <div v-if="project.metadata.projectTwitter">
                                Twitter: {{ project.metadata.projectTwitter }}
                            </div>
                            <div v-if="project.metadata.userGithub">
                                Github: {{ project.metadata.userGithub }}
                            </div>
                            <div
                                v-if="project.metadata.description"
                                class="text-xs"
                            >
                                {{ project.metadata.description }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
