<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                No Access
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    You do not have access. Please contact PGF to request
                    access.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
