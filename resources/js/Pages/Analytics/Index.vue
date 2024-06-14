<script setup>
import { ref, watch, reactive, toRefs } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";

const stats = ref(usePage().props.stats.valueOf());
</script>

<template>
    <AuthenticatedLayout title="Analytics">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Analytics
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <p class="mb-3 highlight-green">
                    This is work in progress and analytics will be expanded
                    based on requirements. Note that small discrepancies in data
                    exist, likely due to initial testing.
                </p>

                <p class="mb-3">
                    Over the past year, excluding rounds with the word 'test'
                    in, there have been {{ stats.rounds }} rounds involving ~{{
                        stats.applications
                    }}
                    applications.
                </p>

                <p class="mb-3">
                    {{ stats.roundsEvaluatedByHumans }} rounds had human
                    evaluators, evaluating a total of
                    {{ stats.roundApplicationsEvaluatedByHumans }} applications
                    using Checker.
                </p>

                <p>
                    {{ stats.roundsEvaluatedByAI }} rounds were evaluated by AI,
                    evaluating a total of ~{{
                        stats.roundApplicationsEvaluatedByAI
                    }}
                    applications.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
