<script setup>
import { ref, defineProps, defineEmits, computed } from "vue";

import Modal from "@/Components/Modal.vue";
import { watch } from "vue";
import { isValidEmail } from "@/utils";

// Props for rounds and initial setup (if any)
const props = defineProps({
    notificationSetup: {
        type: Object,
        default: () => ({
            uuid: null,
            title: "",
            email_subject: "",
            additional_emails: [],
            days_of_the_week: [],
            time_type: "",
            time_of_the_day: "",
            notification_setup_rounds: [],
            nr_summaries_per_email: 25,
        }),
    },
    rounds: {
        type: Array,
        required: true,
    },
});

const searchTerm = ref("");

const filteredRounds = computed(() => {
    return props.rounds.filter((round) =>
        round.name?.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

// Convert time_of_the_day from UTC to current locale
const convertTimeToLocale = (utcTime) => {
    let date = new Date(utcTime);
    let ret = date.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });

    if (ret == "24:00") {
        ret = "00:00";
    }

    return ret;
};

const emit = defineEmits(["update-notification-setup"]);

const showModal = ref(false);

const utcTimeOfTheDay = ref(props.notificationSetup.time_of_the_day);
let localTimeOfTheDay = ref(convertTimeToLocale(utcTimeOfTheDay.value));
console.log("a" + localTimeOfTheDay.value);

watch(utcTimeOfTheDay, (newTime) => {
    localTimeOfTheDay.value = convertTimeToLocale(newTime);
});

const emailInput = ref();
const daysOfTheWeek = ref(props.notificationSetup.days_of_the_week || []);
const notificationSetupRounds = ref(
    props.notificationSetup.notification_setup_rounds.map(
        (value) => value.round_id
    )
);
const timeType = ref(props.notificationSetup.time_type);

// Days of the week for checkboxes
const weekDays = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
];

// Function to add an email to the list
const addEmail = (email) => {
    if (!isValidEmail(email)) {
        alert("Invalid email");
        return;
    }

    if (email && !props.notificationSetup.additional_emails.includes(email)) {
        props.notificationSetup.additional_emails.push(email);
    }
};

// Function to remove an email from the list
const removeEmail = (email) => {
    const index = props.notificationSetup.additional_emails.indexOf(email);
    if (index !== -1) {
        // Use Vue's splice method to trigger reactivity
        props.notificationSetup.additional_emails.splice(index, 1);
    }
};

// Submit function
const submitNotificationSetup = () => {
    props.notificationSetup.days_of_the_week = daysOfTheWeek.value;
    props.notificationSetup.notification_setup_rounds =
        notificationSetupRounds.value;

    props.notificationSetup.time_of_the_day = localTimeOfTheDay.value;
    props.notificationSetup.time_type = timeType.value;

    const setup = {
        uuid: props.notificationSetup.uuid,
        title: props.notificationSetup.title,
        email_subject: props.notificationSetup.email_subject,
        additional_emails: props.notificationSetup.additional_emails,
        days_of_the_week: props.notificationSetup.days_of_the_week,
        time_type: props.notificationSetup.time_type,
        time_of_the_day: props.notificationSetup.time_of_the_day,
        notification_setup_rounds:
            props.notificationSetup.notification_setup_rounds,
        nr_summaries_per_email: props.notificationSetup.nr_summaries_per_email,
        // rounds: selectedRounds.value,
    };
    emit("update-notification-setup", setup);
    showModal.value = false;
};
</script>

