7
<script setup>
import { ref } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import { shortenURL } from "@/utils.js";
import Tooltip from "@/Components/Tooltip.vue";
import NotificationSetups from "./Components/NotificationSetup.vue";

const notificationSetups = ref(usePage().props.notificationSetups.valueOf());
const rounds = ref(usePage().props.rounds.valueOf());

const upsertNotificationSetup = (notificationSetup) => {
    let timeParts = notificationSetup.time_of_the_day.split(":"); // Splits "11:11" into ["11", "11"]
    let now = new Date();
    let date = new Date(
        now.getUTCFullYear(),
        now.getUTCMonth(),
        now.getUTCDate(),
        parseInt(timeParts[0]),
        parseInt(timeParts[1])
    );

    notificationSetup.time_of_the_day = date.toUTCString();

    axios
        .post(route("notificationsetup.upsert"), notificationSetup)
        .then((response) => {
            notificationSetups.value = response.data.notificationSetups;
        });
};

const deleteMe = (notificationSetup) => {
    if (!confirm("Are you sure you want to delete this notification?")) {
        return;
    }

    axios
        .delete(route("notificationsetup.delete", notificationSetup))
        .then((response) => {
            notificationSetups.value = response.data.notificationSetups;
        });
};
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Notifications
            </h2>
        </template>

        <div class="py-6">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                    <div class="mb-5">
                        Get notified of new applications by setting up
                        notifications below. You can specify the frequency of
                        notifications
                    </div>

                    <div
                        v-if="
                            notificationSetups &&
                            notificationSetups.data.length > 0
                        "
                    >
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Details</th>
                                    <th>Nr. Emails sent</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(
                                        notificationSetup, index
                                    ) in notificationSetups.data"
                                    :key="index"
                                >
                                    <td>
                                        {{ notificationSetup.title }}
                                    </td>
                                    <td>
                                        {{ notificationSetup.days_of_the_week
                                        }}<br />
                                        <span
                                            v-if="
                                                notificationSetups.time_type ==
                                                'specific'
                                            "
                                        >
                                            {{
                                                new Date(
                                                    notificationSetup.time_of_the_day
                                                ).toLocaleTimeString("en-US", {
                                                    hour: "2-digit",
                                                    minute: "2-digit",
                                                    hour12: false,
                                                })
                                            }}<br />
                                        </span>
                                        {{
                                            notificationSetup.additional_emails.join(
                                                ", "
                                            )
                                        }}
                                    </td>

                                    <td>
                                        <span
                                            v-if="
                                                notificationSetup.notification_logs &&
                                                notificationSetup
                                                    .notification_logs.length >
                                                    0
                                            "
                                        >
                                            {{
                                                notificationSetup
                                                    .notification_logs[0].count
                                            }}
                                        </span>
                                    </td>
                                    <td class="nowrap flex">
                                        <NotificationSetups
                                            :rounds="rounds"
                                            :notificationSetup="
                                                Object.assign(
                                                    {},
                                                    notificationSetup
                                                )
                                            "
                                            @update-notification-setup="
                                                upsertNotificationSetup
                                            "
                                            class="mr-1"
                                        >
                                            <SecondaryButton
                                                >Edit</SecondaryButton
                                            >
                                        </NotificationSetups>
                                        <SecondaryButton
                                            @click="deleteMe(notificationSetup)"
                                            >Delete</SecondaryButton
                                        >
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <Pagination :links="notificationSetups.links" />
                    </div>

                    <NotificationSetups
                        class="mb-5"
                        :rounds="rounds"
                        @update-notification-setup="upsertNotificationSetup"
                    >
                        <span class="pointer">
                            <PrimaryButton>Add Notification</PrimaryButton>
                        </span>
                    </NotificationSetups>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
