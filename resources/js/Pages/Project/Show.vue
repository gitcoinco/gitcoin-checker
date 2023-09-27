<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

const project = ref(usePage().props.project.valueOf());
const applications = ref(usePage().props.applications.valueOf());
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ project.title }}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <table v-if="applications && applications.data.length > 0">
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(application, index) in applications.data"
                            :key="index"
                        >
                            <td>{{ application.round.name }}</td>
                            <td>
                                <Link
                                    :href="
                                        route(
                                            'round.show',
                                            application.round.id
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
            </div>
        </div>
    </AppLayout>
</template>
