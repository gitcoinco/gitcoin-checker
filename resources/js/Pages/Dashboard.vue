<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Welcome from "@/Components/Welcome.vue";
import Stats from "@/Components/Stats.vue";

import { defineProps, computed } from "vue";

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
                Dashboard -
                <span class="text-sm"
                    >Index data from
                    <a :href="props.indexData" target="_blank">{{
                        cleanedIndexData
                    }}</a></span
                >
            </h2>
        </template>

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
