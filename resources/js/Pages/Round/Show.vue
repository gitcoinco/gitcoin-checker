<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const round = ref(usePage().props.round.valueOf());
const projects = ref(usePage().props.projects.valueOf());
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projects for round {{ round.name }}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table v-if="projects && projects.data.length > 0">
                    <thead>
                        <tr>
                            <th>Title</th>
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
                                <!-- <Link
                                    :href="route('round.show', round.id)"
                                    class="text-blue-500 hover:underline"
                                >
                                    View
                                </Link> -->
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