<template>
    <div>
        <div v-if="!showModal">
            <span @click="showModal = true">
                <slot></slot>
            </span>
        </div>
        <div v-else>
            <Modal :show="showModal">
                <div class="bg-white p-6 rounded-lg shadow-lg">
                    <div class="text-right">
                        <span class="pointer" @click="showModal = false"
                            >Close</span
                        >
                    </div>

                    <form
                        @submit.prevent="submitNotificationSetup"
                        class="space-y-4"
                    >
                        <h2>Get notified about applications</h2>

                        <div>
                            <label
                                for="title"
                                class="block text-sm font-medium text-gray-700"
                                >Title:</label
                            >
                            <input
                                v-model="notificationSetup.title"
                                type="text"
                                id="title"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <label
                                for="email_subject"
                                class="block text-sm font-medium text-gray-700"
                                >Email Subject:</label
                            >
                            <input
                                v-model="notificationSetup.email_subject"
                                type="text"
                                id="email_subject"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Days of the Week:</label
                            >
                            <div class="flex flex-wrap">
                                <div
                                    v-for="day in weekDays"
                                    :key="day"
                                    class="flex items-center space-x-2 mr-4 mb-2"
                                >
                                    <input
                                        type="checkbox"
                                        :value="day"
                                        v-model="daysOfTheWeek"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <label class="text-sm text-gray-500">{{
                                        day
                                    }}</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label
                                for="time_type"
                                class="block text-sm font-medium text-gray-700"
                                >Time Type:</label
                            >
                            <select
                                v-model="timeType"
                                id="time_type"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option disabled value="">
                                    Please select a time type
                                </option>
                                <option value="specific">Specific</option>
                                <option value="hour">Every Hourly</option>
                                <option value="minute">Every Minute</option>
                            </select>
                        </div>
                        <div v-if="timeType == 'specific'">
                            <label
                                for="timeOfTheDay"
                                class="block text-sm font-medium text-gray-700"
                                >Time of the Day:</label
                            >
                            <input
                                v-model="localTimeOfTheDay"
                                type="time"
                                id="timeOfTheDay"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label
                                for="nr_summaries_per_email"
                                class="block text-sm font-medium text-gray-700"
                                >Number of summaries per email:</label
                            >
                            <input
                                v-model="
                                    notificationSetup.nr_summaries_per_email
                                "
                                type="number"
                                id="nr_summaries_per_email"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label
                                for="additionalEmails"
                                class="block text-sm font-medium text-gray-700"
                                >Additional Emails:</label
                            >
                            <div
                                v-for="(
                                    email, index
                                ) in notificationSetup.additional_emails"
                                :key="index"
                                class="flex items-center space-x-2"
                            >
                                <span class="text-sm text-gray-500">{{
                                    email
                                }}</span>
                                <button
                                    type="button"
                                    @click="removeEmail(email)"
                                    class="text-sm text-red-500 hover:text-red-700"
                                >
                                    Remove
                                </button>
                            </div>
                            <div class="flex items-center">
                                <input
                                    type="email"
                                    v-model="emailInput"
                                    placeholder="Add email"
                                    class="mt-1 flex-grow rounded-md border-gray-300 shadow-sm"
                                />
                                <button
                                    type="button"
                                    @click="addEmail(emailInput)"
                                    class="ml-2 px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                                >
                                    Add Email
                                </button>
                            </div>
                        </div>

                        <div>
                            <div>
                                <input
                                    v-model="searchTerm"
                                    type="text"
                                    id="roundSearch"
                                    placeholder="Rounds to Include:"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    style="
                                        margin-bottom: 0;
                                        border-bottom-right-radius: 0;
                                        border-bottom-left-radius: 0;
                                        border-bottom: 1px dotted #d2d6dc;
                                    "
                                />
                            </div>

                            <select
                                v-model="notificationSetupRounds"
                                multiple
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                                style="
                                    border-top-left-radius: 0;
                                    border-top-right-radius: 0;
                                    border-top: 0;
                                "
                            >
                                <option
                                    v-for="round in filteredRounds"
                                    :value="round.id"
                                    :key="round.id"
                                >
                                    {{ round.name }}
                                </option>
                            </select>
                        </div>
                        <button
                            type="submit"
                            class="mt-2 px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            Submit
                        </button>
                    </form>
                </div>
            </Modal>
        </div>
    </div>
</template>

<style scoped>
/* Add your styles here */
</style>
