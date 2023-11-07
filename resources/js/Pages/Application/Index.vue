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

const removeTests = (newStatus) => {
    router.visit(
        route("round.application.index", {
            selectedApplicationRemoveTests: newStatus,
        })
    );
};

const statusChanged = (newStatus) => {
    router.visit(
        route("round.application.index", {
            status: newStatus,
        })
    );
};

const roundType = (newStatus) => {
    // Refresh applications using ajax
    axios
        .get(
            route("user-preferences.rounds.selectedApplicationRoundType", {
                selectedApplicationRoundType: newStatus,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            refreshApplications();
        });
};

function refreshApplications() {
    axios
        .get(route("dashboard", {}), {
            responseType: "json",
        })
        .then((response) => {
            applications.value = response.data.applications;
        });
}
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
                    <Applications
                        :applications="applications"
                        @status-changed="statusChanged"
                        @remove-tests="removeTests"
                        @round-type="roundType"
                        @refresh-applications="refreshApplications"
                        @user-rounds-changed="refreshApplications"
                    />
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
