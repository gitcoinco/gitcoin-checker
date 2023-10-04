<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";

import { defineProps, computed } from "vue";

const projectsCount = ref(usePage().props.projectsCount.valueOf());
const roundsCount = ref(usePage().props.roundsCount.valueOf());
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <h1 class="mt-8 text-2xl font-medium text-gray-900">Stats</h1>

            <p class="mt-6 text-gray-500 leading-relaxed">
                Bits of data from the index, which is converted into a
                relational database.
            </p>
        </div>

        <div
            class="bg-gray-200 bg-opacity-25 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8 p-6 lg:p-8"
        >
            <div>
                <div class="flex items-center">
                    <i
                        class="fa fa-building-o text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        {{ projectsCount }} projects
                    </h2>
                </div>
            </div>

            <div>
                <div class="flex items-center">
                    <i
                        class="fa fa-circle-o text-gray-400 fa-lg"
                        aria-hidden="true"
                    ></i>
                    <h2 class="ml-3 text-xl font-semibold text-gray-900">
                        {{ roundsCount }} Rounds
                    </h2>
                </div>
            </div>
        </div>
    </div>
</template>
