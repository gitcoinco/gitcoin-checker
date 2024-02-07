<script setup>
import { ref, defineProps, defineEmits } from "vue";
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
            additional_emails: [],
            days_of_the_week: [],
            time_of_the_day: "",
            notification_setup_rounds: [],
        }),
    },
    rounds: {
        type: Array,
        required: true,
    },
});

// Convert time_of_the_day from UTC to current locale
const convertTimeToLocale = (utcTime) => {
    let date = new Date(utcTime);

    return date.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
};

const emit = defineEmits(["update-notification-setup"]);

const showModal = ref(false);

const utcTimeOfTheDay = ref(props.notificationSetup.time_of_the_day);
let localTimeOfTheDay = ref(convertTimeToLocale(utcTimeOfTheDay.value));

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

    const setup = {
        uuid: props.notificationSetup.uuid,
        title: props.notificationSetup.title,
        additional_emails: props.notificationSetup.additional_emails,
        days_of_the_week: props.notificationSetup.days_of_the_week,
        time_of_the_day: props.notificationSetup.time_of_the_day,
        notification_setup_rounds:
            props.notificationSetup.notification_setup_rounds,
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
                            <label
                                class="block text-sm font-medium text-gray-700"
                                >Rounds to Include:</label
                            >

                            notificationSetupRounds:
                            {{ notificationSetupRounds }}

                            <select
                                v-model="notificationSetupRounds"
                                multiple
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option
                                    v-for="round in rounds"
                                    :value="round.id"
                                    :key="round.id"
                                >
                                    {{ round.name }} - {{ round.id }}
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
