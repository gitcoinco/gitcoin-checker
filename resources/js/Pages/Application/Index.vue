<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Welcome from "@/Components/Welcome.vue";
import Stats from "@/Components/Stats.vue";
import Applications from "@/Pages/Application/Components/Applications.vue";
import { usePage, router } from "@inertiajs/vue3";

import { defineProps, computed, ref } from "vue";

const applications = ref(usePage().props.applications.valueOf());

const props = defineProps({
    indexData: String,
});

const cleanedIndexData = computed(() => {
    return props.indexData.replace("https://", "");
});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Applications
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Applications :applications="applications" />
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>
            </div>
        </div>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Stats />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
