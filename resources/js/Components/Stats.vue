<script setup>
import { ref, onMounted } from "vue";
import axios from "axios"; // Ensure axios is imported
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import VueApexCharts from "vue3-apexcharts";
import { formatDecimals } from "@/utils";

const apexchart = VueApexCharts;

const { projectsCount, roundsCount } = usePage().props;
let gptStats = usePage().props.gptStats;

const chartOptions = ref({
    colors: ["#28A745", "#DC3545", "#007BFF"],
    chart: {
        id: "line-chart",
        type: "line",
        title: {
            text: "",
            align: "left",
        },
    },
    xaxis: {
        type: "datetime",
    },
    yaxis: {
        title: {
            text: "Finalisation in hours",
        },
    },
    stroke: {
        curve: "smooth",
    },
});

const series = ref([
    // {
    //     name: "Created",
    //     data: [],
    // },
    // {
    //     name: "Approved",
    //     data: [],
    // },
    // {
    //     name: "Rejected",
    //     data: [],
    // },
    {
        name: "Avg. hours approval",
        data: [],
    },
    {
        name: "Avg. hours rejection",
        data: [],
    },
]);

onMounted(async () => {
    try {
        const response = await axios.get(
            route("api.applications.stats.history")
        );
        // const defaultData = response.data.map((item) => ({
        //     x: new Date(item.date + "-01").getTime(),
        //     y: item.created,
        // }));
        // // You'll need to adjust the following lines to match how you fetch your approved/rejected data
        // const approvedData = response.data.map((item) => ({
        //     x: new Date(item.date + "-01").getTime(),
        //     y: item.approved,
        // }));
        // const rejectedData = response.data.map((item) => ({
        //     x: new Date(item.date + "-01").getTime(),
        //     y: item.rejected,
        // }));

        gptStats = response.data.gptStats;

        const avgHoursToApproval = response.data.history.map((item) => ({
            x: new Date(item.date).getTime(),
            y: item.avgHoursToApproval,
        }));

        const avgHoursToRejection = response.data.history.map((item) => ({
            x: new Date(item.date).getTime(),
            y: item.avgHoursToRejection,
        }));

        series.value = [
            // { ...series.value[0], data: defaultData },
            // { ...series.value[1], data: approvedData },
            // { ...series.value[2], data: rejectedData },
            { ...series.value[0], data: avgHoursToApproval },
            { ...series.value[1], data: avgHoursToRejection },
        ];
    } catch (error) {
        console.error("There was an error fetching the data:", error);
    }
});
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

        <div class="py-6" v-if="gptStats">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div>
                    <div class="mb-3">
                        Historic applications that have been approved:
                        {{ gptStats.APPROVED.count }}<br />
                        AVG GPT Score:
                        {{
                            formatDecimals(gptStats.APPROVED.avgGPTScore)
                        }}%<br />
                    </div>

                    <div class="mb-3">
                        Historic applications that have been rejected:
                        {{ gptStats.REJECTED.count }}<br />
                        AVG GPT Score:
                        {{
                            formatDecimals(gptStats.REJECTED.avgGPTScore)
                        }}%<br />
                    </div>

                    <div class="mb-3">
                        Historic applications that are pending:
                        {{ gptStats.PENDING.count }}<br />
                        AVG GPT Score:
                        {{
                            formatDecimals(gptStats.PENDING.avgGPTScore)
                        }}%<br />
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <h2 class="text-2xl mb-5">
                How long it takes to finalise applications
            </h2>
            <apexchart
                type="line"
                :options="chartOptions"
                :series="series"
            ></apexchart>
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
