<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Welcome from "@/Components/Welcome.vue";
import Stats from "@/Components/Stats.vue";
import Applications from "@/Pages/Application/Components/Applications.vue";
import { usePage, router } from "@inertiajs/vue3";

import { defineProps, computed, ref } from "vue";

// const applications = ref(usePage().props.applications.valueOf());
const busyLoadingApplications = ref(false);

const props = defineProps({
    indexData: String,
});

const cleanedIndexData = computed(() => {
    return props.indexData.replace("https://", "");
});

const searchProjects = (newStatus) => {
    busyLoadingApplications.value = true;
    axios
        .get(
            route("dashboard", {
                selectedSearchProjects: newStatus,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            applications.value = response.data.applications;
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
};

const removeTests = (newStatus) => {
    router.visit(
        route("dashboard", {
            selectedApplicationRemoveTests: newStatus,
        })
    );
};

const statusChanged = (newStatus) => {
    busyLoadingApplications.value = true;
    axios
        .get(
            route("dashboard", {
                selectedApplicationStatus: newStatus,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            applications.value = response.data.applications;
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
};

const orderByChanged = (newVal) => {
    busyLoadingApplications.value = true;
    axios
        .get(
            route("dashboard", {
                roundApplicationOrderBy: newVal,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            applications.value = response.data.applications;
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
};

const orderByDirectionChanged = (newVal) => {
    busyLoadingApplications.value = true;
    axios
        .get(
            route("dashboard", {
                roundApplicationOrderByDirection: newVal,
            }),
            {
                responseType: "json",
            }
        )
        .then((response) => {
            applications.value = response.data.applications;
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
};

const roundType = (newStatus) => {
    busyLoadingApplications.value = true;
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
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
};

function refreshApplications() {
    busyLoadingApplications.value = true;
    axios
        .get(route("dashboard", {}), {
            responseType: "json",
        })
        .then((response) => {
            applications.value = response.data.applications;
        })
        .finally(() => {
            busyLoadingApplications.value = false;
        });
}
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <!-- <div class="py-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Applications
                        :busyLoadingApplications="busyLoadingApplications"
                        :applications="applications"
                        @status-changed="statusChanged"
                        @remove-tests="removeTests"
                        @round-type="roundType"
                        @refresh-applications="refreshApplications"
                        @user-rounds-changed="refreshApplications"
                        @search-projects="searchProjects"
                        @order-by-changed="orderByChanged"
                        @order-by-direction-changed="orderByDirectionChanged"
                    />
                </div>
            </div>
        </div> -->

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
